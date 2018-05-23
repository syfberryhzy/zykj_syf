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

  $api->get('products/{product}', 'ProductsController@show');
  $api->get('evaluates/{product}', 'ProductsController@evaluate');

  // 小程序登录
  $api->post('weapp/authorizations', 'AuthorizationsController@weappStore')
      ->name('api.weapp.authorizations.store');
  $api->put('/weapp/refresh', 'AuthorizationsController@refreshToken');
  $api->get('/entrance/{coupon}', 'UserCouponsController@entrance');
  // 需要 token 验证的接口
  $api->group(['middleware' => 'api.auth'], function($api) {
    # 注册、退出
    $api->put('/weapp/register', 'AuthorizationsController@register');
    $api->delete('/weapp/logout', 'AuthorizationsController@destroy');
    $api->get('/user', 'AuthorizationsController@get_user_info');
    # 优惠券
    $api->resource('/coupons', 'UserCouponsController');
    $api->post('/receive/{coupon}', 'UserCouponsController@receive');
    # 个人中心
    $api->get('/user', 'AuthorizationsController@get_user_info');
    # 收货地址
    $api->resource('addresses', 'AddressesController');
    $api->post('addresses/{address}/default', 'AddressesController@setDefault');
    # 购物车
    $api->get('carts', 'CartsController@index');
    $api->post('carts/{item}', 'CartsController@store');
    $api->put('carts/{item}', 'CartsController@update');
    $api->delete('carts', 'CartsController@destroy');

    $api->resource('orders', 'OrdersController');
    $api->resource('wechat', 'WechatController');
    $api->post('wechat/sure', 'WechatController@orderSure');
    $api->post('wechat/pay/{order}', 'WechatController@pay_order');
    #代理
    $api->resource('agents', 'AgentsController');
    $api->resource('agents', 'AgentsController');
    $api->post('agents/getcode', 'AgentsController@get_qrcode');
    $api->post('withward', 'AgentsController@withward');
  });


  // $api->get('carts/index', 'CartsController@index');

  // $api->get('carts/index', 'CartsController@index');
});
