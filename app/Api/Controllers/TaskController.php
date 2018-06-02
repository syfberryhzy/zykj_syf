<?php

namespace App\Api\Controllers;

use App\Models\Coupon;
use App\Models\UserCoupon;
use App\Models\Banner;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class TaskController extends Controller
{
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

    #当月消费统计
}
