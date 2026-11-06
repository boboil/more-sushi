<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'video_model' => env('GEMINI_VIDEO_MODEL', 'gemini-omni-1.2-flash'),
        // Ціни Standard tier, USD за 1M токенів (aistudio.google.com/docs/pricing, 06.11.2026).
        'prices' => [
            'input' => (float) env('GEMINI_PRICE_INPUT', 1.50),
            'output_video' => (float) env('GEMINI_PRICE_OUTPUT_VIDEO', 12.00),
            'output_text' => (float) env('GEMINI_PRICE_OUTPUT_TEXT', 9.00),
        ],
    ],

    'youtube' => [
        'client_id' => env('YOUTUBE_CLIENT_ID'),
        'client_secret' => env('YOUTUBE_CLIENT_SECRET'),
        // Канал «Дари Моря Суші»: інший канал цього акаунта підключити не можна.
        'channel_id' => env('YOUTUBE_CHANNEL_ID', 'UCSMjVdmMHYrtbW9rWWpEnMA'),
    ],

    'ffmpeg' => [
        'ffmpeg' => env('FFMPEG_BIN', 'ffmpeg'),
        'ffprobe' => env('FFPROBE_BIN', 'ffprobe'),
    ],

];
