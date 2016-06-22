<?php
use \Phalcon\Db;

class Collection extends ModelEx
{
	/**
	 * 获取收藏数据列表
	 * @param  array|null $criteria
	 * @param  int|string     $page_num
	 * @param  int|string     $page_size
	 * @return array
	 */
	public static function getList(array $criteria=null, $page_num=null, $page_size)
	{
		$crt = new Criteria($criteria);

		$cte_condition_arr = array();
		$cte_condition_str = '';
		$page_condition_str = '';
		$bind = array();

		if($crt->user_id)
		{
			$cte_condition_arr[] = 'user_id = :user_id';
			$bind['user_id'] = $crt->user_id;
		}

		if(!empty($cte_condition_arr))
		{
			$cte_condition_str = 'where '.implode(' and ', $cte_condition_arr);
		}

		if($page_num)
		{
			$page_condition_str = 'where rownum between :from and :to';
			$bind['from'] = $page_size * ($page_num - 1) + 1;
			$bind['to'] = $page_size * $page_num;
		}

		$sql = <<<SQL
		with CTE as (
			select id, title, img, des, type, rel_id, user_id,
			row_number() over( order by create_date) as rownum 
			from Hui_Collection
			$cte_condition_str
		)
		select * from CTE
		$page_condition_str
SQL;
		//echo $sql; exit;
		return self::nativeQuery($sql, $bind);
	}

	/**
	 * 获取收藏数据总数
	 * @param  array|null $criteria
	 * @return int
	 */
	public static function getCount(array $criteria=null)
	{
		$crt = new Criteria($criteria);
		$condition_arr = array();
		$condition_str = '';
		$bind = array();

		if($crt->user_id)
		{
			$condition_arr[] = 'user_id = :user_id';
			$bind['user_id'] = $crt->user_id;
		}

		if(!empty($condition_arr))
		{
			$condition_str = 'where '.implode(' and ', $condition_arr);
		}

		$sql = "select count(1) from Hui_Collection $condition_str";

		$result = self::fetchOne($sql, $bind, null, Db::FETCH_NUM);
		return $result[0];
	}
}