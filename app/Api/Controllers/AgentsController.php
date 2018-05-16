<?php

namespace App\Api\Controllers;

use App\Models\User;
use App\Models\Apply;
use Illuminate\Http\Request;
use App\Api\Requests\AgentRequest;

class AgentsController extends Controller
{
    /**
     * 申请代理
     */
    public function store(AgentRequest $request)
    {
        $user = auth()->user();
        if ($user->status != 0) {
          return response()->json(['status' => 'fail', 'code' => '401', 'message' => '请勿重复申请']);
        }
        Apply::create([
          'user_id' => $user->id,
          'parent_id' => $user->parent_id ?? 0,
          'username' => $request->name,
          'phone' => $request->phone,
          'wechat' => $request->wechat,
          'areas' => $request->areas,
          'details' => $request->address
        ]);
        $user->update(['status' => 1]);
        return response()->json(['status' => 'success', 'code' => '201', 'message' => '申请提交成功']);
    }

    /**
    * 代理推广
    */
    public function index()
    {
        //
    }
    /**
    * 生成代理二维码
    */
    public function get_qrcode(Request $request) {
        header('content-type:image/png');
        //header('content-type:image/gif');格式自选，不同格式貌似加载速度略有不同，想加载更快可选择jpg
        //header('content-type:image/jpg');
        $uid = 6;
        $access_token = $request->token;
        $data = array();
        // $data['scene'] = $request->scene;
        $data['scene'] = '10086';
        $data['page'] = "pages/index/index";
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $access_token;
        $da = $this->get_http_array($url,$data);
        //这里强调显示二维码可以直接写该访问路径，同时也可以使用curl保存到本地，详细用法可以加群或者加我扣扣
    }

    public function get_http_array($url,$post_data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   //没有这个会自动输出，不用print_r();也会在后面多个1
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $output = curl_exec($ch);
        curl_close($ch);
        $out = json_decode($output);
        return $out;
    }

    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
