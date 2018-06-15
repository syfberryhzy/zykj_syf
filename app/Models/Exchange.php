<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;
use App\Models\User;
use App\Models\Order;

/**
 * Class Exchange.
 *
 * @package namespace App\Models;
 */
class Exchange extends Model implements Transformable
{
    use TransformableTrait;

    const CASH_STATUS = 1; //现金--消费业绩
    const MCOIN_STATUS = 2; //M币
    const AWARD_STATUS = 3; //奖金--奖励
    const WITHDRAW_STATUS = [5, 6]; //提现 5=提现申请，6=提现完成
    const WITHDRAW_STATUS_APPLY = 5;//5=提现申请
    const WITHDRAW_STATUS_AGREE = 6;//6=提现完成
    const SPEND_DATE = 7; //7=当天消费
    const SPEND_MONTH = 8; //8=当月消费
    const VICTORY_DATE = 9; //9=当天业绩
    const VICTORY_MONTH = 10; //10=当月业绩
    const EARN_DATE = 11;
    const EARN_TOTAL = 12;

    const SPEND_STATUS = [7, 8];
    const VICTORY_STATUS = [9, 10];
    const ADD_TYPE = 1;//增加
    const REDUCE_TYPE = 2;//减少

    protected $fillable = ['user_id', 'total', 'amount', 'current', 'model', 'uri', 'status', 'type'];

    public function users()
    {
      return $this->belongsTo(User::class, 'user_id');
    }

    public function orders()
    {
      return $this->belongsTo(Order::class, 'uri');
    }


}
