<?php

namespace App\Admin\Extensions\Tools;

use Encore\Admin\Grid\Tools\BatchAction;
use Encore\Admin\Admin;

class ButtonTool extends BatchAction
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

        $('.applyaction').on('click', function () {

            console.log($(this).data('id'));
            var id = $(this).data('id');
            var action = $(this).data('action');
            var url = $(this).data('url');
            $.ajax({
                method: 'post',
                url: url + '/operate/'+id,
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
