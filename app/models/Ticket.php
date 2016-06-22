<?php

/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 16-6-7
 * Time: 上午9:33
 */

use \Phalcon\Db;

class Ticket extends ModelEx
{
    /**
     * 获取指定ID票券信息
     * @param string|int $id
     * @return array
     */
    public static function getTicketById($id)
    {
        $sql = <<<SQL
        select id, user_id, type, title,
          case type
            when 1 then
              '红包'
            when 2 then
              '优惠券'
            when 3 then
              '通话时长卡'
            when 4 then
              '优惠券'
            else
              '红包'
          end as type_text,
          scope, value, end_date, use_fee,
          case
            when unlock_time > getdate() then
              1
            else
              0
          end is_lock
          from Ticket where id = :id
SQL;
        $bind = array('id' => $id);
        return self::fetchOne($sql, $bind, null, Db::FETCH_ASSOC);
    }

    /**
     * 更新票券
     * @param $id
     * @param array $data
     * @return bool
     */
    public static function updateTicketById($id, array $data)
    {
        $crt = new Criteria($data);
        $field_str = '';
        $bind = array('id' => $id);

        if($crt->unlock_time)
        {
            $field_str .= 'unlock_time = :unlock_time, ';
            $bind['unlock_time'] = $crt->unlock_time;
        }

        if($crt->state)
        {
            $field_str .= 'state = :state, ';
            $bind['state'] = $crt->state;
        }

        if(!empty($field_str))
        {
            $field_str = rtrim($field_str, ', ');
        }

        else
        {
            return false;
        }

        $sql = "update Ticket set $field_str where id = :id";
        return self::nativeExecute($sql, $bind);
    }
}