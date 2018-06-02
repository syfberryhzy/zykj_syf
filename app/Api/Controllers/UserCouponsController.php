<?php

namespace App\Api\Controllers;

use App\Models\Coupon;
use App\Models\UserCoupon;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Transformers\UserCouponTransformer;

class UserCouponsController extends Controller
{
    public function index()
    {
        $user_id = auth()->user()->id;
        $datas = UserCoupon::where('user_id', $user_id)->get();
        return $this->response->collection($datas, new UserCouponTransformer);
    }

    /**
    * 领券入口
    */
    public function entrance(Coupon $coupon)
    {
        $this->checkCoupon($coupon);
        $coupon->start_at = Carbon::parse($coupon->start_at)->toDateString();
        $coupon->end_at = Carbon::parse($coupon->end_at)->toDateString();
        return response()->json(['status' => 'success', 'code' => '201', 'message' => '福利多多,先到先得', 'data' => $coupon]);
    }

    /**
    * 点击领券
    */
    public function receive(Coupon $coupon)
    {
        $this->checkCouponAgain($coupon);
        # 领券操作
        $user_id = auth()->user()->id;
        $result = UserCoupon::create([
          'user_id' => $user_id,
          'coupon_id' => $coupon->id
        ]);
        if ($result) {
          $coupon->increment('receive');//领取人数自增
          return response()->json(['status' => 'success', 'code' => '201', 'message' => '领取成功']);
        }
        return response()->json(['status' => 'fail', 'code' => '422', 'message' => '领取失败']);
    }


    public function checkCoupon($coupon)
    {
        #是否开启
        if ($coupon->status != 1) {
        return response()->json(['status' => 'fail', 'code' => '401', 'message' => '活动未开启,敬请期待~_~']);
        }
        #是否过期
        $end = Carbon::parse($coupon->end_at);
        $now = Carbon::now();
        if ($now->gt($end)) {
          return response()->json(['status' => 'fail', 'code' => '401', 'message' => '活动已结束,下次速度哦']);
        }
    }

    public function checkCouponAgain($coupon)
    {
        $this->checkCoupon($coupon);
        #是否已领取
        $user_id = auth()->user()->id;
        $result = UserCoupon::where(['user_id' => $user_id, 'coupon_id' => $coupon->id])->first();
        if ($result) {
          return response()->json(['status' => 'fail', 'code' => '401', 'message' => '领取已达上限']);
        }
        #是否已领完
        if($coupon->quantum == $coupon->receive) {
          return response()->json(['status' => 'fail', 'code' => '401', 'message' => '优惠券已领完，下次速度哦']);
        }
    }

}
