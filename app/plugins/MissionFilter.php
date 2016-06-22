<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 15-6-4
 * Time: 下午12:33
 */

use \Phalcon\Mvc\User\Plugin;

class MissionFilter extends Plugin
{

    public function beforeExecuteRoute()
    {
        //重置日常任务
        Mission::resetDailyMissionState();
        //领取自动任务
        $user = $this->session->get('user');
        if(isset($user['user_id']) and $user['nickname'] != '游客')
        {
            Mission::assignAutoAssignMission($user['user_id']);
        }
    }

    public function afterExecuteRoute()
    {
        //判断任务条件设置非自动交付任务的满足字段,并且处理自动交付任务(奖励)
        $user = $this->session->get('user');
        if(isset($user['user_id']) and $user['nickname'] != '游客')
        {
            $user_mission_state = Mission::getFullProgressInMissionState($user['user_id']);

            foreach($user_mission_state as $state)
            {
                $satisfy = true;
                foreach($state['objectives'] as $objective)
                {
                    $type_name = ucfirst(strtolower($objective['type']));
                    $target_name = ucfirst(strtolower($objective['target']));
                    $interpreter_objective_method = 'interpreter'.$type_name.$target_name.'Objective';
                    $satisfy_part = false;

                    if(method_exists($this, $interpreter_objective_method))
                    {
                        $obj_value = $objective['objective'];
                        $cur_value = $objective['value'];
                        $comparision = $objective['comparision'];
                        $satisfy_part = call_user_func_array(array($this, $interpreter_objective_method), array($cur_value, $obj_value, $comparision));

                    }

                    $satisfy = $satisfy && $satisfy_part;
                    if(!$satisfy) break;
                }

                //如果所有条件都满足,则做进一步处理
                if($satisfy)
                {
                    $state_id = $state['id'];
                    if(!$state['is_auto_deliv'])
                    {
                        Mission::updateMissionState($state_id, array('is_satisfy' => true));
                    }
                    else
                    {
                        $bonuses = $state['bonuses'];
                        foreach($bonuses as $bonus)
                        {
                            $type_name = ucfirst(strtolower($bonus['type']));
                            $target_name = ucfirst(strtolower($bonus['target']));
                            $interpreter_bonus_method = 'interpreter'.$type_name.$target_name.'Bonus';
                            if(method_exists($this, $interpreter_bonus_method))
                            {
                                $value = $bonus['value'];
                                $msg_bonus = call_user_func_array(array($this, $interpreter_bonus_method), array($value));
                                if(!$msg_bonus) continue;

                                $events_to_client = $this->view->getVar('events');
                                if(is_array($events_to_client))
                                {
                                    $events_to_client['system.bonus'][] = $msg_bonus;
                                }
                                else
                                {
                                    $events_to_client = array( 'system.bonus' => array($msg_bonus) );
                                }
                                $this->view->setVar('events', $events_to_client);
                            }
                        }
                        //是否循环任务, 是则重置任务状态(删除),不是则标记为完成
                        if($state['is_loop'])
                        {

                            Mission::delMissionState($state_id);
                        }
                        else
                        {
                            Mission::updateMissionState($state_id, array('is_complete' => true));
                        }
                    }
                }
            }
        }
    }

    /**
     * 解释用户属性.签到次数任务目标
     * @param $cur_value
     * @param $obj_value
     * @param $comparision
     * @return bool
     */
    protected function interpreterUserpropSigntimesObjective($cur_value, $obj_value, $comparision)
    {
        $user = $this->session->get('user');

        $clock_in = ClockIn::getClockIn($user['user_id']);

        $sign_times = $clock_in['times'];

        return $comparision == $this->_compare($sign_times, $obj_value);
    }

    protected function interpreterHuiGoldBonus($value)
    {
        $user = User::getCurrentUser();
        $success = User::addHuiGold($user['user_id'], $value);

        if(!$success) return false;

        //成功则返回消息提示文本
        return array('name' => '惠金币', 'code' => 'huiGold', 'value' => $value);
    }

    protected function _compare($cur_value, $obj_value)
    {
        if($cur_value == $obj_value)
        {
            return 0;
        }
        elseif($cur_value <= $obj_value)
        {
            return 1;
        }
        elseif($cur_value >= $obj_value)
        {
            return 2;
        }
        elseif($cur_value < $obj_value)
        {
            return 3;
        }
        elseif($cur_value > $obj_value)
        {
            return 4;
        }
    }
}