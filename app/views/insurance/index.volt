<!--保险巨惠首页-->
<div data-role="page" id="insurance_page" data-theme="a" style="padding-top:51px">
    <div data-role="header" class="cm-fixed" data-theme="g">
        <a class="ui-btn-left ui-btn ui-icon-md-chevron-left ui-btn-icon-notext" href="#"></a>
        <h1>本地惠-保险巨惠</h1>
        <a class="ui-btn-right ui-btn ui-icon-md-menu ui-btn-icon-notext" href=""><i class="icon-reorder"></i></a>
    </div>
    <div role="main" class="ui-content">
        <form id="insurance_form" action="" onsubmit="return false;">
            <table style="width: 100%">
                <tr>
                    <th class="cm-span3">用车地</th>
                    <td>
                        <div class="ui-field-contain">
                            <div data-role="controlgroup" data-type="horizontal">
                                <select name="province_id" id="insurance_province_sel"></select>
                                <select name="city_id" id="insurance_city_sel"></select>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th class="cm-span3">车价</th>
                    <td>
                        <div class="ui-field-contain">
                            <div data-role="controlgroup" data-type="horizontal">
                                <input name="car_price" type="text" data-theme="a" data-wrapper-class="controlgroup-textinput ui-btn ui-btn-a cm-span3">
                                <button data-theme="a" style="min-width: 20px; width: 40px">万元</button>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th class="cm-span3">手机号</th>
                    <td>
                        <div class="ui-field-contain">
                            <div data-role="controlgroup" data-type="horizontal">
                                {% if user.phone is empty %}
                                {% set phone = '' %}
                                {% else %}
                                {% set phone = user.phone %}
                                {% endif %}
                                <input type="text" name="phone" data-theme="a" value="{{ phone }}"/>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th class="cm-span3">使用年限</th>
                    <td>
                        <div class="ui-field-contain">
                            <div data-role="controlgroup" data-type="horizontal">
                                <select name="service_year" data-theme="j">
                                    <option value="1" selected>1年以下</option>
                                    <option value="2">1-2年</option>
                                    <option value="3">2-3年</option>
                                    <option value="4">3-4年</option>
                                    <option value="5">4-6年</option>
                                    <option value="6">6年以上</option>
                                </select>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>险种</th>
                    <td>
                        <div class="ui-field-contain">
                            <div data-role="controlgroup" data-type="horizontal">
                                <select name="insurance_set_id">
                                    {% for insurance_set in insurance_sets %}
                                    <option value="{{ insurance_set.id }}" {% if insurance_set.id == '3' %}selected{% endif %}>{{ insurance_set.name }}</option>
                                    {% endfor %}
                                </select>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>较强险</th>
                    <td>
                        <div class="ui-field-contain">
                            <div data-role="controlgroup" data-type="horizontal">
                                <select name="compulsory_state_id"">
                                    {% for compulsory_state in compulsory_states %}
                                    <option value="{{ compulsory_state.id }}">{{ compulsory_state.status }}</option>
                                    {% endfor %}
                                </select>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div class="ui-field-contain">
                            <div data-role="controlgroup" data-type="horizontal">
                                <label for="insurance_damage">车辆损失险</label>
                                <input type="checkbox" name="damage" id="insurance_damage" data-theme="j" value="1" data-set="effective simple"/>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div class="ui-field-contain">
                            <div data-role="controlgroup" data-type="horizontal">
                                <label for="insurance_need_third">商业第三方责任险</label>
                                <input type="checkbox" name="need_third" id="insurance_need_third" data-set="effective simple skilled" value="1"/>
                                <select name="third">
                                    <option value="300000">300000元</option>
                                    <option value="50000">50000元</option>
                                    <option value="100000">100000元</option>
                                    <option value="150000">150000元</option>
                                    <option value="200000">200000元</option>
                                    <option value="500000">500000元</option>
                                    <option value="1000000">1000000元</option>
                                </select>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div class="ui-feild-contain">
                            <div data-role="controlgroup" data-type="horizontal">
                                <label for="insurance_need_driver">司机座椅责任险</label>
                                <input type="checkbox" name="need_driver" id="insurance_need_driver" value="1"/>
                                <input type="number" name="driver" min="1" value="1" data-wrapper-class="controlgroup-textinput ui-btn ui-btn-a cm-span3"/>
                                <button data-theme="a" style="min-width: 20px; width: 40px">万元</button>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="ui-feild-contain">
                            <div data-role="controlgroup" data-type="horizontal">
                                <label for="insurance_need_passenger">乘客座椅责任险</label>
                                <input type="checkbox" name="need_passenger" id="insurance_need_passenger" value="1"/>
                            </div>
                        </div>
                    </td>
                    <td style="text-align: center">
                        <div class="ui-feild-contain">
                            <div data-role="controlgroup" data-type="horizontal">
                                <select name="passenger_number">
                                    <option value="4">4</option>
                                    <option value="1">副驾驶</option>
                                </select>
                                <input type="number" name="passenger" min="1" value="1" data-wrapper-class="controlgroup-textinput ui-btn ui-btn-a cm-span2"/>
                                <button data-theme="a" style="min-width: 20px; width: 40px">万元</button>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div class="ui-feild-contain">
                            <div data-role="controlgroup" data-type="horizontal">
                                <label for="insurance_need_glass">玻璃单独破损险</label>
                                <input type="checkbox" name="need_glass" id="insurance_need_glass" value="1"/>
                                <select name="glass_id">
                                    <option value="1">国产</option>
                                    <option value="2">进口</option>
                                </select>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div class="ui-feild-contain">
                            <div data-role="controlgroup" data-type="horizontal">
                                <label for="insurance_robbery">全车盗抢</label>
                                <input type="checkbox" name="robbery" id="insurance_robbery" value="1"/>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div class="ui-feild-contain">
                            <div data-role="controlgroup" data-type="horizontal">
                                <label for="insurance_need_scratch">车身划痕险</label>
                                <input type="checkbox" name="need_scratch" id="insurance_need_scratch" value="1" data-set="effective"/>
                                <select name="scratch">
                                    <option value="2000">2000元</option>
                                    <option value="5000">5000元</option>
                                </select>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div class="ui-feild-contain">
                            <div data-role="controlgroup" data-type="horizontal">
                                <label for="insurance_self_ignition">自然损失险(需选择车损险)</label>
                                <input type="checkbox" name="self_ignition" id="insurance_self_ignition" value="1"/>
                            </div>
                        </div>
                </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div class="ui-feild-contain">
                            <div data-role="controlgroup" data-type="horizontal">
                                <label for="insurance_wading">涉水损失险</label>
                                <input type="checkbox" name="wading" id="insurance_wading" value="1"/>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div class="ui-feild-contain">
                            <div data-role="controlgroup" data-type="horizontal">
                                <label for="insurance_need_optional_deductible">可选免陪额</label>
                                <input type="checkbox" name="need_optional_deductible" id="insurance_need_optional_deductible" value="1"/>
                                <input type="number" name="optional_deductible" min="1" value="500" data-wrapper-class="controlgroup-textinput ui-btn ui-btn-a cm-span2"/>
                                <button data-theme="a" style="min-width: 20px; width: 40px">元</button>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div class="ui-feild-contain">
                            <div data-role="controlgroup" data-type="horizontal">
                                <label for="insurance_not_deductible">不计免赔率特约条款</label>
                                <input type="checkbox" name="not_deductible" id="insurance_not_deductible" value="1" data-set="effective simple skilled"/>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
            <div class="ui-field-contain">
                <button id="insurance_get_price_btn" data-theme="g">获得报价</button>
            </div>
        </form>
        {% if user.user_id == 'WEIBO_ACCOUNT' or user.user_id == 'INSURANCE_ACCOUNT' %}
        车友惠购险，可享最高4.9折优惠<br />
        下载车友惠成为会员，更可获得最高30%油卡返利。
        <div style="width:150px;margin:10px auto; text-align:center;line-height:40px; height:40px; font-size:16px; border-radius: 6px; background-color:#F2773B;">
            <a href="#" style="color:#ffffff;text-decoration:none">下载车友惠</a>
        </div>
        咨询热线400-009-0047转2<br />
        {% else %}
        <ul data-role="listview" data-theme="g">
            <li><a href="#insurance/list">查看订单</a></li>
            <li><a href="#act_list">活动资讯</a></li>
            <li><a href="#help/zhizhao">车险支招</a>
            <li><a href="#help/zhizhao">帮助</a>
            </li>
        </ul>
        <p>
        <div style="width:30%;float:left">咨询热线</div>
        <div style="width:65%;text-align:center;float:left">400-009-0047转2</div>
        </p>
        {% endif %}
    </div>
    <div id="insurance_first_result_popup" data-role="popup">
        <h1>初算结果</h1>
        <table>
            <tr>
                <th>初算标准保费</th>
                <td class="first-standard-result"></td>
            </tr>
            <tr>
                <th>预计车友惠出单价格</th>
                <td class="first-discount-result"></td>
            </tr>
            <tr>
                <td></td>
            </tr>
            <tr>
                <td></td>
            </tr>
            <tr>
                <td></td>
            </tr>
        </table>
    </div>
</div>