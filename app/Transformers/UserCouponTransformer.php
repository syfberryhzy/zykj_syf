<?php

namespace App\Transformers;

use App\Models\UserCoupon;
use League\Fractal\TransformerAbstract;
use Carbon\Carbon;

class UserCouponTransformer extends TransformerAbstract
{
    public function transform(UserCoupon $info)
    {
        $coupons = $info->coupons;
        $coupons->start_at = Carbon::parse($coupons->start_at)->toDateString();
        $coupons->end_at = Carbon::parse($coupons->end_at)->toDateString();
        return [
            'id' => $info->id,
            'user_id' => $info->user_id,
            'coupons' => $coupons,
            'status' => $info->status
        ];
    }
}
