<?php

namespace App\Console\Commands;

use App\Models\Shop\Product;
use App\Models\Shop\Tag;
use Illuminate\Console\Command;

/**
 * Одноразове початкове заповнення фільтрів за назвою та складом товару.
 * Лише додає теги (нічого не прибирає), тож ручні правки в адмінці не втрачаються.
 */
class AutofillProductTags extends Command
{
    protected $signature = 'shop:tags-autofill {--dry-run : Лише показати, що буде додано}';

    protected $description = 'Розставити фільтри товарам за назвою та складом';

    private const RULES = [
        'spicy' => '/спайс|гостр|халапен|чилі|чили/iu',
        'baked' => '/запеч/iu',
        'salmon' => '/лосос|червоної риби/iu',
        'tuna' => '/тунц|тунец|тунець/iu',
        'eel' => '/вугр|вугор/iu',
        'shrimp' => '/кревет/iu',
        'crab' => '/краб/iu',
    ];

    public function handle(): int
    {
        $tags = Tag::whereIn('slug', array_keys(self::RULES))->pluck('id', 'slug');
        $dry = (bool) $this->option('dry-run');
        $rows = [];

        foreach (Product::with('tags')->get() as $product) {
            $text = $product->title . ' ' . html_entity_decode(strip_tags((string) $product->consist));
            $matched = collect(self::RULES)
                ->filter(fn (string $re, string $slug) => $tags->has($slug) && preg_match($re, $text))
                ->keys();
            $new = $matched->diff($product->tags->pluck('slug'));
            if ($new->isEmpty()) {
                continue;
            }
            $rows[] = [$product->id, $product->title, $new->implode(', ')];
            if (! $dry) {
                $product->tags()->syncWithoutDetaching($new->map(fn ($slug) => $tags[$slug])->all());
            }
        }

        $this->table(['ID', 'Товар', 'Додано'], $rows);
        $this->info(($dry ? '[dry-run] ' : '') . 'Товарів оновлено: ' . count($rows));

        return self::SUCCESS;
    }
}
