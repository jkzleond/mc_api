<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 16-8-31
 * Time: 下午4:56
 */

define('APP_KEY', '6d21a53c6bf995e82980c731e32af339');
define('API_URL', 'http://api.id98.cn/api/v2/callback');

$api_settings = array(
    'is_phone_show' => false,
    'is_call_show' => true,
    'is_record' => false,
    'is_max_length' => true,
    'cdr_callback_url' => 'http://116.55.248.76:8090/mc_api/id98_callback.json'
);

/**
 * 调用id98(www.id98.cn)的双向回拨接口
 * @param $phone
 * @param $call
 * @param $return_url
 * @param $uid
 * @param $ext
 * @param null $max_length
 * @param int $phone_show
 * @param int $call_show
 * @param int $record
 * @param string $output
 * @return string
 */
function call_back($phone, $call, $return_url, $uid, $ext, $max_length=null,  $phone_show=0, $call_show=1, $record=0, $output='json') {
    $ch = curl_init(API_URL);
    $param_str = 'appkey='.APP_KEY.'&phone='.$phone.'&call='.$call.'&phoneShow='.$phone_show.'&callShow='.$call_show.'&uid='.$uid.'&ext='.$ext.'&max_length='.$max_length.'&record='.$record.'&returnUrl='.$return_url.'&output='.$output;
    curl_setopt_array($ch, array(
        CURLOPT_TRANSFERTEXT => 1,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => $param_str
    ));
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

$options = getopt('f:t:d:');

$from = $options['f'];
$to = $options['t'];
$user_data_encoded = $options['d'];
$user_data = json_decode(base64_decode($options['d']), true);
$order_data = $user_data['order'];
$car_owner_data = $user_data['car_owner'];
$order_id = $order_data['id'];

$phone_bill = $order_data['record']['phone_bill']; //订单剩余话费

$call_price = 0.085;
if($api_settings['is_phone_show']){
    $call_price += 0.01;
}
if($api_settings['is_call_show']){
    $call_price += 0.01;
}
if($api_settings['is_record']){
    $call_price += 0.01;
}
if($api_settings['is_max_length']) {
    $call_price += 0.01;
}

$max_call_time = floor($phone_bill / $call_price); //按剩余话费不同给出最大通话时长

$hangup_cdr_url = 'http://116.55.248.76:8090/mc_api/id98_callback.json';
$user_data_str = $order_data['id'].'|'.$car_owner_data['source'].'|'.$car_owner_data['id'];

echo call_back($from, $to, $api_settings['cdr_callback_url'], $from, $user_data_str, $max_call_time, (int)$api_settings['is_phone_show'], (int)$api_settings['is_call_show'], (int)$api_settings['is_record']);
