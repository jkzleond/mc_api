<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 15-4-5
 * Time: 下午6:51
 */

use Phalcon\Db;

class Province extends ModelEx {

    /**
     * 获取省列表
     * @return array
     */
    public static function getProvinceList()
    {
        $sql = 'select id, [name] from Province';
        return self::nativeQuery($sql, null, null, Db::FETCH_OBJ);
    }

    /**
     * 获取城市列表
     * @param $pid
     * @return array
     */
    public static function getCityListByPid($pid)
    {
        $sql = 'select id, [name] from City where pid = :pid';
        return self::nativeQuery($sql, array('pid' => $pid), null, Db::FETCH_OBJ);
    }

    /**
     * 获取省信息
     * @param $id
     * @return array
     */
    public static function getProvinceById($id)
    {
        $sql = 'select id,name from Province where id = :id';
        return self::nativeQuery($sql, array('id' => $id));
    }

    /**
     * 获取城市信息
     * @param $id
     * @return array
     */
    public static function getCityById($id)
    {
        $sql = 'select id,name,pid,fzjg from City where id = :id';
        return self::nativeQuery($sql, array('id' => $id));
    }



}