<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    
    protected $table = 'categories';
    
    // Sử dụng code làm khóa chính thay vì id
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'code',
        'level',
        'slug',
        'title',
        'image',
        'parent_code'
    ];
    
    public function products()
    {
        return $this->hasMany(Product::class, 'category_code', 'code');
    }
    
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_code', 'code');
    }
    
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_code', 'code');
    }
}
