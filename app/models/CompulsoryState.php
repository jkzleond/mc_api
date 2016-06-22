<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 15-6-22
 * Time: 下午3:27
 */

use \Phalcon\Db;

class CompulsoryState extends ModelEx
{
    /**
     * 获取交强险列表
     * @return array
     */
    public static function getList()
    {
        $sql = 'select id, status from Inrurance_Compulsory'; //数据库中表名拼写错误,应是Insurance_Compulsory
        return self::nativeQuery($sql, null, null, Db::FETCH_OBJ);
    }
}