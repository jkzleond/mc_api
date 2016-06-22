<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 15-6-24
 * Time: 下午4:54
 */

use \Phalcon\Db;

class Insurance extends ModelEx
{
    /**
     * 获取保险套餐列表
     * @return array
     */
    public static function getInsuranceSetList()
    {
        $sql = 'select id, name, optionsList as options_list from Insurance_Set';
        return self::nativeQuery($sql, null, null, Db::FETCH_OBJ);
    }

    /**
     * 添加保险信息
     * @param array $insurance_info
     * @return bool|int
     */
    public static function addInsuranceInfo(array $insurance_info)
    {
        $crt = new Criteria($insurance_info);
        $sql = <<<SQL
        insert into Insurance_Info (
        user_userId, carNo_id, carType_id, insuranceParam_id, insuranceResult_id, state_id,   userName, phoneNo, emailAddr, buy_id, address_id, finalParam_id, finalResult_id, insuranceNo,insuranceSetId, sfzh, failureReason, giftMoney, issuingTime, actulAmount, preferenceItems
        ) values (
        :user_id, :car_info_id, :car_type_id, :insurance_param_id, :insurance_result_id, :state_id, :user_name, :phone_no, :email_addr, :buy_id, :address_id, :final_param_id, :final_result_id, :insurance_no, :insurance_set_id, :sfzh, :failure_reason, :gift_money, :issuing_time, :actul_amount, :preference_items
        )
SQL;
        $bind = array(
            'user_id' => $crt->user_id, 
            'car_info_id' => $crt->car_info_id, 
            'car_type_id' => $crt->car_type_id, 
            'insurance_param_id' => $crt->insurance_param_id, 
            'insurance_result_id' => $crt->insurance_result_id, 
            'state_id' => $crt->state_id, 
            'user_name' => $crt->user_name, 
            'phone_no' => $crt->phone_no,
            'email_addr' => $crt->email_addr, 
            'buy_id' => $crt->buy_id, 
            'address_id' => $crt->address_id, 
            'final_param_id' => $crt->final_param_id, 
            'final_result_id' => $crt->final_result_id, 
            'insurance_no' => $crt->insurance_no, 
            'insurance_set_id' => $crt->insurance_set_id, 
            'sfzh' => $crt->sfzh,
            'failure_reason' => $crt->failure_reason,
            'gift_money' => $crt->gift_money, 
            'issuing_time' => $crt->issuing_time, 
            'actul_amount' => $crt->actul_amount, 
            'preference_items' => $crt->preference_items
        );

        $success = self::nativeExecute($sql, $bind);

        if(!$success) return false;

        $connection = self::_getConnection();

        return $connection->lastInsertId();
    }

    /**
     * 添加保险参数
     * @param array $insurance_param
     * @return bool|int
     */
    public static function addInsuranceParam(array $insurance_param)
    {
        $param_crt = new Criteria($insurance_param);

        $sql = <<<SQL
        insert into Insurance_Param (carPrice, carSeat, firstYear, firstMonth, insuranceYear, insuranceMonth, compulsory_id, damage_id, third, driver, passenger, robbery_id, glass_id, optionalDeductible, notDeductible_id, newDevice, goods, offshore, ton, scratch, selfIgnition_id, discount_companyId, tax, displacement, serviceYear, level) values (
        :car_price, :car_seat, :first_year, :first_month, :insurance_year, :insurance_month,
        :compulsory_id, :damage_id, :third, :driver, :passenger, :robbery_id, :glass_id,
        :optional_deductible, :not_deductible_id, :new_device, :goods, :offshore, :ton, :scratch, :self_ignition_id, :discount_company_id, :tax, :displacement, :service_year, :level
        )
SQL;
        $bind = array(
            'car_price' => $param_crt->car_price,
            'car_seat' => $param_crt->car_seat,
            'first_year' => $param_crt->first_year,
            'first_month' => $param_crt->first_month,
            'insurance_year' => $param_crt->insurance_year,
            'insurance_month' => $param_crt->insurance_month,
            'compulsory_id' => $param_crt->compulsory_state_id,
            'damage_id' => $param_crt->damage,
            'third' => $param_crt->third,
            'driver' => $param_crt->driver,
            'passenger' => $param_crt->passenger,
            'robbery_id' => $param_crt->robbery,
            'glass_id' => $param_crt->glass_id,
            'optional_deductible' => $param_crt->optional_deductible,
            'not_deductible_id' => $param_crt->not_deductible,
            'new_device' => $param_crt->new_device,
            'goods' => $param_crt->goods,
            'offshore' => $param_crt->offshore,
            'ton' => $param_crt->ton,
            'scratch' => $param_crt->scratch,
            'self_ignition_id' => $param_crt->self_ignition,
            'discount_company_id' => $param_crt->discount_company_id,
            'tax' => $param_crt->tax,
            'displacement' => $param_crt->displacement,
            'service_year' => $param_crt->service_year,
            'level' => $param_crt->level //标准保费计算等级(家庭自用车与客车按座位数, 货车按吨位)
        );
        
        $success = self::nativeExecute($sql, $bind);
        
        if(!$success) return false;
        
        $connection = self::_getConnection();
        
        return $connection->lastInsertId();
    }

    /**
     * 添加保险初算结果
     * @param array $insurance_result
     * @return bool|int
     */
    public static function addInsuranceResult(array $insurance_result)
    {
        $result_crt = new Criteria($insurance_result);
        $sql = <<<SQL
        insert into Insurance_Result (
        roundYear, lastMonth, roundMonth, coefficient, standardCompulsoryInsurance, afterDiscountCompulsoryInsurance, singleNotDeductibleCompulsoryInsurance, standardDamageInsurance, afterDiscountDamageInsurance, singleNotDeductibleDamageInsurance, standardThird, afterDiscountThird, singleNotDeductibleThird, standardDriver, afterDiscountDriver, singleNotDeductibleDriver, standardPassenger, afterDiscountPassenger, singleNotDeductiblePassenger, standardRobbery, afterDiscountRobbery, singleNotDeductibleRobbery, standardGlass, afterDiscountGlass, singleNotDeductibleGlass, standardOptionalDeductible, afterDiscountOptionalDeductible, standardNotDeductible, afterDiscountNotDeductible, totalStandard, totalAfterDiscount, totalSingleNotDeductible, standardNewDevice, afterDiscountNewDevice, standardGoods, afterDiscountGoods, standardOffshore, afterDiscountOffshore, trailerStandardCompulsory, trailerPreferentialCompulsory, trailerStandardDamage, trailerPreferentialDamage, trailerStandardThird, trailerPreferentialThird, trailerStandardDriver, trailerPreferentialDriver, trailerStandardPassenger, trailerPreferentialPassenger, trailerStandardRobbery, trailerPreferentialRobbery, trailerStandardGlass, trailerPreferentialGlass, trailerStandardOptionalDeductible, trailerPreferentialOptionalDeductible, trailerStandardNotDeductible, trailerPreferentialNotDeductible, trailerStandardNewDevice, trailerPreferentialNewDevice, trailerStandardGoods, trailerPreferentialGoods, trailerStandardOffshore, trailerPreferentialOffshore, standardScratch, afterDiscountScratch, singleNotDeductibleScratch, standardSelfIgnition, afterDiscountSelfIgnition, singleNotDeductibleSelfIgnition, business, taxMoney, giftMoney
        ) values (
        :round_year, :last_month, :round_month, :coefficient, :standard_compulsory_insurance, :after_discount_compulsory_insurance, :single_not_deductible_compulsory_insurance, :standard_damage_insurance, :after_discount_damage_insurance, :single_not_deductible_damage_insurance, :standard_third, :after_discount_third, :single_not_deductible_third, :standard_driver, :after_discount_driver, :single_not_deductible_driver, :standard_passenger, :after_discount_passenger, :single_not_deductible_passenger, :standard_robbery, :after_discount_robbery, :single_not_deductible_robbery, :standard_glass, :after_discount_glass, :single_not_deductible_glass, :standard_optional_deductible, :after_discount_optional_deductible, :standard_not_deductible, :after_discount_not_deductible, :total_standard, :total_after_discount, :total_single_not_deductible, :standard_new_device, :after_discount_new_device, :standard_goods, :after_discount_goods, :standard_offshore, :after_discount_offshore, :trailer_standard_compulsory, :trailer_preferential_compulsory, :trailer_standard_damage, :trailer_preferential_damage, :trailer_standard_third, :trailer_preferential_third, :trailer_standard_driver, :trailer_preferential_driver, :trailer_standard_passenger, :trailer_preferential_passenger, :trailer_standard_robbery, :trailer_preferential_robbery, :trailer_standard_glass, :trailer_preferential_glass, :trailer_standard_optional_deductible, :trailer_preferential_optional_deductible, :trailer_standard_not_deductible, :trailer_preferential_not_deductible, :trailer_standard_new_device, :trailer_preferential_new_device, :trailer_standard_goods, :trailer_preferential_goods, :trailer_standard_offshore, :trailer_preferential_offshore, :standard_scratch, :after_discount_scratch, :single_not_deductible_scratch, :standard_self_ignition, :after_discount_self_ignition, :single_not_deductible_self_ignition, :business, :tax_money, :gift_money
        )
SQL;
        $bind = array(
            'round_year' => $result_crt->round_year,
            'last_month' => $result_crt->last_month,
            'round_month' => $result_crt->round_month,
            'coefficient' => $result_crt->coefficient,
            'standard_compulsory_insurance' => $result_crt->standard_compulsory_insurance,
            'after_discount_compulsory_insurance' => $result_crt->after_discount_compulsory_insurance,
            'single_not_deductible_compulsory_insurance' => $result_crt->single_not_deductible_compulsory_insurance,
            'standard_damage_insurance' => $result_crt->standard_damage_insurance,
            'after_discount_damage_insurance' => $result_crt->after_discount_damage_insurance,
            'single_not_deductible_damage_insurance' => $result_crt->single_not_deductible_damage_insurance,
            'standard_third' => $result_crt->standard_third,
            'after_discount_third' => $result_crt->after_discount_third,
            'single_not_deductible_third' => $result_crt->single_not_deductible_third,
            'standard_driver' => $result_crt->standard_driver,
            'after_discount_driver' => $result_crt->after_discount_driver,
            'single_not_deductible_driver' => $result_crt->single_not_deductible_driver,
            'standard_passenger' => $result_crt->standard_passenger,
            'after_discount_passenger' => $result_crt->after_discount_passenger,
            'single_not_deductible_passenger' => $result_crt->single_not_deductible_passenger,
            'standard_robbery' => $result_crt->standard_robbery,
            'after_discount_robbery' => $result_crt->after_discount_robbery,
            'single_not_deductible_robbery' => $result_crt->single_not_deductible_robbery,
            'standard_glass' => $result_crt->standard_glass,
            'after_discount_glass' => $result_crt->after_discount_glass,
            'single_not_deductible_glass' => $result_crt->single_not_deductible_glass,
            'standard_optional_deductible' => $result_crt->standard_optional_deductible,
            'after_discount_optional_deductible' => $result_crt->after_discount_optional_deductible,
            'standard_not_deductible' => $result_crt->standard_not_deductible,
            'after_discount_not_deductible' => $result_crt->after_discount_not_deductible,
            'total_standard' => $result_crt->total_standard,
            'total_after_discount' => $result_crt->total_after_discount,
            'total_single_not_deductible' => $result_crt->total_single_not_deductible,
            'standard_new_device' => $result_crt->standard_new_device,
            'after_discount_new_device' => $result_crt->after_discount_new_device,
            'standard_goods' => $result_crt->standard_goods,
            'after_discount_goods' => $result_crt->after_discount_goods,
            'standard_offshore' => $result_crt->standard_offshore,
            'after_discount_offshore' => $result_crt->after_discount_offshore,
            'trailer_standard_compulsory' => $result_crt->trailer_standard_compulsory,
            'trailer_preferential_compulsory' => $result_crt->trailer_preferential_compulsory,
            'trailer_standard_damage' => $result_crt->trailer_standard_damage,
            'trailer_preferential_damage' => $result_crt->trailer_preferential_damage,
            'trailer_standard_third' => $result_crt->trailer_standard_third,
            'trailer_preferential_third' => $result_crt->trailer_preferential_third,
            'trailer_standard_driver' => $result_crt->trailer_standard_driver,
            'trailer_preferential_driver' => $result_crt->trailer_preferential_driver,
            'trailer_standard_passenger' => $result_crt->trailer_standard_passenger,
            'trailer_preferential_passenger' => $result_crt->trailer_preferential_passenger,
            'trailer_standard_robbery' => $result_crt->trailer_standard_robbery,
            'trailer_preferential_robbery' => $result_crt->trailer_preferential_robbery,
            'trailer_standard_glass' => $result_crt->trailer_standard_glass,
            'trailer_preferential_glass' => $result_crt->trailer_preferential_glass,
            'trailer_standard_optional_deductible' => $result_crt->trailer_standard_optional_deductible,
            'trailer_preferential_optional_deductible' => $result_crt->trailer_preferential_optional_deductible,
            'trailer_standard_not_deductible' => $result_crt->trailer_standard_not_deductible,
            'trailer_preferential_not_deductible' => $result_crt->trailer_preferential_not_deductible,
            'trailer_standard_new_device' => $result_crt->trailer_standard_new_device,
            'trailer_preferential_new_device' => $result_crt->trailer_preferential_new_device,
            'trailer_standard_goods' => $result_crt->trailer_standard_goods,
            'trailer_preferential_goods' => $result_crt->trailer_preferential_goods,
            'trailer_standard_offshore' => $result_crt->trailer_standard_offshore,
            'trailer_preferential_offshore' => $result_crt->trailer_preferential_offshore,
            'standard_scratch' => $result_crt->standard_scratch,
            'after_discount_scratch' => $result_crt->after_discount_scratch,
            'single_not_deductible_scratch' => $result_crt->single_not_deductible_scratch,
            'standard_self_ignition' => $result_crt->standard_self_ignition,
            'after_discount_self_ignition' => $result_crt->after_discount_self_ignition,
            'single_not_deductible_self_ignition' => $result_crt->single_not_deductible_self_ignition,
            'business' => $result_crt->business,
            'tax_money' => $result_crt->tax_money,
            'gift_money' => $result_crt->gift_money
        );
        
        $success = self::nativeExecute($sql, $bind);
        
        if(!$success) return false;
        
        $connection = self::_getConnection();
        
        return $connection->lastInsertId();
    }

    /**
     * 添加保险材料(附件)
     * @param array $criteria
     * @return bool|int
     */
    public static function addInsuranceAttach(array $criteria)
    {
        $crt = new Criteria($criteria);

        $sql = <<<SQL
        insert into Insurance_Attach (driving_license_a, driving_license_b, idcard, insurance_card)  values (:driving_license_a, :driving_license_b, :idcard, :insurance_card)
SQL;
        $bind = array(
            'driving_license_a' => $crt->driving_license_a,
            'driving_license_b' => $crt->driving_license_b,
            'idcard' => $crt->idcard,
            'insurance_card' => $crt->insurance_card
        );

        $success = self::nativeExecute($sql, $bind);

        if(!$success) return false;

        $connection = self::_getConnection();

        return $connection->lastInsertId();

    }

    /**
     * 获取指定id保险信息
     * @param $id
     * @return array
     */
    public static function getInsuranceInfoById($id)
    {
        $sql = <<<SQL
        select
        id, user_userId as user_id, carNo_id as car_no_id, carType_id as car_type_id, insuranceParam_id as param_id, insuranceResult_id as result_id, state_id, createDate as create_date, userName as user_name, phoneNo as phone, emailAddr as email, buy_id, address_id, finalParam_id as final_param_id, finalResult_id as final_result_id, insuranceNo as insurance_no, insuranceSetId as insurance_set_id, payState as pay_state, sfzh as sfzh, failureReason as failure_reason, giftMoney as gift_money, issuingTime as issuing_time, actulAmount as actul_amount, preferenceItems as preference_items from Insurance_Info where id = :id
SQL;

        $bind = array('id' => $id);

        return self::fetchOne($sql, $bind, null, Db::FETCH_ASSOC);

    }

    /**
     * 获取保险信息列表
     * @param array|null $criteria
     * @param null $page_num
     * @param null $page_size
     * @return array
     */
    public static function getInsuranceInfoList(array $criteria=null, $page_num=null, $page_size=null)
    {
        $crt = new Criteria($criteria);

        $cte_condition_arr = array();
        $cte_condition_str = '';

        $page_condition_str = '';

        $bind = array();

        if($crt->user_id)
        {
            $cte_condition_arr[] = 'user_userId = :user_id';
            $bind['user_id'] = $crt->user_id;
        }

        if($crt->state)
        {
            $cte_condition_arr[] = 'state_id = :state';
            $bind['state'] = $crt->state;
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
        with INS_CTE as(
         select id, user_userId as user_id, carNo_id as car_no_id, carType_id as car_type_id, insuranceParam_id as param_id, insuranceResult_id as result_id, state_id,
          convert(varchar(25),createDate,126) as create_date,
          convert(varchar(25),lastModifiedTime,126) as last_modified_time,
          userName as user_name, phoneNo as phone, emailAddr as email, buy_id, address_id, finalParam_id as final_param_id, finalResult_id as final_result_id, insuranceNo as insurance_no, insuranceSetId as insurance_set_id, payState as pay_state, sfzh as sfzh, failureReason as failure_reason, giftMoney as gift_money, issuingTime as issuing_time, actulAmount as actul_amount, preferenceItems as preference_items,
          ROW_NUMBER() over (order by createDate desc) as rownum
          from Insurance_Info
          $cte_condition_str
        )
        select * from INS_CTE
        $page_condition_str
SQL;

        return self::nativeQuery($sql, $bind);
    }

    /**
     * 获取保险信息总数
     * @param array $criteria
     * @return int|string
     */
    public static function getInsuranceInfoCount(array $criteria=null)
    {
        $crt = new Criteria($criteria);

        $condition_arr = array();
        $condition_str = '';
        $bind = array();

        if($crt->user_id)
        {
            $condition_arr[] = 'user_userId = :user_id';
            $bind['user_id'] = $crt->user_id;
        }

        if($crt->state)
        {
            $condition_arr[] = 'state_id = :state';
            $bind['state'] = $crt->state;
        }

        if(!empty($condition_arr))
        {
            $condition_str = 'where '.implode(' and ', $condition_arr);
        }

        $sql = "select count(id) from Insurance_Info $condition_str";

        $result =  self::fetchOne($sql, $bind, null, Db::FETCH_NUM);
        return $result[0];
    }


    /**
     * 获取已精算的保险信息
     * @param array|null $criteria
     * @param null $page_num
     * @param null $page_size
     * @return array
     */
    public static function getActualedInsuranceInfoList(array $criteria=null, $page_num=null, $page_size=null)
    {
        $crt = new Criteria($criteria);

        $cte_condition_arr = array('state_id = 3');
        $cte_condition_str = '';

        $page_condition_str = '';

        $bind = array();

        if($crt->user_id)
        {
            $cte_condition_arr[] = 'user_userId = :user_id';
            $bind['user_id'] = $crt->user_id;
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
        with INS_CTE as(
          select id, user_userId as user_id, carNo_id as car_no_id, carType_id as car_type_id, insuranceParam_id as param_id, insuranceResult_id as result_id, state_id,
          convert(varchar(25),createDate,126) as create_date,
          convert(varchar(25),lastModifiedTime,126) as last_modified_time,
          finalParam_id as final_param_id,
          ROW_NUMBER() over (order by createDate desc) as rownum
          from Insurance_Info
          $cte_condition_str
        )
        select i.*, fr.total_standard, fr.min_total_after_discount, fr.max_gift_money from INS_CTE i
        left join (
          select
          info_id,
          max(fr.totalStandard) as total_standard,
          min(fr.totalAfterDiscount) as min_total_after_discount,
          max(fr.giftMoney) as max_gift_money
          from Insurance_Info_To_FinalResult i2fr
          left join Insurance_FinalResult fr on fr.id = i2fr.result_id
          group by i2fr.info_id
        ) fr on fr.info_id = i.id
        $page_condition_str
SQL;
        return self::nativeQuery($sql, $bind);
    }

    /**
     * 获取已精算保险信息总数
     * @param array|null $criteria
     * @return mixed
     */
    public static function getActualedInsuranceInfoCount(array $criteria=null)
    {
        $crt = new Criteria($criteria);

        $condition_arr = array('state_id = 3');
        $condition_str = '';
        $bind = array();

        if($crt->user_id)
        {
            $condition_arr[] = 'user_userId = :user_id';
            $bind['user_id'] = $crt->user_id;
        }

        if(!empty($condition_arr))
        {
            $condition_str = 'where '.implode(' and ', $condition_arr);
        }

        $sql = "select count(id) from Insurance_Info $condition_str";

        $result = self::fetchOne($sql, $bind, null, Db::FETCH_NUM);

        return $result[0];
    }

    /**
     * 获取保险定单信息
     * @param $info_id
     * @return array
     */
    public static function getInsuranceOrderInfo($info_id)
    {
        $sql = <<<SQL
        select i.id as id, i.user_userId as user_id, i.userName as user_name, i.phoneNo as phone, carType_id as car_type_id, insuranceParam_id as param_id, insuranceResult_id as result_id, state_id,
       convert(varchar(25),i.createDate,126) as create_date,
       convert(varchar(25),lastModifiedTime,126) as last_modified_time,
       finalParam_id as final_param_id,
       finalResult_id as final_result_id,
       car.hphm,
       c.companyName as company_name,
       fr.*,
       p.id as order_id, p.orderNo as order_no, p.money as order_fee
       from Insurance_Info i
       left join CarInfo car on car.id = i.carNo_id
       left join Insurance_Discount c on c.companyId = i.companyId
       left join Insurance_FinalResult fr on fr.id = i.finalResult_id
       left join (
          select id, relId, orderNo, [state], [money] from PayList where orderType = 'insurance'
          and id in (
            select max(id) from PayList where orderType = 'insurance' group by relId
          )
      ) p on p.relId = i.id
      where i.id = :info_id
SQL;
        $bind = array('info_id' => $info_id);

        return self::fetchOne($sql, $bind, null, Db::FETCH_ASSOC);

    }

    public static function getHasOrderInsuranceInfoList(array $criteria=null, $page_num=null, $page_size=null)
    {
        $crt = new Criteria($criteria);

        $cte_condition_arr = array('state_id = 4');
        $cte_condition_str = '';

        $page_condition_str = '';

        $bind = array();

        if($crt->user_id)
        {
            $cte_condition_arr[] = 'user_userId = :user_id';
            $bind['user_id'] = $crt->user_id;
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
        with INS_CTE as(
          select id, user_userId as user_id, carNo_id as car_no_id, carType_id as car_type_id, insuranceParam_id as param_id, insuranceResult_id as result_id, state_id,
          convert(varchar(25),createDate,126) as create_date,
          convert(varchar(25),lastModifiedTime,126) as last_modified_time,
          finalParam_id as final_param_id,
          finalResult_id as final_result_id,
          companyId as company_id,
          ROW_NUMBER() over (order by createDate desc) as rownum
          from Insurance_Info
          $cte_condition_str
        )
        select i.id as info_id, i.*, c.companyName as company_name, fr.*, p.id as order_id, p.orderNo as order_no, p.state as order_state, p.money as order_fee from INS_CTE i
        left join Insurance_Discount c on c.companyId = i.company_id
        left join Insurance_FinalResult fr on fr.id = i.final_result_id
        left join (
          select id, relId, orderNo, state, money from PayList where orderType = 'insurance'
          and id in (
            select max(id) from PayList where orderType = 'insurance' group by relId
          )
        ) p on p.relId = i.id
        $page_condition_str
SQL;

        return self::nativeQuery($sql, $bind);
    }

    /**
     * 获取已下单保险信息总数
     * @param array|null $criteria
     * @return mixed
     */
    public static function getHasOrderInsuranceCount(array $criteria=null)
    {
        $crt = new Criteria($criteria);

        $condition_arr = array('state_id = 4');
        $condition_str = '';
        $bind = array();

        if($crt->user_id)
        {
            $condition_arr[] = 'user_userId = :user_id';
            $bind['user_id'] = $crt->user_id;
        }

        if(!empty($condition_arr))
        {
            $condition_str = 'where '.implode(' and ', $condition_arr);
        }

        $sql = "select count(id) from Insurance_Info $condition_str";

        $result = self::fetchOne($sql, $bind, null, Db::FETCH_NUM);

        return $result[0];
    }

    /**
     * 获取已出单保险信息
     * @param array|null $criteria
     * @param null $page_num
     * @param null $page_size
     * @return array
     */
    public static function getHasPolicyInsuranceList(array $criteria=null, $page_num=null, $page_size=null)
    {
        $crt = new Criteria($criteria);

        $cte_condition_arr = array('state_id = 5');
        $cte_condition_str = '';

        $page_condition_str = '';

        $bind = array();

        if($crt->user_id)
        {
            $cte_condition_arr[] = 'user_userId = :user_id';
            $bind['user_id'] = $crt->user_id;
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
        with INS_CTE as(
          select id, user_userId as user_id, insuranceParam_id as param_id, insuranceResult_id as result_id, state_id, insuranceNo as insurance_no,
          convert(varchar(25),createDate,126) as create_date,
          convert(varchar(25),lastModifiedTime,126) as last_modified_time,
          convert(varchar(25),issuingTime,126) as issuing_time,
          finalParam_id as final_param_id,
          finalResult_id as final_result_id,
          companyId as company_id,
          ROW_NUMBER() over (order by createDate desc) as rownum
          from Insurance_Info
          $cte_condition_str
        )
        select i.id as info_id, i.*, c.companyName as company_name, fr.* from INS_CTE i
        left join Insurance_Discount c on c.companyId = i.company_id
        left join Insurance_FinalResult fr on fr.id = i.final_result_id
        $page_condition_str
SQL;

        return self::nativeQuery($sql, $bind);
    }

    /**
     * 获取已出单保险总数
     * @param array|null $criteria
     * @return mixed
     */
    public static function getHasPolicyInsuranceCount(array $criteria=null)
    {
        $crt = new Criteria($criteria);

        $condition_arr = array('state_id = 5');
        $condition_str = '';
        $bind = array();

        if($crt->user_id)
        {
            $condition_arr[] = 'user_userId = :user_id';
            $bind['user_id'] = $crt->user_id;
        }

        if(!empty($condition_arr))
        {
            $condition_str = 'where '.implode(' and ', $condition_arr);
        }

        $sql = "select count(id) from Insurance_Info $condition_str";

        $result = self::fetchOne($sql, $bind, null, Db::FETCH_NUM);

        return $result[0];
    }

    /**
     * 获取指定id保险初算结果
     * @param $id
     * @return array
     */
    public static function getInsuranceFirstResultById($id)
    {
        $sql = <<<SQL
        select * from Insurance_Result where id = :id
SQL;
        $bind = array('id' => $id);

        return self::fetchOne($sql, $bind, null, Db::FETCH_ASSOC);
    }

    /**
     * 获取某条精算结果
     * @param $id
     * @return object
     */
    public static function getInsuranceFinalResultById($id)
    {
        $sql = 'select * from Insurance_FinalResult where id = :id';
        $bind = array('id' => $id);

        return self::fetchOne($sql, $bind, null, Db::FETCH_ASSOC);
    }

    /**
     * 获取指定保险信息的所有保险公司精算结果
     * @param $info_id
     * @return array
     */
    public static function getInsuranceFinalResults($info_id)
    {
        $sql = <<<SQL
        select i2fr.info_id, c.companyId as company_id, c.shortName as company_short_name, c.companyName as company_name, fr.id as result_id, fr.*
        from
          (
            select * from Insurance_Info_To_FinalResult
            where info_id = :info_id
          ) i2fr
        left join Insurance_FinalResult fr on fr.id = i2fr.result_id
        left join Insurance_discount c on c.companyId = i2fr.company_id
        order by fr.totalAfterDiscount asc, fr.giftMoney desc
SQL;
        $bind = array(
            'info_id' => $info_id
        );

        return self::nativeQuery($sql, $bind);
    }

    /**
     * 获取指定数目的保险公司列表
     * @param $top int
     * @return array
     */
    public static function getInsuranceCompanyList($top)
    {
        $top = (int) $top;
        $sql = <<<SQL
        select top $top companyId as id, discount, carPriceDiscount as car_price_discount, companyName as [name], ename,  shortName as short_name, gift, gift2, gift3, isOrder as [order] from Insurance_Discount
        order by isOrder asc, discount desc, gift desc
SQL;

        return self::nativeQuery($sql);
    }

    /**
     * 获取最大优惠(最低折扣包括折扣和礼包)
     * @return array
     */
    public static function getMinDiscount()
    {
        $sql = 'select top 1 discount, gift, gift2 from Insurance_Discount order by discount asc, gift2 desc, gift desc';
        return self::fetchOne($sql, null, null, Db::FETCH_ASSOC);
    }

    /**
     * 更新保险信息
     * @param $id
     * @param array|null $criteria
     * @return bool
     */
    public static function updateInsuranceInfo($id, array $criteria=null)
    {
        $crt = new Criteria($criteria);

        $field_str = 'lastModifiedTime = getdate(), ';
        $bind = array('id' => $id);

        if($crt->car_no_id)
        {
            $field_str .= 'carNo_id = :car_no_id, ';
            $bind['car_no_id'] = $crt->car_no_id;
        }

        if($crt->user_name)
        {
            $field_str .= 'userName = :user_name, ';
            $bind['user_name'] = $crt->user_name;
        }

        if($crt->phone)
        {
            $field_str .= 'phoneNo = :phone, ';
            $bind['phone'] = $crt->phone;
        }

        if($crt->sfzh)
        {
            $field_str .= 'sfzh = :sfzh, ';
            $bind['sfzh'] = $crt->sfzh;
        }
        if($crt->attach_id)
        {
            $field_str .= 'attachId = :attach_id, ';
            $bind['attach_id'] = $crt->attach_id;
        }

        if($crt->state_id)
        {
            $field_str .= 'state_id = :state_id, ';
            $bind['state_id'] = $crt->state_id;
        }

        if($crt->final_result_id)
        {
            $field_str .= 'finalResult_id = :final_result_id, ';
            $bind['final_result_id'] = $crt->final_result_id;
        }

        if($crt->address_id)
        {
            $field_str .= 'address_id = :address_id, ';
            $bind['address_id'] = $crt->address_id;
        }

        if($crt->company_id)
        {
            $field_str .= 'companyId = :company_id, ';
            $bind['company_id'] = $crt->company_id;
        }

        $field_str = rtrim($field_str, ', ');

        $sql = <<<SQL
        update Insurance_Info set $field_str where id = :id
SQL;

        return self::nativeExecute($sql, $bind);
    }

    /***
     * 更新保险参数
     * @param $id
     * @param array|null $criteria
     * @return array
     */
    public static function updateInsuranceParam($id, array $criteria=null)
    {
        $crt = new Criteria($criteria);
        $field_str = '';
        $bind = array('id' => $id);

        if($crt->first_year)
        {
            $field_str .= 'firstYear = :first_year, ';
            $bind['first_year'] = $crt->first_year;
        }

        if($crt->first_month)
        {
            $field_str .= 'firstMonth = :first_month, ';
            $bind['first_month'] = $crt->first_month;
        }

        if($crt->insurance_year)
        {
            $field_str .= 'insuranceYear = :insurance_year, ';
            $bind['insurance_year'] = $crt->insurance_year;
        }

        if($crt->insurance_month)
        {
            $field_str .= 'insuranceMonth = :insurance_month, ';
            $bind['insurance_month'] = $crt->insurance_month;
        }

        $field_str = rtrim($field_str, ', ');

        $sql = <<<SQL
        update Insurance_Param set $field_str where id = :id
SQL;
        return self::nativeQuery($sql, $bind);
    }

    /**
     * 更新精算参数
     * @param $id
     * @param array|null $criteria
     * @return bool
     */
    public static function updateFinalParam($id, array $criteria=null)
    {
        $crt = new Criteria($criteria);
        $field_str = '';
        $bind = array('id' => $id);

        if($crt->company_id)
        {
            $field_str .= 'discount_companyId = :company_id, ';
            $bind['company_id'] = $crt->company_id;
        }

        $field_str = rtrim($field_str, ', ');

        $sql = "update Insurance_FinalParam set $field_str where id = :id";

        return self::nativeExecute($sql, $bind);
    }

    /**
     * 添加保单收取地址
     * @param array|null $criteria
     * @return bool|int
     */
    public static function addInsuranceAddress(array $criteria=null)
    {
        $crt = new Criteria($criteria);
        $sql = 'insert into Insurance_Address (provinceId, cityId, address) values (:province_id, :city_id, :address)';
        $bind = array(
            'province_id' => $crt->province_id,
            'city_id' => $crt->city_id,
            'address' => $crt->address
        );

        $success =  self::nativeExecute($sql, $bind);

        if(!$success) return false;

        $connection = self::_getConnection();
        return $connection->lastInsertId();
    }

    /**
     * 添加保险预约
     * @param array|null $criteria
     * @return  bool
     */
    public static function addInsuranceReservation(array $criteria=null)
    {
        $crt = new Criteria($criteria);
        $sql = 'insert into Insurance_Reservation (user_id, phone, car_info_id, offer_date) values (:user_id, :phone, :car_info_id, :offer_date)';
        $bind = array(
            'user_id' => $crt->user_id,
            'phone' => $crt->phone,
            'car_info_id' => $crt->car_info_id,
            'offer_date' => $crt->offer_date
        );
        return self::nativeExecute($sql, $bind);
    }

    /**
     * 指定的电话号码和指定车辆信息是否已预约(未报价)
     * @param  string  $phone
     * @param  string|int  $car_info_id
     * @return boolean
     */
    public static function isReserved($phone, $car_info_id)
    {
        $sql = 'select id from Insurance_Reservation where phone = :phone and car_info_id = :car_info_id and mark is null';
        $bind = array(
            'phone' => $phone,
            'car_info_id' => $car_info_id
        );

        $result = self::fetchOne($sql, $bind, null, Db::FETCH_ASSOC);

        return !empty($result);
    }
}