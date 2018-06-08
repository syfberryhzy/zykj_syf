<?php

namespace App\Transformers;

use App\Models\ShoppingCart as Cart;
use League\Fractal\TransformerAbstract;

class CartTransformer extends TransformerAbstract
{
    public function transform(Cart $cart)
    {
        return [
            'id' => $item->id,
            'title' => $item->title,
            'image' => env('APP_URL_UPLOADS', ''). '/' . $item->image,
            'url' => $item->url
        ];
    }
}
