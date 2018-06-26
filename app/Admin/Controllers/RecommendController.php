<?php

namespace App\Admin\Controllers;

use App\Models\Recommend;
use App\Models\User;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class RecommendController extends Controller
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

            $content->header('代理结构');
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

            $content->header('代理结构');
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

            $content->header('代理结构');
            $content->description('添加');

            $content->body($this->form());
        });
    }
    public function tree($ids)
    {
        $result = [];
        if ($ids = json_decode($ids)) {
          $data = Recommend::whereIn('user_id', $ids)->get();
          if($data) {
            $result = collect($data)->map(function ($item, $key) {
              return [
                'id' => $item->id,
                'user_id' => $item->user_id,
                'user_avatar' => $item->user->avatar,
                'user_name' => $item->user->username,
                'recommend' => $item->recommend
              ];
            });
          }
        }
        return $result;
    }
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Admin::grid(Recommend::class, function (Grid $grid) {

            $grid->id('ID')->sortable();
            $grid->column('user.username', '代理');
            $grid->column('团队')->display(function () {
              $result = [];
              $ids = $this->member;
              if ($ids = json_decode($ids)) {
                 $data = Recommend::whereIn('user_id', $ids)->get();
                 if($data) {
                   $result = collect($data)->map(function ($item, $key) {
                     return [
                     'id' => $item->id,
                     'user_id' => $item->user_id,
                     'user_avatar' => $item->user->avatar,
                     'user_name' => $item->user->username,
                     'recommend' => $item->recommend
                     ];
                   });
                 }
              }
             $other = count($result) ? collect($result)->sum('recommend') : 0;
             $data = [
               'recommend' => $this->recommend,
               'other' => $other,
               'sum' => $this->recommend + $other,
               'team' => $result
             ];
             $html = '代理总人数: '. $data['sum'] .' <br/>';
             foreach($data['team'] as $key => $item) {
                $html .= '--下级代理： <img src="'.$item['user_avatar'].'" style="width:30px;height:30px;"> '. $item['user_name'] . '&nbsp;&nbsp;代理人数: '. $item['recommend'].'<br/>';
             }
             return $html;

            });
            $grid->created_at('添加时间');
            // $grid->updated_at();
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

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form(Recommend::class, function (Form $form) {

            $form->display('id', 'ID');

            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
        });
    }
}
