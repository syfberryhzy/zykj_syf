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

class McoinController extends Controller
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

            $content->header('M币明细');
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

            $content->header('M币明细');
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

            $content->header('M币明细');
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
          $grid->model()->where('status', Exchange::MCOIN_STATUS)->orderBy('created_at', 'desc');
          $grid->id('ID')->sortable();
          $grid->column('users.username', '用户');
          $grid->total('开始');
          $grid->column('amount', '数目')->display(function () {
            return $this->type == 1 ? '<i style="color:#3c8dbc;">+</i> '. $this->amount : '<i style="color:#dd4b39;">-</i> ' . $this->amount;
          });
          $grid->current('结束');
          $grid->created_at('添加时间');
          // $grid->updated_at();
          $grid->disableExport();
          $grid->disableRowSelector();
          $grid->disableCreateButton();
          $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('user_id', '用户')->select(User::all()->pluck('username', 'id'));
            $filter->between('created_at', '添加时间')->datetime();
          });
          $grid->actions(function ($actions) {
            $actions->disableDelete();
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

            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
        });
    }
}
