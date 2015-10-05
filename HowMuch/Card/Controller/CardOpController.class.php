<?php
namespace Card\Controller;
use Think\Controller;
use Common\Controller\ListPage;
use Common\Controller\FormElement;
use Common\Controller\Form;
use Common\Controller\SmallDataList;

class CardOpController extends Controller
{
    private $admin;
    private $sid;
    private $uid;
    
    private $el_info = array(
                "info_status", 
                "opid", 
                "info_code", 
                "cost", 
                "sign_img", 
                "rising_cost", 
                "bank", 
                "pay_type",
                "card", 
                "fee",
                "amount", 
                "agreement",
                "pay_pwd", 
                "save_amount", 
                "query_pwd", 
                "fact_save",
                "cvv2", 
                "counts", 
                "bill", 
                "costing_op",
                "finally_repayment_date",
                "costing_per", "costing", 
                "cdate_month", "cdate_year", 
                "info_times", 
                "year_fee",
                "info_due", 
                "card_type", 
                "info_rising_num",
                "card_type_name",
                "info_rising_amount",
                "remark",
    );
    
    private $sel_info = array(
                "info_status", 
                "cost", 
                "info_code", 
                "rising_cost", 
                "bank", 
                "pay_type",
                "card",
                "fee",
                "amount", 
                "agreement", 
                "bill", 
                "counts", 
                "cdate_month", "cdate_year", 
                "info_times", 
                "year_fee",
                "info_due", 
                "info_rising_num",
                "info_rising_amount",
                "remark",
    );
    
    private $fel_info = array(
                "info_code", 
                "info_status", 
                "bank", 
                "sign_img",
                "card",
                "cost", 
                "amount", 
                "rising_cost",
                "pay_pwd", 
                "pay_type",
                "query_pwd", 
                "fee",
                "bill", 
                "agreement",
                "finally_repayment_date", 
                "save_amount",
                "cdate_month", "cdate_year", 
                "counts", 
                "year_fee",
                "remark",
    );
    
    
    public function __construct()
    {
        parent::__construct();
        if (!IS_AJAX)
        {
            header("Location:".ERROR_URL);
            exit(0);
        }
        karray_insert($this->el_info, 0, CardController::$el_customer);
        karray_insert($this->sel_info, 0, CardController::$el_customer);
        karray_insert($this->fel_info, 0, CardController::$el_customer);
        karray_cat($this->el_info, CardController::$el_relate);
        karray_cat($this->sel_info, CardController::$el_relate);
        karray_cat($this->fel_info, CardController::$el_relate);
        $this->admin = get_user_info("admin");
        $this->uid = get_user_info("uid");
        $this->sid = get_user_info("sid");
    }
    
    //测试到期日期
    public function testDueDate()
    {
        if (IS_POST)
        {
            $due_date = self::getDueDate($_POST, $_POST["times"]);
            $this->ajaxReturn(array("echo" => 0, "callback" => '$("#dis_duedate_id").html("'.$due_date.'");'));
        }
        
        $df = new Form("");
        $df->setElement("bill", "num", "出账单日", array("bool" => "required", "min" => 1, 
                "ext" => 'max="28"', "addon" => "日"));
        $df->setElement("finally_repayment_date", "num", "最后还款日", array("bool" => "required", "min" => 18, 
                "ext" => 'max="25"', "addon_dir" => 1, "addon" => "T + ", "value" => 20));
        $df->setElement("agreement", "num", "服务期数", array("bool" => "required", "min" => 1,
                "ext" => 'max="12"', "addon" => "期", "value" => 1));
        $df->setElement("times", "date", "签约日期", array("bool" => "required", "value" => date("Y-m-d")));
        $df->setElement("dis_duedate", "static", "到期日期");
        $df->set("btn 0 txt", "计算");
        echo $df->fetch();
        exit(0);
    }
    
    //调试生成期数列表
    public function installmentList($period = "")
    {
        if ($period == "")
        {
            $arg = session("testinstallment");
            session("testinstallment", null);
            $period = update_card_installment("test", $arg, $arg["type"], $arg["times"]);
        }
        
  		$data = new SmallDataList("card_installment_list", "", 0, array("page" => array("size" => 15)));
		$data->set("data_list", $period);
        $data->set("tooltip", 1);
		$data->setTitle(array("期数起始", "总期数", "随机期数", "还款日期"));
		$data->setField(array("start_date", "total", "installment", "days"));  
        echo $data->fetch();
    }
    
    //测试生成期数
    public function testInstallment()
    {
        if (IS_POST)
        {
            $_POST["due_date"] = self::getDueDate($_POST, $_POST["times"]);
            if ($_POST["type"] == 1)
                $_POST["type"] = true;
            else
                $_POST["type"] = false;
            session("testinstallment", $_POST);
            
    		$js = '$("#dis_duedate_id").html("'.$_POST["due_date"].'");';
            
            $this->ajaxReturn(array("echo" => 0, "url" => U("CardOp/installmentList"), 
                    "tag" => "#dis_installmentlist_id", "callback" => $js));
        }
        
        $df = new Form("", array("cols" => 2, "kajax" => "true"));
        $df->setElement("code", "string", "内部代码", array("bool" => "required", "value" => "12345678"));
        $df->setElement("type", "radio", "生成模式", array("bool" => "required", 
                "value" => 1, "list" => parse_select_list("array", array(1, 2), array("新卡录入", "续约生成"))
        ));
        $df->setElement("bill", "num", "出账单日", array("bool" => "required", "min" => 1, 
                "ext" => 'max="28"', "addon" => "日"));
        $df->setElement("finally_repayment_date", "num", "最后还款日", array("bool" => "required", "min" => 18, 
                "ext" => 'max="25"', "addon_dir" => 1, "addon" => "T + ", "value" => 20));
        $df->setElement("agreement", "num", "服务期数", array("bool" => "required", "min" => 1,
                "ext" => 'max="12"', "addon" => "期", "value" => 1));
        $df->setElement("times", "date", "签约日期", array("bool" => "required", "value" => date("Y-m-d")));
        $df->setElement("dis_duedate", "static", "到期日期");
        $df->setElement("dis_installmentlist", "static", "", array("close_label" => 1, "element_cols" => 12,
                "sig_row" => 1));
        $df->set("btn 0 txt", "生成期数");
        echo $df->fetch();
        exit(0);
    }
    
//     static public function getDueDate($card, $op_date = "")
//     {
//         if ($op_date == "")
//             $op_date = date("Y-m-d");
        
//         //获取操作日期的日期戳子 
//         $op_time = strtotime($op_date);
        
//         //获取本期账单的最后还款日时间戳   
//         //如果出账单日比当前操作日大，则代表还款期为跨月情况，并且没有进入本期还款期，此时最后还款日为上一期最后还款日
//         if ($card["bill"] > date("d", $op_time))
//             $end_date_time = strtotime("+".$card["finally_repayment_date"]." day", 
//                     strtotime(date("Y-m-", strtotime("-1 month", $op_time)).$card["bill"]));
//         else
//             $end_date_time = strtotime("+".$card["finally_repayment_date"]." day", 
//                                         strtotime(date("Y-m-", $op_time).$card["bill"]));
        
//         //判断如果当前操作日在还款期内并且有还款工作日 则本月算一期，则到期日期要减一个月 
//         if (self::isCardDate($card, $op_date) == 2)
//         {
//             if ($card["agreement"] == 1)    //如果在还款期内且服务期数为1期，则到期日期为本期最后还款日
//                 return date("Y-m-d", $end_date_time);
//             return date("Y-m-d", strtotime("+".($card["agreement"] - 1)." month", $end_date_time));
//         }
//         else
//             return date("Y-m-d", strtotime("+".$card["agreement"]." month", $end_date_time));
//     }
    
    static public function getDueDate($card, $op_date = "")
    {
        if ($op_date == "")
            $op_date = date("Y-m-d");
        
        //获取操作日期的日期戳子 
        $op_time = strtotime($op_date);
        
        //获取服务几个月
        $agreement = $card["agreement"];
        
        //判断出账单日如果大于当前操作日, 当代表还款期为跨月，则服务月份少一个月
        if ((int)($card["bill"]) > (int)(date("d", $op_time)))
            $agreement = $agreement - 1;
            
        //获取最后一期出账单日
        //计算操作日加判断后的月份得出的日期截取年月和出账单日组合一个新的日期  获取此日期的日期戳
        $bill_time = strtotime(date("Y-m", strtotime("+".$agreement." month", $op_time))."-".$card["bill"]);
        
        //判断当前操作日期是否在此卡的还款期内，并且有还款日的情况下，对算出的到期月份减一个月再组合出账单日获取此日期戳
        if (self::isCardDate($card, $op_date) == 2)
            $bill_time = strtotime(date("Y-m", strtotime("-1 month", $bill_time))."-".$card["bill"]);
        
//         $deg = "agreement = ".$bool.", bill = ".$card["bill"].", day = ".date("d", $op_time)." ";
            //对最后一期账单日进行计算最后一期还款日， 此日期为到期日期
        return date("Y-m-d", strtotime("+".$card["finally_repayment_date"]." day", $bill_time));
    }
    
    //卡片终审通过处理函数
    public function auditHandle($card, $add = true)
    {
        //容错如果不是错误直接返回
        if (!is_array($card))
            return false;
        
        if ($add)
        {
            $data = array();
            $data["status"] = $card["status"];
            $data["times"] = date("Y-m-d");
            
            $data["due_date"] = $this->getDueDate($card);
            $data["update_time"] = getcurtime();
            
            //更新卡片状态、签约日期和到期日期
            M("card")->where("id=".$card["id"])->save($data);
            
//             echo(M()->getLastSql());
            
            //更新客户卡数，拓展员卡数，客户卡额
            M("basis")->where("id=".$card["bid"])->setInc("amount_num", $card["amount"]);
            $obj = M("users");
            $obj->where("id=".$card["opid"])->setInc("card_num");
            $obj->where("id=".$card["eid"])->setInc("card_num");
            
            //拓展员签单佣金结算
            sales_bonus($card["eid"], $card["code"], $card["amount"]);
        }
            
        $iscardtype = sqlCol("select id from card_type where bank='".$card["bank"].
                "' and (type='".$card["card_type"]."' or name='".$card["card_type_name"]."')");
        if (!$iscardtype)
        {
            $data = array();
            $data["bank"] = $card["bank"];
            $data["type"] = $card["card_type"];
            $data["name"] = $card["card_type_name"];
            
            //把信用卡正面图片复制到card_type文件夹中 重新生成文件名
            $auth = I("session.user_auth");
            $path = __UP__."/card_type/".$auth["code"];
            if (!is_dir($path))
                mkdir($path, 0777, true);
            $filename = dechex(mt_rand(16, 99)).date("YmdHi").dechex(mt_rand(16, 99)).".jpg";
            $data["img"] = "card_type/".$auth["code"]."/".$filename;
            copy(__UP__.$card["card_img1"], $path."/".$filename);
            
            $data["sort_id"] = sqlCol("select max(sort_id) from card_type") + 1;
            $data["card"] = substr($card["card"], 0, 8);
            $data["amount"] = 0;
            $data["update_time"] = getcurtime();
            $data["create_time"] = getcurtime();
//             $this->ajaxReturn(array("echo" => 1, "info" => $card["card_img1"]." ".$data["img"]));
            $obj = M("card_type");
            $ret = $obj->create($data, 1);
            if ($ret)
                $obj->add();
        }
//        $this->ajaxReturn(array("echo" => 1, "info" => "调试"));
                    
        update_card_installment("", $card, $add);
    }
    
    
    //判断信用卡资料正确性
    public function cardOtherLimit()
    {
        //判断信用卡有效期
        $year = date("Y");
        if ($_POST["status"] != DRAFT && 
                ($_POST["cdate_year"] < $year || ($_POST["cdate_year"] == $year && 
                $_POST["cdate_month"] < date("m"))))
            $this->ajaxReturn(array("echo" => 1, "info" => "不支持有效期已过期或即将过期的信用卡!"));
                
        //验证卡片费率输入是否符合拓展员的标准
        if ($this->admin == 3)
        {
            $e = sqlRow("select award_min,signing_min from users where id=".$_POST["eid"]);
            if ($_POST["status"] != DRAFT && $e && 
                    ($_POST["rising_cost"] <= ($e["award_min"] * 100) ||
                    $_POST["rising_cost"] > 15 || 
                    $_POST["cost"] <= ($e["signing_min"] * 100) ||
                    $_POST["cost"] > 2.1))
                $this->ajaxReturn(array("echo" => 1, "info" => "输入的服务费率或增值费率不正确!"));
        }
        
        //调试
//         $this->ajaxReturn(array("echo" => 1, "info" => $e["award_min"]." ".$e["signing_min"].
//                 " | ".$_POST["cost"]." ".$_POST["rising_cost"]));
       
        $this->posOpLimit();
//         $this->ajaxReturn(array("echo" => 1, "info" => "jsdflsjd"));
    }
    
    public function posOpLimit()
    {
        //判断低值服务是否符合条件，条件为本站必须要有18台以上0.38费率的pos
        //判断高值服务是否符合条件，条件为本站必须要有18台以上1.25费率的pos
        $pos_cost = array(2 => "<'0.0050'", 3 => "='0.0125'", 4 => "<'0.0050'");
        if ($_POST["costing_op"] == 2 || $_POST["costing_op"] == 3 || $_POST["costing_op"] == 4)
        {
            $pos_num = sqlCol("select count(id) from pos where status=0 and 
                        cost".$pos_cost[$_POST["costing_op"]]." and sid=".$this->sid);
            if ($pos_num < 18 || 
                    ($_POST["costing_op"] == 4 && 
                        sqlCol("select count(id) from pos where status=0 
                                    and cost='0.0078' and sid=".$this->sid) < 12))
                $this->ajaxReturn(array("echo" => 1, 
                        "info" => "站内现有POS终端台数，不足以支持此操作选项的运营！请新增适配费率POS终端!"));
        }
    }
   
    //保存卡片到数据库或修改卡片资料处理 $ajax为是否要ajax返回
    
    private function cardSave(& $formObj, $ajax = true)
    {
        $form = & $formObj->form;
        $obj = & $form["dataObj"];
        
//         $this->ajaxReturn(array("echo" => 1, "info" => $_POST["fee"].", ".$_POST["agreement"]));
        
        $ret = $obj->validate($form["validate"])->create();
        if (!$ret)
            $this->ajaxReturn(array("echo" => 1, "info" => $obj->getError()));     
        
        $this->cardOtherLimit();
        
        if ($_POST["cdate_year"] && $_POST["cdate_month"])
            $_POST["effective_date"] = $_POST["cdate_year"]."-".$_POST["cdate_month"]."-1";
        $_POST["costing_per"] /= 100;
        $_POST["cost"] /= 100;
        $_POST["rising_cost"] /= 100;
        $_POST["repayment"] = 1; 
        $_POST["relate"] = "alipay=".$_POST["alipay"]."|wxpay=".$_POST["wxpay"]."|aging=".$_POST["aging"].
        "|temp_amount=".$_POST["temp_amount"]."|tenpay=".$_POST["tenpay"].
        "|insure=".$_POST["insure"]."|autopay=".$_POST["autopay"].
        "|quick_pay=".$_POST["quick_pay"]."|auto_aging=".$_POST["auto_aging"]."|overdue=".$_POST["overdue"].
        "|affiliate=".$_POST["affiliate"]."|exceed=".$_POST["exceed"];
        
        if ($_POST["id"])  //卡片修改
        {
//             $this->ajaxReturn(array("echo" => 1, "info" => "初审".$_POST["code"]." ".$_POST["old_status"]));
            //财务初审通过改变临时状态为报备状态
            if ($this->admin == 1 && $_POST["old_status"] == SUB_AUDIT)
                $_POST["temp_status"] = FILING;
            
            $ret = $obj->auto($form["auto"])->create($_POST, 2);
            $ret = $ret ? $obj->where("id=".$_POST["id"])->save() : $ret;
            $form["return"]["info"] = $ret ? "提交成功!" : "输入数据格式有误!";
            
            if ($this->admin == 6 && $_POST["old_status"] == DRAFT && $_POST["status"] == NORMAL)
                $this->auditHandle($_POST);
            
            //财务初审核通过保存操作记录
            if ($this->admin == 1 && $_POST["old_status"] == SUB_AUDIT)
                save_operating_record($_POST["code"], CARD_SUB);
        }
        else  //卡片添加
        {
            $_POST["code"] = build_code("card", $_POST["bid"]);
            if (sqlCol("select card_num from basis where id=".$_POST["bid"]) >= 15)
                $this->ajaxReturn(array("echo" => 1, "info" => "此客户下属信用卡数已满，无法添加!"));
            
            $_POST["typing"] = $this->uid;
        
            $ret = $obj->auto($form["auto"])->create($_POST, 1);
            if (!$ret)
                $form["return"]["info"] = $obj->getError();
            else
            {
                $ret = $obj->add();
                if ($ret)
                {
                    M("basis")->where("id=".$_POST["bid"])->setInc("card_num");
                    M("basis")->where("id=".$_POST["bid"])->setInc("codeID");
                    if ($this->admin == 6)
                    {
                        $_POST["id"] = $ret;
                        $this->auditHandle($_POST);
                    }
                    save_operating_record($_POST["code"], CARD_ADD);
                }
                
                $form["return"]["info"] = $ret ? "提交成功!" : "输入数据格式有误!".M()->getLastSql();
            }
        }
        
        if (!$ret)
        {
            $form["return"]["close"] = 0;
            $form["return"]["url"] = "";
        }
        
        if ($ajax)
            $this->ajaxReturn($form["return"]);
        return $ret;
    }
    
    //删除卡所有关联信息
    public function delete_card_all($code, $basis_num = 0, $sales_num = 0, $oper_num = 0)
    {
        $card = sqlRow("select code,card,status,eid,bid,opid,card_img1,card_img2,sign_img from card where
                 (code='".$code."' or id='".$code."')");
        if (!$card)
            return false;
        $code = $card["code"];
        
        unlink(__UP__.str_replace(",", "/", $card["card_img1"]));
        unlink(__UP__.str_replace(",", "/", $card["card_img2"]));
        unlink(__UP__.str_replace(",", "/", $card["sign_img"]));
        
        //期数记录
        M("card_installment")->where("code='".$code."'")->delete();
        //操作记录
        M("operating_record")->where("code='".$code."'")->delete();
        //还款记录
        M("repayment_record")->where("put_code='".$code."'")->delete();
        //核心表数据
        M("core_deductive")->where("ccode='".$code."'")->delete();
        M("core_disburse")->where("ccode='".$code."'")->delete();
        M("core_repayment")->where("ccode='".$code."'")->delete();
        //交易记录
        M("transaction")->where("card='".$card["card"]."'")->delete();
        //增值记录
        M("rising")->where("card='".$card["card"]."'")->delete();
        //核对数据记录
        M("deal_inspect")->where("code='".$code."'")->delete();
        //签单、增值记录
        M("expand_bonus")->where("card_code='".$code."'")->delete();
        //删除卡
        M("card")->where("code='".$code."'")->delete();
        //删除临时表
        M("card_op")->where("code='".$code."'")->delete();
        
        if ($basis_num)
            M("basis")->where("id=".$card["bid"]." and card_num>0")->setDec("card_num");
        if ($sales_num && ($card["status"] != DRAFT || $card["status"] == NO_PASS))
            M("users")->where("id=".$card["eid"]." and card_num>0")->setDec("card_num");
        if ($oper_num && ($card["status"] != DRAFT || $card["status"] == NO_PASS))
            M("users")->where("id=".$card["opid"]." and card_num>0")->setDec("card_num");
        
        return true;
    }
    
    //判断此卡是否在本分站解约并且未满3个月
    public function rescindAdd($card)
    {
        $code = sqlCol("select code from card where card='".$card."' and status=".RESCIND." and sid=".$this->sid);
        if ($code)
            $this->delete_card_all($code);
    }
    
    //添加、编辑、草稿提交分发处理函数
    public function cardHandle(& $formObj)
    {
        //判断解约状态重复卡片录入操作
        $this->rescindAdd($_POST["card"]);
        
        //保存卡片原状态
        $_POST["old_status"] = $_POST["status"];
        
        //设置提交成功后局部刷新客户信息窗口
       $basisinfourl = U("Basis/index")."&form=info&where='id=".$_POST["bid"]."'";
       $formObj->form["return"]["callback"] = 'partial_refresh("'.$basisinfourl.'", ".basisinfo");';
        
       //如果是拓展员则默认状态为初审状态
        if ($this->admin == 3 || $this->admin == 4)
            $_POST["status"] = SUB_AUDIT;
        else
        {
            //如果不是拓展员操作，但又为初审状态，代码财务完善资料，此时把卡片的录健人改为财务
            if ($_POST["status"] == SUB_AUDIT)
                $_POST["typing"] = $this->uid;
            
            //获取客户状态, 如果是站长则默认状态为正常，如果客户不为正常状态，则此时卡片状态为添加锁定
            $bstatus = sqlCol("select status from basis where id=".$_POST["bid"]);
            if ($this->admin == 6)
            {
                $_POST["status"] = NORMAL;
                if (($_POST["old_status"] == "" || $_POST["old_status"] == DRAFT) &&
                        $_POST["btnID"] != "save_draft" && !$this->isCardDate($_POST))
                    $this->ajaxReturn(array("echo" => 1, "info" => "警告：此卡离最后还款日期间已经没有还款工作日, 请在最后还款日后添加 !"));
            }
            else 
                $_POST["status"] = $bstatus != NORMAL ? ADD_LOCK : AUDIT;
        }
        
        switch ($_POST["btnID"])
        {
        	case "add_card":
        	    if ($this->cardSave($formObj, false))
        	    {
                   if ($_POST["id"] == "")
                        $cardtag = ".cardadd";
                   else
                        $cardtag = ".cardedit";
                       
        	       $formObj->form["return"]["close"] = 0;
        	       $cardaddurl = U("Card/Index")."&form=add&bid=".$_POST["bid"];
                   $formObj->form["return"]["callback"] .= 'partial_refresh("'.$cardaddurl.'", "'.$cardtag.'");';
        	    }
                $this->ajaxReturn($formObj->form["return"]);
        	    break;
        	case "save_draft":
                $_POST["status"] = DRAFT;
        	    if ($this->cardSave($formObj, false))
        	    {
        	       $formObj->form["return"]["close"] = 1;
        	       $formObj->form["return"]["info"] = "保存草稿成功!";
        	    }
                $this->ajaxReturn($formObj->form["return"]);
        	    break;
        	case "del_card":
//         	    $this->cardDel($formObj);
        	    break;
        	default:
        	    $this->cardSave($formObj, true);
        	    break;
        }
    }
    
    //卡片编辑窗口
    public function cardEdit($con, & $formObj)
    {
        $form = & $formObj->form;
        $el = & $form["element"];
        $obj = & $form["dataObj"];
        
        $data = $obj->where($con)->select();
        $card = $data[0];
        $formObj->formDataShowEdit($data);
        
        if (!($this->admin == 3 || $this->admin == 4))
        {
            $el["opid"]["input_val"] = get_username($card["opid"]);
            $el["costing_per"]["value"] = $card["costing_per"] * 100;
        }
            
        $el["cost"]["value"] *= 100;
        $el["rising_cost"]["value"] *= 100;
//         $el["cost"]["input_val"] *= 100;
//         $el["rising_cost"]["input_val"] *= 100;
        $cdate = explode("-", $card["effective_date"]);
        $el["cdate_year"]["value"] = $cdate[0];
        $el["cdate_month"]["value"] = $cdate[1];
        
        $relate = explode('|', $card["relate"]);
        foreach ($relate as $val)
        {
            $tmp = explode("=", $val);
            $el[$tmp[0]]["value"] = $tmp[1];
        }
        
        
        $formObj->set("btn 0 class", "hidden");
        $formObj->set("btn 1 class", "hidden");
        
        if ($card["status"] == NORMAL || $card["status"] == NO_PASS)
            $formObj->set("btn 2 txt", "确定编辑");
        else if (I("get.audit") == 1)
            $formObj->set("btn 2 txt", "确定提交");
        else
            $formObj->set("btn 1 class", "");
    }

    
    //卡片显示字段公共处理函数
    public function infoField(& $el, $card)
    {
        $usr = sqlRow("select b.name, b.phone1, u.username, u.phone1 as uphone from 
                        basis b, users u where b.eid=u.id and b.id=".$card["bid"]);
        $el["bname"]["value"] = $usr["name"]."&emsp;&emsp;".format_dis_field($usr["phone1"], array(3, 4, 4));
        $el["eid_name"]["value"] = $usr["username"]."&emsp;&emsp;".format_dis_field($usr["uphone"], array(3, 4, 4));
        $el["bname"]["element_cols"] = 3;
        $el["eid_name"]["label_cols"] = 3;
        $el["eid_name"]["element_cols"] = 4;
        $cop_txt = array(1 => "常规服务", 2 => "低值服务", 3 => "高值服务", 4 => "保值服务");
        $el["costing_op"]["value"] = $cop_txt[$card["costing_op"]];
        $el["costing_per"]["value"] = $card["costing"]."元";
        $el["fee"]["value"] .= "元";
        $pt = C("PAYTYPE_TEXT");
        $el["pay_type"]["value"] = $pt[$card["pay_type"]];
        $el["costing"]["value"] = "";
        $el["agreement"]["value"] .= "期";
        $el["amount"]["value"] .= "元";
        $el["bill"]["value"] .= "号";
        $el["save_amount"]["value"] .= "元";
        $el["fact_save"]["value"] .= "元";
        $el["year_fee"]["value"] .= "元";
        $el["counts"]["value"] .= "/笔/月";
        $el["finally_repayment_date"]["value"] = "T + ".$card["finally_repayment_date"];
        if ($card["opid"])
            $el["opid"]["value"] = get_username($card["opid"]);
        $el["cost"]["value"] = field_conv_per($card["cost"]);
        $el["rising_cost"]["value"] = field_conv_per($card["rising_cost"]);
        $cdate = explode("-", $card["effective_date"]);
        $el["cdate_month"]["value"] = $cdate[1]." / ".$cdate[0]."";
        $el["cdate_month"]["element_cols"] = 2;
        $el["cdate_year"]["element_cols"] = 1;
        $el["info_typing"]["value"] = get_username($card["typing"]);
        
        $relate = explode('|', $card["relate"]);
        foreach ($relate as $val)
        {
            $tmp = explode("=", $val);
            $el[$tmp[0]]["value"] = get_bool_txt($tmp[1]);
        }
        $seeimg = new FormElement("img", "link", '查看图片', array("close_label" => 1, "close_element_div" => 1,
                "begin" => 0, "over" => 0, 
               "url" => U("CardOp/auditImgWin")."&id=".$card["id"], "pop" => CardController::getPop("auditimgwin")));
        $el["sign_img"] = array("name" => "sign_img", "type" => "static",  
                "txt" => "信用卡图", "value" => $seeimg->fetch());
    }
 
    //卡片审核通过与不通过填写原因窗口及处理函数
    public function cardAudit()
    {
        if (IS_POST)
        {
            $return = array("echo" => 1, "close" => 1, "info" => "操作成功!",
                    "url" => session("prev_urlcard"), "tag" => ".card", "callback" => "");
            
            $audit = $_POST["audit"];
            if ($this->admin == 1)
                $oper_type = CARD_SUB_NOPASS;
            else
                $oper_type = $audit == 2 ? CARD_AUDIT_NO_PASS : CARD_AUDIT;
            
            if ($audit == 1)
            {
                $card = M("card")->where("code='".$_POST["code"]."'")->find();
                $card["status"] = NORMAL;
                $this->auditHandle($card);
            }
            else
                M("card")->where("code='".$_POST["code"]."'")->setField("status", NO_PASS);
            
            $ret = save_operating_record($_POST["code"], $oper_type);
            
            $this->ajaxReturn($return);
            exit(0);
        }
        
        $aut = new Form("", array("name" => "cardauditform"));
//         $aut->set("kajax", "false");
        $tl = "通过备注";
        $type_id = CARD_AUDIT;
        $value = "";
        
        if (I("get.audit") == 2)
        {
            $tl = "拒绝原因";
            $type_id = CARD_AUDIT_NO_PASS;
        }
        else
            $value = sqlCol("select txt from sel_remark where type_id=".$type_id);
            
        $aut->setElement("audittype", "autocomplete", $tl, array(
                "placeholder" => "自己写".$tl."!",
                "list" => parse_autocomplete("select txt from sel_remark where type_id=".$type_id)));
        $aut->setElement("remark", "textarea", "", array("close_label" => 1, "element_cols" => 12, 
                "rows" => 6, "bool" => "required", "value" => $value));
        $aut->setElement("code", "hidden", "", array("value" => I("get.code")));
        $aut->setElement("audit", "hidden", "", array("value" => I("get.audit")));
        $aut->set("btn 0 txt", "提交");
        echo $aut->fetch();
    }    
    
    //判断站长录入新卡或审核新卡是否在还款期并且是否有还款工作日
    static public function isCardDate($card, $op_date = "")
    {
        if ($op_date == "")
            $op_date = date("Y-m-d");
        $day = date("d", strtotime($op_date));
        $end_num = 0;
        $ispay = is_repay_date($card, $end_num, $op_date);
        if ($ispay)  //判断当前操作日是否在还款期
        {
            //如果是在还款期则算是否有还款工作日，如果没有工作日是则返回假，否则返回真
             $day_arrays = date_filter("", $op_date, 1, $end_num);
             if (count($day_arrays) == 0)
                 return 0;   //在还款期内没有还款工作日
             else
                 return 2;  //在还款期内有还款工作日
        }
        return 1;  //不在还款期内
    }
    
    
    //卡片终审详细信息窗口
    public function masterAudit($formObj, $ext, $card) 
    {
        $alert = "";
        $bool = "";
        
        $formObj->setElementSort($this->el_info);
        
        //判断当前日是否在还款期间，如果是在还款期间，则判断是否有还款工作日，如果没有工作日则审核不通过
//         $card["bill"] = 22;
//         $card["finally_repayment_date"] = 20;  //做测试用的
        if (!$this->isCardDate($card))
        {
            $alert = '<p style="color:red">此卡本期已无可供还款操作的财务工作日，需过了本期最后还款日，方可审核!</p>';
            $bool = "disabled";
        }
        
        $formObj->set("btn 0", array("txt" => "审核通过", "end" => "&emsp;&emsp;", 
                "bool" => $bool, "front" => $alert,
                "ext" => 'type="button"',
                "url" => U("CardOp/auditImgWin")."&audit=1&id=".$card["id"], 
                "pop" => CardController::getPop("auditimgwin", 1),
        ));
        
        $formObj->set("btn 1", array("txt" => "拒绝通过", "ext" => 'type="button"', 
                "ext" => 'type="button"',
                "url" => U("Card/CardOp/cardAudit")."&audit=2&code=".$card["code"],
                "pop" => CardController::getPop("audit", "拒绝通过原因"),
        ));
        $formObj->set("btn 2 class", "hidden");
        $formObj->set("close_btn_up", 1);
        $formObj->set("close_btn_down", 0);
        parse_link_html($formObj->form["btn"]);    
        
        return false;
    }
    
    //卡片详细信息公共代码
    public function commonInfo($con, & $formObj, $auditcall, $pext = array())
    {
    	$obj = & $formObj->form["dataObj"];
        $el = & $formObj->form["element"];
    	$cardlist = $obj->where($con)->select();
        $card = $cardlist[0];
        
        $code = new FormElement("info_code", "static", "内部代码", array("value" => $card["code"]));
        
        $status = new FormElement("info_status", "static", "卡片状态", array(
                 "value" => \KyoCommon\Controller\IndexController::statusLink($card, "card"),
        ));
        $typing = new FormElement("info_typing", "static", "录件人");
        
        $ext = array("card_group" => array("add", $code->get(), $status->get(), $typing->get()),
                    "card_img1" => "del", "card_img2" => "del");
        
        foreach ($pext as $key => $val)
        {
            $ext[$key] = $val;
        } 
        
        $times = new FormElement("info_times", "static", "签约日期", array("value" => $card["times"]));
        $due = new FormElement("info_due", "static", "到期日期", array("value" => $card["due_date"]));
        
        $rising_num = new FormElement("info_rising_num", "static", "增值次数", array("value" => $card["rising_num"]." 次"));
        $rising_amount = new FormElement("info_rising_amount", "static", "增值总额", array("value" => $card["rising_amount_num"]." 元"));
        
        $ext["card"] = array("add", $times->get(), $due->get(), $rising_num->get(), $rising_amount->get());
        
        //如果pext传参为空数组则代表不需要扩展元素
        if (count($pext) == 0)
        {
        	$formObj->formDataInfo($cardlist, $ext);
        	$this->infoField($el, $card);
        }
        
    	if (I("get.audit") == 1)
    	{
            if ($auditcall == 1)
                return $this->masterAudit($formObj, $ext, $card);
            else
                return $this->financeAudit($formObj, $ext, $card);
    	}
        
        $formObj->setElementSort($this->el_info);
        
        //如果不是 1 3 4 6权限是对卡片做不了任何操作
        if (!is_range($this->admin, array(1, 3, 4, 6)))
            return $card;
        
        $status = $card["status"];
        $formObj->set("close_btn_down", 0);
        $formObj->set("btn 0 class", "hidden");
        $formObj->set("btn 1 class", "hidden");
        $formObj->set("btn 2 class", "hidden");
        
        if ($card["typing"] != $this->uid)
            return $card;
        
        if ($status == DRAFT || $status == NO_PASS)
        {
            if ($this->admin == 3 || $this->admin == 4)
                $editpop = CardController::getPop("salesedit");
            else
                $editpop = CardController::getPop("edit");
    		$formObj->set("btn 0", array("txt" => "编辑卡片", "ext" => 'type="button"', 
    		        "end" => "&nbsp;&nbsp;",
                    "url" => U("Card/index")."&form=edit&bid=".$card["bid"]."&where='id=".$card["id"]."'",
                    "pop" => $editpop));
            
            if ($status == DRAFT || $status == NO_PASS)
        		$formObj->set("btn 1", array("txt" => "删除卡片", "ext" => 'type="button"', 
                        "class" => "del_btn",
                        "ext" => 'confirm="确定删除吗?"',
                        "url" => U("Card/index")."&form=del&bid=".$card["bid"]."&where='id=".$card["id"]."'"));
            parse_link_html($formObj->form["btn"]);        
        }
        
        return $card;
    }
    
    //紧急锁卡接口
    public function cardLock()
    {
        $sos = new SosController();
        $sos->lock(session("prev_urlcard"), ".card");
        exit(0);    
    }
    
    //超级管理员卡片信息
    public function adminCardInfo($con, & $formObj)
    {
        $card = $this->commonInfo($con, $formObj, 1, $ext);
        if ($card == false || I("get.list"))
            return false;
        
        if ($card["status"] == NORMAL || $card["status"] > ADD_LOCK)
        {
            $formObj->set("close_btn_down", 0);
            $formObj->set("btn 1 class", "hidden");
            $formObj->set("btn 2 class", "hidden");
            
    		$formObj->set("btn 0", array("txt" => "删除卡片", "ext" => 'type="button"', 
                    "class" => "del_btn",
                    "ext" => 'confirm="确定删除吗?"',
                    "url" => U("Card/index")."&form=del&bid=".$card["bid"]."&where='code=".$card["code"]."'"));
            
            parse_link_html($formObj->form["btn"]);        
        }
    }
    
    //财务、站长卡片详细信息窗口
    public function cardInfo($con, & $formObj)
    {
        $card = $this->commonInfo($con, $formObj, 1, $ext);
        if ($card == false || I("get.list"))
            return false;
        //判断更换卡片在正常状态和锁卡状态下才可以操作
        if (($card["status"] == NORMAL || $card["status"] == LOCK) && 
            ($card["temp_status"] == NORMAL || $card["temp_status"] == UNLOCK || $card["temp_status"] == WAIVE))
        {
    		$formObj->set("btn 1", array("txt" => "更换新卡", "ext" => 'type="button"', 
    		        "end" => "&nbsp;&nbsp;",
                    "url" => U("Lifecycle/changeCard")."&id=".$card["id"]."&list=card",
                    "pop" => LifecycleController::getPop("overdue")));
        }
            
        //卡必须在正常状态下才可以操作以下
        if ($card["status"] == NORMAL && ($card["temp_status"] == NORMAL || $card["temp_status"] == WAIVE)) 
        {
    		$formObj->set("btn 0", array("txt" => "紧急锁卡", "end" => "&nbsp;&nbsp;",
                    "url" => U("CardOp/cardLock")."&code=".$card["code"],
                    "pop" => SosController::getPop("lock"),
                    "ext" => 'type="button" confirm="锁卡操作会影响卡片正常工作，确定锁卡吗？"'));
                
    		$formObj->set("btn 2", array("txt" => "中途续约", "end" => "&nbsp;&nbsp;",
                    "url" => U("Renewal/index")."&cardlist=1&code=".$card["code"],
                    "pop" => RenewalController::getPop("renewal"),
                    "ext" => 'type="button"'));
            
    		$formObj->set("btn 3", array("txt" => "卡片增值", "ext" => 'type="button"', 
    		        "end" => "&nbsp;&nbsp;",
                    "url" => U("Rising/rising")."&cardlist=1&code=".$card["code"],
                    "pop" => RisingController::getPop("rising")));
            
    		$formObj->set("btn 4", array("txt" => "残值临查", "end" => "&nbsp;&nbsp;",
    		        "ext" => 'type="button" confirm="残值临查期间此卡不能做任何操作和交易,确定提交吗?"', 
                    "url" => U("Surplus/request")."&code=".$card["code"]));
            
            if ($this->admin == 6)
            {
        		$formObj->set("btn 5", array("txt" => "编辑卡片", "ext" => 'type="button"', 
        		        "end" => "&nbsp;&nbsp;", 
                        "url" => U("CardOp/masterEdit")."&code=".$card["code"],
                        "pop" => CardController::getPop("medit")));
                
        		$formObj->set("btn 6", array("txt" => "中途解约", "ext" => 'type="button"', 
        		        "end" => "&nbsp;&nbsp;", "class" => "kyo_red",
                        "url" => U("Rescind/rescind")."&code=".$card["code"],
                        "pop" => RescindController::getPop("rescind")));
            }
        }
        parse_link_html($formObj->form["btn"]);        
    }
    
    //正常状态下站长编辑卡片信息
    public function masterEdit($code)
    {
        if (IS_POST)
        {
            $optxt = array(1 => "常规服务", 2 => "低值服务", 3 => "高值服务", 4 => "保值服务");
            
            $this->posOpLimit();
            $_POST["update_time"] = getcurtime();
            $obj = M("card");
            $ret = $obj->create($_POST, 2);
            if (!$ret)
                $this->ajaxReturn(array("echo" => 1, "close" => 1, "info" => "编辑失败!"));  
            $obj->save();
            
            $remark = "";
            if ($_POST["pay_pwd"] != $_POST["pay_pwd"]) 
        		$remark .= "&emsp;支付密码由 ".$_POST["old_pay_pwd"]." 更改为  ".$_POST["pay_pwd"]."<br />";
            
            if ($_POST["old_query_pwd"] != $_POST["query_pwd"]) 
        		$remark .= "&emsp;电话密码由 ".$_POST["old_query_pwd"]." 更改为  ".$_POST["query_pwd"]."<br />";
            
            if ($_POST["old_costing_op"] != $_POST["costing_op"]) 
        		$remark .= "&emsp;操作选项由 ".$optxt[$_POST["old_costing_op"]]." 更改为  ".$optxt[$_POST["costing_op"]]."<br />";
            
            if ($_POST["old_remark"] != $_POST["remark"]) 
        		$remark .= "&emsp;备注由 ".$_POST["old_remark"]." 更改为  ".$_POST["remark"]."<br />";
            
            save_operating_record($_POST["code"], CARD_MOD, $remark);
                
            $this->ajaxReturn(array("echo" => 1, "close" => 1, "info" => "编辑成功!"));  
        }
        
    	$form = new Form("");
    	$card = sqlRow("select b.name, c.id, c.code, c.card, c.pay_pwd, c.query_pwd, c.costing_op, c.remark
						from card c, basis b where c.bid = b.id and c.code='".$code."'");
        
    	$form->setElement("name", "static", "卡主姓名", array("value" => $card["name"]));
    	$form->setElement("card", "static", "卡&emsp;&emsp;号", array("value" => format_dis_field($card["card"])));
        $form->setElement("pay_pwd", "string", "支付密码", array("bool" => "required", "hint" => "num", 
                "min" => 6, "maxlength" => 6, "value" => $card["pay_pwd"]));
        $form->setElement("query_pwd", "string", "电话密码", array("bool" => "required", "hint" => "num", 
            "min" => 6, "maxlength" => 6, "value" => $card["query_pwd"]));
        $form->setElement("costing_op", "select", "操作选项", array("bool" => "required", 
                "value" => $card["costing_op"],
                "list" => parse_select_list("array", array(1, 2, 3, 4), array("常规服务", "低值服务", "高值服务", "保值服务")),
        ));
    	$form->setElement("remark", "textarea", "补充备注", array("value" => $card["remark"]));
    	$form->setElement("id", "hidden", "", array("value" => $card["id"]));
    	$form->setElement("old_pay_pwd", "hidden", "", array("value" => $card["pay_pwd"]));
    	$form->setElement("old_query_pwd", "hidden", "", array("value" => $card["query_pwd"]));
    	$form->setElement("old_costing_op", "hidden", "", array("value" => $card["costing_op"]));
    	$form->setElement("old_remark", "hidden", "", array("value" => $card["remark"]));
    	$form->setElement("code", "hidden", "", array("value" => $card["code"]));
        $form->set("btn 0 txt", "确定编辑");
        
    	echo $form->fetch();
    }
    
    //初审详细信息窗口
    public function financeAudit($formObj, $ext, $card) 
    {
        $formObj->setElementSort($this->fel_info);
        
        $formObj->set("btn 0", array("txt" => "预审通过", "end" => "&emsp;&emsp;", 
                "ext" => 'type="button"',
                "url" => U("CardOp/auditImgWin")."&audit=11&id=".$card["id"], 
                "pop" => CardController::getPop("auditimgwin", 1),
        ));
        $formObj->set("btn 1", array("txt" => "拒绝通过", "ext" => 'type="button"', 
                "ext" => 'type="button"',
                "url" => U("Card/CardOp/cardAudit")."&audit=2&code=".$card["code"],
                "pop" => CardController::getPop("audit", "拒绝通过原因"),
        ));
        $formObj->set("btn 2 class", "hidden");
        $formObj->set("close_btn_up", 1);
        $formObj->set("close_btn_down", 0);
        parse_link_html($formObj->form["btn"]);    
        return false;
    }
    
    //拓展员的卡片详细信息
    public function salesCardInfo($con, & $formObj)
    {
        $card = $this->commonInfo($con, $formObj, 3);
        if ($card == false)
            return false;
        
        $formObj->setElementSort($this->sel_info);
    }
    
    //卡片草稿状态删除处理
    public function cardDel($con, & $formObj)
    {
        $obj = & $formObj->form["dataObj"];
        $con = explode("=", $con);
        $code = $con[1];
        
        $ret = $this->delete_card_all($code, 1, 1, 1);
//         $card = $obj->field("id,bid,code,status,card_img1,card_img2,sign_img")->where($con)->find();
//         unlink(__UP__.str_replace(",", "/", $card["card_img1"]));
//         unlink(__UP__.str_replace(",", "/", $card["card_img2"]));
//         unlink(__UP__.str_replace(",", "/", $card["sign_img"]));
//         $ret = $obj->where($con)->delete();
//         $ret = M("basis")->where("id=".$card["bid"])->setDec("card_num");
//         M("operating_record")->where("code='".$card["code"]."'")->delete();
        $formObj->form["return"]["close"] = 1;
        $formObj->form["return"]["callback"] = '$(".del_btn").closest(".pop_win").find(".pop_close").click();anime_top();';
        $formObj->form["return"]["info"] = $ret ? "删除成功!" : $code."没有匹配到要删除的数据!".$con;
        if ($ret)
            return true;
        return false;    	    
    }    
    
    //审核卡片验证图片信息窗口
    public function auditImgWin()
    {
        $imgcss = 'style="width:360px;height:230px"';
//                 姓名 卡号 有效期  cvv2 银行
        $audit = new Form("", array("name" => "cardimgwin", "cols" => 2, "type" => "info", "kajax" => false));
        $audit->set("close_btn_down", 1);
        
        $card = sqlRow("select b.name,c.id,c.bid,c.code,c.card,c.effective_date as cdate,c.bank,c.cvv2, 
                            c.sign_img,c.card_img1,c.card_img2,c.amount,c.cost,c.rising_cost,c.fee,c.agreement
                from basis b, card c where b.id=c.bid and c.id=".I("get.id"));
        
        $audit->setElement("uname", "static", "姓名", array("value" => $card["name"]));
        $audit->setElement("amount", "static", "授信额度", array("value" => $card["amount"]." 元"));
        $audit->setElement("card", "static", "卡号", array("value" => format_dis_field($card["card"])));
        $audit->setElement("cost", "static", "服务费率", array("value" => field_conv_per($card["cost"])));
        $audit->setElement("bank", "static", "发卡行", array("value" => $card["bank"]));
        $audit->setElement("rising_cost", "static", "增值费率", array("value" => field_conv_per($card["rising_cost"])));
        $audit->setElement("cvv2", "static", "CVN2", array("value" => format_dis_field($card["cvv2"], array(4, 3))));
        $audit->setElement("agreement", "static", "服务期数", array("value" => $card["agreement"]." 期"));
        $cdate = explode("-", $card["cdate"]);
        $cdate = $cdate[1]." / ".$cdate[0];
        $audit->setElement("cdate", "static", "有效期", array("value" => $cdate));
        $audit->setElement("fee", "static", "服务费用", array("value" => $card["fee"]." 元"));
        
        $imghtml = '<img src="'.__UP__.str_replace(",", "/", $card["card_img1"]).'" '.$imgcss.' />&nbsp;&nbsp;';
        $imghtml .= '<img src="'.__UP__.str_replace(",", "/", $card["card_img2"]).'" '.$imgcss.' />&nbsp;&nbsp;';
        $imghtml .= '<img src="'.__UP__.str_replace(",", "/", $card["sign_img"]).'" '.$imgcss.' />';
        $audit->setElement("img", "static", '', array("close_label" => 1, "element_cols" => 12,
               "class" => "text-center",  "value" => $imghtml));
        if (I("get.audit"))
        {
            $audit->set("close_btn_down", 0);
            
            if (I("get.audit") == 11)
            {
                $url = U("Card/index")."&form=edit&audit=1&bid=".$card["bid"]."&where='id=".$card["id"]."'";
                $pop = CardController::getPop("edit", "完善初审卡片资料");
                $txt = "完善资料";
            }
            else
            {
                $url = U("Card/CardOp/cardAudit")."&audit=1&code=".$card["code"];
                $pop = CardController::getPop("audit", "审核通过备注");
                $txt = "审核通过";
            }
            
            $audit->set("btn 0", array("txt" => $txt, "end" => "&emsp;&emsp;", 
                    "ext" => 'type="button"', "url" => $url, "pop" => $pop));
            
            $audit->set("btn 1", array("txt" => "拒绝通过", "ext" => 'type="button"', 
                    "ext" => 'type="button"',
                    "url" => U("Card/CardOp/cardAudit")."&audit=2&code=".$card["code"],
                    "pop" => CardController::getPop("audit", "拒绝通过原因"),
            ));        
        }
        echo $audit->fetch();
    }
    
    //卡片种类图片列表的回调函数
    public function cardTypeImg($data)
    {
        static $flag = 0;
        $html = "";
        $img = 'style="width:160px;height:90px" kctype="'.$data["type"].'" kcname="'.$data["name"].'"';
        
        if ($flag == 0)
            $html = '<tr>';
        
        $html .= '<td>';
        $html .= '<img class="cardtypeImg" src="'.__UP__.str_replace(",", "/", $data["img"]).'" '.$img.' />';
        $html .= '<br />'.$data["type"].' '.$data["name"];
        $html .= '</td>';
        
        if ($flag++ == 4)
        {
            $flag = 0;
            $html .= '</tr>';
        }
        
        return $html;
    }
    
    //卡片种类图片列表
    public function cardTypeList($bank)
    {
        $list = new SmallDataList("card_type_list", "card_type");
        $list->setPage("size", 20);
        $list->setPage("param", "bank=".$bank);
        $list->set("close_num", 1);
        $list->set("close_top_page", 0);
        if (I("get.val"))
            $list->set("where", "bank='".$bank."' and name like '%".I("get.val")."%'");
        else
            $list->set("where", "bank='".$bank."'");
        $list->set("tr_call", array("run", "CardOp/cardTypeImg"));
        echo $list->fetch();
    }
    
    //获取卡片名称窗口
    public function cardTypeWin($bank = "")
    {
         
        $find = new Form("", array("name" => "cardfindform", "ktype" => "info", "kajax" => "true"));
        $find->set("close_btn_down", 1);
        $turl = U("CardOp/cardTypeList").'&bank='.$bank;
        $find->setElement("find_card_type", "autocomplete", "卡片名称", array("bool" => "autofocus required sync", 
                "form" => "", "group" => "start",
                "label_cols" => 5,
                "element_cols" => 3,
                "ext" => 'val_callback="selcardtype(\''.$turl.'\', \'.kyo_index_card_type_list\')"',
                "list" => parse_autocomplete("select name from card_type where bank='".$bank."'"),
              ));
        $find->setElement("btn_find_cardtype", "button", "查询", array("type" => "button", 
                "group" => "end",
               "ext" => 'id="btn_find_cardtype_id" turl="'.$turl.'" tTag=".kyo_index_card_type_list"',
        ));
        
        
        if (!I("get.p"))
            echo $find->fetch()."<br />";
        $this->cardTypeList($bank);
    }
    
    //获取服务期限选择下拉框
    public function getAgreement()
    {
        if (I("get.type"))
            $agreement = new FormElement("agreement", "num", "服务期限", array("bool" => "readonly required", 
                    "close_label" => 1, "close_element_div" => 1, "begin" => 0, "over" => 1, "form" => "cardform",
                    "addon" => "月"));
        else
            $agreement = new FormElement("agreement", "select", "服务期限", array("bool" => "required", 
                    "close_label" => 1,
                    "close_element_div" => 1,
                    "begin" => 0,
                    "over" => 1,
                    "list" => parse_select_list("for", array(1, 12, 1, 1), "请选择服务期限"),
                    "form" => "cardform",
                    "addon" => "月"));
        echo $agreement->fetch();
    }
}