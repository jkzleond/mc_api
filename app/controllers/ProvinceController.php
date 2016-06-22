<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 15-6-19
 * Time: 下午3:47
 */

class ProvinceController extends ControllerBase
{
    /**
     * 获取省份列表
     */
    public function getProvinceListAction()
    {
        $province_list = Province::getProvinceList();

        $this->view->setVars(array(
            'rows' => $province_list
        ));
    }

    /**
     * 获取指定省份的城市列表
     * @param $province_id
     */
    public function getProvinceCitiseAction($province_id)
    {
        $city_list = Province::getCityListByPid($province_id);

        $this->view->setVars(array(
            'rows' => $city_list
        ));
    }
}