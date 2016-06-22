<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 15-7-5
 * Time: 下午5:27
 */

class Goods extends ModelEx
{
    /**
     * 获取指定id的商品
     * @param array $ids
     * @return array
     */
    public static function getGoodsByIds(array $ids)
    {
        $ids_str = implode(', ', $ids);
        $sql = "select id, name, des, price, isShelf as is_shelf, createDate as create_date, cat_id, latestUpdate as latest_update, type_id from Hui_Goods where id in ($ids_str)";
        return self::nativeQuery($sql);
    }
}