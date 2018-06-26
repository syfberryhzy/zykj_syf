<?php

namespace App\Admin\Controllers;

use App\Models\Coupon;
use App\Models\Banner;

use Carbon\Carbon;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Encore\Admin\Controllers\ModelForm;
use App\Admin\Extensions\Tools\ButtonTool;

class CouponController extends Controller
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

            $content->header('优惠券列表');
            $content->description('请提前设置活动，活动将于活动有效期开始时间自动生效');

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

            $content->header('优惠券');
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

            $content->header('优惠券');
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
        return Admin::grid(Coupon::class, function (Grid $grid) {
            $grid->model()->orderBy('created_at', 'desc');
            $grid->id('ID')->sortable();
            $grid->title('活动标题')->label('success');
            $grid->par_value('优惠券');
            $grid->more_value('满减');
            $grid->quantum('数量');
            $grid->receive('已领取');
            $grid->column('有效期')->display(function(){
              return Carbon::parse($this->start_at)->toDateString().' - '.Carbon::parse($this->end_at)->toDateString();
            });
            $grid->column('状态')->display(function(){
              $start = Carbon::parse($this->start_at);
              $end = Carbon::parse($this->end_at);
              $now = Carbon::now();
              if ($this->status == 1) {
                if($now->lt($start)) { return '<span style="color:#d2d6de;">敬请期待</span>'; }
                if ($now->between($start, $end)) {
                   if($this->active == 1) {
                     return '<span style="color:#00a65a;">火热进行中</span><img src="/fire.png" style="width:30px;height:30px;">';
                   }
                   return '<span style="color:#d2d6de;">还没开始就注定已经结束~_~</span>';
                }
                if ($now->gt($end)) { return '<span style="color:#d2d6de;">活动结束</span>'; }
              } else {
                return '<span style="color:#d2d6de;">不参与活动</span>';
              }

              // return Carbon::parse($this->start_at)->toDateString().' - '.Carbon::parse($this->end_at)->toDateString();
            });
            $states = [
              'on'  => ['value' => 1, 'text' => '是', 'color' => 'primary'],
              'off' => ['value' => 0, 'text' => '否', 'color' => 'danger'],
            ];
            //$grid->status('参与活动？')->switch($states);
            // $grid->created_at('创建时间');
            // $grid->updated_at('编辑时间');
            $grid->actions(function ($actions) {
               $actions->append('<a href="/admin/client/user_coupons?coupon_id='. $actions->getKey() .'" title="领券详情"><i class="fa fa-eye"></i></a>');
            });
            $grid->disableExport();
            $grid->actions(function ($actions){
              $actions->disableDelete();
              $start = Carbon::parse($actions->row->start_at);
              $end = Carbon::parse($actions->row->end_at);
              $now = Carbon::now();
              if ($actions->row->status == 0) {
                // 添加操作
                $actions->append(new ButtonTool($actions->getKey(), 1, '参与', '/admin/site/coupon'));
              }
              if ($actions->row->status == 1 && $actions->row->active == 0) {
                $actions->append(new ButtonTool($actions->getKey(), 0, '退出', '/admin/site/coupon'));
              }
            });
            $grid->filter(function ($filter){
                $filter->disableIdFilter();
                $filter->like('title', '活动标题');
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
        return Admin::form(Coupon::class, function (Form $form) {

            $form->display('id', 'ID');
            $form->text('title', '活动标题');
            $form->currency('par_value', '优惠券')->symbol('￥');
            $form->currency('more_value', '满减')->symbol('￥');
            $form->number('quantum', '数量')->rules('regex:/^[0-9]*$/', [
                'regex' => '数量必须为正整数',
            ])->default(0);
            $form->dateRange('start_at', 'end_at', '有效期');
            $states = [
              'on'  => ['value' => 1, 'text' => '是', 'color' => 'primary'],
              'off' => ['value' => 0, 'text' => '否', 'color' => 'danger'],
            ];
            $form->switch('status', '参与活动？')->states($states);
            // $form->display('created_at', '创建时间');
            // $form->display('updated_at', '编辑时间');
            //保存前回调
            $form->saving(function (Form $form) {
                #将有效期做修改
                $form->start_at = Carbon::parse($form->start_at)->startOfDay();
                $form->end_at = Carbon::parse($form->end_at)->endOfDay();
                // dd($form->start_at, $form->end_at);
            });
            $form->saved(function (Form $form) {
                #展示

                if ($form->model()->status == 1) {
                    $banner = Banner::find(1);
                    $banner->url = env('APP_URL_COUPONS').'/'. $form->model()->id;
                    $banner->status = 1;
                    $banner->save();
                    Coupon::where('id', '<>', $form->model()->id)->update(['status' => 0]);
                }
            });
        });
    }

    public function delete($id)
    {

    }
    public function operate(Coupon $coupon, Request $request)
    {
        $action = $request->action ? $request->action : 0;
        if ($action == 1) {
          #是否过期
          $end = Carbon::parse($coupon->end_at);
          $now = Carbon::now();
          if ($now->gt($end)) {
            return response()->json(['message' => '该活动截止日期已过期!', 'status' => 0], 201);
          }
        }
        $coupon->status = $request->action;

        if ($coupon->save()) {
          return response()->json(['message' => '操作成功！', 'status' => 1], 201);
        }
        return response()->json(['message' => '操作失败!', 'status' => 0], 201);
        //return response()->json(['message' => '错误操作!', 'status' => 0], 201);
    }
}
