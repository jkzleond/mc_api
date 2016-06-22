<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width initial-scale=1.0 maximum-scale=1.0 user-scalable=no" />
	<title>中奖列表</title>
	<link rel="stylesheet" type="text/css" href="{{ url('/assets/temp/insuranceShare/css/css.css') }}" />
	<link rel="stylesheet" type="text/css" href="{{ url('/assets/temp/insuranceShare/css/ext_css.css?123') }}" />
</head>
<body class="body_zj ">
	<div>
		<img src="{{url('/assets/temp/insuranceShare/img/top.jpg')}}" width="100%" style="max-width: 100%; min-width: 320px;" />
	</div>
	<div  class="zj_div2">
	{% if win_list is empty %}
		<div class="zj_div_lb_div">
			<div class="zj_div_lb_div_top">
			</div>
			<div class="zj_div_lb_div_txt">
				<span>您还未抽中奖品</span>	
			</div>
		</div>
	{% else %}
		{% for win_award in win_list  %}
		<div class="zj_div_lb_div">
			<div class="zj_div_lb_div_top">
			</div>
			<div class="zj_div_lb_div_txt">
				<span>{{ win_award['name'] }}:{{ win_award['value'] }}</span><br />
				兑换码：{{ win_award['random_code'] }} 凭兑换码进行兑换
			</div>
			<div class="zj_div_lb_div_time">
				中奖时间：{{ win_award['win_date'] }}
			</div>
		</div>
		{% endfor %}
	{% endif %}
	</div>
</body>
</html>