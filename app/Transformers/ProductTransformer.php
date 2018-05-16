<?php

namespace App\Transformers;

use App\Models\Product;
use League\Fractal\TransformerAbstract;

class ProductTransformer extends TransformerAbstract
{
    public function transform(Product $item)
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
