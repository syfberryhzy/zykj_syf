<?php

namespace App\Api\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ShoppingCart as Cart;
use App\Models\ProductItem;

class ShoppingCartsController extends Controller
{
  public function index()
  {

      $user = auth()->user();
      $data = Cart::where('user_id', $user->id)->get();
	  $datas = [];
	  foreach($data as $index => $value) {
      $item = $value->items;
		  $product = $value->product;
		  $value->price = $user->status == 1 ? ($item->unit_price - $product->diff_price) : $item->unit_price;
		  $value->image = env('APP_URL_UPLOADS', ''). '/'.$value->image;
		  if ($product->status == 0 || $item->quantity == 0) {
			  continue;
		  }else {
			  $datas[] = collect($value)->except(['items', 'product']);
		  }

      }
      return response()->json(['status' => 'success', 'code' => '201', 'data' => $datas]);

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

      $product = $item->product;
	  //dd($request->qty);
      $find = Cart::where(['user_id' => $user->id, 'item_id' => $item->id])->first();
      // $price = $user->status == 1 ? ($item->unit_price - $product->diff_price) : $item->unit_price;
  	  $qty = $request->qty ? $request->qty : 1;
      if ($find) {
        $find->increment('qty', $qty);
        $result = $find->save();
      } else {
        $result = Cart::create([
          'user_id' => $user->id,
          'item_id' => $item->id,
          'qty' => $qty,
          'name' => $product->title,
          'size' => $item->norm,
          'product_id' => $product->id,
          'image' => $product->images[0]
        ]);
      }
      if ($result) {
        return response()->json(['status' => 'success', 'code' => '201', 'message' => '添加成功']);
      }
      return response()->json(['status' => 'fail', 'code' => '422', 'message' => '添加失败']);
  }

  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function update(Cart $cart, Request $request)
  {
      $user = auth()->user();
      $this->checkCart($cart->items);
		if($request->type == 'reduce') {
			if($cart->qty == 1) {
				 return response()->json(['status' => 'fail', 'code' => '401', 'message' => '~_~受不了了，不能再减啦']);
			}
			$cart->decrement('qty', 1);
		} else {
			$cart->increment('qty', 1);
		}


      if ($cart->save()) {
        return response()->json(['status' => 'success', 'code' => '201', 'message' => '修改成功']);
      }
      return response()->json(['status' => 'fail', 'code' => '422', 'message' => '修改失败']);
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

	  if ($request->cart && $request->type == 'single') {
		  $result = Cart::find($request->cart)->delete();
	  } else if ($request->type == 'all'){
		  $result = $user->carts()->delete();
	  }
     if ($result) {
		  return response()->json(['status' => 'success', 'code' => '201', 'message' => '删除成功']);
	 }
	return response()->json(['status' => 'fail', 'code' => '422', 'message' => '删除失败']);

  }
}
