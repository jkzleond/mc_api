<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 15-5-29
 * Time: 下午3:43
 */

use \Phalcon\Mvc\User\Plugin;
use \Phalcon\Events\Event;
use \Phalcon\Mvc\Dispatcher;

class AutoLoginFilter extends Plugin
{
    public function beforeExecuteRoute(Event $event, Dispatcher $dispatcher)
    {
        //手动切换session_id
        $user = null;
        //获取url中带的session id
        $session_id = $this->request->get('ssid');

        if($session_id)
        {
            $this->session->setId($session_id);
        }

    }
}