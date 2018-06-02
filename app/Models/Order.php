<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;
use App\Models\User;
use App\Models\OrderItem;

/**
 * Class Order.
 *
 * @package namespace App\Models;
 */
class Order extends Model implements Transformable
{
    use TransformableTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public $fillable = [
      'user_id', 'pay_id', 'consignee', 'phone', 'address', 'tradetotal',
      'preferentialtotal', 'customerfreightfee', 'total', 'paiedtotal',
      'out_trade_no', 'freightbillno', 'status', 'type', 'remark'
    ];

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}
