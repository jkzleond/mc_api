<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 15-6-3
 * Time: 下午4:53
 */

class ClockinController extends ControllerBase
{
    /**
     * 签到
     * @param null $user_id
     */
    public function doClockInAction($user_id=null)
    {

        if(!$user_id)
        {
            $user = User::getCurrentUser();
            $user_id = $user['user_id'];
        }

        $clock_in = ClockIn::getClockIn($user_id);


        $success = false;

        if(empty($clock_in))
        {
            $success =  ClockIn::addClockIn($user_id);
        }
        else
        {

            $success = ClockIn::updateClockIn($user_id);
        }

        $this->view->setVars(array(
            'success' => $success
        ));

        $this->eventsManager->fire('userAction:checkInSuccess', $this);
    }

    /**
     * 获取签到信息
     * @param null $user_id
     */
    public function getClockInInfoAction($user_id=null)
    {
        $user = User::getCurrentUser();
        $clock_in = ClockIn::getClockIn($user['user_id']);

        $this->view->setVars(array(
            'row' => $clock_in
        ));
    }
}