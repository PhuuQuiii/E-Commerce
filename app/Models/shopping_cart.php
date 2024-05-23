<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class shopping_cart extends Model
{
    use HasFactory;
    protected $table = 'shopping_cart';
    protected $fillable = ['shopping_cart_id','user_id','product_id','quantity', 'color', 'size', 'img','created_at','modified_at'];

    protected $primaryKey = 'shopping_cart_id';

    public function products()
    {
        return $this->hasMany(Product::class, 'product_id', 'product_id');
    }
}
