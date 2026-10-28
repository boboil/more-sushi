<?php

namespace App\Models\Blog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Ролик, склеєний з готових відео сценаріїв.
 *
 * @property int $id
 * @property string $title
 * @property array|null $items [{scenario_id}] у порядку показу
 * @property string|null $video_path
 * @property float|null $duration
 * @property string|null $error
 * @property \Illuminate\Support\Carbon|null $built_at
 */
class VideoReel extends Model
{
    protected $table = 'blog_video_reels';

    protected $fillable = ['title', 'items'];

    protected $casts = [
        'items' => 'array',
        'duration' => 'float',
        'built_at' => 'datetime',
    ];

    /**
     * Сценарії в порядку ролика (повтори дозволені).
     */
    public function scenarios(): Collection
    {
        $ids = collect($this->items ?? [])->pluck('scenario_id')->filter();
        $scenarios = VideoScenario::query()->whereIn('id', $ids->unique())->get()->keyBy('id');

        return $ids->map(fn ($id) => $scenarios->get($id))->filter()->values();
    }
}
