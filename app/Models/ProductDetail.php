<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductDetail extends Model
{
    use HasFactory;
    
    // Sử dụng slug làm khóa chính thay vì id
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'code', 'gallery', 'gallery_local', 'detail_indicators',
        'meta_description', 'long_description', 'specs', 'key_features'
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'slug', 'code');
    }
} 