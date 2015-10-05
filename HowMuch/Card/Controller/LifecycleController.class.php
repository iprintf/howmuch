<?php
namespace Card\Controller;
use Common\Controller\ListPage;
use Common\Controller\FormElement;
use Common\Controller\Form;

class LifecycleController extends ListPage
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
           			$title = "更换新片";
           		$pop = "w:800,h:380,c:1,n:'overdue_card',t:".$title;
           		break;
        	default:
        	    break;
        }
        return $pop;
    }
 
    //更换新卡操作
    public function changeCard($list = "expiring")
    {
    	if (IS_POST)
    	{
    		$return = array("echo" => 1, "close" => 1, "info" => "更新成功!",
    				"url" => session("prev_url".$list), "tag" => ".".$list, "callback" => "");
    		$obj = M("card");
            
    		$remark = "&emsp;CVN2由 ".$_POST["old_cvv2"]." 更改为  ".$_POST["cvv2"]."<br />";
    		$remark .= "&emsp;卡片有效期由 ".$_POST["old_month"]." / ".$_POST["old_year"]." 更改为  ";
    		$remark .= " ".$_POST["cdate_month"]." / ".$_POST["cdate_year"];
    		
	        $_POST["effective_date"] = $_POST["cdate_year"]."-".$_POST["cdate_month"]."-1";
            $_POST["update_time"] = getcurtime();
	        $obj->create($_POST, 2);
    		$obj->where("code='".$_POST["code"]."'")->save();
    
    		save_operating_record($_POST["code"], CARD_EDIT, $remark);
            
    		$this->ajaxReturn($return);
    
    		exit(0);
    	}
    	$form = new Form();
    	$form->set("cols", 2);
    	$card = sqlRow("select b.name, c.code, c.card, c.cvv2, c.effective_date from card c, basis b
						where c.bid = b.id and c.id =".I("get.id"));
//     	    	dump(I("get.id"));
    
    	$form->setElement("name", "static", "姓名", array("value" => $card["name"]));
    	$form->setElement("cvv2_info", "static", "原CVN2码", array("value" => format_dis_field($card["cvv2"], array(4, 3))));
    	$form->setElement("card", "static", "卡号", array("value" => format_dis_field($card["card"])));
        $cdate = explode("-", $card["effective_date"]);
    	$form->setElement("effective_date", "static", "原卡有效期", array("value" => $cdate[1]." / ".$cdate[0]));
    	$form->setElement("fenzu", "group", "新卡信息", array("class"=> "text-center","value" => $card["fenzu"]));
    	 
    	$form->setElement("cdate_month", "select", "新卡有效期", array("bool" => "required", "element_cols" => 2,
    			"group" => "start", "title" => "卡有效期月",
    			"list" => parse_select_list("for", array(1, 12, 1, 1), "月"),
    	));
    	$form->setElement("cdate_year", "select", "", array("bool" => "required", "element_cols" => 2,
    			"group" => "end", "title" => "卡有效期年",
    			"list" => parse_select_list("for", array(2014, 2020, 1, 1), "年"),
    	));
    	$form->setElement("cvv2", "num","新卡CVN2码", array("bool" => "required", "hint" => "num",
    			"label_cols" => 2, "min" => 3, "maxlength" => 7));
        
    	$form->setElement("old_cvv2", "hidden", "", array("value" => $card["cvv2"]));
    	$form->setElement("old_year", "hidden", "", array("value" => $cdate[0]));
    	$form->setElement("old_month", "hidden", "", array("value" => $cdate[1]));
    	$form->setElement("code", "hidden", "", array("value" => $card["code"]));
    	$form->set("btn 0 txt", "更换");
    	echo $form->fetch();
    }    
    
 //-----------------------------卡片有效期，即将失效列表-code-----------------------------   
    public function expiring()
    {
        $this->setNav("&nbsp;->&nbsp;卡片操作&nbsp;->&nbsp;卡片有效期，即将失效卡片");
        $this->mainPage("expiring");
        $this->set("close_tool", 1);
        $this->set("close_top_ctrl", 1);
        
        if ($this->admin == 3)
        	$this->setCustomList("pro_card_duemain", true, array(2, "null", $this->uid));
        else
        	$this->setCustomList("pro_card_duemain", true, array(2, $this->sid, "null"));
        
        $this->setData("close_chkall", 1);
    	$this->setOp("更换新卡", U("Lifecycle/changeCard")."&id=[cid]",array("pop" => $this->getPop("overdue")));;
    	$this->setData("close_chkall", 1);
    	$this->setTitle(array("客户姓名", "客户电话", "发卡行", "卡号", "卡失效年月", "服务结止期", "拓展员", "拓展员电话"));
    	$this->setField(array("name", "phone1", "bank", "card", "effective_date", "due_date",  "username", "phones"));
        $this->setField("card", array("name" => 3, "url" => U("Card/index")."&form=info&list=1&bid=[bid]&where='id=[cid]'",
                "pop" => CardController::getPop("info")));
        if ($this->admin == 3)
        {
            $this->setData("data_field 3 pop", CardController::getPop("salesinfo"));
            $this->setDataHide(array(6, 7));
            $this->setData("close_op", 1);
        }
        $this->display();        
    }
    
    
//-----------------------------即期列表-code-----------------------------
    public function index()
    {
    	$this->setNav("&nbsp;->&nbsp;卡片操作&nbsp;->&nbsp;即期卡片");
    	$this->mainPage("dueing");
        $this->set("close_tool", 1);
        $this->set("close_top_ctrl", 1);
        
        if ($this->admin == 3)
        	$this->setCustomList("pro_card_duemain", true, array(1, "null", $this->uid));
        else
        	$this->setCustomList("pro_card_duemain", true, array(1, $this->sid, "null"));
//     	$this->setCustomList("kyo_test", true);
//     	dump(M()->getLastSql()); //调试
        $this->setData("op_call", array("run", "Renewal/disOp"));
    	$this->setData("close_chkall", 1);
    	$this->setTitle(array("客户姓名", "客户电话", "发卡行", "卡号", "初始额度", "现总额度", "增值次数", "即期日期", "剩余天数", "拓展员", "拓展员电话"));
    	$this->setField(array("name", "phone1", "bank", "card", "amount", "amount_num", "rising_num", "due_date","days", "username", "phones"));
        $this->setField("card", array("name" => 3, "url" => U("Card/index")."&form=info&list=1&bid=[bid]&where='id=[cid]'",
                "pop" => CardController::getPop("info")));
        if ($this->admin == 3)
        {
            $this->setData("data_field 3 pop", CardController::getPop("salesinfo"));
            $this->setDataHide(array(9, 10));
        }
        
    	$this->display();
    }
 
//-----------------------------到期列表-code-----------------------------
    public function expireOp($data, $op)
    {
        if ($data["temp_status"] != RESCIND)
            $html = R("Renewal/disOp", array($data, $op));
        if ($this->admin == 6)
            $html .= "&emsp;".R("Rescind/disOp", array($data));
        return $html;          
    }
    
    public function expireField($data, $txt)
    {
        return '<span class="kyo_red">'.ltrim($data["days"], "-").'</span>';
    }
    
    public function expire()
    {
    	$this->setNav("&nbsp;->&nbsp;卡片操作&nbsp;->&nbsp;到期卡片");
    	$this->mainPage("expire");
        $this->set("close_tool", 1);
        $this->set("close_top_ctrl", 1);
        
        if ($this->admin == 3)
        	$this->setCustomList("pro_card_duemain", true, array(3, "null", $this->uid));
        else
        	$this->setCustomList("pro_card_duemain", true, array(3, $this->sid, "null"));
//     	$this->setCustomList("kyo_test", true);
        $this->setData("op_call", array("run", "Lifecycle/expireOp"));
        
    	$this->setData("close_chkall", 1);
    	$this->setTitle(array("客户姓名", "客户电话", "发卡行", "卡号", "初始额度", "现总额度", "增值次数", "到期日期", "过期天数", "拓展员", "拓展员电话"));
    	$this->setField(array("name", "phone1", "bank", "card", "amount", "amount_num", "rising_num", "due_date", "days", "username", "phones"));
        $this->setField("card", array("name" => 3, "url" => U("Card/index")."&form=info&list=1&bid=[bid]&where='id=[cid]'",
                "pop" => CardController::getPop("info")));
        $this->setData("data_field 8 run", "Lifecycle/expireField");
        if ($this->admin == 3)
        {
            $this->setData("data_field 3 pop", CardController::getPop("salesinfo"));
            $this->setDataHide(array(9, 10));
        }
    	$this->display();
    }
}