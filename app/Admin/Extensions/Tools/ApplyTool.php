<?php

namespace App\Admin\Extensions\Tools;

use Encore\Admin\Grid\Tools\BatchAction;
use Encore\Admin\Admin;

class ApplyTool extends BatchAction
{
    protected $id;
    protected $action;

    public function __construct($id = 1, $action = true)
    {
        $this->id = $id;
        $this->action = $action;
    }

    public function script()
    {
        return <<<EOT

        $('.applyaction').on('click', function () {

            console.log($(this).data('id'));
            var id = $(this).data('id');
            var action = $(this).data('action');

            $.ajax({
                method: 'post',
                url: '/admin/member/applies/operate/'+id,
                data: {
                    _token:LA.token,
                    action:action
                },
                success: function (res) {
                    $.pjax.reload('#pjax-container');
                    if (res.status == 1) {
                      toastr.success(res.message);
                    } else {
                      toastr.warning(res.message);
                    }
                }
            });

        });

EOT;
    }

    protected function render()
    {
        Admin::script($this->script());
        if ($this->action == 1) {
            return '<button type="button" class="btn btn-info btn-xs applyaction" data-id="'. $this->id .'" data-action="'. $this->action .'"><i class="fa fa-save"></i>&nbsp;同意</button>';
        } elseif ($this->action == 2) {
            return '&nbsp;<button type="button" class="btn btn-warning btn-xs applyaction" data-id="'. $this->id .'" data-action="'. $this->action .'"><i class="fa fa-save"></i>&nbsp;拒绝</button>';
        }
    }

    public function __toString()
    {
        return $this->render();
    }
}
