<?php

namespace App\Models\Blog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Сценарій відео: сцена, стиль і репліки персонажів.
 *
 * @property int $id
 * @property string $title
 * @property string|null $setting
 * @property string|null $style
 * @property string|null $start_frame перший кадр відео; з ним фото персонажів не відправляються
 * @property array|null $lines [{character_id, direction, text}]
 * @property array|null $extensions продовження: [{lines, instructions, prompt, status, duration}]
 * @property bool $extending поточна задача — продовження готового відео
 * @property string|null $extra_instructions
 * @property array|null $extra_references
 * @property string $language
 * @property string $aspect_ratio
 * @property string $resolution
 * @property string $status draft|processing|done|failed
 * @property string|null $interaction_id остання вдала (або поточна) задача — від неї продовжується ланцюжок
 * @property string|null $previous_interaction_id задача до поточного продовження, щоб відкотитись при помилці
 * @property string|null $file_id
 * @property string|null $video_path
 * @property float|null $duration секунди
 * @property float $cost USD, усього витрачено на сценарій (всі генерації й продовження)
 * @property float|null $last_cost USD, остання генерація чи продовження
 * @property array|null $last_usage usage з відповіді Gemini
 * @property string|null $last_prompt
 * @property string|null $error
 * @property \Illuminate\Support\Carbon|null $generated_at
 */
class VideoScenario extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';

    /** Gemini подовжує згенероване відео максимум до ~40 секунд загалом. */
    public const MAX_DURATION = 40;

    public const STATUSES = [
        self::STATUS_DRAFT => 'Чернетка',
        self::STATUS_PROCESSING => 'Генерується',
        self::STATUS_DONE => 'Готово',
        self::STATUS_FAILED => 'Помилка',
    ];

    protected $table = 'blog_video_scenarios';

    protected $fillable = [
        'title', 'setting', 'style', 'start_frame', 'lines', 'extra_instructions', 'extra_references',
        'language', 'aspect_ratio', 'resolution',
    ];

    protected $attributes = [
        'language' => 'українська',
        'aspect_ratio' => '9:16',
        'resolution' => '720p',
        'status' => self::STATUS_DRAFT,
    ];

    protected $casts = [
        'lines' => 'array',
        'extensions' => 'array',
        'extending' => 'boolean',
        'duration' => 'float',
        'cost' => 'float',
        'last_cost' => 'float',
        'last_usage' => 'array',
        'extra_references' => 'array',
        'generated_at' => 'datetime',
    ];

    /**
     * Персонажі в порядку першої появи в репліках.
     */
    public function characters(): Collection
    {
        $ids = collect($this->lines ?? [])->pluck('character_id')->filter()->unique()->values();
        $characters = VideoCharacter::query()->whereIn('id', $ids)->get()->keyBy('id');

        return $ids->map(fn ($id) => $characters->get($id))->filter()->values();
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function canBeExtended(): bool
    {
        return $this->status === self::STATUS_DONE
            && $this->interaction_id
            && $this->video_path
            && ($this->duration ?? 0) < self::MAX_DURATION - 5;
    }
}
