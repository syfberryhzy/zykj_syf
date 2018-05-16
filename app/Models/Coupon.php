<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\UserCoupon;

class Coupon extends Model
{
    protected $timestramp = false;


    protected $hidden = [];

    public function items()
    {
      return $this->hasMany(UserCoupon::class);
    }

    // public function setEndAtAttribute()
    // {
    //     return $this->attributes['end_at'] = date('Y-m-d H:i:s');
    // }


        /**
         * The "booting" method of the model.
         *
         * @return void
         */
        protected static function boot()
        {
            static::bootTraits();
        }


}
