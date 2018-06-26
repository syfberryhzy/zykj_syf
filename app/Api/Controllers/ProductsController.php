<?php

namespace App\Api\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Evaluate;
use App\Transformers\ProductTransformer;
use Carbon\Carbon;

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
    public function evaluate(Product $product, Request $request)
    {
        $datas = Evaluate::where('product_id', $product->id)->where('status', 1)->orderBy('id', 'desc')->get();
        $total = count($datas);
    		$page = (int)$request->page ?? 1;
        $totalPage = (int)ceil($total / 10);
        $page > $totalPage && $page = $totalPage;
        $data = collect($datas)->forPage($page, 10);
      	$logs = [];
        foreach($data as $key => $item) {
      			$images = $item->images ? collect(json_decode($item->images))->map( function ($val, $index) {
				        return env('APP_URL_UPLOADS').'/evaluate/'. $val;
            }) : '';

            $logs[$key]['user_name'] = $item->users->username ?? $item->users->nickname;
            $logs[$key]['avatar'] = $item->users->avatar;

            $logs[$key]['pro_size'] = $item->orderItems->norm;
      			$logs[$key]['content'] = $item->content;
      			$logs[$key]['images'] = $images;
      			$logs[$key]['time'] = Carbon::parse($item->created_at)->toDateTimeString();
        }

       return response()->json(['status' => 'success', 'code' => '201', 'data' => $logs, 'toal' => $total, 'totalPage' => $totalPage]);
    }
}
