<?php

namespace App\Admin\Extensions\Tools;

use Encore\Admin\Grid\Tools\BatchAction;
use Encore\Admin\Admin;

class Send extends BatchAction
{
    protected $id;
    protected $action;
    protected $text;
    protected $url;

    public function __construct($id = 1, $action = true, $text, $url)
    {
        $this->id = $id;
        $this->action = $action;
        $this->text = $text;
        $this->url = $url;
    }

    public function script()
    {
        return <<<EOT

$(".applyaction").on("click", function() {
  var id = $(this).data("id");
  var action = $(this).data("action");
  var url = $(this).data("url");
  swal(
    {
      title: "输入物流公司",
      type: "input",
      showCancelButton: true,
      closeOnConfirm: false,
      confirmButtonText: "确认",
      cancelButtonText: "取消"
    },
    function(inputValue) {
      if (inputValue === false) {
        return;
      }
      if (!inputValue) {
        swal("物流公司不能为空", "", "error");
        return;
      }
      swal(
        {
          title: "输入物流单号",
          type: "input",
          showCancelButton: true,
          closeOnConfirm: false,
          confirmButtonText: "确认",
          cancelButtonText: "取消"
        },
        function(freightbillno) {
          // 用户点击了取消，inputValue 为 false
          // === 是为了区分用户点击取消还是没有输入
          if (freightbillno === false) {
            return;
          }
          if (!freightbillno) {
            swal("物流单号不能为空", "", "error");
            return;
          }
          $.ajax({
            method: "post",
            url: url + "/" + id,
            type: "POST",
            data: JSON.stringify({
              // 将请求变成 JSON 字符串
              express_company: inputValue,
              freightbillno: freightbillno,
              _token: LA.token
            }),
            contentType: "application/json", // 请求的数据格式为 JSON
            success: function(data) {
              // 返回成功时会调用这个函数
              swal(
                {
                  title: "操作成功",
                  type: "success"
                },
                function() {
                  // 用户点击 swal 上的 按钮时刷新页面
                  location.reload();
                }
              );
            },
            error: function(data) {
              console.log(data.responseJSON);
              swal({
                title: data.responseJSON.msg,
                type: "warning",
                showCancelButton: false,
                closeOnConfirm: true,
                closeOnCancel: true
              });
            }
          });
        }
      );
    }
  );
});


EOT;
    }

    protected function render()
    {
        Admin::script($this->script());
        //return '<button type="button" class="btn btn-info btn-xs applyaction" data-id="'. $this->id .'" data-action="'. $this->action .'" data-url="'.$this->url.'" ><i class="fa fa-save"></i>&nbsp;'.$this->text.'</button>';
        if ($this->action == 1) {
            return '<button type="button" class="btn btn-info btn-xs applyaction" data-id="'. $this->id .'" data-action="'. $this->action .'" data-url="'.$this->url.'" ><i class="fa fa-save"></i>&nbsp;'.$this->text.'</button>';
        } elseif ($this->action == 0) {
            return '&nbsp;<button type="button" class="btn btn-warning btn-xs applyaction" data-id="'. $this->id .'" data-action="'. $this->action .'" data-url="'.$this->url.'"><i class="fa fa-save"></i>&nbsp;'.$this->text.'</button>';
        }
    }

    public function __toString()
    {
        return $this->render();
    }
}
