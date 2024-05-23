<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class product extends Model
{
    use HasFactory;
    protected $table = 'product';
    protected $fillable = ['product_id', 'name', 'description', 'created_by_user_id', 'product_brand_id', 'product_category_id', 'price', 'stock', 'discount_id', 'created_at', 'modified_at', 'deleted_at'];
    protected $primaryKey = 'product_id';

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_items', 'product_id', 'order_id');
    }

    public function productBrand()
    {
        return $this->belongsTo(product_brand::class, 'product_brand_id', 'product_brand_id');
    }

    public function productCategory()
    {
        return $this->belongsTo(product_category::class, 'product_category_id', 'product_category_id');
    }

    public function productReviews()
    {
        return $this->hasMany(product_review::class, 'product_id', 'product_id');
    }

    public function userAddress()
    {
        return $this->belongsTo(user_address::class, 'created_by_user_id', 'user_id');
    }

    public function images()
    {
        return $this->hasMany(product_image::class, 'product_id', 'product_id');
    }

    public function shoppingCart()
    {
        return $this->belongsTo('product_id', 'product_id');
    }

    public function productSizes()
    {
        return $this->hasMany(product_size::class, 'product_id', 'product_id');
    }

    // Định nghĩa mối quan hệ với bảng ProductColor
    public function productColors()
    {
        return $this->hasMany(product_color::class, 'product_id', 'product_id');
    }

}