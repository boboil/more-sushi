<?php

namespace Tests\Feature;

use App\Filament\Resources\Shop\Products\Pages\EditProduct;
use App\Filament\Resources\Shop\Products\Pages\ListProducts;
use App\Models\Role;
use App\Models\Shop\Category;
use App\Models\Shop\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductFeaturedTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_mark_product_for_main_banner(): void
    {
        $this->actingAs($this->createManager());
        Storage::fake('public_root')->put('images/products/test.webp', 'img');
        $product = $this->createProduct('Піца 2+1');

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->set('data.featured', true)
            ->set('data.featured_sort', 2)
            ->call('save')
            ->assertHasNoErrors();

        $product->refresh();
        $this->assertTrue($product->featured);
        $this->assertSame(2, $product->featured_sort);
    }

    public function test_list_can_be_filtered_by_featured(): void
    {
        $this->actingAs($this->createManager());
        $featured = $this->createProduct('Сет Токіо');
        $featured->update(['featured' => true]);
        $regular = $this->createProduct('Філадельфія');

        Livewire::test(ListProducts::class)
            ->filterTable('featured', true)
            ->assertCanSeeTableRecords([$featured])
            ->assertCanNotSeeTableRecords([$regular]);
    }

    public function test_catalog_api_exposes_featured_flag_and_order(): void
    {
        $category = Category::query()->create(['title' => 'Сети', 'enable' => true]);
        $product = $this->createProduct('Сет Токіо');
        $product->update(['isActive' => true, 'featured' => true, 'featured_sort' => 3]);
        $category->products()->attach($product);

        $this->getJson('/api/shop/catalog')
            ->assertOk()
            ->assertJsonPath('data.0.products.data.0.featured', true)
            ->assertJsonPath('data.0.products.data.0.featured_sort', 3);
    }

    private function createProduct(string $title): Product
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
