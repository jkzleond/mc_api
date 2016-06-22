<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 15-5-4
 * Time: 下午9:50
 */


/**
 * Class Criteria
 * 该类不属于数据操作的模型类，仅用于描述查询条件及写入数据，较之array则避免了使用array元素之前进行的isset判断
 * 见__get, __set 方法
 */
class Criteria
{
    protected $_collection;

    public function __construct(array $collection=null)
    {
        $this->_collection = (array)$collection;
    }

    public function __get($prop)
    {
        if(!isset($this->_collection[$prop])) return null;
        return $this->_collection[$prop];
    }

    public function __set($prop, $value)
    {
        $this->_collection[$prop] = $value;
    }
}