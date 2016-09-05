<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 15-5-29
 * Time: 下午12:29
 */

$router = $di->getShared('router');

$router->addGet('/', array(
    'controller' => 'index',
    'action' => 'index'
));

//微信入口
$router->addGet('/wx_entry', array(
    'controller' => 'index',
    'action' => 'wxEntry'
));

//用户登录
$router->addGet('/login/{way:.*}.json', array(
    'controller' => 'user',
    'action' => 'login'
));

//获取用户信息
$router->addGet('/user.json', array(
    'controller' => 'user',
    'action' => 'getUserInfo'
));

//修改用户电话
$router->addPut('/user/{user_id:.*}/phone.json', array(
    'controller' => 'user',
    'action' => 'modifyPhone'
));

//获取用户车辆列表
$router->addGet('/user/{user_id:.*}/cars.json', array(
    'controller' => 'user',
    'action' => 'getCarList'
));

//添加用户车辆
$router->addPost('/user/{user_id:.*}/cars.json', array(
    'controller' => 'user',
    'action' => 'addCar'
));

//删除车辆
$router->addDelete('/cars/{car_id:\d+}.json', array(
    'controller' => 'user',
    'action' => 'delCar'
));

//修改车辆
$router->addPut('/user/{user_id:.*}/cars/{car_id:\d+}.json', array(
    'controller' => 'user',
    'action' => 'modifyCar'
));

//获取卡券列表
$router->addGet('/tickets/{user_id:.*}.json', array(
    'controller' => 'user',
    'action' => 'getTicketsList'
));

//获取过期卡券
$router->addGet('/tickets/{user_id:.*}/{type:expired}.json', array(
    'controller' => 'user',
    'action' => 'getTicketsList'
));

//获取挪车记录
$router->addGet('/records.json', array(
    'controller' => 'movecar',
    'action' => 'getRecordList'
));

//获取订单列表
$router->addGet('/user/{user_id:.*}/orders.json', array(
    'controller' => 'movecar',
    'action' => 'getOrderList'
));

//获取订单详情
$router->addGet('/orders/{order_id:\d+}.json', array(
    'controller' => 'movecar',
    'action' => 'getOrderInfo'
));

//获取订单轨迹
$router->addGet('/orders/{order_id:\d+}/track.json', array(
    'controller' => 'movecar',
    'action' => 'getOrderTrack'
));

//获取订单相关车主
$router->addGet('/orders/{order_id:\d+}/car_owners.json', array(
    'controller' => 'movecar',
    'action' => 'getOrderCarOwners'
));

//订单申诉
$router->addPost('/orders/{order_id:\d+}/appeal.json', array(
    'controller' => 'movecar',
    'action' => 'appeal'
));

//订单反馈
$router->addPost('/orders/{order_id:\d+}/feedback.json', array(
    'controller' => 'movecar',
    'action' => 'feedback'
));

//添加订单
$router->addPost('/orders.json', array(
    'controller' => 'movecar',
    'action' => 'addOrder'
));

//挪车通知
$router->addPost('/notify.json', array(
    'controller' => 'movecar',
    'action' => 'notify'
));

//标记车主电话联系成功率
$router->addPut('/car_owner/{car_owner_source:.*}/{car_owner_id:\d+}/mark.json', array(
    'controller' => 'movecar',
    'action' => 'markCarOwner'
));

//删除挪车记录
$router->addDelete('/records/{id:\d+}.json', array(
    'controller' => 'movecar',
    'action' => 'deleteRecord'
));

//订单支付
$router->addPost('/orders/{order_id:\d+}/pay/{way:.*}.json', array(
    'controller' => 'movecar',
    'action' => 'orderPay'
));

//微信H5支付,获取支付参数
$router->addGet('/wx_pay/{order_id:\d+}/unified_order.json', array(
    'controller' => 'movecar',
    'action' => 'getWxUnifiedOrder'
));

//车友惠APP内支付, 获取支付协议链接
$router->addGet('/cm_pay/{order_id:\d+}/protocol.json', array(
    'controller' => 'movecar',
    'action' => 'getCmPayProtocol'
));


//双向回拨,话单回调
$router->add('/ccp_callback.json', array(
    'controller' => 'movecar',
    'action' => 'ccpCallback'
));

//www.id98.cn双向回拨,话单回调
$router->add('/id98_callback.json', array(
    'controller' => 'movecar',
    'action' => 'id98Callback'
));

//轻码云双向回呼,话单回调
$router->add('/qmy_callback.json', array(
    'controller' => 'movecar',
    'action' => 'qmyCallback'
));

//操作日志(按操作界面操作事件)
$router->addPost('/oplog.json', array(
    'controller' => 'movecar',
    'action' => 'opLog'
));


/*
其他tmp
 */
//保险20免一活动
//分享步骤 一
$router->addGet('/insurance_share', array(
    'controller' => 'temp',
    'action' => 'insuranceShare'
));

//分享步骤 二
$router->addGet('/insurance_share/{p_user_phone:\d+}', array(
    'controller' => 'temp',
    'action' => 'insuranceShare'
));
$router->addGet('/insurance_share/{p_user_phone:\d+}/{user_phone:\d+}', array(
    'controller' => 'temp',
    'action' => 'insuranceShare'
));

//活动描述
$router->addGet('/insurance_share/describe', array(
    'controller' => 'temp',
    'action' => 'insuranceShareDescribe'
));

//活动
$router->addGet('/insurance_share/draw/{aid:\d+}', array(
    'controller' => 'temp',
    'action' => 'insuranceShareDraw'
));

//中奖列表
$router->addGet('/insurance_share/win_list/{aid:\d+}', array(
    'controller' => 'temp',
    'action' => 'winList'
));

/*
保险巨惠微信公开登录入口
 */
$router->addGet('/insurance/wxentrance', array(
    'controller' => 'insurance',
    'action' => 'microMessengerEntrance'
));

$router->addGet('/insurance/wx_login', array(
    'controller' => 'insurance',
    'action' => 'microMessengerLogin'
));

//微信用户绑定页面
$router->addGet('/insurance/bind/wx', array(
    'controller' => 'insurance',
    'action' => 'microMessengerBind'
));

