<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', [//默认
  'namespace' => 'App\Api\Controllers',
  'middleware' => [
      'bindings',
      \Illuminate\Session\Middleware\StartSession::class,
  ]
], function ($api) {
  // $api->group([
  //   'middleware' => 'api.throttle',
  //    'limit' => config('api.rate_limits.sign.limit'),
        // 'expires' => config('api.rate_limits.sign.expires'),
  // ]);

  $api->get('shop/banners', 'IndexController@index');
  $api->get('shop/categories', 'IndexController@categoryList');
  $api->get('shop/news', 'IndexController@newsList');
  $api->get('shop/news/{news}', 'IndexController@newsDetail');
  $api->get('shop/products/{typeText}/{sort}/{limit?}', 'IndexController@productListByType');
  $api->get('shop/category/{category}/{sort}/{limit?}', 'IndexController@productListByCate');
  $api->get('shop/search/{title}/{sort}/{limit?}', 'IndexController@productListByName');

  // $api->get('products/{product}', 'ProductsController@show');
  $api->get('evaluates/{product}', 'ProductsController@evaluate');//商品评价列表
  $api->any('/payments/wechat/notify', 'WechatController@update');//支付回调
  // 小程序登录
  $api->post('weapp/authorizations', 'AuthorizationsController@weappStore')
      ->name('api.weapp.authorizations.store');
  $api->put('/weapp/refresh', 'AuthorizationsController@refreshToken');
  $api->get('/entrance/{coupon}', 'UserCouponsController@entrance');
  // 需要 token 验证的接口
  $api->group(['middleware' => 'api.auth'], function($api) {
    # 注册、退出
    $api->put('/weapp/register', 'AuthorizationsController@register');
    $api->get('products/{product}', 'ProductsController@show');
    $api->delete('/weapp/logout', 'AuthorizationsController@destroy');
    $api->get('/user', 'AuthorizationsController@get_user_info');


    # 优惠券
    $api->resource('/coupons', 'UserCouponsController');
    $api->post('/receive/{coupon}', 'UserCouponsController@receive');
    # 个人中心
    $api->get('/client', 'UsersController@client');//我的客户
    $api->get('/earn', 'UsersController@earn');//我的收入
    $api->get('/agent', 'UsersController@agent');//我的代理
    $api->get('/history', 'UsersController@history');//我的粉丝
    $api->get('/victory', 'UsersController@victory');//我的业绩
    $api->get('/user/coupons', 'UsersController@coupons');//我的优惠券

    # 收货地址
    $api->resource('addresses', 'AddressesController');

    # 购物车
    $api->get('carts', 'ShoppingCartsController@index');
    $api->post('carts/{item}', 'ShoppingCartsController@store');//
    $api->put('carts/{cart}', 'ShoppingCartsController@update');
    $api->delete('carts', 'ShoppingCartsController@destroy');

    #订单
    $api->resource('orders', 'OrdersController');//订单基础信息
    $api->post('uploads', 'OrdersController@uploads');//上传图片--单一
    $api->post('orders/evaluate/{order}', 'OrdersController@evaluate');//订单评价
    $api->post('orders/{order}/refund', 'OrdersController@refund');//订单申请退款
    $api->get('share/{order}', 'OrdersController@share');//分享赚
    $api->get('orders/logistics/{order}', 'OrdersController@logistics');//查看物流
    #支付
    $api->post('wechat/sure', 'WechatController@orderSure');//确认订单
    $api->post('wechat', 'WechatController@store');//提交订单


    #代理

    $api->resource('agents', 'AgentsController');
    $api->post('agents/getcode', 'AgentsController@get_qrcode');//代理二维码
    $api->post('withward', 'AgentsController@withward');//提现申请
  });

  //定时任务
  $api->get('tasks/task', 'TaskController@task');
  $api->get('tasks/active', 'TaskController@active');

  // $api->get('carts/index', 'CartsController@index');
});
