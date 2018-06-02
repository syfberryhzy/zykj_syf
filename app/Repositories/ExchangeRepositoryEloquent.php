<?php

namespace App\Repositories;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\ExchangeRepository;
use App\Models\Exchange;
use Illuminate\Support\Facades\Redis;
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


    //个人消费
    public function wx_pay($order)
    {
        $user = auth()->user();
        $result = Exchange::create([
            'user_id' => $user->id,
            'total' => $order->total,
            'amount' => $order->total,
            'current' => '0',
            'model' => 'order',
            'uri' => $order->id,
            'status' => Exchange::CASH_STATUS,
            'type' => Exchange::REDUCE_TYPE
        ]);
        #修改用户数据
        $user->increment('spend_total', $order->total);
        $user->increment('spend_current', $order->total);
        return $result;
    }
    //M币增加--代理身份可获得
    public function m_add($order)
    {
        $user = auth()->user();
        if ($user->status != 2) {
          return false;
        }
        $config = ['rate' => '100'];
        $integral = number_format($order->total * $config['rate'] / 100, 2);
        $current = $user->integral_current + $integral;
        $result = Exchange::create([
          'user_id' => $user->id,
          'total' => $user->integral_current,
          'amount' => $integral,
          'current' => $current,
          'model' => "order",
          'uri' => $order->id,
          'status' => Exchange::INTEGRAL_STATUS,
          'type' => Exchange::ADD_TYPE
        ]);
        if ($result) {
          $order->status = 2; //已付
          $order->paiedtotal = $order->total;
          $order->save();
          #修改用户
          $user->increment('m_total', $integral);
          $user->increment('m_current', $integral);
        }
        return $result;
    }
    #M币消费
    public function m_pay()
    {
        $user = auth()->user();
        $res = $user->m_current - $order->total;
        $result = Exchange::create([
          'user_id' => $user->id,
          'total' => $user->m_current,
          'amount' => $order->money,
          'current' => $res,
          'model' => 'order',
          'uri' => $order->id,
          'status' => Exchange::MCOIN_STATUS,
          'type' => Exchange::REDUCE_TYPE
        ]);
        #修改用户数据
        $user->decrement('m_current', $order->total);
        return $result;
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
          #修改用户数据
          $user->decrement('victory_current', $data);
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

    //推荐等级
    public function get_share($orderId)
    {
        $user = auth()->user();
        $user_rank = unserialize(Redis::get('user_rank'));
        $rank = $user_rank['rank'];
        if (in_array($rank, ['B', 'C'])) {
          if ($rank == 'B') {
            #diff_price
            $tj_id = $orderId;
            $share_price = 'diff_price';
            $parent_id = auth()->user()->parent_id;
          }else if ($rank == 'C') {
            #share_price
            $tj_id = $user_rank['order_id'];
            $share_price = 'share_price';
            $parent_id = Order::find($old_order_id)->user_id;
          }
          $this->award($parent_id, $orderId, $tj_id, $share_price);
        }
    }

    //推荐奖励
    public function award($parent_id, $xd_id, $tj_id, $share_price)
    {
      $xd = OrderItem::select('id', 'num', 'product_id')->where('order_id', $xd_id)->get();
      $tj = OrderItem::select('product_id')->where('order_id', $tj_id)->get();
      $tj = collect($tj)->groupBy('product_id')->keys()->toArray();
      $parent = User::find($parent_id);
      foreach($xd as $key => $item) {
        #匹配不到相同商品
        if (!in_array($item['product_id'], $tj)) {
          continue;//跳出本次循环
        }
        $share_price = $share_price == 'diff_price' ? $item->product->diff_price : $item->product->share_price;
        $price = $share_price * $item['num'];

        $result = Exchange::create([
          'user_id' => $parent_id,
          'total' => $parent->victory_current,
          'amount' => $price,
          'current' => $parent->victory_current + $price,
          'model' => 'order_item',
          'uri' => $item['id'].$item['product_id'],
          'status' => Exchange::AWARD_STATUS,
          'type' => Exchange::ADD_TYPE
        ]);

        if ($result) {
          $parent->increment('victory_current', $price);
          $parent->increment('victory_total', $price);
        }
      }
    }
}
