<?php

/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 16-5-16
 * Time: 下午12:47
 */

use \Palm\Exception\DbTransException;

class MovecarController extends ControllerBase
{

    private $_price = 10.00; //挪车价格

    /**
     * 呼叫车主
     * 通过双向回拨接口呼叫车主
     * @param $from
     * @param $to
     * @param array $user_data
     */
    private function _call_to($from, $to, array $user_data)
    {
        $user_data_str = base64_encode(json_encode($user_data));
        $script_path = dirname(__FILE__).'/../../script/ccp_rest/call_back.php';
        $cmd = 'php '.$script_path.' -f '.$from.' -t '.$to.' -d '.$user_data_str;
        exec($cmd);
    }

    /**
     * 生成微信H5支付统一订单参数(通过执行脚本)
     * @param array $data
     * @return string json格式的统一订单参数
     */
    private function _gen_wx_unified_order(array $data)
    {
        $data_str = base64_encode(json_encode($data));
        $script_path = dirname(__FILE__).'/../../script/wx_pay/jsapi.php';
        $cmd = 'php '.$script_path.' -d '.$data_str;
        return exec($cmd);
    }

    /**
     * 发送短信(执行脚本)
     * @param $phones
     * @param $action
     * @param $content
     * @return string json格式
     */
    private function _send_sms($phones, $action, $content)
    {
        $script_path = dirname(__FILE__).'/../../script/sms/send.php';
        $cmd = 'php '.$script_path.' -a '.$action.' -m '.$phones.' -c '.$content;
        return exec($cmd);
    }

    /*
     * 挪车服务首页
     */
    public function indexAction()
    {
        
    }

    /**
     * 获取挪车记录列表数据
     */
    public function getRecordListAction()
    {

    }

    /**
     * 添加挪车订单
     */
    public function addOrderAction()
    {
        $user = User::getCurrentUser();

        try
        {
            $this->db->begin();
            $success = true;
            $data = $this->request->getJsonRawBody(true);

            $car_owner_list = MoveCar::getCarOwnerList($data['hphm']);
            if(empty($car_owner_list))
            {
                throw new DbTransException('未找到车主电话');
            }

            if(count($car_owner_list) == 1 and $car_owner_list[0]['phone'] == $user['phone'])
            {
               throw new DbTransException('车主就是您本人啊~');
            }

/*            $new_record_id = MoveCar::addRecord(array(
                'user_id' => $user['user_id'],
                'source' => $user['source'],
                'hphm' => $data['hphm']
            ));
            if(!$new_record_id)
            {
                throw new DbTransException('挪车记录添加失败');
            }
            $this->view->setVar('record_id', $new_record_id);*/

            /*生成挪车订单*/
            $ticket_id = !empty($data['ticket_id']) ? $data['ticket_id'] : null;

            $new_order_id = Order::addMoveCarOrder(array(
                'source' => $user['source'],
                'hphm' => $data['hphm'],
                'uphone' => $data['phone']
            ), $user['user_id'], $this->_price, $ticket_id);
            if(!$new_order_id)
            {
                throw new DbTransException('订单生成失败');
            }

            $this->view->setVar('order_id', $new_order_id);

            /*补录挪车用户电话*/
            if(empty($user['phone']))
            {
                User::updateMoveCarUserInfo($user['user_id'], array('phone' => $data['phone']));
            }

            $this->db->commit();
        }
        catch(DbTransException $e)
        {
            $success = false;
            $this->view->setVar('msg', $e->getMessage());
            $this->db->rollback();
        }
        catch(Exception $e)
        {
            $success = false;
            $this->view->setVar('msg', $e->getTraceAsString());
        }

        $this->view->setVar('success', $success);
    }

    /**
     * 获取用户订单列表
     * @param $user_id
     */
    public function getOrderListAction($user_id)
    {
        $start = $this->request->get('start');
        $length = $this->request->get('length');
        $is_latest = $this->request->get('is_latest');

        if($is_latest)
        {
            $order_list = Order::getOrderList($user_id, 'id asc', $start, $length);
        }
        else
        {
            $order_list = Order::getOrderList($user_id, 'id desc', $start, $length);
        }

        $this->view->setVars(array(
            'success' => true,
            'list' => $order_list
        ));
    }

    /**
     * 获取指定ID的订单信息
     * @param $order_id
     */
    public function getOrderInfoAction($order_id)
    {
        $order = Order::getOrderById($order_id);
        $this->view->setVars(array(
            'success' => true,
            'data' => $order
        ));
    }

    /**
     * 获取指定ID的订单的相关车主
     * @param $order_id
     */
    public function getOrderCarOwnersAction($order_id)
    {
        $order = Order::getOrderById($order_id);
        $hphm = $order['record']['hphm'];
        $car_owner_list = MoveCar::getCarOwnerList($hphm);

        $this->view->setVars(array(
            'success' => true,
            'list' => $car_owner_list
        ));
    }

    /**
     * 通知指定ID的订单相关的车主
     */
    public function notifyAction()
    {
        $user = User::getCurrentUser();
        $data = $this->request->getJsonRawBody(true);
        $order_id = $data['order_id'];
        $car_owner_id = $data['car_owner_id'];
        $car_owner_source = $data['car_owner_source'];

        $order = Order::getOrderById($order_id);
        $car_owner = MoveCar::getCarOwnerById($car_owner_id, $car_owner_source);

        if($order['record']['hphm'] != $car_owner['hphm'])
        {
            $this->view->setVars(array(
                'success' => false,
                'msg' => '异常数据'
            ));
            return;
        }

        if($order['is_payed'] != 1)
        {
            $this->view->setVars(array(
                'success' => false,
                'msg' => '订单未支付',
                'code' => 'no_pay'
            ));
            return;
        }

        if(time() - strtotime($order['create_date']) >= 7200)
        {
            $this->view->setVars(array(
                'success' => false,
                'msg' => '订单已过期'
            ));
            return;
        }

        if($order['record']['phone_bill'] < 0.36)
        {
            $this->view->setVars(array(
                'success' => false,
                'msg' => '通话时长已用完'
            ));
            return;
        }

        if(time() - strtotime($order['record']['last_call_time']) < 60)
        {
            $this->view->setVars(array(
                'success' => false,
                'msg' => '两次通知需要间隔1分钟, 请勿频繁通知!'
            ));
            return;
        }

        if(empty($order['record']['last_call_time']))
        {
            //第一次通知,发送短信
            $car_owners = MoveCar::getCarOwnerList($order['record']['hphm']);
            $phones = '';
            foreach($car_owners as $cw)
            {
                $phones .= $cw['phone'].',';
            }
            $phones = rtrim($phones, ',');
            $this->_send_sms($phones, 'mc', $order['record']['hphm']);
        }

        //更新挪车记录的最后通知时间
        Order::updateRecord($order_id, array('last_call_time' => date('Y-m-d H:i:s')));

        Order::addTrack($order_id, 'notify', '通知车主', 'success');

        $this->_call_to($order['record']['uphone'], $car_owner['phone'], $order);

        $this->view->setVars(array(
            'success' => true,
            'data' => array(
                'hphm' => $car_owner['hphm']
            )
        ));

    }

    /**
     * 获取订单轨迹(跟踪)列表
     * @param int|string $order_id
     */
    public function getOrderTrackAction($order_id)
    {
        $track_list = Order::getTrackList($order_id);
        $this->view->setVars(array(
            'success' => true,
            'list' => $track_list
        ));
    }

    /**
     * 订单申诉
     * @param $order_id
     */
    public function appealAction($order_id)
    {
        $data = $this->request->getJsonRawBody(true);
        $is_appealed = Order::isAppealed($order_id);

        if($is_appealed)
        {
            $this->view->setVars(array(
                'success' => false,
                'msg' => '该订单已申诉过,请耐心等待处理'
            ));
            return;
        }

        $success = Order::appealOrderById($order_id, $data);
        $this->view->setVar('success', $success);
    }

    /**
     * 订单反馈
     * @param $order_id
     */
    public function feedbackAction($order_id)
    {
        $data = $this->request->getJsonRawBody(true);
        $is_feedbacked = Order::isFeedBacked($order_id);

        if($is_feedbacked)
        {
            $this->view->setVars(array(
                'success' => false,
                'msg' => '该订单已反馈过,我们会认真考虑您的建议的'
            ));

            return;
        }

        $success = Order::feedBackOrderById($order_id, $data);
        $this->view->setVar('success', $success);
    }

    /**
     * 获取微信H5支付统一订单
     * @param $order_id
     */
    public function getWxUnifiedOrderAction($order_id)
    {
        $user = User::getCurrentUser();
        $open_id = $user['wx_openid'];
        $order = Order::getOrderById($order_id);
        $unified_order_json = $this->_gen_wx_unified_order(array(
            'remote_ip' => $this->request->getClientAddress(),
            'open_id' => $open_id,
            'order' => $order
        ));

        $this->view->setVars(array(
            'success' => true,
            'data' => json_decode($unified_order_json, true)
        ));
    }

    /**
     * 获取车友惠
     * @param $order_id
     */
    public function getCmPayProtocolAction($order_id)
    {
        $order = Order::getOrderById($order_id);
        if(empty($order))
        {
            $this->view->setVars(array(
                'success' => false,
                'msg' => '订单不存在'
            ));
        }
        $order_des = urlencode('挪车业务服务费');
        $xml = <<<XML
        <root>
            <orderId>{$order['id']}<orderId>
            <orderNo>{$order['order_no']}</orderNo>
            <orderFee>{$order['total_fee']}</orderFee>
            <payType>
                <offline>0</offline>
                <alipay>1</alipay>
                <wxpay>1</wxpay>
            </payType>
            <des>$order_des</des>
        </root>
XML;
        $order_name = urlencode('挪车业务服务费');
        $order_info = urlencode(base64_encode($xml));
        $cm_protocol = 'pay://yn.122.net/?ordername='.$order_name.'&orderinfo='.$order_info;

        $this->view->setVars(array(
            'success' => true,
            'data' => $cm_protocol
        ));
    }

    /**
     * 车主登记页面
     */
    public function checkInAction()
    {

    }

    /**
     * 绑定车主信息
     */
    public function bindCarOwnerAction()
    {

    }
}