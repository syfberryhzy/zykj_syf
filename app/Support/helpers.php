<?php

/**
 * 以post方式提交xml到对应的接口url
 * @param string $xml  需要post的xml数据
 * @param string $url  url
 * @param bool $useCert 是否需要证书，默认不需要
 * @param int $second   url执行超时时间，默认30s
 */
function postXmlCurl($xml, $url, $useCert = false, $second = 30)
{
    $ch = curl_init();
    //设置超时
    curl_setopt($ch, CURLOPT_TIMEOUT, $second);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);//严格校验
    //设置header
    curl_setopt($ch, CURLOPT_HEADER, false);
    //要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // if ($useCert == true) {
    //     $path = '/data/www/sharepay.mandokg.com/storage/app/public/';
    //     //设置证书
    //     //使用证书：cert 与 key 分别属于两个.pem文件
    //     curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
    //     curl_setopt($ch, CURLOPT_SSLCERT, $path . \Voyager::setting('sslcert_path'));
    //     curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
    //     curl_setopt($ch, CURLOPT_SSLKEY, $path . \Voyager::setting('sslkey_path'));
    // }
    //post提交方式
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
    //运行curl
    $data = curl_exec($ch);
    //返回结果
    if ($data) {
        curl_close($ch);
        return $data;
    } else {
        $error = curl_errno($ch);
        curl_close($ch);
        return $error;
    }
}

/**
 * 生成签名
 * @param  [type] $params [description]
 * @return [type]         [description]
 */
function makeSign($params)
{
    //签名步骤一：按字典序排序参数
    ksort($params);
    $string = toUrlParams($params);
    //签名步骤二：在string后加入KEY
    $string = $string . "&key=" . config('wechat.payment.key');
    //签名步骤三：MD5加密
    $string = md5($string);
    //签名步骤四：所有字符转为大写
    $sign = strtoupper($string);
    return $sign;
}

/**
 * 输出xml字符
 * @throws WxPayException
**/
function toXml($params)
{
    if (!is_array($params)
        || count($params) <= 0) {
        return response()->json(array('info' => array("数组数据异常！")), 400);
    }

    $xml = "<xml>";

    foreach ($params as $key => $val) {
        if (is_numeric($val)) {
            $xml.="<".$key.">".$val."</".$key.">";
        } else {
            $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
        }
    }
    $xml.="</xml>";
    return $xml;
}

/**
 * 将xml转为array
 * @param string $xml
 * @throws WxPayException
 */
function fromXml($xml)
{
    //禁止引用外部xml实体
    libxml_disable_entity_loader(true);
    return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
}

/**
 * 格式化参数格式化成url参数
 */
function toUrlParams($params)
{
    $buff = "";
    foreach ($params as $k => $v) {
        if ($k != "sign" && $v != "" && !is_array($v)) {
            $buff .= $k . "=" . $v . "&";
        }
    }

    $buff = trim($buff, "&");
    return $buff;
}

/**
*
* 产生随机字符串，不长于32位
* @param int $length
* @return 产生的随机字符串
*/
function getNonceStr($length = 32)
{
    $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
}

/**
 * 获取 access_token
 * @param  [type] $appid  [description]
 * @param  [type] $secret [description]
 * @return [type]         [description]
 */
function getAccessToken($appid, $secret)
{
    if (Cache::has('access_token')) {
        return Cache::get('access_token');
    }

    $client = new \GuzzleHttp\Client();
    $url = "https://api.weixin.qq.com/cgi-bin/token"
            . "?grant_type=client_credential"
            . "&appid={$appid}"
            . "&secret={$secret}";

    $res = $client->request('GET', $url);
    $data = json_decode($res->getBody(), true);
    $access_token = $data['access_token'];

    $expiresAt = $data['expires_in'] - 100;

    Cache::put('access_token', $access_token, $expiresAt / 60);

    return $access_token;
}
