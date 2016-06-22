<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 15-7-5
 * Time: 下午4:09
 */

use \Phalcon\Db;
use \Palm\Exception\DbTransException;

class Order extends ModelEx
{
    /**
     * 生成订单
     * @param $order_type
     * @param array $criteria
     * @return mixed
     */
    public static function addOrder($order_type, array $criteria=null)
    {
        $crt = new Criteria($criteria);
        if($order_type == 'activity')
        {
            return self::addActivityOrder($crt->au_id, $crt->user_id, $crt->pay_items);
        }
        elseif($order_type == 'insurance')
        {
            return self::addInsuranceOrder($crt->info_id, $crt->user_id, $crt->total_fee);
        }
        elseif($order_type == 'move_car')
        {
            return self::addMoveCarOrder($crt->record_id, $crt->user_id, $crt->total_fee, $crt->ticket_id);
        }
    }

    /**
     * 添加活动订单
     * @param $au_id
     * @param $user_id
     * @param array $pay_items
     * @return bool|array 成功返回 array($order_id, $order_no, $total_fee)
     *                    失败返回 false
     */
    public static function addActivityOrder($au_id, $user_id, array $pay_items)
    {

        $connection = self::_getConnection();
        $connection->begin();

        $goods_number_map = array();
        $goods_price_map = array();

        foreach($pay_items as $pay_item)
        {
            $goods_number_map[$pay_item['id']] = $pay_item['number'];
        }

        $goods = Goods::getGoodsByIds(array_keys($goods_number_map));

        $total_fee = 0;

        foreach($goods as $one_goods)
        {
            $goods_price_map[$one_goods['id']] = $one_goods['price'];
            $total_fee += $one_goods['price'] * $goods_number_map[$one_goods['id']];
        }

        $add_order_sql = "insert into PayList (orderNo, orderName, money, userId, orderType, relId) values (:order_no, '活动收费', :total_fee, :user_id, 'activity', :au_id)";
        $add_order_bind = array(
            'total_fee' => $total_fee,
            'user_id' => $user_id,
            'au_id' => $au_id
        );

        do
        {
            $order_no = self::_genOrderNo();
            $add_order_bind['order_no'] = $order_no;
            $add_order_success = $connection->execute($add_order_sql, $add_order_bind);
            $err_info = $connection->getInternalHandler()->errorInfo();
        }while($err_info[1] == '2627');

        if(!$add_order_success)
        {
            $connection->rollback();
            return false;
        }

        $new_order_id = $connection->lastInsertId();

        $order_detail_values_str = '';

        foreach($goods_price_map as $goods_id => $goods_price)
        {
            $goods_number = $goods_number_map[$goods_id];
            $order_detail_values_str .= "($goods_id, $new_order_id, $goods_number, $goods_price), ";
        }

        $order_detail_values_str = rtrim($order_detail_values_str, ', ');

        $add_order_detail_sql = "insert into OrderToGoods (goods_id, order_id, number, price) values $order_detail_values_str";

        $add_order_detail_success = $connection->execute($add_order_detail_sql);

        if(!$add_order_detail_success)
        {
            $connection->rollback();
            return false;
        }

        $success = $connection->commit();

        if(!$success) return false;

        return array($new_order_id, $order_no, $total_fee);
    }

    /**
     * 添加保险订单
     * @param $info_id
     * @param $user_id
     * @param $total_fee
     * @return array|bool
     */
    public static function addInsuranceOrder($info_id, $user_id, $total_fee)
    {
        $add_order_sql = "insert into PayList (orderNo, orderName, money, userId, orderType, relId) values (:order_no, '保险保费', :total_fee, :user_id, 'insurance', :info_id)";

        $add_order_bind = array(
            'total_fee' => $total_fee,
            'user_id' => $user_id,
            'info_id' => $info_id
        );

        $connection = self::_getConnection();

        //先删除同一个info_id的已存在订单
        $del_order_sql = "delete from PayList where orderType = 'insurance' and relId = :info_id";
        $del_order_bind = array(
            'info_id' => $info_id
        );

        $connection->execute($del_order_sql, $del_order_bind);

        do
        {
            $order_no = self::_genOrderNo();
            $add_order_bind['order_no'] = $order_no;
            $add_order_success = $connection->execute($add_order_sql, $add_order_bind);
            $err_info = $connection->getInternalHandler()->errorInfo();
        }while($err_info[1] == '2627');

        if(!$add_order_success) return false;

        $new_order_id = $connection->lastInsertId();

        return array($new_order_id, $order_no, $total_fee);
    }

    /**
     * 添加挪车订单
     * @param array $move_car_data
     * @param $user_id
     * @param $total_fee
     * @param $ticket_id
     * @return int|bool
     * @throws DbTransException
     */
    public static function addMoveCarOrder(array $move_car_data, $user_id, $total_fee, $ticket_id=null)
    {
        $new_order_id = null;
        $actual_fee = $total_fee;
        $connection = self::_getConnection();
        $is_under_outer_transaction = $connection->isUnderTransaction();
        if(!$is_under_outer_transaction)
        {
            $connection->begin();
        }

        try
        {
            if(!empty($ticket_id))
            {
                //判断票券是否合法(是否存在, 是否属于用户, 是否能用在该应用, 是否符合使用条件, 是否过期)
                $ticket = Ticket::getTicketById($ticket_id);
                if(empty($ticket) or $ticket['user_id'] != $user_id or ($ticket['scope'] != 1 and $ticket['scope'] != 2) or $ticket['use_fee'] > $total_fee or strtotime($ticket['end_date']) < time() or $ticket['is_lock'] == 1)
                {
                    var_dump($user_id);
                    throw new DbTransException('非法票券');
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
                else
                {
                    $actual_fee = $total_fee;
                }

                //暂时锁定票券

                $update_ticket_data = array('unlock_time' => date('Y-m-d H:i:s', time() + 60));
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

            $sql = "insert into PayList (orderNo, orderName, money, userId, orderType, ticket_id, state) values (:order_no, '挪车业务', :total_fee, :user_id, 'move_car', :ticket_id, :state)";
            $bind = array(
                'total_fee' => $actual_fee,
                'user_id' => $user_id,
                'ticket_id' => $ticket_id,
                'state' => $actual_fee == 0 ? 'ORDER_FREE' : 'TRADE_WAIT'
            );

            do
            {
                $order_no = self::_genOrderNo();
                $bind['order_no'] = $order_no;
                $success = $connection->execute($sql, $bind);
                $err_info = $connection->getInternalHandler()->errorInfo();
            }while($err_info[1] == '2627');

            if(!$success)
            {
                throw new DbTransException('订单生成失败');
            }

            $new_order_id = $connection->lastInsertId();

            $add_move_car_sql = "insert into OrderToMoveCar (order_id, phone_bill, price, hphm, uphone, source) values (:order_id, :phone_bill, :price, :hphm, :uphone, :source)";
            $add_move_car_bind = array(
                'order_id' => $new_order_id,
                'phone_bill' => 5, //每个挪车订单有5元话费可用
                'price' => $total_fee, //挪车价格
                'hphm' => $move_car_data['hphm'],
                'uphone' => $move_car_data['uphone'],
                'source' => $move_car_data['source']
            );
            $add_move_car_success = self::nativeExecute($add_move_car_sql, $add_move_car_bind);
            if(!$add_move_car_success)
            {
                throw new DbTransException('订单生成失败');
            }

            if(!$is_under_outer_transaction)
            {
                $connection->commit();
            }

        }
        catch(DbTransException $e)
        {
            if(!$is_under_outer_transaction)
            {
                $connection->rollback();
                return false;
            }
            else
            {
                throw $e;
            }
        }


        return $new_order_id ? $new_order_id : false;
    }

    /**
     * 更新指定ID订单相关的挪车记录
     * @param $order_id
     * @param $data
     * @return bool
     */
    public static function updateRecord($order_id, $data)
    {
        $crt = new Criteria($data);
        $field_str = '';
        $bind = array('order_id' => $order_id);

        if($crt->last_call_time)
        {
            $field_str .= 'last_call_time = :last_call_time, ';
            $bind['last_call_time'] = $crt->last_call_time;
        }

        if(empty($field_str))
        {
           return false;
        }
        else
        {
            $field_str = rtrim($field_str, ', ');
        }

        $sql = "update OrderToMoveCar set $field_str where order_id = :order_id";
        return self::nativeExecute($sql, $bind);
    }

    /**
     * 获取指定ID订单信息
     * @param $order_id
     * @return array
     */
    public static function getOrderById($order_id)
    {
        $sql = "select id, orderNo as order_no, orderType as order_type, money as total_fee, relId as rel_id, ticket_id, convert(varchar(20), createTime, 20) as create_date,
    state,
    case
      when state = 'ORDER_FREE' or state = 'TRADE_SUCCESS' or state = 'TRADE_FINISHED' then
       1
      else
       0
    end as is_payed,
    convert(varchar(20), dateadd(hh, 2, createTime), 20) as expire_date,
    case
      when getdate() >= dateadd(hh, 2, createTime) then
       1
      else
       0
    end as is_expired
 from PayList where id = :order_id";
        $bind = array('order_id' => $order_id);
        $order = self::fetchOne($sql, $bind, null, Db::FETCH_ASSOC);

        $get_record_sql = "select hphm, price, phone_bill, uphone, convert(varchar(20),last_call_time, 20) as last_call_time from OrderToMoveCar where order_id = :order_id";
        $get_record_bind = array('order_id' => $order['id']);
        $record = self::fetchOne($get_record_sql, $get_record_bind, null, Db::FETCH_ASSOC);
        $order['record'] = $record;

        if($order['ticket_id'])
        {
            //获取订单所使用的票券信息
            $ticket = Ticket::getTicketById($order['ticket_id']);
            $order['ticket'] = $ticket;
        }

        return $order;
    }

    /**
     * 订单是否已申诉过
     * @param int|string $order_id
     * @return bool
     */
    public static function isAppealed($order_id)
    {
        $sql = "select top 1 id from MC_Appeal where order_id = :order_id";
        $bind = array('order_id' => $order_id);

        return (bool) self::fetchOne($sql, $bind);
    }

    /**
     * 订单申诉
     * @param $order_id
     * @param $data
     * @return bool
     */
    public static function appealOrderById($order_id, $data)
    {
        $crt = new Criteria($data);
        $sql = <<<SQL
        insert into MC_Appeal (order_id, problem, addition, advise) values (:order_id, :problem, :addition, :advise)
SQL;
        $bind = array(
            'order_id' => $order_id,
            'problem' => $crt->problem,
            'addition' => $crt->addition,
            'advise' => $crt->advise
        );

        return self::nativeExecute($sql, $bind);
    }

    /**
     * 订单是否已反馈过
     * @param $order_id
     * @return bool
     */
    public static function isFeedBacked($order_id)
    {
        $sql = "select feedback_date from OrderTOMoveCar where feedback_date is not null and order_id = :order_id";
        $bind = array(
            'order_id' => $order_id
        );
        $result = self::fetchOne($sql, $bind, null, Db::FETCH_NUM);
        return (bool)$result;
    }

    /**
     * 订单反馈
     * @param $order_id
     * @param array $data
     * @return bool
     * @throws DbTransException
     */
    public static function feedBackOrderById($order_id, array $data)
    {
        $crt = new Criteria($data);
        $connection = self::_getConnection();
        $is_under_outer_transaction = $connection->isUnderTransaction();
        $success = false;
        try
        {
            if(!$is_under_outer_transaction)
            {
                $connection->begin();
            }

            $add_feedback_sql = <<<SQL
              insert into MC_Feedback (order_id, q1_1, q1_2, q1_3, q1_4, q1_5, q1_6, q1_7, q2, advise)
              values (:order_id, :q1_1, :q1_2, :q1_3, :q1_4, :q1_5, :q1_6, :q1_7, :q2, :advise)
SQL;
            $add_feedback_bind = array(
                'order_id' => $order_id,
                'q1_1' => $crt->q1_1,
                'q1_2' => $crt->q1_2,
                'q1_3' => $crt->q1_3,
                'q1_4' => $crt->q1_4,
                'q1_5' => $crt->q1_5,
                'q1_6' => $crt->q1_6,
                'q1_7' => $crt->q1_7,
                'q2' => $crt->q2,
                'advise' => $crt->advise
            );

            $add_feedback_success = self::nativeExecute($add_feedback_sql, $add_feedback_bind);
            if(!$add_feedback_success)
            {
                throw new DbTransException();
            }

            $update_record_sql = "update OrderToMoveCar set feedback_date = :feedback_date where order_id = :order_id";
            $update_record_bind = array(
                'order_id' => $order_id,
                'feedback_date' => date('Y-m-d H:i:s')
            );
            $update_record_success = self::nativeExecute($update_record_sql, $update_record_bind);
            if(!$update_record_success)
            {
                throw new DbTransException();
            }

            $success = $connection->commit();
        }
        catch(DbTransException $e)
        {
            if(!$is_under_outer_transaction)
            {
                $connection->rollback();
                $success = false;
            }
            else
            {
                throw $e;
            }
        }

        return $success;
    }

    /**
     * 获取用户订单(挪车服务)列表
     * @param $user_id
     * @param string $order_by
     * @param null $start
     * @param int $length
     * @return array
     */
    public static function getOrderList($user_id, $order_by=null, $start=null, $length=10)
    {
        $condition_str = 'where o.user_id = :user_id ';
        $bind = array(
            'user_id' => $user_id
        );

        $order_by_str = '';

        if(!empty($order_by))
        {
            $order_by_arr = explode(' ', $order_by);
            $order_field = $order_by_arr[0];
            $order_method = count($order_by_arr) == 2 ? $order_by_arr[1] : 'asc';
            $compare_opt = $order_method == 'asc' ? '>' : '<';

            if(!empty($start))
            {
                $condition_str .= "and o.$order_field $compare_opt :start ";
                $bind['start'] = $start;
            }
            $order_by_str = 'order by o.'.$order_by;
        }

        $length_str = '';
        if(!empty($length))
        {
            $length_str = 'top '.$length;
        }

        $sql = <<<SQL
        select $length_str
        o.id,
        o.order_no,
        o.total_fee,
        o.create_date,
        convert(varchar(20), dateadd(hh, 2, o.create_date), 20) as expire_date,
        case
          when getdate() >= dateadd(hh, 2, o.create_date) then
           1
          else
           0
        end as is_expired,
        o2mc.hphm,
        o2mc.uphone,
        case
          when o2mc.feedback_date is not null then
            1
          else
            0
        end as is_feedbacked
        from (
          select id, userId as user_id, orderNo as order_no, money as total_fee, convert(varchar(20), createTime, 20) as create_date from PayList where orderType = 'move_car' and (state = 'ORDER_FREE' or state = 'TRADE_SUCCESS' or state = 'TRADE_FINISHED')
        ) o
        left join OrderToMoveCar o2mc on o2mc.order_id = o.id
        $condition_str
        $order_by_str
SQL;
        $order_list = self::nativeQuery($sql, $bind);
        if(!empty($order_list))
        {
            foreach($order_list as &$order)
            {
                $record = array();
                $record['hphm'] = $order['hphm'];
                unset($order['hphm']);
                $record['uphone'] = $order['uphone'];
                unset($order['uphone']);
                $order['record'] = $record;
            }
        }

        return $order_list;
    }

    /**
     * 添加订单轨迹
     * @param $order_id
     * @param $action
     * @param $title
     * @param $result
     * @return bool
     */
    public static function addTrack($order_id, $action, $title, $result)
    {
        $sql = "insert into MC_OrderTrack (order_id, action, title, result) values (:order_id, :action, :title, :result)";
        $bind = array(
            'order_id' => $order_id,
            'action' => $action,
            'title' => $title,
            'result' => $result
        );
        return self::nativeExecute($sql, $bind);
    }

    /**
     * 获取订单轨迹列表
     * @param int|string $order_id
     * @return array
     */
    public static function getTrackList($order_id)
    {
        $sql = <<<SQL
        select action, title, result, convert(varchar(20), create_date, 20) as create_date from MC_OrderTrack where order_id = :order_id
        order by create_date asc
SQL;
        $bind = array(
            'order_id' => $order_id
        );
        return self::nativeQuery($sql, $bind);
    }

    /**
     * @return string
     */
    protected static function _genOrderNo()
    {
        //设置随机种子
        mt_srand(microtime(true) * 1000);
        return date('YmdHis').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }
}