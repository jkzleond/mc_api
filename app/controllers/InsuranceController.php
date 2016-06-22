<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 15-6-17
 * Time: 下午12:44
 */

class InsuranceController extends ControllerBase
{
    public function indexAction()
    {
        $user = $this->session->get('user');
        $insurance_set_list = InsuranceSet::getList();
        $compulsory_state_list = CompulsoryState::getList();
        $this->view->setVars(array(
            'user' => (object) $user,
            'insurance_sets' => $insurance_set_list,
            'compulsory_states' => $compulsory_state_list
        ));
    }

    /**
     * 添加初算结果
     */
    public function addFirstResultAction()
    {

        //直接接受backbone的模型json字符串并解码未关联数组
        $json_data= $this->request->getJsonRawBody(true);

        $result = InsuranceCalculator::calInsuranceResult(new Criteria($json_data));

        $new_param_id = Insurance::addInsuranceParam($json_data);

        if (!$new_param_id) {
            $this->view->setVars(array(
                'success' => false
            ));
            return;
        }


        $new_result_id = Insurance::addInsuranceResult($result);

        if (!$new_result_id) {
            $this->view->setVars(array(
                'success' => false
            ));
            return;
        }


        $user = User::getCurrentUser();

        $insurance_info = array(
            'user_id' => $user['user_id'],
            'car_type_id' => !empty($json_data['car_type_id']) ? $json_data['car_type_id'] : 1, 
            'insurance_param_id' => $new_param_id,
            'insurance_result_id' => $new_result_id,
            'state_id' => 1, //设置为已自算状态
            'user_name' => isset($user['uname']) ? $user['uname'] : '微博用户',
            //'phone_no' => $json_data['phone']
            //'insurance_set_id' => $json_data['insurance_set_id']
        );

        $new_info_id = Insurance::addInsuranceInfo($insurance_info);

        if (!$new_info_id) {
            $this->view->setVars(array(
                'success' => false
            ));
            return;
        }

        $this->view->setVars(array(
            'success' => true,
            'row' => array(
                'info_id' => $new_info_id
            )
        ));
    }

    /**
     * 申请精算
     */
    public function actualAction()
    {
        $form_data = $this->request->getJsonRawBody(true);
        $info_id = $form_data['info_id'];

        $is_using_attach = !empty($form_data['driving_license_a']);

        $info = Insurance::getInsuranceInfoById($info_id);
        $user = User::getCurrentUser();

        $info_update = array(
            'state_id' => 2, //设置状态为待精算
            'last_modified_time' => date('Y-m-d H:i:s'),
            'phone' => $user['phone'],
            'weixin' => !empty($form_data['weixin']) ? $form_data['weixin'] : null
        );

        if(!$is_using_attach)
        {
            Insurance::updateInsuranceParam($info['param_id'], $form_data);
            $car_info = CarInfo::getCarInfoByUserIdAndHphm($user['user_id'], $form_data['hphm']);
            if(!empty($car_info))
            {
                CarInfo::updateCarInfo($car_info['id'], array(
                    'engine_number' => $car_info['engine_number'],
                    'frame_number' => $car_info['frame_number'],
                    'auto_name' => $car_info['auto_name']
                ));

                $info_update['car_no_id'] = $car_info['id'];
            }
            else
            {
                $new_car_no_id = CarInfo::addCarInfo(array(
                    'user_id' => $user['user_id'],
                    'hphm' => $form_data['hphm'],
                    'no_hphm' => isset($form_data['no_hphm'])? 1 : 0,
                    'engine_number' => $form_data['engine_number'],
                    'frame_number' => $form_data['frame_number'],
                    'auto_name' => $form_data['auto_name']
                ));

                $info_update['car_no_id'] = $new_car_no_id;
            }
            $info_update['user_name'] = $form_data['user_name'];
            //$info_update['sfzh'] = $form_data['sfzh'];
        }
        else
        {
            $attach_id = Insurance::addInsuranceAttach($form_data);
            $info_update['attach_id'] = $attach_id;
        }

        $success = Insurance::updateInsuranceInfo($info_id, $info_update);

        //邀请码处理
        if( isset($form_data['invitation_code']) )
        {
            $is_involved = Activity::isUserJoin($user['user_id'], 228);

            if(!$is_involved)
            {
                $p_user = Activity::getActivityUser(array(
                    'invitation_code' => $form_data['invitation_code']
                ));

                if(!empty($p_user))
                {
                    Activity::addActivityUser(array(
                        'user_id' => $user['user_id'],
                        'p_user_id' => $p_user['user_id']
                    ), 228);
                }
            }
        }

        $return_data = array(
            'success' => $success
        );

        if($success)
        {
            $return_data['err_msg'] = '申请精算成功';
        }
        else
        {
            $return_data['err_msg'] = '申请精算失败';
        }

        $this->view->setVars($return_data);
    }

    /**
     * 获取保险信息
     * @param $id
     */
    public function getInsuranceInfoAction($id)
    {
        $info = Insurance::getInsuranceInfoById($id);

        $this->view->setVars(array(
            'row' => $info
        ));
    }

    public function getInsuranceInfoListAction($state)
    {
        $page_size = $this->request->get('rows');
        $page_num = $this->request->get('page');
        $info_list = null;
        $info_total = 0;

        $user = User::getCurrentUser();
        $user_id = $user['user_id'];


        if($state == 1 or $state == 2)
        {
            $info_list = Insurance::getInsuranceInfoList(array('state' => $state, 'user_id' => $user_id), $page_num, $page_size);
            $info_total = Insurance::getInsuranceInfoCount(array('state' => $state, 'user_id' => $user_id));

            foreach($info_list as &$info)
            {
                $result = Insurance::getInsuranceFirstResultById($info['result_id']);
                $company = Insurance::getMinDiscount();
                $discount = $company['discount'];
                $gift = $company['gift'];
                $gift2 = $company['gift2'];

                $res_crt = new Criteria($result);
                $total_standard = $res_crt->totalStandard;
                $total_business = $total_standard - $res_crt->standardCompulsoryInsurance;
                $after_discount_compulsory = $res_crt->afterDiscountCompulsoryInsurance;
                $after_discount_business = $total_business * $discount;
                $total_after_discount = $after_discount_compulsory + $after_discount_business;
                $info['min_after_discount'] = round($total_after_discount, 2);

                $gift_money = $res_crt->afterDiscountCompulsoryInsurance * $gift + ($res_crt->standardThird < 0 ?  0 : $res_crt->standardThird) * $discount * $gift +  ($total_business - ($res_crt->standardThird<0?0: $res_crt->standardThird) - $res_crt->standardOptionalDeductible - $res_crt->standardNotDeductible) * $discount * $gift2;
                $info['gift_money'] = round($gift_money, 2);
            }
        }
        elseif($state == 3)
        {
            $info_list = Insurance::getActualedInsuranceiNfoList(array('user_id' => $user_id), $page_num, $page_size);
            $info_total = Insurance::getActualedInsuranceInfoCount(array('user_id' => $user_id));
        }
        elseif($state == 4)
        {
            $info_list = Insurance::getHasOrderInsuranceInfoList(array('user_id' => $user_id), $page_num, $page_size);
            $info_total = Insurance::getHasOrderInsuranceCount(array('user_id' => $user_id));
        }
        elseif($state == 5)
        {
            $info_list = Insurance::getHasPolicyInsuranceList(array('user_id' => $user_id), $page_num, $page_size);
            $info_total = Insurance::getHasPolicyInsuranceCount(array('user_id' => $user_id));
        }

        $this->view->setVars(array(
            'rows' => $info_list,
            'total' => $info_total
        ));
    }

    /**
     * 获取保险初算结果
     * @param $id
     */
    public function getInsuranceFirstResultAction($id)
    {
        $first_result = Insurance::getInsuranceFirstResultById($id);

        $this->view->setVars(array(
            'row' => $first_result
        ));
    }

    /**
     * 获取保险的所有公司精算结果
     * @param $info_id
     */
    public function getFinalResultsAction($info_id)
    {
        $result_list = Insurance::getInsuranceFinalResults($info_id);

        $this->view->setVars(array(
            'rows' => $result_list,
            'total' => count($result_list)
        ));
    }

    /**
     * 获取保险公司列表
     */
    public function getInsuranceCompanyListAction()
    {
        $company_list = Insurance::getInsuranceCompanyList(5);

        $this->view->setVars(array(
            'rows' => $company_list
        ));
    }

    /**
     * 保险下单
     * @param $info_id
     */
    public function applyPolicyAction($info_id)
    {
        $data = $this->request->getJsonRawBody(true);

        $company_id = $data['company_id'];
        $final_result_id = $data['result_id'];

        $user = User::getCurrentUser();

        $info = Insurance::getInsuranceInfoById($info_id);

        $final_param_id = $info['final_param_id'];

        Insurance::updateFinalParam($final_param_id, array(
            'company_id' => $company_id
        ));

        $return_data = array();

        if($info['state_id'] >= 4)
        {
            $return_data['success'] = false;
            $return_data['err_msg'] = '此订单已下单';
        }
        else
        {
            $success = Insurance::updateInsuranceInfo($info_id, array(
                'company_id' => $company_id,
                'final_result_id' => $final_result_id
            ));

            if($success)
            {
                $final_result = Insurance::getInsuranceFinalResultById($final_result_id);

                $total_fee = $final_result['totalAfterDiscount'];

                $order_result = Order::addOrder('insurance', array(
                    'info_id' => $info_id,
                    'user_id' => $user['user_id'],
                    'total_fee' => $total_fee
                ));

                if(!$order_result)
                {
                    $return_data['success'] = false;
                    $return_data['err_msg'] = '保险支付订单生成失败';
                }
                else
                {
                    $return_data['success'] = true;
                    $return_data['order_info'] = array(
                        'order_id' => $order_result[0],
                        'order_no' => $order_result[1],
                        'order_fee' => $order_result[2]
                    );
                }
            }
            else
            {
                $return_data['success'] = false;
                $return_data['err_msg'] = '订单生成失败';
            }
        }

        $this->view->setVars($return_data);
    }

    /**
     * 获取保险订单详情
     * @param $insurance_info_id
     */
    public function getInsuranceOrderInfoAction($insurance_info_id)
    {
        $insurance_order_info = Insurance::getInsuranceOrderInfo($insurance_info_id);

        $this->view->setVars(array(
            'row' => $insurance_order_info
        ));
    }

    /**
     * 确认下单
     * @param $insurance_info_id
     */
    public function certainInsuranceOrderAction($insurance_info_id)
    {
        $data = $this->request->getJsonRawBody(true);
        $connection = $this->db;
        $connection->begin();
        $new_address_id = Insurance::addInsuranceAddress($data);

        $return_data = array();

        if(!$new_address_id)
        {
            $connection->rollback();
            $return_data['success'] = false;
            $return_data['err_msg'] = '下单失败!';
        }

        $success = Insurance::updateInsuranceInfo($insurance_info_id, array(
            'address_id' => $new_address_id,
            'state_id' => 4
        ));

        if(!$success)
        {
            $connection->rollback();
            $return_data['success'] = false;
            $return_data['err_msg'] = '下单失败!';
        }

        $success = $connection->commit();

        if($success)
        {
            $return_data['success'] = true;
            $return_data['err_msg'] = '下单成功!';
        }

        $this->view->setVars($return_data);
    }

    /**
     * 添加保险预约
     */
    public function addReservationAction()
    {
        $data = $this->request->getJsonRawBody(true);

        //提交数据包含car_info_id说明该车辆信息已存在,则修改
        if(!empty($data['car_info_id']))
        {
            $car_info_id = $data['car_info_id'];

            //同一个号码同一车辆只能预约一次(未报价前)
            if( Insurance::isReserved($data['phone'], $car_info_id) )
            {
                $this->view->setVars(array(
                    'success' => false,
                    'err_msg' => '车辆已预约'
                ));
                return;
            }

            CarInfo::updateCarInfo($car_info_id, $data);
        }
        else 
        {
            //车辆信息不存在,则添加
            $data['car_info_id'] = CarInfo::addCarInfo($data);
        }

        $success = Insurance::addInsuranceReservation($data);

        $this->view->setVars(array(
            'success' => $success,
            'err_msg' => '保险预约成功'
        ));
    }

    /**
     * 公开的微信入口
     */
    public function microMessengerEntranceAction()
    {
        $this->view->disable();

        $key = $this->request->get('key', null, null);
        if(!$key)
        {
            echo 'key验证失败!';
            return;
        }

        $key_arr = explode('|', base64_decode($key));
        $app_id = $key_arr[0];
        $app_secret = $key_arr[1];

        if(!$app_id or !$app_secret)
        {
            echo 'key验证失败';
            return;
        }

        $source = $this->request->get('source', null, 'cm');

        $user_agent = $this->request->getUserAgent();
        print_r($user_agent);
        if(strpos($user_agent, 'MicroMessenger') === false)
        {
            echo '请在微信环境中打开!';
            return;
        }

        $wx_redirect_url = urlencode('http://ip.yn122.net:8092/insurance/wx_login?appid='.$app_id.'&secret='.$app_secret.'&source='.$source);
        $oauth2_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$app_id.'&redirect_uri='.$wx_redirect_url.'&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect';
        return $this->response->redirect($oauth2_url);
    }

    /**
     * 微信登录
     */
    public function microMessengerLoginAction()
    {
        $app_id = $this->request->get('appid', null, null);
        $app_secret = $this->request->get('secret', null, null);

        $state = $this->request->get('state');
        $code = $this->request->get('code');

        $source = $this->request->get('source', null, 'cm');

        $access_token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$app_id.'&secret='.$app_secret.'&code='.$code.'&grant_type=authorization_code';
        $access_token_json = file_get_contents($access_token_url);
        $access_token_info = json_decode($access_token_json, true);
        $openid = $access_token_info['openid'];
        
        $bind_user = User::getWxBindUser($openid, $source); 

        if(!$bind_user)
        {
            //micromessenger user bind page
            return $this->response->redirect('/insurance/bind/wx?source='.$source.'&openid='.$openid);
        }
        else
        {   
            //app entrance page
            return $this->response->redirect('/?userId='.$bind_user['user_id'].'#insurance');
        }
    }

    /**
     * 微信账号绑定页面
     */
    public function microMessengerBindAction()
    {
        $openid = $this->request->get('openid');
        $source = $this->request->get('source');
        
        $user_phone = $this->request->get('user_phone', null, null);

        $this->view->setVars(array(
            'openid' => $openid,
            'source' => $source,
            'bind_success' => false,
            'is_user' => true
        ));

        if($user_phone)
        {   
            $this->_doMicroMessengerBind($user_phone, $openid, $source);
        }
    }

    /**
     * 处理微信用户绑定
     */
    private function _doMicroMessengerBind($user_phone, $openid, $source='cm')
    {
        
        $user = User::getUserByPhone($user_phone);

        if(empty($user))
        {
            $user_agent = $this->request->getUserAgent();

            $this->view->setVar('is_user', false);

            $client_type = null;

            if( strpos($user_agent, 'iPhone') !== false )
            {
                $client_type = 'iPhone';
            }
            elseif( strpos($user_agent, 'iPod') !== false )
            {
                $client_type = 'iPod';
            }
            elseif( strpos($user_agent, 'iPad') !== false )
            {
                $client_type = 'iPad';
            }
            elseif( strpos($user_agent, 'Android') !== false )
            {
                $client_type = 'Android';
            }

            $register_result = file_get_contents('http://192.168.3.31/vehIllegalQuery/index.php?mod=Member&act=RegisterSave&PWD='.$user_phone.'&PHONE='.$user_phone.'&clientType='.$client_type);

            $user = User::getUserByPhone($user_phone);

            $this->view->setVars(array(
                'car_mate_user_phone' => $user_phone,
                'car_mate_pwd' => $user_phone
            ));
        }

        $bind_success = User::wxBindUser($user['user_id'], $openid, $source);

        $this->view->setVar('bind_success', $bind_success);
        $this->view->setVar('user_id', $user['user_id']);
    }

}