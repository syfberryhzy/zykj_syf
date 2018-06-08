<?php

namespace App\Api\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use App\Api\Requests\AddressRequest;
# 收货地址
class AddressesController extends Controller
{
    /**
    * 收货列表
    */
    public function index()
    {
        $user_id = auth()->user()->id;
        $data = Address::where('user_id', $user_id)->orderBy('status', 'desc')->first();
        return response()->json(['status' => 'success', 'code' => '201', 'data' => $data]);
    }

    /**
    * 添加地址
    */
    public function store(AddressRequest $request)
    {
        $user_id = auth()->user()->id;
        $address = Address::create([
          'user_id' => $user_id,
          'consignee' => $request->name,
          'phone' => $request->phone,
          'areas' => $request->areas,
          'address' => $request->address,
          'gender' => $request->gender == 1 ? 1: 2,
          'status' => $request->status == 1 ? 1 : 0,
        ]);
      return response()->json(['status' => 'success', 'code' => '201', 'message' => '保存成功']);
    }

    public function show(Address $address)
    {
        return $this->response->array($address)->setStatusCode(201);
    }

    public function update(Address $address, AddressRequest $request)
    {
        $user_id = auth()->user()->id;
        $address = $address->update([
          'consignee' => $request->name,
          'phone' => $request->phone,
          'areas' => $request->areas,
          'address' => $request->address,
          'gender' => $request->gender == 1 ? 1: 2,
          'status' => $request->status == 1 ? 1 : 0,
        ]);
        return response()->json(['status' => 'success', 'code' => '201', 'message' => '保存成功']);
    }
    /**
    * 设置默认
    */
    public function setDefault(Address $address)
    {
        $this->authorize('update', $address);
        $user_id = auth()->user()->id;
        $result = Address::where('user_id', $user_id)->where('id', '<>', $address->id)->update(['status' => 0]);
        $address->status = 1;

        if ($address->save()) {
          return response()->json(['status' => 'success', 'code' => 201, 'message' => '设置成功'])->setStatusCode(201);
        }
        return response()->json(['status' => 'fail', 'code' => 422, 'message' => '设置失败'])->setStatusCode(201);
    }
}
