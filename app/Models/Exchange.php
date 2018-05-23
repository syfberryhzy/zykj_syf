<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;
use App\Models\User;

/**
 * Class Exchange.
 *
 * @package namespace App\Models;
 */
class Exchange extends Model implements Transformable
{
    use TransformableTrait;

    const CASH_STATUS = 1; //现金--消费
    const MCOIN_STATUS = 2; //M币
    const AWARD_STATUS = 3; //奖金--业绩奖励
    const WITHDRAW_STATUS = [5, 6]; //提现 5=提现申请，6=提现完成
    const WITHDRAW_STATUS_APPLY = 5;//5=提现申请
    const WITHDRAW_STATUS_AGREE = 6;//6=提现完成

    const ADD_TYPE = 1;//增加
    const REDUCE_TYPE = 2;//减少

    protected $fillable = ['user_id', 'total', 'amount', 'current', 'model', 'uri', 'status', 'type'];

    public function users()
    {
      return $this->belongsTo(User::class, 'user_id');
    }



}
