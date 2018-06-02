<?php

namespace App\Api\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\CancelOrder;
use App\Jobs\TranslateSlug;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\Address;
use App\Models\Coupon;
use App\Models\UserCoupon;
use App\Models\Exchange;
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
          ['more_value', '<=', $money],
          ['start_at', '<=', Carbon::now()],
          ['end_at', '>=', Carbon::now()],
        ];
        $data = Coupon::where($where)->select('id')->get();
        $coupons = UserCoupon::where('user_id', auth()->user()->id)->where('status', 0)->whereIn('coupon_id', $data)->get();
        return $coupons;
    }
    /**
    * 确认下单
    */
    public function orderSure(Request $request)
    {

        #检测库存???
        $orderArr = $request->orders;
        $user = auth()->user();
        // $cart = $request->byCart ? $this->carts->getCarts($user, $orderArr) : [$orderArr];
        $cart = $this->carts->getCartsAll($user);
        // dump(TranslateSlug::dispatch($cart, $operate = true));
        // dd($cart);
        $address = Address::where('user_id', $user->id)->orderBy('status', 'desc')->first();

        $orders = [
          'cart'  => collect($cart)->toArray(),
          'sum'  => collect($cart)->sum('total'),
          'num'  => collect($cart)->sum('qty')
        ];
        #优惠券
        $coupons = $this->get_coupons($orders);
        Redis::set($user->id, serialize($orders));

        // dd(unserialize(Redis::get($user->id)));
        return response()->json(['status' => 'success', 'code' => '201', 'data' => $orders, 'address' => $address, 'coupons' => $coupons]);
        if (TranslateSlug::dispatch($cart, $operate = true)) {
          $orders->cart = $cart;
          $orders->sum = collect($cart)->sum('total');
          $orders->num = collect($cart)->sum('qty');
          return response()->json(['status' => 'success', 'code' => '201', 'data' => $orders, 'address' => $address]);
        } else {
          return response()->json(['status' => 'fail', 'code' => '401', 'message' => '商品库存不足']);
        }

    }

    /**
    * 生成订单--立即购买、购物车
    */
    public function store(Request $request)
    {
        $user = auth()->user();
        $orders = unserialize(Redis::get($user->id));
        // $cart = $request->byCart ? $this->carts->getCarts($user, $orderArr) : [$orderArr];
        $cart = $orders['cart'];

        // $address = Address::find($request->address_id);
        // if (!$address || $address->user_id != $user->id) {
        //   return response()->json(['status' => 'fail', 'code' => '401', 'message' => '收货地址有误']);
        // }
        $address = Address::where('user_id', $user->id)->orderBy('status', 'desc')->first();

        // dd($orders['sum']);
        // 是否使用优惠券
        // isexist($request->coupons)
        // $request->coupons ? $preferentialtotal = UserCoupon::find($request->coupons)->amount();
        $order = Order::create([
          'user_id' => $user->id,
          'type' => $request->type ?? 0, //付款方式 0=微信支付，1=M币支付
          'consignee' => $address->consignee,
          'phone' => $address->phone,
          'address' => $address->areas .' '. $address->address,
          'tradetotal' => $orders['sum'] ?? '0.00',//订单总金额
          'preferentialtotal' => $request->preferentialtotal ?? '0.00',//订单优惠金额
          'customerfreightfee' => $request->customerfreightfee ?? '0.00',//邮费
          'total' => $orders['sum'] ?? '0.00',//订单实际应付金额
          'out_trade_no' => date('YmdHis') . rand(1000, 9999),
        ]);
        if (!$order) {
          return response()->json(['status' => 'fail', 'code' => '422', 'message' => '订单创建失败']);
        }
        #TODO 添加订单详情 orderItem
        event(new OrderItemEvent($order, $cart));
        #TODO 删除购物车相关数据
        // if($request->byCart) {
        //   $cart = $this->carts->deleteCarts($user, $orderArr);
        // }
        #待付款订单15分钟自动取消
        CancelOrder::dispatch($order)->delay(Carbon::now()->addMinutes(1));
        $data = [
          'money' => $order->total,
          'out_trade_no' => $order->out_trade_no,
          'order_id' => $order->id,
          'type' => $order->type
        ];
        return response()->json(['status' => 'success', 'code' => '201', 'message' => '订单创建成功', 'data' => $data]);
    }

    /*
    * 支付
    */
    public function pay_order(Order $order, Request $request)
    {
        $type = $request->type;
        if ($type == 1) {
          $this->wxpay($order);
        } else {
          $this->mbpay($order);
        }
    }
    /**微信统一下单
     * @return [type]           [description]
     */
    public function getPrepayId($request, $out_trade_no)
    {
        $params = [
            'appid' => env('WECHAT_MINI_PROGRAM_APPID'),
            'mch_id' => env('WECHAT_MINI_PROGRAM_SECRET'),
            'nonce_str' => getNonceStr('32'),
            'body' => '米可美汇商城消费',
            'out_trade_no' => $out_trade_no,
            // 'total_fee' => (int)($request->total * 100),
            'total_fee' => 1,
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],
            'trade_type' => 'JSAPI',
            'notify_url' => 'http://meijiasong.mandokg.com/wechat/payment/notify',
            'openid' => auth()->user()->openid
        ];

        $params['sign'] = makeSign($params);
        $xml = toXml($params);
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $res = fromXml(postXmlCurl($xml, $url, false, 6));
        // dd($params,$xml, $res);
        if ($res['return_code'] == 'SUCCESS') {
            return response()->json(['status' => 'success', 'code' => '201', 'message' => '支付成功']);
        } else {
            return response()->json(['status' => 'fail', 'code' => '422', 'message' => $res['return_msg']]);
        }
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
        $user = auth()->user();
        $res = $user->m_current - $order->total;
        if ($res < 0) {
          return response()->json(['status' => 'fail', 'code' => '401', 'message' => '您的M币不足']);
        }
        #扣除M币
        $result = Exchange::create([
          'user_id' => $user->id,
          'total' => $user->m_current,
          'amount' => $order->total,
          'current' => $res,
          'model' => "order",
          'uri' => $order->id,
          'status' => Exchange::MCOIN_STATUS,
          'type' => Exchange::REDUCE_TYPE
        ]);

        if ($result) {
          # 用户--M币
          $user->m_current = $res;
          $user->save();
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
