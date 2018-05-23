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
      // 等级:A--会员=》会员， B--会员=》游客， C--游客=》游客
        $user = auth()->user();
        $result = [];
        if ($user->parent_id != 0) {
            $result['rank'] = $user->status == 2 ? 'A' : 'B';
            $result['agent_id'] = $user->parent_id;
            return $result;
        }

        #带上级链接
        if ($request->agentId) {
            $agent = User::find($request->agentId);
            #绑定上级关系
            if ($agent && $agent->status == 2 && $user->status == 0) {
              $user->parent_id = $agent->id;
              $user->save();
              $result['rank'] = 'B';
              $result['agent_id'] = $agent->id;
              return $result;
            }
        }
        # 分享赚
        if ($request->sellId && $request->orders) {
          $agent = User::find($request->sellId);
          $result['rank'] = 'C';
          $result['agent_id'] = $agent->id;
          $result['data'] = $request->orders;
          return $result;
        }

        if(count($request->all()) == 0) {
            return $result['rank'] = 'D';
        }
    }

    public function verifyIdentidy($request)
    {
        $data = $this->get_user_rank($request);

        Redis::set('user_rank', serialize($data));
    }

    public function get_share($orderId)
    {
        $user = auth()->user();

        $rank = Redis::get('user_rank');
        #diff_price
        if ($rank == 'B') {
          $old_order_id = $orderId;
          $share_price = 'diff_price';
          $parent_id = auth()->user()->parent_id;
        }
        #share_price
        if ($rank == 'C') {
          $old_order_id = Redis::get('tj_orders_'.auth()->user()->id);
          $share_price = 'share_price';
          $parent_id = Order::find($old_order_id)->user_id;
        }
        $this->award($parent_id, $orderId, $old_order_id, $share_price);
    }

    public function award($parent_id, $qty_id, $price_id, $share_price)
    {
      $qties = OrderItem::where('order_id', $new_orderId)->get()->pluck('num', 'id');
      $prices = OrderItem::where('order_id', $orderId)->get()->pluck($share_price, 'id');
      $user = User::find($parent_id);
      // $total = $user->victory_total;//总业绩
      $current = $user->victory_current;//当前业绩
      foreach($qties as $key => $item) {
        $price = $prices[$key] * $item;
        $sum[] = $price;
        $new = $victory_current + $price;
        $data[] = [
          'user_id' => $parent_id,
          'total' => $current,
          'amount' => $price,
          'current' => $new,
          'model' => 'order_item',
          'uri' => $key,
          'status' => Exchange::AWARD_STATUS,
          'type' => Exchange::ADD_TYPE
        ];
        $current = $new;
      }
      $total = collect($sum)->sum();
      #业绩奖励
      $result = Exchange::create($data);
      if ($result) {
        $user->victory_current = $current;
        $user->victory_total = $total;
        $user->save();
      }
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
    * 添加游客
    */
    public function addVister($user)
    {
        #上级代理 --游客记录+1
        if ($user->parent_id != 0) {
          $recommend = Recommend::where('user_id', $user->parent_id)->first();
          $recommend->visitor .= $recommend->visit == 0 ? $user->id : ','. $user->id;
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
        $this->addMember($user);
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
    public function getQrcode($request, $userId) {
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
        $data['path'] = "pages/index?agentId=21";
        // $data['line_color'] = '{"r":"220","g":"182","b":"99"}';
        $data = json_encode($data);
        $access = json_decode($this->get_access_token(),true);
        $access_token= $access['access_token'];
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $access_token;
        $da = $this->get_http_array($url,$data);

        $file_name = date('Y_m_d_') . uniqid() . '.png';
        $result = Storage::disk('qrcode')->put($file_name, $da);
        Recommend::where('user_id', $userId)->update(['qr_code' => $file_name]);
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
