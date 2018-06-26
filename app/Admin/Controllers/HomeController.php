<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\User;

class HomeController extends Controller
{
    public function index()
    {
        return Admin::content(function (Content $content) {

            $content->header('首页');
            $content->description('面板...');

            $start = date('Y-m-d 00:00:00');
            $end = date('Y-m-d 23:59:59');
            $where[] = ['created_at', '>=', $start];
            $where[] = ['created_at', '<=', $end];
            $data[] = '今日订单数据: '. Order::where($where)->count();
            $data[] = '总用户数量: '. User::count();
            $data[] = '今日新增用户: '.  User::where($where)->count();

            $content->row(Dashboard::title($data));
            $content->row(function (Row $row) {
              $start_day = Carbon::now()->startOfDay();
              $end_day = Carbon::now()->endOfDay());

              $start_week = Carbon::now()->startOfWeek();
              $end_week = Carbon::now()->endOfWeek();

              $start_month = Carbon::now()->startOfMonth();
              $end_month = Carbon::now()->endOfMonth();
                $row->column(4, function (Column $column) {
                    $column->append(Dashboard::environment());

                });

                $row->column(4, function (Column $column) {
                    $column->append(Dashboard::extensions());
                });

                $row->column(4, function (Column $column) {
                    $column->append(Dashboard::dependencies());
                });
            });
        });
    }
}
