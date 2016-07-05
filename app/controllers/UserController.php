<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 15-6-9
 * Time: 下午2:12
 */

use \Palm\Exception\DbTransException;
use \Phalcon\Db;
use \Phalcon\Session\Bag as SessionBag;

class UserController extends ControllerBase
{
    public function loginAction($way)
    {
        $user_info = null;
        $user_id = $this->request->get('userId');
        $wx_openid = $this->request->get('wx_openid');

        if($way == 'cm')
        {
            //车友惠userid登录
            $user_info = User::getUserInfoById($user_id);
        }
        elseif($way == 'wx' and !empty($wx_openid))
        {
            //微信openid登录

            //绑定过车友惠账户的用户
            $user_info = User::getWxBindUser($wx_openid);
            if(empty($user_info))
            {
                $user_info = User::getMoveCarUserInfo($wx_openid.'@wx_cm.com');
            }

            if(empty($user_info))
            {
                $user_info = array(
                    'user_id' => $wx_openid.'@wx_cm.com'
                );
            }

            $user_info['wx_openid'] = $wx_openid;
        }

        if(!empty($user_info))
        {
            try
            {
                try
                {
                    $this->db->begin();
                    $mc_user = User::getMoveCarUserInfo($user_info['user_id']);
                    if(empty($mc_user))
                    {
                        $add_mc_user_success = User::addMoveCarUserInfo($user_info['user_id']);
                        if(!$add_mc_user_success) throw new DbTransException();
                    }
                    $this->db->commit();
                }
                catch(Exception $e)
                {
                    $this->db->rollback();
                    throw $e;
                }

                /*首登红包逻辑*/
                try
                {
                    $this->db->begin();
                    $get_red_bag_sql = "select top 1 id from Ticket where user_id = :user_id and datediff(dd, create_date, getdate()) = 0 and title='每日首登红包'";
                    $get_red_bag_bind = array(
                        'user_id' => $user_info['user_id']
                    );
                    $daily_red_bag = $this->db->fetchOne($get_red_bag_sql, Db::FETCH_ASSOC,$get_red_bag_bind);

                    if(!empty($daily_red_bag) and $user_info['user_id'] != 'jkzleond@163.com')
                    {
                        //已经有每日红包和不是测试号
                        throw new DbTransException();
                    }

                    $add_ticket1_success = User::addTicket(array(
                        'type' => 1,
                        'scope' => 2,
                        'value' => 10.00,
                        'use_fee' => 0.00,
                        'title' => '每日首登红包',
                        'des' => '每日首登赠送红包,仅限当日使用',
                        'end_date' => date('Y-m-d', strtotime('+1 day')),
                        'user_id' => $user_info['user_id']
                    ));

//                    $get_ticket_sql = "select top 1 id from Ticket where user_id = :user_id and datediff(dd, create_date, getdate()) = 0 and title='每日首登改价卡'";
//                    $get_ticket_bind = array(
//                        'user_id' => $user_info['user_id']
//                    );
//                    $daily_ticket = $this->db->fetchOne($get_ticket_sql, Db::FETCH_ASSOC,$get_ticket_bind);
//
//                    if(!empty($daily_ticket))
//                    {
//                        throw new DbTransException();
//                    }

                    $add_ticket2_success = User::addTicket(array(
                        'type' => 1,
                        'scope' => 2,
                        'value' => 9.90,
                        'use_fee' => 0.00,
                        'title' => '每日首登红包',
                        'des' => '每日首登赠送红包,仅限当日使用',
                        'end_date' => date('Y-m-d', strtotime('+1 day')),
                        'user_id' => $user_info['user_id']
                    ));

                    if(!$add_ticket1_success or !$add_ticket2_success)
                    {

                        throw new DbTransException();
                    }

                    $this->db->commit();
                }
                catch(DbTransException $e)
                {
                    $this->db->rollback();
                    /*$add_ticket_success = User::addTicket(array(
                        'type' => 2,
                        'scope' => 2,
                        'value' => 10.00,
                        'use_fee' => 0.00,
                        'title' => '每日首登优惠券',
                        'des' => '每日首登赠送优惠券,仅限当日使用',
                        'end_date' => date('Y-m-d H:i:s', strtotime('+1 day')),
                        'user_id' => $user_info['user_id']
                    ));

                    $add_ticket_success = User::addTicket(array(
                        'type' => 3,
                        'scope' => 2,
                        'value' => 10.00,
                        'use_fee' => 0.00,
                        'title' => '每日首登通话时长卡',
                        'des' => '每日首登赠送通话时长卡,仅限当日使用',
                        'end_date' => date('Y-m-d H:i:s', strtotime('+1 day')),
                        'user_id' => $user_info['user_id']
                    ));

                    $add_ticket_success = User::addTicket(array(
                        'type' => 4,
                        'scope' => 2,
                        'value' => 0.10,
                        'use_fee' => 0.00,
                        'title' => '每日首登改价卡',
                        'des' => '每日首登赠送改价卡,仅限当日使用',
                        'end_date' => date('Y-m-d H:i:s', strtotime('+1 day')),
                        'user_id' => $user_info['user_id']
                    ));*/
                }

                /*首登红包逻辑结束*/

                //获取票券数量
                $mc_ticket_count = User::getTicketCount($user_info['user_id'], null, 2);

                //获取用户车辆数量
                $mc_car_count = User::getCarCount($user_info['user_id']);

                $session_id = $this->session->getId();

                $user = new SessionBag('user');

                foreach($user_info as $key => $value)
                {
                    $user->set($key, $value);
                }

                $client_type = $this->request->get('clientType');
                $ver = $this->request->get('ver');
                $uuid = $this->request->get('uuid');

                $user->set('client_type', $client_type);
                $user->set('ver', $ver);
                $user->set('uuid', $uuid);
                $user->set('source', $way); //用户来源
                $user->set('ticket_count', $mc_ticket_count); //挪车业务相关用户信息
                $user->set('car_count', $mc_car_count); //挪车业务,用户登记的车辆数

                $guid = User::setCurrentUser($user_info['user_id'], $user->getIterator());


                $this->view->setVars(array(
                    'success' => true,
                    'data' => array(
                        'session_id' => $session_id,
                        'guid' => $guid,
                        'user_info' => $user->getIterator(),
                    )
                ));
            }
            catch(Exception $e)
            {
                echo 'Error:'.$e->getMessage().PHP_EOL;
                echo 'File:'.$e->getFile().PHP_EOL;
                echo 'Line:'.$e->getLine().PHP_EOL;
                echo $e->getTraceAsString().PHP_EOL;

                $this->view->setVars(array(
                    'success' => false
                ));
            }
        }
        else
        {
            $this->view->setVars(array(
                'success' => false
            ));
        }
    }

    /**
     * 获取session id
     */
    public function getSessionIdAction()
    {
        $session_id = $this->session->getId();
        $this->view->setVar('session_id', $session_id);
    }

    /**
     * 获取用户信息
     */
    public function getUserInfoAction()
    {
        $user_id = $this->request->get('user_id');
        $user = null;
        if(empty($user_id))
        {
            $user = User::getCurrentUser();
        }
        else
        {
            $user = User::getUserInfoById($user_id);
        }

        $this->view->setVar('data', $user);
    }

    /**
     * 获取用户头像图片
     * @param $user_id
     */
    public function getUserAvatarAction($user_id)
    {
        $this->view->disable();
        $this->response->setContentType('image/png');
        $pic_data_str = User::getUserAvatarById($user_id);

        echo base64_decode($pic_data_str);
    }

    /**
     * 获取用户红包列表
     * @param string $user_id
     * @param string $type 'available' or 'expired'
     */
    public function getTicketsListAction($user_id, $type='available')
    {
        $start = $this->request->get('start');
        $length = $this->request->get('length');
        $is_latest = $this->request->get('is_latest');
        $tickets_list = null;
        $is_expired = false;
        if($type == 'expired')
        {
            $is_expired = true;
        }

        $tickets_list = User::getTicketList($user_id, null, 2, $is_expired, $start, $length, $is_latest);

        $this->view->setVars(array(
            'success' => true,
            'list' => $tickets_list
        ));
    }

    /**
     * 修改挪车业务用户电话
     * @param $user_id
     */
    public function modifyPhoneAction($user_id)
    {
        $data = $this->request->getJsonRawBody(true);
        $success = User::updateMcUser($user_id, $data);
        $this->view->setVar('success', $success);
    }

    /**
     * 获取用户车辆列表
     * @param $user_id
     */
    public function getCarListAction($user_id)
    {
        // $start, $length, $is_latest 三个参数用户下拉刷新与无穷scroll
        $start = $this->request->get('start');
        $length = $this->request->get('length');
        $is_latest = $this->request->get('is_latest');

        if($is_latest)
        {
            $car_list = User::getCarList($user_id, 'id asc', $start, $length);
        }
        else
        {
            $car_list = User::getCarList($user_id, 'id desc', $start, $length);
        }

        $this->view->setVars(array(
            'success' => true,
            'list' => $car_list
        ));
    }

    /**
     * 添加车辆
     * @param $user_id
     */
    public function addCarAction($user_id)
    {
        $data = $this->request->getJsonRawBody(true);
        $hphm = $data['hphm'];
        $car_exists = User::isCarExists($user_id, $hphm, true);

        if(!empty($car_exists))
        {
            if($car_exists['state'] != 0)
            {
                $this->view->setVars(array(
                    'success' => false,
                    'msg' => '该车辆已存在!'
                ));
            }
            else
            {
                //车辆已存在但被标记为删除,则重新标记为正常
                $success = User::updateCar($car_exists['id'], array('state' => 1));
                $this->view->setVar('success', $success);
                $this->view->setVar('data', $car_exists);
            }
            return;
        }

        $new_car_id = User::addCar($user_id, $hphm);

        if(empty($new_car_id))
        {
            $this->view->setVars(array(
                'success' => false,
                'msg' => '添加车辆失败'
            ));
            return;
        }

        $this->view->setVars(array(
            'success' => true,
            'data' => array(
                'id' => $new_car_id,
                'hphm' => $data['hphm']
            )
        ));
    }

    /**
     * 删除车辆
     * @param $car_id
     */
    public function delCarAction($car_id)
    {
        $success = User::deleteCar($car_id);
        if(!$success)
        {
            $this->view->setVar('msg', '车辆删除失败');
        }
        $this->view->setVar('success', $success);
    }

    /**
     * 修改车辆信息
     * @param $user_id
     * @param $car_id
     */
    public function modifyCarAction($user_id, $car_id)
    {
        $data = $this->request->getJsonRawBody(true);
        $hphm = $data['hphm'];
        $car_exists = User::isCarExists($user_id, $hphm, true);

        if(!empty($car_exists))
        {
            if($car_exists['state'] != 0)
            {
                $this->view->setVars(array(
                    'success' => false,
                    'msg' => '该车辆已存在!'
                ));
                return;
            }
            else
            {
                //同一车牌(并且也属于同一用户)车辆已存在但被标记为删除,则重新标记为正常,同时将欲修改的车辆标记为删除
                try
                {
                    $this->db->begin();
                    $del_success = User::deleteCar($car_id);
                    if(!$del_success)
                    {
                        throw new DbTransException();
                    }
                    $update_success = User::updateCar($car_exists['id'], array('state' => 1));
                    if(!$update_success)
                    {
                        throw new DbTransException();
                    }
                    $success = $this->db->commit();
                    $this->view->setVar('data', $car_exists);
                }
                catch(DbTransException $e)
                {
                    $this->db->rollback();
                    $success = false;
                }
            }
        }
        else
        {
            $success = User::updateCar($car_id, $data);
        }
        $this->view->setVar('success', $success);
        if(!$success) $this->view->setVar('msg', '车辆更新失败');
    }


}