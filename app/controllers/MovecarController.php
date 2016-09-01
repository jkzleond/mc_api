<?php

/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 16-5-16
 * Time: 下午12:47
 */

use \Palm\Exception\DbTransException;
use \Palm\Utils\DateTime;

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
            $hphm = strtoupper(str_replace(' ', '',$data['hphm']));
            $car_owner_list = MoveCar::getCarOwnerList($hphm);
            if(empty($car_owner_list))
            {
                throw new DbTransException('未找到车主电话');
            }

            if(count($car_owner_list) == 1 and $car_owner_list[0]['phone'] == $user['phone'])
            {
               throw new DbTransException('车主就是您本人啊~');
            }

            /*生成挪车订单*/

            $new_order_id = Order::addMoveCarOrder(array(
                'source' => $user['source'],
                'hphm' => $hphm,
                'uphone' => $data['phone']
            ), $user['user_id'], $this->_price);
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
        $order['is_appealed'] = Order::isAppealed($order_id);
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

        if($order['is_payed'] != 1)
        {
            $this->view->setVars(array(
                'success' => false,
                'msg' => '订单未支付',
                'code' => 'no_pay'
            ));
            return;
        }

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

        $car_owner_phone = str_replace('-', '', $car_owner['phone']);
        if(strpos($car_owner_phone, '0871') === 0 and strlen($car_owner_phone) < 12)
        {
            //昆明区号的号码加6
            $car_owner_phone = str_replace('0871', '08716', $car_owner_phone);
        }

        $this->_call_to($order['record']['uphone'], $car_owner_phone, array(
            'order' => $order,
            'car_owner' => $car_owner
        ));

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
     * 订单支付
     * @param $order_id
     * @param $way
     */
    public function orderPayAction($order_id, $way)
    {
        $order = Order::getOrderById($order_id);

        if(empty($order))
        {
            $this->view->setVars(array(
                'success' => false,
                'msg' => '订单不存在'
            ));
            return;
        }

        $data = $this->request->getJsonRawBody(true);
        $user_id = $order['user_id'];
        $total_fee = $order['record']['price'];
        $actual_fee = $total_fee;
        $ticket_id = !empty($data['ticket_id']) ? $data['ticket_id'] : null;
        try
        {
            $this->db->begin();
            if(!empty($ticket_id))
            {
                //判断票券是否合法(是否存在, 是否属于用户, 是否能用在该应用, 是否符合使用条件, 是否过期)
                $ticket = Ticket::getTicketById($ticket_id);
                if(empty($ticket) or $ticket['user_id'] != $user_id or ($ticket['scope'] != 1 and $ticket['scope'] != 2) or $ticket['use_fee'] > $total_fee or strtotime($ticket['end_date']) < time() or $ticket['is_lock'] == 1)
                {
                    throw new DbTransException(json_encode($ticket));
                }
                //根据票券类型重新计算订单价格
                $ticket_type = $ticket['type'];
                if($ticket_type == '4')
                {
                    //改价卡
                    $actual_fee = $ticket['value'];
                }
                elseif($ticket_type == '1' or $ticket_type == '2')
                {
                    //红包及优惠券
                    $actual_fee = max($total_fee - $ticket['value'], 0);
                }

                //暂时锁定票券
                $update_ticket_data = array('unlock_time' => date('Y-m-d H:i:s', time() + 15));
                if($actual_fee == 0)
                {
                    $update_ticket_data['state'] = 2;
                }
                $lock_ticket_success = Ticket::updateTicketById($ticket_id, $update_ticket_data);

                if(!$lock_ticket_success)
                {
                    throw new DbTransException('票券异常');
                }
            }

            $update_order_success = Order::updateOrder($order_id, array(
                'total_fee' => $actual_fee,
                'state' => $actual_fee == 0 ? 'ORDER_FREE' : null,
                'ticket_id' => !empty($ticket_id) ? $ticket_id : false
            ));

            if(!$update_order_success)
            {
                throw new DbTransException('支付失败');
            }

            $this->db->commit();

            $order['total_fee'] = $actual_fee;
        }
        catch(DbTransException $e)
        {
            $this->db->rollback();
            $this->view->setVars(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
            return;
        }

        if($actual_fee == 0)
        {
            $this->view->setVars(array(
                'success' => true,
                'code' => 'order_free'
            ));
            return;
        }

        if($way == 'wxpay')
        {
            //获取微信H5支付统一订单
            $user = User::getCurrentUser();
            $open_id = $user['wx_openid'];
            $unified_order_json = $this->_gen_wx_unified_order(array(
                'remote_ip' => $this->request->getClientAddress(),
                'open_id' => $open_id,
                'order' => $order
            ));

            $this->view->setVars(array(
                'success' => true,
                'data' => json_decode($unified_order_json, true)
            ));

            return;
        }
        elseif($way == 'cm')
        {
            //车友惠支付链接
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
            return;
        }
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
     * 获取车友惠支付链接
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
            return;
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
     * 标记车主成功率
     * @param $car_owner_source
     * @param $car_owner_id
     */
    public function markCarOwnerAction($car_owner_source, $car_owner_id)
    {
        $data = $this->request->getJsonRawBody(true);
        $is_success = $data['success'];
        $status = isset($data['status']) ? $data['status'] : 1;
        $success = MoveCar::markCarOwnerById($car_owner_id, $is_success, $status, $car_owner_source);
        $this->view->setVars(array(
            'success' => $success
        ));
    }

    /**
     * 双向回拨,话单回调
     */
    public function ccpCallbackAction()
    {
        $data = $this->request->getJsonRawBody(true);
        $user_data = explode('|', $data['callerCdr']['userData']);
        $order_id = $user_data[0];
        $car_owner_source = $user_data[1];
        $car_owner_id = $user_data[2];
        $caller = $data['calledCdr']['caller'];
        $called = $data['calledCdr']['called'];
        $caller_start_time = $data['callerCdr']['starttime'];
        $caller_start_time = !empty($caller_start_time) ? DateTime::ft2ft($caller_start_time, '%Y%m%d%H%M%S', '%Y-%m-%d %H:%M:%S') : null;
        $caller_end_time = $data['callerCdr']['endtime'];
        $caller_end_time = !empty($caller_end_time) ? DateTime::ft2ft($caller_end_time, '%Y%m%d%H%M%S', '%Y-%m-%d %H:%M:%S') : null;
        $called_start_time = $data['calledCdr']['starttime'];
        $called_start_time = !empty($called_start_time) ? DateTime::ft2ft($called_start_time, '%Y%m%d%H%M%S', '%Y-%m-%d %H:%M:%S') : null;
        $called_end_time = $data['calledCdr']['endtime'];
        $called_end_time = !empty($called_end_time) ? DateTime::ft2ft($called_end_time, '%Y%m%d%H%M%S', '%Y-%m-%d %H:%M:%S') : null;
        $caller_duration = $data['callerCdr']['duration'];
        $called_duration = $data['calledCdr']['duration'];
        $bye_type = $data['callerCdr']['byetype'];

        try
        {
            $this->db->begin();

            $add_order_track_sql = <<<SQL
            insert into MC_OrderTrack(order_id, action, title, result) values (:order_id, :action, :title, :result)
SQL;
            $add_order_track_bind = array(
                'order_id' => $order_id,
                'action' => 'call_end',
                'title' => '语音连接结束-'.(!empty($called_duration) ? '连接成功' : '连接失败'),
                'result' => !empty($called_duration) ? 'success' : 'failed'
            );
            $add_order_track_success = ModelEx::nativeExecute($add_order_track_sql, $add_order_track_bind);
            if(!$add_order_track_success)
            {
                throw new DbTransException('add order track failed');
            }

            $add_call_record_sql = <<<SQL
        insert into MC_CallRecord (order_id, caller, called, caller_start_time, called_start_time, caller_end_time, called_end_time, caller_duration, called_duration, bye_type) values (:order_id, :caller, :called, :caller_start_time, :called_start_time, :caller_end_time, :called_end_time, :caller_duration, :called_duration, :bye_type)
SQL;
            $add_call_record_bind = array(
                'order_id' => $order_id,
                'caller' => $caller,
                'called' => $called,
                'caller_start_time' => $caller_start_time,
                'called_start_time' => $called_start_time,
                'caller_end_time' => $caller_end_time,
                'called_end_time' => $called_end_time,
                'caller_duration' => $caller_duration,
                'called_duration' => $called_duration,
                'bye_type' => $bye_type
            );

            $add_call_record_success = ModelEx::nativeExecute($add_call_record_sql, $add_call_record_bind);
            if(!$add_call_record_success)
            {
                throw new DbTransException('add call record failed');
            }

            $phone_bill = ceil($caller_duration / 60.00) * 0.12 + ceil($called_duration / 60.00) * 0.12;
            $update_phone_bill_sql = <<<SQL
            update OrderToMoveCar set phone_bill -= $phone_bill where order_id = :order_id
SQL;
            $update_phone_bill_bind = array(
                'order_id' => $order_id
            );

            $update_phone_bill_success = ModelEx::nativeExecute($update_phone_bill_sql, $update_phone_bill_bind);
            if(!$update_phone_bill_success)
            {
                throw new DbTransException('update phone bill failed');
            }

            $car_owner_table = 'MC_Car';
            if($car_owner_source == 'jg')
            {
                $car_owner_table = 'JGCarOwner';
            }
            $car_owner_field_str = 'call_count += 1';
            if($called_duration == 0)
            {
                $car_owner_field_str .= ', not_link_count += 1';
            }

            $mark_car_owner_sql = <<<SQL
            update $car_owner_table set $car_owner_field_str where id = :car_owner_id
SQL;
            $mark_car_owner_bind = array(
                'car_owner_id' => $car_owner_id
            );
            $mark_car_owner_success = ModelEx::nativeExecute($mark_car_owner_sql, $mark_car_owner_bind);
            if(!$mark_car_owner_success)
            {
                throw new DbTransException('mark car owner failed');
            }

            $this->db->commit();
        }
        catch(DbTransException $e)
        {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     *  www.id98.cn的双向回拨回调处理
     */
    public function id98CallbackAction()
    {
        $this->view->disable();
        $call_id = $this->request->get('call_id');
        $uid = $this->request->get('uid');
        $phone = $this->request->get('phone');
        $call = $this->request->get('call');
        $start_time = $this->request->get('start_time');
        $end_time = $this->request->get('end_time');
        $last_time = $this->request->get('last_time');
        $fee = $this->request->get('fee');
        $endtype = $this->request->get('endtype');
        $ext = $this->request->get('ext');
        $record_url = $this->request->get('recordurl');

        file_put_contents('id68callback.log', $this->request->get());

    }

    /**
     * 记录操作日志
     */
    public function opLogAction()
    {

        $data = $this->request->getJsonRawBody(true);
        $action_name = $data['action_name'];
        $action_title = $data['action_title'];

        $user = User::getCurrentUser();
        $user_crt = new Criteria($user);
        $add_oplog_sql = <<<SQL
        insert into IAM_USEROPLOG (userid, uname, clienttype, mod, act, requrl, reqdate, comments, ip, createDate) values (:user_id, :user_name, :client_type, :mod, :action_name, :requrl, :reqdate, :action_title, :ip, :create_date)
SQL;
        $add_oplog_bind = array(
            'user_id' => $user_crt->user_id,
            'user_name' => $user_crt->user_name,
            'client_type' => $user_crt->client_type,
            'mod' => 'move_car',
            'action_name' => $action_name,
            'requrl' => 'http://116.55.248.76:8090/mc_api',
            'reqdate' => date('Y-m-d'),
            'action_title' => $action_title,
            'ip' => $this->request->getServer('REMOTE_ADDR'),
            'create_date' => date('Y-m-d H:i:s')
        );

        //echo $add_oplog_sql;
        //print_r($add_oplog_bind);

        $success = ModelEx::nativeExecute($add_oplog_sql, $add_oplog_bind);
        $this->view->setVars(array(
            'success' => $success
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