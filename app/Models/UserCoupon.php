<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Coupon;

class UserCoupon extends Model
{
    protected $fillable = ['user_id', 'coupon_id', 'status'];

    public function users()
    {
      return $this->belongsTo(User::class, 'user_id');
    }

    public function coupons()
    {
      return $this->belongsTo(Coupon::class, 'coupon_id');
    }

}
