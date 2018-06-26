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
use App\Admin\Extensions\Tools\Send;
use App\Admin\Extensions\Tools\Agree;
use Encore\Admin\Widgets\Table;
use App\Http\Requests\Admin\HandleRefundRequest;

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
            $where = [];
            $sql = $grid->model()->orderBy('created_at', 'desc');

            if (in_array(Request::get('status'), ['0', '1', '2', '3', '4', '6'])) {
                if(in_array(Request::get('status'), ['0', '1', '2', '3', '4'])) {
                  $where[] = ['status', Request::get('status')];
                  $sql = $sql->where($where);
                }
                if(Request::get('status') == 1) {
                  //已关闭 已取消和已退款单
                  $sql = $sql->orWhere('refund_status', Order::REFUND_STATUS_SUCCESS);
                }
                if(Request::get('status') == 2) {
                  //已付款--待发货
                  $sql = $sql->whereIn('refund_status', [0, 4]);
                }
                if(Request::get('status') == 4) {
                  //已完成 已收货和已评价
                  $sql = $sql->orWhere('status', 5);
                }
                if(Request::get('status') == 6) {
                  //退款中 已收货和已评价
                  $sql = $sql->whereIn('refund_status', ['1', '2']);
                }

            }
            $data = [
              'all' => Order::count(),
              '0' => Order::where('status', 0)->count(),
              '1' => Order::where('status', 1)->orWhere('refund_status', Order::REFUND_STATUS_SUCCESS)->count(),
              '2' => Order::where('status', 2)->whereIn('refund_status', ['0', '4'])->count(),
              '3' => Order::where('status', 3)->count(),
              '4' => Order::where('status', 4)->orWhere('status', 5)->count(),
              '6' => Order::whereIn('refund_status', ['1', '2'])->count(),
            ];

            $grid->id('ID')->sortable();
            $grid->column('users.username','收货信息')->display(function($user){
                return '下单人：'.$user
                .'<br/>收件人：'.$this->consignee
                .'<br/>电&nbsp;&nbsp;&nbsp;话：'.$this->phone
                .'<br/>地&nbsp;&nbsp;&nbsp;址：'.$this->address;
            });
            $grid->column('订单信息')->display(function(){
                $type = $this->type == 0 ? "<span class='label label-success'>微信购买</span>" : "<span class='label label-info'>M币兑换</span>";
                return '类型：'. $type
                .'<br/>总价：'.$this->tradetotal
                .'<br/>优惠：'.$this->preferentialtotal
                .'<br/>邮费：'.$this->customerfreightfee
                .'<br/>应付：'.$this->total
                .'<br/>已付：'.$this->paiedtotal;
            });
            $grid->status('订单状态')->display(function ($status){
				if(in_array($this->refund_status, [0, 4]) ) {
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
				} else {
					switch ($this->refund_status) {
					   case 1:
                         $info = "<span class='label label-primary'>申请退款</span>";
                         break;
                       case 2:
                         $info = "<span class='label label-success'>退款中</span>";
                         break;
                        case 3:
                          $info = "<span class='label label-danger'>退款成功</span>";
                          break;
					}
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
            $grid->disableExport();
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
                $tools->append(new OrderStatus($data));
            });
            $grid->actions(function ($actions) {
              $actions->disableDelete();
              $actions->disableEdit();
              $actions->append('<a href="/admin/manage/orders/'.$actions->getKey().'"><i class="fa fa-eye"></i></a>');
			  if ($actions->row->refund_status == 1) {
				  //申请退款
				  $actions->append(new Agree($actions->getKey(), 1, '同意退款', '/admin/manage/orders/'.$actions->getKey().'/refund'));
				  $actions->append(new Agree($actions->getKey(), 0, '拒绝退款', '/admin/manage/orders/'.$actions->getKey().'/refund'));
			  }
              if ($actions->row->status == 2 && !in_array($actions->row->refund_status, [1, 2, 3])) {
              //   // 添加操作
                $actions->append(new Send($actions->getKey(), 1, '发货', '/admin/manage/orders/send'));
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
        return Admin::form(Order::class, function (Form $form) {

            $form->display('id', 'ID');

            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
        });
    }

    public function show(Order $order)
    {
        return Admin::content(function (Content $content) use ($order) {
            $content->header('查看订单');
            $content->body(view('admin.orders.show', ['order' => $order]));
        });
    }

    public function handleRefund(Order $order, HandleRefundRequest $request)
    {
        // 判断订单状态是否正确
        if ($order->refund_status !== Order::REFUND_STATUS_APPLIED) {
            throw new InvalidRequestException('订单状态不正确');
			return response()->json(['message' => '订单状态不正确'])->setStatusCode(400);
        }
        // 是否同意退款

        if (Request::post('agree')) {
            // 同意退款的逻辑这里先留空
            $payment = \EasyWeChat::payment();

            $result = $payment->refund->byOutTradeNumber($order->out_trade_no, $order->out_refund_no, 1, 1, [
                'refund_desc' => $order->refund_reason,
            ]);
			if($result['result_code'] != 'SUCCESS') {
				return response()->json(['message' => $result['err_code_des']])->setStatusCode(422);
			}
            $order->update([
                'refund_status' => Order::REFUND_STATUS_PROCESSING,//退款中
            ]);
			return response()->json(['message' => '退款成功'])->setStatusCode(200);
        } else {
            // 将拒绝退款理由放到订单的 extra 字段中
            $extra = $order->extra ?: [];
            $extra['refund_disagree_reason'] = Request::post('reason');
            // 将订单的退款状态改为未退款
            $order->update([
                'refund_status' => Order::REFUND_STATUS_PENDING,
                'extra'         => $extra,
            ]);
			return response()->json(['message' => '拒绝退款操作成功'])->setStatusCode(200);
        }

		return response()->json(['message' => '操作错误'])->setStatusCode(400);
    }

    /**
    * 发货
    */
    public function send(Order $order, Request $request)
    {
        // 判断订单状态是否正确
        if ($order->status !== Order::ORDER_STATUS_PAYED || $order->refund_status !== Order::ORDER_STATUS_PAYED) {
            //throw new InvalidRequestException('订单状态不正确');
			return response()->json(['message' => '订单状态不正确'])->setStatusCode(400);
        }

		if(Request::post('freightbillno') && Request::post('express_company')) {
			// 将订单的状态改为待收货
            $order->update([
                'status' => Order::ORDER_STATUS_SEND,
                'express_company'         => Request::post('express_company'),
                'freightbillno'         => Request::post('freightbillno'),
            ]);
			return response()->json(['message' => '发货成功'])->setStatusCode(200);
		}
        return response()->json(['message' => '发货失败'])->setStatusCode(400);
    }
}
