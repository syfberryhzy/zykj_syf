<?php

namespace App\Admin\Controllers;

use App\Models\User;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class UserController extends Controller
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

            $content->header('用户管理');
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

            $content->header('用户管理');
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

            $content->header('用户管理');
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
        return Admin::grid(User::class, function (Grid $grid) {
            $grid->model()->orderBy('created_at', 'desc');
            $grid->id('ID')->sortable();
            $grid->name('用户名');
            $grid->avatar('头像')->image('', '100', '100');
            $grid->phone('联系方式');
            $grid->column('用户信息')->display(function(){
                return '总计消费:'. $this->spend_total.'</br>'
                  .'当月消费:'. $this->spend_current.'</br>'
                  .'当前积分:'. $this->integral_current.'</br>'
                  .'当前M币:'. $this->m_current;
            });
            $grid->status('用户身份')->display(function ($str) {
              return $str == 1 ? '<i class="fa fa-vimeo">会员</i>' : '<i class="fa fa-user-plus">游客</i>';
            });
            $grid->created_at('添加时间');
            $grid->updated_at('编辑时间');

            // $grid->disableCreateButton();
            $grid->disableExport();
            // $grid->disableActions();

            $grid->filter(function($filter) {
                $filter->disableIdFilter();
                $filter->like('name', '用户名');
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
        return Admin::form(User::class, function (Form $form) {

            $form->display('id', 'ID');
            $form->text('name', '用户名');
            $form->text('openid', '微信ID')->default(str_random(10));
            $form->image('avatar', '头像');
            $form->mobile('phone', '手机')->options(['mask' => '999 9999 9999']);
            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
        });
    }
}
