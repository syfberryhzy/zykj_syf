<?php

namespace App\Api\Controllers;

use App\Models\Product;
use App\Models\ProductItem;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CartsController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if ($user->session_id === null) {
            tap($user)->update(['session_id' => \Session::getId()]);
        }
        request()->session()->setId($user->session_id);
        \ShoppingCart::name('cart.user.' . $user->id);
        return \ShoppingCart::all();
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
        request()->session()->setId($user->session_id);

        \ShoppingCart::name('cart.user.' . $user->id);
        $price = $user->status == 1 ? number_format($item->unit_price - $product->diff_price) : $item->unit_price;
        $row = \ShoppingCart::add(
            $item->id,
            $product->title,
            $request->qty ?? 1,
            $price,
            $options = [
                'size' => $item->norm,
                'product_id' => $product->id,
                'image' => env('APP_URL_UPLOADS', ''). '/' . $product->images[0]
            ]
        );

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
        $this->checkCart($item);
        if ($request->qty == 0) {
            return response()->json(['status' => 'fail', 'code' => '401', 'message' => '加购数量不符合']);
        }

        $user = auth()->user();
        request()->session()->setId($user->session_id);
        \ShoppingCart::name('cart.user.' . $user->id);
        $cart = \ShoppingCart::get($request->cart);
        if ($item->quantity < $cart->qty + $request->qty) {
            return response()->json(['status' => 'fail', 'code' => '401', 'message' => '该商品库存不足']);
        }

        \ShoppingCart::update($request->cart, $request->qty);
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
        request()->session()->setId($user->session_id);
        \ShoppingCart::name('cart.user.' . $user->id);
        $request->cart ? \ShoppingCart::remove($request->cart) : \ShoppingCart::destroy();
        return response()->json(['status' => 'success', 'code' => '201', 'message' => '删除成功']);
    }
}
