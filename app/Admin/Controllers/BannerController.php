<?php

namespace App\Admin\Controllers;

use App\Models\Banner;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class BannerController extends Controller
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

            $content->header('首页轮播');
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

            $content->header('首页轮播');
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

            $content->header('首页轮播');
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
        return Admin::grid(Banner::class, function (Grid $grid) {
            $grid->model()->orderBy('created_at', 'desc');
            $grid->id('ID')->sortable();
            $grid->column('title', '标题')->label('success');
            $grid->image('图片')->image('', '150', '150');
            $grid->url('链接')->label('danger');
            $grid->sort('排序')->sortable();
            $states = [
                'on'  => ['value' => 1, 'text' => '显示', 'color' => 'primary'],
                'off' => ['value' => 0, 'text' => '隐藏', 'color' => 'danger'],
            ];
            $grid->status('状态')->switch($states);
            // $grid->created_at('创建时间');
            // $grid->updated_at('编辑时间');
            $grid->disableExport();
            $grid->disableRowSelector();

            $grid->filter(function($filter){
                $filter->disableIdFilter();
                $filter->like('title', '标题');
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
        return Admin::form(Banner::class, function (Form $form) {

            $form->display('id', 'ID');
            $form->text('title', '标题');
            $form->image('image', '图片');
            $form->url('url', '链接')->default('')->help('可不填');
            $form->number('sort', '排序')->default(0);
            $states = [
                'on'  => ['value' => 1, 'text' => '显示', 'color' => 'primary'],
                'off' => ['value' => 0, 'text' => '隐藏', 'color' => 'danger'],
            ];
            $form->switch('status', '显示？')->states($states)->default(1);
            $form->display('created_at', '创建时间');
            $form->display('updated_at', '编辑时间');
        });
    }
}
