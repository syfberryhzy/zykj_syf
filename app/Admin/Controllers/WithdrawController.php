<?php

namespace App\Admin\Controllers;

use App\Models\Exchange;
use App\Models\User;
use App\Models\MchPay;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Encore\Admin\Controllers\ModelForm;
use App\Admin\Extensions\Tools\ButtonTool;

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
            $content->breadcrumb(
                 ['text' => '首页', 'url' => '/admin'],
                 ['text' => '用户管理', 'url' => '/admin/users'],
                 ['text' => '编辑用户']
             );
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
            // $grid->total('开始');
            $grid->column('amount', '提现金额');
            // $grid->column('amount', '提现金额')->display(function () {
            //   return $this->type == 1 ? '<i style="color:#3c8dbc;">+</i> '. $this->amount : '<i style="color:#dd4b39;">-</i> ' . $this->amount;
            // });
            // $grid->current('余额');
            $grid->column('申请信息')->display(function () {
              $model = $this->model;
              if($model == 'withward') {
                return '企业转账';
              } else {
                $model = json_decode($model, true);
                return '支付宝手动转账<br/>'.'支付宝用户名:'.$model['check_name'].'<br/>支付宝账户:'. $model['account'];
              }
            });
            $grid->column('提现状态')->display(function () {
              return $this->status == Exchange::WITHDRAW_STATUS_APPLY ? '<span class="label label-default">待打款</span>' : '<span class="label label-success">已打款</span>';
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
            //$grid->disableActions();
            $grid->actions(function ($actions) {
                $actions->disableDelete();
                $actions->disableEdit();
                if ($actions->row->status == Exchange::WITHDRAW_STATUS_APPLY) {
                  // 添加操作
                  $actions->append(new ButtonTool($actions->getKey(), 1, '打款', '/admin/member/withdraws'));
                }

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

    //企业转账
    public function operate(Exchange $exchange, Request $request)
    {

      if($exchange->model == 'withward') {
        //企业转账
        $out_trade_no =  'mike'. date('YmdHis') . rand(1000, 9999);
        $payment = \EasyWeChat::payment();
        $result = $payment->transfer->toBalance([
          'partner_trade_no' => $out_trade_no, // 商户订单号，需保持唯一性(只能是字母或者数字，不能包含有符号)
          'openid' => $exchange->users->weixin_openid,
          'check_name' => 'NO_CHECK', // NO_CHECK：不校验真实姓名, FORCE_CHECK：强校验真实姓名
          //'re_user_name' => '王小帅', // 如果 check_name 设置为FORCE_CHECK，则必填用户真实姓名
          'amount' => $exchange->amount * 100, // 企业付款金额，单位为分
          'desc' => '收益提现', // 企业付款操作说明信息。必填
        ]);

        if($result['result_code'] == 'SUCCESS') {
          MchPay::create([
            'user_id' => $exchange->user_id,
            'partner_trade_no' => $out_trade_no,
            'desc' => '收益提现',
            'amount' => $exchange->amount,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
          ]);
        }
        return response()->json(['message' => $result['err_code_des'], 'status' => 0], 201);
      }
      $exchange->status = Exchange::WITHDRAW_STATUS_AGREE;
      if($exchange->save()) {
          return response()->json(['message' => '打款成功！', 'status' => 1], 201);
      }
      return response()->json(['message' => '打款失败!', 'status' => 0], 201);
    }

}
