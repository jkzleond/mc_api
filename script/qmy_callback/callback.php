<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 16-8-31
 * Time: 下午4:56
 */

define('API_URL', 'https://api.qingmayun.com/%s/accounts/%s/call/callBack?sig=%s&timestamp=%s');
define('SOFT_VERSION', '20141029');
define('ACCOUNT_SID', 'c569c5f230f04ebb8ddee3da0f5a1d03');
define('ACCOUNT_TOKEN', '472f1597860041728427bfcb239d567e');
define('APP_ID', 'c0b193741c134352b7f4af4551cd1271');

/**
 * 调用轻码云的双向回拨接口
 * @param $caller
 * @param $called
 * @param string|null $from_number
 * @param string|null $to_number
 * @param int|null $allowed_call_time
 * @param bool $is_record
 * @param string|null $user_data
 * @return string
 */
function call_back($caller, $called, $from_number=null, $to_number=null, $allowed_call_time=null, $is_record=true, $user_data=null) {
    $timestamp = date('YmdHis');
    $sig = md5(ACCOUNT_SID.ACCOUNT_TOKEN.$timestamp);
    $url = sprintf(API_URL,SOFT_VERSION, ACCOUNT_SID, $sig, $timestamp);
    $ch = curl_init();
    echo $url;
    $data_str = json_encode(array(
        'callback' => array(
            'appId' => APP_ID,
            'caller' => $caller,
            'called' => $called,
            'fromSerNum' => $from_number,
            'toSerNum' => $to_number,
            'allowedCallTime' => $allowed_call_time,
            'record' => (int)$is_record,
            'userData' => $user_data
        )
    ));

    $headers = array(
        'Accept: application/json',
        'Content-type: application/json',
        'Content-Length: '.strlen($data_str)
    );

    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_TRANSFERTEXT => 1,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_POST => 1,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $data_str
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

$max_call_time = floor($phone_bill / $call_price / 2) * 60; //按剩余话费不同给出最大通话时长

$user_data_str = $order_data['id'].'|'.$car_owner_data['source'].'|'.$car_owner_data['id'];

echo call_back($from, $to, null, $from, $max_call_time, true, $user_data_str);
