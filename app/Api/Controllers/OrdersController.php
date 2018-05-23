<?php

namespace App\Api\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Order;
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
        return $this->response->collection($orders, new OrderTransformer());
    }
}
