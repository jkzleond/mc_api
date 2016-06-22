<?php
class Adv extends ModelEx
{
	/**
	 * 获取首页广告
	 * @return object
	 */
	public static function getIndexAdv()
	{
		$sql = <<<SQL
		select
            top 1    
			id,pic as pic_data,url,provinceId,
			case clockType
			when 1 then 1
			when 4 then 2
			when 3 then 3
			when 2 then 4
			else 99
			end as priority
		from WelAdv
		where
		  case clockType
			when 4 then
			  case
				when datediff(mi,datename(yyyy, getdate()) + '-' + datename(mm, getdate()) + '-' + datename(dd, getdate()) + ' ' + repeatTime, getdate()) <= duration and datediff(mi,datename(yyyy, getdate()) + '-' + datename(mm, getdate()) + '-' + datename(dd, getdate()) + ' ' + repeatTime, getdate()) >= 0 then 1
				else 0
			  end
			when 3 then
			  case
				when datediff(mi,datename(yyyy, getdate()) + '-' + datename(mm, getdate()) + '-' + repeatTime, getdate()) <= duration and datediff(mi,datename(yyyy, getdate()) + '-' + datename(mm, getdate()) + '-' + repeatTime, getdate()) >= 0 then 1
				else 0
			  end
			when 2 then
			  case
				when datediff(mi,datename(yyyy, getdate()) + '-' + repeatTime, getdate()) <= duration and datediff(mi,datename(yyyy, getdate()) + '-' + repeatTime, getdate()) >= 0 then 1
				else 0
			  end
			when 1 then
			  case
				when startTime <= getdate() and endTime >= getdate() then 1
                when startTime <= getdate() and endTime is null then 1
				else 0
			  end
			else 1
		  end = 1 and isState = 1 and provinceId = 0
		order by priority asc, createTime desc
SQL;
		return self::fetchOne($sql);
	}
}