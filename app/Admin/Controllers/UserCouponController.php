<?php

namespace App\Admin\Controllers;

use App\Models\UserCoupon;
use App\Models\Coupon;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class UserCouponController extends Controller
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

            $content->header('领券记录');
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

            $content->header('领券记录');
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

            $content->header('领券记录');
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
        return Admin::grid(UserCoupon::class, function (Grid $grid) {
            $grid->model()->orderBy('created_at', 'desc');
            $grid->id('ID')->sortable();
            $grid->column('users.username', '领券者');
            $grid->column('领券详情')->display(function () {
              return $this->coupons->par_value.' '.$this->coupons->more_value. ' '.$this->coupons->start_at. ' '.$this->coupons->end_at;
            });
            $grid->status('状态')->display(function ($status) {

                switch($status){
                  case '0':
                    $info = '<span class="label label-default">未使用</span>';
                    break;
                  case '1':
                    $info = '<span class="label label-success">已使用</span>';
                    break;
                  case '2':
                    $info = '<span class="label label-danger">已过期</span>';
                    break;
                  default:
                    $info = '<span class="label label-default">未使用</span>';
                    break;
                }
                return $info;
            })->sortable();
            $grid->created_at('领券时间');
            // $grid->updated_at();
            $grid->disableExport();
            $grid->disableRowSelector();
            $grid->disableCreateButton();
            // $grid->disableActions();
            // $grid->actions(function ($actions) {
            //   $actions->dis
            // });
            $grid->filter(function ($filter) {
              $filter->disableIdFilter();
              $filter->equal('coupon_id', '优惠券')->select(Coupon::all()->pluck('title', 'id'));
              // $filter->where(function ($query) {
              //     $query->where()->
              // }, '优惠券');
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
        return Admin::form(UserCoupon::class, function (Form $form) {

            $form->display('id', 'ID');

            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
        });
    }
}
