<?php

namespace App\Api\Controllers;

use App\Models\User;
use App\Models\Apply;
use App\Models\Recommend;
use Illuminate\Http\Request;
use App\Api\Requests\AgentRequest;
use App\Repositories\AgentRepositoryEloquent;
use App\Repositories\ExchangeRepositoryEloquent;
use Illuminate\Support\Facades\Storage;
use QrCode;

class AgentsController extends Controller
{
    public $agent;
    public $exchange;

    public function __construct(AgentRepositoryEloquent $reponsitory, ExchangeRepositoryEloquent $exchangeRep)
    {
        $this->agent = $reponsitory;
        $this->exchange = $exchangeRep;
    }
    /**
     * 申请代理
     */
    public function store(AgentRequest $request)
    {
        $user = auth()->user();
        if ($user->status != 0) {
          return response()->json(['status' => 'fail', 'code' => '401', 'message' => '请勿重复申请']);
        }
        $parent_id = $user->parent_id == 0 ? 1 : $user->parent_id;
        Apply::create([
          'user_id' => $user->id,
          'parent_id' => $parent_id,
          'username' => $request->name,
          'phone' => $request->phone,
          'wechat' => $request->wechat,
          'areas' => $request->areas,
          'details' => $request->address
        ]);
        $user->update(['status' => 1, 'parent_id' => $parent_id]);
        if ($parent_id == 1) {
          $this->agent->addVister($user);
        }
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
    public function get_qrcode(Request $request)
    {

        $res = Recommend::where('user_id', auth()->user()->id)->first();
        if (!$res) {
          return response()->json(['status' => 'fail', 'code' => '401', 'message' => '您还没有成为代理']);
        }
        if (!$res->qr_code) {
          $mini = \EasyWeChat::miniProgram();
          $path = 'pages/index?agent_id='. auth()->user()->id;
          $optional = [
            'width' => 200,
          ];
          $data = $mini->app_code->get($path, $optional);
          $file_name = date('Y_m_d_') . uniqid() . '.png';
          $result = Storage::disk('qrcode')->put($file_name, $data);

          if ($result) {
            $res->qr_code = $file_name;
            $res->save();
            return response()->json(['status' => 'success', 'code' => '201', 'data' => env('APP_URL_QRCODES').'/'.$file_name]);
          }
           return response()->json(['status' => 'fail', 'code' => '422', 'message' => '生成代理二维码失败']);
         }
        return response()->json(['status' => 'success', 'code' => '201', 'data' => env('APP_URL_QRCODES').'/'. $res->qr_code]);
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
    //   $file_name = date('Y_m_d_') . uniqid() . '.png';
    //
    // $result = Storage::disk('qrcode')->put($file_name, '');
    // QrCode::format('png')->errorCorrection('L')->size(200)->merge('/public/logo.png',.50)->margin(2)
    // ->color(220,182,99)
    // ->encoding('UTF-8')->generate('http://laravelacademy.org', public_path('uploads/qrcode/' . $file_name));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
    /**
    * 提现申请
    */
    public function withward(Request $request)
    {
        if (!$request->money) {
          return response()->json(['status' => 'fail', 'code' => '401', 'message' => '请输入提现金额']);
        }
        if ($this->exchange->withward(auth()->user()->id, $request->money)) {
          return response()->json(['status' => 'success', 'code' => '201', 'message' => '提现申请已提交']);
        }
        return response()->json(['status' => 'fail', 'code' => '422', 'message' => '悟空，你又调皮']);
    }
}
