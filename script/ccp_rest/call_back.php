<?php
/*
 *  Copyright (c) 2014 The CCP project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a Beijing Speedtong Information Technology Co.,Ltd license
 *  that can be found in the LICENSE file in the root of the web site.
 *
 *   http://www.yuntongxun.com
 *
 *  An additional intellectual property rights grant can be found
 *  in the file PATENTS.  All contributing project authors may
 *  be found in the AUTHORS file in the root of the source tree.
 */

include_once(dirname(__FILE__)."/SDK/CCPRestSDK.php");

//子帐号
$subAccountSid= '2afe800c1b6e11e6bb9bac853d9f54f2';

//子帐号Token
$subAccountToken= 'ae7854830d2924047e93cf10e700ed0c';

//VoIP帐号
$voIPAccount= '8013699800000002';

//VoIP密码
$voIPPassword= 'QgFlCQet';

//应用Id
$appId='8a48b5515493a1b70154b9c4615825c5';

//请求地址，格式如下，不需要写https://
$serverIP='app.cloopen.com';

//请求端口 
$serverPort='8883';

//REST版本号
$softVersion='2013-12-26';

    /**
    * 双向回呼
    * @param $from 主叫电话号码
    * @param $to 被叫电话号码
    * @param $customerSerNum 被叫侧显示的客服号码
    * @param $fromSerNum 主叫侧显示的号码
    * @param $promptTone 自定义回拨提示音
    * @param $alwaysPlay 是否一直播放提示音
    * @param $terminalDtmf 用于终止播放promptTone参数定义的提示音
    * @param $userData 第三方私有数据
    * @param $maxCallTime 最大通话时长
    * @param $hangupCdrUrl 实时话单通知地址
    * @param $needBothCdr 是否给主被叫发送话单
    * @param $needRecord 是否录音
    * @param $countDownTime 设置倒计时时间
    * @param $countDownPrompt 倒计时时间到后播放的提示音
    */
function callBack($from,$to,$customerSerNum=null,$fromSerNum=null,$promptTone=null,$alwaysPlay=null,$terminalDtmf=null,$userData=null,$maxCallTime=null,$hangupCdrUrl=null,$needBothCdr=null,$needRecord=null,$countDownTime=null,$countDownPrompt=null) {
        // 初始化REST SDK
        global $appId,$subAccountSid,$subAccountToken,$voIPAccount,$voIPPassword,$serverIP,$serverPort,$softVersion;
        $rest = new REST($serverIP,$serverPort,$softVersion);
        $rest->setSubAccount($subAccountSid,$subAccountToken,$voIPAccount,$voIPPassword);
		$rest->setAppId($appId);

        // 调用回拨接口
        echo "Try to make a callback,called is $to <br/>";
        $result = $rest->callBack($from,$to,$customerSerNum,$fromSerNum,$promptTone,$alwaysPlay,$terminalDtmf,$userData,$maxCallTime,$hangupCdrUrl,$needBothCdr,$needRecord,$countDownTime,$countDownPrompt);
        if($result == NULL ) {
            echo "result error!";
        }
          if($result->statusCode!=0) {
            echo "error code :" . $result->statusCode . "<br>";
            echo "error msg :" . $result->statusMsg . "<br>";
            //TODO 添加错误处理逻辑
          } else {
            echo "callback success!<br>";
            // 获取返回信息
            $callback = $result->CallBack;
            echo "callSid:".$callback->callSid."<br/>";
            echo "dateCreated:".$callback->dateCreated."<br/>";
           //TODO 添加成功处理逻辑
          }
}

//Demo调用,参数填入正确后，放开注释可以调用    
//callBack("主叫电话号码","被叫电话号码","被叫侧显示的客服号码","主叫侧显示的号码","自定义回拨提示音","是否一直播放提示音","用于终止播放promptTone参数定义的提示音","第三方私有数据","最大通话时长","实时话单通知地址","是否给主被叫发送话单","是否录音","设置倒计时时间","倒计时时间到后播放的提示音");

$options = getopt('f:t:d:');

$from = $options['f'];
$to = $options['t'];
$order_data_encoded = $options['d'];
$order_data = json_decode(base64_decode($options['d']), true);
$order_id = $order_data['id'];

$phone_bill = $order_data['record']['phone_bill']; //订单剩余话费
$max_call_time = floor($phone_bill / 0.12); //按剩余话费不同给出最大通话时长


$hangup_cdr_url = 'http://116.55.248.76:8090/ccp_callback.php';
$need_both_cdr = true;

callBack($from, $to, null, null, null, null, null, $order_data['id'], $max_call_time, $hangup_cdr_url, $need_both_cdr);

$json = <<<JSON
{
    "calledCdr":{
        "appId":"8a48b5515493a1b70154b9c4615825c5",
        "beginCallTime":"",
        "byetype":"",
        "callSid":"160606162442635300010185003810f4",
        "called":"15087057477",
        "caller":"13294958423",
        "duration":"0",
        "endtime":"",
        "lineNumber":"263",
        "lostRate":"",
        "ringingBeginTime":"",
        "ringingEndTime":"",
        "starttime":"",
        "subId":"2afe800c1b6e11e6bb9bac853d9f54f2",
        "userData":""
    },
    "callerCdr":{
        "appId":"8a48b5515493a1b70154b9c4615825c5",
        "beginCallTime":"20160606162442",
        "byetype":"-10",
        "callSid":"160606162442635300010185003810f4",
        "called":"13294958423",
        "caller":"01058364432",
        "duration":"0",
        "endtime":"",
        "lineNumber":"326",
        "lostRate":"0.00",
        "ringingBeginTime":"20160606162447",
        "ringingEndTime":"20160606162512",
        "starttime":"",
        "subId":"2afe800c1b6e11e6bb9bac853d9f54f2",
        "userData":""
    },
    "orderid":"CM1018520160606162442331008",
    "recordurl":""
}

JSON;
