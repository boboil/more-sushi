<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Models\Role;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    /** Системне імʼя (латиницею, унікальне) генеруємо з назви посади. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $base = Str::slug($data['label'], '_') ?: 'role';
        $name = $base;
        for ($i = 2; Role::query()->where('name', $name)->exists(); $i++) {
            $name = $base . '_' . $i;
        }
        $data['name'] = $name;

        return $data;
    }
}
