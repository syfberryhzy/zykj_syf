<?php

namespace App\Api\Requests;

// use Illuminate\Foundation\Http\FormRequest;
use Dingo\Api\Http\FormRequest;

class AgentRequest extends FormRequest
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
          'wechat' => [
            // 微信账号仅支持6-20个字母、数字、下划线或减号，以字母开头
            'required',
            'regex:/^[a-zA-Z]{1}[-_a-zA-Z0-9]{5,19}+$/'
          ],
          'areas' => 'required|string|max:150',
          'address' => 'required|string|max:150'
        ];
    }

    public function messages()
    {
      return [
        'name.required' => '姓名不能为空',
        'name.max' => '姓名不能超过30个字符',
        'phone.required' => '手机号不能为空',
        'phone.regex' => '请正确填写手机号',
        'wechat.required' => '微信号不能为空',
        'wechat.regex' => '请输入正确的微信号',
        'areas.required' => '收货区域不能为空',
        'areas.max' => '收货区域不能超过150个字符',
        'address.required' => '详细地址不能为空',
        'address.max' => '详细地址不能超过150个字符',
      ];
    }
}
