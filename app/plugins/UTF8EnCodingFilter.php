<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 15-3-23
 * Time: 上午1:51
 */

use Phalcon\Mvc\User\Plugin;
use Phalcon\Events\Event;
use Phalcon\Mvc\Dispatcher;

class UTF8EnCodingFilter extends Plugin {

    public function beforeExecuteRoute(Event $event, Dispatcher $dispatcher)
    {
        $this->response->setContentType('text/html', 'UTF-8');
    }

}