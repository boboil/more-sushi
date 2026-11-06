<?php

namespace App\Models\Blog;

use Illuminate\Database\Eloquent\Model;

/**
 * Підключений канал YouTube, у який адмінка завантажує відео.
 *
 * @property int $id
 * @property string $channel_id
 * @property string $channel_title
 * @property string $refresh_token
 */
class YouTubeConnection extends Model
{
    protected $table = 'blog_youtube_connections';

    protected $fillable = ['channel_id', 'channel_title', 'refresh_token'];

    protected $hidden = ['refresh_token'];

    protected $casts = [
        'refresh_token' => 'encrypted',
    ];
}
