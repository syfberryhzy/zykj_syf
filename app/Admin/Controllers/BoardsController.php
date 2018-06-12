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

class BoardsController extends Controller
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

            $content->header('业绩排行榜');
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

            $content->header('业绩排行榜');
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

            $content->header('业绩排行榜');
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
          $grid->model()->whereIn('status', Exchange::VICTORY_STATUS)->orderBy('created_at', 'desc');
          $grid->id('ID')->sortable();
          $grid->column('users.username', '用户');
          $grid->total('业绩');
          // $grid->column('amount', '数目')->display(function () {
          //   return $this->type == 1 ? '<i style="color:#3c8dbc;">+</i> '. $this->amount : '<i style="color:#dd4b39;">-</i> ' . $this->amount;
          // });

          $grid->created_at('添加时间');
          $grid->status('统计类型')->display(function () {
              return $this->status == Exchange::VICTORY_DATE ? '日统计' : '月统计';
          //   return $this->type == 1 ? '<i style="color:#3c8dbc;">+</i> '. $this->amount : '<i style="color:#dd4b39;">-</i> ' . $this->amount;
          });
          // $grid->updated_at();
          $grid->disableExport();
          $grid->disableRowSelector();
          $grid->disableCreateButton();
          $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('user_id', '用户')->select(User::all()->pluck('username', 'id'));
            $filter->between('created_at', '添加时间')->datetime();
          });
          $grid->disableActions();
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
