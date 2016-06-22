<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 15-3-19
 * Time: 下午10:40
 */

$eventsManager = $di->getShared('eventsManager');

/**
 * 登录处理
 */

//$eventsManager->attach('dispatch', new AutoLoginFilter($di));



/**
 * 权限验证
 */
//$eventsManager->attach('dispatch', new AuthFilter($di));
/**
 * utf8编码
 */
//$eventsManager->attach('dispatch', new UTF8EnCodingFilter($di));

/**
 * 任务插件
 */
//注册该事件钩子以处理自动任务的接取,交付并执行奖励和所有任务的条件满足判断
//$mission_filter = new MissionFilter($di);
//$eventsManager->attach('dispatch', $mission_filter);
//$eventsManager->attach('user-action', $mission_filter);

/**
 * ajax请求json自动转换
 */
$eventsManager->attach('dispatch', new AjaxFilter($di));

/**
 * ajax跨域策略
 */
$eventsManager->attach('dispatch', new CORSFilter($di));