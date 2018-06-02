<?php

namespace App\Api\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Exchange;
use App\Models\Order;
use App\Models\OrderItem;
use App\Repositories\OrderRepositoryEloquent;
use App\Transformers\OrderTransformer;

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
        $status = $request->status ?? 0;
        $user = auth()->user();
        $orders = Order::where('user_id', $user->id);
        if ($status > 0) {
          $orders = Order::where('status', $status);
        }
        $orders = $orders->orderBy('created_at', 'desc')->paginate(10);
        return $this->response->item($orders, new OrderTransformer());
    }

    public function show(Order $order)
    {
        $order->username = $order->users->username;
        $order->items = $order->items;
        $order = collect($order)->except(['users']);
        return response()->json(['status' => 'success', 'code' => '201', 'data' => $order]);
    }
//     array:4 [
//   0 => array:3 [
//     "id" => 9
//     "num" => 2
//     "product_id" => 1
//   ]
//   1 => array:3 [
//     "id" => 11
//     "num" => 2
//     "product_id" => 3
//   ]
//   2 => array:3 [
//     "id" => 12
//     "num" => 4
//     "product_id" => 2
//   ]
//   3 => array:3 [
//     "id" => 13
//     "num" => 2
//     "product_id" => 2
//   ]
// ]
// array:3 [
//   0 => array:1 [
//     "product_id" => 1
//   ]
//   1 => array:1 [
//     "product_id" => 2
//   ]
//   2 => array:1 [
//     "product_id" => 3
//   ]
// ]
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
    public function share(Order $order)
    {
      $this->award(2, 53, 53, 'share_price');
      dd(123);
        $order->username = $order->users->username;
        $order->items = $order->items;
        $order = collect($order)->except(['users']);
        return response()->json(['status' => 'success', 'code' => '201', 'data' => $order]);
    }
}
