<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\UserCoupon;
use App\Models\Exchange;

class User extends Model
{
    public function userCoupons()
    {
      return $this->hasMany(UserCoupon::class);
    }

    public function applies()
    {
      return $this->hasMany(App\Models\Apply::class, 'user_id');
    }

    public function parents()
    {
      return $this->hasMany(App\Models\Apply::class, 'parent_id');
    }

    public function exchanges()
    {
      return $this->hasMany(Exchange::class, 'user_id');
    }
}
