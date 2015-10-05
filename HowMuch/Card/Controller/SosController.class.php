<?php

namespace Card\Controller;

use Common\Controller\ListPage;
use Common\Controller\FormElement;
use Common\Controller\Form;

class SosController extends ListPage
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
            case "lock":
                if (!$title)
                    $title = "SOS紧急锁卡 ";
                $pop = "w:480,h:360,c:1,n:'lockcardwin',t:".$title;
                break;
            case "unlock":
                if (!$title)
                    $title = "解锁备注";
                $pop = "w:480,h:360,n:'unlockwin',t:".$title;
                break;
            case "apply":
                if (!$title)
                    $title = "解锁申请备注";
                $pop = "w:480,h:360,n:'unlockwin',t:".$title;
                break;
            case "handle":
                if (!$title)
                    $title = "解锁申请处理";
                $pop = "w:480,h:400,n:'unlockwin',t:".$title;
                break;                
            default:
                break;
        }
        return $pop;
    }
    
    public function lock($url = "", $tag = "#body", $callback = "")
    {
        if (IS_POST)
        {
            $return = array("echo" => 1, "close" => 1, "info" => "操作成功!", 
                    "url" => $url, "tag" => $tag, 
                    "callback" => $callback);
            
            $obj = M("card");
            $obj->where("code='".$_POST["code"]."'")->save(array("status" => LOCK, 
                    "temp_status" => NORMAL, "update_time" => getcurtime()));
            
            $ret = save_operating_record($_POST["code"], CARD_SOS_LOCK);
            if (!$ret)
                $this->ajaxReturn(array("echo" => 1, "info" => "操作记录保存失败!"));
            if ($this->admin == 2)
            {
                $msg = "操作员 ".get_username($this->uid);
                $msg .= " 对 ".$_POST["code"]." 这张卡进行了SOS紧急锁卡, ";
                $msg .= " 请 ".parse_msg_link(U("Card/Sos/index")."&ibody=1")." 对此卡进行解锁处理!";
                auto_send_msg("SOS操作紧急锁卡", $msg);
            }
            
            $this->ajaxReturn($return);
        }
        
        $code = I("get.code");
        
        //判断此卡是否有做其它操作, 没有操作才能锁卡
        $ret = sqlCol("select id from card where code='".$code."' and status=0 and temp_status=0");
        if (!$ret)
        {
            echo '<h3 class="kyo_red">'.$code.' 此卡已在其它操作流程中，不能进行锁卡!</h3>';
            exit(0); 
        }
        
        $sos = new Form("", array("name" => "lockform", "kajax" => "true"));
        
        $sos->setElement("audittype", "autocomplete", "锁卡原因", array(
                "placeholder" => "自己写锁卡原因!",
                "list" => parse_autocomplete("select txt from sel_remark where type_id=".CARD_SOS_LOCK)));
        $sos->setElement("remark", "textarea", "", array("close_label" => 1, "element_cols" => 12, 
                "rows" => 6, "bool" => "required"));
        $sos->setElement("code", "hidden", "", array("value" => $code));
        $sos->set("btn 0 txt", "确定");
        echo $sos->fetch();
        exit(0);        
    }
    
    public function unlock()
    {
        if (IS_POST)
        {
            $return = array("echo" => 1, "close" => 1, "info" => "操作成功!", 
                    "url" => session("prev_urlsos"), "tag" => ".sos", "callback" => "");
            
            $obj = M("card");
            if ($_POST["op_type"] == CARD_SOS_UNLOCK)
                $obj->where("code='".$_POST["code"]."'")->save(array("status" => 0, "temp_status" => 0, "update_time" => getcurtime()));
            else   //解锁申请....
                $obj->where("code='".$_POST["code"]."'")->save(array("temp_status" => UNLOCK, "update_time" => getcurtime()));
            
            $ret = save_operating_record($_POST["code"], $_POST["op_type"]);
            if (!$ret)
                $this->ajaxReturn(array("echo" => 1, "info" => "操作记录保存失败!"));
            
            $this->ajaxReturn($return);
        }
        
        $apply = I("get.apply");
        $code = I("get.code");
        $op_type = CARD_SOS_UNLOCK;
        $btn_txt = "确定解锁";
        
        if ($apply == 1)
        {
            $btn_txt = "提交申请";
            $op_type = CARD_SOS_APPLY;
        }
            
//         dump($code);
        $aut = new Form("", array("name" => "unlockform"));
        
        $un = sqlRow("select remark,oper_time from operating_record where code='".$code."' and type=".CARD_SOS_APPLY." order by oper_time desc");
        
        $aut->setElement("remark", "textarea", "", array("close_label" => 1, "element_cols" => 12, 
                "rows" => 8, "bool" => "required", "value" => $un["remark"]));
        $aut->setElement("code", "hidden", "", array("value" => $code));
        $aut->setElement("op_type", "hidden", "", array("value" => $op_type));
        $aut->set("btn 0 txt", $btn_txt);
        if ($apply == 2)
        {
            $aut->setElement("apply_txt", "static", "申请提交时间", array( "value" => $un["oper_time"]));
            if ($this->admin == 3 || $this->admin == 4)
                $aut->set("close_btn_down", 1);
        }
        echo $aut->fetch();
    }
    
    //锁卡操作
    public function disUnlock($data, $op)
    {
        $txt = "解锁";
        $apply = 0;
        $pop = "unlock";
        
//         $data["tmp_status"] = 1;
//         $this->admin = 1;
        
        if ($this->admin == 3 || $this->admin == 4)
        {
            $txt = "解锁申请";
            $pop = "apply";
            $apply = 1;
            if ($data["temp_status"] == UNLOCK)
            {
                $txt = "解锁中";
                $apply = 2;
            }
        }
        else if ($data["temp_status"] == UNLOCK)
        {
            $apply = 2;
            $pop = "handle";
            $txt = "解锁中";
        }
        
        $lnk = new FormElement("unlock", "link", $txt, array(
                "url" => U("Sos/unlock")."&code=".$data["code"]."&apply=".$apply, 
                "pop" => $this->getPop($pop)));
        
        return $lnk->fetch();
    }

    public function index()
    {
        $this->setNav("&nbsp;->&nbsp;卡片操作&nbsp;->&nbsp;锁卡列表");
        $this->mainPage("sos");
        $this->set("close_tool", 1);
        $this->set("close_top_ctrl", 1);
        
        if ($this->admin == 3)
        	$this->setCustomList("pro_card_duemain", true, array(4, "null", $this->uid));
        else
        	$this->setCustomList("pro_card_duemain", true, array(4, $this->sid, "null"));
//     	$this->setCustomList("kyo_test", true);
        $this->setData("op_call", array("run", "Sos/disUnlock"));
        $this->setData("close_chkall", 1);
        $this->setData("tooltip", 1);
        $this->setTitle(array("客户姓名","客户电话","发卡行","卡号","锁卡原因","锁卡时间", "拓展员", "拓展员电话"));
        $this->setField(array("name","phone1","bank","card","remark","oper_tiime", "eid", "phones"));
        $this->setField("card", array("name" => 3, "url" => U("Card/index")."&form=info&list=1&bid=[bid]&where='id=[cid]'",
                "pop" => CardController::getPop("info")));
        if ($this->admin == 3)
        {
            $this->setData("data_field 2 pop", CardController::getPop("salesinfo"));
            $this->setDataHide(array(6, 7));
        }
        $this->display();
    }
}