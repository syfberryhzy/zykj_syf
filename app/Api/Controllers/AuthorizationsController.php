<?php

namespace App\Api\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Api\Requests\WeappAuthorizationRequest;

class AuthorizationsController extends Controller
{
    public function weappStore(WeappAuthorizationRequest $request)
    {
        $code = $request->code;

        //根据 code 获取微信 openid 和 session_key
        $miniProgram = \EasyWeChat::miniProgram();
        $data = $miniProgram->auth->session($code);

        // 如果结果错误，说明 code 已过期或不正确，返回 401 错误
        if (isset($data['errcode'])) {
            return $this->response->errorUnauthorized('code 不正确');
        }

        # 找到 openid 对应的用户
        $user = User::firstOrCreate([
          'weixin_openid' => $data['openid']
        ]);

        $attributes['weixin_session_key'] = $data['session_key'];
        # 更新用户数据
        $user->update($attributes);

        # 为对应用户创建 JWT
        $token = \Auth::guard('api')->fromUser($user);
        return $this->respondWithToken($token)->setStatusCode(201);
    }


    protected function respondWithToken($token)
    {
        return $this->response->array([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => \Auth::guard('api')->factory()->getTTL() * 60
        ]);
    }

    /*
    * 授权,注册用户
    */
    public function register(Request $request)
    {
        $user = $this->user();
        $user->update([
          'username' => $request->name,
          'nickname' => $request->name,
          'avatar' => $request->avatarUrl,
          'gender' => $request->gender,
        ]);

      return $this->response->array($user)->setStatusCode(201);
    }

    /**
    * 刷新token
    */
    public function refreshToken()
    {
        $token = \Auth::guard('api')->refresh();
        return $this->respondWithToken($token)->setStatusCode(201);
    }

    /**
    * 登出
    */
    public function destroy()
    {
        \Auth::guard('api')->logout();
        return $this->response->noContent();
    }
}
