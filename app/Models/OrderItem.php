<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    public $fillable = ['user_id', 'order_id', 'product_id', 'title', 'norm', 'num', 'pre_price', 'total_price'];
}
