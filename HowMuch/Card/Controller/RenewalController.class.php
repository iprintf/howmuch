<?php
namespace Card\Controller;
use Common\Controller\ListPage;
use Common\Controller\FormElement;
use Common\Controller\Form;

class RenewalController extends ListPage
{
    private $admin;
    private $uid;
    
    //构造函数
    public function __construct()
    {
        parent::__construct();
        $this->admin = get_user_info("admin");
        $this->uid = get_user_info("uid");
    }   
        
    //获取客户相关弹窗口信息
    static public function getPop($type, $title = "")
    {
        $pop = "";
        switch ($type)
        {
        	case "renewal":
                if (!$title)
                    $title = "续约";
                $pop = "w:900,h:520,c:1,n:'renewalwin',t:".$title;
        	    break;
        	case "reject":
                if (!$title)
                    $title = "续约拒绝";
                $pop = "w:900,h:570,c:1,n:'renewalwin',t:".$title;
        	    break;
                break;
        	case "small":
            	if (!$title)
        	   		$title = "放弃续约理由";
        	   	$pop = "w:480,h:360,c:1,n:'auditwin',t:".$title;
        	   	break;
        	case "info":
           		if (!$title)
           			$title = "续约申请信息";
           		$pop = "w:900,h:530,n:'renewalinfo',t:".$title;
           		break;
        	case "audit":
           		if (!$title)
           			$title = "续约申请审核";
           		$pop = "w:900,h:570,n:'renewalaudit',t:".$title;
           		break;
        	default:
        	    break;
        }
        return $pop;
    }
    
    //续约窗口
    public function index()
    {
    	$form = new Form("");
        $code = I("get.code");
        $apply = I("get.apply");
    	$form->set("cols", 2);
    	$card = sqlRow("select b.name, c.code, c.card, c.amount + c.rising_amount_num as amount,
    						c.agreement, c.effective_date, c.temp_status,c.times, c.bill,
    	                    c.finally_repayment_date,c.due_date,c.repayment,c.fee,c.eid,
                            round(u.award_min * 100, 0) as award_min,
    	                    round(u.signing_min * 100, 1) as signing_min,
    						round(c.cost * 100, 1) as cost,
    						round(c.rising_cost * 100, 0) as rising_cost
						from
    						card c, basis b, users u
						where
    						c.bid = b.id and c.eid = u.id and c.status = 0 and c.code='".$code."'");
        
//     	dump(M()->getLastSql());
        
        $op_type = CARD_RENEWAL;
        $btn_txt = "确认续约";
        if ($apply == 1)
        {
            $op_type = CARD_RENEWAL_APPLY;
            $btn_txt = "提交续约";
        }
        
        //已服务期数
        //    1. 按签约日期计算签约时是否在还款期 如果在还款期则加一期
        //    2. 按当前续约日期计算是否在还款期，如果在还款期则加一期
        //    3. 从签约日期到续约日期计算有多少个月
        
        //计算已服务的期数
        $srved_num = date("m", strtotime(date("Y-m-d")) - strtotime($card["times"]));
        $end_num = 0;
        if (is_repay_date($card, $end_num) != 2)
            $srved_num -= 1;
        if (CardOpController::isCardDate($card, $card["times"]) == 2)
            $srved_num += 1;
        
        $cost = 1.2;
        $rising_cost = 3;
        if ($this->admin == 3)
        {
            $cost = $card["signing_min"];
            $rising_cost = $card["award_min"];
        }
        
    	$form->setElement("name", "static", "姓名", array("value" => $card["name"]));
    	$form->setElement("amount_info", "static", "总额度", array("value" => $card["amount"]." 元"));
    	$form->setElement("card", "static", "卡号", array("value" => format_dis_field($card["card"])));
        $cdate = explode("-", $card["effective_date"]);
    	$form->setElement("effective_date", "static", "卡片有效期", array("value" => $cdate[1]." / ".$cdate[0]));
    	$form->setElement("fenzu", "group", "签约信息", array("class"=> "text-center"));
        
        $form->setElement("agreement_info", "static", "原服务期限", array("value" => $card["agreement"]." 期"));
        $form->setElement("agreement", "select", "续约-服务期限", array("bool" => "required", 
                "list" => parse_select_list("for", array(1, (12 - $card["agreement"]), 1, 1), "请选择服务期限"),
                "addon" => "月", "id" => 'new_agreement_id'));
        
    	$form->setElement("agreement_old_info", "static", "已服务期数", array("value" => $srved_num." 期"));
        $form->setElement("fee", "num", "续约-服务费用", array("bool" => "required readonly",
                "addon" => "元", "id" => "new_fee_id"));
        
    	$form->setElement("cost_info", "static", "原服务费率", array("value" => $card["cost"]." %"));
//     	$form->setElement("cost", "num", "续约-服务费率", array("id" => "new_cost_id", 
//     	        "bool" => "readonly", "addon" => "%", "value" => $card["cost"]));
        $form->setElement("cost", "select", "续约-服务费率", array("bool" => "required",
                "list" => parse_select_list("for", array((float)$cost, 5.1, 0.1, 1), "请选择服务费率"),
                "id" => "new_cost_id", "addon" => "%", "value" => $card["cost"]));
        
    	$form->setElement("rising_cost_info", "static", "原增值费率", array("value" => $card["rising_cost"]." %"));
//     	$form->setElement("rising_cost", "num", "续约-增值费率", array(
//     	        "bool" => "readonly", "addon" => "%", "value" => $card["rising_cost"]));
    	        
        $form->setElement("rising_cost", "select", "续约-增值费率", array("bool" => "required",
                "list" => parse_select_list("for", array($rising_cost, 25, 1, 1), "请选择增值费率"),
                "addon" => "%", "value" => $card["rising_cost"]));
        
    	$form->setElement("code", "hidden", "", array("value" => $card["code"]));
    	$form->setElement("op_type", "hidden", "", array("value" => $op_type));
    	$form->setElement("temp_status", "hidden", "", array("value" => $card["temp_status"]));
    	$form->setElement("old_amount", "hidden", "", array("value" => $card["amount"]));
    	$form->setElement("finally_repayment_date", "hidden", "", array("value" => $card["finally_repayment_date"]));
    	$form->setElement("bill", "hidden", "", array("value" => $card["bill"]));
    	$form->setElement("due_date", "hidden", "", array("value" => $card["due_date"]));
    	$form->setElement("repayment", "hidden", "", array("value" => $card["repayment"]));
    	$form->setElement("old_fee", "hidden", "", array("value" => $card["fee"]));
    	$form->setElement("old_times", "hidden", "", array("value" => $card["times"]));
    	$form->setElement("old_eid", "hidden", "", array("value" => $card["eid"]));
        if ($apply != 1)
        {
        	$form->setElement("old_agreement", "hidden", "", array("value" => $card["agreement"]));
        	$form->setElement("old_cost", "hidden", "", array("value" => $card["cost"]));
        	$form->setElement("old_rising_cost", "hidden", "", array("value" => $card["rising_cost"]));
        }
        
        $js = ' function renewal_fee()
                {
                    var mval = $("#new_agreement_id").val();
                    var cval = $("#new_cost_id").val();
                    if (mval && cval)
                        $("#new_fee_id").val(($("#old_amount_id").val() * (cval / 100) * mval).toFixed(0));
                }
                $("#new_agreement_id").change(renewal_fee);
                $("#new_cost_id").change(renewal_fee);';
        
        $form->set("handle_run post", "Renewal/renewalPost");
        $form->set("handle_run show_edit", "Renewal/renewalEdit");
        $form->set("handle_run info", "Renewal/renewalInfo");
//         $form->set("kajax", "false");
        if (($this->admin == 3 || $this->admin == 4) && $card["temp_status"])
            $btn_txt = "重新提交";
    	
        if (!I("get.cardlist"))
        {
            if (($this->admin == 1 || $this->admin == 6) && $card["temp_status"])
            {
                $btn_txt = "同意续约";
            	$form->setBtn("拒绝续约", U("Renewal/reject")."&code=".$card["code"], array(
            	        "pop" => $this->getPop("small", "拒绝续约理由"), "ext" => 'type="button" confirm="确定拒绝续约吗？"'));
            }
            else 
            	$form->setBtn("放弃续约", U("Renewal/waive")."&code=".$card["code"], array(
            	        "pop" => $this->getPop("small"), "ext" => 'type="button" confirm="确定放弃续约吗？"'));
        }
        //站长 、财务提示是否结算收钱
        if ($this->admin == 1 || $this->admin == 6)
            $form->set("btn 0 ext", 'type="submit" confirm="此卡续约服务费是否收取？"');
        
    	$form->set("btn 0 txt", $btn_txt);
    	
    	echo $form->fetch().js_head($js);
    }
    
    //放弃续约窗口及处理 还有放弃续约信息查看
    public function waive()
    {
        if (IS_POST)
        {
            M("card")->where("code='".$_POST["code"]."'")->save(array(
                    "temp_status" => WAIVE, "update_time" => getcurtime()));
            
            $ret = save_operating_record($_POST["code"], CARD_RENEWAL_WAIVE);
            if (!$ret)
                $this->ajaxReturn(array("echo" => 1, "info" => "操作记录保存失败!"));
            
            $this->ajaxReturn(array("echo" => 1, "close" => 1, "info" => "操作成功!", 
                        "url" => session("prev_urldueing"), "tag" => ".dueing"));
        }
        $aut = new Form("", array("name" => "waiveform"));
        $code = I("get.code");
        
        $aut->setElement("remark", "textarea", "", array("close_label" => 1, "element_cols" => 12, 
                "rows" => 8, "bool" => "required"));
        $aut->setElement("code", "hidden", "", array("value" => I("get.code")));
        if (I("get.info"))
        {
            $un = sqlRow("select remark, oper_time from operating_record where code='".$code."' and 
                            type=".CARD_RENEWAL_WAIVE." order by oper_time desc");
            $aut->set("element remark bool", "readonly");
            $aut->set("element remark value", $un["remark"]);
            $aut->setElement("waive_txt", "static", "放弃续约时间", array( "value" => $un["oper_time"]));
            $aut->set("close_btn_down", 1);
        }
        $aut->set("btn 0 txt", "放弃续约");    
        echo $aut->fetch();
    }
    
    //拒绝续约窗口及处理 还有拒绝续约信息查看
    public function reject()
    {
        if (IS_POST)
        {
            M("card")->where("code='".$_POST["code"]."'")->save(array(
                    "temp_status" => NO_PASS, "update_time" => getcurtime()));
            
            $ret = save_operating_record($_POST["code"], CARD_RENEWAL_NOPASS);
            if (!$ret)
                $this->ajaxReturn(array("echo" => 1, "info" => "操作记录保存失败!"));
            $this->ajaxReturn(array("echo" => 1, "close" => 1, "info" => "操作成功!", 
                        "url" => session("prev_urldueing"), "tag" => ".dueing"));
        }
        $aut = new Form("", array("name" => "rejectform"));
        $code = I("get.code");
        
        $aut->setElement("remark", "textarea", "", array("close_label" => 1, "element_cols" => 12, 
                "rows" => 8, "bool" => "required"));
        $aut->setElement("code", "hidden", "", array("value" => I("get.code")));
        
        $aut->set("btn 0 txt", "拒绝续约");    
        echo $aut->fetch();
    }    
    
    //拓展员续约操作接口
    public function salesOp($data)
    {
        if ($data["temp_status"] == WAIVE || 
                    sqlCol("select id from operating_record where type='".CARD_RENEWAL_WAIVE."' and code='".$data["code"]."'"))
            $lnk = new FormElement("waive", "link", "放弃续约", array(
                    "class" => "kyo_black",
                    "url" => U("Renewal/waive")."&code=".$data["code"]."&info=1", 
                    "pop" => $this->getPop("small", "放弃续约理由")));        
        else if ($data["temp_status"] == APPLY)
            $lnk = new FormElement("audit", "link", "续约待审", array(
                    "url" => U("Renewal/index")."&code=".$data["code"]."&form=info&where='id=[id]'", 
                    "class" => "text-success",
                    "pop" => $this->getPop("info")));        
        else if ($data["temp_status"] == NO_PASS)
            $lnk = new FormElement("reject", "link", "平台拒续", array(
                    "url" => U("Renewal/index")."&code=".$data["code"]."&apply=1&form=edit&where='id=[id]'", 
                    "class" => "text-danger",
                    "pop" => $this->getPop("reject")));        
        else
            $lnk = new FormElement("apply", "link", "续约申请", array(
                    "url" => U("Renewal/index")."&code=".$data["code"]."&apply=1", 
                    "pop" => $this->getPop("renewal", "续约申请")));        
        
        return $lnk->fetch();
    }
    
    //续约操作接口
    public function disOp($data, $op)
    {
        if ($this->admin == 3 || $this->admin == 4)
            return $this->salesOp($data);
        
        if ($data["temp_status"] == WAIVE ||
                    sqlCol("select id from operating_record where type='".CARD_RENEWAL_WAIVE."' and code='".$data["code"]."'"))
            $lnk = new FormElement("waive", "link", "放弃续约", array(
                    "url" => U("Renewal/waive")."&code=".$data["code"]."&info=1", 
                    "class" => "kyo_black",
                    "pop" => $this->getPop("small", "放弃续约理由")));        
        else if ($data["temp_status"] == APPLY)
            $lnk = new FormElement("audit", "link", "续约待审", array(
                    "url" => U("Renewal/index")."&code=".$data["code"]."&form=edit&where='id=[id]'", 
                    "class" => "text-success",
                    "pop" => $this->getPop("audit")));        
        else if ($data["temp_status"] == NO_PASS)
            $lnk = new FormElement("reject", "link", "平台拒续", array(
                    "url" => U("Renewal/index")."&code=".$data["code"]."&form=info&where='id=[id]'", 
                    "class" => "text-danger",
                    "pop" => $this->getPop("info", "平台拒绝续约")));        
        else
            $lnk = new FormElement("reject", "link", "确认续约", array(
                    "url" => U("Renewal/index")."&code=".$data["code"]."&apply=0", 
                    "pop" => $this->getPop("renewal")));        
        
        return $lnk->fetch();
    }
    
    //查看拒绝申请的操作日志
    public function opRecord($code, & $formObj, $op_type = CARD_RENEWAL_NOPASS, $op_txt = "提交")
    {
        
        $op = sqlRow("select o.oper_time,u.type,u.username,c.card from operating_record o, card c, users u 
                       where c.code=o.code and o.opid=u.id and o.code='".$code."' 
                        and o.type=".$op_type." order by o.oper_time desc");
        if ($op)
        {
            $r = new FormElement("oper", "link", "点击这里", array(
            "begin" => 0, "over" => 0, "close_element_div" => 1,
            "url" => U("KyoCommon/Index/showOpRecord")."&code=".$code, 
            "pop" => "w:1000,h:1000,n:'oprecord't:".format_dis_field($op["card"])." 操作记录"));
            
            $html = "此卡续约申请于&emsp;".$op["oper_time"]."&emsp;被&emsp;".get_perm_name($op["type"]);
            $html .= " / ".$op["username"]."&emsp;".$op_txt.", 详情&emsp;".$r->fetch()."&emsp;查看!";
            $formObj->setElement("reject_mess", "static", "", array("close_label" => 1, 
                    "element_cols" => 12, "class" => "col-md-offset-2", "value" => $html));    
        }
    }
    
    //拓展员续约待审
    public function renewalInfo($con, & $formObj)
    {
        $el = & $formObj->form["element"];
        $code = I("get.code");
    	$card = M("card_op")->where("code='".$code."'")->find();
        $el["cost"]["type"] = "static";
        $el["rising_cost"]["type"] = "static";
        $el["fee"]["type"] = "static";
        $el["agreement"]["type"] = "static";
        $el["fee"]["value"] = $card["fee"]." 元";
        $el["cost"]["value"] = ($card["cost"] * 100)." %";
        $el["rising_cost"]["value"] = ($card["rising_cost"] * 100)." %";
        $el["agreement"]["value"] = $card["agreement"]." 个月";
        $this->opRecord($code, $formObj, CARD_RENEWAL_APPLY, "提交");
    }
    
    //拓展员平台拒续  财务的续约待审
    public function renewalEdit($con, & $formObj)
    {
        $el = & $formObj->form["element"];
        $code = I("get.code");
    	$card = M("card_op")->where("code='".$code."'")->find();
        $el["fee"]["value"] = $card["fee"];
        $el["cost"]["value"] = $card["cost"] * 100;
        $el["rising_cost"]["value"] = $card["rising_cost"] * 100;
        $el["agreement"]["value"] = $card["agreement"];
        
        $op_txt = "拒绝";
        $op_type = CARD_RENEWAL_NOPASS;
        if ($this->admin == 1 || $this->admin == 6)
        {
            $op_type = CARD_RENEWAL_APPLY;
            $op_txt = "提交";
        }
        $this->opRecord($code, $formObj, $op_type, $op_txt);
    }    
    
    //续约申请和确认续约的处理
    public function renewalPost(& $formObj)
    {
        $return = array("echo" => 1, "close" => 1, "info" => "操作成功!",
                    "url" => session("prev_urldueing"), "tag" => ".dueing", "callback" => "");
            
        $_POST["update_time"] = getcurtime();
        $_POST["cost"] /= 100;
        $_POST["rising_cost"] /= 100;
            
        //续约申请
        if ($_POST["op_type"] == CARD_RENEWAL_APPLY)
        {
    		$obj = M("card_op");
            if ($_POST["temp_status"])  //再次续约申请
            {
                $obj->create($_POST, 2);
                $obj->save();
            }
            else
            {
                $obj->create($_POST, 1);
                $obj->add();
            }
            M("card")->where("code='".$_POST["code"]."'")->save(array(
                    "temp_status" => APPLY, "update_time" => getcurtime()));
            auto_send_msg("即/到期卡片续约申请", "拓展员".get_username($this->uid)." 提交了一张即/到期卡片的续约请求, 请 ".parse_msg_link(U("Card/Lifecycle/index")."&ibody=1")." 对此卡进行审核处理!");
        }
        else  //续约
        {
            $obj = M("card");
            $_POST["temp_status"] = 0;
                
            //到期日期重新计算   两种情况：1. 在服务期内续约， 2. 在服务期后续约
            $_POST["agreement"] += $_POST["old_agreement"];   //使用新的期数计算到期日期
            $due_time = strtotime($_POST["due_date"]);   //获取原到期时间的时间戳
            if ($due_time > strtotime(date("Y-m-d")))   //如果原到期时间戳大于当前时间戳代表为中途续约
                $_POST["due_date"] = CardOpController::getDueDate($_POST, $_POST["times"]);
            else
                $_POST["due_date"] = date("Y-m-d", strtotime("+".$_POST["agreement"]." month", $due_time));
                
            //服务费用重新计算
            $_POST["fee"] += $_POST["old_fee"];
            $ret = $obj->create($_POST, 2);
            $obj->where("code='".$_POST["code"]."'")->save();
            //拓展员签单佣金结算
            sales_bonus($_POST["old_eid"], $_POST["code"], $_POST["old_amount"], 6);
                
            M("card_op")->where("code='".$_POST["code"]."'")->delete();
            //删除放弃续约的操作日志，保存此卡流程往后的正确性
            M("operating_record")->where("code='".$_POST["code"]."' and type='".CARD_RENEWAL_WAIVE."'")->delete();
                
            //更新卡片的期数设置
            update_card_installment("", $_POST);
                
            //如果是确认续约时，则将把修改情况保存到操作记录中，如果只是续约申请则不需要保存
    		$_POST["remark"] .= "&emsp;服务期数由 ".$_POST["old_agreement"]." 期 更改为  ".$_POST["agreement"]." 期<br />";
    		$_POST["remark"] .= "&emsp;服务费用由 ".$_POST["old_fee"]." 元 更改为  ".$_POST["fee"]." 元<br />";
            $_POST["cost"] *= 100;
            $_POST["rising_cost"] *= 100;
            if ($_POST["old_cost"] != $_POST["cost"])
        		$_POST["remark"] .= "&emsp;服务费率由 ".$_POST["old_cost"]."% 更改为  ".$_POST["cost"]."%<br />";
            if ($_POST["old_rising_cost"] != $_POST["rising_cost"])
        		$_POST["remark"] .= "&emsp;增值费率由 ".$_POST["old_rising_cost"]."% 更改为  ".$_POST["rising_cost"]."%<br />";
        }
            
        //保存操作记录
		save_operating_record($_POST["code"], $_POST["op_type"]);
    		
		$this->ajaxReturn($return);
    }
}