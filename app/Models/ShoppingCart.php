<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\ProductItem;

class ShoppingCart extends Model
{
  protected $table = 'carts';

  protected $fillable = ['id', 'user_id', 'item_id', 'product_id', 'image'];

  public function items()
  {
    return $this->hasMany(ProductItem::class, 'item_id');
  }

  public function product()
  {
    return $this->hasMany(Product::class, 'product_id');
  }
}
