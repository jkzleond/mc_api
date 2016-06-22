<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 15-6-3
 * Time: 下午11:43
 */

use \Phalcon\Db;

class ClockIn extends ModelEx
{
    /**
     * 添加签到记录
     * @param $user_id
     * @return bool
     */
    public static function addClockIn($user_id)
    {
        $sql = 'insert into Hui_ClockIn (userId, times) values (:user_id, 1)';
        $bind = array('user_id' => $user_id);
        return self::nativeExecute($sql, $bind);
    }

    /**
     * 增加签到次数
     * @param $user_id
     * @return bool
     */
    public static function updateClockIn($user_id)
    {

        $sql = 'update HUI_ClockIn set times = (times % 7) + 1, lastClockInDate = getdate() where userId = :user_id and datediff(d, lastClockInDate, getdate()) >= 1';
        $bind = array('user_id' => $user_id);
        self::nativeExecute($sql, $bind);
        $connection = self::_getConnection();

        return $connection->affectedRows();
    }

    /**
     * 获取用户的签到信息
     * @param $user_id
     * @return array
     */
    public static function getClockIn($user_id)
    {
        $sql = 'select userId, times, lastClockInDate from Hui_ClockIn where userId = :user_id';
        $bind = array('user_id' => $user_id);
        return self::fetchOne($sql, $bind, null, Db::FETCH_ASSOC);
    }
}