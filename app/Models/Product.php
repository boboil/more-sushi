<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'product';

    protected $fillable = [
        'title',
        'price',
        'main_image',
        'images',
        'description',
    ];

    public function orders()
    {
        return $this->belongsToMany(AdminOrder::class);
    }
}
