<?php

namespace Tests\Feature;

use App\Filament\Resources\Blog\VideoReels\Pages\EditVideoReel;
use App\Filament\Resources\Blog\VideoScenarios\Pages\CreateVideoScenario;
use App\Filament\Resources\Blog\VideoScenarios\Pages\EditVideoScenario;
use App\Models\Blog\VideoCharacter;
use App\Models\Blog\VideoReel;
use App\Models\Blog\VideoScenario;
use App\Models\Role;
use App\Models\User;
use App\Services\GeminiVideoService;
use App\Services\VideoTools;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Livewire\Livewire;
use Tests\TestCase;

class BlogVideoTest extends TestCase
{
    use RefreshDatabase;

    private const API = 'https://generativelanguage.googleapis.com/v1beta';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public_root');
        config(['services.gemini.key' => 'test-key']);
    }

    public function test_admin_pages_open(): void
    {
        $this->actingAs($this->createManager());
        $scenario = $this->createScenario();

        $this->get('/admin/video-scenarios')->assertOk();
        $this->get('/admin/video-scenarios/create')->assertOk();
        $this->get("/admin/video-scenarios/{$scenario->id}/edit")->assertOk();
        $this->get('/admin/video-characters')->assertOk();
        $this->get('/admin/video-characters/create')->assertOk();
    }

    public function test_admin_can_create_scenario_with_dialogue(): void
    {
        $this->actingAs($this->createManager());
        $anna = VideoCharacter::query()->create(['name' => 'Аня']);

        Livewire::test(CreateVideoScenario::class)
            ->set('data.title', 'Новий сет')
            ->set('data.lines', [['character_id' => $anna->id, 'direction' => 'усміхається', 'text' => 'Привіт!']])
            ->call('create')
            ->assertHasNoErrors();

        $scenario = VideoScenario::query()->where('title', 'Новий сет')->firstOrFail();
        $this->assertSame('Привіт!', $scenario->lines[0]['text']);
        $this->assertSame(VideoScenario::STATUS_DRAFT, $scenario->status);
    }

    public function test_prompt_references_character_images_and_dialogue(): void
    {
        $scenario = $this->createScenario();

        ['prompt' => $prompt, 'images' => $images] = app(GeminiVideoService::class)->buildPrompt($scenario);

        $this->assertSame(['blog-video/references/anna.jpg', 'blog-video/references/kitchen.jpg'], $images);
        $this->assertStringContainsString('Аня — looks exactly like the person in <IMAGE_REF_0>', $prompt);
        $this->assertStringContainsString('<IMAGE_REF_1> as reference for the location', $prompt);
        $this->assertStringContainsString('spoken in Ukrainian', $prompt);
        $this->assertStringContainsString('Аня (усміхається): "Спробуйте наш новий рол!"', $prompt);
        $this->assertStringContainsString('Олег (кусає рол)', $prompt);
    }

    public function test_start_frame_replaces_character_photos(): void
    {
        $scenario = $this->createScenario();
        $scenario->update(['start_frame' => 'blog-video/references/kitchen.jpg', 'extra_references' => []]);

        ['prompt' => $prompt, 'images' => $images] = app(GeminiVideoService::class)->buildPrompt($scenario);

        $this->assertSame(['blog-video/references/kitchen.jpg'], $images);
        $this->assertStringContainsString('Use <IMAGE_REF_0> as the exact first frame', $prompt);
        $this->assertStringNotContainsString('looks exactly like', $prompt);
        $this->assertStringContainsString('Олег (кусає рол) — silent, no speech', $prompt);
        $this->assertStringContainsString('Only Аня speaks; everyone else stays silent.', $prompt);
    }

    public function test_generate_starts_background_interaction(): void
    {
        $this->actingAs($this->createManager());
        $scenario = $this->createScenario();
        Http::fake([self::API . '/interactions' => Http::response(['id' => 'v1_abc', 'status' => 'in_progress'])]);

        Livewire::test(EditVideoScenario::class, ['record' => $scenario->getKey()])
            ->callAction('generate')
            ->assertHasNoErrors();

        $scenario->refresh();
        $this->assertSame(VideoScenario::STATUS_PROCESSING, $scenario->status);
        $this->assertSame('v1_abc', $scenario->interaction_id);
        $this->assertNotEmpty($scenario->last_prompt);

        Http::assertSent(function (Request $request) {
            return $request->hasHeader('x-goog-api-key', 'test-key')
                && $request['model'] === 'gemini-omni-1.1-flash'
                && $request['background'] === true
                && $request['response_format']['aspect_ratio'] === '9:16'
                && $request['response_format']['delivery'] === 'uri'
                && count($request['input']) === 3
                && $request['input'][0]['type'] === 'image'
                && $request['input'][2]['type'] === 'text';
        });
    }

    public function test_api_error_keeps_scenario_draft(): void
    {
        $this->actingAs($this->createManager());
        $scenario = $this->createScenario();
        Http::fake([self::API . '/interactions' => Http::response(['error' => ['message' => 'Quota exceeded']], 429)]);

        Livewire::test(EditVideoScenario::class, ['record' => $scenario->getKey()])
            ->callAction('generate')
            ->assertNotified('Gemini відхилив запит');

        $this->assertSame(VideoScenario::STATUS_DRAFT, $scenario->refresh()->status);
    }

    public function test_poll_downloads_finished_video(): void
    {
        $scenario = $this->createScenario();
        $scenario->forceFill(['status' => VideoScenario::STATUS_PROCESSING, 'interaction_id' => 'v1_abc'])->save();

        Http::fake([
            self::API . '/interactions/v1_abc' => Http::sequence()
                ->push(['id' => 'v1_abc', 'status' => 'in_progress'])
                ->push(['id' => 'v1_abc', 'status' => 'completed', 'steps' => [
                    ['type' => 'model_output', 'content' => [
                        ['type' => 'video', 'mime_type' => 'video/mp4', 'uri' => self::API . '/files/vid123:download?alt=media'],
                    ]],
                ]]),
            self::API . '/files/vid123:download*' => Http::response('MP4BYTES'),
            self::API . '/files/vid123' => Http::sequence()
                ->push(['name' => 'files/vid123', 'state' => 'PROCESSING'])
                ->push(['name' => 'files/vid123', 'state' => 'ACTIVE']),
        ]);

        $gemini = app(GeminiVideoService::class);

        $this->assertFalse($gemini->refresh($scenario));
        $this->assertFalse($gemini->refresh($scenario));
        $this->assertSame('vid123', $scenario->file_id);
        $this->assertTrue($gemini->refresh($scenario));

        $this->assertSame(VideoScenario::STATUS_DONE, $scenario->status);
        Storage::disk('public_root')->assertExists($scenario->video_path);
        $this->assertSame('MP4BYTES', Storage::disk('public_root')->get($scenario->video_path));
    }

    public function test_cost_is_recorded_from_usage(): void
    {
        // usage реальної генерації 360p / 8 с.
        $usage = [
            'total_input_tokens' => 1534,
            'total_output_tokens' => 15448,
            'output_tokens_by_modality' => [['modality' => 'video', 'tokens' => 15448]],
            'total_thought_tokens' => 310,
        ];
        $this->assertSame(0.2754, app(GeminiVideoService::class)->cost($usage));

        $scenario = $this->createScenario();
        $scenario->forceFill(['status' => VideoScenario::STATUS_PROCESSING, 'interaction_id' => 'v1_abc', 'cost' => 0.5])->save();
        Http::fake([self::API . '/interactions/v1_abc' => Http::response([
            'status' => 'completed',
            'usage' => $usage,
            'steps' => [['type' => 'model_output', 'content' => [['type' => 'video', 'mime_type' => 'video/mp4', 'data' => base64_encode('MP4')]]]],
        ])]);

        app(GeminiVideoService::class)->refresh($scenario);

        $this->assertSame(0.2754, $scenario->last_cost);
        $this->assertSame(0.7754, $scenario->cost);
        $this->assertSame(1534, $scenario->last_usage['total_input_tokens']);
    }

    public function test_poll_marks_failed_interaction(): void
    {
        $scenario = $this->createScenario();
        $scenario->forceFill(['status' => VideoScenario::STATUS_PROCESSING, 'interaction_id' => 'v1_abc'])->save();
        Http::fake([self::API . '/interactions/v1_abc' => Http::response(['id' => 'v1_abc', 'status' => 'failed', 'error' => ['message' => 'Safety filter']])]);

        $this->assertTrue(app(GeminiVideoService::class)->refresh($scenario));
        $this->assertSame(VideoScenario::STATUS_FAILED, $scenario->status);
        $this->assertSame('Safety filter', $scenario->error);
    }

    public function test_extend_continues_previous_interaction(): void
    {
        $this->actingAs($this->createManager());
        $scenario = $this->createFinishedScenario(2);
        Http::fake([self::API . '/interactions' => Http::response(['id' => 'v1_ext', 'status' => 'in_progress'])]);
        $anna = VideoCharacter::query()->where('name', 'Аня')->firstOrFail();

        Livewire::test(EditVideoScenario::class, ['record' => $scenario->getKey()])
            ->mountAction('extend')
            ->set('mountedActions.0.data.lines', [['character_id' => $anna->id, 'direction' => 'нарізає рол', 'text' => 'А тепер нарізаємо!']])
            ->set('mountedActions.0.data.instructions', 'She slices the roll.')
            ->callMountedAction()
            ->assertHasNoErrors();

        $scenario->refresh();
        $this->assertSame(VideoScenario::STATUS_PROCESSING, $scenario->status);
        $this->assertTrue($scenario->extending);
        $this->assertSame('v1_ext', $scenario->interaction_id);
        $this->assertSame('v1_base', $scenario->previous_interaction_id);
        $this->assertCount(1, $scenario->extensions);

        Http::assertSent(fn (Request $request) => $request['previous_interaction_id'] === 'v1_base'
            && count($request['input']) === 1
            && str_contains($request['input'][0]['text'], 'Extend this video')
            && str_contains($request['input'][0]['text'], 'Аня (нарізає рол): "А тепер нарізаємо!"')
            && str_contains($request['input'][0]['text'], 'She slices the roll.'));
    }

    public function test_extension_segment_is_joined_to_previous_video(): void
    {
        $this->requireFfmpeg();
        $scenario = $this->startedExtension($this->createFinishedScenario(2));
        $this->fakeFinishedInteraction('v1_ext', $this->makeClip(1));

        $this->assertTrue(app(GeminiVideoService::class)->refresh($scenario));

        $this->assertSame(VideoScenario::STATUS_DONE, $scenario->status);
        $this->assertFalse($scenario->extending);
        $this->assertSame('v1_ext', $scenario->interaction_id);
        $this->assertStringEndsWith('-joined.mp4', $scenario->video_path);
        $this->assertEqualsWithDelta(3, $scenario->duration, 0.3);
        $this->assertSame(VideoScenario::STATUS_DONE, $scenario->extensions[0]['status']);
        $this->assertEqualsWithDelta(1, $scenario->extensions[0]['duration'], 0.3);
    }

    public function test_full_extended_video_is_used_as_is(): void
    {
        $this->requireFfmpeg();
        $scenario = $this->startedExtension($this->createFinishedScenario(1));
        $this->fakeFinishedInteraction('v1_ext', $this->makeClip(3));

        app(GeminiVideoService::class)->refresh($scenario);

        $this->assertStringEndsNotWith('-joined.mp4', $scenario->video_path);
        $this->assertEqualsWithDelta(3, $scenario->duration, 0.3);
        $this->assertEqualsWithDelta(2, $scenario->extensions[0]['duration'], 0.3);
    }

    public function test_failed_extension_keeps_previous_video(): void
    {
        $scenario = $this->createFinishedScenario(2);
        $video = $scenario->video_path;
        $scenario = $this->startedExtension($scenario);
        Http::fake([self::API . '/interactions/v1_ext' => Http::response(['status' => 'failed', 'error' => ['message' => 'Safety filter']])]);

        app(GeminiVideoService::class)->refresh($scenario);

        $this->assertSame(VideoScenario::STATUS_DONE, $scenario->status);
        $this->assertSame($video, $scenario->video_path);
        $this->assertSame('v1_base', $scenario->interaction_id);
        $this->assertStringContainsString('Safety filter', $scenario->error);
        $this->assertSame(VideoScenario::STATUS_FAILED, $scenario->extensions[0]['status']);
    }

    public function test_reel_joins_scenario_videos_in_order(): void
    {
        $this->requireFfmpeg();
        $this->actingAs($this->createManager());
        $a = $this->createFinishedScenario(1);
        $b = $this->createFinishedScenario(2);
        $reel = VideoReel::query()->create(['title' => 'Хвилинка', 'items' => [
            ['scenario_id' => $a->id], ['scenario_id' => $b->id], ['scenario_id' => $a->id],
        ]]);

        $this->get('/admin/video-reels')->assertOk();
        $this->get("/admin/video-reels/{$reel->id}/edit")->assertOk();

        Livewire::test(EditVideoReel::class, ['record' => $reel->getKey()])
            ->callAction('build')
            ->assertNotified('Ролик зібрано');

        $reel->refresh();
        Storage::disk('public_root')->assertExists($reel->video_path);
        $this->assertEqualsWithDelta(4, $reel->duration, 0.4);
    }

    public function test_reel_with_missing_video_file_reports_error(): void
    {
        $this->actingAs($this->createManager());
        $draft = $this->createFinishedScenario(1);
        Storage::disk('public_root')->delete($draft->video_path);
        $reel = VideoReel::query()->create(['title' => 'Порожній', 'items' => [['scenario_id' => $draft->id]]]);

        Livewire::test(EditVideoReel::class, ['record' => $reel->getKey()])
            ->callAction('build')
            ->assertNotified('Не вдалося зібрати ролик');

        $this->assertStringContainsString($draft->title, $reel->refresh()->error);
    }

    private function createFinishedScenario(int $seconds): VideoScenario
    {
        $scenario = $this->createScenario();
        $path = 'blog-video/videos/base-' . Str::random(6) . '.mp4';
        Storage::disk('public_root')->put($path, $this->hasFfmpeg() ? $this->makeClip($seconds) : 'MP4');
        $scenario->forceFill([
            'status' => VideoScenario::STATUS_DONE,
            'interaction_id' => 'v1_base',
            'video_path' => $path,
            'duration' => $seconds,
        ])->save();

        return $scenario;
    }

    private function startedExtension(VideoScenario $scenario): VideoScenario
    {
        Http::fake([self::API . '/interactions' => Http::response(['id' => 'v1_ext', 'status' => 'in_progress'])]);
        app(GeminiVideoService::class)->extend($scenario, [['character_id' => null, 'direction' => '', 'text' => 'Далі']]);

        return $scenario;
    }

    private function fakeFinishedInteraction(string $id, string $bytes): void
    {
        Http::fake([self::API . "/interactions/{$id}" => Http::response(['id' => $id, 'status' => 'completed', 'steps' => [
            ['type' => 'model_output', 'content' => [['type' => 'video', 'mime_type' => 'video/mp4', 'data' => base64_encode($bytes)]]],
        ]])]);
    }

    private function makeClip(int $seconds): string
    {
        $path = tempnam(sys_get_temp_dir(), 'clip') . '.mp4';
        Process::run(['ffmpeg', '-v', 'error', '-y', '-f', 'lavfi', '-i', "testsrc=size=180x320:rate=24:duration={$seconds}",
            '-f', 'lavfi', '-i', "sine=frequency=440:duration={$seconds}", '-c:v', 'libx264', '-pix_fmt', 'yuv420p',
            '-c:a', 'aac', '-shortest', $path])->throw();
        $bytes = file_get_contents($path);
        @unlink($path);

        return $bytes;
    }

    private function hasFfmpeg(): bool
    {
        return app(VideoTools::class)->available();
    }

    private function requireFfmpeg(): void
    {
        if (! $this->hasFfmpeg()) {
            $this->markTestSkipped('ffmpeg не встановлено');
        }
    }

    private function createScenario(): VideoScenario
    {
        $disk = Storage::disk('public_root');
        foreach (['anna', 'kitchen'] as $name) {
            $disk->put("blog-video/references/{$name}.jpg", (string) Image::canvas(40, 40, '#ff8800')->encode('jpg'));
        }

        $anna = VideoCharacter::query()->create([
            'name' => 'Аня',
            'description' => 'руде каре, фартух',
            'reference_images' => ['blog-video/references/anna.jpg'],
        ]);
        $oleg = VideoCharacter::query()->create(['name' => 'Олег']);

        return VideoScenario::query()->create([
            'title' => 'Новий рол ' . Str::random(4),
            'setting' => 'Кухня ресторану',
            'lines' => [
                ['character_id' => $anna->id, 'direction' => 'усміхається', 'text' => 'Спробуйте наш новий рол!'],
                ['character_id' => $oleg->id, 'direction' => 'кусає рол', 'text' => ''],
            ],
            'extra_references' => ['blog-video/references/kitchen.jpg'],
        ]);
    }

    private function createManager(): User
    {
        $user = User::factory()->create();
        $role = Role::query()->create(['name' => 'manager', 'label' => 'Менеджер']);
        $user->roles()->attach($role);
        $user->unsetRelation('roles');

        return $user;
    }
}
