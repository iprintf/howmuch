<?php
namespace Card\Controller;
use Common\Controller\ListPage;
use Common\Controller\FormElement;
use Common\Controller\Form;

class RisingController extends ListPage
{
    private $admin;
    private $uid;
    private $sid;
    
    //构造函数
    public function __construct()
    {
        parent::__construct();
        $this->admin = get_user_info("admin");
        $this->uid = get_user_info("uid");
        $this->sid = get_user_info("sid");
    }   
    
    //获取客户相关弹窗口信息
    static public function getPop($type, $title = "")
    {
        $pop = "";
        switch ($type)
        {
        	case "rising":
                if (!$title)
                    $title = "卡片增值";
                $pop = "w:800,h:480,c:1,n:'risingwin',t:".$title;
        	    break;
        	case "error":
                if (!$title)
                    $title = "卡片增值残值验证失败";
                $pop = "w:800,h:510,c:1,n:'risingwin',t:".$title;
        	    break;
        	case "reject":
            	if (!$title)
        	   		$title = "增值失败原因";
        	   	$pop = "w:480,h:360,c:1,n:'rejectwin',t:".$title;
        	   	break;
        	case "verify":
                if (!$title)
                    $title = "信用卡残值查询";
                $pop = "w:480,h:280,n:'verifywin',t:".$title;
        	    break;
        	default:
        	    break;
        }
        return $pop;
    }
    
    public function disOp($data, $op)
    {
        $status = $data["state"];
        
        $lnk = new FormElement("apply_info", "link", "增值提交", array(
                "url" => U("Rising/rising")."&code=".$data["code"], 
                "pop" => $this->getPop("rising")));      
        
        switch ($status)
        {
        	case AUDIT:
                if ($this->admin == 3)
                    $lnk = new FormElement("apply_info", "link", "增值待审", array(
                            "url" => U("Rising/rising")."&form=info&where='id=".$data["id"]."'&code=".$data["code"], 
                            "pop" => $this->getPop("rising")));      
                else
                    $lnk = new FormElement("apply_info", "link", "增值待审", array(
                            "url" => U("Rising/rising")."&form=edit&where='id=".$data["id"]."'&code=".$data["code"], 
                            "pop" => $this->getPop("rising")));      
        	    break; 
        	case ERROR:
                if ($this->admin == 3)
                    return "残值不足";
                $lnk = new FormElement("apply_info", "link", "残值不足", array(
                        "url" => U("Rising/rising")."&form=edit&error=1&where='id=".$data["id"]."'&code=".$data["code"], 
                        "pop" => $this->getPop("error")));      
                break;
        	case NO_PASS:
                $lnk = new FormElement("apply_info", "link", "增值失败", array(
                        "url" => U("Rising/reject")."&info=1&code=".$data["code"], 
                        "pop" => $this->getPop("reject")));      
        	    break; 
        	case SUB_AUDIT:
                return "等待验证";
        	case NORMAL:
                return "增值已划";
        	case SUCCESS:
                return "增值成功";
        	default:
                break;
        }
        
        return $lnk->fetch();
    }
    
    //增值失败窗口
    public function reject()
    {
        if (IS_POST)
        {
            
            if ($_POST["id"])   //ID为值则为验证失败后提交的增值失败
                M("rising")->where("id=".$_POST["id"])->save(array(
                        "status" => NO_PASS, "update_time" => getcurtime()));
            else   //直接提交增值失败
            {
                $card = array();
                $card = sqlRow("select card,sid,eid,bid from card where code='".$_POST["code"]."'");
                $card["status"] = NO_PASS;
                $card["update_time"] = getcurtime();
                M("rising")->add($card);
            }
            //增值失败修改卡的临时状态为正常状态
            M("card")->where("code='".$_POST["code"]."'")->save(array("temp_status" => NORMAL, "update_time" => getcurtime()));
            
            $ret = save_operating_record($_POST["code"], CARD_RISING_FAIL);
            if (!$ret)
                $this->ajaxReturn(array("echo" => 1, "info" => "操作记录保存失败!"));
            
            $this->ajaxReturn(array("echo" => 1, "close" => 1, "info" => "操作成功!", 
                        "url" => session("prev_urlrising"), "tag" => ".rising"));
        }
        $aut = new Form("", array("name" => "rejectform"));
        $code = I("get.code");
        
        $aut->setElement("remark", "textarea", "", array("close_label" => 1, "element_cols" => 12, 
                "rows" => 8, "bool" => "required"));
        $aut->setElement("code", "hidden", "", array("value" => $code));
        $aut->setElement("id", "hidden", "", array("value" => I("get.id")));
        if (I("get.info"))
        {
            $un = sqlRow("select remark, oper_time from operating_record where code='".$code."' and 
                            type=".CARD_RISING_FAIL." order by oper_time desc");
            $aut->set("element remark bool", "readonly");
            $aut->set("element remark value", $un["remark"]);
            $aut->setElement("waive_txt", "static", "增值失败时间", array( "value" => $un["oper_time"]));
            $aut->set("close_btn_down", 1);
        }
        $aut->set("btn 0 txt", "增值失败");    
        echo $aut->fetch();    
    }
    
    //增值窗口
    public function rising($code)
    {
        $rf = new Form("", array("cols" => 2));
        $rf->set("table", "rising");
        
        $card = sqlRow("select b.name,c.sid,c.eid,c.bid,c.card,c.due_date,c.rising_cost,
                        (c.amount + c.rising_amount_num) as amount, c.card from
                        basis b, card c where b.id=c.bid and c.code='".$code."'");
        
//         dump(M()->getLastSql());
        
        $rf->setElement("name", "static", "姓名", array("value" => $card["name"]));
        $rf->setElement("due_date", "static", "到期日期", array("value" => $card["due_date"]));
        $rf->setElement("card_info", "static", "卡号", array("value" => substr($card["card"], -4)));
        $rf->setElement("amount_info", "static", "现总额度", array("value" => $card["amount"]));
        $rf->setElement("rising_group", "group", "增值信息", array("class" => "text-center"));
        $rf->setElement("amount", "num", "增值额度", array("bool" => "required", "hint" => "money", "addon" => "元"));
//         $rf->setElement("rtype", "radio", "增值类型", array("bool" => "required", 
//                 "list" => parse_select_list("array", array(1, 2), array("固定", "临时"))));
        $rf->setElement("rtype", "radio", "增值类型", array("bool" => "required", 
                "value" => 1,
                "list" => parse_select_list("array", array(1), array("固定"))));
        
        $rf->setElement("img", "file", "增值图片", array("bool" => "required"));
        $rf->setElement("end_date", "date", "临调截止日期");
//         $rf->setElement("settlement", "radio", "结算类型", array("bool" => "required",
//                 "list" => parse_select_list("array", array(1, 2), array("自用", "转贷"))));
        $rf->setElement("settlement", "radio", "结算类型", array("bool" => "required",
                "value" => 1,
                "list" => parse_select_list("array", array(1), array("自用"))));
        if ($this->admin != 3)
        {
            $rf->set("element card_info value", format_dis_field($card["card"]));
//             $rf->setElement("surplus", "radio", "验证残值", array("bool" => "required",
//                     "list" => parse_select_list("array", array(1, 2), array("是", "否"))));
        }
        
        $rf->setElement("sid", "hidden", "", array("value" => $card["sid"]));
        $rf->setElement("eid", "hidden", "", array("value" => $card["eid"]));
        $rf->setElement("bid", "hidden", "", array("value" => $card["bid"]));
        $rf->setElement("card", "hidden", "", array("value" => $card["card"]));
        $rf->setElement("cost", "hidden", "", array("value" => $card["rising_cost"]));
        $rf->setElement("code", "hidden", "", array("value" => $code));
        $rf->setElement("cardlist", "hidden", "", array("value" => I("get.cardlist")));
        
        $rf->set("handle_run post", "Rising/risingPost");
        $rf->set("handle_run info", "Rising/risingInfo");
        $rf->set("handle_run show_edit", "Rising/risingAudit");
        $rf->set("btn 0 txt", "增值提交");
        if (!I("get.cardlist"))
        {
            $rf->set("btn 1", array("txt" => "增值失败", "ext" => 'type="button"', 
    		        "front" => "&nbsp;&nbsp;",
                    "url" => U("Rising/reject")."&code=".$code,
                    "pop" => $this->getPop("reject")));
        }
//         dump($rf->fetch());
        
        $js = '<script type="text/javascript">';
        $js .= '$("#rtype_id0").click(function(){';
        $js .= '$("#end_date_id").prop("disabled", true);';
        $js .= '$("#end_date_id").prop("required", false);';
        $js .= '});';
        $js .= '$("#rtype_id1").click(function(){';
        $js .= '$("#end_date_id").prop("disabled", false);';
        $js .= '$("#end_date_id").prop("required", true);';
        $js .= '});';
        $js .= '</script>';
        
        echo $rf->fetch().$js;
    }
    
    //可增值列表
    public function index()
    {
    	$this->setNav("&nbsp;->&nbsp;卡片操作&nbsp;->&nbsp;增值卡片");
    	$this->mainPage("rising");
        $this->set("close_tool", 1);
        $this->set("close_top_ctrl", 1);
        
        if ($this->admin == 3)
        	$this->setCustomList("pro_card_duemain", true, array(6, "null", $this->uid));
        else
        	$this->setCustomList("pro_card_duemain", true, array(6, $this->sid, "null"));
//     	$this->setCustomList("kyo_test", true);
        $this->setData("op_call", array("run", "Rising/disOp"));
        
    	$this->setData("close_chkall", 1);
    	$this->setTitle(array("数据生成日","客户姓名", "客户电话", "发卡行", "卡号", "现总授信", "此次增值", "已服务期", "拓展员", "拓展员电话","划账日期"));
    	$this->setField(array("dates","bname", "bphones", "bank", "card", "amount", "ramount", "agreement", "username", "uphones","repay_time"));
        $this->setField("card", array("name" => 4, "url" => U("Card/index")."&form=info&list=1&bid=[bid]&where='id=[cid]'",
                "pop" => CardController::getPop("info")));
        if ($this->admin == 3)
        {
            $this->setData("data_field 3 pop", CardController::getPop("salesinfo"));
            $this->setDataHide(array(7, 8));
        }
    	$this->display();    
    }
    
    //增值成功处理函数
    public function risingSuccess($cdata)
    {
        $_POST["status"] = SUCCESS;
        $_POST["rising_date"] = date("Y-m-d");
        if (isset($_POST["amount"]) && $_POST["amount"] != "")
            $amount = $_POST["amount"];
        else
            $amount = sqlCol("select amount from rising where id=".$_POST["id"]);
        //卡片的增值次数和增值额度增加
        sqlCol("update card set rising_amount_num=rising_amount_num + ".$amount.",
                 rising_num=rising_num+1, temp_status=".NORMAL.", update_time='".getcurtime()."' 
                 where card='".$_POST["card"]."'");
        //拓展员增值佣金结算
        sales_bonus($cdata["eid"], $cdata["code"], $amount, 4);
        $obj = M("rising");
        $obj->create($_POST, 2);
        $obj->where("id=".$_POST["id"])->save();
        return CARD_RISING;
    }
    
    //增值残值提交处理函数
    public function surplus($cdata)
    {
        //如果验证金额和推送金额一致则代表增值成功, 否则验证失败
        if ($_POST["old_money"] == $_POST["money"])
             return $this->risingSuccess($cdata);
        else
        {
            $_POST["status"] = ERROR;
            $_POST["temp_rmb"] = $_POST["old_money"]."|".$_POST["money"];
            $oper_type = CARD_RISING_ERROR;
            $obj = M("rising");
            $obj->create($_POST, 2);
            $obj->where("id=".$_POST["id"])->save();
        }
        return $oper_type;
    }
    
    //增值划账
    public function remit()
    {
        if (I("get.where"))
        {
            $curtime = getcurtime();
            $list = session("temp_data");
            $ids = explode("(", I("get.where"));
            $ids = explode(")", $ids[1]);
            $ids = explode(",", $ids[0]);
            
            foreach ($ids as $id) 
            {
                //往还款记录表中写数据
                $record = $list[$id - 1];
                
                $data = array();
                $data["rtype"] = 2;
                $data["account_type"] = 1;
                $data["repay_time"] = $curtime;
                $data["sid"] = $this->sid;
                $data["opid"] = $this->uid;
                $data["put_code"] = $record["code"];
                $data["out_name"] = $record["rid"];    //增值划款  划款户名存此次增值ID
                $data["put_name"] = $record["bname"];
                $data["put_bank"] = $record["bank"];
                $data["put_card"] = $record["card"];
                $data["put_amount"] = $record["remit_amount"];
                $data["fact_amount"] = $record["remit_amount"];
//                 dump(M("rising")->where("id=".$record["rid"])->find());
//                 exit(0);
                M("repayment_record")->add($data);
//     //             //增值状态修改为 增值已划状态
                M("rising")->where("id=".$record["rid"])->save(array("status" => NORMAL, 
                                            "update_time" => $curtime));
            }
            session("temp_data", null);
//             $this->ajaxReturn(array("echo" => 1, "info" => "结算成功，请到划账历史中查询!"));
//             exit(0); 
        }
   	    $this->setNav("&nbsp;->&nbsp;财务管理&nbsp;->&nbsp;增值划账");
    	$this->mainPage("rising");
        $this->setTool("close_tool_find", 1);
        
        $this->setTool("tool_btn_down", array("txt" => "导出报表", "icon" => "cloud-download",
                "url" => U("KyoCommon/Index/excelExport"), 
                "ext" => 'callback=\'$(this).prop("disabled", true)\';')); 
        
//         $this->setBatch("确认还款", U(), array('icon' => "tower", "query" => true, "bool" => "kopen"));
        $this->setBatch("确认划账", U(), array('icon' => "tower", "tag" => "#body", 
                "ext" => 'confirm="请仔细核对划账金额，确认无误请继续!"'));
        
        $this->setData("close_op", 1);
        $this->setData("tooltip", 1);
    	$this->setCustomList("pro_card_duemain",false, array(8, $this->sid, "null"));
        
        //把数据临时缓冲到session表中
        session('temp_data', $this->getData("data_list"));
        
        $title = array("dates" => "数据生成日","bname" => "增值客户", "rcard" => "增值卡片", "amount" => "此次增值", 
        			"poundage" => "增值扣费", "remit_amount" => "实划增值", "card_name" => "储蓄账户", "bank" => "开户行", 
        			"bank_addr" => "支行名称","card" => "收款增值账号" );#,  "uphones" => "拓展员电话");
        $_SESSION["excel"]["title"] = $title;
        $_SESSION["excel"]["filename"] = "增值划款报表";
        $_SESSION["excel"]["data"] = $this->getData("data_list");
        
    	$this->setTitle($title);
    	$this->setField(array_keys($title));
        
    	$this->display();    
    }
    
    //拓展员增值待审查看详细信息
    public function risingInfo($con, & $formObj)
    {
        $el = & $formObj->form["element"];
    	$rising = M("rising")->where($con)->find();
        $el["amount"]["type"] = "static";
        $el["rtype"]["type"] = "static";
        $el["settlement"]["type"] = "static";
        $el["end_date"]["type"] = "static";
        $el["img"]["info"] = 1;
        $el["img"]["value"] = $rising["img"];
        $el["amount"]["value"] = $rising["amount"]." 元";
        $el["rtype"]["value"] = "临时增值";
        if ($rising["rtype"] == 1)
            $el["rtype"]["value"] = "固定增值";
        $el["settlement"]["value"] = "自用";
        if ($rising["settlement"] == 1)
            $el["settlement"]["value"] = "转贷";
        $el["end_date"]["value"] = $rising["end_date"];
        $formObj->setElement("submit_time", "static", "提交时间", array("value" => $rising["create_time"]));
    }
    
    //增值待审，修改增值信息窗口
    public function risingAudit($con, & $formObj)
    {
        $el = & $formObj->form["element"];
    	$rising = M("rising")->where($con)->find();
        $el["amount"]["value"] = $rising["amount"];
        $el["rtype"]["value"] = $rising["rtype"];
        $el["settlement"]["value"] = $rising["settlement"];
        if ($rising["rtype"] == 1)
            $el["end_date"]["bool"] = "disabled";
        $el["end_date"]["value"] = $rising["end_date"];
        $el["img"]["value"] = $rising["img"];
        if (I("get.error"))
        {
            $trmb = explode("|", $rising["temp_rmb"]);
            $formObj->setElement("surplus1_info", "static", "卡内应有残值", array("value" => $trmb[0]." 元"));
            $formObj->setElement("surplus2_info", "static", "实际卡内残值", array("value" => $trmb[1]." 元", 
                    "label_cols" => "8"));
            $formObj->setElement("verify", "hidden", "", array("value" => "1"));
            $formObj->set("btn 0 txt", "确定增值");
            
            parse_link_html($formObj->form["btn"]);
        }
        $formObj->setElement("id", "hidden", "", array("value" => $rising["id"]));
    }
    
    
    //增值提交处理函数
    public function risingPost(& $formObj)
    {
        $name = "rising";
        if ($_POST["cardlist"])
            $name = "card";
		$return = array("echo" => 1, "close" => 1, "info" => "操作成功!",
                "url" => session("prev_url".$name), "tag" => ".".$name, "callback" => "");
        
		$obj = M("rising");
        $_POST["update_time"] = getcurtime();
        $_POST["poundage"] = $_POST["amount"] * $_POST["cost"];
        
        //如果是拓展员，提交增值状态为等待审核
        if ($this->admin == 3)
        {
            $_POST["status"] = AUDIT;
            $oper_type = CARD_RISING_APPLY;
        }
        else
        {
            $_POST["status"] = SUB_AUDIT;
            $oper_type = CARD_RISING_VERIFY;
        }
        
        if ($_POST["id"]) //只有财务和站长有权限修改增值表信息，则代表审核拓展员提交数据和验证失败修改额度提交
        {
            if ($_POST["verify"])  //金额验证失败后，修改金额提交过来保存为增值成功状态
                $oper_type = $this->risingSuccess($_POST);
            else   //处理拓展员提交增值
            {
                $obj->create($_POST, 2);
                $obj->save();
            }
        }
        else
        {
            $_POST["create_time"] = getcurtime();
            $obj->create($_POST, 1);
            $obj->add();
            //提交增值 改变卡的临时状态  标识正在增值中
            M("card")->where("code='".$_POST["code"]."'")->save(array("temp_status" => RISING, 
                    "update_time" => $_POST["update_time"]));
            //发送申请提醒站内信
            if ($oper_type == CARD_RISING_APPLY)
                auto_send_msg("卡片增值审核申请", "拓展员 ".get_username($this->uid)." 提交了一张卡片增值请求, 请 ".parse_msg_link(U("Card/Rising/index")."&ibody=1")." 做增值查验!");
        }
        
        //保存操作记录
		save_operating_record($_POST["code"], $oper_type);
    		
		$this->ajaxReturn($return);
    }
}
