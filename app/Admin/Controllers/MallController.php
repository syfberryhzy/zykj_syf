<?php

namespace App\Admin\Controllers;

use App\Models\Product;
use App\Models\ProductItem;
use App\Models\Category;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\Request;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Encore\Admin\Widgets\Table;
use App\Admin\Extensions\Tools\ProductType;

class MallController extends Controller
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

            $content->header('兑换专区');
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

            $content->header('兑换专区');
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

            $content->header('兑换专区');
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
            $model = $grid->model()->where('type', 4)->orderBy('created_at', 'desc');
            $grid->id('ID')->sortable();
            $grid->column('title', '名称')->label('info');
            $grid->column('category.title', '属性类名')->label('success');
            // $grid->type('版块类型')->display(function ($type){
            //     switch ($type) {
            //       case 0:
            //         $info = "<span class='label label-warning'>普通商品</span>";
            //         break;
            //       case 1:
            //         $info = "<span class='label label-primary'>今日推荐</span>";
            //         break;
            //       case 2:
            //         $info = "<span class='label label-success'>独家定制</span>";
            //         break;
            //       case 3:
            //         $info = "<span class='label label-danger'>限时秒杀</span>";
            //         break;
            //       case 4:
            //         $info = "<span class='label label-info'>M币专区</span>";
            //         break;
            //       default:
            //         $info = "<span class='label label-warning'>普通商品</span>";
            //         break;
            //     }
            //     return $info;
            // });
            $grid->images('图片')->display(function ($images) {
              return $images[0];
            })->image('', 100, 100);
            $grid->pre_price('价格（单位:M币）')->sortable();

            $grid->column('库存')->display(function () {
              return collect($this->items)->sum('quantity');
            })->sortable();
            $grid->sale_num('销量')->sortable();
            $grid->column('规格参数')->expand(function () {
                $items = $this->items->toArray();
                $headers = ['ID', '商品规格', '商品库存'];
                $title = ['id', 'norm', 'quantity'];
                $datas = array_map(function ($item) use ($title) {
                    $status = $item['status'];
                    $item['quantity'] = $item['quantity'];
                    // $item['status'] = $status == 1 ? '显示' : '隐藏';
                    // $item['operate'] = $status == 1 ? '隐藏' : '显示';
                    return array_only($item, $title);
                }, $items);
                return new Table($headers, $datas);
            }, '查看详情');
            $states = [
              'on'  => ['value' => 1, 'text' => '上架', 'color' => 'primary'],
              'off' => ['value' => 0, 'text' => '下架', 'color' => 'danger'],
            ];
            $grid->status('上架？')->switch($states);
            // $grid->created_at();
            // $grid->updated_at();
            // $grid->tools(function ($tools) {
            //     $tools->append(new ProductType());
            // });
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
                // 0 => '普通商品',
                // 1 => '今日推荐',
                // 2 => '独家定制',
                // 3 => '限时秒杀',
                4 => 'M币专区'
              ];
              $form->text('title', '商品名称');
              $form->select('category_id', '属性类名')->options(Category::buildSelectOptions($nodes = [], $parentId = 0, $prefix = ''));
              $form->select('type', '版块类型')->options($types);
              $form->currency('pre_price', '兑换单价（M币）')->symbol('M')->help('单位:M币');
              // $form->currency('share_price', '分享赚')->symbol('￥');
              $form->number('sale_num', '商品销量')->default(0);

            })->tab('商品详情', function ($form) {
               $form->multipleImage('images', '商品图片')->removable();
               $form->textarea('description', '商品简述')->rows(3);
               $form->editor('contact', '商品详情');
               $states = [
                   'on'  => ['value' => 1, 'text' => '上架', 'color' => 'primary'],
                   'off' => ['value' => 0, 'text' => '下架', 'color' => 'danger'],
               ];
               $form->switch('status', '上架？')->states($states)->default(1);
               $form->display('created_at', '创建时间');
               // $form->display('updated_at', '编辑时间');
             })->tab('规格参数', function ($form) {
               $form->hasMany('items', '', function(Form\NestedForm $form) {
                   $form->text('norm', '规格')->setWidth(2, 2);
                   // $form->currency('unit_price', '单价')->symbol('￥');
                   $form->number('quantity', '库存')->rules('regex:/^[0-9]*$/', [
                       'regex' => '库存必须为正整数',
                   ])->default(0);
                   // $states = [
                   //     'on'  => ['value' => 1, 'text' => '上架', 'color' => 'primary'],
                   //     'off' => ['value' => 0, 'text' => '下架', 'color' => 'danger'],
                   // ];
                   // $form->switch('status', '状态')->states($states)->default(1);
                   $form->divide();
               });
             });
             // $form->saving(function (Form $form) {
             //
             //
             //  });
             $form->saved(function (Form $form) {
                $price = $form->model()->pre_price;
                ProductItem::where('product_id', $form->model()->id)->update(['unit_price' => $price]);
             });
        });
    }





}
