<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'image',
        'price',
        'currency',
        'slug',
        'discount',
        'indicators',
        'category_code'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_code', 'code');
    }

    public function productDetail()
    {
        return $this->hasOne(ProductDetail::class, 'code', 'slug');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'code', 'slug');
    }
}
