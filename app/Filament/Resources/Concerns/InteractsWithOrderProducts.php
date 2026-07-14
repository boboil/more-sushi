<?php

namespace App\Filament\Resources\Concerns;

use Illuminate\Database\Eloquent\Model;

trait InteractsWithOrderProducts
{
    protected array $orderProducts = [];

    protected function beforeCreate(): void
    {
        $this->captureOrderProductsFromForm();
    }

    protected function beforeSave(): void
    {
        $this->captureOrderProductsFromForm();
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['order_products'] = $this->getRecord()->products
            ->map(fn ($product): array => [
                'product_id' => $product->getKey(),
                'quantity' => $product->pivot->shop_product_quantity,
            ])
            ->values()
            ->all();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $data = $this->extractOrderProducts($data);
        $record = new ($this->getModel())($data);
        $record->save();
        $this->record = $record;
        $this->syncOrderProducts();

        return $record;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $data = $this->extractOrderProducts($data);
        $record->update($data);
        $this->syncOrderProducts();

        return $record;
    }

    private function extractOrderProducts(array $data): array
    {
        if (array_key_exists('order_products', $data)) {
            $this->orderProducts = $data['order_products'];
        }

        unset($data['order_products']);

        return $data;
    }

    private function captureOrderProductsFromForm(): void
    {
        $this->orderProducts = array_values($this->form->getRawState()['order_products'] ?? []);
    }

    private function syncOrderProducts(): void
    {
        $products = collect($this->orderProducts)
            ->filter(fn (array $item): bool => filled($item['product_id'] ?? null))
            ->mapWithKeys(fn (array $item): array => [
                $item['product_id'] => [
                    'shop_product_quantity' => max(1, (int) ($item['quantity'] ?? 1)),
                ],
            ])
            ->all();

        $this->getRecord()->products()->sync($products);
    }
}
