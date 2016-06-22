<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 15-6-4
 * Time: 下午12:53
 */

use \Phalcon\Db;

class Mission extends ModelEx
{
    /**
     * 获取可接任务
     * @param $user_id
     * @return array
     */
    public static function getAvailableMissionList($user_id, $page_num=null, $page_size=null)
    {

        $page_condition_str = '';
        $bind = array('user_id' => $user_id);

        if($page_num)
        {
            $page_condition_str = 'where rownum between :from and :to';
            $bind['from'] = $page_size * ($page_num - 1) + 1;
            $bind['to'] = $page_size * $page_num;
        }

        $sql = <<<SQL
        select id, title, des, rownum from (
          select id, title, des, row_number() over (order by id desc) as rownum from Hui_Mission
          where id not in (
            select mssId from Hui_MissionState where userId = :user_id
          )
        ) m
        $page_condition_str
SQL;
        return self::nativeQuery($sql, $bind);
    }

    /**
     * 获取可接任务总数
     * @param $user_id
     * @return mixed
     */
    public static function getAvailableMissionCount($user_id)
    {
        $sql = <<<SQL
        select count(id) from Hui_Mission
        where id not in (
          select mssId from Hui_MissionState where userId = :user_id
        )
SQL;
        $bind = array('user_id' => $user_id);

        $result = self::fetchOne($sql, $bind, null, Db::FETCH_NUM);

        return $result[0];
    }

    /**
     * 获取进行中任务
     * @param $user_id
     * @param null $page_num
     * @param null $page_size
     * @return array
     */
    public static function getInProgressMissionList($user_id, $page_num=null, $page_size=null)
    {
        $page_condition_str = '';
        $bind = array('user_id' => $user_id);

        if($page_num)
        {
            $page_condition_str = 'where rownum between :from and :to';
            $bind['from'] = $page_size * ($page_num - 1) + 1;
            $bind['to'] = $page_size * $page_num;
        }

        $sql = <<<SQL
        select id, title, des, isStatisfy, rownum from (
          select m.id, m.title, m.des, s.isSatisfy row_number() over (order by id desc) as rownum from Hui_Mission m
          left join Hui_MissionState s on s.mssId = m.id
          where s.userId = :user_id and isComplete = 0
        ) m
        $page_condition_str
        order by isSatisfy desc
SQL;
        return self::nativeQuery($sql, $bind);
    }

    /**
     * 获取进行中任务总数
     * @param $user_id
     * @return mixed
     */
    public static function getInProgressMissionCount($user_id)
    {

        $sql = <<<SQL
        select count(id) from Hui_MissionState where isComplete != 0 and userId = :user_id
SQL;
        $bind = array('user_id' => $user_id);

        $result = self::fetchOne($sql, $bind, null, Db::FETCH_NUM);

        return $result[0];
    }

    /**
     * 获取已完成任务列表
     * @param $user_id
     * @param null $page_num
     * @param null $page_size
     */
    public static function getCompletedMissionList($user_id, $page_num=null, $page_size=null)
    {
        $page_condition_str = '';
        $bind = array('user_id' => $user_id);

        if($page_num)
        {
            $page_condition_str = 'where rownum between :from and :to';
            $bind['from'] = $page_size * ($page_num - 1) + 1;
            $bind['to'] = $page_size * $page_num;
        }

        $sql = <<<SQL
        select id, title, des, rownum from (
          select m.id, m.title, m.des, row_number() over (order by id desc) as rownum from Hui_Mission m
          left join Hui_MissionState s on s.mssId = m.id
          where s.userId = :user_id and isComplete = 1
        ) m
        $page_condition_str
        order by isSatisfy desc
SQL;
    }

    /**
     * 获取已完成任务总数
     * @param $user_id
     * @return mixed
     */
    public static function getCompletedMissionCount($user_id)
    {
        $sql = <<<SQL
        select count(id) from Hui_MissionState where isComplete = 1 and userId = :user_id
SQL;
        $bind = array('user_id' => $user_id);

        $result = self::fetchOne($sql, $bind, null, Db::FETCH_NUM);

        return $result[0];
    }


    /**
     * 获取用户可用的自动领取任务
     * @param $user_id
     * @return array
     */
    public static function getAvailableAutoMissionList($user_id)
    {
        $sql = <<<SQL
        select m.id, m.title, m.des from Hui_Mission m
        left join (
          select mssId where userId = :user_id
        ) s
        on s.mssId = m.id
        where m.isAutoAss = 1 and ISNULL(s.mssId, 0) = 0
SQL;
        $bind = array('user_id' => $user_id);

        return self::nativeQuery($sql, $bind);
    }

    /**
     * 获取任务状态
     * @param $user_id
     * @return array
     */
    public static function getUserMissionStateList($user_id)
    {
        $sql = <<<SQL
        select id, userId, mssId, isComplete, assignDate, isComplete
        where userId = :user_id
SQL;
        $bind = array('user_id' => $user_id);

        return self::nativeQuery($sql, $bind);
    }

    /**
     * 获取用户进行中(未完成,未满足)任务状态的完全信息(包括任务,任务目标,与任务奖励的信息)
     * @param $user_id
     * @return array
     */
    public static function getFullProgressInMissionState($user_id)
    {
        $sql = <<<SQL
        select s.id, os.id o_id, os.value o_value, o.type o_type, o.hook o_hook, o.comparision o_comparision, o.objective o_objective, b.id b_id, b.type b_type, b.target b_target, b.value b_value, m.isAutoDeliv, m.isLoop from Hui_MissionState s
        left join Hui_MissionObjectiveState os on os.mstateId = s.id
        left join Hui_MissionObjective o on o.id = os.objId
        left join Hui_MissionBonus b on b.mssId = s.mssId
        left join Hui_Mission m on m.id = s.mssId
        where s.userId = :user_id and s.isComplete != 1 and s.isSatisfy != 1
        order by s.id desc
SQL;
        $bind = array('user_id' =>$user_id);

        $states =  self::nativeQuery($sql, $bind);

        $filtered_states = array();
        $cur_id = null;
        $cur_state = null;
        $cur_obj_id = null;
        $cur_bonus_id = null;

        foreach($states as $state)
        {
            $state_id = $state['id'];
            $obj_id = $state['o_id'];
            $bonus_id = $state['b_id'];


            if($state_id == $cur_id)
            {
                if($obj_id != $cur_obj_id)
                {
                    $cur_state['objectives'][] = array(
                        'type' => $state['o_type'],
                        'target' => $state['o_hook'],
                        'objective' => $state['o_objective'],
                        'comparision' => $state['o_comparision'],
                        'value' => $state['o_value']
                    );
                    $cur_obj_id = $obj_id;
                }

                if($bonus_id != $cur_bonus_id)
                {
                    $cur_state['bonuses'][] = array(
                        'type' => $state['b_type'],
                        'target' => $state['b_target'],
                        'value' => $state['b_value']
                    );
                    $cur_bonus_id = $bonus_id;
                }

            }
            else
            {
                $cur_id = $state_id;

                $cur_state = new ArrayObject(array(
                    'id' => $state['id'],
                    'is_auto_deliv' => $state['isAutoDeliv'],
                    'is_loop' => $state['isLoop']
                ));

                $cur_state['objectives'][] = array(
                    'type' => $state['o_type'],
                    'target' => $state['o_hook'],
                    'objective' => $state['o_objective'],
                    'comparision' => $state['o_comparision'],
                    'value' => $state['o_value']
                );
                $cur_obj_id = $obj_id;

                $cur_state['bonuses'][] = array(
                    'type' => $state['b_type'],
                    'target' => $state['b_target'],
                    'value' => $state['b_value']
                );
                $cur_bonus_id = $bonus_id;
                $filtered_states[] = $cur_state;
            }
        }
        return $filtered_states;
    }

    /**
     * 获取指定任务的目标状态(进度)
     * @param $mssid
     * @return array
     */
    public static function getObjectiveStateByMssid($user_id, $mssid)
    {
        $sql = <<<SQL
        select distinct o.des, o.objective, os.value from Hui_MissionObjectiveState os
        left join Hui_MissionObjective o.id = os.objId and
        where os.mStateId in (
          select id from Hui_MissionState where mssId = :mssid and userId = :user_id
        )
SQL;
        $bind = array(
            'user_id' => $user_id,
            'mssid' => $mssid
        );

        return self::nativeQuery($sql, $bind);
    }

    /**
     * 添加任务
     * @param $title
     * @param null $des
     * @param bool $is_loop
     * @param bool $is_auto_deliv
     * @param bool $is_auto_ass
     * @param bool $is_visible
     * @param null $prev_id
     * @param null $pid
     * @param bool $is_daily
     * @param array $objectives
     * @return bool
     */
    public static function addMission($title, $des=null, $is_loop=false, $is_auto_deliv=false, $is_auto_ass=false, $is_visible=true, $prev_id=null, $pid=null, $is_daily=false, array $objectives=null)
    {
        $sql_mission = <<<SQL
        insert into Hui_Mission (title, des, isLoop, isAutoDeliv, isAutoAss, isVisible, prevId, pid, isDaily) values (:title, :des, :is_loop, :is_auto_deliv, :is_auto_ass, :is_visible, :prev_id, :pid, :is_daily)
SQL;
        $bind_mission = array(
            'title' => $title,
            'des' => $des,
            'is_loop' => $is_loop,
            'is_auto_deliv' => $is_auto_deliv,
            'is_auto_ass' => $is_auto_ass,
            'is_visible' => $is_visible,
            'prev_id' => $prev_id,
            'pid' => $pid,
            'is_daily' => $is_daily
        );

        $connection = self::_getConnection();
        $connection->begin();

        $success_mission = $connection->execute($sql_mission, $bind_mission);

        if(empty($objectives)) return $success_mission;

        $mssid = $connection->lastInsertId();

        $bind_objective = array('mssid' => $mssid);
        $values_str = '';


        foreach($objectives as $index => $objective)
        {

            $type_field = ':type'.$index;
            $hook_field = ':hook'.$index;
            $step_field = ':step'.$index;
            $objective_field = ':objective'.$index;
            $des_field = ':des'.$index;

            $values_str .= "($type_field, $hook_field, $step_field, $objective_field, $des_field, :mssid), ";

            $crt_objective = new Criteria($objective);

            $bind_objective[$type_field] = $crt_objective->type;
            $bind_objective[$hook_field] = $crt_objective->hook;
            $bind_objective[$step_field] = $crt_objective->step;
            $bind_objective[$objective_field] = $crt_objective->objective;
            $bind_objective[$des_field] = $crt_objective->des;
        }

        $values_str = rtrim($values_str, ', ');

        $sql_objective = <<<SQL
        insert into Hui_MissionObjective ([type], hook, step, objective, des, mssId) values $values_str
SQL;
        $success_objective = $connection->execute($sql_objective, $bind_objective);

        if(!$success_objective)
        {
            $connection->rollback();
            return false;
        }

        return $connection->commit();
    }

    /**
     * 更新任务
     * @param $id
     * @param $criteria
     * @return bool
     */
    public static function updateMission($id, array $criteria=null)
    {
        $crt = new Criteria($criteria);

        $field_str = '';
        $bind_mission = array('id' => $id);

        if($crt->title)
        {
            $field_str .= 'title = :title, ';
            $bind_mission['title'] = $crt->title;
        }

        if($crt->des)
        {
            $field_str .= 'des = :des, ';
            $bind_mission['des'] = $crt->des;
        }

        if($crt->is_loop)
        {
            $field_str .= 'isLoop = :is_loop, ';
            $bind_mission['is_loop'] = $crt->is_loop;
        }

        if($crt->is_auto_deliv)
        {
            $field_str .= 'isAutoDeliv = :is_auto_deliv, ';
            $bind_mission['is_auto_deliv'] = $crt->is_auto_deliv;
        }

        if($crt->is_auto_ass)
        {
            $field_str .= 'isAutoAss = :is_auto_ass, ';
            $bind_mission['is_auto_ass'] = $crt->is_auto_ass;
        }

        if($crt->is_pub)
        {
            $field_str .= 'isPub = :is_pub, ';
            $bind_mission['is_pub'] = $crt->is_pub;
        }

        if($crt->is_daily)
        {
            $field_str .= 'isDaily = :is_daily, ';
            $bind_mission['is_daily'] = $crt->is_daily;
        }

        if($crt->visible)
        {
            $field_str .= 'visible = :visible, ';
            $bind_mission['visible'] = $crt->visible;
        }

        if($crt->prev_id)
        {
            $field_str .= 'prevId = :prev_id, ';
            $bind_mission['prev_id'] = $crt->prev_id;
        }

        $field_str = rtrim($field_str, ', ');

        $sql_mission = <<<SQL
        update Hui_Mission set $field_str
        where id = :id
SQL;

        $connection = self::_getConnection();

        $connection->begin();

        $success_mission = $connection->execute($sql_mission, $bind_mission);

        if(!$crt->objectives) return $success_mission;

        $objective_ids = array();

        foreach($crt->objectives as $objective)
        {

            $crt_obj = new Criteria($objective);

            if($crt_obj->id)
            {
                $field_obj_str = '';
                $bind_objective = array('id' => $crt_obj->id);

                if($crt_obj->type)
                {
                    $field_str .= '[type] = :type';
                    $bind_objective['type'] = $crt_obj->type;
                }

                if($crt_obj->hook)
                {
                    $field_str .= 'hook = :hook';
                    $bind_objective['hook'] = $crt_obj->hook;
                }

                if($crt_obj->step)
                {
                    $field_str .= 'step = :step';
                    $bind_objective['step'] = $crt_obj->step;
                }

                if($crt_obj->objective)
                {
                    $field_str .= 'objective = :objective';
                    $bind_objective['objective'] = $crt_obj->objective;
                }

                if($crt_obj->des)
                {
                    $field_str .= 'des = :des';
                    $bind_objective['des'] = $crt_obj->des;
                }

                $sql_objective = <<<SQL
            update Hui_MissionObjective set $field_obj_str
            where id = :id
SQL;
                $success_objective = $connection->execute($sql_objective, $bind_objective);

                if(!$success_objective)
                {
                    $connection->rollback();
                    return false;
                }

                $objective_ids[] = $crt_obj->id;
            }
            else
            {
                $sql_add_objective = <<<SQL
                insert into Hui_MissionObjective set ([type], hook, step, objective, des, mssId)
                values (:type, :hook, :step, :objecive, :des, :mssid)
SQL;
                $bind_add_objective = array(
                    'type' => $crt_obj->type,
                    'hook' => $crt_obj->hook,
                    'step' => $crt_obj->step,
                    'objective' => $crt_obj->objective,
                    'des' => $crt_obj->des,
                    'mssid' => $id
                );

                $success_add_objective = $connection->execute($sql_add_objective, $bind_add_objective);

                if(!$success_add_objective)
                {
                    $connection->rollback();
                    return false;
                }

                $objective_id = $connection->lastInsertId();
                $objective_ids[] = $objective_id;

                //为任务添加了新任务目标后,同时要为正在进行的该任务添加目标状态
                $sql_get_state = <<<SQL
                select id from Hui_MissionState where isComplete != 1 and mssId = :mssid;
SQL;
                $bind_get_state = array('mssid' => $id);

                $states = $connection->query($sql_get_state, $bind_get_state);

                if(!empty($states))
                {
                    $field_obj_state_str = '';

                    foreach($states as $state)
                    {
                        $state_id = $state['id'];
                        $field_obj_state_str .= "( $objective_id, $state_id ), ";
                    }

                    $field_obj_state_str = rtrim($field_obj_state_str, ', ');

                    $sql_add_obj_state = <<<SQL
                    insert into Hui_MissionObjectiveState set ( objId, mStateId )
                    values $field_obj_state_str
SQL;
                    $success_add_obj_state = $connection->execute($sql_add_obj_state);

                    if(!$success_add_obj_state)
                    {
                        $connection->rollback();
                        return false;
                    }
                }

            }
        }

        //删除不再使用的object同时删除与之相关的所有信息

        $objective_ids_str = implode(', ', $objective_ids);

        $sql_del_obj_state = "delete from Hui_MissionObjectiveState where objId not in ( $objective_ids_str )";

        $success_del_obj_state = $connection->execute($sql_del_obj_state);

        if(!$success_del_obj_state)
        {
            $connection->rollback();
            return false;
        }

        $sql_del_objective = <<<SQL
        delete from Hui_MissionObjective where id not in ($objective_ids_str)
SQL;
        $success_del_objective = $connection->execute($sql_del_objective);

        if(!$success_del_objective)
        {
            $connection->rollback();
            return false;
        }

        return $connection->commit();

    }

    /**
     * 删除任务并删除与之相关的所有信息
     * @param $id
     * @return bool
     */
    public static function delMission($id)
    {

        $sql = 'delete from Hui_Mission where id = :id';
        $bind = array('id' => $id);
        return self::nativeExecute($sql, $bind);

        /*
         * 以下事务已用外键级联删除代替
         *
        $connection = self::_getConnection();
        $sql_objective_state = <<<SQL
        delete from Hui_MissionObjectiveState where mStateId in (
          select id from Hui_MissionState where mssid = :mssid
        )
SQL;
        $bind_objective_state = array('mssid' => $id);

        $success_obj_state = $connection->execute($sql_objective_state, $bind_objective_state);

        if(!$success_obj_state)
        {
            $connection->rollback();
            return false;
        }

        $sql_mission_state = <<<SQL
        delete from Hui_MissionState where mssId = :mssid
SQL;
        $bind_mission_state = array('mssid' => $id);

        $success_mission_state = $connection->execute($sql_mission_state, $bind_mission_state);

        if(!$success_mission_state)
        {
            $connection->rollback();
            return false;
        }

        $sql_objective = <<<SQL
        delete from Hui_MissionObjective where mssId = :mssid
SQL;
        $bind_objective = array('mssid' => $id);

        $success_objective = $connection->execute($sql_objective, $bind_objective);

        if(!$success_objective)
        {
            $connection->rollback();
            return false;
        }

        $sql_mission = <<<SQL
        delete from Hui_Mission where id = :id
SQL;
        $bind_mission = array('id' => $id);

        $success_mission = $connection->execute($sql_mission, $bind_mission);

        if(!$success_mission)
        {
            $connection->rollback();
            return false;
        }

        return $connection->commit();
        */
    }

    /**
     * 添加任务状态(领取任务用)
     * @param $user_id
     * @param $mssid
     * @param bool $is_complete
     * @return bool
     */
    public static function addMissionState($user_id, $mssid, $is_complete=false)
    {

        $connection = self::_getConnection();

        $connection->begin();

        $sql_state = <<<SQL
        insert into Hui_MissionState (userId, mssId, isComplete)
        values (:user_id, :mssid, :is_complete)
SQL;
        $bind_state = array(
            'user_id' => $user_id,
            'mssid' => $mssid,
            'is_complete' => $is_complete
        );

        $success_state = $connection->execute($sql_state, $bind_state);

        if(!$success_state)
        {
            $connection->rollback();
            return false;
        }

        $state_id = $connection->lastInsertId();

        $sql_get_objective = <<<SQL
        select id from Hui_MissionObjective where mssId = :mssid
SQL;
        $bind_get_objective = array(
            'mssid' => $mssid
        );

        $objectives = $connection->query($sql_get_objective, $bind_get_objective);

        if(empty($objectives))
        {
            $connection->rollback();
            return false;
        }

        $values_obj_state_str = '';

        foreach($objectives as $objective)
        {
            $obj_id = $objective['id'];
            $values_obj_state_str .= "( $obj_id, $state_id ), ";
        }

        $values_obj_state_str = rtrim($values_obj_state_str, ', ');

        $sql_add_obj_state = <<<SQL
        insert into Hui_MissionObjectiveState ( objId, mStateId)
        values $values_obj_state_str
SQL;

        $success_obj_state = $connection->execute($sql_add_obj_state);

        if(!$success_obj_state)
        {
            $connection->rollback();
            return false;
        }

        return $connection->commit();
    }

    /**
     * 更新任务状态(用于交付任务等)
     * @param $id
     * @param array $criteria
     * @return bool
     */
    public static function updateMissionState($id, array $criteria=null)
    {

        $crt = new Criteria($criteria);

        $field_state_str = '';
        $bind_state = array('id' => $id);

        if(!is_null($crt->is_complete))
        {
            $field_state_str .= 'isComplete = :is_complete, ';
            $bind_state['is_complete'] = $crt->is_complete;
        }

        if(!is_null($crt->is_satisfy))
        {
            $field_state_str .= 'isSatisfy = :is_satisfy, ';
            $bind_state['is_satisfy'] = $crt->is_satisfy;
        }

        if($crt->complete_date)
        {
            $field_state_str .= 'completeDate = :complete_date, ';
            $bind_state['complete_date'] = $crt->is_complete;
        }

        $field_state_str = rtrim($field_state_str, ', ');

        $sql = <<<SQL
        update Hui_MissionState set $field_state_str where id = :id
SQL;

        return self::nativeExecute($sql, $bind_state);
    }

    /**
     * 删除任务状态(用于放弃任务)
     * @param $id
     * @return bool
     */
    public static function delMissionState($id)
    {

        $sql = 'delete from Hui_MissionState where id =:id';
        $bind = array('id' => $id);

        return self::nativeExecute($sql, $bind);
       /*
        * 以下事务已由外键级联删除代替
        *
        $connection = self::_getConnection();
        $connection->begin();
        $sql_del_state = "delete from Hui_MissionState where id = :id";
        $bind_state = array(
            'id' => $id
        );

        $success_state = $connection->execute($sql_del_state, $bind_state);

        if(!$success_state)
        {
            $connection->rollback();
            return false;
        }

        $sql_obj_state = "delete from Hui_MissionObjectiveState where mStateId = :state_id";
        $bind_obj_state = array(
            'state_id' => $id
        );

        $success_obj_state = $connection->execute($sql_obj_state, $bind_obj_state);

        if(!$success_obj_state)
        {
            $connection->rollback();
            return false;
        }

        return $connection->commit();*/
    }

    /**
     * 重置日常任务
     * @return bool
     */
    public static function resetDailyMissionState()
    {
        $sql = <<<SQL
        delete s from
        Hui_MissionState s
        left join Hui_Mission m on m.id = s.mssId
        where m.isDaily = 1 and datediff(d, s.assignDate, getdate()) >= 1
SQL;
        return self::nativeExecute($sql);

    }

    /**
     * 领取自动任务
     * @param $user_id
     * @return bool
     * @throws Exception
     */
    public static function assignAutoAssignMission($user_id)
    {
        $connection = self::_getConnection();



            $sql_get_auto_mission = <<<SQL
        select m.id, o.id as objId from Hui_mission m
        left join Hui_MissionObjective o on o.mssId = m.id
        where m.isAutoAss = 1
        and o.id is not NULL
        and m.id not in
        (
          select mssId from Hui_MissionState where userId = :user_id
        )
SQL;
            $bind_get_auto_mission = array('user_id' => $user_id);

            $missions = self::nativeQuery($sql_get_auto_mission, $bind_get_auto_mission);

            if(empty($missions))
            {
                return false;
            }

        try
        {
            $connection->begin();
            foreach($missions as $mission)
            {
                $sql_add_mission_state = <<<SQL
            insert into Hui_MissionState (userId, mssId) values (:user_id, :mssId)
SQL;
                $bind_add_mission_state = array(
                    'user_id' => $user_id,
                    'mssId' => $mission['id']
                );

                $connection->execute($sql_add_mission_state, $bind_add_mission_state);
                $state_id = $connection->lastInsertId();

                $sql_add_obj_state = <<<SQL
                insert into Hui_MissionObjectiveState (mStateId, objId) values (:state_id, :obj_id)
SQL;
                $bind_add_obj_state = array(
                    'state_id' => $state_id,
                    'obj_id' => $mission['objId']
                );
                $connection->execute($sql_add_obj_state, $bind_add_obj_state);
            }

            $connection->commit();
        }
        catch(\Exception $e)
        {
            $connection->rollback();
            throw $e;
        }
    }

}