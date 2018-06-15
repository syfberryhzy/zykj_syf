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

    const ORDER_STATUS_PENDING = 0;
    const ORDER_STATUS_FAILED = 1;
    const ORDER_STATUS_PAYED = 2;
    const ORDER_STATUS_SEND = 3;
    const ORDER_STATUS_RECEIPT = 4;
    const ORDER_STATUS_SUCCESS = 5;


    const REFUND_STATUS_PENDING = 0;
    const REFUND_STATUS_APPLIED = 1;
    const REFUND_STATUS_PROCESSING = 2;
    const REFUND_STATUS_SUCCESS = 3;
    const REFUND_STATUS_FAILED = 4;

    public static $orderStatusMap = [
        self::ORDER_STATUS_PENDING    => '待付款',
        self::ORDER_STATUS_PAYED    => '已付款',
        self::ORDER_STATUS_SEND => '待收货',
        self::ORDER_STATUS_RECEIPT    => '待评价',//已收货
        self::ORDER_STATUS_SUCCESS     => '已完成',
        self::ORDER_STATUS_FAILED     => '已取消',
    ];

    public static $refundStatusMap = [
        self::REFUND_STATUS_PENDING    => '未退款',
        self::REFUND_STATUS_APPLIED    => '已申请退款',
        self::REFUND_STATUS_PROCESSING => '退款中',
        self::REFUND_STATUS_SUCCESS    => '退款成功',
        self::REFUND_STATUS_FAILED     => '退款失败',
    ];

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
