<?php
namespace Card\Controller;
use Common\Controller\ListPage;
use Common\Controller\FormElement;
use Common\Controller\Form;

class RescindController extends ListPage
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
        	case "rescind":
                if (!$title)
                    $title = "解约";
                $pop = "w:480,h:360,c:1,n:'rescindwin',t:".$title;
        	    break;
        	case "statement":
            	if (!$title)
        	   		$title = "结账单";
                $pop = "w:1000,h:650,c:1,n:'bankadd',t:".$title;
        	   	break;
        	default:
        	    break;
        }
        return $pop;
    }
    
    public function disOp($data)
    {
        if ($this->admin == 6)
        {
            if ($data["temp_status"] == RESCIND)
                $lnk = new FormElement("apply_info", "link", "等待解约", array(
                        "url" => U("Rescind/rescind")."&code=".$data["code"]."&info=1", 
                        "pop" => $this->getPop("rescind", "解约原因")));      
            else
                $lnk = new FormElement("apply_info", "link", "确定解约", array(
                        "url" => U("Rescind/rescind")."&code=".$data["code"], 
                        "pop" => $this->getPop("rescind", "确定解约原因")));      
            
            return $lnk->fetch();
        }
    }
    
    //站长提交解约接口
    public function rescind()
    {
        if (IS_POST)
        {
            M("card")->where("code='".$_POST["code"]."'")->save(array(
                    "temp_status" => RESCIND, "update_time" => getcurtime()));
            
            $ret = save_operating_record($_POST["code"], CARD_RESCIND);
            if (!$ret)
                $this->ajaxReturn(array("echo" => 1, "info" => "操作记录保存失败!"));
            
            $this->ajaxReturn(array("echo" => 1, "close" => 1, "info" => "操作成功!", 
                        "url" => session("prev_urlexpire"), "tag" => ".expire"));
        }
        $aut = new Form("", array("name" => "rescindform"));
        $code = I("get.code");
        $info = I("get.info");
        
        $aut->setElement("remark", "textarea", "", array("close_label" => 1, "element_cols" => 12, 
                "rows" => 8, "bool" => "required"));
        $aut->setElement("code", "hidden", "", array("value" => I("get.code")));
        
        $aut->set("btn 0 txt", "确认解约");    
        
        if ($info)
        {
            $un = sqlRow("select remark,oper_time from operating_record where code='".$code."' and 
                            type=".CARD_RESCIND." order by oper_time desc");
            $aut->set("element remark bool", "readonly");
            $aut->set("element remark value", $un["remark"]);
            $aut->setElement("rescind_txt", "static", "解约提交时间", array( "value" => $un["oper_time"]));
            $aut->set("close_btn_down", 1);        
        }
        
        echo $aut->fetch();    
    }
    
    //解约列表
    public function index()
    {
    	$this->setNav("&nbsp;->&nbsp;卡片操作&nbsp;->&nbsp;解约卡片");
    	$this->mainPage("rescind");
        $this->set("close_tool", 1);
        $this->set("close_top_ctrl", 1);
        
        if ($this->admin == 3)
        	$this->setCustomList("pro_card_duemain", true, array(5, "null", $this->uid));
        else
        	$this->setCustomList("pro_card_duemain", true, array(5, $this->sid, "null"));
        
        $this->setOp("结账单", U("Rescind/statement")."&card=[card]", array("pop" => $this->getPop("statement")));
        
    	$this->setData("close_chkall", 1);
    	$this->setData("tooltip", 1);
    	$this->setTitle(array("客户姓名", "客户电话", "发卡行", "卡号", "到期日期",  "解约原因", "解约提交时间", "拓展员", "拓展员电话"));
    	$this->setField(array("bname", "phone1", "bank", "card", "due_date", "remark", "oper_time", "username", "phones"));
        $this->setField("card", array("name" => 3, "url" => U("Card/index")."&form=info&list=1&bid=[bid]&where='id=[cid]'",
                "pop" => CardController::getPop("info")));
        if ($this->admin == 3)
        {
            $this->setData("data_field 3 pop", CardController::getPop("salesinfo"));
            $this->setDataHide(array(7, 8));
        }
    	$this->display();    
    }
    
    //结账单
    function statement($card)
    {
//         dump($card);
        $sf = new Form("", array("kajax" => "false", "cols" => 2, "type" => "info"));
        $mes = sqlRow("CALL settlement(2,'".$card."')");
        
        $sf->setElement("ename", "static", "所属拓展员", array("value" => $mes["username"]));
        $sf->setElement("times", "static", "到期日期", array("value" => $mes["due_date"]));
        $sf->setElement("name", "static", "客户姓名", array("value" => $mes["bname"]));
        $sf->setElement("agreement", "static", "服务期数", array("value" => $mes["agreement"]." 个月"));
        $sf->setElement("card", "static", "银行卡号", array("value" => format_dis_field($mes["card"])));
        $sf->setElement("cost", "static", "服务费率", array("value" => ($mes["cost"] * 100)." %"));
        $sf->setElement("amount", "static", "授信额度", array("value" => $mes["amount"]." 元"));
        $sf->setElement("rising_cost", "static", "增值费率", array("value" => ($mes["rising_cost"] * 100)." %"));
        
        $html =  '<table class="table kyo_table_list text-center"><thead>';
        $html .=  '<tr class="list_title">';
        $html .=  '<th>结算日期</th>';
        $html .=  '<th>摘要</th>';
        $html .=  '<th>服务额度</th>';
        $html .=  '<th>客户预付费</th>';
        $html .=  '<th>应收服务费</th>';
        $html .=  '</tr></thead><tbody>';
        $data = sqlAll("CALL settlement(1,'".$card."')");
        foreach ($data as $row)
        {
            $html .=  '<tr>';
            foreach ($row as $col)
            {
                $html .= '<td>'.$col.'</td>'; 
            }
            $html .=  '</tr>';
        }
        $html .=  '</tbody></table>';
        
        $sf->setElement("statement", "custom", "", array("custom_html" => $html, "close_label" => 1, "element_cols" => 12));
        $data = sqlAll("CALL settlement(1,'".$card."')");
        $sf->setElement("sgroup", "group", "小计");
        $html =  '<table class="table kyo_table_list text-center"><thead>';
        $html .=  '<tr class="list_title">';
        $html .=  '<th>已服务期数</th>';
        $html .=  '<th>应收服务费</th>';
        $html .=  '<th>客户预付费</th>';
        $html .=  '<th>费用结余</th>';
        $html .=  '</tr></thead><tbody>';
        $data = sqlRow("CALL settlement(3,'".$card."')");
        $html .=  '<tr>';
        foreach ($data as $col)
        {
            $html .= '<td>'.$col.'</td>'; 
        }
        $html .=  '</tr>';
        $html .=  '</tbody></table>';
        $sf->setElement("ssum", "custom", "", array("custom_html" => $html, "close_label" => 1, "element_cols" => 12));
        
        if ($this->admin == 1 && !$mes["state"])
        {
    		$sf->set("btn 0", array("txt" => "打印", "end" => "&nbsp;&nbsp;",
                    "url" => U("Rescind/statement")."&print=1&card=".$card, "ext" => 'type="button"'));
            
            if (I("get.print"))
            {
                header("Content-type: text/html; charset=utf-8");
                $html = "<!DOCTYPE html><html><head>";
                $html .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
                $html .= '<link type="text/css" rel="stylesheet" href="http://s.cc:918/Public/css/bootstrap.min.css" />';
                $html .= '<link type="text/css" rel="stylesheet" href="http://s.cc:918/Public/css/kyo.css" />';
                $html .= '<title>_</title></head><body onload="window.print()">';
                $html .= '<body onload="window.print()">';
                $html .= '<h1 class="text-center">结账单</h1>';
                if ($mes["status"] != RESCIND)
                    $this->rescindCode($card);
                $sf->set("close_btn_down", 1);
                $html .= "<div class='col-md-12'>".$sf->fetch()."</div>";
                $html .= '</body></html>';
                
                $excel = new \Common\Controller\KyoMail();
                $this->ajaxReturn(array("echo" => 1, 
                        "info" => $excel->printf("解约结账单", $html, RUNTIME_PATH."解约结账单.html")));
                exit(0);
            }
        }
        else   //如果不是财务则没有按钮显示
            $sf->set("close_btn_down", 1);
        echo $sf->fetch();
    }
    
    public function rescindCode($card)
    {
        M("card")->where("card='".$card."'")->save(
            array("status" => RESCIND, "update_time" => getcurtime()));
        $c = sqlRow("select code,bid,eid,opid from card where card='".$card."'");
        M("basis")->where("id=".$c["bid"])->setDec("card_num");
        M("users")->where("id=".$c["eid"]." or id=".$c["opid"])->setDec("card_num");
        save_operating_record($c["code"], CARD_RESCIND_PRINT, "信用卡解约成功并且已打印结账单!");
    }
}