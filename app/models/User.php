<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 15-5-29
 * Time: 下午4:10
 */

use \Phalcon\Db;
use \Palm\Exception\DbTransException;

class User extends ModelEx
{
    /**
     * 添加挪车用户信息(挪车业务用户信息)
     * @param string $user_id
     * @return bool
     */
    public static function addMoveCarUserInfo($user_id)
    {
        $sql = "insert into MC_User (user_id) values (:user_id)";
        $bind = array(
            'user_id' => $user_id
        );

        return self::nativeExecute($sql, $bind);
    }

    /**
     * 获取制定用户的挪车业务用户信息
     * @param $user_id
     * @return array
     */
    public static function getMoveCarUserInfo($user_id)
    {

        $sql = "select top 1 id, user_id, balance, phone from MC_User where user_id = :user_id";
        $bind = array(
            'user_id' => $user_id
        );
        return self::fetchOne($sql, $bind, null, Db::FETCH_ASSOC);
    }

    /**
     * 获取票券列表
     * @param $user_id
     * @param int|string $type
     * @param int|string $scope
     * @param bool|false $is_expired
     * @param int|null $start
     * @param int|null $length
     * @param bool|false $is_latest
     * @return array
     */
    public static function getTicketList($user_id, $type=null, $scope=1, $is_expired=false, $start=null, $length=null, $is_latest=false)
    {
        $top = '';
        if($length and !$is_latest)
        {
            $top = 'top '.$length;
        }

        $sql = "select $top id, type, user_id, no, title, value, des, convert(varchar(20), create_date, 20) as create_date, convert(varchar(20), end_date, 20) as end_date from Ticket where user_id = :user_id and scope=:scope and state = 1 and (unlock_time < getdate() or unlock_time is null)";
        $bind = array(
            'user_id' => $user_id,
            'scope' => $scope
        );

        if($type)
        {
            $sql .= ' and type = :type';
            $bind['type'] = $type;
        }

        if($is_expired === true)
        {
            $sql .= ' and end_date <= getdate()';
        }
        elseif($is_expired === false)
        {
            $sql .= ' and end_date > getdate()';
        }

        if($start)
        {
            if($is_latest)
            {
                $sql .= ' and id > :start';
            }
            else
            {
                $sql .= ' and id < :start';
            }
            $bind['start'] = $start;
        }

        $sql .= ' order by id desc';

        return self::nativeQuery($sql, $bind);
    }

    /**
     * 获取票券总数
     * @param $user_id
     * @param int|string $type 票券类型 1:红包, 2:优惠券
     * @param int|string $scope 使用应用范围 1:全部, 2:挪车业务
     * @param bool|false $include_expired
     * @return mixed
     */
    public static function getTicketCount($user_id, $type=null, $scope=1, $include_expired=false)
    {
        $sql = "select count(id) as [count] from Ticket where state = 1 and (unlock_time is null or unlock_time < getdate()) and scope = :scope and user_id = :user_id";
        $bind = array(
            'user_id' => $user_id,
            'scope' => $scope
        );

        if($type)
        {
            $sql .= ' and type = :type';
            $bind = array(
                'type' => $type
            );
        }

        if(!$include_expired)
        {
            //包含过期票券
            $sql .= ' and datediff(dd, end_date, getdate()) < 0';
        }

        $result = self::fetchOne($sql, $bind, null, Db::FETCH_NUM);
        return $result[0];
    }

    /**
     * 添加票券
     * @param array $data
     * @return bool|int
     */
    public static function addTicket(array $data)
    {
        $crt = new Criteria($data);
        $sql = "insert into Ticket (no, type, scope, value, balance, use_fee, title, des, end_date, user_id) values (:no, :type, :scope, :value, :value, :use_fee, :title, :des, :end_date, :user_id)";
        $bind = array(
            'type' => $crt->type,
            'scope' => $crt->scope,
            'value' => $crt->value,
            'use_fee' => $crt->use_fee,
            'title' => $crt->title,
            'des' => $crt->des,
            'end_date' => $crt->end_date,
            'user_id' => $crt->user_id
        );

        $connection = self::_getConnection();

        do
        {
            //生成票券码, 并添加数据
            mt_srand(microtime() * 1000);
            $md5_str = md5(date('YmdHis').mt_rand(1, 99999));
            $ticket_no = strtoupper(substr($md5_str, 16, 16));
            $bind['no'] = $ticket_no;
            $success = $connection->execute($sql, $bind);
            $err_info = $connection->getInternalHandler()->errorInfo();
        }while($err_info[1] == '2627');

        return $success ? $connection->lastInsertId() : false;
    }

    /**
     * 添加微信用户(微信注册)
     * @param array $openid
     * @return bool
     */
    public static function addWxUser($openid)
    {
        $user_id = 'wx_account@yn122.net';
        $pwd = '';

        $sql = 'insert into IAM_USER (userid, pwd, weixintoken, wx_openid, status) values (:user_id, :pwd, :openid, :openid, :status)';
        $bind = array(
            'user_id' => $user_id,
            'pwd' => $pwd,
            'openid' => $openid,
            'status' => 2 //设置用户状态为微信注册
        );

        return self::nativeExecute($sql, $bind);
    }
    /**
     * 更具用户ID获取用户信息
     * @param $id
     * @return array
     */
    public static function getUserInfoById($id)
    {
        $sql = <<<SQL
        select u.id as id, u.userid as user_id, u.uname, u.nickname,
		u.sex, u.wx_openid, u.wx_unoinid, u.wx_token,
		--c.name as city, p.name as province, u.provinceId as province_id, u.cityid as city_id,
		isnull(mu.phone, u.phone) as phone
		--u.HuiGold, status
		from IAM_USER u
		--left join City c on u.cityid=c.id
		--left join Province p on u.provinceid=p.id
		left join MC_User mu on mu.user_id = u.userid
		where u.USERID = :id
SQL;
        $bind['id'] = $id;

        return self::fetchOne($sql, $bind, null, Db::FETCH_ASSOC);
    }

    /**
     * 根据用户phone获取用户信息
     * @param  string $phone
     * @return array
     */
    public static function getUserByPhone($phone)
    {
        $sql = <<<SQL
        select top 1 u.id as id, u.userid as user_id, u.uname, u.nickname,
        u.sex,
        c.name as city, p.name as province, u.provinceId as province_id, u.cityid as city_id,
        u.phone as phone, u.HuiGold
        from IAM_USER u
        left join City c on u.cityid=c.id
        left join Province p on u.provinceid=p.id
        where u.phone = :phone
SQL;
        $bind['phone'] = $phone;

        return self::fetchOne($sql, $bind, null, Db::FETCH_ASSOC);
    }

    /**
     * 获取用户列表数据
     * @param  array|null $criteria
     * @return array
     */
    public static function getUserList(array $criteria=null)
    {
        $crt = new Criteria($criteria);
        $condition_arr = array();
        $condition_str = '';
        $bind = array();

        if($crt->user_id)
        {
            $condition_arr[] = 'userid = :user_id';
            $bind['user_id'] = $crt->user_id;
        }

        if($crt->wx_openid)
        {
            $condition_arr[] = 'weixintoken = :wx_openid';
            $bind['wx_openid'] = $crt->wx_openid;
        }

        if(!empty($condition_arr))
        {
            $condition_str = 'where '.implode(' and ', $condition_arr);
        }

        $sql = <<<SQL
        select id, userid as user_id, uname, nickname, phone from IAM_USER
        $condition_str
SQL;
        return self::nativeQuery($sql, $bind);
    }

    /**
     * 获取微信绑定用户
     * @param  string $openid 微信openid
     * @param  string $source 微信openid来源,不同的公众号代号,如车友惠服务号为cm
     * @return array
     */
    public static function getWxBindUser($openid, $source='cm')
    {
        $sql = null;
        $bind = array('openid' => $openid);
        if($source == 'cm')
        {
            $sql = <<<SQL
            select
            u.id as id, u.userid as user_id, u.uname, u.nickname,
            u.sex, u.wx_openid, u.wx_unoinid, u.wx_token,
            --c.name as city, p.name as province, u.provinceId as province_id, u.cityid as city_id,
            isnull(mu.phone, u.phone) as phone
            --u.HuiGold, status
            from IAM_USER u
            --left join City c on u.cityid=c.id
            --left join Province p on u.provinceid=p.id
            left join MC_User mu on mu.user_id = u.userid
            where u.weixintoken = :openid
SQL;
        }
        else
        {
            $sql = <<<SQL
            select u.userid as user_id, u.status from IAM_USER u
            left join WX_User wxu on wxu.user_id = u.userid
            where wxu.openid = :openid and source = :source
SQL;
            $bind['source'] = $source;
        }

        return self::fetchOne($sql, $bind, null, Db::FETCH_ASSOC);
    }

    /**
     * 绑定微信用户
     * @param  string $user_id
     * @param  string $openid
     * @param  string $source  
     * @return bool
     */
    public static function wxBindUser($user_id, $openid, $source='cm')
    {
        $sql = null;
        $bind = null;

        if($source == 'cm')
        {
            $sql = <<<SQL
            update IAM_USER set weixintoken = :openid, wx_openid = :openid
            where userid = :user_id
SQL;
            $bind = array(
                'user_id' => $user_id,
                'openid' => $openid
            );
        }
        else
        {
            $get_wx_user_sql = <<<SQL
            select top 1 count(1) as [count] from WX_USER where openid = :openid and source = :source
SQL;
            $get_wx_user_bind = array(
                'openid' => $openid,
                'source' => $source
            );

            $wx_user = self::fetchOne($get_wx_user_sql, $get_wx_user_bind, null, Db::FETCH_ASSOC);

            if($wx_user['count'] == 0)
            {
                $sql = <<<SQL
                insert into WX_USER (openid, user_id, source) values(:openid, :user_id, :source)
SQL;
                $bind = array(
                    'openid' => $openid,
                    'user_id' => $user_id,
                    'source' => $source
                );
            }
            else
            {
                $sql = <<<SQL
                update WX_USER set user_id = :user_id where openid = :openid and source = :source
SQL;
                $bind = array(
                    'openid' => $openid,
                    'user_id' => $user_id,
                    'source' => $source
                );
            }

        }
        file_put_contents('../bind.log', $sql.PHP_EOL.var_export($bind, 1).PHP_EOL, FILE_APPEND);
        return self::nativeExecute($sql, $bind);
    }

    /**
     * 获取当前登录的用户信息
     * @return mixed
     * @throws Exception
     */
    public static function getCurrentUser()
    {
//        $di = \Phalcon\DI::getDefault();
//        $session = $di->getShared('session');
//        return $session->get('user');
        $di = \Phalcon\DI::getDefault();
        $guid = $di->get('request')->getHeader('AUTH_TOKEN');

        $sql = 'select top 1 data from AuthToken where guid = :guid';
        $bind = array('guid' => $guid);
        $user = self::fetchOne($sql, $bind, null, Db::FETCH_ASSOC);
        if(empty($user))
        {
            throw new Exception('error_token', 1001);
        }
        return json_decode($user['data'], true);
    }

    /**
     * 生成token
     * @param $user_id
     * @param $data
     * @return bool|string 成功返回生成的token guid,失败返回false
     */
    public static function genToken($user_id, $data)
    {
        $di =\Phalcon\DI::getDefault();
        $connection = $di->get('db');
        try
        {
            $connection->begin();

            $get_token_sql = 'select top 1 guid from AuthToken where user_id = :user_id';
            $get_token_bind = array(
                'user_id' => $user_id
            );
            $exists_token = self::fetchOne($get_token_sql, $get_token_bind, null, Db::FETCH_ASSOC);
            if(empty($exists_token))
            {
                $add_token_sql = 'insert into AuthToken (guid, user_id, data) values (:guid, :user_id, :data)';
            }
            else
            {
                $add_token_sql = 'update AuthToken set guid = :guid, data = :data where user_id = :user_id';
            }

            $add_token_bind = array(
                'user_id' => $user_id,
                'data' => json_encode($data)
            );
            do
            {
                $guid = md5(uniqid(mt_rand(), true));
                $add_token_bind['guid'] = $guid;
                $add_token_success = self::nativeExecute($add_token_sql, $add_token_bind);
                $err_info = $connection->getInternalHandler()->errorInfo();
            }while($err_info[1] == '2627');

            if(!$add_token_success)
            {
                throw new DbTransException('token error');
            }
            $connection->commit();
        }
        catch(DbTransException $e)
        {
            $connection->rollback();
            return false;
        }
        return $guid;
    }

    /**
     * @param $user_id
     * @return string
     */
    public static function getUserAvatarById($user_id)
    {
        $sql = 'select PHOTO from IAM_USERPHOTO where USERID = :user_id';
        $bind = array('user_id' => $user_id);

        $result = self::fetchOne($sql, $bind, null, Db::FETCH_NUM);

        return $result[0];
    }

    /**
     * 添加惠金币
     * @param $user_id
     * @param $num
     * @return bool
     */
    public static function addHuiGold($user_id, $num)
    {
        $sql = <<<SQL
        update IAM_USER set HuiGold = HuiGold + :num where USERID = :user_id
SQL;
        $bind = array('user_id' => $user_id, 'num' => $num);

        return self::nativeExecute($sql, $bind);

    }


    /**
     * 保存用户信息(如果已存在则更新,不存在则添加)
     * @param array $criteria
     * @return bool|int
     */
    public static function saveUserInfo(array $criteria)
    {
        $crt = new Criteria($criteria);

        $user_id = $crt->user_id;

        if(!$user_id) return false; //必须含有user_id,否则不做任何操作并返回false

        $sql_exists = 'select count(id) from IAM_USER_INFO where userid = :user_id';
        $bind_exists = array('user_id' => $user_id);

        $result_exists = self::fetchOne($sql_exists, $bind_exists, null, Db::FETCH_NUM);
        $exists = !empty($result_exists[0]);

        if(!$exists)
        {
            $sql_add = 'insert into IAM_USER_INFO (userid, uname, phone, sex, address,
		idcardno, city, province, area, sinaWeibo, weixin, hphm, hpzl,
		people, qqNum) values (:user_id, :uname,
		:phone, :sex, :address, :id_no, :city,
		:province, :area, :sina_weibo, :weixin,
		:hphm, :hpzl, :people, :qq_num)';
            $bind_add = array(
                'user_id' => $crt->user_id,
                'uname' => $crt->uname,
                'phone' => $crt->phone,
                'sex' => $crt->sex,
                'address' => $crt->address,
                'id_no' => $crt->id_no,
                'city' => $crt->city,
                'province' => $crt->province,
                'area' => $crt->area,
                'sina_weibo' => $crt->sina_weibo,
                'weixin' => $crt->weixin,
                'hphm' => $crt->hphm,
                'hpzl' => $crt->hpzl,
                'people' => $crt->people,
                'qq_num' => $crt->qq_num
            );

            $success = self::nativeExecute($sql_add, $bind_add);
            if(!$success) return false;
            $connection = self::_getConnection();
            return $connection->lastInsertId();
        }
        else
        {
            $field_str = '';
            $bind_update = array('user_id' => $crt->user_id);

            if($crt->uname)
            {
                $field_str .= 'uname = :uname, ';
                $bind_update['uname'] = $crt->uname;
            }

            if($crt->phone)
            {
                $field_str .= 'phone = :phone, ';
                $bind_update['phone'] = $crt->phone;
            }

            if($crt->sex)
            {
                $field_str .= 'sex = :sex, ';
                $bind_update['sex'] = $crt->sex;
            }

            if($crt->address)
            {
                $field_str .= 'address = :address, ';
                $bind_update['address'] = $crt->address;
            }

            if($crt->id_no)
            {
                $field_str .= 'idcardno = :id_no, ';
                $bind_update['id_no'] = $crt->id_no;
            }

            if($crt->city)
            {
                $field_str .= 'city = :city, ';
                $bind_update['city'] = $crt->city;
            }

            if($crt->province)
            {
                $field_str .= 'province = :province, ';
                $bind_update['province'] = $crt->province;
            }

            if($crt->area)
            {
                $field_str .= 'area = :area, ';
                $bind_update['area'] = $crt->area;
            }

            if($crt->sina_weibo)
            {
                $field_str .= 'sinaWeibo = :sina_weibo, ';
                $bind_update['sina_weibo'] = $crt->sina_weibo;
            }

            if($crt->weixin)
            {
                $field_str .= 'weixin = :weixin, ';
                $bind_update['weixin'] = $crt->weixin;
            }

            if($crt->hphm)
            {
                $field_str .= 'hphm = :hphm, ';
                $bind_update['hphm'] = $crt->hphm;
            }

            if($crt->hpzl)
            {
                $field_str .= 'hpzl = :hpzl, ';
                $bind_update['hpzl'] = $crt->hpzl;
            }

            if($crt->people)
            {
                $field_str .= 'people = :people, ';
                $bind_update['people'] = $crt->people;
            }

            if($crt->qq_num)
            {
                $field_str .= 'qqNum = :qq_num, ';
                $bind_update['qq_num'] = $crt->qq_num;
            }

            if($field_str)
            {
                $field_str = rtrim($field_str, ', ');
            }

            $sql_update = "update IAM_USER_INFO set $field_str where userid = :user_id";

            return self::nativeExecute($sql_update, $bind_update);
        }
    }

    /**
     * 更改挪车用户信息
     * @param array $user_id
     * @param array $data
     * @return bool
     */
    public static function updateMoveCarUserInfo($user_id, array $data)
    {
        $crt = new Criteria($data);
        $field_str = '';
        $bind = array('user_id' => $user_id);

        if($crt->phone)
        {
            $field_str .= 'phone = :phone, ';
            $bind['phone'] = $crt->phone;
        }

        if(!empty($field_str))
        {
            $field_str = rtrim($field_str, ', ');
        }

        $sql = <<<SQL
        update MC_USER set $field_str where user_id = :user_id
SQL;
        return self::nativeExecute($sql, $bind);
    }

    /**
     * 获取用户车辆总数
     * @param $user_id
     * @return int
     */
    public static function getCarCount($user_id)
    {
        $sql = "select count(id) from MC_Car where state = 1 and user_id = :user_id";
        $bind = array('user_id' => $user_id);
        $result = self::fetchOne($sql, $bind, null, Db::FETCH_NUM);
        return (int)$result[0];
    }

    /**
     * 修改挪车业务用户信息
     * @param string $user_id
     * @param array $data
     * @return bool
     */
    public static function updateMcUser($user_id, $data)
    {
        $crt = new Criteria($data);
        $field_str = '';
        $bind = array('user_id' => $user_id);

        if($crt->phone)
        {
            $field_str .= 'phone = :phone, ';
            $bind['phone'] = $crt->phone;
        }

        if(!empty($field_str))
        {
            $field_str = rtrim($field_str, ', ');
        }

        $sql = "update MC_User set $field_str  where user_id = :user_id";

        return self::nativeExecute($sql, $bind);
    }

    /**
     * 获取用户车辆
     * @param string $user_id
     * @param string $order_by 排序字段
     * @param mixed  $start 排序字段起始值
     * @param int    $length 列表长度
     * @return array
     */
    public static function getCarList($user_id, $order_by=null, $start = null, $length = 10)
    {
        $condition_str = 'where state = 1 and user_id = :user_id ';
        $bind = array(
            'user_id' => $user_id
        );

        $order_by_str = '';

        if(!empty($order_by))
        {
            $order_by_arr = explode(' ', $order_by);
            $order_field = $order_by_arr[0];
            $order_method = count($order_by_arr) == 2 ? $order_by_arr[1] : 'asc';
            $compare_opt = $order_method == 'asc' ? '>' : '<';

            if(!empty($start))
            {
                $condition_str .= "and $order_field $compare_opt :start ";
                $bind['start'] = $start;
            }
            $order_by_str = 'order by '.$order_by;
        }

        $length_str = '';
        if(!empty($length))
        {
            $length_str = 'top '.$length;
        }

        $sql = <<<SQL
        select $length_str
        id, hphm
        from MC_Car
        $condition_str
        $order_by_str
SQL;

        return self::nativeQuery($sql, $bind);
    }

    /**
     * 用户的某车辆是否存在
     * @param $user_id
     * @param $hphm
     * @param bool $get_data 是否获取数据,否则获取bool
     * @return bool
     */
    public static function isCarExists($user_id, $hphm, $get_data=false)
    {
        $sql = "select top 1 id, hphm, state from MC_Car where user_id = :user_id and hphm = :hphm";
        $bind = array(
            'user_id' => $user_id,
            'hphm' => $hphm
        );
        $car = self::fetchOne($sql, $bind, null, Db::FETCH_ASSOC);
        return $get_data ? $car : (bool)$car;
    }

    /**
     * 添加用户车辆
     * @param $user_id
     * @param $hphm
     * @return int|bool
     */
    public static function addCar($user_id, $hphm)
    {
        $connection = self::_getConnection();
        $sql = <<<SQL
        insert into MC_Car (user_id, hphm) values(:user_id, :hphm)
SQL;
        $bind = array(
            'user_id' => $user_id,
            'hphm' => $hphm
        );
        self::nativeExecute($sql, $bind);
        return $connection->lastInsertId();
    }

    /**
     * 删除用户车辆信息
     * @param $car_id
     * @return bool
     */
    public static function deleteCar($car_id)
    {
        $sql = <<<SQL
        update MC_Car set state = 0 where id = :car_id
SQL;
        $bind = array('car_id' => $car_id);
        return self::nativeExecute($sql, $bind);
    }

    /**
     * 更新车辆信息
     * @param $car_id
     * @param array $data
     * @return bool
     */
    public static function updateCar($car_id, array $data)
    {
        $crt = new Criteria($data);
        $field_str = '';
        $bind = array('car_id' => $car_id);

        if($crt->hphm)
        {
            $field_str .= 'hphm = :hphm, ';
            $bind['hphm'] = $crt->hphm;
        }

        if($crt->state)
        {
            $field_str .= 'state = :state, ';
            $bind['state'] = $crt->state;
        }

        if(!empty($field_str))
        {
            $field_str = rtrim($field_str, ', ');
        }

        $sql = <<<SQL
        update MC_Car set $field_str
        where id = :car_id
SQL;
        return self::nativeExecute($sql, $bind);
    }


}