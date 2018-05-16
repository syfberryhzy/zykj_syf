<?php

namespace App\Policies;

use App\Models\Coupon;
use Illuminate\Auth\Access\HandlesAuthorization;
use Carbon\Carbon;

class CouponPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function view(Coupon $coupon)
    {
        $end = Carbon::parse($coupon->end_at);
        $now = Carbon::now();
        if ($coupon->status == 1 && $now->lt($end)) {
          return true;
        }
        return false;
    }
}
