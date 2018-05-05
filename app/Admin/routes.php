<?php

use Illuminate\Routing\Router;

Admin::registerAuthRoutes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index');

    $router->resource('site/banners', BannerController::class);
    $router->resource('site/news', NewsController::class);
    $router->resource('site/coupon', CouponController::class);

    $router->resource('mall/category', CategoryController::class);
    $router->resource('mall/products', ProductController::class);

    $router->resource('base/users', UserController::class);
    $router->resource('manage/orders', OrderController::class);
    $router->resource('manage/evaluates', EvaluateController::class);
    // $router->resource('mall/products', ProductController::class);
    // $router->resource('mall/products', ProductController::class);

});
