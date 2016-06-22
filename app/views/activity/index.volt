<!--活动入口页面-->
<div data-role="page" id="activitise_page" data-theme="a" style="padding-top: 95px;padding-bottom:51px;">
    <div data-role="header" class="cm-fixed" data-theme="g" style="padding-bottom: 0px;">
        <a class="ui-btn-left ui-btn ui-icon-md-chevron-left ui-btn-icon-notext" href="#"></a>
        <h1>本地惠-车友活动</h1>
        <a class="ui-btn-right ui-btn ui-icon-md-menu ui-btn-icon-notext" href="#menu"><i class="icon-reorder"></i></a>
        <div id="activity_navbar" data-role="navbar" class="" cellpadding="0" cellspacing="8">
            <ul>
                <li><a data-type-id="all" href="#activitise/all">全部</a></li>
                <li><a data-type-id="my" href="#activitise/my">我发布的</a></li>
            </ul>
        </div>
    </div>
    <div role="main" class="ui-content">
        <table id="activity_list_view_all" class="list_cd_box_div2" cellpadding="0" cellspacing="0">
            <tr>
                <td class="item-container">

                </td>
            </tr>
            <tr><td><div class="pager ui-btn ui-btn-a" style="display:none"><span>加载更多</span></div></td></tr>
        </table>
        <table id="activity_list_view_my" class="list_cd_box_div2" cellpadding="0" cellspacing="0">
            <tr>
                <td class="item-container">

                </td>
            </tr>
            <tr><td><div class="pager ui-btn ui-btn-a" style="display:none"><span>加载更多</span></div></td></tr>
        </table>
    </div>
    <div data-role="popup" id="activity_pub_popup" data-overlay-theme="g" data-theme="a" data-corners="true" data-enhance="false">
        <a data-rel="back" data-theme="j" class="ui-btn ui-corner-all ui-shadow ui-btn-a ui-icon-delete ui-btn-icon-notext ui-btn-right" style="background-color: #333; color: #FFF; border-color:#666; border-radius: 25em; position: absolute; right: -12px; top:-12px;"></a>
        <div id="activity_pub_form_container" style="background-color: #eee; border-radius: 0.5em; min-width:320px; float: left">
            <table class="fb_box" style="padding:10px">
                <tr class="fb_box_bt">
                    <td>标题：</td><td><input name="name" type="text" value=""/></td>
                </tr>
                <tr class="fb_box_bt">
                    <td>活动开始时间：</td>
                    <td><input name="start_date" type="text" value=""/></td>
                </tr>
                <tr class="fb_box_bt">
                    <td>活动结束时间：</td>
                    <td><input name="end_date" type="text" value=""/></td>
                </tr>
                <tr class="fb_box_bt">
                    <td>活动地点：</td>
                    <td><input name="place" type="text"/></td>
                </tr>
                <tr class="fb_box_bt">
                    <td>是否需要付款：</td>
                    <td>
                        <input type="radio" name="need_pay" value="0" checked/>否
                        <input type="radio" name="need_pay" value="1"/>是
                    </td>
                </tr>
                <tr class="fb_box_bt">
                    <td>内容：</td>
                </tr>
                <tr class="fb_box_bt">
                    <td colspan="2"><textarea name="contents"></textarea></td>
                </tr>
                <tr class="fb_box_bt">
                    <td colspan="2">
                        <button id="activity_pub_submit_btn" class="submit-btn" data-theme="g">提交</button>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <div data-role="footer" class="cm-fixed" style="background-color: rgba(0,0,0,0.2)">
        <a href="#" data-theme="g" style="padding:0.7em 0.5%;width: 99%;color:white" id="activity_pub_btn">我要发布</a>
    </div>
</div>