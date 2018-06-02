<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Recommend extends Model
{
    Protected $fillable = ['user_id', 'parent_id', 'recommend', 'member', 'visit', 'visitor', 'qr_code'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
