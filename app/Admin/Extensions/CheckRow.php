<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;

class CheckRow
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    protected function script()
    {
        return <<<SCRIPT

$('.grid-check-row').on('click', function () {
    var id = $(this).data('id');
    var status = $(this).data('status');
    $.ajax(function () {
      url:'admin/',
      data:{}
    });
    console.log($(this).data('id'));

});

SCRIPT;
    }

    protected function render()
    {
        Admin::script($this->script());

        return "<a class='btn btn-xs btn-success fa fa-check grid-check-row' data-id='{$this->id}' data-status='1' title='同意'></a>
        <a class='btn btn-xs btn-danger fa fa-times grid-check-row' data-id='{$this->id}' data-status='2' title='拒绝'></a>";
    }

    public function __toString()
    {
        return $this->render();
    }
}
