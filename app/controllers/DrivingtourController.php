<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 15-5-29
 * Time: 下午2:57
 */

class DrivingTourController extends ControllerBase
{

    /**
     * 自驾游列表页面
     */
    public function listAction()
    {

    }

    /**
     * 获取自驾游列表
     */
    public function getListAction()
    {
        $page_num = $this->request->get('page');
        $page_size = $this->request->get('rows');

        $user = User::getCurrentUser();

        $province_id = isset($user['province_id']) ? $user['province_id'] : null;

        $driving_tour_list = Activity::getDrivingTourList(null, $page_num, $page_size);
        $driving_tour_total = Activity::getDrivingTourCount();

        $this->view->setVars(array(
            'total' => $driving_tour_total,
            'rows' => $driving_tour_list
        ));
    }

    /**
     * 获取自驾游详情
     * @param $id
     */
    public function getDetailAction($id)
    {
        $tour = Activity::getDrivingTourDetailById($id);
        $user = User::getCurrentUser();
        $is_user_join = Activity::isUserJoin($user['user_id'], $id);

        $tour['is_user_join'] = $is_user_join;
        $this->view->setVars(array(
            'row' => $tour
        ));
    }
}