<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\Analytics\OrderAnalytics;
use App\Filament\Widgets\Analytics\OrderPeriod;
use App\Filament\Widgets\OrdersByHourChart;
use App\Filament\Widgets\OrdersByWeekdayChart;
use App\Filament\Widgets\OrdersChart;
use App\Filament\Widgets\OrderStats;
use App\Filament\Widgets\OrderTypesChart;
use App\Filament\Widgets\TopProducts;
use App\Models\Role;
use App\Models\Shop\Order;
use App\Models\Shop\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class AnalyticsDashboardTest extends TestCase
{
    use RefreshDatabase;

    private const NOW = '2026-10-20 15:00:00';

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(self::NOW);
        CarbonImmutable::setTestNow(self::NOW);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_presets_resolve_to_expected_ranges(): void
    {
        $p = OrderPeriod::fromFilters(['period' => 'last_7']);
        $this->assertSame(['2026-10-14', '2026-10-20'], [$p->from->toDateString(), $p->to->toDateString()]);
        $this->assertSame(['2026-10-07', '2026-10-13'], [$p->previousFrom->toDateString(), $p->previousTo->toDateString()]);
        // період ще триває — попередній обрізаний до того ж часу
        $this->assertSame('15:00:00', $p->previousTo->format('H:i:s'));

        $p = OrderPeriod::fromFilters(['period' => 'last_month']);
        $this->assertSame(['2026-09-01', '2026-09-30'], [$p->from->toDateString(), $p->to->toDateString()]);
        $this->assertSame(['2026-08-01', '2026-08-31'], [$p->previousFrom->toDateString(), $p->previousTo->toDateString()]);

        $p = OrderPeriod::fromFilters(['period' => 'this_year']);
        $this->assertFalse($p->isDaily());
        $this->assertCount(10, $p->buckets());

        $p = OrderPeriod::fromFilters(['period' => 'custom', 'from' => '2026-10-10', 'to' => '2026-10-01']);
        $this->assertSame(['2026-10-01', '2026-10-10'], [$p->from->toDateString(), $p->to->toDateString()]);

        // без дати початку — дефолтний період
        $p = OrderPeriod::fromFilters(['period' => 'custom', 'from' => null]);
        $this->assertSame(7, $p->days());
    }

    public function test_summary_counts_revenue_and_new_customers(): void
    {
        $this->order('2026-09-01 12:00', 500, '+380 99 111 22 33');
        $this->order('2026-10-19 12:00', 400, '0991112233');
        $this->order('2026-10-20 13:00', 600, '+380671234567');

        $p = OrderPeriod::fromFilters(['period' => 'last_7']);
        $summary = OrderAnalytics::summary(OrderAnalytics::orders($p->from, $p->to), $p->from);

        $this->assertSame(2, $summary['count']);
        $this->assertSame(1000.0, $summary['revenue']);
        $this->assertSame(500.0, $summary['average']);
        $this->assertSame(2, $summary['customers']);
        // 099 111 22 33 вже замовляв у вересні (в іншому форматі номера)
        $this->assertSame(1, $summary['newCustomers']);
    }

    public function test_dashboard_page_renders_with_period_filter(): void
    {
        $this->actingAs($this->createManager());

        $this->get('/admin')->assertOk()->assertSee('Аналітика');

        Livewire::test(Dashboard::class)
            ->set('filters.period', 'custom')
            ->assertSee('З')
            ->assertSee('По');
    }

    public function test_widgets_render_for_selected_period(): void
    {
        $this->actingAs($this->createManager());
        $roll = $this->product('Філадельфія');
        $set = $this->product('Сет Токіо');

        $this->order('2026-10-20 12:30', 700, '0991112233', [$roll->id => 3, $set->id => 1]);
        $this->order('2026-10-19 19:10', 300, '0671234567', [$roll->id => 1], 'самовивiz', 'термінал');
        $this->order('2026-08-01 12:00', 999, '0500000000', [$set->id => 9]);

        $filters = ['period' => 'last_7'];

        Livewire::test(OrderStats::class, ['pageFilters' => $filters])
            ->assertSee('Замовлень')
            ->assertSee('1 000 грн')
            ->assertSee('500 грн')
            ->assertSee('50% / 50%');

        Livewire::test(OrdersChart::class, ['pageFilters' => $filters])->assertOk();
        Livewire::test(OrdersChart::class, ['pageFilters' => $filters])->set('filter', 'revenue')->assertOk();
        Livewire::test(OrdersByHourChart::class, ['pageFilters' => $filters])->assertOk();
        Livewire::test(OrdersByWeekdayChart::class, ['pageFilters' => $filters])->assertOk();
        Livewire::test(OrderTypesChart::class, ['pageFilters' => $filters])->assertOk();

        // сет замовили 9 шт у серпні — у тиждень не потрапляє
        Livewire::test(TopProducts::class, ['pageFilters' => $filters])
            ->assertCanSeeTableRecords([$roll, $set], inOrder: true)
            ->assertSee('Філадельфія');

        Livewire::test(TopProducts::class, ['pageFilters' => ['period' => 'custom', 'from' => '2026-08-01', 'to' => '2026-08-01']])
            ->assertCanSeeTableRecords([$set])
            ->assertCanNotSeeTableRecords([$roll]);
    }

    /**
     * @param  array<int, int>  $products
     */
    private function order(string $at, float $sum, string $phone, array $products = [], string $delivery = 'курʼєр', string $payment = 'готівка'): Order
    {
        $order = new Order([
            'customer_name' => 'Тест',
            'customer_phone' => $phone,
            'customer_delivery_type' => $delivery,
            'customer_street' => '',
            'customer_building' => '',
            'online_payment' => $payment,
            'sticks_educational' => 0,
            'sticks_standard' => 1,
            'is_as_soon_as_possible' => true,
            'time' => $at,
            'sum' => $sum,
        ]);
        $order->created_at = $at;
        $order->updated_at = $at;
        $order->save();
        foreach ($products as $id => $quantity) {
            $order->products()->attach($id, ['shop_product_quantity' => $quantity]);
        }

        return $order;
    }

    private function product(string $title): Product
    {
        return Product::query()->create([
            'title' => $title,
            'price' => 300,
            'weight' => 250,
            'count' => 1,
            'main_image' => 'images/products/test.webp',
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
