<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 16-7-5
 * Time: 下午11:27
 */

namespace Palm\Utils;


class DateTime
{
    /**
     * 由一种格式的时间转换到另一种格式的时间
     * @param $date_str
     * @param $source_format
     * @param $target_format
     * @return string
     */
    public static function ft2ft($date_str, $source_format, $target_format)
    {
        $date_parts = strptime($date_str, $source_format);
        return strftime($target_format, mktime($date_parts['tm_hour'], $date_parts['tm_min'], $date_parts['tm_sec'], $date_parts['tm_mon'] + 1, $date_parts['tm_mday'], $date_parts['tm_year'] + 1900));
    }
}