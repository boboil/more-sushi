<?php

namespace App\Services\Notifications;

use App\Models\Shop\Order;
use Illuminate\Support\Facades\Log;

/**
 * Замовлення з сайту, яке Poster не прийняв: клієнт бачить «замовлення прийнято»,
 * тому оператор має дізнатися про нього з Telegram і передзвонити.
 */
class PosterOrderAlert
{
    public function __construct(private readonly TelegramNotifier $telegram) {}

    /** Poster прийняв замовлення, якщо повернув його номер. */
    public static function accepted(mixed $posterResponse): bool
    {
        return is_array($posterResponse) && ! empty($posterResponse['response']['incoming_order_id']);
    }

    public function report(Order $order, mixed $posterResponse): void
    {
        Log::error('Poster rejected site order', ['order_id' => $order->id, 'poster' => $posterResponse]);

        $this->telegram->send($this->message($order, $posterResponse));
    }

    public function message(Order $order, mixed $posterResponse): string
    {
        $e = fn ($v) => htmlspecialchars(trim((string) $v), ENT_QUOTES);
        $order->loadMissing('products');

        $lines = [
            "⚠️ <b>Замовлення #{$order->id} з сайту НЕ потрапило в Poster</b>",
            'Клієнт бачив «замовлення прийнято» — треба передзвонити.',
            '',
            "<b>Клієнт:</b> {$e($order->customer_name)}",
            "<b>Телефон:</b> +{$e(ltrim($order->customer_phone, '+'))}",
            '<b>Отримання:</b> ' . $e($order->customer_delivery_type)
                . (filled($order->customer_street) ? ', ' . $e(trim($order->customer_street . ' ' . $order->customer_building)) : ''),
            "<b>Оплата:</b> {$e($order->online_payment)}",
            '<b>Час:</b> ' . ($order->is_as_soon_as_possible ? 'якомога швидше' : $order->time?->format('d.m H:i')),
            '<b>Палички:</b> ' . (int) $order->sticks_standard . ' стандартних, ' . (int) $order->sticks_educational . ' учбових',
            '',
            '<b>Товари:</b>',
        ];
        foreach ($order->products as $product) {
            $lines[] = "• {$e($product->title)} × {$product->pivot->shop_product_quantity}" . ($product->poster_id ? " <i>(Poster {$product->poster_id})</i>" : ' <i>(без Poster ID)</i>');
        }
        $lines[] = '';
        $lines[] = "<b>Сума:</b> {$e($order->sum)} грн";
        $lines[] = '<b>Відповідь Poster:</b> <code>' . $e($this->posterError($posterResponse)) . '</code>';

        return implode("\n", $lines);
    }

    private function posterError(mixed $response): string
    {
        if (is_string($response)) {
            return mb_substr($response, 0, 300);
        }
        if (is_array($response) && (isset($response['error']) || isset($response['message']))) {
            return mb_substr(json_encode(array_intersect_key($response, array_flip(['error', 'message'])), JSON_UNESCAPED_UNICODE), 0, 300);
        }

        return mb_substr((string) json_encode($response, JSON_UNESCAPED_UNICODE), 0, 300) ?: 'порожня відповідь';
    }
}
