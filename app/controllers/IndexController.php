<?php

class IndexController extends ControllerBase
{
    //微信appid,secret
    private $_app_id = 'wx1f42c4cb56c5095d';
    private $_app_secret = '276e08a1e2d2c9680823e6ddd0720c4c';
    private $_mc_client_url = 'http://ip.yn122.net/cyh/move_car/www/?{:params}#/tab/move_car';
    private $_mc_client_wx_url = 'http://ip.yn122.net/cyh/move_car/www/?wx_openid={:openid}#/tab/move_car';

    /**
     * 通用入口
     */
    public function indexAction()
    {
    	$user_agent = $this->request->getUserAgent();

    	if(strpos($user_agent, 'MicroMessenger') !== false)
    	{
            //微信环境跳转到微信授权页面(这里暂时使用静默授权)
            $target_url = base64_encode($this->_mc_client_wx_url);
            $wx_auth_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$this->_app_id.'&redirect_uri='.urlencode('http://ip.yn122.net/cyh/wx_redirect/'.$target_url).'&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect';

    		return $this->response->redirect($wx_auth_url);
    	}
        elseif(strpos($user_agent, 'YN122') !== false)
        {
            //车友惠环境则直接登录,并跳转到客户端页面
            $user_id = $this->request->get('userId');
            if(empty($user_id))
            {
                echo '缺少必要参数';
                exit;
            }
            return $this->response->redirect(str_replace('{:params}', 'userId='.$user_id, $this->_mc_client_url));
        }
        else
        {
            //测试时使用
            $user_id = $this->request->get('userId');
            if(empty($user_id))
            {
                echo '缺少必要参数';
                exit;
            }
            return $this->response->redirect(str_replace('{:params}', 'userId='.$user_id, $this->_mc_client_url));
        }
    }

    /**
     * 微信入口
     */
    public function wxEntryAction()
    {
        $code = $this->request->get('code');

        $wx_token_json = file_get_contents('https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$this->_app_id.'&secret='.$this->_app_secret.'&code='.$code.'&grant_type=authorization_code');
        
        $wx_token = json_decode($wx_token_json, true);
        $openid = $wx_token['openid'];
        return $this->response->redirect($this->_mc_client_url.'?openid='.$openid);      
    }
}

