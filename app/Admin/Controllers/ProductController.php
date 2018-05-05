<?php

namespace App\Admin\Controllers;

use App\Models\Product;
use App\Models\Category;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class ProductController extends Controller
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

            $content->header('商品管理');
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

            $content->header('商品管理');
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

            $content->header('商品管理');
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
        return Admin::grid(Product::class, function (Grid $grid) {
            $grid->model()->orderBy('created_at', 'desc');
            $grid->id('ID')->sortable();
            $grid->column('title', '名称')->label('info');
            $grid->column('category.title', '属性类名')->label('success');
            $grid->type('版块类型')->display(function ($type){
                switch ($type) {
                  case 0:
                    $info = "<span class='label label-warning'>普通商品</span>";
                    break;
                  case 1:
                    $info = "<span class='label label-warning'>今日推荐</span>";
                    break;
                  case 2:
                    $info = "<span class='label label-success'>独家定制</span>";
                    break;
                  case 3:
                    $info = "<span class='label label-danger'>限时秒杀</span>";
                    break;
                  case 4:
                    $info = "<span class='label label-info'>M币专区</span>";
                    break;
                  default:
                    $info = "<span class='label'>普通商品</span>";
                    break;
                }
                return $info;
            });
            $grid->images('图片')->image('', 100, 100);
            $grid->diff_price('会员差价')->sortable();
            $grid->share_price('分享赚')->sortable();
            $grid->sale_num('销量')->sortable();
            // $grid->created_at();
            // $grid->updated_at();
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form(Product::class, function (Form $form) {
            $form->tab('基础信息', function ($form) {
              $types = [
                0 => '普通商品',
                1 => '今日推荐',
                2 => '独家定制',
                3 => '限时秒杀',
                4 => 'M币专区'
              ];
              $form->text('title', '商品名称');
              $form->select('category_id', '属性类名')->options(Category::buildSelectOptions($nodes = [], $parentId = 0, $prefix = ''));
              $form->select('type', '版块类型')->options($types);
              $form->currency('diff_price', '会员差价')->symbol('￥');
              $form->currency('share_price', '分享赚')->symbol('￥');
              $form->number('sale_num', '商品销量')->default(0);
              // $states = [
              //   'on'  => ['value' => 1, 'text' => '上线', 'color' => 'primary'],
              //   'off' => ['value' => 0, 'text' => '下架', 'color' => 'default'],
              // ];
              // $form->switch('status', '状态')->states($states)->default(1);

            })->tab('商品详情', function ($form) {
               $form->multipleImage('images', '商品图片')->removable();
               $form->textarea('description', '商品简述')->rows(3);
               $form->editor('content', '商品详情');
               $form->display('created_at', '创建时间');
               $form->display('updated_at', '编辑时间');
             })->tab('规格参数', function ($form) {

               // $form->html('', $label = '');
               $form->hasMany('items', '', function(Form\NestedForm $form) {
                   $form->text('norm', '规格')->setWidth(2, 2);
                   // 12等分，6就是50% enen
                   $form->currency('unit_price', '单价')->symbol('￥');
                   $form->number('quantity', '库存')->rules('regex:/^[0-9]*$/', [
                       'regex' => '库存必须为正整数',
                   ])->default(0);
                   $form->divide();
               });
             });
        });
    }





}
