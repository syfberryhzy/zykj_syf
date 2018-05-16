<?php

namespace App\Api\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Api\Requests\UserRequest;
use App\Transformers\UserTransformer;
# 个人中心
class UsersController extends Controller
{
    /**
    * 个人信息
    */
    public function me()
    {
        return $this->response->array($this->user());
    }

    /**
    *
    */
    public function store(UserRequest $request)
    {
      //
    }
}
