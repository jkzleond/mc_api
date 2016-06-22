<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 15-5-29
 * Time: 下午2:58
 */

use \Phalcon\Db;

class LocalFavour extends ModelEx
{
    /**
     * 获取本地惠发现列表
     * @param array|null $criteria
     * @param null $page_num
     * @param null $page_size
     * @return array
     */
    public static function getLocalFavourList(array $criteria=null, $page_num=null, $page_size=null)
    {

        $crt = new Criteria($criteria);

        $cte_condition_arr = array();
        $cte_condition_str = '';
        $page_condition_str = '';

        $bind = array();

        if($crt->type)
        {
            $cte_condition_arr[] = 'f.typeId = :type_id';
            $bind['type_id'] = $crt->type;
        }

        if($crt->province_id)
        {
            $cte_condition_arr[] = 'f.provinceid in (:province_id, 0)';
            $bind['province_id'] = $crt->province_id;
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
        WITH FA_CTE AS (
            select f.id,title,ISNULL(des,'') as des,favourType as [type],
			CONVERT(varchar(100), publishTime, 23) as publish_time,c.[count] as comment_num,
			ISNULL(picData,'') as pic_data, ROW_NUMBER() OVER (ORDER BY f.orderTime desc) AS rownum
			from LocalFavour f
			inner join LocalFavourType t on f.isState=1
			and f.typeId in (1, 6, 7)
			and f.typeId=t.id
			left join (select pid,count(id) as count from LocalFavourComment
				where isState=1 and userid is not null group by pid
			) c on f.id=c.pid
			left join LocalFavourPic p on p.pid=f.id
			$cte_condition_str
		)
		select * from FA_CTE
		$page_condition_str
SQL;

        return self::nativeQuery($sql, $bind);
    }

    /**
     * 获取本地惠发现总数
     * @param array|null $criteria
     * @return mixed
     */
    public static function getLocalFavourCount(array $criteria=null)
    {
        $crt = new Criteria($criteria);
        $condition_arr = array('typeId in (1, 6, 7)');
        $condition_str = '';
        $bind = array();

        if($crt->type)
        {
            $condition_arr[] = 'typeId = :type_id';
            $bind['type_id'] = $crt->type;
        }

        if($crt->province_id)
        {
            $condition_arr[] = 'provinceId in (:province_id, 0)';
            $bind['province_id'] = $crt->province_id;
        }

        if(!empty($condition_arr))
        {
            $condition_str = 'where '.implode(' and ', $condition_arr);
        }

        $sql = "select count(id) from LocalFavour $condition_str";

        $result = self::fetchOne($sql, $bind, null, Db::FETCH_NUM);

        return $result[0];
    }

    public static function getLocalFavourDetailById($id)
    {
        $sql = "select title, contents as content from LocalFavour where id = :id";
        $bind = array('id' => $id);
        return self::fetchOne($sql, $bind, null, Db::FETCH_ASSOC);
    }

    public static function addReadCount($id)
    {
        $sql = "update LocalFavour set countFavourRead = countFavourRead + 1 where id = :id";
        $bind = array('id' => $id);
        return self::nativeExecute($sql, $bind);
    }

    /**
     * 为指定id的发现添加回复
     * @param $local_favour_id
     * @param array|null $criteria
     * @return bool|int
     */
    public static function addComment($local_favour_id, array $criteria=null)
    {
        $crt = new Criteria($criteria);

        $sql = <<<SQL
        insert into LocalFavourComment(pId,userid,comment,push,commentTime,isState,clientType) values(
		:pid, :user_id, :comment, 1, getDate(), 1, :client_type)
SQL;
        $bind = array(
            'pid' => $local_favour_id,
            'user_id' => $crt->user_id,
            'comment' => $crt->contents,
            'client_type' => $crt->client_type
        );

        $success =  self::nativeExecute($sql, $bind);

        if(!$success) return false;

        $db = self::_getConnection();
        return $db->lastInsertId();
    }

    /**
     * 获取发现回复
     * @param $local_favour_id
     * @param null $page_num
     * @param null $page_size
     * @return array
     */
    public static function getDiscoveryCommentList($local_favour_id, $page_num=null, $page_size=null)
    {
        $bind = array(
            'local_favour_id' => $local_favour_id
        );
        $page_condition_str = '';

        if($page_num)
        {
            $page_condition_str = 'where rownum between :from and :to';
            $bind['from'] = ($page_num - 1) * $page_size + 1;
            $bind['to'] = $page_num * $page_size;
        }

        $sql = <<<SQL
        with COM_CTE as (
          select convert(varchar(25), commenttime, 126) as comment_time,
          userid as user_id, comment, commentReply as comment_reply,
          ROW_NUMBER() over ( order by commenttime desc ) as rownum
          from LocalFavourComment
          where pid=:local_favour_id and isState=1 and userid is not null
        )

        select c.comment_time, c.user_id, c.comment, c.rownum,
		ISNULL(pu.nickname,'匿名用户') as nickname, ISNULL(pu.photo,'') as photo,comment_reply
		from COM_CTE c
		left join (select u.nickname,u.userid,p.photo from IAM_USER u,IAM_USERPHOTO p
		where u.userid=p.userid
		) pu on pu.userid=c.user_id
		$page_condition_str
		order by comment_time desc
SQL;
        return self::nativeQuery($sql, $bind);
    }

    /**
     * 获取指定id的发现回复总数
     * @param $local_favour_id
     * @return mixed
     */
    public static function getDiscoveryCommentCount($local_favour_id)
    {
        $sql = 'select count(id) from LocalFavourComment where pId = :local_favour_id and isState=1 and userid is not null';
        $bind = array(
            'local_favour_id' => $local_favour_id
        );

        $result = self::fetchOne($sql, $bind, null, Db::FETCH_NUM);
        return $result[0];
    }
}