<?php

namespace App\Models\Blog;

use Illuminate\Database\Eloquent\Model;

/**
 * Персонаж для відео в блог: опис зовнішності/голосу і референсні фото.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $voice
 * @property array|null $reference_images шляхи на диску public_root
 */
class VideoCharacter extends Model
{
    protected $table = 'blog_video_characters';

    protected $fillable = ['name', 'description', 'voice', 'reference_images'];

    protected $casts = ['reference_images' => 'array'];
}
