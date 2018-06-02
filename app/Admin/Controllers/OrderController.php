<?php

namespace App\Admin\Controllers;

use App\Models\Order;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Illuminate\Support\Facades\Request;
use App\Admin\Extensions\Tools\OrderStatus;
use Encore\Admin\Widgets\Table;

class OrderController extends Controller
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

            $content->header('订单管理');
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

            $content->header('订单管理');
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

            $content->header('订单管理');
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
        return Admin::grid(Order::class, function (Grid $grid) {

            if (in_array(Request::get('status'), ['0', '1', '2', '3', '4', '5'])) {
                $grid->model()->where('status', Request::get('status'))->orderBy('created_at', 'desc');
            } else {
                $grid->model()->orderBy('created_at', 'desc');
            }

            $grid->id('ID')->sortable();
            $grid->column('users.username','收货信息')->display(function($user){
                return '下单人：'.$user
                .'<br/>收件人：'.$this->consignee
                .'<br/>电&nbsp;&nbsp;&nbsp;话：'.$this->phone
                .'<br/>地&nbsp;&nbsp;&nbsp;址：'.$this->address;
            });
            $grid->column('订单信息')->display(function(){
                return '总价：'.$this->tradetotal
                .'<br/>优惠：'.$this->preferentialtotal
                .'<br/>邮费：'.$this->customerfreightfee
                .'<br/>应付：'.$this->total
                .'<br/>已付：'.$this->paiedtotal;
            });
            $grid->status('订单状态')->display(function ($status){
                switch ($status) {
                  case 0:
                    $info = "<span class='label label-warning'>待支付</span>";
                    break;
                  case 1:
                    $info = "<span class='label label-primary'>已取消</span>";
                    break;
                  case 2:
                    $info = "<span class='label label-success'>待发货</span>";
                    break;
                  case 3:
                    $info = "<span class='label label-danger'>待收货</span>";
                    break;
                  case 4:
                    $info = "<span class='label label-info'>已收货</span>";
                    break;
                  case 5:
                    $info = "<span class='label label-info'>已完成</span>";
                    break;
                  default:
                    $info = "<span class='label label-warning'>待支付</span>";
                    break;
                }
                return $info;
            });
            $grid->column('订单详情')->expand(function () {
                $items = $this->items->toArray();
                $headers = ['ID', '商品ID', '商品名称', '规格', '数量', '商品单价','总计'];
                $title = ['id', 'product_id', 'title', 'norm', 'num',  'pre_price', 'total_price'];
                $datas = array_map(function ($item) use ($title) {
                    return array_only($item, $title);
                }, $items);
                return new Table($headers, $datas);
            }, '查看详情')->badge('red');
            $grid->created_at('创建时间');
            // $grid->updated_at('编辑时间');
            $grid->disableCreation();
            $grid->filter(function ($filter) {
                $filter->disableIdFilter();

                $filter->where(function ($query) {
                    $query->whereHas('users', function ($query) {
                        $query->where('username', 'like', "%{$this->input}%");
                    });
                }, '下单人');
                $filter->where(function ($query) {
                    $query->where('consignee', 'like', "%{$this->input}%")
                        ->orWhere('phone', 'like', "%{$this->input}%")
                        ->orWhere('address', 'like', "%{$this->input}%");
                }, '收件人或电话或地址');
                $filter->between('created_at', '下单时间')->datetime();
            });
            $grid->tools(function ($tools) {
                $tools->append(new OrderStatus());
            });
//             <button onclick="clickA()">播放/暂停</button>
// <audio id="audio" src="raw/1.mp3">你的浏览器不支持</audio>
// <script>
//     var a=document.getElementById("audio");
//     function clickA(){
//         if(a.paused){
//             a.play();
//         }else{
//             a.pause();
//         }
//     }
// </script>
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form(Order::class, function (Form $form) {

            $form->display('id', 'ID');

            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
        });
    }
}
