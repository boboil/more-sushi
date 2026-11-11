<?php

namespace Tests\Unit;

use App\Services\Statistics\OrderAttribution;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OrderAttributionTest extends TestCase
{
    public static function channels(): array
    {
        return [
            'ChatGPT ad' => [['source' => 'chatgpt', 'medium' => 'cpc', 'campaign' => 'rolls'], 'chatgpt / cpc'],
            'ChatGPT answer link (it adds utm_source itself)' => [['source' => 'chatgpt.com'], 'chatgpt.com'],
            'case and www are normalised' => [['source' => 'Google', 'medium' => 'CPC'], 'google / cpc'],
            'organic search referrer' => [['referrer' => 'www.google.com.ua'], 'google / organic'],
            'other referrer' => [['referrer' => 'chatgpt.com'], 'chatgpt.com / referral'],
            'click id only' => [['click_id_type' => 'gclid'], 'gclid / click'],
            'direct' => [['landing_page' => '/'], OrderAttribution::DIRECT],
        ];
    }

    #[DataProvider('channels')]
    public function test_channel_label(array $attribution, string $expected): void
    {
        $this->assertSame($expected, OrderAttribution::channel($attribution));
    }

    public function test_orders_without_attribution_are_untracked(): void
    {
        $this->assertSame(OrderAttribution::UNTRACKED, OrderAttribution::channel(null));
        $this->assertSame(OrderAttribution::UNTRACKED, OrderAttribution::channel([]));
    }

    public function test_campaign_label_keeps_original_case(): void
    {
        $this->assertSame('chatgpt · Rolls_Nov', OrderAttribution::campaign(['source' => 'ChatGPT', 'campaign' => 'Rolls_Nov']));
        $this->assertNull(OrderAttribution::campaign(['source' => 'chatgpt']));
        $this->assertNull(OrderAttribution::campaign(null));
    }

    public function test_sanitize_keeps_known_short_strings_only(): void
    {
        $clean = OrderAttribution::sanitize([
            'source' => " chatgpt\n",
            'medium' => 'cpc',
            'campaign' => str_repeat('x', 500),
            'email' => 'someone@example.com',
            'term' => ['nested'],
            'content' => '',
        ]);

        $this->assertSame(['source', 'medium', 'campaign'], array_keys($clean));
        $this->assertSame('chatgpt', $clean['source']);
        $this->assertSame(200, mb_strlen($clean['campaign']));
        $this->assertNull(OrderAttribution::sanitize('utm_source=chatgpt'));
        $this->assertNull(OrderAttribution::sanitize(['email' => 'x@example.com']));
    }
}
