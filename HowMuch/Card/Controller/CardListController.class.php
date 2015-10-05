<?php
namespace Card\Controller;
use Common\Controller\ListPage;
use Common\Controller\FormElement;
use Common\Controller\Form;

class CardListController extends ListPage
{
    //获取客户相关弹窗口信息
    static public function getPop($type, $title = "")
    {
        $pop = "";
        switch ($type)
        {
        	case "add":
                if (!$title)
                    $title = "卡片续约";
                $pop = "w:800,h:500,n:'bankadd',t:".$title;
        	    break;
        	case "off":
            	if (!$title)
        	   		$title = "卡片解约";
        	   	$pop = "w:800,h:450,n:'cardoff',t:".$title;
        	   	break;
        	case "overdue":
           		if (!$title)
           			$title = "更新卡片";
           		$pop = "w:800,h:450,n:'overdue_card',t:".$title;
           		break;
        	case "edit":
                if (!$title)
                    $title = "编辑信用卡资料";
                $pop = "w:1100,h:1000,c:1,n:'cardedit',t:".$title;
        	    break;
        	case "info":
                if (!$title)
                    $title = "信用卡详细信息";
                $pop = "w:1000,h:940,n:'cardinfo',t:".$title;
        	    break;
        	case "auditinfo":
                if (!$title)
                    $title = "信用卡审核详细信息";
                $pop = "w:1000,h:940,n:'auditcardinfo',t:".$title;
        	    break;
     	case "audit":
                if (!$title)
                    $title = "信用卡审核";
                $pop = "w:480,h:360,n:'cardaudit',t:".$title;
        	    break;                
        	default:
        	    break;
        }
        return $pop;
    }
    
//-----------------------------即期列表-code-----------------------------
 
    public function due_list()
    {
    	$this->setNav("&nbsp;->&nbsp;即期卡片");
    	$this->mainPage("card");
    	$this->setFind("typelist name", array("txt" => "客户姓名", "val" => "name"));//增加搜索选择
        $this->setFind("typelist card", array("txt" => "客户卡号", "val" => "card"));
    	$this->setCustomList("pro_card_duemain",true,array(1,get_user_info("sid"), 
    			"'".$_POST["search_type"]."'", "'".$_POST["search_key"]."'"));
//     	dump(M()->getLastSql()); //调试
    	$this->setOp("续约", U("CardList/renewal_card")."&id=[id]",array("pop" => $this->getPop("add")));
    	//$this->setData("close_op", 1);
    	$this->setData("close_chkall", 1);
    	$this->setTitle(array("内部代码", "姓名", "发卡行", "卡号", "初始额度", "现总额度", "增值次数", "即期日期", "剩余天数","联系电话"));
    	$this->setField(array("code", "name", "bank", "card", "amount", "amount_num", "rising_num", "due_date","days","phone1"));
    	$this->display();
    }
    
 
    public function renewal_card()
    {
    	if (IS_POST)
    	{
    		$return = array("echo" => 1, "close" => 1, "info" => "操作成功!",
                    "url" => U("CardList/due_list"), "tag" => "#body", "callback" => "");
    		$obj = M("card_op");
    		
    		save_operating_record($_POST["code"], CARD_RENEWAL, "续约");
    		
    		$obj->create($_POST, 1);
    		$obj->add();
    		
    		$this->ajaxReturn($return);
    		
			exit(0);    		
    	}
    	$form = new Form();
    	$form->set("cols", 2);
    	$card = sqlRow(" select 
    						c.id as id,
    						b.name as name,
    						c.code as code,
    						c.card as card,
    						c.amount + c.rising_amount_num as amount,
    						c.agreement as agreement,
    						c.effective_date as effective_date,
    						c.cost as cost,
    						c.rising_cost as rising_cost,
    						(case c.pay_type 
    							when 1 then '卡内留存'
    							when 2 then '现金预付'
    							when 3 then '增值后补'
    							else '未选择' end) as pay_type
						from
    						card c, basis b
						where
    						c.bid = b.id and c.status = 0 and c.id =".I("get.id"));
//     	dump(I("get.id"));
    	
    	$form->setElement("name", "static", "姓名", array("value" => $card["name"]));
    	$form->setElement("amount", "static", "总额度", array("value" => $card["amount"]." 元"));
    	$form->setElement("card", "static", "卡号", array("value" => $card["card"]));
    	$form->setElement("effective_date", "static", "卡片有效期", array("value" => $card["effective_date"]));
    	$form->setElement("fenzu", "group", "签约信息", array("class"=> "text-center","value" => $card["fenzu"]));
    	$form->setElement("agreement_info", "static", "服务期限", array("value" => $card["agreement"]." 月"));
        $form->setElement("agreement", "select", "续约-服务期限", array("bool" => "required", 
                "list" => parse_select_list("for", array(1, 12, 1, 1), "请选择服务期限"),
                "addon" => "月",
        ));
    	$form->setElement("cost_info", "static", "服务费率", array("value" => $card["cost"]." %"));
        $form->setElement("cost", "select", "续约-服务费率", array("bool" => "required",
                "list" => parse_select_list("for", array(1.2, 2.1, 0.1, 1), "请选择服务费率"),
                "addon" => "%",
        ));
    	$form->setElement("rising_cost_info", "static", "增值费率", array("value" => $card["rising_cost"]." %"));
        $form->setElement("rising_cost", "select", "续约-增值费率", array("bool" => "required",
                "list" => parse_select_list("for", array(3, 15, 1, 1), "请选择增值费率"),
                "addon" => "%",
        ));
        $form->setElement("pay_type_info", "static", "支付方式", array("value" => $card["pay_type"]));
        $form->setElement("pay_type", "select", "支付方式", array("bool" => "required",
        		"list" => parse_select_list("array", array(1, 2, 3), array("卡内留存", "现金预付", "增值后补"), "请选择支付方式"),
        ));
    	$form->setElement("code", "hidden", "", array("value" => $card["code"]));
    	
    	$form->set("btn 0 txt", "续约");
    	
    	echo $form->fetch();
    }
    
//-----------------------------卡片有效期，即将失效列表-code-----------------------------
    public function expiring_list()
    {
    	$this->setNav("&nbsp;->&nbsp;卡片有效期，即将失效卡片");
    	$this->mainPage("card");
		$this->setFind("typelist name", array("txt" => "客户姓名", "val" => "name"));//增加搜索选择
    	$this->setFind("typelist card", array("txt" => "客户卡号", "val" => "card"));
    	$this->setCustomList("pro_card_duemain",true,array(2,get_user_info("sid"),
    			"'".$_POST["search_type"]."'", "'".$_POST["search_key"]."'"));
    	$this->setOp("更换新卡", U("CardList/overdue_card")."&id=[id]",array("pop" => $this->getPop("overdue")));;
//     	$this->setData("close_op", 1);
    	$this->setData("close_chkall", 1);
    	$this->setTitle(array("内部代码", "姓名", "发卡行", "卡号", "服务期限", "联系电话", "有效日期", "剩余天数"));
    	$this->setField(array("code", "name", "bank", "card", "due_date", "phone1", "effective_date", "days"));
    	$this->display();
    }
    public function overdue_card()
    {
    	if (IS_POST)
    	{
    		$return = array("echo" => 1, "close" => 1, "info" => "更新成功!",
    				"url" => U("CardList/expiring_list"), "tag" => "#body", "callback" => "");
    		$obj = M("card");
    
    		save_operating_record($_POST["code"], CARD_RENEWAL, "更新卡片");
    		
	        $_POST["effective_date"] = $_POST["cdate_year"]."-".$_POST["cdate_month"]."-1";
	        $obj->create($_POST, 2);
    		$obj->where("code='".$_POST["code"]."'")->save();
    
    		$this->ajaxReturn($return);
    
    		exit(0);
    	}
    	$form = new Form();
    	$form->set("cols", 2);
    	$card = sqlRow(" select
    						b.id as id,
    						b.name as name,
    						c.code as code,
    						c.card as card,
    						c.cvv2 as cvv2,
    						c.amount + c.rising_amount_num as amount,
    						c.due_date as due_date,
    						c.effective_date as effective_date
    
						from
    						card c, basis b
						where
    						c.bid = b.id and c.status = 0 and c.id =".I("get.id"));
    	//     	dump(I("get.id"));
    
    	$form->setElement("name", "static", "姓名", array("value" => $card["name"]));
    	$form->setElement("amount", "static", "总额度", array("value" => $card["amount"]." 元"));
    	$form->setElement("card", "static", "卡号", array("value" => $card["card"]));
    	$form->setElement("due_date", "static", "到期日期", array("value" => $card["due_date"]));
    	$form->setElement("fenzu", "group", "签约信息", array("class"=> "text-center","value" => $card["fenzu"]));
    	 
    	$form->setElement("cvv2_info", "static", "CVV2码", array("value" => $card["cvv2"]));
    	$form->setElement("cvv2", "num","新CVV2码", array("bool" => "required", "hint" => "num",
    			"min" => 3, "maxlength" => 7));
    	$form->setElement("effective_date", "static", "卡有效期", array("value" => $card["effective_date"]));
    	$form->setElement("cdate_year", "select", "新卡有效期", array("bool" => "required", "element_cols" => 2,
    			"group" => "start", "title" => "卡有效期年",
    			"list" => parse_select_list("for", array(2014, 2020, 1, 1), "年"),
    	));
    	
    	$form->setElement("code", "hidden", "", array("value" => $card["code"]));
    	$form->set("btn 0 txt", "更新");
    	echo $form->fetch();
    }
    
    
    
    
//-----------------------------到期列表-code-----------------------------
    public function expire_list()
    {
    	$this->setNav("&nbsp;->&nbsp;到期卡片");
    	$this->mainPage("card");
    	$this->setFind("typelist name", array("txt" => "客户姓名", "val" => "name"));//增加搜索选择
    	$this->setFind("typelist card", array("txt" => "客户卡号", "val" => "card"));
    	$this->setCustomList("pro_card_duemain",true,array(3,get_user_info("sid"),
    			"'".$_POST["search_type"]."'", "'".$_POST["search_key"]."'"));
    	$this->setOp("续约", U("CardList/renewal_card")."&id=[id]",array("pop" => $this->getPop("add")));;
    	$this->setOp("解约", U("CardList/card_off")."&id=[id]",array("pop" => $this->getPop("off")));;
    	//$this->setData("close_op", 1);
    	$this->setData("close_chkall", 1);
    	$this->setTitle(array("内部代码", "姓名", "发卡行", "卡号", "初始额度", "现总额度", "增值次数", "到期日期", "剩余天数", "联系电话"));
    	$this->setField(array("code", "name", "bank", "card", "amount", "amount_num", "rising_num", "due_date", "days", "phone1"));
    	$this->display();
    }
    
    


    public function card_off()
    {
    	if (IS_POST)
    	{
    		$return = array("echo" => 1, "close" => 1, "info" => "操作成功fdsfadsfas!",
    				"url" => U("CardList/expire_list"), "tag" => "#body", "callback" => "");
    		$obj = M("card");
    
    		save_operating_record($_POST["code"], CARD_RENEWAL, "卡片解约");
    
    		$obj->create($_POST, 1);
    		$obj->where("code='".$_POST["code"]."'")->save();
    
    		$this->ajaxReturn($return);
    
    		exit(0);
    	}
    	$form = new Form();
    	$form->set("cols", 2);
    	$card = sqlRow("select 
    						c.id as id,
    						b.name as name,
    						c.code as code,
    						c.card as card,
    						c.amount + c.rising_amount_num as amount,
    						c.agreement as agreement,
    						c.due_date as due_date,
    						c.times as times,
    						c.rising_num as rising_num,
    						ceiling(datediff(curdate(),c.times)/30) as serve_period
						from
    						card c, basis b
						where
    						c.bid = b.id and c.status = 0 and c.id =".I("get.id"));
    	//     	dump(I("get.id"));
    
    	$form->setElement("name", "static", "姓名", array("value" => $card["name"]));
    	$form->setElement("amount", "static", "总额度", array("value" => $card["amount"]." 元"));
    	$form->setElement("card", "static", "卡号", array("value" => $card["card"]));
    	$form->setElement("rising_num", "static", "提额次数", array("value" => $card["rising_num"]." 次"));
    	$form->setElement("times", "static", "签约日期", array("value" => $card["times"]));
    	$form->setElement("agreement_info", "static", "服务期限", array("value" => $card["agreement"]." 月"));
    	$form->setElement("due_date", "static", "到期日期", array("value" => $card["due_date"]));
    	$form->setElement("serve_period", "static", "已服务期数", array("value" => $card["serve_period"]." 期"));
    	$form->setElement("remark", "textarea", "解约原因", array("rows" => 3,"element_cols" => "col-md-9"));
    	
    	$form->setElement("code", "hidden", "", array("value" => $card["code"]));
    	$form->setElement("termination_status", "hidden", "", array("value" => 1));
    	$form->set("btn 0 txt", "解约");
    	echo $form->fetch();
    }
   
    
    
    
    
    
}