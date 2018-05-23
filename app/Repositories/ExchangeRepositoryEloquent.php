<?php

namespace App\Repositories;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\ExchangeRepository;
use App\Models\Exchange;
use Auth;
use App\Validators\ExchangeValidator;

/**
 * Class ExchangeRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class ExchangeRepositoryEloquent extends BaseRepository implements ExchangeRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return Exchange::class;
    }



    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

    public function m_pay()
    {
        #扣除M币
        $user = auth()->user();
        Exchange::create([
          'user_id' => $user->id,
          'total' => $user->m_current,
          'amount' => $order->money,
          'current' => $res,
          'model' => 'order',
          'uri' => $order->id,
          'status' => Exchange::MCOIN_STATUS,
          'type' => Exchange::REDUCE_TYPE
        ]);
    }
    /**
    * 提现申请
    */
    public function withward($data)
    {
        $user = auth()->user();
        $res = $user->victory_current - $data;
        if ($res < 0) {
            return false;
        }
        $add = Exchange::create([
          'user_id' => $user->id,
          'total' => $user->victory_current, //现有业绩
          'amount' => $data,
          'current' => $res,
          'model' => 'withward',
          'uri' => '0',
          'status' => Exchange::WITHDRAW_STATUS_APPLY,
          'type' => Exchange::REDUCE_TYPE
        ]);

        if ($add) {
          $user->update([
            'victory_current' => $add->current
          ]);
        }
        return false;
    }

    /**
    * 同意提现
    */
    public function withwardAgree($data)
    {
        $data->status = Exchange::WITHDRAW_STATUS_AGREE;
        $data->save();
    }

}
