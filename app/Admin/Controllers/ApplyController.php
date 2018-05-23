<?php

namespace App\Admin\Controllers;

use App\Models\Apply;
use App\Models\User;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use App\Admin\Extensions\Tools\ApplyTool;
use Illuminate\Http\Request;
use App\Repositories\AgentRepositoryEloquent;

class ApplyController extends Controller
{
    use ModelForm;

    public $agent;

    public function __construct(AgentRepositoryEloquent $reponsitory)
    {
        $this->agent = $reponsitory;
    }

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index()
    {
        return Admin::content(function (Content $content) {

            $content->header('会员申请');
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

            $content->header('会员申请');
            $content->description('审核');

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

            $content->header('会员申请');
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
        return Admin::grid(Apply::class, function (Grid $grid) {
            $grid->id('ID')->sortable();
            $grid->username('申请人');
            $grid->column('parents.username', '推荐人');
            $grid->phone('联系电话');
            $grid->wechat('微信号');
            $grid->status('审核状态')->display(function ($status) {
                switch($status){
                  case '0':
                    $info = '<span class="label label-default">待审核</span>';
                    break;
                  case '1':
                    $info = '<span class="label label-success">已通过</span>';
                    break;
                  case '2':
                    $info = '<span class="label label-danger">已拒绝</span>';
                    break;
                  default:
                    $info = '<span class="label label-default">待审核</span>';
                    break;
                }
                return $info;
            })->sortable();


            $grid->created_at('申请时间');
            // $grid->updated_at('审核时间');
            $grid->disableCreateButton();
            $grid->disableExport();
            $grid->disableRowSelector();
            $grid->actions(function ($actions) {
              $actions->disableDelete();
              $actions->disableEdit();

              if ($actions->row->status == 0) {
                // 添加操作
                $actions->append(new ApplyTool($actions->getKey(), 1));
                $actions->append(new ApplyTool($actions->getKey(), 2));
              }
            });
            $grid->filter(function ($filter) {
              $filter->disableIdFilter();
              $filter->like('username', '申请人');
              $filter->like('wechat', '微信号');
              $filter->like('phone', '联系方式');
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
        return Admin::form(Apply::class, function (Form $form) {

            $form->display('id', 'ID');
            $form->display('username', '申请人');
            $form->display('parents.username', '推荐人');
            $form->display('phone', '联系方式');
            $form->display('wechat', '微信号');
            $states = [
               'on'  => ['value' => 1, 'text' => '审核通过', 'color' => 'primary'],
               'off' => ['value' => 2, 'text' => '驳回申请', 'color' => 'danger'],
            ];
            $form->switch('status', '状态')->states($states)->default(1);
            $form->display('created_at', '申请时间');
            $form->display('updated_at', '审核时间');
        });
    }

    public function operate(Apply $apply, Request $request)
    {
        if ($request->action) {
          $apply->status = $request->action;

          if ($apply->save()) {
            # 修改申请用户信息
            $this->agent->updateUser($apply->user_id, $request->action, $apply);
            return response()->json(['message' => '审核成功！', 'status' => 1], 201);
          }
          return response()->json(['message' => '审核失败!', 'status' => 0], 201);
        }
        return response()->json(['message' => '错误操作!', 'status' => 0], 201);
    }
}
