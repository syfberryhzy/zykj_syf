<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PaymentController extends Controller
{
  public function wechatRefundNotify(Request $request)
  {
      // 给微信的失败响应
      $failXml = '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[FAIL]]></return_msg></xml>';
      // 把请求的 xml 内容解析成数组
      $input = parse_xml($request->getContent());
      // 如果解析失败或者没有必要的字段，则返回错误
      if (!$input || !isset($input['req_info'])) {
          return $failXml;
      }
      // 对请求中的 req_info 字段进行 base64 解码
      $encryptedXml = base64_decode($input['req_info'], true);
      // 对解码后的 req_info 字段进行 AES 解密
      $decryptedXml = openssl_decrypt($encryptedXml, 'AES-256-ECB', md5(config('pay.wechat.key')), OPENSSL_RAW_DATA, '');
      // 如果解密失败则返回错误
      if (!$decryptedXml) {
          return $failXml;
      }
      // 解析解密后的 xml
      $decryptedData = parse_xml($decryptedXml);
      // 没有找到对应的订单，原则上不可能发生，保证代码健壮性
      if(!$order = Order::where('no', $decryptedData['out_trade_no'])->first()) {
          return $failXml;
      }

      if ($decryptedData['refund_status'] === 'SUCCESS') {
          // 退款成功，将订单退款状态改成退款成功
          $order->update([
              'refund_status' => Order::REFUND_STATUS_SUCCESS,
          ]);
      } else {
          // 退款失败，将具体状态存入 extra 字段，并表退款状态改成失败
          $extra = $order->extra;
          $extra['refund_failed_code'] = $decryptedData['refund_status'];
          $order->update([
              'refund_status' => Order::REFUND_STATUS_FAILED,
          ]);
      }

      return '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
  }
}
