<?php

use Illuminate\Routing\Router;

Admin::registerAuthRoutes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index')->name('root');

    $router->resource('site/settings', SettingsController::class);
    $router->resource('site/banners', BannerController::class);
    $router->resource('site/news', NewsController::class);
    $router->resource('site/coupon', CouponController::class);
    $router->post('site/coupon/operate/{coupon}', 'CouponController@operate');
    $router->resource('client/user_coupons', UserCouponController::class);

    $router->resource('mall/category', CategoryController::class);
    $router->resource('mall/products', ProductController::class);
    $router->resource('mall/exchange', MallController::class);

    $router->resource('base/users', UserController::class);
    $router->resource('member/applies', ApplyController::class);
    $router->post('member/applies/operate/{apply}', 'ApplyController@operate');
    $router->resource('member/recommends', RecommendController::class);
    $router->resource('member/cashs', CashsController::class);
    $router->resource('member/mcoins', McoinController::class);
    $router->resource('member/award', AwardsController::class);
    $router->resource('member/withdraws', WithdrawController::class);

    //$router->resource('manage/orders', OrderController::class);
    $router->get('manage/orders', 'OrderController@index')->name('admin.orders.index');
    $router->get('manage/orders/{order}', 'OrderController@show')->name('admin.orders.show');
    $router->post('manage/orders/{order}/refund', 'OrderController@handleRefund')->name('admin.orders.handle_refund');
    $router->post('manage/orders/send/{order}', 'OrderController@send')->name('admin.orders.send');
    $router->resource('manage/refunds', RefundsController::class);
    $router->resource('manage/evaluates', EvaluateController::class);
    $router->resource('manage/recommend', RecommendController::class);

    $router->resource('accumulative/boards', BoardsController::class);
    // $router->resource('mall/products', ProductController::class);

});
