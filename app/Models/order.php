<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class order extends Model
{
    use HasFactory;
    protected $table = 'order';
    protected $fillable = [
        'order_id',
        'user_id',
        'order_status_id',
        'shipping_method_id',
        'order_note',
        'order_address',
        'order_phone',
        'order_name',
        'total',
        'shop_id',
    ];

    protected $primaryKey = 'order_id';


    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_items', 'order_id', 'product_id');
    }

    public function items()
    {
        return $this->hasMany(order_items::class, 'order_id', 'order_id');
    }

    // Định nghĩa mối quan hệ với người dùng (users)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}