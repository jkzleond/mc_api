<?php

use \Phalcon\Db;

class MoveCar extends ModelEx
{

    /**
     * 弃用
     * @param array $data
     * @return bool|int
     */
    public static function addRecord(array $data)
    {
        $crt = new Criteria($data);
        $sql = 'insert into MC_RequestRecord (userid, wx_openid, source, hphm) values (:user_id, :openid, :source, :hphm)';
        $bind = array(
            'user_id' => $crt->user_id,
            'openid' => $crt->openid,
            'source' => $crt->source,
            'hphm' => $crt->hphm
        );

        $success = self::nativeExecute($sql, $bind);
        $connection = self::_getConnection();
        return $success ? $connection->lastInsertId() : false;
    }

    public static function getCarOwnerPhone($hphm)
    {
        $sql = 'select phone from JGCarOwner where hphm = :hphm';
        $bind = array('hphm' => $hphm);
        $car_owner = self::fetchOne($sql, $bind, null, Db::FETCH_ASSOC);
        return $car_owner['phone'];
    }

    /**
     * 获取车主列表
     * @param $hphm
     * @return array | null
     */
    public static function getCarOwnerList($hphm)
    {
        $get_mc_car_sql = "
          select
            c.id,
            case
              when c.success_count > 0 or c.fail_count > 0 then
                c.success_count / (c.success_count + c.fail_count) * 10
              else
                5
            end as success_rate,
            isnull(mu.phone, u.phone) as phone,
            'cm' as source
          from MC_Car c
          left join IAM_USER u on u.userid = c.user_id
          left join MC_User mu on mu.user_id = c.user_id
          where c.hphm = :hphm and c.state = 1
          order by success_rate desc
";
        $get_mc_car_bind = array('hphm' => $hphm);

        $mc_car_list = self::nativeQuery($get_mc_car_sql, $get_mc_car_bind);

        if(!empty($mc_car_list))
        {
            return $mc_car_list;
        }

        $get_jg_car_sql = "select id, phone, 'jg' as source from JGCarOwner where hphm = :hphm";
        $get_jg_car_bind = array('hphm' => $hphm);
        $jg_car_list = self::nativeQuery($get_jg_car_sql, $get_jg_car_bind);
        return $jg_car_list;
    }

    /**
     * 获取指定ID的车主信息
     * @param $id
     * @param string $source 数据来源 'cm' or 'jg' default: 'cm'
     * @return array
     */
    public static function getCarOwnerById($id, $source='cm')
    {
        $bind = array('id' => $id);
        if($source == 'cm')
        {
            $sql = <<<SQL
            select
              c.id,
              c.hphm,
              isnull(mu.phone, u.phone) as phone
            from MC_Car c
            left join IAM_USER u on u.userid = c.user_id
            left join MC_User mu on mu.user_id = c.user_id
            where c.id = :id
SQL;

        }
        else
        {
            $sql = "select id, hphm, phone from JGCarOwner where id = :id";
        }

        return self::fetchOne($sql, $bind, null, Db::FETCH_ASSOC);
    }

    /**
     * 标记车主(成功率)
     * @param int $id
     * @param bool $is_success
     * @param string $source
     * @return bool
     */
    public static function markCarOwnerById($id, $is_success, $source='cm')
    {
        $bind = array('id' => $id);
        $field_str = null;
        if($is_success)
        {
            $field_str = 'success_count += 1';
        }
        else
        {
            $field_str = 'fail_count += 1';
        }

        $table_name = 'MC_Car';

        if($source != 'cm')
        {
            $table_name = 'JGCarOwner';
        }

        $sql = <<<SQL
            update $table_name set $field_str where id = :id
SQL;
        return self::nativeExecute($sql, $bind);
    }
}
