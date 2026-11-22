<?php

namespace Tests\Feature;

use App\Filament\Resources\Shop\Orders\Pages\EditOrder;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Pages\DeliverySettings;
use App\Models\Role;
use App\Models\Shop\Order;
use App\Models\Shop\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;

class FilamentAdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_admin_or_manager_role_cannot_access_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')->assertForbidden();

        $admin = $this->createAdmin();

        $this->actingAs($admin)->get('/admin')->assertOk();

    }

    public function test_manager_can_manage_shop_but_not_personnel(): void
    {
        $manager = $this->createUserWithRole('manager', 'Менеджер');

        $this->actingAs($manager)->get('/admin')->assertOk();
        $this->get('/admin/products')->assertOk();
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/roles')->assertForbidden();
    }

    public function test_admin_creates_role_sets_rate_and_assigns_staff(): void
    {
        $admin = $this->createAdmin();
        $worker = User::factory()->create(['name' => 'Працівник']);
        $this->actingAs($admin)->get('/admin/roles')->assertOk();

        Livewire::test(CreateRole::class)
            ->set('data.label', 'Кухар')->set('data.salary_coefficient', 0.8)->set('data.users', [$worker->id])
            ->call('create')
            ->assertHasNoFormErrors();

        $role = Role::query()->where('label', 'Кухар')->firstOrFail();
        $this->assertMatchesRegularExpression('/^[a-z0-9_]+$/', $role->name);
        $this->assertSame(0.8, (float) $role->salary_coefficient);
        $this->assertSame(0.8, $worker->fresh()->salaryCoefficient());

        Livewire::test(EditRole::class, ['record' => $role->getKey()])
            ->set('data.salary_coefficient', 1.1)
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame(1.1, (float) $role->fresh()->salary_coefficient);
        $this->get('/admin/roles/' . $role->getKey() . '/edit')->assertOk();

        $adminRole = Role::query()->where('name', 'admin')->firstOrFail();
        $this->assertFalse(RoleResource::canDelete($adminRole));
        $this->assertTrue(RoleResource::canDelete($role));
    }

    public function test_migrated_admin_pages_and_order_product_editor_render(): void
    {
        $admin = $this->createAdmin();
        $product = Product::query()->create([
            'title' => 'Філадельфія',
            'price' => 320,
            'weight' => 300,
            'count' => 8,
            'main_image' => 'images/products/philadelphia.webp',
        ]);
        $order = Order::query()->create([
            'customer_name' => 'Тестовий клієнт',
            'customer_phone' => '+380000000000',
            'customer_delivery_type' => 'Курʼєр',
            'customer_street' => 'Центральна',
            'customer_building' => '1',
            'online_payment' => 'Готівка',
            'time' => now(),
            'sum' => 320,
        ]);
        $order->products()->attach($product, ['shop_product_quantity' => 2]);

        $this->actingAs($admin);

        foreach ([
            '/admin/products',
            '/admin/products/create',
            '/admin/categories',
            '/admin/orders/' . $order->getKey() . '/edit',
            '/admin/questions',
            '/admin/landing-orders',
            '/admin/users',
            '/admin/delivery',
        ] as $url) {
            $this->get($url)->assertOk();
        }

        $component = Livewire::test(EditOrder::class, ['record' => $order->getKey()]);
        $itemKey = array_key_first($component->get('data.order_products'));

        $component
            ->set("data.order_products.{$itemKey}.quantity", 3)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('shop_order_shop_product', [
            'shop_order_id' => $order->getKey(),
            'shop_product_id' => $product->getKey(),
            'shop_product_quantity' => 3,
        ]);

        Livewire::test(DeliverySettings::class)
            ->set('data.cost', 99)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('delivery', ['cost' => 99]);

        $password = $admin->password;

        Livewire::test(EditUser::class, ['record' => $admin->getKey()])
            ->fillForm(['name' => 'Оновлений адміністратор'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($password, $admin->fresh()->password);
    }

    public function test_order_shows_ad_campaign(): void
    {
        $this->actingAs($this->createAdmin());
        $order = new Order([
            'customer_name' => 'Клієнт з реклами',
            'customer_phone' => '380000000001',
            'customer_delivery_type' => 'курʼєр',
            'customer_street' => 'Центральна',
            'customer_building' => '1',
            'online_payment' => 'готівка',
            'time' => now(),
            'sum' => 500,
        ]);
        $attribution = ['source' => 'facebook', 'medium' => 'cpc', 'campaign' => 'akciya-ponedilok', 'content' => 'story-1', 'landing_page' => '/promo/ponedilok'];
        $order->attribution = $attribution;
        $order->save();

        $this->get('/admin/orders')->assertOk()->assertSee('facebook / cpc')->assertSee('akciya-ponedilok');
        $this->get('/admin/orders/' . $order->getKey() . '/edit')->assertOk()
            ->assertSee('Рекламна кампанія')->assertSee('story-1')->assertSee('/promo/ponedilok');
        $this->get('/admin/orders/create')->assertOk()->assertDontSee('Рекламна кампанія');

        Livewire::test(EditOrder::class, ['record' => $order->getKey()])
            ->set('data.sum', 550)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($attribution, $order->fresh()->attribution);
    }

    private function createAdmin(): User
    {
        return $this->createUserWithRole('admin', 'Адміністратор');
    }

    private function createUserWithRole(string $name, string $label): User
    {
        $user = User::factory()->create();
        $role = Role::query()->create(['name' => $name, 'label' => $label]);
        $user->roles()->attach($role);
        $user->unsetRelation('roles');

        return $user;
    }
}
