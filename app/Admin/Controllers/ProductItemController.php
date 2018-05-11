<?php

namespace App\Admin\Controllers;

use App\Models\ProductItem;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class ProductItemController extends Controller
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

            $content->header('商品规格参数');
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

            $content->header('商品规格参数');
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

            $content->header('商品规格参数');
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
        return Admin::grid(ProductItem::class, function (Grid $grid) {

            $grid->id('ID')->sortable();
            $grid->norm('商品规格');
            $grid->unit_price('商品单价');
            $grid->quantity('商品库存');
            $states = [
                'on'  => ['value' => 1, 'text' => '上线', 'color' => 'primary'],
                'off' => ['value' => 0, 'text' => '下架', 'color' => 'default'],
            ];
              // $form->switch('status', '状态')->states($states)->default(1);
            $grid->status('状态')->switch($states);
            $grid->created_at('创建时间');
            $grid->updated_at('编辑时间');
            
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form(ProductItem::class, function (Form $form) {

            $form->display('id', 'ID');

            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
        });
    }
}
