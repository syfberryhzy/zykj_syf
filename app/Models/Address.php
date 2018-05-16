<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = ['user_id', 'consignee', 'phone', 'areas', 'address', 'gender', 'status', 'zipcode'];
}
