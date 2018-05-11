<?php

namespace App\Admin\Controllers;

use App\Models\Exchange;
use App\Models\User;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class WithdrawController extends Controller
{
    use ModelForm;

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index()
    {
        return Admin::content(function (Content $content) {

            $content->header('提现记录');
            $content->description('列表');

            $content->body($this->grid());
        });
    }

    /**
     * Edit interface.
     *
     * @param $id
     * @return Content
     */
    public function edit($id)
    {
        return Admin::content(function (Content $content) use ($id) {

            $content->header('提现记录');
            $content->description('查看');

            $content->body($this->form()->edit($id));
        });
    }

    /**
     * Create interface.
     *
     * @return Content
     */
    public function create()
    {
        return Admin::content(function (Content $content) {

            $content->header('提现记录');
            $content->description('添加');

            $content->body($this->form());
        });
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Admin::grid(Exchange::class, function (Grid $grid) {
            $grid->model()->whereIn('status', Exchange::WITHDRAW_STATUS)->orderBy('created_at', 'desc');
            $grid->id('ID')->sortable();
            $grid->column('users.username', '用户');
            $grid->total('开始');
            $grid->column('amount', '数目')->display(function () {
              return $this->type == 1 ? '<i style="color:#3c8dbc;">+</i> '. $this->amount : '<i style="color:#dd4b39;">-</i> ' . $this->amount;
            });
            $grid->current('结束');
            $grid->column('提现状态')->display(function () {

              return $this->status == 3 ? '<span class="label label-default">待打款</span>' : '<span class="label label-success">已打款</span>';
            });
            $grid->created_at('申请时间');
            // $grid->updated_at();
            $grid->disableExport();
            $grid->disableRowSelector();
            $grid->disableCreateButton();
            $grid->filter(function ($filter) {
              $filter->disableIdFilter();
              $filter->equal('user_id', '用户')->select(User::all()->pluck('username', 'id'));
              $filter->between('created_at', '申请时间')->datetime();
            });
            $grid->actions(function ($actions) {
                $actions->disableDelete();
                $actions->append('<a href=""><i class="fa fa-eye"></i></a>');
            });
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form(Exchange::class, function (Form $form) {

            $form->display('id', 'ID');
            $form->select('user_id', '用户')->options(User::all()->pluck('username', 'id'));
            $form->currency('total', '开始');
            $form->currency('amount', '数目');
            $form->currency('current', '结束');
            $form->radio('model', '来源')->options(['1'=> 'Exchanges','2'=> 'Order'])->default('1');
            $form->number('uri', '标注');
            // $form->saving(function (Form $form) {
            //   $form->uri =
            // });
            $form->display('created_at', '添加时间');
            $form->display('updated_at', '编辑时间');
        });
    }
}
