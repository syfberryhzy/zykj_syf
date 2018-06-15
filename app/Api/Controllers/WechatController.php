<?php

namespace App\Api\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\CancelOrder;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\ShoppingCart as Cart;
use App\Models\Address;
use App\Models\Coupon;
use App\Models\UserCoupon;
use App\Models\Exchange;
use App\Models\Product;
use App\Models\ProductItem;
use App\Requests\OrderRequest;
use App\Events\OrderItemEvent;
use App\Events\OrderEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Repositories\CartRepositoryEloquent;
use App\Repositories\ExchangeRepositoryEloquent;
use App\Exceptions\InvalidRequestException;

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
		$order = \DB::transaction(function () use ($user, $request, $orders) {
    			//$orders = unserialize(Redis::get($user->id));
    			$cart = $orders['cart'];
    			$type = $orders['type'];

    			$address = Address::find($request->address_id);
    			if (!$address || $address->user_id != $user->id) {
    				return response()->json(['status' => 'fail', 'code' => '401', 'message' => '收货地址有误']);
    			}

    			#是否使用优惠券
    			$preferentialtotal = 0;
    			if ($request->coupons) {
    				$data = UserCoupon::find($request->coupons)->coupons;
    				$preferentialtotal = $data ? $data->par_value : 0;
    			}
    			$out_trade_no =  date('YmdHis') . rand(1000, 9999);
    			//微信支付凭证

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
    				'remark' => $request->remark,
    				'attach' => Redis::get('user_rank')//附加參數 string
    			]);

    			if (!$order) {
    				    return response()->json(['status' => 'fail', 'code' => '422', 'message' => '订单创建失败']);
			    }
			    #库存数量减掉

    			foreach($cart as $key => $val) {
    				$items = ProductItem::find($val['item_id']);

    				if ($items->decreaseStock($val['qty']) <= 0) {
    					return response()->json(['status' => 'fail', 'code' => '422', 'message' => '该商品库存不足']);
    					throw new InvalidRequestException('该商品库存不足');
					}
					$pro = Product::where('id', $val['product_id'])->increment('sale_num', $val['qty']);
    			}
				#TODO 删除购物车相关数据
				if($request->byCart) {
					$this->deleteCarts($user, $orderArr);
				}
    			return $order;
            //return response()->json(['status' => 'success', 'code' => '201', 'message' => '订单创建成功', 'data' => $data]);
        });
		$data = [
    		'money' => $order->total,
    		'out_trade_no' => $order->out_trade_no,
    		'order_id' => $order->id,
    		'prepay_id' => $order->pay_id,
    		'type' => $order->type
    	];
    		#TODO 添加订单详情 orderItem
  			event(new OrderItemEvent($order, $cart, $request->byCart));

  			#优惠券已使用
  			$request->coupons && UserCoupon::where('id', $request->coupons)->update(['status' => 1]);
  			#待付款订单15分钟自动取消
  			//CancelOrder::dispatch($order)->delay(Carbon::now()->addMinutes(1));
  			$this->dispatch(new CancelOrder($order, 60));//60s
		    return response()->json(['status' => 'success', 'code' => '201', 'message' => '订单创建成功', 'data' => $data]);
  }

  public function refund_service($guanlian)
     {
         $order = DB::table('user_order')->where('guanlian', $guanlian)->first();
         require app_path().'\Helper\aop\AopClient.php';
         require app_path().'\Helper\aop\request\AlipayTradeRefundRequest.php';
         $aop = new \AopClient ();
         $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
         $aop->appId = '2017112000054766';
         $aop->rsaPrivateKey = 'MIIEpQIBAAKCAQEA59xAliFlfkhDGt4+vtcaOlFAint5FIju65MNVIN7tYede/FuRvB1XJpPlmsarKjqfdJrbamB14KvasAbols+3DRACtn1R7OvCSeBGmag2lNwC30vrPoXpKLln8DvfcTYKabrBF/vWQ63FnN5YXlm4Sja/IeGfOqn50NpjIMYz5hxcgAB84wD7ODaVtzZgkvmihRU5N89Cf3thG5QHJsGummspKb5ytL4eyfxkLYFT+npv2+8bQhowLh0PRzxMP/hg09IqIjhMiCelfFrg7pe71uWQfArYWNQdAXnVaMbXVqBR9SoglhO/zcrVieGMPB5sQEpPugKTf9SrTNyq9Q7kQIDAQABAoIBAQDIT01xJpsTdYSb8sOMhjM/jLDQswmRBxg6Z0nN6OX4l5gj2xnlqZoLDbmSfyeFYU1stFxhWl81e87mz99P7bqp7W4isdipQIAIzZtI3r86v3j+RAHrVAkXEDCHStzc8DG8ElvZ5LPYYdElUU/dOU/7WBuQrdkvlF7IekH2xc+qkCkDSokMcwyCQTFLSeRTXkMGEzZEFhYM0NdKUeoiIIL2iekcepHfpsDwIqc1mNtZz5K8O9ZiFFWbvS5Q+BB9iptqXJWbgQBM7Oo+L1eFPyXArqyrSF12JqJCBQhwy+UOguzrGsebozV4toe1o8hM9oXrZLmcBiQaoM9If4slPvUNAoGBAPP21psovwRVOkNYSDKyaSWJVUP5zwJ6sSWm1NZlzUoeef2Qkgemplj6Bmo0pcd3eF2Ztvfu0RijQc+fTMNzritbXJaen7L7f5jxpD508bGNlHqult+y5VeNmvOM7bWoKhfooyBJQ5JGv7drzJmvHqYD9HMlJp2XsEqsSKGL63wPAoGBAPNMjDBtJJxZhdj2+rGFrnvzJjeHsaKGxLiDuvDwpMbKjGhydaGHAX/T4Be/Gdq7dh29KkaZH97iUuaVcBBqSW2ujl5//9V4odSjS4k6uGeSTx/6y3/kmrfBrygB7jgSKMS2zEaJuM57LtBUH4xmeq5THE5I42cccbO/3jCp8a5fAoGBAMf5lYAprioHEnMRclzcEYRLRjEqG52UpJCQZ/Y2DEitIqHOV2UeHUzh5VA5R4pxS6Ct12TzxUHE0LU3htzPffzcLtDnxVAZB0Z/DHqFsXgw7XyCj/ld0tApqtHouxEkfxyJ/O0CIPlONOhM3LE88opyw3V/BmA3brJG9mI1JxnRAoGBAN99XHWLfIrmrU3tKcHyY6JWa6+sxR7fn0tDLnDvDN3S54F2StnS8yyhywLlN3G2q7yLrI7nT+Bkk/ReJ2/cwpCvPPZPrAlC4505V0S6nPP+8RIWReK4cusDTst4YoQ9Ihf5NtJA5nM9snYKIGTPKjiB/clnqQRpm4SbZhXbtjcPAoGAR1BP7MIqu3OWapB/ql8kqOYoncPefaYirW0XK5x78LPJ5tpnlt7oSqsVcb9+hiArYHwN7/bonSCu4TIYwBXBk7j6tOxVI9JKVmQsJMIKoUSErwZG/FVbpW7So3DpSWfAfcqeCZjUXd4hOZJBTooAOc/OwuB9RcRVegdB8uQZ/Ug=';
         $aop->alipayrsaPublicKey='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhKrvvWTTFTnPox3LHx683kw4C1z3rlyXmPZUTjZRSPn5cbE/SXxrRDWf6SJAxYEdoBrPpwEeyw7kB//Zw2ZOBCqdVPAuYHeQnlDYj/S7oqp3HbmGhyS139oHxxsqojp1tk/f5U7rVzEP6FJeM+vhEA+aoLR9uT7SsPxgsPWhV1y8Gx+teFlyaa4a3ISO9TWibFkGI/sbGLj2tSPo/Mvg2+xWM/0+DJsIx81u2BdwQLK1/9i5qoxwRB/SXYDjaU37yMiPj3Ee87B+0MIsotaTyxKY/BMuwZxTpVrwGhgbCRuGCQ/7Vhv2TAxYiyyMwjDcclLQQV+/C7EB+r64tjs+ewIDAQAB';
         $aop->apiVersion = '1.0';
         $aop->signType = 'RSA2';
         $aop->postCharset='utf-8';
         $aop->format='json';
         $req = new \AlipayTradeRefundRequest ();
         $list = array(
             'out_trade_no' => $order->guanlian,
             'refund_amount' => 0.01,
         );
         $data = json_encode($list);
         $req->setBizContent($data);
         $result = $aop->execute ( $req);

         $responseNode = str_replace(".", "_", $req->getApiMethodName()) . "_response";
         return $result->$responseNode->code;

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
            dump($result);
            return $result['prepay_id'];
        } else {
            return response()->json(['status' => 'fail', 'code' => '422', 'message' => $result['return_msg']]);
        }
    }

    public function refund(Order $order)
    {
      dd($order);
      if($order->type == 1) {
        return response()->json(['status' => 'fail', 'code' => '401', 'message' => '兑换商品不支持退款']);
      }
      if($order->status == 0) {
        return response()->json(['status' => 'fail', 'code' => '401', 'message' => '未付款订单不支持退款']);
      }
      $payment = \EasyWeChat::payment();
      // $payment->refund->byTransactionId(string $transactionId, string $refundNumber, int $totalFee, int $refundFee, array $config = []);

      $out_refund_no =  date('YmdHis') . rand(1000, 9999);
      $result = $payment->refund->byTransactionId($order->prepay_id, $out_refund_no, $order->paiedtotal, $order->paiedtotal, [
          // 可在此处传入其他参数，详细参数见微信支付文档
          'refund_desc' => '商品已售完',
      ]);
      return [$result, $out_refund_no];
    }


    public function update(Request $request)
    {
        // dump($request->all());
        // if(!$request->out_trade_no || !$request->prepay_id) {
        //   return response()->json(['status' => 'fail', 'code' => '401', 'message' => '请求错误']);
        // }
        $where[] = $request->out_trade_no ?  ['out_trade_no', '=', $request->out_trade_no] : ['prepay_id', '=', $request->prepay_id];
        $order =   $order = Order::where($where)->firstOrFail();
        $user_rank = $order->attach;
        Redis::set('user_rank', $user_rank);

        // if ($order['type'] == 1 && $user->m_current < $order['total']) {
        //   return response()->json(['status' => 'fail', 'code' => '401', 'message' => 'M币不足']);
        // }

        // if (2 === $order->status) {
        //   return response()->json(['status' => 'fail', 'code' => '401', 'message' => '请勿重复支付']);
        // }
        $order->type == 0 ? $this->wxpay($order) : $this->mbpay($order);
        return response()->json(['status' => 'success', 'code' => '201', 'message' => '操作成功']);
    }
    /**
    * 微信支付成功
    */
    public function wxpay($order)
    {
        $userID = $order->user_id;
        #现金付款
        $result = $this->exchanges->wx_pay($userID, $order);
        if ($result) {
          #订单--已付
          $order->status = 2; //已付
          $order->paiedtotal = $order->total;
          $order->save();
          #代理--M币增加
          $this->exchanges->m_add($userID, $order);
          #是否有上级推荐购买：
          $add = $this->exchanges->get_share($userID, $order->id);
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
        $result = $this->exchanges->m_pay($user->id, $order);

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
