<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\UserCoupon;
use App\Models\Exchange;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Auth;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    protected $fillable = ['username', 'nickname', 'avatar', 'weixin_openid', 'weixin_seesion_key', 'areas', 'address', 'status', 'gender', 'session_id'];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

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
