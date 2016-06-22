<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 15-3-19
 * Time: 下午4:07
 */

use \Phalcon\DI;
use \Phalcon\Db;

class ModelEx extends \Phalcon\Mvc\Model
{

    protected static $_table_name = '';

    /**
     * 查询记录
     * @param null $parameters
     * @throws \Phalcon\Exception
     * @return array
     */
    public static function find($parameters=null)
    {
        $result = null;
        $connection = self::_getConnection();
        if (empty($parameters))
        {
            $sql = 'SELECT * FROM '.self::_getTableName();
            $result = $connection->fetchAll($sql, DB::FETCH_ASSOC);
        }
        elseif (is_string($parameters))
        {
            $sql_wgo = self::_buildWGO(array($parameters));
            $sql = 'SELECT * FROM '.self::_getTableName().$sql_wgo;
            $result = $connection->fetchAll($sql, DB::FETCH_ASSOC);
        }
        elseif (is_array($parameters))
        {

            $sql = 'SELECT ';
            $columns_str = isset($parameters['columns']) ? $parameters['columns'] : '*';
            $limit = isset($parameters['limit']) ? $parameters['limit'] : null;
            $bind = isset($parameters['bind'])? $parameters['bind'] : null;
            $sql = $sql.$columns_str.' FROM '.self::_getTableName();
            $sql = $sql.self::_buildWGO($parameters);
            $sql = $limit ? $connection->limit($sql, $limit) : $sql;
            $result = $connection->fetchAll($sql, DB::FETCH_ASSOC, $bind);
        }
        else
        {
            throw new \Phalcon\Exception('Model Query Parameter Exception');
        }
        return $result;
    }

    /**
     * find the first row
     * @param mixed $parameters
     * @return array
     */
    public static function findFirst($parameters=null)
    {

        if(empty($parameters) || is_string($parameters))
        {
            return self::find(array($parameters, 'limit' => 1));
        }

        if(is_array($parameters))
        {
            $parameters['limit'] = 1;
            return self::find($parameters);
        }

    }


    /**
     * build wgo sql
     * @param mixed $parameters
     * @return string
     */
    private static function _buildWGO($parameters)
    {
        $condition = isset($parameters[0]) && is_string($parameters[0]) ? $parameters[0]:'';
        $group_by = isset($parameters['group_by'])?$parameters['columns']:null;
        $order_by = isset($parameters['columns'])?$parameters['order']:null;
        $sql_part = '';
        if($condition)
        {
            $sql_part .= ' WHERE '.$condition;
        }
        if($group_by)
        {
            $sql_part .= ' GROUP BY '.$group_by;
        }
        if($order_by)
        {
            $sql_part .= ' ORDER BY '.$order_by;
        }

        return $sql_part;
    }


    /**
     * native sql query
     * @param  string $sql
     * @param  array $bindParameters
     * @param  array $bindTypes
     * @param  int $fetch_mode
     * @return array
     */
    public static function nativeQuery($sql, $bindParameters=null, $bindTypes=null, $fetch_mode=Db::FETCH_ASSOC)
    {
        $connection = self::_getConnection();
        $result = $connection->query($sql, $bindParameters, $bindTypes);
        $result->setFetchMode($fetch_mode);
        return $result->fetchAll();
    }

    /**
     * native sql execute
     * @param $sql
     * @param null $bindParameters
     * @param null $bindTypes
     * @return bool
     */
    public static function nativeExecute($sql, $bindParameters=null, $bindTypes=null)
    {
        $connection = self::_getConnection();
        return $connection->execute($sql, $bindParameters, $bindTypes);
    }

    /**
     * fetch one object using native sql query
     * @param $sql
     * @param null $bindParameters
     * @param null $bindTypes
     * @param int $fetch_mode
     * @return object
     */
    public static function fetchOne($sql, $bindParameters=null, $bindTypes=null, $fetch_mode = Db::FETCH_OBJ)
    {
        $connection = self::_getConnection();
        $result = $connection->query($sql, $bindParameters, $bindTypes);
        $result->setFetchMode($fetch_mode);
        return $result->fetch();
    }

    /**
     * get db adapter connection
     * @return \Phalcon\Db\Adapter\Pdo
     */
    protected static function _getConnection()
    {
        $di = DI::getDefault();
        return $di->getShared('db');
    }

    private static function _getTableName()
    {
        if(!static::$_table_name)
        {
            return strtolower(get_called_class());
        }
        else
        {
            return static::$_table_name;
        }
    }

}