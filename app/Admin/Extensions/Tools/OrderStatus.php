<?php

namespace App\Admin\Extensions\Tools;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\AbstractTool;
use Illuminate\Support\Facades\Request;

class OrderStatus extends AbstractTool
{
    public $datas;
    public function __construct($data)
    {
      $this->datas = $data;
    }
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
            'all'   => '全部订单('.$this->datas['all'].')',
            '0'     => '待支付('.$this->datas['0'].')',
            '2'     => '待发货('.$this->datas['2'].')',
            '3'     => '待收货('.$this->datas['3'].')('.$this->datas['all'].')',
            '4'     => '已完成('.$this->datas['4'].')',
            '6'     => '退款中('.$this->datas['6'].')',
            '1'     => '已关闭('.$this->datas['1'].')',
        ];

        return view('admin.tools.order', compact('options'));
    }
}
