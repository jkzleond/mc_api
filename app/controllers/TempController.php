<?php
use \Phalcon\Db;

class TempController extends ControllerBase
{
	//微信appid,secret
	private $_app_id = 'wx1f42c4cb56c5095d';
	private $_app_secret = '276e08a1e2d2c9680823e6ddd0720c4c';

	private $_lock_time = null;

	public function initialize()
	{
		ini_set('display_errors', 0);

		//解决微信andriod版两次请求的并发问题,使用文件所
		//文件名称为微信code+.lock,两次请求使用同一个code
		$lock_time = $this->request->get('lock_time', null, null);

		if($lock_time)
		{
			$lock_file = $lock_time.'.lock';

			while(file_exists($lock_file))
			{
				
			}

			touch($lock_file);
		}
	}

	public function afterExecuteRoute()
	{
		//解除锁
		$lock_time = $this->request->get('lock_time', null, null);
		$lock_file = $lock_time.'.lock';

		if($lock_time and file_exists($lock_file))
		{
			unlink($lock_file);
		}
	}

	public function insuranceShareDescribeAction()
	{
		$p_user_phone = $this->request->get('p_user_phone', null, '0');

		$user_id = $this->request->get('userId', null, null);
		$client_type = $this->request->get('clientType', null, null);

		file_put_contents('des.log', var_export($_GET, 1));

		$this->view->setVar('user_id', $user_id);
		if($user_id)
		{
			$user = User::getUserInfoById($user_id);
			$this->view->setVar('is_in_car_mate', true);
		}
		else
		{
			$this->view->setVar('is_in_car_mate', false);
		}

		if(!empty($user))
		{
			$this->view->setVar('user_phone', $user['phone']);
		}
		else
		{
			$this->view->setVar('user_phone', false);
		}

		$this->view->setVar('p_user_phone', $p_user_phone);
	}

	/**
	 * 车险20免一, 分享
	 */
	public function insuranceShareAction()
	{
		$user_agent = $this->request->getUserAgent();
		$is_in_car_mate = strpos($user_agent, 'YN122') !== false;
		$location_url = $this->request->get('location_url', null, null);
		//不在车优惠环境并且存在跳转参数,则跳转
		if(!$is_in_car_mate and $location_url)
		{
			$location_url = base64_decode($location_url);
			return $this->response->redirect($location_url);
		}

		$this->view->setVar('is_in_car_mate', $is_in_car_mate);

		$p_user_phone = $this->dispatcher->getParam('p_user_phone', null, '0');

		$user_phone = $this->request->get('user_phone', null, null);
		
		$this->view->setVar('p_user_phone', $p_user_phone);
		$this->view->setVar('is_user', true);

		$p_user_id = null;
		if($p_user_phone !== '0')
		{
			$p_user = User::getUserByPhone($p_user_phone);
			$p_user_id = $p_user['user_id'];
		}

		$wx_state = $this->request->get('state', null, false);
		$user_agent = $this->request->getUserAgent();
		$is_wx = strpos($user_agent, 'MicroMessenger') !== false;
		$this->view->setVar('is_wx', $is_wx);

		$wx_userinfo_json = $this->cookies->get('wx_userinfo_json')->getValue('trim');
		$wx_userinfo = json_decode($wx_userinfo_json, true);
		file_put_contents('wx_userinfo.log', '['.microtime(true).']'.var_export($wx_userinfo_json, 1)."\r\n", FILE_APPEND);
		
		//使用微信客户端访问,并且不是从授权页面跳转过来的(跳转过来都带state),重定向到授权页面
		if($is_wx and !$wx_state and !$wx_userinfo)
		{
			$auth_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$this->_app_id.'&redirect_uri='.urlencode('http://ip.yn122.net:8092/insurance_share/'.$p_user_phone.'?lock_time='.floor(microtime(true)*100)).'&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect';
			return $this->response->redirect($auth_url);
		}

		$this->view->setVar('wx_state', $wx_state);

		$wx_code = $this->request->get('code', null, null);
		$wx_openid = $this->request->get('wx_openid', null, null);
		$wx_unionid = $this->request->get('wx_unionid', null, null);
		$wx_token = null;

		$db = $this->db;

		if($is_wx and $wx_state and !$user_phone)
		{

			if($wx_code)
			{
				if(!$wx_userinfo)
				{
					$wx_token_json = file_get_contents('https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$this->_app_id.'&secret='.$this->_app_secret.'&code='.$wx_code.'&grant_type=authorization_code');
					$wx_token = json_decode($wx_token_json, true);
					

					$wx_userinfo_json = file_get_contents('https://api.weixin.qq.com/sns/userinfo?access_token='.$wx_token['access_token'].'&openid='.$wx_token['openid'].'&lang=zh_CN');
					$wx_userinfo = json_decode($wx_userinfo_json, true);

					file_put_contents('wx_userinfo.log', '[pull_userinfo]'.var_export($wx_userinfo, 1)."\r\n", FILE_APPEND);

					//如果获取用户信息失败,则重新获取code授权
					if(empty($wx_userinfo) or !isset($wx_userinfo['openid']) )
					{
						file_put_contents('wx_userinfo.log', "[re_auth]\r\n", FILE_APPEND);
						$auth_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$this->_app_id.'&redirect_uri='.urlencode('http://ip.yn122.net:8092/insurance_share/'.$p_user_phone.'?lock_time='.floor(microtime(true)*100)).'&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect';
						return $this->response->redirect($auth_url);
					}

					$this->cookies->set('wx_userinfo_json', $wx_userinfo_json);
				}
				
				//保存微信用户信息
				
				$get_wx_user_sql = 'select top 1 id from WX_USER where openid = :openid';
				$get_wx_user_bind = array('openid' => $wx_userinfo['openid']);
				$wx_user_result = $db->query($get_wx_user_sql, $get_wx_user_bind);
				$wx_user_result->setFetchMode(Db::FETCH_ASSOC);
				$wx_user = $wx_user_result->fetch();

				$wx_user_id = !empty($wx_user) ? $wx_user['id'] : null;

				//没有此微信用户记录则添加
				
				if(!$wx_user_id)
				{
					$insert_wx_user_sql = 'insert into WX_USER (openid, nickname, sex, province, city, country, headimgurl,privilege, unionid) values (:openid, :nickname, :sex, :province, :city, :country, :headimgurl, :privilege, :unionid)';
					$insert_wx_user_bind = array(
						'openid' => $wx_userinfo['openid'],
						'nickname' => base64_encode($wx_userinfo['nickname']),
						'sex' => $wx_userinfo['sex'],
						'province' => $wx_userinfo['province'],
						'city' => $wx_userinfo['city'],
						'country' => $wx_userinfo['country'],
						'headimgurl' => $wx_userinfo['headimgurl'],
						'privilege' => json_encode($wx_userinfo['privilege']),
						'unionid' => isset($wx_userinfo['unionid']) ? $wx_userinfo['unionid'] : null
					);

					$db->execute($insert_wx_user_sql, $insert_wx_user_bind);
					$wx_user_id = $db->lastInsertId();
				}

				$get_view_sql = 'select top 1 id from Hui_ActivityShareView where wx_user_id = :wx_user_id and p_user_id = :p_user_id and aid = :aid';
				$get_view_bind = array(
					'wx_user_id' => $wx_user_id,
					'p_user_id' => $p_user_id,
					'aid' => 228
				);
				$view_result = $db->query($get_view_sql, $get_view_bind);
				$view_result->setFetchMode(Db::FETCH_ASSOC);
				$view_record = $view_result->fetch();

				if($wx_user_id and empty($view_record))
				{
					//添加微信用户访问记录(本次活动)
					$insert_view_sql = 'insert into Hui_ActivityShareView (p_user_id, wx_user_id, aid) values (:p_user_id, :wx_user_id, :aid)';
					$insert_view_bind = array(
						'p_user_id' => $p_user_id,
						'wx_user_id' => $wx_user_id,
						'aid' => 228
					);
					$insert_view_success = $db->execute($insert_view_sql, $insert_view_bind);
				}
			}

			$this->view->setVar('wx_openid', $wx_token['openid']);
		}

		$bind_user = null;

		if($wx_userinfo)
		{
			$bind_user_list = User::getUserList(array(
				'wx_openid' => isset($wx_userinfo['openid']) ? $wx_userinfo['openid'] : 'cyh' //避免wx_openid为null时,取到所有用户
			));

			if(!empty($bind_user_list))
			{
				$bind_user = $bind_user_list[0];
			}
		}

		if(!$user_phone and !$bind_user)
		{
			
			
			//查找上家分享码
			$query_sql = 'select invitation_code from ActivityUser where userid = :user_id and aid = :aid';
			$query_bind = array(
				'user_id' => $p_user_id,
				'aid' => 228
			);
			$query_result = $db->query($query_sql, $query_bind);
			$query_result->setFetchMode(Db::FETCH_ASSOC);
			$involved_p_user = $query_result->fetch();
			$this->view->setVar('invitation_code', !empty($involved_p_user) ? $involved_p_user['invitation_code'] : '');
			return;
		}

		$user = !empty($bind_user) ? $bind_user : User::getUserByPhone($user_phone);

		//未注册的用户
		if(empty($user))
		{	
			$this->view->setVar('is_user', false);

			$client_type = null;

			if( strpos($user_agent, 'iPhone') !== false )
			{
				$client_type = 'iPhone';
			}
			elseif( strpos($user_agent, 'iPod') !== false )
			{
				$client_type = 'iPod';
			}
			elseif( strpos($user_agent, 'iPad') !== false )
			{
				$client_type = 'iPad';
			}
			elseif( strpos($user_agent, 'Android') !== false )
			{
				$client_type = 'Android';
			}

			$register_result = file_get_contents('http://192.168.3.31/vehIllegalQuery/index.php?mod=Member&act=RegisterSave&PWD='.$user_phone.'&PHONE='.$user_phone.'&clientType='.$client_type);

			$user = User::getUserByPhone($user_phone);
			
			$this->view->setVar('car_mate_user_phone', $user_phone);
			$this->view->setVar('car_mate_pwd', $user_phone);
		}

		$this->view->setVar('user_id', $user['user_id']);
		
		//如果用户没绑定,则绑定(微信客户端访问页面时)
		if($is_wx and !$bind_user)
		{
			$bind_user_sql = 'update IAM_USER set weixintoken = :wx_openid, wx_openid = :wx_openid where userid = :user_id';
			$bind_user_bind = array(
				'wx_openid' => $wx_userinfo['openid'],
				'user_id' => $user['user_id']
			);
			$bind_user_success = $db->execute($bind_user_sql, $bind_user_bind);
		}

		$query_sql = 'select invitation_code from ActivityUser where userid = :user_id and aid = :aid';
		$query_bind = array(
			'user_id' => $user['user_id'],
			'aid' => 228
		);
		$query_result = $db->query($query_sql, $query_bind);
		$query_result->setFetchMode(Db::FETCH_ASSOC);
		$involved_user = $query_result->fetch();
		$is_already = !empty($involved_user);

		$this->view->setVar('is_already', $is_already);

		if($is_already)
		{
			//在微信客户端访问则进入过此页面的微信用户信息
			if($is_wx)
			{
				$get_view_sql = <<<SQL
				select u.nickname, u.headimgurl, convert(varchar(20), v.create_date, 20) as create_date from Hui_ActivityShareView v
				left join WX_USER u on u.id = v.wx_user_id
				where v.wx_user_id is not null and v.p_user_id = :p_user_id and v.aid = :aid
SQL;
				$get_view_bind = array(
					'p_user_id' => $user['user_id'],
					'aid' => 228
				);
				
				$record_result = $db->query($get_view_sql, $get_view_bind);
				$record_result->setFetchMode(Db::FETCH_ASSOC);
				$record_list = $record_result->fetchAll();
				$this->view->setVar('view_record_list', $record_list);
			}

			if($p_user_id)
			{
				$this->flashSession->success('您也获得了邀请码哦！<br/>可以点击右上角分享给您的好友，也可以将邀请码告知您的好友，在保险精算时填写邀请码！如有疑问请<a href="tel:400-009-0047">拨打服务热线</a>或<a href="http://wpa.qq.com/msgrd?v=3&uin=1011973383&site=qq&menu=yes">加客服QQ</a>联系我们');
			}
			else
			{
				$this->flashSession->success('您已成功参加活动<br/>可以点击右上角分享给您的好友，也可以将邀请码告知您的好友，在保险精算时填写邀请码！<br/>成功邀请<b style="color:orange">20</b>个好友购买保险，您的车险就可以免单啦！如有疑问请<a href="tel:400-009-0047">拨打服务热线</a>或<a href="http://wpa.qq.com/msgrd?v=3&uin=1011973383&site=qq&menu=yes">加客服QQ</a>联系我们');
			}
			$this->view->setVar('invitation_code', $involved_user['invitation_code']);
			$this->view->setVar('p_user_phone', $user['phone']);
			return;
		}

		$invitation_code = strtoupper((str_pad(dechex($user['id']), 5, '0', STR_PAD_LEFT)));

		$insert_au_sql = 'insert into ActivityUser(userid, aid, p_user_id, invitation_code) values (:user_id, :aid, :p_user_id, :invitation_code)';
		$insert_au_bind = array(
			'user_id' => $user['user_id'],
			'aid' => 228,
			'p_user_id' => $p_user_id,
			'invitation_code' => $invitation_code
		);					
		$insert_au_success = $db->execute($insert_au_sql, $insert_au_bind);


		if($p_user_id)
		{
			$this->flashSession->success('您也获得了邀请码哦！<br/> 可以点击右上角分享给您的好友，也可以将邀请码告知您的好友，在保险精算时填写邀请码！如有疑问请<a href="tel:400-009-0047">拨打服务热线</a>或<a href="http://wpa.qq.com/msgrd?v=3&uin=1011973383&site=qq&menu=yes">加客服QQ</a>联系我们');
		}
		else
		{
			$this->flashSession->success('您已成功参加活动<br/>可以点击右上角分享给您的好友，也可以将邀请码告知您的好友，在保险精算时填写邀请码！<br/>成功邀请<b style="color:orange">20</b>个好友购买保险，您的车险就可以免单啦！如有疑问请<a href="tel:400-009-0047">拨打服务热线</a>或<a href="http://wpa.qq.com/msgrd?v=3&uin=1011973383&site=qq&menu=yes">加客服QQ</a>联系我们');
		}
		$this->view->setVar('invitation_code', $invitation_code);
		$this->view->setVar('p_user_phone', $user['phone']);
		$this->view->setVar('is_success', true);
	}

	/**
	 * 车险20免一, 抽奖
	 */
	public function insuranceShareDrawAction($aid)
	{
		//$this->view->disable();
		$user = User::getCurrentUser();
		$chance = Activity::getDrawChance($aid, $user['user_id']); //获取抽奖机会
		$is_certain = $this->request->get('is_certain', null, false); //是否花费20次抽奖机会必中
		$this->view->setVar('chance', $chance);
		$this->view->setVar('session_id', $this->session->getSessionId);

		if($chance == 0)
		{
			return;
		}

		//必中必须花费20次抽奖机会,机会不够则直接返回
		if($is_certain and $chance < 20)
		{
			return;
		}

		$db = $this->db;
		//验证是否再开奖时段
		
		$cur_time = date('H:i');
		
		$valid_time_sql = <<<SQL
		select top 1 a.is_period, dp.id as period_id from Activity a
		left join Hui_DrawPeriod dp on dp.aid = a.id
		where ( 
				datediff(n, start_time, :cur_time) >= 0 and datediff(n, :cur_time, end_time) >= 0 or is_period = 0
			  ) and aid = :aid
SQL;
		$valid_time_bind = array(
			'cur_time' => $cur_time,
			'aid' => $aid
		);

		$valid_time_result = $db->query($valid_time_sql, $valid_time_bind);
		$valid_time_result->setFetchMode(Db::FETCH_ASSOC);
		$valid_time = $valid_time_result->fetch();
		
		$is_on_time = !empty($valid_time);

		$this->view->setVar('is_on_time', $is_on_time);

		if(!$is_on_time)
		{
			//不在抽奖时段,则取最近开始时间
			$nearest_time_sql = <<<SQL
			select min(start_time) from Hui_DrawPeriod where aid = :aid and start_time > :cur_time
SQL;
			$nearest_time_bind = array(
				'aid' => $aid,
				'cur_time' => $cur_time
			);

			$nearest_time_result = $db->query($nearest_time_sql, $nearest_time_bind);
			$nearest_time_assoc = $nearest_time_result->fetch();
			$nearest_time = $nearest_time_assoc['start_time'];
			$this->view->setVar('nearest_time', date('H:i', $nearest_time) );
		}
		else
		{
			//在抽奖时段则开始抽奖
			
			$minus = $is_certain ? 20 : 1;
			//减少抽奖机会
			$minus_chance_sql = <<<SQL
			update AwardChance set chance = chance - $minus, updateDate = getdate() where userid = :user_id and aid = :aid
			and chance > 0
SQL;
			$minus_chance_bind = array(
				'user_id' => $user['user_id'],
				'aid' => $aid
			);
			$minus_chance_success = $db->execute($minus_chance_sql, $minus_chance_bind);

			$period_id = isset($valid_time['period_id']) ? $valid_time['period_id'] : null;
			$is_period = $valid_time['is_period'];

			$award_rand = 10000;

			if(!$is_certain)
			{
				mt_srand(time()); //以时间戳做随机种子
				$award_rand = mt_rand(1, 10000); //计算随机数
			}
			else
			{
				$award_rand = 0; //必中
			}

			//查询中奖几率大于随机数的奖品
			$get_award_sql = <<<SQL
			select a.id, a.rate, a.name, a.pic, a.dayLimit as day_limit from Award a
			left join Hui_DrawToAward d2a on d2a.award_id = a.id
			where a.rate >= :rate and ( :is_period = 0 or d2a.period_id = :period_id) and a.aid = :aid
SQL;
			$get_award_bind = array(
				'rate' => $award_rand,
				'is_period' => $is_period,
				'period_id' => $period_id,
				'aid' => $aid
			);
			
			$award_result = $db->query($get_award_sql, $get_award_bind);
			$award_result->setFetchMode(Db::FETCH_ASSOC);
			$award_list = $award_result->fetchAll();
			if(empty($award_list))
			{
				$this->view->setVar('is_bingo', false);
			}
			else
			{
				$this->view->setVar('is_bingo', true);

				$rand_index = array_rand($award_list);

				$award = $award_list[$rand_index];

				//计算单日中人奖数
				$bingo_num_sql = <<<SQL
				select count(1) from AwardGain where awid = :award_id and aid = :aid
				and datediff(d, winDate, getdate()) = 0
SQL;
				$bingo_num_bind = array(
					'award_id' => $award['id'],
					'aid' => $aid
				);
				$bingo_num_result = $db->query($bingo_num_sql, $bingo_num_bind);
				$bingo_num_result->setFetchMode(Db::FETCH_NUM);
				$bingo_num_row = $bingo_num_result->fetch();
				$bingo_num = $bingo_num_row[0];
				
				if($bingo_num >= $award['day_limit'])
				{
					//中奖人数达到奖品单日限额
					$this->view->setVar('is_bingo', false);
					return;
				}
				else
				{
					//未达到单日限额, 处理中奖
					do
					{
						//计算6位随机码
						$code_str = '';

						for($i = 0; $i < 6; $i++)
						{
							//利用ascii码表
							$rand_ord = rand(48, 122);

							//处理不是数字或字母的ord
							if($rand_ord > 57 and $rand_ord < 65)
							{
								$rand_ord = 51;
							}	
							elseif($rand_ord > 90 and $rand_ord < 97)
							{
								$rand_ord = 101;
							}
							elseif($rand_ord == 48 or $rand_ord == 111)
							{
								//把0和o换成q
								$rand_ord = 113;
							}
							elseif($rand_ord == 79 )
							{
								//把O换成Q
								$rand_ord = 81;
							}

							$code_str .= chr($rand_ord);
						}

						$bingo_sql = <<<SQL
						insert into AwardGain (aid, awid, userid, winDate, randomCode) values (:aid, :award_id, :user_id, getdate(), :code)
SQL;
						$bingo_bind = array(
							'aid' => $aid,
							'award_id' => $award['id'],
							'user_id' => $user['user_id'],
							'code' => $code_str
						);

						$bingo_success = $db->execute($bingo_sql, $bingo_bind);
						$err_info = $db->getInternalHandler()->errorInfo();
					}
					while($err_info[1] == '2672'); //领取码重复则重新计算领取码后insert
					
					$this->view->setVar('award', $award);
				}
			}
		}
	}

	/**
	 * 中奖列表页面
	 */
	public function winListAction($aid)
	{
		$user = User::getCurrentUser();
		$db = $this->db;

		$get_win_list_sql = <<<SQL
		select a.name, a.value, convert(varchar(20), ag.winDate, 20) as win_date, ag.randomCode as random_code from AwardGain ag
		left join Award a on a.id = ag.awid
		where ag.aid = :aid and ag.userid = :user_id
SQL;
		$get_win_list_bind = array(
			'aid' => $aid,
			'user_id' => $user['user_id']
		);

		$win_list_result = $db->query($get_win_list_sql, $get_win_list_bind);
		$win_list_result->setFetchMode(Db::FETCH_ASSOC);
		$win_list = $win_list_result->fetchAll();

		$this->view->setVar('win_list', $win_list);
	}
}