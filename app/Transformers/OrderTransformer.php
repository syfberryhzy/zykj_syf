<?php

namespace App\Transformers;

use App\Models\Order;
use League\Fractal\TransformerAbstract;

class OrderTransformer extends TransformerAbstract
{
    public function transform(Order $item)
    {
        return [
            'id' => $item->id,
            'title' => $item->title,
            'price' => $item->pre_price,
            'vip_price' => number_format($item->pre_price - $item->diff_price, 2),
            'sale_num' => $item->sale_num,
            'image' => env('APP_URL_UPLOADS', ''). '/' . $item->images[0]
        ];
    }
}
