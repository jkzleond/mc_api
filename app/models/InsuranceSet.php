<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 15-6-22
 * Time: 下午3:20
 */

use \Phalcon\Db;

class InsuranceSet extends ModelEx
{
    /**
     * 获取保险套餐列表
     * @return array
     */
    public static function getList()
    {
        $sql = 'select id, name, optionsList as options_list from Insurance_Set';
        return self::nativeQuery($sql, null, null, Db::FETCH_OBJ);
    }
}