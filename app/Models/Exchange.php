<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Exchange extends Model
{
    const INTEGRAL_STATUS = 1;
    const MCOIN_STATUS = 2;
    const WITHDRAW_STATUS = [3, 4];
    public function users()
    {
      return $this->belongsTo(User::class, 'user_id');
    }
}
