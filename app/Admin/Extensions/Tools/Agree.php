<?php

namespace App\Admin\Extensions\Tools;

use Encore\Admin\Grid\Tools\BatchAction;
use Encore\Admin\Admin;

class Agree extends BatchAction
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

        $('#btn-refund-disagree').click(function() {
          var url = $(this).data("url");
        // 注意：Laravel-Admin 的 swal 是 v1 版本，参数和 v2 版本的不太一样
          swal({
            title: '输入拒绝退款理由',
            type: 'input',
            showCancelButton: true,
            closeOnConfirm: false,
            confirmButtonText: "确认",
            cancelButtonText: "取消",
          }, function(inputValue){
            // 用户点击了取消，inputValue 为 false
            // === 是为了区分用户点击取消还是没有输入
            if (inputValue === false) {
              return;
            }
            if (!inputValue) {
              swal('理由不能为空', '', 'error')
              return;
            }
            // Laravel-Admin 没有 axios，使用 jQuery 的 ajax 方法来请求
            $.ajax({
              url: url,
              type: 'POST',
              data: JSON.stringify({   // 将请求变成 JSON 字符串
                agree: false,  // 拒绝申请
                reason: inputValue,
                // 带上 CSRF Token
                // Laravel-Admin 页面里可以通过 LA.token 获得 CSRF Token
                _token: LA.token,
              }),
              contentType: 'application/json',  // 请求的数据格式为 JSON
              success: function (data) {  // 返回成功时会调用这个函数
                swal({
                  title: '操作成功',
                  type: 'success'
                }, function() {
                  // 用户点击 swal 上的 按钮时刷新页面
                  location.reload();
                });
              },
          error: function (data) {
            console.log(data.responseJSON);
          swal({
                  title:data.responseJSON.msg,
                  type:"warning",
                  showCancelButton:false,
                  closeOnConfirm:true,
                  closeOnCancel:true
              });
          }
            });
          });
        });
        // 同意 按钮的点击事件
        $('#btn-refund-agree').click(function() {
          var url = $(this).data("url");
          swal({
            title: '确认要将款项退还给用户？',
            type: 'warning',
            showCancelButton: true,
            closeOnConfirm: false,
            confirmButtonText: "确认",
            cancelButtonText: "取消",
          }, function(ret){
            // 用户点击取消，不做任何操作
            if (!ret) {
              return;
            }
          $.ajax({
            url: url,
            type: 'POST',
            data: JSON.stringify({   // 将请求变成 JSON 字符串
              agree: true,  // 同意申请
              _token: LA.token,
            }),
            contentType: 'application/json',  // 请求的数据格式为 JSON
            success: function (data) {  // 返回成功时会调用这个函数

              swal({
                title: '操作成功',
                type: 'success'
              }, function() {
                // 用户点击 swal 上的 按钮时刷新页面
                location.reload();
              });
            },
          error: function (data) {
            console.log(data.responseJSON);
          swal({
                  title:data.responseJSON.msg,
                  type:"warning",
                  showCancelButton:false,
                  closeOnConfirm:true,
                  closeOnCancel:true
              });
          }
          });
           });
        });


EOT;
    }

    protected function render()
    {
        Admin::script($this->script());
        //return '<button type="button" class="btn btn-info btn-xs applyaction" data-id="'. $this->id .'" data-action="'. $this->action .'" data-url="'.$this->url.'" ><i class="fa fa-save"></i>&nbsp;'.$this->text.'</button>';
        if ($this->action == 1) {
            return '<button type="button" class="btn btn-info btn-xs applyaction" id="btn-refund-agree" data-id="'. $this->id .'" data-action="'. $this->action .'" data-url="'.$this->url.'" ><i class="fa fa-save"></i>&nbsp;'.$this->text.'</button>';
        } elseif ($this->action == 0) {
            return '&nbsp;<button type="button" class="btn btn-warning btn-xs applyaction" id="btn-refund-disagree" data-id="'. $this->id .'" data-action="'. $this->action .'" data-url="'.$this->url.'"><i class="fa fa-save"></i>&nbsp;'.$this->text.'</button>';
        }
    }

    public function __toString()
    {
        return $this->render();
    }
}
