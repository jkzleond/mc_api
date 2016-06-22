<?php
/**
 * Ajax跨域策略
 * 必须在路由进行匹配之前,否则OPTIONS无法通过路由
 */

use Phalcon\Mvc\User\Plugin;
use Phalcon\Events\Event;
use Phalcon\Mvc\Dispatcher;

class CORSFilter extends Plugin 
{
    public function beforeDispatch(Event $event, Dispatcher $dispatcher)
    {
        if ($this->request->isAjax() or 1)
        {
            $allow_host_list = $this->config->cors->allow_hosts->toArray();
            $http_host = !empty($this->request->getServer('HTTP_ORIGIN')) ? $this->request->getServer('HTTP_ORIGIN') : rtrim($this->request->getServer('HTTP_REFERER'), '/');
            $allow_host = '';
            if(in_array($http_host, $allow_host_list))
            {
                $allow_host = $http_host;
            }
            $this->response->setHeader('Access-Control-Allow-Origin', $allow_host);
            $this->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE');
            $this->response->setHeader('Access-Control-Allow-Headers', 'content-type');
            $this->response->setHeader('Access-Control-Allow-Credentials', 'true');

            if ($this->request->isOptions())
            {
                //处理预请求
                $this->response->setStatusCode('202');
                exit;
            }
        }
    }
}