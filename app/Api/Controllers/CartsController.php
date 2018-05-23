<?php

namespace App\Api\Controllers;

use App\Models\Product;
use App\Models\ProductItem;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\CartRepositoryEloquent;

class CartsController extends Controller
{
    public $carts;

    public function __construct(CartRepositoryEloquent $repository)
    {
        $this->carts = $repository;
    }

    public function index()
    {
        $user = auth()->user();
        if ($user->session_id === null) {
            tap($user)->update(['session_id' => \Session::getId()]);
        }
        request()->session()->setId($user->session_id);
        $datas = $this->carts->getCartsAll($user);
        return $datas;
    }

    public function checkCart($item)
    {
        $product = $item->product;
        if ($item->status != 1 || $item->quantity == 0) {
          return response()->json(['status' => 'fail', 'code' => '401', 'message' => '该规格已售完']);
        }

        if ($product->status != 1) {
          return response()->json(['status' => 'fail', 'code' => '401', 'message' => '该商品已下架']);
        }
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ProductItem $item, Request $request)
    {
        $this->checkCart($item);
        $user = auth()->user();
        if ($user->session_id === null) {
            tap($user)->update(['session_id' => \Session::getId()]);
        }
        $row = $this->carts->addCart($user, $item, $request);

        return response()->json(['status' => 'success', 'code' => '201', 'message' => '添加成功', 'data' => $row->rawId()]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(ProductItem $item, Request $request)
    {
        $user = auth()->user();
        $this->checkCart($item);
        if ($request->qty == 0) {
            return response()->json(['status' => 'fail', 'code' => '401', 'message' => '加购数量不符合']);
        }
        $this->carts->editCart($user, $item, $request);
        return response()->json(['status' => 'success', 'code' => '201', 'message' => '添加成功', 'data' => $request->cart]);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $user = auth()->user();
        $request->cart ? $this->carts->deleteCart($user, $request->cart) : $this->carts->destroyCart($user);

        return response()->json(['status' => 'success', 'code' => '201', 'message' => '删除成功']);
    }
}
