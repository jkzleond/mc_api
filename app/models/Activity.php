<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 15-6-1
 * Time: 下午12:52
 */

use \Phalcon\Db;

class Activity extends ModelEx
{
    /**
     * 获取自驾游列表
     * @param array|null $criteria
     * @param null $page_num
     * @param null $page_size
     * @return array
     */
    public static function getDrivingTourList(array $criteria=null, $page_num=null, $page_size=null)
    {

        $crt = new Criteria($criteria);

        $cte_condition_arr = array(
            '[type] = 3',
            'ISNULL(abandon, 0) != 1',
            'state = 1 or state = 2'
        );
        $cte_condition_str = '';

        $page_condition_str = '';

        $bind = array();

        if(!empty($cte_condition_arr))
        {
            $cte_condition_str = 'where '.implode(' and ', $cte_condition_arr);
        }

        if($page_num)
        {
            $page_condition_str = 'where rownum between :from and :to';
            $bind['from'] = ($page_num - 1) * $page_size + 1;
            $bind['to'] = $page_num * $page_size;
        }


        $sql = <<<SQL
        with ACT_DT_CTE as(
          select id, [name], url,
          convert(varchar(25),startDate,126) as start_date,
          convert(varchar(25),endDate,126) as end_date,
          a.place,
          picData as pic_data,
          ISNULL(u.user_num, 0) as user_num,
          a.state,
          ROW_NUMBER() over(order by a.state asc, a.createDate desc) as rownum
          from Activity a
          left join(
            select aid, count(userid) as user_num from ActivityUser
            where ISNULL(abandon, 0) != 1
            group by aid
          ) u on u.aid = a.id
          $cte_condition_str
        )
        select * from ACT_DT_CTE
        $page_condition_str
SQL;

        return self::nativeQuery($sql, $bind);
    }

    /**
     * 获取自驾游总数
     * @param array|null $criteria
     * @return mixed
     */
    public static function getDrivingTourCount(array $criteria=null)
    {
        $crt = new Criteria($criteria);

        $condition_arr = array(
            '[type] = 3',
            'ISNULL(abandon, 0) != 1',
            'state = 1 or state = 2'
        );
        $condition_str = '';


        $bind = array();


        if(!empty($condition_arr))
        {
            $condition_str = 'where '.implode(' and ', $condition_arr);
        }

        $sql = "select count(id) from Activity $condition_str";

        $result = self::fetchOne($sql, $bind, null, Db::FETCH_NUM);

        return $result[0];
    }

    /**
     * 根据ID获取自驾游详细信息
     * @param $id
     * @return object
     */
    public static function getDrivingTourDetailById($id)
    {
        $sql = <<<SQL
        select a.id, a.[name], a.place,
        convert(varchar(25),a.startDate,126) as start_date,
        convert(varchar(25),a.endDate,126) as end_date,
        a.state, a.infos, a.[option], a.[type] as type_id,
        convert(varchar(25),a.awardStart,126) as award_start_date,
        convert(varchar(25),a.awardEnd,126) as award_end_date,
        a.awardState as award_state, a.deposit, a.needPay as need_pay, a.payTypes as pay_types, a.contents,
        convert(varchar(25),a.signStartDate,126) as sign_start_date,
        convert(varchar(25),a.signEndDate,126) as sign_end_date,
        a.payDes as pay_des, a.signDes as sign_des, a.tripLine as trip_line,
        g.id as goods_id, g.name as goods_name, g.price as goods_price
        from Activity a
        left join Hui_ActivityToGoods a2g on a2g.activity_id = a.id
        left join Hui_Goods g on g.id = a2g.goods_id
        where a.id = :id
SQL;
        $bind = array('id' => $id);

        $results = self::nativeQuery($sql, $bind);

        //relation goods
        $aid = null;
        $activity = array();
        $activity['goods'] = array();
        foreach($results as $result)
        {
            if($aid != $result['id'])
            {
                $aid = $result['id'];
                foreach($result as $key => $value)
                {
                    if(strncmp($key, 'goods_', 6) == 0) continue;
                    $activity[$key] = $value;
                }
            }
            if($result['goods_id'])
            {
                $activity['goods'][] = array(
                    'id' => $result['goods_id'],
                    'name' => $result['goods_name'],
                    'price' => $result['goods_price']
                );
            }
        }

        return $activity;
    }

    /**
     * 获取除自驾游以外的所有活动
     * @param array|null $criteria
     * @param null $page_num
     * @param null $page_size
     * @return array
     */
    public static function getActivityList(array $criteria=null,$page_num=null, $page_size=null)
    {
        $crt = new Criteria($criteria);

        $cte_condition_arr = array(
            '[type] != 3',
            'ISNULL(abandon, 0) != 1'
        );
        $cte_condition_str = '';

        $page_condition_str = '';

        $bind = array();
        
        if($crt->pub_user_id)
        {
            $cte_condition_arr[] = 'pubUser = :pub_user_id';
            $cte_condition_arr[] = '(state <= 3)';
            $bind['pub_user_id'] = $crt->pub_user_id;
        }
        else if(!$crt->is_test)
        {   
            $cte_condition_arr[] = '(state = 1 or state = 2)'; // 0:未进行 1: 进行中 2:已过期 3:待审核
        }

        if(!empty($cte_condition_arr))
        {
            $cte_condition_str = 'where '.implode(' and ', $cte_condition_arr);
        }

        if($page_num)
        {
            $page_condition_str = 'where rownum between :from and :to';
            $bind['from'] = ($page_num - 1) * $page_size + 1;
            $bind['to'] = $page_num * $page_size;
        }


        $sql = <<<SQL
        with ACT_DT_CTE as(
          select id, [name], url, [type], 
          convert(varchar(25),startDate,126) as start_date,
          convert(varchar(25),endDate,126) as end_date,
          a.place,
          picData as pic_data,
          ISNULL(u.user_num, 0) as user_num,
          a.state,
          ROW_NUMBER() over(order by a.state asc, a.createDate desc) as rownum
          from Activity a
          left join(
            select aid, count(userid) as user_num from ActivityUser
            where ISNULL(abandon, 0) != 1
            group by aid
          ) u on u.aid = a.id
          $cte_condition_str
        )
        select * from ACT_DT_CTE
        $page_condition_str
SQL;
        return self::nativeQuery($sql, $bind);
    }

    /**
     * 获取除自驾游以外的活动总数
     * @param array $criteria
     * @return mixed
     */
    public static function getActivityCount(array $criteria=null)
    {
        $crt = new Criteria($criteria);

        $condition_arr = array(
            '[type] != 3',
            'ISNULL(abandon, 0) != 1'
        );
        $condition_str = '';


        $bind = array();

        if($crt->pub_user_id)
        {
            $condition_arr[] = 'pubUser = :pub_user_id';
            $condition_arr[] = '(state <= 3)';
            $bind['pub_user_id'] = $crt->pub_user_id;
        }
        else
        {
            $condition_arr[] = '(state = 1 or state = 2)'; // 0:未进行 1: 进行中 2:已过期 3:待审核
        }

        if(!empty($condition_arr))
        {
            $condition_str = 'where '.implode(' and ', $condition_arr);
        }

        $sql = "select count(id) from Activity $condition_str";

        $result = self::fetchOne($sql, $bind, null, Db::FETCH_NUM);

        return $result[0];
    }


    /**
     * 获取指定用户发布的活动(除自驾游以外)
     * @param $pub_user_id
     * @param null $page_num
     * @param null $page_size
     * @return array
     */
    public static function getActivityByPubUser($pub_user_id, $page_num=null, $page_size=null)
    {
        $cte_condition_arr = array(
            '[type] != 3', //除自驾游以外的
            'ISNULL(abandon, 0) != 1',
            'pubUser = :pub_user_id'
        );
        $cte_condition_str = '';

        $page_condition_str = '';

        $bind = array('pub_user_id' => $pub_user_id);

        if(!empty($cte_condition_arr))
        {
            $page_condition_str = 'where '.implode(' and ', $cte_condition_arr);
        }

        if($page_num)
        {
            $page_condition_str = 'where rownum between :from and :to';
            $bind['from'] = ($page_num - 1) * $page_size + 1;
            $bind['to'] = $page_num * $page_size;
        }


        $sql = <<<SQL
        with ACT_DT_CTE as(
          select id, [name], url,
          convert(varchar(25),startDate,126) as start_date,
          convert(varchar(25),endDate,126) as end_date,
          a.place,
          picData as pic_data,
          ISNULL(u.user_num, 0) as user_num,
          a.state,
          ROW_NUMBER() over(order by a.state asc, a.createDate desc) as rownum
          from Activity a
          left join(
            select aid, count(userid) as user_num from ActivityUser
            where ISNULL(abandon, 0) != 1
            group by aid
          ) u on u.aid = a.id
          $cte_condition_str
        )
        select * from ACT_DT_CTE
        $page_condition_str
SQL;
        return self::nativeQuery($sql, $bind);
    }

    /**
     * 获取活动的详细信息
     * @param $id
     * @return array
     */
    public static function getActivityDetailById($id)
    {
        $sql = <<<SQL
        select a.id, a.[name], a.place,
        convert(varchar(25),a.startDate,126) as start_date,
        convert(varchar(25),a.endDate,126) as end_date,
        a.state, a.infos, a.[option], a.[type] as type_id,
        convert(varchar(25),a.awardStart,126) as award_start_date,
        convert(varchar(25),a.awardEnd,126) as award_end_date,
        a.awardState as award_state, a.deposit, a.needPay as need_pay, a.payTypes as pay_types, a.contents,
        convert(varchar(25),a.signStartDate,126) as sign_start_date,
        convert(varchar(25),a.signEndDate,126) as sign_end_date,
        a.payDes as pay_des, a.signDes as sign_des, a.tripLine as trip_line,
        act_sel.name as sel_name, act_sel.optionList as sel_items_text, act_sel.shortNames as sel_items_value,
        g.id as goods_id, g.name as goods_name, g.price as goods_price
        from Activity a
        left join ActivitySelect act_sel on act_sel.aid = a.id
        left join Hui_ActivityToGoods a2g on a2g.activity_id = a.id
        left join Hui_Goods g on g.id = a2g.goods_id
        where a.id = :id
SQL;
        $bind = array('id' => $id);

        $results = self::nativeQuery($sql, $bind);

        //relation goods
        $aid = null;
        $activity = array();
        $activity['goods'] = array();
        foreach($results as $result)
        {
            if($aid != $result['id'])
            {
                $aid = $result['id'];
                foreach($result as $key => $value)
                {
                    if(strncmp($key, 'goods_', 6) == 0) continue;
                    $activity[$key] = $value;
                }
            }
            if($result['goods_id'])
            {
                $activity['goods'][] = array(
                    'id' => $result['goods_id'],
                    'name' => $result['goods_name'],
                    'price' => $result['goods_price']
                );
            }
        }

        return $activity;
    }

    /**
     * 指定用户是否已参加过指定活动
     * @param $user_id
     * @param $aid
     * @return bool
     */
    public static function isUserJoin($user_id, $aid)
    {
        $sql = 'select count(id) from ActivityUser where aid = :aid and userid = :user_id';
        $bind = array('aid' => $aid, 'user_id' => $user_id);
        $result = self::fetchOne($sql, $bind, null, Db::FETCH_NUM);

        return !empty($result[0]);
    }

    //获取单个活动参与用户信息
    public static function getActivityUser(array $criteria=null)
    {
        $crt = new Criteria($criteria);
        $condition_arr = array();
        $condition_str = '';
        $bind = array();

        if($crt->aid)
        {
            $condition_arr[] = 'aid = :aid';
            $bind['aid'] = $crt->aid;
        }

        if($crt->user_id)
        {
            $condition_arr[] = 'user_id = :user_id';
            $bind['user_id'] = $crt->user_id;
        }

        if($crt->invitation_code)
        {
            $condition_arr[] = 'invitation_code = :invitation_code';
            $bind['invitation_code'] = $crt->invitation_code;
        }

        if(!empty($condition_arr))
        {
            $condition_str = 'where '.implode(' and ', $condition_arr);
        }

        $sql = <<<SQL
        select id, userid as user_id, invitation_code, aid from ActivityUser
        $condition_str
SQL;
        return self::fetchOne($sql, $bind, null, Db::FETCH_ASSOC);
    }

    /**
     * 获取活动上家信息
     * @param  int|string $aid
     * @param  string     $user_id
     * @return array
     */
    public static function getActivityPuser($aid, $user_id)
    {

        $sql = <<<SQL
        select invitation_code from ActivityUser
        where aid = :aid and userid = ( select top 1 p_user_id from ActivityUser where aid = :aid and userid = :user_id )
SQL;
        $bind = array(
            'aid' => $aid,
            'user_id' => $user_id
        );
        
        return self::fetchOne($sql, $bind, null, Db::FETCH_ASSOC);
    }

    /**
     * 添加活动用户
     * @param array $user_info
     * @param $aid
     * @return bool
     */
    public static function addActivityUser(array $user_info, $aid)
    {
        $crt = new Criteria($user_info);
        $sql = <<<SQL
        insert into ActivityUser(userid, aid, state, options, payType,
		selected, submitTime, p_user_id, invitation_code) values(:user_id, :aid, 0, :options, :pay_type,
		:selected,getDate(), :p_user_id, :invitation_code)
SQL;
        $bind = array(
            'user_id' => $crt->user_id,
            'aid' => $aid,
            'options' => $crt->options,
            'pay_type' => $crt->pay_type,
            'selected' => $crt->selected,
            'p_user_id' => $crt->p_user_id,
            'invitation_code' => $crt->invitation_code
        );

        return self::nativeExecute($sql, $bind);
    }

    /**
     * 添加活动
     * @param array $criteria
     * @return string|bool
     */
    public static function addActivity(array $criteria)
    {
        $crt = new Criteria($criteria);
        $sql = 'insert into Activity ([name], place, createDate, startDate, endDate, state, needPay, pubUser) values (:name, :place, getdate(), :start_date, :end_date, 3, :need_pay, :pub_user)';
        $bind = array(
            'name' => $crt->name,
            'place' => $crt->place,
            'start_date' => $crt->start_date,
            'end_date' => $crt->end_date,
            'need_pay' => $crt->need_pay,
            'pub_user' => $crt->pub_user
        );

        $success = self::nativeExecute($sql, $bind);

        if(!$success) return false;

        $connection = self::_getConnection();
        return $connection->lastInsertId();
    }

    /**
     * 更新活动浏览量
     * @return bool
     */
    public static function updateActivityViewNum($id)
    {
        $connection = self::_getConnection();
        try
        {
            $connection->begin();
            $sql = 'update Activity set viewNum += 1 where id = :id';
            $bind = array('id' => $id);
            $success = self::nativeExecute($sql, $bind);
            if(!$success) throw new \Exception();
            $connection->commit();
        }
        catch(\Exception $e)
        {
            $connection->rollback();
        }

        return $success;
    }

    /**
     * 活动报名
     * @param array $sign_data
     * @param $aid
     * @return bool
     */
    public static function signUp(array $sign_data, $aid)
    {
        $connection = self::_getConnection();
        $connection->begin();
        $add_activity_user_success = self::addActivityUser($sign_data, $aid);
        $new_au_id = $connection->lastInsertId();
        $save_user_info_success = User::saveUserInfo($sign_data);
        $success = $add_activity_user_success and $save_user_info_success;

        if(!$success)
        {
            $connection->roolback();
            return false;
        }

        $success = $connection->commit();

        if(!$success) return false;

        return $new_au_id;
    }

    /**
     * 获取指定用户抽奖机会
     * @return int
     */
    public static function getDrawChance($aid, $user_id)
    {
        $sql = 'select chance from AwardChance where aid = :aid and userid = :user_id';
        $bind = array(
            'aid' => $aid,
            'user_id' => $user_id
        );

        $result = self::fetchOne($sql, $bind, null, Db::FETCH_ASSOC);
        return  !empty($result) ? $result['chance'] : 0;
    }
}