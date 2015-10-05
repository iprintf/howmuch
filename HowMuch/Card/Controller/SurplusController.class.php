<?php

namespace Card\Controller;

use Common\Controller\ListPage;
use Common\Controller\FormElement;
use Common\Controller\Form;

class SurplusController extends ListPage
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
    
    // 获取客户相关弹窗口信息
    static public function getPop($type, $title = "")
    {
        $pop = "";
        switch ($type)
        {
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
    
    //残值临查提交申请接口函数
    public function request($code)
    {
        if (!sqlCol("select id from card where code='".$code."' and status=0 and temp_status=0"))
            $this->ajaxReturn(array("echo" => 1, "info" => "对不起，此卡已经在其它操作流程中，无法提交残值临查!"));
        M("card")->where("code='".$code."'")->save(array("status" => SEARCH, "temp_status" => NORMAL, 
                                    "update_time" => getcurtime()));
		save_operating_record($code, CARD_SURPLUS);
        $this->ajaxReturn(array("echo" => 1, "info" => "残值临查提交成功，请在残值临查列表中等待结果!",
                "close" => 1, "url" => session("prev_urlcard"), "tag" => ".card"));   
    }
 
    //操作员验证金额窗口
    public function submitValWin()
    {
        if (IS_POST)
        {
            $cdata = sqlRow("select code,eid from card where card='".$_POST["card"]."'");
            $_POST["update_time"] = getcurtime();
            $_POST["remark"] = "卡内现有残值:".$_POST["old_money"]." 元, 残值查询结果:".$_POST["money"]." 元";
            
            if ($_POST["stype"] == 1)   //增值残值查询
                $oper_type = R("Rising/surplus", array($cdata));
            else
            {
                //残值临查
                $oper_type = CARD_SURPLUS_END;
                //将卡片状态改变为正常状态
                M("card")->where("code='".$cdata["code"]."'")->setField("temp_status", SEARCH);
            }
    		save_operating_record($cdata["code"], $oper_type);
            
        	$surplus_num = sqlCol("call pro_card_duemain(0, 99, 7, null, ".get_user_info("uid").")");
            $_SESSION["notice"]["surplus"] = $surplus_num;
        		
    		$this->ajaxReturn(array("echo" => 1, "close" => 1, "info" => "操作成功!", 
                     "url" => session("prev_urlsurplus_query"), "tag" => ".surplus_query", 
    		        "callback" => '$(".top_surplus_num").html("'.$surplus_num.'");'));
        }
        
        $v = new Form("", array("name" => "verifyform"));
        
        $v->setElement("old_money", "string", "卡内应有残值", array("bool" => "readonly", "addon" => "元",
                 "value" => I("get.money")));
        $v->setElement("money", "string", "实际卡内残值", array("bool" => "required", "addon" => "元"));
        $v->setElement("id", "hidden", "", array("value" => I("get.id")));
        $v->setElement("card", "hidden", "", array("value" => I("get.card")));
        $v->setElement("stype", "hidden", "", array("value" => I("get.stype")));
        $v->set("btn 0 txt", "提交残值");
        
        echo $v->fetch();
    }
    
    //操作员残值查询列表
    public function query()
    {
    	$this->setNav("&nbsp;->&nbsp;残值查询");
    	$this->mainPage("surplus_query");
        $this->set("close_tool", 1);
        $this->set("close_top_ctrl", 1);
        
    	$this->setCustomList("pro_card_duemain",true,array(7, "null", $this->uid));
//         dump(M()->getLastSql());
        
        $this->setOp("提交残值", U("Surplus/submitValWin")."&money=[money]&id=[id]&card=[card]&stype=[stype]", 
                array("pop" => $this->getPop("verify")));
        
    	$this->setData("close_chkall", 1);
    	$this->setTitle(array("客户姓名", "发卡行", "卡号", "现有残值", "提交时间"));
    	$this->setField(array("bname", "bank", "card", "money", "update_time"));
    	$this->display();    
    }
    
    //财务确定操作员残值查询结果
    public function verify($cid)
    {
        M("card")->where("id=".$cid)->save(array("status" => NORMAL, 
                            "temp_status" => NORMAL, "update_time" => getcurtime()));
        $this->ajaxReturn(array("echo" => 1, "info" => "操作成功!", 
                "url" => session("prev_urlsurplus"), "tag" => ".surplus"));   
    }
    
    public function disOp($data, $op)
    {
        if ($data["temp_status"] != SEARCH)
            return "等待查询";
        $lnk = new FormElement("verify", "link", "残值确认", array("query" => true,
    		"ext" => 'confirm="残值确认后记录会消失，可以在此卡操作记录中查看，是否继续?" 
            url="'.U("Surplus/verify").'&cid='.$data["cid"].'"')); 
        return $lnk->fetch();
    }
    
    //处理残值查询结果字段显示
    public function getListField($data, $txt)
    {
        $remark = explode(",", $data["remark"]);
        $data["ret"] = explode(":", $remark[1]);
        $data["ret"] = $data["ret"][1];
        $remark = explode(":", $remark[0]);
        return $remark[1];
    }
    
    //残值临查列表
    public function index()
    {
        $this->setNav("&nbsp;->&nbsp;卡片操作&nbsp;->&nbsp;残值临查");
        $this->mainPage("surplus");
        $this->set("close_tool", 1);
        $this->set("close_top_ctrl", 1);
        
        $this->setCustomList("pro_card_duemain ", true, array(9, $this->sid, "null"));
//         $this->setData("where", "sid=".$this->sid." and status=".SEARCH);
        $this->setData("op_call", array("run", "Surplus/disOp"));
        $this->setData("close_chkall", 1);
        $this->setTitle(array("客户姓名","客户电话","发卡行","卡号", "现有残值", "临查提交时间", "查询结果", "残值查验时间"));
        $this->setField(array("bname","bphones","bank","card", "remark", "update_time", "ret", "oper_time"));
        $this->setData("data_field 4 run", "Surplus/getListField");
        $this->setField("card", array("name" => 3, "url" => U("Card/index")."&form=info&list=1&bid=[bid]&where='id=[cid]'",
                "pop" => CardController::getPop("info")));
        
        $this->display();
    }
}