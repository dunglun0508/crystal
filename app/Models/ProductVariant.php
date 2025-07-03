<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;
    
    // Không sử dụng auto-increment id
    public $incrementing = false;
    
    // Sử dụng id làm primary key nhưng không auto-increment
    protected $primaryKey = 'id';
    protected $keyType = 'int';

    protected $fillable = [
        'code', 'name', 'art_no', 'price', 'currency',
        'stock_status', 'variant_image'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'code', 'slug');
    }
} 