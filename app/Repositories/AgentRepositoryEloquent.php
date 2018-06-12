<?php

namespace App\Repositories;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\AgentRepository;
use App\Models\User;
use App\Models\Recommend;
use App\Validators\AgentValidator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redis;
use QrCode;
use Auth;
/**
 * Class AgentRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class AgentRepositoryEloquent extends BaseRepository implements AgentRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return User::class;
    }



    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }
    /**
    * 验证用户身份
    */
    public function get_user_rank($request)
    {
      // 等级:A--会员=》会员， B--会员=》游客， C--游客=》游客 D = 无推荐 E=本人自己
        $user = auth()->user();
        $result = [];
        if ($user->status == 2) {
            $result['rank'] = 'A';
            $result['agent_id'] = $user->parent_id;
            return $result;
        }
        if ($user->parent_id != 0) {
            $result['rank'] = $user->status == 2 ? 'A' : 'B';
            $result['agent_id'] = $user->parent_id;
            return $result;
        }
        if ($request->agent_id == $user->id ||  $request->sell_id == $user->id) {
            $result['rank'] = 'D';
            return $result;
        }
        #带上级链接
        if ($request->agent_id) {
            $agent = User::find($request->agent_id);
            #绑定上级关系
            if ($agent && $agent->status == 2 && $user->status == 0) {
              $user->parent_id = $agent->id;
              $user->save();
              # 添加游客数据
              $this->joinIn($user, $is_vip = false);
              $result['rank'] = 'B';
              $result['agent_id'] = $agent->id;
              return $result;
            }
        }
        # 分享赚
        if ($request->sell_id && $request->order_id) {
            $agent = User::find($request->sell_id);
            $result['rank'] = 'C';
            $result['agent_id'] = $agent->id;
            $result['order_id'] = $request->order_id;
            return $result;
        }
        if(!$request->all()) {
            $result['rank'] = 'D';
            return $result;
        }
    }

    public function verifyIdentidy($request)
    {
        $data = $this->get_user_rank($request);
        Redis::set('user_rank', serialize($data));
        return $data;
    }


    /**
    * 后台审核代理--同意
    */
    public function updateUser($userId, $action, $apply)
    {
        $user = User::find($userId);
        $user->status = 0;
        if ($action == 1) {
          $user->status = 2;
          $user->username = $apply->username;
          $user->phone = $apply->phone;
          $user->areas = $apply->areas;
          $user->address = $apply->details;
          $user->wechat = $apply->wechat;
        }
        $user->save();
        #添加代理信息
        if ($action == 1) {
          $this->addAgent($user);
        }
    }

    /**
    * 加入代理数据
    */
    public function joinIn($user, $is_vip = true)
    {
      if ($user->parent_id == 0) {
          return;
      }

      $recommend = Recommend::where('user_id', $user->parent_id)->first();

      $vistors = $recommend->visitor ? json_decode($recommend->visitor) : [];
      $members = $recommend->member ? json_decode($recommend->member) : [];

      #升级会员
      if ($is_vip) {
        if ($vistors && in_array($user->id, $vistors)) {
          $key = array_search($user->id, $vistors);
          unset($vistors[$key]);
        } else {
          array_push($members, $user->id);
        }
      } else {
        if ($members && in_array($user->id, $members)) {
          $key = array_search($user->id, $members);
          unset($members[$key]);
        } else {
           array_push($vistors, $user->id);
        }
      }
      $recommend->visitor = json_encode($vistors);
      $recommend->member = json_encode($members);
      $recommend->visit = count($vistors);
      $recommend->recommend = count($members);
      $recommend->save();
      return;
    }

    /**
    * 团队业绩
    */
    public function victoryTree($user)
    {
        // 每天消费总结
    }



        /**
        * 添加游客
        */
        public function addVister($user)
        {
            #上级代理 --游客记录+1
            if ($user->parent_id != 0) {
              $recommend = Recommend::where('user_id', $user->parent_id)->first();

              $vistor = $recommend->visit == 0 ? [] : explode(',', $recommend->visitor);
              $vistor[] = $user->id;
              $recommend->visitor .= implode(',', $vistor);
              $recommend->visit += 1;
              $recommend->save();
            }
        }

        /**
        * 添加会员
        */
        public function addMember($user)
        {
            $recommend = Recommend::where('user_id', $user->parent_id)->first();
            #游客记录-1
            // $visitors = $recommend->visit > 1 ? implode(',', $recommend->visitor) : [$recommend->visitor];
            $visitors = $this->get_array($recommend->visitor);
            if ($key = array_search($user->id, $visitors)) {
              array_splice($visitors, $key, 1);
              $recommend->visit -= 1;
            }
            $recommend->visitor = count($visitors) > 1 ? explode(',', $visitors) : '';

            #会员记录+1
            if (!$this->isExist($user, $recommend->member)) {
              $recommend->member .= $recommend->recommend == 0 ? $user->id : ','. $user->id;
              $recommend->recommend += 1;
            }
            $recommend->save();
        }


    /**
    * 是否存在
    */
    public function isExist($user, $data)
    {
      $arr = $this->get_array($data);
      return in_array($user, $arr);
    }

    /**
    * 是否存在
    */
    public function get_array($data)
    {
      if (!$data) {
        return [];
      }
      if (strpos($data, ',') == false) {
        return [$data];
      }
      return implode(',', $data);
    }
    /**
    * 添加代理
    */
    public function addAgent($user) {
        #添加新代理--不重复添加
        $res = Recommend::where('user_id', $user->id)->first();
        if (!$res) {
          Recommend::create([
            'user_id' => $user->id,
            'parent_id' => $user->parent_id
          ]);
        }
        $this->joinIn($user, $is_vip = true);
    }

   //获取access_token
    public function get_access_token(){
        $appid = env('WECHAT_MINI_PROGRAM_APPID');
        $secret = env('WECHAT_MINI_PROGRAM_SECRET');
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
        return $data = $this->curl_get($url);
    }
    public function curl_get($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        return $data;
    }
    /**
    * 生成代理二维码
    */
    public function getQrcode1($request, $userId) {
        header('content-type:image/png');
        //header('content-type:image/gif');格式自选，不同格式貌似加载速度略有不同，想加载更快可选择jpg
        //header('content-type:image/jpg');
        $data = array();
        // $data['scene'] = $request->scene;
        // $data['path'] = $request->path;
        // $data['auto_color'] = $request->auto_color;
        // $data['line_color'] = $request->line_color;
        // $data['width'] = $request->width;
        $data['scene'] = '10086';
        $data['path'] = "pages/index?agent_id=21";
        // $data['line_color'] = '{"r":"220","g":"182","b":"99"}';
        $data = json_encode($data);
        $access = json_decode($this->get_access_token(),true);
        $access_token= $access['access_token'];
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $access_token;
        $da = $this->get_http_array($url,$data);
        $file_name = date('Y_m_d_') . uniqid() . '.png';
        $result = Storage::disk('qrcode')->put($file_name, $da);
        Recommend::where('user_id', $userId)->update(['qr_code' => $file_name]);
        return $file_name;
    }
	public function getQrcode($request, $userId) {


        //$data = array();
        // $data['scene'] = $request->scene;
        // $data['page'] = $request->path;
        // $data['auto_color'] = $request->auto_color;
        // $data['line_color'] = $request->line_color;
        // $data['width'] = $request->width;
        $scene = '10086';
        $page = "pages/index?agent_id=21";

        $access = json_decode($this->get_access_token(),true);
        $access_token= $access['access_token'];
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $access_token;

        $file_name =  date('Y_m_d_') . uniqid() . '.png';
       $file = public_path('uploads/qrcode/' . $file_name);

    $qrcode = array(
        'scene'         => $scene,
        'width'         => 200,
        'page'          => $page,
        'auto_color'    => true
    );
    $result = request($url,true,'POST',json_encode($qrcode));

    $errcode = json_decode($result,true)['errcode'];
    $errmsg = json_decode($result,true)['errmsg'];
    if($errcode)
		return array('status'=>0,'info'=>$errmsg);
    $res = file_put_contents($file,$result);            //  将获取到的二维码图片流保存成图片文件
  dump($res);
    if($res===false)
		return array('status'=>0,'info'=>'生成二维码失败');
	dd($file);
    return array('status'=>1,'info'=>$file);           //返回本地图片地址
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

}
