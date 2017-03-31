<?php
ini_set('display_errors', 1);
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 16-6-20
 * Time: 下午3:14
 */

$options = getopt('a:m:c:');

$action = $options['a'];
$mobile = $options['m'];
$content = $options['c'];

$template_setting = array(
    //验证码
    'code' => '【车友惠】您的验证码为%s, 10秒后将失效',
    //挪车
    'mc' => '【车友惠】%s的车主,您好.您的爱车挡了车友的路.稍后该车友会通过车友惠平台虚拟号码电话联系您,请勿拒接哦~感谢您的合作!'
);

$template = $template_setting[$action];
$content = sprintf($template, $content);
$content = urlencode($content);
$ch = curl_init();
$url = "http://apis.baidu.com/kingtto_media/106sms/106sms?tag=2&mobile=$mobile&content=$content";
$header = array(
    'apikey: 36ceb2e4dba28990224a96247a8eefd8',
);

// 添加apikey到header
curl_setopt($ch, CURLOPT_HTTPHEADER  , $header);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// 执行HTTP请求
curl_setopt($ch , CURLOPT_URL , $url);
$res = curl_exec($ch);
curl_close($ch);
echo $res;