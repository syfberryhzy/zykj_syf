<?php

namespace App\Api\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Evaluate;
use App\Transformers\ProductTransformer;

class ProductsController extends Controller
{
    /*
    * 商品详情
    */
    public function show(Product $product)
    {
        $product->image_url = env('APP_URL_UPLOADS', ''). '/';
        $product->vip_price = $product->pre_price - $product->diff_price;
        $product->total_quantity = collect($product->items)->sum('quantity');
        $parent_id = auth()->user()->parent_id;
        $user_id = auth()->user()->id;
        // \Redis::zrem
        $parent_id && \Redis::zadd('history.' . $parent_id .'.chilren', time(), $user_id .'_'. $product->id. '_'. $product->title. '_'. time());
        // $parent_id && \Redis::zadd('user.' . $parent_id . '.history.user', time(), $user_id);
        // return $this->response->item($product, new ProductTransformer());
        return $product;
    }

    /*
    * 商品评价列表--分页
    */
    public function evaluate(Product $product)
    {
        $datas = Evaluate::where('product_id', $product->id)->where('status', 1)->paginate(10);
        // foreach($datas as $key => $item) {
        //     $item->
        // }
        return $this->response->array($datas);
    }
}
