<?php

namespace App\Admin\Extensions\Tools;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\AbstractTool;
use Illuminate\Support\Facades\Request;

class OrderStatus extends AbstractTool
{
    protected function script()
    {
        $url = Request::fullUrlWithQuery(['status' => '_gender_']);

        return <<<EOT

$('input:radio.user-gender').change(function () {

    var url = "$url".replace('_gender_', $(this).val());

    $.pjax({container:'#pjax-container', url: url });

});

EOT;
    }

    public function render()
    {
        Admin::script($this->script());

        $options = [
            'all'   => '全部订单',
            '0'     => '待支付',
            '1'     => '已取消',
            '2'     => '待发货',
            '3'     => '待收货',
            '4'     => '已收货',
            '5'     => '已完成'
        ];

        return view('admin.tools.order', compact('options'));
    }
}
