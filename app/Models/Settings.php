<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    const MALL_NAME_ID = 1;//公司名称
    const MALL_SCALE_ID = 2;//M币获取比例

    protected $talbe = 'settings';

    // protected $fillable = ['']
}
