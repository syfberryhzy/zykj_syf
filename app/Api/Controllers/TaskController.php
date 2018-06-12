<?php

namespace App\Api\Controllers;

use App\Models\Coupon;
use App\Models\UserCoupon;
use App\Models\User;
use App\Models\Banner;
use App\Models\Exchange;
use App\Models\Recommend;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\ExchangeRepositoryEloquent;
use Carbon\Carbon;

class TaskController extends Controller
{
    public $exchanges;

    public function __construct(ExchangeRepositoryEloquent $exRepository)
    {
        $this->exchanges = $exRepository;
    }
    //定时任务
    #下架秒杀商品

    #12点
    #优惠券到期 #banner= 1隐藏
    public function coupon_expired()
    {
      $where = [
        ['status', 1],
        ['end_at', '<=', Carbon::now()]
      ];
      #优惠券活动过期
      $ids = Coupon::where($where)->select('id')->get();
      Coupon::where($where)->update(['status' => 0]);
      #未使用的过期优惠券
      UserCoupon::whereIn('coupon_id', $ids)->where('status', 0)->update(['status' => 2]);
      #优惠券banner隐藏
      $banner = Banner::find(1);
      $url = explode('/', $banner->url);
      $id = end($url);
      if (in_array($id, $ids)) {
        $banner->url = '';
        $banner->status = 0;
        $banner->save();
      }
    }
    public function getUser()
    {
      $users = User::all()->pluck('id');
      return $users;
    }

    /**
    * 当天的个人业绩
    */
    public function victory($userID) {
      $user = User::find($userID);
      $ids = $this->begats($userID, $i = 1);

      $where[] = ['created_at', '>=', Carbon::now()->startOfDay()];
      $where[] = ['created_at', '<=', Carbon::now()->endOfDay()];

      #是否已存在记录，存在 = 删除
      $log = Exchange::where($where)->where('status', Exchange::VICTORY_DATE)->delete();

      $where[] = ['status', Exchange::SPEND_DATE];
      $datas = Exchange::select('amount')->whereIn('user_id', $ids)->where($where)->get();
      $total = collect($datas)->sum('amount');
      $result = Exchange::create([
        'user_id' => $userID,
        'total' => $total,
        'amount' => $total,
        'current' => 0,
        'model' => 'victory_self',
        'uri' => 0,
        'status' => Exchange::VICTORY_DATE,
        'type' => Exchange::ADD_TYPE
      ]);

      if ($result) {
        $user->increment('victory_current', 0);//当晚清零
      }
    }

    /**
    * 后裔, 系谱, 子孙
    */
    public function begats($userID, $i = 1)
    {
        $datas = [];
        $user = User::find($userID);
        if($user) {
          $datas[] = $userID;
        }
        if($user->status == 2) {
          $log = Recommend::where('user_id', $user->id)->first();
          if ($log) {
            $members = $log->member ? json_decode($log->member) : [];
            $vistors = $log->visitor ? json_decode($log->visitor) : [];

            $datas = array_merge($datas, $members, $vistors);
            if($i == 1) {
              foreach($members as $key => $val) {
                $data = $this->begats($val, $i = 2);
                $datas = array_merge($datas, $data);
              }
            }
          }
        }
        return array_unique($datas);
    }
    public function all_spend($userID) {

      $user = User::find($userID);
      $where[] = ['user_id', $userID];

      $where[] = ['created_at', '>=', Carbon::now()->startOfDay()];
      $where[] = ['created_at', '<=', Carbon::now()->endOfDay()];
      #是否已存在记录，存在 = 删除
      $log = Exchange::where($where)->where('status', Exchange::SPEND_DATE)->delete();
      $where[] = ['status', Exchange::CASH_STATUS];
      $datas = Exchange::select('amount')->where($where)->get();
      $total = collect($datas)->sum('amount');
      $result = Exchange::create([
        'user_id' => $userID,
        'total' => $total,
        'amount' => $total,
        'current' => 0,
        'model' => 'spend_self',
        'uri' => 0,
        'status' => Exchange::SPEND_DATE,
        'type' => Exchange::ADD_TYPE
      ]);

      if ($result) {
        $user->increment('spend_current', 0);//当晚清零
      }
    }
    public function spend()
    {
      $users = $this->getUser();
      //个人消费统计
      foreach($users as $item) {
        $this->all_spend($item);
      }
      //团队业绩统计
      foreach($users as $item) {
        $this->victory($item);
      }

    }
}
