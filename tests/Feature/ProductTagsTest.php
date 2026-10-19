<?php

namespace Tests\Feature;

use App\Filament\Resources\Shop\Products\Pages\ListProducts;
use App\Filament\Resources\Shop\Tags\Pages\CreateTag;
use App\Models\Role;
use App\Models\Shop\Category;
use App\Models\Shop\Product;
use App\Models\Shop\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductTagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_seeds_default_filters(): void
    {
        $this->assertSame(
            ['spicy', 'baked', 'salmon', 'tuna', 'eel', 'shrimp', 'crab'],
            Tag::getEnabledTags()->pluck('slug')->all(),
        );
    }

    public function test_admin_can_create_tag_with_products_and_generated_slug(): void
    {
        $this->actingAs($this->createManager());
        $product = $this->createProduct('Філадельфія');

        $this->get('/admin/tags')->assertOk();
        $this->get('/admin/tags/create')->assertOk();

        Livewire::test(CreateTag::class)
            ->set('data.title', 'Вегетаріанське')
            ->set('data.sort_order', 10)
            ->set('data.products', [$product->id])
            ->call('create')
            ->assertHasNoErrors();

        $tag = Tag::where('title', 'Вегетаріанське')->firstOrFail();
        $this->assertSame('vegetarianske', $tag->slug);
        $this->assertTrue($product->tags()->whereKey($tag->id)->exists());
    }

    public function test_bulk_actions_attach_and_detach_tags(): void
    {
        $this->actingAs($this->createManager());
        $a = $this->createProduct('Рол 1');
        $b = $this->createProduct('Рол 2');
        $spicy = Tag::where('slug', 'spicy')->firstOrFail();

        Livewire::test(ListProducts::class)
            ->selectTableRecords([$a, $b])
            ->mountAction(\Filament\Actions\Testing\TestAction::make('attachTags')->table()->bulk())
            ->set('mountedActions.0.data.tag_ids', [$spicy->id])
            ->callMountedAction()
            ->assertHasNoErrors();

        $this->assertSame(2, $spicy->products()->count());

        Livewire::test(ListProducts::class)
            ->selectTableRecords([$a])
            ->mountAction(\Filament\Actions\Testing\TestAction::make('detachTags')->table()->bulk())
            ->set('mountedActions.0.data.tag_ids', [$spicy->id])
            ->callMountedAction()
            ->assertHasNoErrors();

        $this->assertSame([$b->id], $spicy->products()->pluck('product_shop.id')->all());
    }

    public function test_api_returns_enabled_tags_and_product_tag_slugs(): void
    {
        $category = Category::query()->create(['title' => 'Роли', 'enable' => true]);
        $product = $this->createProduct('Спайсі лосось');
        $product->update(['isActive' => true]);
        $category->products()->attach($product);
        $salmon = Tag::where('slug', 'salmon')->firstOrFail();
        $hidden = Tag::where('slug', 'crab')->firstOrFail();
        $hidden->update(['enable' => false]);
        $product->tags()->attach([$salmon->id, $hidden->id]);

        $this->getJson('/api/shop/tags')
            ->assertOk()
            ->assertJsonMissing(['slug' => 'crab'])
            ->assertJsonFragment(['slug' => 'salmon', 'title' => 'Лосось']);

        $this->getJson('/api/shop/catalog')
            ->assertOk()
            ->assertJsonPath('data.0.products.data.0.tags', ['salmon']);
    }

    public function test_autofill_command_adds_tags_by_title_and_composition(): void
    {
        $product = $this->createProduct('Запечений рол');
        $product->update(['consist' => '<p>Лосось, вугор, крем-сир</p>']);

        $this->artisan('shop:tags-autofill', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame(0, $product->tags()->count());

        $this->artisan('shop:tags-autofill')->assertSuccessful();
        $this->assertEqualsCanonicalizing(['baked', 'salmon', 'eel'], $product->tags()->pluck('slug')->all());
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
