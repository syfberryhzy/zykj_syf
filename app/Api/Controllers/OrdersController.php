<?php

namespace App\Api\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Exchange;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Evaluate;
use App\Repositories\OrderRepositoryEloquent;
use App\Transformers\OrderTransformer;
use App\Handlers\WxImageUploadHandler;
use Illuminate\Support\Facades\Storage;

class OrdersController extends Controller
{
    public $order;

    public function __construct(OrderRepositoryEloquent $reponsitory)
    {
        $this->order = $reponsitory;
    }
    /**
    * 订单列表--分页
    */
    public function index(Request $request)
    {
        $status = $request->status ?? 'all';
        $page = (int)$request->page ?? 1;
        $user = auth()->user();

        $where[] = ['user_id', $user->id];
        switch($status) {
          case 'unpay': $where[] = ['status', 0];
          break;
          case 'payed': $where[] = ['status', 2];
          break;
          case 'confirm': $where[] = ['status', 3];
          break;
          case 'evaluate': $where[] = ['status', 4];
          break;
        }

        $orders = Order::where($where)->orderBy('created_at', 'desc')->get();
        $total = count($orders);
        $totalPage = (int)ceil($total / 5);
        $page > $totalPage && $page = $totalPage;
        $data = collect($orders)->forPage($page, 5);
        $datas = [];
        foreach($data as $index => $value) {
          $items = $value->items;
          $item = collect($items)->first();
          $item->image = env('APP_URL_UPLOADS'). '/' . $item->product->images[0];
          $value->item = collect($item)->only(['image', 'title', 'norm', 'pre_price','product_id', 'product_item_id' ]);
          $value->num = collect($items)->sum('num');
          $datas[] = collect($value)->except(['items', 'created_at', 'updated_at', 'consignee', 'phone', 'address', 'preferentialtotal', 'customerfreightfee', 'freightbillno']);
        }
        return response()->json(['data' => $datas, 'total' => $total, 'totalPage' => $totalPage]);
    }

    public function show(Order $order)
    {
        $order->username = $order->users->username;
         collect($order->items)->map(function ($value, $index) {
            $value->image = env('APP_URL_UPLOADS'). '/' . $value->product->images[0];
            // collect($value)->except(['product']);
        });
        $order->num = collect($order->items)->sum('num');
        $order = collect($order)->except(['users']);
        return response()->json(['status' => 'success', 'code' => '201', 'data' => $order]);
    }

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
        dump($result);
      }
    }

    /*
    * 分享赚
    */
    public function share(Order $order)
    {
        $order->username = $order->users->username;
        $order->items = $order->items;
        $order = collect($order)->except(['users']);
        return response()->json(['status' => 'success', 'code' => '201', 'data' => $order]);
    }

    public function update(Order $order, Request $request)
    {
        $operate = $request->operate;
        if ($operate == 'cancel') {
          $order->status = 1;//取消订单
        } elseif($operate == 'confirm') {
          $order->status = 4;//确认支付
        } else {
          return response()->json(['status' => 'fail', 'code' => '401', 'message' => '悟空，请不要开玩笑']);
        }
        if ($order->save()) {
          return response()->json(['status' => 'success', 'code' => '401', 'message' => '操作成功']);
        }
        return response()->json(['status' => 'fail', 'code' => '422', 'message' => '操作失败']);
    }


  public function evaluate(Order $order, Request $request)
    {
      $user = auth()->user();
      $data = $request->data;
      // \Redis::set($user->id.'eval', serialize($data));
      // $data = unserialize(\Redis::get($user->id.'eval'));
      // dd($res);
      // dd($user);
      foreach ($data as $key => $value) {
          $images = $value['images'] ? json_encode($value['images'], JSON_UNESCAPED_UNICODE) : '';
          $datas[] = [
              'user_id' => $user->id,
              'order_id' => $order->id,
              'order_item_id' => $value['item_id'],
              'product_id' => $value['product_id'],
              'content' => $value['content'],
              'images' => $images
              // 'images' => $value['images'] ? json_encode($value['images'], JSON_UNESCAPED_UNICODE) : ''
          ];
      }
      $res = Evaluate::insert($datas);
      if (!$res) {
          return response()->json(['status' => 'fail', 'code' => '422', 'message' => '操作失败']);
      }
      $result = $order->update(['status' => 5]);//已评价
      if ($result) {
        return response()->json(['status' => 'success', 'code' => '201', 'message' => '操作成功']);
      }
      return response()->json(['status' => 'fail', 'code' => '422', 'message' => '操作失败']);
    }

    public function refund(Order $order, Request $request)
    {
      if($order->type == 1) {
        return response()->json(['status' => 'fail', 'code' => '401', 'message' => '兑换商品不可退款']);
      }
      if($order->status != 2) {
        return response()->json(['status' => 'fail', 'code' => '401', 'message' => '未付款订单不可退款']);
      }
      if($order->refund_status != 0) {
        return response()->json(['status' => 'fail', 'code' => '401', 'message' => '该订单已经申请过退款，请勿重复申请']);
      }
      if(!$request->refund_reason) {
        return response()->json(['status' => 'fail', 'code' => '401', 'message' => '请填写退款理由']);
      }
      $extra = $order->extra ?: [];
      $extra['refund_reason'] = $request->refund_reason;
      $out_refund_no =  date('YmdHis') . rand(1000, 9999);

      $order->refund_status = 1;
      $order->out_refund_no = $out_refund_no;
      $order->extra = $out_refund_no;
      if($order->save()) {
        return response()->json(['status' => 'success', 'code' => '201', 'message' => '申请退款成功,等待处理']);
      }
      // $payment = \EasyWeChat::payment();
      //
      // $result = $payment->refund->byOutTradeNumber($order->out_trade_no, $out_refund_no, 1, 1, [
      //     'refund_desc' => '商品已售完',
      // ]);
      return response()->json(['status' => 'fail', 'code' => '422', 'message' => '申请退款失败']);
    }
    /**
    * 上传图片
    */
    public function uploads(WxImageUploadHandler $uploads)
    {
        $user = auth()->user();
        $result = $uploads->save($_FILES['wxfile'], 'evaluate', $user->id);
        return response()->json($result);
        // if ($result) {
        //   return response()->json(['status' => 'success', 'code' => '201', 'message' => '操作成功', 'data' => [env('APP_URL_UPLOADS').'/evaluate/'.$result, $result]]);
        // }
        // return response()->json(['status' => 'fail', 'code' => '422', 'message' => '操作失败']);
    }

    public function logistics(Order $order)
    {

        // if($order->status !== Order::ORDER_STATUS_RECEIPT) {
        //   return response()->json(['status' => 'fail', 'code' => '422', 'message' => '订单状态不正确']);
        // }
        $kd_type ='zhongtong';
        $logistics = '499061307957';
        // $kd_type = $order->express_company;
        // $logistics = $order->freightbillno;
        $url = "https://m.kuaidi100.com/query?type=".$kd_type."&postid=". $logistics."&id=1&valicode=&temp=0.4412782272241622";
        $msg = curl_init();
        curl_setopt($msg, CURLOPT_URL, $url);
        curl_setopt($msg, CURLOPT_SSL_VERIFYPEER, false); //如果接口URL是https的,我们将其设为不验证,如果不是https的接口,这句可以不用加
        curl_setopt($msg, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($msg);
        curl_close($msg);
        $data = json_decode($data,true);//将json格式转化为数组格式,方便使用
        return response()->json(['status' => 'success', 'code' => '201', 'data' => $data]);
    }
}
