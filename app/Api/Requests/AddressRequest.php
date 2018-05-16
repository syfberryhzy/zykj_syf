<?php

namespace App\Api\Requests;

use Dingo\Api\Http\FormRequest;

class AddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:30',
            'phone' => [
              'required',
              'regex:/^13\d{9}$|^14\d{9}$|^15\d{9}$|^17\d{9}$|^18\d{9}$/'
            ],
            'areas' => 'required|string|max:150',
            'address' => 'required|string|max:255'
        ];
    }

    public function attributes()
    {
      return [
        'name.required' => '联系人不能为空',
        'phone.required' => '手机号不能为空',
        'phone.numeric' => '请正确填写手机号',
        'phone.regex' => '请正确填写手机号',
        'areas.required' => '收货区域不能为空',
        'areas.max' => '收货区域不能超过150个字符',
        'address.required' => '详细地址不能为空',
        'address.max' => '详细地址不能超过150个字符',
      ];
    }

    public function messages()
    {
      return [
        'name.required' => '联系人不能为空',
        'phone.required' => '手机号不能为空',
        'phone.numeric' => '请正确填写手机号',
        'phone.regex' => '请正确填写手机号',
        'areas.required' => '收货区域不能为空',
        'areas.max' => '收货区域不能超过150个字符',
        'address.required' => '详细地址不能为空',
        'address.max' => '详细地址不能超过150个字符',
      ];
    }
}
