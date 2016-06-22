<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 15-3-19
 * Time: 下午3:20
 */

use Phalcon\Events\Event;
use Phalcon\Mvc\Dispatcher;

class AuthFilter extends Phalcon\Mvc\User\Plugin
{
    function beforeExecuteRoute(Event $event, Dispatcher $dispatcher)
    {
        $user = $this->session->get('user');
        $auth = $user['auth'];
        $url = $this->router->getRewriteUri();

        if(!$auth && $url != '/login' && $url != '/validatecode' )
        {
            if($this->request->isAjax())
            {
                $this->view->disable();
                $this->response->setStatusCode(302, '未登录');
                $this->response->setJsonContent(array(
                    'redirect' => $this->url->get('/login')
                ));
            }
            else
            {
                $this->response->redirect('/login');
            }

            $this->response->send();
            return false;
        }

    }
}