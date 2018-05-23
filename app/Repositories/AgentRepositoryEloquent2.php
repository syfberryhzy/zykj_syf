<?php

namespace App\Repositories;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\AgentRepository;
use App\Models\Agent;
use App\Models\User;
use App\Models\Recommend;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redis;
use App\Validators\AgentValidator;

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
        return Agent::class;
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
            $result['agentId'] = $user->parent_id;
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
              $result['agentId'] = $agent->id;
              return $result;
            }
          }

        # 分享赚
        if ($request->sellId && $request->orders) {
          $agent = User::find($request->sellId);
          $result['rank'] = 'C';
          $result['agentId'] = $agent->id;
          $result['data'] = $request->orders;
          return $result;
        }
        return $result['rank'] = 'D';
    }

    public function verifyIdentidy($request)
    {
        $data = $this->get_user_rank($request);
        Redis::set('user_rank', $data);
    }


}
