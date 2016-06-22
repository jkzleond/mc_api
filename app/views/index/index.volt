{% extends 'base.volt' %}

{% block header_css %}
{{ super() }}
<link rel="stylesheet" type="text/css" href="{{ url('/assets/css/local_favour.css') }}"/>
<style type="text/css">
    .index-pic {
        width: 100%;
        height: 100%;
        position: fixed;
        z-index: 99999998;
    }
    .index-loading {
        width: 100%;
        bottom: 50px;
        text-align: center;
        font-size: 14px;
        font-weight: bold;
        position: fixed;
        z-index: 99999999;
    }
</style>
{% endblock %}

{% block header_js %}
{{ super() }}
{% endblock %}

{% block content %}

<!-- 开屏图片 -->
<div class="index-pic">    
    <img class="index-pic" src="data:image/png;base64,{{ index_pic.pic_data }}" alt="">
    <div class="index-loading">
        <img src="{{ url('/assets/img/mini-loading.gif') }}" style="display:inline-block;">
        <span>正在很努力的加载中,请稍候...</span>
    </div>
</div>

<!-- 首页 -->
<div data-role="page" id="home_page" data-theme="g" style="padding-top: 51px;">
    <div data-role="header" class="cm-fixed">
        <a class="ui-btn-left ui-btn ui-icon-md-chevron-left ui-btn-icon-notext" href=""></a>
        <h1>本地惠</h1>
        <a class="ui-btn-right ui-btn ui-icon-md-menu ui-btn-icon-notext" href=""><i class="icon-reorder"></i></a>
    </div>
    <div role="main" class="ui-content">
        <table class="sign_in_div" cellpadding="0" cellspacing="0">
            <tr>
                <td id="user_state">
                    <div class="sign_in_div_tp">
                        <img class="user-avatar" src="{{ url('/assets/img/DefaultFace.png') }}" style="width:100%; height: 100%; min-width: 60px;" />
                    </div>
                    <div class="sign_in_div_name_box">
                        <div class="sign_in_div_name">
                            <span>用户名：</span><span class="user-uname"></span></div>
                        <div class="sign_in_div_jb">
                            <span class="user-huigold"></span><span class=""></span></div>
                    </div>
                    <div class="sign_in_div_bt_box">
                        <div class="sign_in_div_bt clock-in-btn">我要签到</div>
                    </div>
                    <table class="sign_in_div_rq_box" cellpadding="0" cellspacing="0" align="center">
                        <tr>
                            <td>
                                <div class="clock-in-state clock-in-checked">
                                    1</div>
                            </td>
                            <td>--</td>
                            <td>
                                <div class="clock-in-state">
                                    2</div>
                            </td>
                            <td>--</td>
                            <td>
                                <div class="clock-in-state">
                                    3</div>
                            </td>
                            <td>--</td>
                            <td>
                                <div class="clock-in-state">
                                    4</div>
                            </td>
                            <td>--</td>
                            <td>
                                <div class="clock-in-state">
                                    5</div>
                            </td>
                            <td>--</td>
                            <td>
                                <div class="clock-in-state">
                                    6</div>
                            </td>
                            <td>--</td>
                            <td>
                                <div class="clock-in-state">
                                    7</div>
                            </td>
                            <td><span>天</span></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <table class="gn_div_box" cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    <div class="gn_div_box_div">
                        <a href="#">
                            <img src="{{ url('/assets/img/c1.png') }}" width="100%" style="max-width: 70px; min-width: 30px;" />
                        </a>
                        <a href="#collection/list"><img src="{{ url('/assets/img/c2.png') }}" width="100%" style="max-width: 70px; min-width: 30px;" />
                        </a>
                    </div>
                </td>
            </tr>
        </table>
        <table class="gn_cd_box_div" cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    <a href="#insurance"><img src="{{ url('/assets/img/a1.png') }}" style="max-width: 70px; min-width: 70px;" /></a>
                </td>
                <td>
                    <a href="#discoverise"><img src="{{ url('/assets/img/a2.png') }}" style="max-width: 70px; min-width: 70px;" /></a>
                </td>
                <td>
                    <a href="#tours"><img src="{{ url('/assets/img/a3.png') }}" style="max-width: 70px; min-width: 70px;" /></a>
                </td>
            </tr>
            <tr>
                <td>
                    <a href="#activitise"><img src="{{ url('/assets/img/a4.png') }}" style="max-width: 70px; min-width: 70px;" /></a>
                </td>
                <td>
                    <a href="#dev"><img src="{{ url('/assets/img/a5.png') }}" style="max-width: 70px; min-width: 70px;" /></a>
                </td>
                <td>
                    <a href="#dev"><img src="{{ url('/assets/img/a6.png') }}" style="max-width: 70px; min-width: 70px;" /></a>
                </td>
            </tr>
        </table>
        <table class="ad_box_div" cellpadding="0" cellspacing="10">
            <tr>
                <td>
                    <a href="http://www.baidu.com"><img src="{{ url('/assets/img/ad1.png') }}" style="width:100%; max-width: 100%; min-width: 100px;" /></a>
                </td>
                <td>
                    <a href="#"><img src="{{ url('/assets/img/ad2.png') }}" style="width:100%; max-width: 100%; min-width: 100px;" /></a>
                </td>
            </tr>
        </table>
    </div>
</div>

<!-- 发现页面 -->
<div data-role="page" id="discovery_page" data-theme="a" style="padding-top: 95px;">
    <div data-role="header" class="cm-fixed" data-theme="g" style="padding-bottom: 0px;">
        <a class="ui-btn-left ui-btn ui-icon-md-chevron-left ui-btn-icon-notext" href="#"></a>
        <h1>本地惠-发现</h1>
        <a class="ui-btn-right ui-btn ui-icon-md-menu ui-btn-icon-notext" href="#menu"><i class="icon-reorder"></i></a>
        <div id="discovery_navbar" data-role="navbar" class="" cellpadding="0" cellspacing="8">
            <ul>
                <li><a data-type-id="0" href="#discoverise" data-rel="#discovery_list_view_0">全部</a></li>
                <li><a data-type-id="6" href="#discoverise/6" data-rel="#discovery_list_view_6">车友惠</a></li>
                <li><a data-type-id="1" href="#discoverise/1" data-rel="#discovery_list_view_1">生活服务</a></li>
                <li><a data-type-id="7" href="#discoverise/7" data-rel="#discovery_list_view_7">行车攻略</a></li>
            </ul>
        </div>
    </div>
    <div role="main" class="ui-content">
        <div id="discovery_list_view_0" style="display: none">
            <ul class="cm-list discovery-list" cellpadding="0" cellspacing="0">

            </ul>
        </div>
        <div id="discovery_list_view_6" style="display: none">
            <ul class="cm-list discovery-list" cellpadding="0" cellspacing="0">

            </ul>
        </div>
        <div id="discovery_list_view_1" style="display: none">
            <ul class="cm-list discovery-list" cellpadding="0" cellspacing="0">

            </ul>
        </div>
        <div id="discovery_list_view_7" style="display: none">
            <ul class="cm-list discovery-list" cellpadding="0" cellspacing="0">

            </ul>
        </div>
    </div>
</div>

<!-- 发现详情页面 -->
<div data-role="page" id="discovery_detail_page" data-theme="a" style="padding: 51px 0px;">
    <div data-role="header" class="cm-fixed" data-theme="g">
        <a class="ui-btn-left ui-btn ui-icon-md-chevron-left ui-btn-icon-notext" href="#" onclick="window.history.back();return false;"></a>
        <h1>本地惠-发现详情</h1>
        <a class="ui-btn-right ui-btn ui-icon-md-menu ui-btn-icon-notext" href="#menu"><i class="icon-reorder"></i></a>
    </div>
    <div role="main" class="ui-content">
        <div id="discovery_content" style="padding: 5px 2%; width: 96%"></div>
        <div id="discovery_comments_container" style="padding:5px;">
            <div class="cm-flex-row">
                <div class="cm-flex1">
                    <span class="cm-bg-prim cm-radius-s" style="float:left; color:#FFF">热门评论(<i id="discovery_comments_num">0</i>)</span>
                </div>
                <div class="cm-flex1">
                    <span style="float:right"><a id="discovery_comments_more" href="#discovery/<%=id%>/comments">更多>></a></span>
                </div>
            </div>
            <div class="item-container"></div>
        </div>
        <div data-role="popup" id="discovery_add_comment_popup" data-overlay-theme="g" data-theme="a" data-corners="true" data-enhance="false">
            <a data-rel="back" data-theme="j" class="ui-btn ui-corner-all ui-shadow ui-btn-a ui-icon-delete ui-btn-icon-notext ui-btn-right" style="background-color: #333; color: #FFF; border-color:#666; border-radius: 25em; position: absolute; right: -12px; top:-12px;"></a>
            <div id="discovery_comment_form_container" style="background-color: #eee; border-radius: 0.5em; min-width:320px; float: left">
                <table class="fb_box" style="padding:10px">
                    <tr class="fb_box_bt">
                        <td colspan="2"><textarea name="contents" class="content" maxlength="100"></textarea></td>
                    </tr>
                    <tr class="fb_box_bt">
                        <td><span class="char-num">100</span></td>
                    </tr>
                    <tr class="fb_box_bt">
                        <td colspan="2">
                            <button class="submit" data-theme="g" disabled>发表</button>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div data-role="footer" class="cm-fixed" style="background-color: rgba(0,0,0,0.8)">
        <a id="discovery_add_comment_btn" class="cm-btn cm-white" style="padding:0.7em 0.5em;color:deepskyblue">添加评论</a>
        <a id="discovery_share_btn" class="cm-btn cm-white cm-right" style="padding:0.7em 0.5em;color:deepskyblue">分享</a>
        <a id="discovery_collect_btn" class="cm-btn cm-white cm-right" style="padding:0.7em 0.5em;color:deepskyblue">收藏</a>
    </div>
</div>

{% endblock %}

{% block footer_js %}

{% endblock %}
