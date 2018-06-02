<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Order;
use App\Models\Product;

class OrderItem extends Model
{
    public $fillable = ['user_id', 'order_id', 'product_id', 'title', 'norm', 'num', 'pre_price', 'total_price'];

    public function order()
    {
      return $this->belongsTo(Order::class, 'order_id');
    }

    public function product()
    {
      return $this->belongsTo(Product::class, 'product_id');
    }
}
