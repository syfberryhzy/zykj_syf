<?php

namespace App\Api\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductItem;
use ShoppingCart;
use Illuminate\Support\Facades\Cache;

class CartsController extends Controller
{
    public $cart;
    public function __constrduct()
    {

        $this->cart = ShoppingCart::instance('syf_weapp');
    }

    public function index()
    {
        $identifier = 'user_1_cart';
        $this->cart->restore($identifier);
        try {
            $this->cart->store($identifier);
        } catch (\Gloudemans\Shoppingcart\Exceptions\CartAlreadyStoredException $e) {
            // \DB::table('shoppingcart')->where(['identifier' => $identifier])->delete();
            $this->cart->store($identifier);
        }
        return $this->response->array([
            'data' => $this->cart->content(),
            'count' => $this->cart->count(),
            'price' => $this->cart->subtotal
        ]);
    }
    public function create(ProductItem $item, CartRequest $request)
    {
        $product = $item->product;
        $identifier = 'user_1_cart';
        $this->cart->restore($identifier);
        $cacheKey = "{$identifier}.product.{$product->id}.product_item.{$item->id}";
        $rowId = Cache::tags(['shoppingcart'])->get($cacheKey);
        #检测是否已存在于购物车
        if ($this->cart->content()->has($rowId) && !is_null($request->qty)) {
            $this->cart->update($rowId, ['qty' => (int)$request->qty]);
        } else {
            $price = $user->status == 1 ? number_format($item->unit_price - $product->diff_price) : $item->unit_price;
            $data = $this->cart->add(
                $item->id,
                $product->title,
                $request->qty ?? 1,
                $price,
                $options = [
                    'size' => $item->norm,
                    'product_id' => $product->id,
                    'image' => env('APP_URL_UPLOADS', ''). '/' . $product->images[0]
                ]
            )->associate(Product::class);
            Cache::tags(['shoppingcart'])->forever($cacheKey, $data->rowId);
        }

        try {
            $this->cart->store($identifier);
        } catch (\Gloudemans\Shoppingcart\Exceptions\CartAlreadyStoredException $e) {
            \DB::table('shoppingcart')->where(['identifier' => $identifier])->delete();
            $this->cart->store($identifier);
        }
        return response($this->cart->content());

    }
    /**
     * 更新购物车中的一个商品，可修改多个，用于减少购物车的时候使用
     *
     * @return [type]           [description]
     */
    public function update(ProductItem $item, Request $request)
    {
        $data = request()->validate([
            'qty' => 'required'
        ]);
        $product = $item->product;
        $identifier = 'user_1_cart';
        $this->cart->restore($identifier);
        $cacheKey = "{$identifier}.product.{$product->id}.product_item.{$item->id}";
        $rowId = Cache::tags(['shoppingcart'])->get($cacheKey);

        if ($data['qty'] == 0) {
            // 删除
            Cache::forget($cacheKey);
            $this->cart->remove($rowId);
        } else {
            // 修改数量
            $this->cart->update($rowId, ['qty' => (int)$data['qty']]);
        }
        try {
            $this->cart->store($identifier);
        } catch (\Gloudemans\Shoppingcart\Exceptions\CartAlreadyStoredException $e) {
            \DB::table('shoppingcart')->where(['identifier' => $identifier])->delete();
            $this->cart->store($identifier);
        }
        return response($this->cart->content());
    }

    public function delete(ProductItem $item, Request $request)
    {
      $product = $item->product;
      $identifier = 'user_1_cart';
      $this->cart->restore($identifier);
      $cacheKey = "{$identifier}.product.{$product->id}.product_item.{$item->id}";
      $rowId = Cache::tags(['shoppingcart'])->get($cacheKey);

      // 删除
      Cache::forget($cacheKey);
      $this->cart->remove($rowId);

      try {
          $this->cart->store($identifier);
      } catch (\Gloudemans\Shoppingcart\Exceptions\CartAlreadyStoredException $e) {
          \DB::table('shoppingcart')->where(['identifier' => $identifier])->delete();
          $this->cart->store($identifier);
      }
      return response($this->cart->content());

    }
    /**
     * 清空购物车
     *
     * @return [type] [description]
     */
    public function destory(Request $request)
    {
        $this->destroy();
    }

}
