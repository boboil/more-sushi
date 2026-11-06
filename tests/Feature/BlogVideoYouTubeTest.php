<?php

namespace Tests\Feature;

use App\Filament\Resources\Blog\VideoReels\Pages\EditVideoReel;
use App\Filament\Resources\Blog\VideoScenarios\Pages\EditVideoScenario;
use App\Models\Blog\VideoReel;
use App\Models\Blog\VideoScenario;
use App\Models\Blog\YouTubeConnection;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BlogVideoYouTubeTest extends TestCase
{
    use RefreshDatabase;

    private const CHANNEL = 'UCtestchannel';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const UPLOAD_URL = 'https://www.googleapis.com/upload/youtube/v3/videos*';

    private const SESSION_URL = 'https://upload.test/session/1';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public_root');
        config(['services.youtube' => ['client_id' => 'client', 'client_secret' => 'secret', 'channel_id' => self::CHANNEL]]);
        $this->actingAs($this->createManager());
    }

    public function test_connect_redirects_to_google_with_state(): void
    {
        $response = $this->get('/admin/youtube/connect');

        $response->assertRedirectContains('https://accounts.google.com/o/oauth2/v2/auth');
        $this->assertStringContainsString('access_type=offline', $response->headers->get('Location'));
        $this->assertStringContainsString('youtube.upload', $response->headers->get('Location'));
        $this->assertStringContainsString('state=' . session('youtube.state'), $response->headers->get('Location'));
    }

    public function test_return_address_stays_inside_admin(): void
    {
        $this->from('https://evil.test/page')->get('/admin/youtube/connect');
        $this->assertSame(url('/admin'), session('youtube.return'));

        $this->from(url('/admin/video-scenarios/1/edit'))->get('/admin/youtube/connect');
        $this->assertSame(url('/admin/video-scenarios/1/edit'), session('youtube.return'));
    }

    public function test_guest_cannot_start_connection(): void
    {
        auth()->logout();

        $this->get('/admin/youtube/connect')->assertRedirectContains('/admin/login');
    }

    public function test_callback_saves_encrypted_token_for_our_channel(): void
    {
        $this->fakeGoogle(self::CHANNEL, 'Дари Моря Суші');

        $this->withSession(['youtube.state' => 'abc', 'youtube.return' => url('/admin')])
            ->get('/admin/youtube/callback?state=abc&code=code1')
            ->assertRedirect(url('/admin'));

        $connection = YouTubeConnection::query()->sole();
        $this->assertSame('Дари Моря Суші', $connection->channel_title);
        $this->assertSame('refresh1', $connection->refresh_token);
        $this->assertNotSame('refresh1', $connection->getRawOriginal('refresh_token'));
    }

    public function test_callback_rejects_other_channel(): void
    {
        $this->fakeGoogle('UCpersonal', 'Особистий');

        $this->withSession(['youtube.state' => 'abc'])->get('/admin/youtube/callback?state=abc&code=code1');

        $this->assertSame(0, YouTubeConnection::query()->count());
    }

    public function test_callback_rejects_wrong_state(): void
    {
        Http::fake();

        $this->withSession(['youtube.state' => 'abc'])
            ->get('/admin/youtube/callback?state=other&code=code1')
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_upload_button_needs_connection(): void
    {
        $scenario = $this->scenarioWithVideo();

        Livewire::test(EditVideoScenario::class, ['record' => $scenario->getKey()])
            ->assertActionHidden('youtubeUpload')
            ->assertActionVisible('youtubeConnect');
    }

    public function test_scenario_is_uploaded_with_metadata(): void
    {
        $this->connect();
        $this->fakeUpload();
        $scenario = $this->scenarioWithVideo();

        Livewire::test(EditVideoScenario::class, ['record' => $scenario->getKey()])
            ->mountAction('youtubeUpload')
            ->assertSet('mountedActions.0.data.privacy', 'unlisted')
            ->assertSet('mountedActions.0.data.synthetic', true)
            ->set('mountedActions.0.data.title', 'Новий рол')
            ->callMountedAction()
            ->assertHasNoErrors()
            ->assertNotified('Відео завантажено на YouTube');

        $scenario->refresh();
        $this->assertSame('yt123', $scenario->youtube_video_id);
        $this->assertNotNull($scenario->youtube_uploaded_at);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'uploadType=resumable')
            && $request->hasHeader('Authorization', 'Bearer access1')
            && $request->hasHeader('X-Upload-Content-Length', '3')
            && $request['snippet']['title'] === 'Новий рол'
            && $request['snippet']['categoryId'] === '22'
            && str_contains($request['snippet']['description'], 'moresushi.in.ua')
            && $request['status']['privacyStatus'] === 'unlisted'
            && $request['status']['selfDeclaredMadeForKids'] === false
            && $request['status']['containsSyntheticMedia'] === true);
        Http::assertSent(fn (Request $request) => $request->url() === self::SESSION_URL
            && $request->method() === 'PUT'
            && $request->body() === 'MP4');
    }

    public function test_reel_is_uploaded(): void
    {
        $this->connect();
        $this->fakeUpload();
        Storage::disk('public_root')->put('blog-video/reels/1.mp4', 'MP4');
        $reel = VideoReel::query()->create(['title' => 'Ролик']);
        $reel->forceFill(['video_path' => 'blog-video/reels/1.mp4'])->save();

        Livewire::test(EditVideoReel::class, ['record' => $reel->getKey()])
            ->callAction('youtubeUpload')
            ->assertNotified('Відео завантажено на YouTube');

        $this->assertSame('yt123', $reel->refresh()->youtube_video_id);
    }

    public function test_title_with_angle_brackets_is_rejected(): void
    {
        $this->connect();
        Http::fake();
        $scenario = $this->scenarioWithVideo();

        Livewire::test(EditVideoScenario::class, ['record' => $scenario->getKey()])
            ->mountAction('youtubeUpload')
            ->set('mountedActions.0.data.title', 'Рол <новий>')
            ->callMountedAction()
            ->assertHasFormErrors(['title'], 'mountedActionSchema0');

        Http::assertNothingSent();
    }

    public function test_api_error_is_shown_and_id_not_saved(): void
    {
        $this->connect();
        Http::fake([
            self::TOKEN_URL => Http::response(['access_token' => 'access1']),
            self::UPLOAD_URL => Http::response(['error' => ['message' => 'Quota', 'errors' => [['reason' => 'quotaExceeded']]]], 403),
        ]);
        $scenario = $this->scenarioWithVideo();

        Livewire::test(EditVideoScenario::class, ['record' => $scenario->getKey()])
            ->callAction('youtubeUpload')
            ->assertNotified('YouTube не прийняв відео');

        $this->assertNull($scenario->refresh()->youtube_video_id);
    }

    public function test_revoked_token_drops_connection(): void
    {
        $this->connect();
        Http::fake([self::TOKEN_URL => Http::response(['error' => 'invalid_grant'], 400)]);
        $scenario = $this->scenarioWithVideo();

        Livewire::test(EditVideoScenario::class, ['record' => $scenario->getKey()])
            ->callAction('youtubeUpload')
            ->assertNotified('YouTube не прийняв відео');

        $this->assertSame(0, YouTubeConnection::query()->count());
    }

    private function connect(): void
    {
        YouTubeConnection::query()->create([
            'channel_id' => self::CHANNEL, 'channel_title' => 'Дари Моря Суші', 'refresh_token' => 'refresh1',
        ]);
    }

    private function fakeGoogle(string $channelId, string $title): void
    {
        Http::fake([
            self::TOKEN_URL => Http::response(['access_token' => 'access1', 'refresh_token' => 'refresh1']),
            'https://www.googleapis.com/youtube/v3/channels*' => Http::response([
                'items' => [['id' => $channelId, 'snippet' => ['title' => $title]]],
            ]),
        ]);
    }

    private function fakeUpload(): void
    {
        Http::fake([
            self::TOKEN_URL => Http::response(['access_token' => 'access1']),
            self::UPLOAD_URL => Http::response('', 200, ['Location' => self::SESSION_URL]),
            self::SESSION_URL => Http::response(['id' => 'yt123'], 201),
        ]);
    }

    private function scenarioWithVideo(): VideoScenario
    {
        Storage::disk('public_root')->put('blog-video/videos/1.mp4', 'MP4');
        $scenario = VideoScenario::query()->create(['title' => 'Рол', 'lines' => []]);
        $scenario->forceFill(['status' => VideoScenario::STATUS_DONE, 'video_path' => 'blog-video/videos/1.mp4'])->save();

        return $scenario;
    }

    private function createManager(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->create(['name' => 'manager', 'label' => 'Менеджер']));
        $user->unsetRelation('roles');

        return $user;
    }
}
