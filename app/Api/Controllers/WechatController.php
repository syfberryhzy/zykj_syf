<?php

namespace App\Api\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\CancelOrder;
use App\Jobs\TranslateSlug;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\ShoppingCart as Cart;
use App\Models\Address;
use App\Models\Coupon;
use App\Models\UserCoupon;
use App\Models\Exchange;
use App\Models\ProductItem;
use App\Requests\OrderRequest;
use App\Events\OrderItemEvent;
use App\Events\OrderEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Repositories\CartRepositoryEloquent;
use App\Repositories\ExchangeRepositoryEloquent;

class WechatController extends Controller
{
    public $carts;
    public $exchanges;

    public function __construct(CartRepositoryEloquent $repository, ExchangeRepositoryEloquent $exRepository)
    {
        $this->carts = $repository;
        $this->exchanges = $exRepository;
    }
    /**
    * 有效的优惠券
    */
    public function get_coupons($money = 0)
    {
        $where = [
          ['status', 1],
          // ['more_value', '<=', $money],
          ['start_at', '<=', Carbon::now()],
          ['end_at', '>=', Carbon::now()],
        ];
        $data = Coupon::where($where)->select('id')->get();
        $coupons = UserCoupon::where('user_id', auth()->user()->id)->where('status', 0)->whereIn('coupon_id', $data)->first();
        if($coupons) {
          $info = $coupons->coupons;
          $coupons->can_use = $info->more_value <= $money ? 1 : 0;
          $coupons->more_value =  $info->more_value;
          $coupons->par_value =  $info->par_value;
          return collect($coupons)->except(['coupons']);
        }
        return [];
    }

    public function getCarts($request, $type)
    {
      if(!$request->orders) {
        return response()->json(['status' => 'fail', 'code' => '401', 'message' => '错误操作']);
      }
      $orderArr = $request->orders;
      $user = auth()->user();
      $cart = Cart::where('user_id', auth()->user()->id)->whereIn('id', $orderArr)->get();
      foreach($cart as $index => $value) {
        $item = $value->items;
  		  $product = $value->product;
        $value->price = ($type == 0 && $user->status == 2) ? ($item->unit_price - $product->diff_price) : $item->unit_price;
  		  $value->total = $value->price * $value->qty;
  		  $value->image = env('APP_URL_UPLOADS', ''). '/'.$value->image;
        $datas[] = collect($value)->except(['items', 'product']);
      }
      return $datas;
    }

    public function deleteCarts($orderArr)
    {
      $res = Cart::where('user_id', auth()->user()->id)->whereIn('id', $orderArr)->delete();
      return $res;
    }

    public function getPro($request, $type)
    {
      if(!$request->item_id || !$request->qty) {
        return response()->json(['status' => 'fail', 'code' => '401', 'message' => '错误操作']);
      }
      $user = auth()->user();
      $item = ProductItem::find($request->item_id);
      $product = $item->product;
      $value = (object)array();
      $value->item_id = $item->id;
      $value->product_id = $product->id;
      $value->name = $product->title;
      $value->size = $item->norm;
      $value->price = ($type == 0 && $user->status == 2) ? ($item->unit_price - $product->diff_price) : $item->unit_price;
      $value->qty = $request->qty;
      $value->total = $value->price * $value->qty;
      // dd($product, $product->image[0]);
      $value->image = env('APP_URL_UPLOADS', ''). '/'.$product->images[0];
      $datas[] = collect($value)->except(['items', 'product']);
      return $datas;
    }
    /**
    * 确认下单
    */
    public function orderSure(Request $request)
    {

        #TODO检测库存???
        $orderArr = $request->orders;
        $user = auth()->user();

        $type = $request->type ? $request->type : 0;
        $cart = $request->byCart == 1 ? $this->getCarts($request, $type) : $this->getPro($request, $type);
        // dump(TranslateSlug::dispatch($cart, $operate = true));
        // dd($cart);

        $address = Address::where('user_id', $user->id)->first();
        $orders = [
          'cart'  => collect($cart)->toArray(),
          'sum'  => collect($cart)->sum('total'),
          'num'  => collect($cart)->sum('qty'),
          'type' => $type
        ];

        #优惠券
        $coupons = $type == 0 ? $this->get_coupons($orders) : [];


        // dd(unserialize(Redis::get($user->id)));
        if ($type == 1 && $orders['sum'] > $user->m_current) {
          return response()->json(['status' => 'fail', 'code' => '401', 'message' => '您的M币不足','data' => $orders, 'address' => $address, 'coupons' => $coupons ]);
        }
        Redis::set($user->id, serialize($orders));
        return response()->json(['status' => 'success', 'code' => '201', 'data' => $orders, 'address' => $address, 'coupons' => $coupons]);
        // if (TranslateSlug::dispatch($cart, $operate = true)) {
        //   $orders->cart = $cart;
        //   $orders->sum = collect($cart)->sum('total');
        //   $orders->num = collect($cart)->sum('qty');
        //   return response()->json(['status' => 'success', 'code' => '201', 'data' => $orders, 'address' => $address]);
        // } else {
        //   return response()->json(['status' => 'fail', 'code' => '401', 'message' => '商品库存不足']);
        // }

    }

    /**
    * 生成订单--立即购买、购物车
    */
    public function store(Request $request)
    {
        $user = auth()->user();

        $orders = unserialize(Redis::get($user->id));
        $cart = $orders['cart'];
        $type = $orders['type'];

        $address = Address::find($request->address_id);
        if (!$address || $address->user_id != $user->id) {
          return response()->json(['status' => 'fail', 'code' => '401', 'message' => '收货地址有误']);
        }
        #确认订单时就把库存数量减掉
        #是否使用优惠券
        // isexist($request->coupons)
        $preferentialtotal = 0;
        if ($request->coupons) {
           $data = UserCoupon::find($request->coupons)->coupons;
           $preferentialtotal = $data ? $data->par_value : 0;
        }
        $out_trade_no =  date('YmdHis') . rand(1000, 9999);
        $prepay_id = $type == 0 ? $this->getPrepayId($request, $out_trade_no) : '';
        $order = Order::create([
          'user_id' => $user->id,
          'type' => $type, //付款方式 0=微信支付，1=M币支付
          'consignee' => $address->consignee,
          'phone' => $address->phone,
          'address' => $address->areas .' '. $address->address,
          'pay_id' => $prepay_id,//微信支付id
          'tradetotal' => $orders['sum'],//订单总金额
          'preferentialtotal' => $preferentialtotal,//订单优惠金额
          'customerfreightfee' => '0.00',//邮费
          'total' => $orders['sum']  - $preferentialtotal,//订单实际应付金额
          'out_trade_no' => $out_trade_no,
          'remark' => $request->remark
        ]);

        if (!$order) {
          return response()->json(['status' => 'fail', 'code' => '422', 'message' => '订单创建失败']);
        }
        #TODO 添加订单详情 orderItem
        event(new OrderItemEvent($order, $cart, $request->byCart));
        #TODO 删除购物车相关数据
        if($request->byCart) {
          $this->deleteCarts($user, $orderArr);
        }
        #优惠券已使用
        $request->coupons && UserCoupon::where('id', $request->coupons)->update(['status' => 1]);
        #待付款订单15分钟自动取消
        CancelOrder::dispatch($order)->delay(Carbon::now()->addMinutes(1));

        $data = [
          'money' => $order->total,
          'out_trade_no' => $order->out_trade_no,
          'order_id' => $order->id,
          'prepay_id' => $prepay_id,
          'type' => $order->type
        ];

        // return toXml(['return_code' => 'SUCCESS', 'return_msg' => 'OK', 'code' => '201', 'data' => $data]);
        return response()->json(['status' => 'success', 'code' => '201', 'message' => '订单创建成功', 'data' => $data]);
    }

    /**微信统一下单
     * @return [type]           [description]
     */
    public function getPrepayId($request, $out_trade_no)
    {
        $payment = \EasyWeChat::payment();
        $result = $payment->order->unify([
            'body' => '米可美汇商城消费',
            'out_trade_no' => $out_trade_no,
            'total_fee' => 1,
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR'], // 可选，如不传该参数，SDK 将会自动获取相应 IP 地址
            // 'notify_url' => 'https://pay.weixin.qq.com/wxpay/pay.action', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'trade_type' => 'JSAPI',
            'openid' => auth()->user()->weixin_openid,
        ]);
        if ($result['return_code'] == 'SUCCESS') {
            return $result['prepay_id'];
        } else {
            return response()->json(['status' => 'fail', 'code' => '422', 'message' => $result['return_msg']]);
        }
    }

    public function update(Request $request)
    {
        // dump($request->all());
        // if(!$request->out_trade_no || !$request->prepay_id) {
        //   return response()->json(['status' => 'fail', 'code' => '401', 'message' => '请求错误']);
        // }
        $where[] = $request->out_trade_no ?  ['out_trade_no', '=', $request->out_trade_no] : ['prepay_id', '=', $request->prepay_id];
        $order =   $order = Order::where($where)->firstOrFail();

        // if ($order['type'] == 1 && $user->m_current < $order['total']) {
        //   return response()->json(['status' => 'fail', 'code' => '401', 'message' => 'M币不足']);
        // }

        if (2 === $order->status) {
          return response()->json(['status' => 'fail', 'code' => '401', 'message' => '请勿重复支付']);
        }
        $order->type == 0 ? $this->wxpay($order) : $this->mbpay($order);
        return response()->json(['status' => 'success', 'code' => '201', 'message' => '操作成功']);
    }
    /**
    * 微信支付成功
    */
    public function wxpay($order)
    {
        $user = auth()->user();

        #M增加
        $integral = $order->total;
        $current = $user->integral_current + $integral;
        #现金付款
        $result = $this->exchanges->wx_pay($order);
        if ($result) {
          #订单--已付
          $order->status = 2; //已付
          $order->paiedtotal = $order->total;
          $order->save();
          #代理--M币增加
          $this->exchanges->m_add($order);
          #是否有上级推荐购买：
          $this->exchanges->get_share($order->id);
          return response()->json(['status' => 'success', 'code' => '201', 'message' => '订单支付成功']);
        }
        return response()->json(['status' => 'fail', 'code' => '422', 'message' => '订单支付失败']);
    }
    /**
    * M币支付
    */
    public function mbpay($order)
    {
        $user = $order->users;
        $res = $user->m_current - $order->total;
        if ($res < 0) {
          return response()->json(['status' => 'fail', 'code' => '401', 'message' => '您的M币不足']);
        }
        #扣除M币
        $result = $this->exchanges->m_pay($order, $user->id);

        if ($result) {
          #订单--已付
          $order->status = 2; //已付
          $order->save();
          return response()->json(['status' => 'success', 'code' => '201', 'message' => '订单支付成功']);
        }
        return response()->json(['status' => 'fail', 'code' => '422', 'message' => '订单支付失败']);
    }

    //分享赚
    public function awards()
    {
        $rank = Redis::get('user_rank');
    }
}
