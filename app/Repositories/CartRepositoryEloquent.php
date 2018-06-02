<?php

namespace App\Repositories;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\CartRepository;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;

use App\Validators\CartValidator;

/**
 * Class CartRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class CartRepositoryEloquent extends BaseRepository implements CartRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return Cart::class;
    }



    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

    public function getCartsAll($user)
    {
        request()->session()->setId($user->session_id);
        \ShoppingCart::name('cart.user.' . $user->id);
        return \ShoppingCart::all();
    }

    public function getCarts($user, $rowIds)
    {
        request()->session()->setId($user->session_id);
        \ShoppingCart::name('cart.user.' . $user->id);
        $datas = array_map($rowIds, function ($item, $key) {
          return \ShoppingCart::get($item);
        });
        return $datas;
    }

    public function add_order_item($user, $rowIds, $order)
    {
        request()->session()->setId($user->session_id);
        \ShoppingCart::name('cart.user.' . $user->id);
        foreach($rowIds as $item) {
          $cart = \ShoppingCart::get($item);
          OrderItem::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'product_id' => $cart->product_id,
            'title' => $cart->title,
            'norm' => $cart->size,
            'num' => $cart->qty,
            'pre_price' => $cart->price,
            'total_price' => $cart->total,
          ]);

        }
        \ShoppingCart::remove($item);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addCart($user, $item, $request)
    {
        $product = $item->product;
        request()->session()->setId($user->session_id);
        \ShoppingCart::name('cart.user.' . $user->id);
        $price = $user->status == 1 ? ($item->unit_price - $product->diff_price) : $item->unit_price;
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
        return $row;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function editCart($user, $item, $request)
    {
        request()->session()->setId($user->session_id);
        \ShoppingCart::name('cart.user.' . $user->id);
        $cart = \ShoppingCart::get($request->cart);

        \ShoppingCart::update($request->cart, $request->qty);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function deleteCart($user, $rowId)
    {
        request()->session()->setId($user->session_id);
        \ShoppingCart::name('cart.user.' . $user->id);
        \ShoppingCart::remove($rowId);
    }

    public function deleteCarts($user, $rowIds)
    {
        request()->session()->setId($user->session_id);
        \ShoppingCart::name('cart.user.' . $user->id);
        foreach($rowIds as $item) {
          \ShoppingCart::remove($item);
        }
    }

    public function destoryCart($user)
    {
      request()->session()->setId($user->session_id);
      \ShoppingCart::name('cart.user.' . $user->id);
      \ShoppingCart::destroy();
    }
}
