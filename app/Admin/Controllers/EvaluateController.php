<?php

namespace App\Admin\Controllers;

use App\Models\Evaluate;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class EvaluateController extends Controller
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

            $content->header('评论管理');
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

            $content->header('评论管理');
            $content->description('编辑');

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

            $content->header('评论管理');
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
        return Admin::grid(Evaluate::class, function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');
            $grid->id('ID')->sortable();
            $grid->column('users.username', '评论者');
            $grid->column('商品信息')->display(function () {
              return '名 称 ：'. $this->product->title.'</br>'
                    .'规 格 ：'. $this->orderItems->norm.'</br>';
            });
            $grid->content('评价内容')->display(function () {
      				return "<table class='table' style='width:300px;'><tr><td>". $this->content ."</td></tr></table>";
      			});
            $grid->images('评论图片')->image(env('APP_URL_UPLOADS').'/evaluate/', 100, 100);
            $states = [
              'on'  => ['value' => 1, 'text' => '显示', 'color' => 'primary'],
              'off' => ['value' => 0, 'text' => '隐藏', 'color' => 'danger'],
            ];
            $grid->status('开启？')->switch($states);
            $grid->created_at('评论时间');
            //$grid->updated_at('编辑时间');
      			$grid->disableCreateButton();
      			$grid->disableExport();
      			$grid->actions(function ($actions) {
      				$actions->disableEdit();
      			});
      			$grid->filter(function ($filter) {
                $filter->disableIdFilter();

                $filter->where(function ($query) {
                    $query->whereHas('users', function ($query) {
                        $query->where('username', 'like', "%{$this->input}%");
                    });
                }, '评论者');
        				$filter->where(function ($query) {
                    $query->whereHas('product', function ($query) {
                        $query->where('title', 'like', "%{$this->input}%");
                    });
                }, '商品名称');

                $filter->between('created_at', '评论时间')->datetime();
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
        return Admin::form(Evaluate::class, function (Form $form) {

            $form->display('id', 'ID');
            $form->display('content', '评价内容');
            //$form->multipleImage('images', '上传图片')->move(env('APP_URL_UPLOADS').'/evaluate/');
            $states = [
                'on'  => ['value' => 1, 'text' => '显示', 'color' => 'primary'],
                'off' => ['value' => 0, 'text' => '隐藏', 'color' => 'danger'],
            ];
            $form->switch('status', '上架？')->states($states)->default(1);
            $form->display('created_at', '评价时间');
            $form->display('updated_at', '修改时间');
			$grid->disableExport();
        $grid->disableRowSelector();
        $grid->disableCreateButton();
        $grid->filter(function ($filter) {
          $filter->disableIdFilter();
          $filter->equal('user_id', '评论者')->select(User::all()->pluck('username', 'id'));
          $filter->between('created_at', '添加时间')->datetime();
        });
        $grid->disableActions();
        });
    }
}
