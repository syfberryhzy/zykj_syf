<?php

namespace App\Models;
use App\Models\User;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;

class Evaluate extends Model
{
    public function users()
    {
		    return $this->belongsTo(User::class, 'user_id');
    }

	  public function orderItems()
    {
		    return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

	  public function product()
    {
		    return $this->belongsTo(Product::class, 'product_id');
    }
    public function setImagesAttribute($images)
    {
        if (is_array($images)) {
            $this->attributes['images'] = json_encode($images);
        }
    }

    public function getImagesAttribute($images)
    {
        return json_decode($images, true);
    }
}
