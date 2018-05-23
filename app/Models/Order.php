<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

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

}
