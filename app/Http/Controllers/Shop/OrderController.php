<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Utils\PosterAuthController;
use App\Models\Shop\Order;
use App\Models\Landing\Order as LOrder;
use App\Models\Shop\Product;
use Carbon\Carbon;
use App\Services\Notifications\PosterOrderAlert;
use App\Services\Promo\PromoDays;
use App\Services\Statistics\OrderAttribution;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class OrderController extends Controller
{
    public function addOrder(Request $request)
    {
        $validatedData = $request->validate([
            'customer.name' => 'required|string|max:255',
            'customer.phone' => ['required', 'regex:/^\+?[0-9]{9,15}$/'],
            'customer.delivery' => 'required|string',
            'customer.street' => 'nullable|string|max:255',
            'customer.building' => 'nullable|string|max:255',
            'customer.payment' => 'required|string',
            'customer.sticks.educational' => 'nullable|integer',
            'customer.sticks.standard' => 'nullable|integer',
            'customer.time.day' => 'nullable|date_format:Y-m-d',
            'customer.time.time' => 'nullable|date_format:H:i',
            'customer.isAsSoonAsPossible' => 'required|string',
            'products' => 'required|array',
            'sum' => 'required|numeric|min:0',
        ]);
        $customer = collect($validatedData['customer']);
        $customer['phone'] = $this->formatPhoneNumber($validatedData['customer']['phone']);
        $products = collect($validatedData['products']);
        $posterOrderProducts = [];
        foreach ($products as $product) {
            $item = Product::find($product['id']);
            $posterOrderProducts[] = ['product_id' => $item->poster_id, 'count' => $product['quantity']];
        }
        $service_type = $customer['delivery'] === 'курʼєр' ? 3 : 1;
        $delivery = [
            'service_mode' => 1,
            'client_address' => null
        ];
        if ($service_type === 3) {
            $delivery = [
                'service_mode' => 3,
                'client_address' => [
                    'address1' => $customer['street'],
                    'address2' => $customer['building']
                ]
            ];
        }
        $comment = $this->commentToPosterOrder($customer);
        if ($promoLine = $this->promoDayComment($products->pluck('id')->all(), $request->input('sum'))) {
            $comment = rtrim($comment) . PHP_EOL . $promoLine;
        }
        $posterUtil = app(PosterAuthController::class); // через контейнер, щоб у тестах не ходити в Poster
        $posterOrder = $posterUtil->createIncomingOrder($posterOrderProducts, $customer, $delivery, $comment);

        $time = Carbon::parse($customer['time']['day'] . $customer['time']['time']);
        $sum = $request->input('sum');
        $order = new Order();
        $order->customer_name = $customer['name'];
        $order->customer_phone = $customer['phone'];
        $order->customer_delivery_type = $customer['delivery'];
        $order->customer_street = $customer['street'];
        $order->customer_building = $customer['building'];
        $order->online_payment = $customer['payment'];
        $order->sticks_educational = $customer['sticks']['educational'];
        $order->sticks_standard = $customer['sticks']['standard'];
        $order->is_as_soon_as_possible = $customer['isAsSoonAsPossible'] == 'true' ? 1 : 0;
        $order->time = $time->toDateTime();
        $order->sum = $sum;
        // звідки прийшов відвідувач (UTM / реферер); у Poster не передаємо
        $order->attribution = OrderAttribution::sanitize($request->input('attribution'));
        $order->save();
        foreach ($products as $product) {
            $order->products()->attach($product['id'], ['shop_product_quantity' => $product['quantity']]);
        }
        // Клієнту все одно відповідаємо «прийнято», а оператору шлемо тривогу в Telegram
        if (! PosterOrderAlert::accepted($posterOrder)) {
            app(PosterOrderAlert::class)->report($order, $posterOrder);
        }
        return new JsonResponse([
            'data' => $order,
            'posterOrder' => $posterOrder
        ]);
    }

    public function commentToPosterOrder($customer): string
    {
        $delivery = $customer['delivery'] === 'курʼєр' ? $customer['delivery'].': '.$customer['street'] . ' ' . $customer['building'] : $customer['delivery'];
        $time = Carbon::parse($customer['time']['day'] . $customer['time']['time']);
        $comment = "Заказ с сайта" .PHP_EOL;
        $comment .= "Клиент: " . $customer['name'].PHP_EOL;
        $comment .= $delivery .PHP_EOL;
        $comment .= "Оплата: " . $customer['payment'].PHP_EOL;
        $comment .= "Палочки: " . $customer['sticks']['standard'] .' стандартных.'. $customer['sticks']['educational'].' учебных.'.PHP_EOL;
        $comment .= $customer['isAsSoonAsPossible'] == 'true' ? 'Как можно быстрее' : 'На время: '. $time->format('d-m-Y H:i').PHP_EOL;
        return $comment;
    }

    /**
     * Рядок для оператора, якщо зараз діє «Акція дня» і вона стосується замовлення:
     * Poster рахує за своїми цінами, тож знижку з сайту оператор має бачити явно.
     */
    public function promoDayComment(array $productIds, mixed $sum): string
    {
        $promo = app(PromoDays::class)->current();
        if (! $promo) {
            return '';
        }
        $matched = $promo->products->whereIn('id', $productIds);
        // знижка діє лише на товари дня; подарунок без списку товарів — на будь-яке замовлення
        if ($matched->isEmpty() && ($promo->isPercent() || $promo->products->isNotEmpty())) {
            return '';
        }
        $offer = $promo->isPercent() ? "-{$promo->discount_percent}% {$promo->offer_title}" : trim(($promo->gift_badge ? $promo->gift_badge . ' ' : '') . $promo->offer_title);
        $line = 'АКЦІЯ ДНЯ (' . $promo->label() . "): {$offer}";
        if ($matched->isNotEmpty()) {
            $line .= '. Товари: ' . $matched->pluck('title')->map(fn ($t) => trim($t))->implode(', ');
        }

        return $line . '. Сума з сайту: ' . $sum . ' грн';
    }

    public function index()
    {
        $orders = Order::getTodayOrders();
        dd($orders);
    }

    public function addLandingOrder(Request $request)
    {
        $customer = collect($request->input('customer'));
        $products = collect($request->input('products'));
        $sum = 0;
        foreach ($products as $product) {
            $sum += $product['price'] * $product['quantity'];
        }
        $time = Carbon::parse('31-12-2023 ' . $customer['time']);
        $order = new LOrder();
        $order->name = $customer['name'];
        $order->phone = $this->formatPhoneNumber($customer['phone']);
        $order->address = $customer['address'];
        $order->time = $time->toDateTime();
        $order->sum = $sum;
        $order->save();
        foreach ($products as $product) {
            $order->products()->attach($product['id'], ['shop_product_quantity' => $product['quantity']]);
        }
        return new JsonResponse([
            'data' => $order
        ]);
    }
    private function formatPhoneNumber($phone_number): string
    {
        $phone = preg_replace('/\D/', '', $phone_number);
        if (str_starts_with($phone, '380')) {
            $phone = '380' . substr($phone, 3);
        } elseif (str_starts_with($phone, '0')) {
            $phone = '380' . substr($phone, 1);
        } else {
            $phone = '380' . $phone;
        }
        return $phone;
    }
}
