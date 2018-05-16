<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Apply extends Model
{
    protected $fillable = ['user_id', 'parent_id', 'username', 'phone', 'wechat', 'areas', 'details'];

    public function parents()
    {
      return $this->hasMany(User::class, 'parent_id');
    }
    public function users()
    {
      return $this->belongsTo(\User::class, 'user_id');
    }
}
