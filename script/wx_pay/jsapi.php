<?php 
ini_set('date.timezone','Asia/Shanghai');
ini_set('display_errors', 1);
//error_reporting(E_ERROR);
require_once dirname(__FILE__)."/lib/WxPay.Api.php";
require_once dirname(__FILE__)."/WxPay.JsApiPay.php";
require_once dirname(__FILE__).'/log.php';

//初始化日志
$logHandler= new CLogFileHandler(dirname(__FILE__)."/logs/".date('Y-m-d').'.log');
$log = Log::Init($logHandler, 15);


$options = getopt('d:');
$data = json_decode(base64_decode($options['d']), true);
$open_id = $data['open_id'];
$remote_ip = $data['remote_ip'];
$_SERVER['REMOTE_ADDR'] = $remote_ip;
$order = $data['order'];


//①、获取用户openid
$tools = new JsApiPay();
//$openId = $tools->GetOpenid();

//②、统一下单
$input = new WxPayUnifiedOrder();
$input->SetBody('挪车业务');
$input->SetAttach('挪车业务');
$input->SetOut_trade_no($order['order_no']);
$input->SetTotal_fee(round($order['total_fee'] * 100));
$input->SetTime_start(date("YmdHis"));
$input->SetTime_expire(date("YmdHis", time() + 600));
$input->SetGoods_tag("挪车业务");
$input->SetNotify_url("http://116.55.248.76/cyh/wx_h5_pay/notify.php");
$input->SetTrade_type("JSAPI");
$input->SetOpenid($open_id);
$order = WxPayApi::unifiedOrder($input);
echo $tools->GetJsApiParameters($order);


//③、在支持成功回调通知中处理成功之后的事宜，见 notify.php
/**
 * 注意：
 * 1、当你的回调地址不可访问的时候，回调通知会失败，可以通过查询订单来确认支付是否成功
 * 2、jsapi支付时需要填入用户openid，WxPay.JsApiPay.php中有获取openid流程 （文档可以参考微信公众平台“网页授权接口”，
 * 参考http://mp.weixin.qq.com/wiki/17/c0f37d5704f0b64713d5d2c37b468d75.html）
 */