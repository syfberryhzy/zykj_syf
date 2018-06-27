<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MchPay extends Model
{
    public $table ='mch_paies';

    public $fillable = ['user_id', 'partner_trade_no', 'desc', 'amount'];
}
