<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 15-6-24
 * Time: 下午11:46
 */

use \Phalcon\Db;

class CarInfo extends ModelEx
{
    /**
     * 获取指定用户名与号牌号码的车辆信息
     * @param $user_id
     * @param $hphm
     * @return object
     */
    public static function getCarInfoByUserIdAndHphm($user_id, $hphm)
    {
        $sql = <<<SQL
        select id, userId, hphm, engineNumber as engine_number, frameNumber as frame_number, autoname as auto_name from CarInfo where userId = :user_id and hphm = :hphm
SQL;
        $bind = array(
            'user_id' => $user_id,
            'hphm' => $hphm
        );

        return self::fetchOne($sql, $bind, null, Db::FETCH_ASSOC);
    }

    /**
     * 添加车辆信息
     * @param array $car_info
     * @return bool|int
     */
    public static function addCarInfo(array $car_info)
    {
        $crt = new Criteria($car_info);
        $sql = <<<SQL
        insert into CarInfo (
        userId, hpzl, hphm, engineNumber, frameNumber, province_id, city_id, noHphm, autoname
        ) values (
        :user_id, :hpzl, :hphm, :engine_number, :frame_number, :province_id, :city_id, :no_hphm, :auto_name
        )
SQL;
        $bind = array(
            'user_id' => $crt->user_id,
            'hpzl' => $crt->hpzl,
            'hphm' => $crt->hphm,
            'engine_number' => $crt->engine_number,
            'frame_number' => $crt->frame_number,
            'province_id' => $crt->province_id,
            'city_id' => $crt->city_id,
            'no_hphm' => empty($crt->no_hphm) ? 0 : 1,
            'auto_name' => $crt->auto_name
        );

        $success = self::nativeExecute($sql, $bind);

        if(!$success) return false;

        $connection = self::_getConnection();

        return $connection->lastInsertId();
    }

    /**
     * 更新车辆信息
     * @param $id
     * @param array $car_info
     * @return bool
     */
    public static function updateCarInfo($id, array $car_info)
    {
        $crt = new Criteria($car_info);
        $field_str = '';
        $bind = array('id' => $id);

        if($crt->engine_number)
        {
            $field_str .= 'engineNumber = :engine_number, ';
            $bind['engine_number'] = $crt->engine_number;
        }

        if($crt->frame_number)
        {
            $field_str .= 'frameNumber = :frame_number, ';
            $bind['frame_number'] = $crt->frame_number;
        }

        if($crt->auto_name)
        {
            $field_str .= 'autoname = :auto_name, ';
            $bind['auto_name'] = $crt->auto_name;
        }

        $field_str = rtrim($field_str, ', ');

        $sql = <<<SQL
        update CarInfo set $field_str where id = :id
SQL;

         return self::nativeExecute($sql, $bind);
    }
}