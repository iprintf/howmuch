<?php
namespace KyoCommon\Controller;
use Think\Controller;
use Common\Controller\ListPage;
use Common\Controller\FormElement;
use Common\Controller\Form;

class MessageController extends ListPage
{
    private $admin;
    private $proxy;
    private $sid;
    private $uid;
    private $recv_el = array();
    private $mtype;
    
    //构造函数
    public function __construct()
    {
        parent::__construct();
        $this->admin = get_user_info("admin");
        $this->sid = get_user_info("sid");
        $this->uid = get_user_info("uid");
        $this->proxy = get_user_info("proxy_id");
        $mess_type = array(1 => array("系统消息", "站内公告", "财务通知", "操作问题", "其它"),
                           2 => array("系统消息", "操作问题", "其它"),
                           6 => array("系统消息", "站内公告", "操作问题", "意见反馈", "其它"),
                           3 => array("系统消息", "客户意见", "其它"),
                           7 => array("系统消息", "系统公告", "客户意见", "其它"),
                           9 => array("系统消息", "系统公告", "其它"));
        $this->mtype = $mess_type[$this->admin];
    }
    
    //站内信弹出窗口选项
    static public function getPop($type, $title = "")
    {
        $pop = "";
        switch ($type)
        {
        	case "add":
                if (!$title)
                    $title = "写信";
                $pop = "w:1100,h:600,c:1,n:'messageadd',t:".$title;
        	    break;
        	case "info":
                if ($title)
                {
                    $pop = "w:1000,h:620,n:'messageinfo',b:partial_refresh('".session("prev_urlmessage")."',";
                    $pop .= " '.message');,t:查看站内信";
                }    
                else
                    $pop = "w:1000,h:620,n:'messageinfo',t:查看站内信";
        	    break;
        	default:
        	    break;
        }
        return $pop;
    }
    
    //登录时生成用户收件箱信息并且统计用户未读信息数
    static public function getMessage($usr = "")
    {
        if ($usr == "")
            $usr = sqlRow("select id,sid,proxy_id,type from users where id=".get_user_info("uid"));
        //接收者代理是登录用户所属代表或所有代理，接收者分站是登录用户所属分站或所有分站，接收者权限组是登录用户权限组
        $sql = "select id from message_text where (proxy=0 or proxy=".$usr["proxy_id"].") and 
                  (sid=0 or sid=".$usr["sid"].") and (grp='0' or grp like '%,".$usr["type"].",%') and
                  (recv_type=0 or recv_type=2) and sender!=".$usr["id"]." and
                  id not in (select mess_id from message where recv_id=".$usr["id"].")";
        $mess = sqlAll($sql);
        
        $mObj = M("message");
        $mess_num = sqlCol("select count(id) from message where recv_id=".$usr["id"]." and status=1");
//         dump(M()->getLastSql());
        foreach ($mess as $row)
        {
            $mObj->add(array("recv_id" => $usr["id"], "mess_id" => $row["id"]));
            $mess_num++;
        }
//         session("new_mess_num", $mess_num);
        return $mess_num;
    }
    
    //根据当前登录权限来分发获取发件人信息
    public function getSenderName($id)
    {
        $usr = sqlRow("select type,username from users where id=".$id);
        if (($this->admin >= 1 && $this->admin <= 6 && $usr["type"] > 6) || 
            ($this->admin == 8 && ($usr["type"] == 7 || $usr["type"] == 9))) 
            return "系统管理员";
        
        return get_perm_name($usr["type"])." ".$usr["username"]; 
    }
    
    //获取回复功能链接
    public function getReplyLink($sender)
    {
        $usr = sqlRow("select proxy_id,sid,type,username from users where id=".$sender);
        if (($this->admin >= 1 && $this->admin <= 6 && $usr["type"] > 6) || 
            ($this->admin == 8 && ($usr["type"] == 7 || $usr["type"] == 9))) 
            $name = "系统管理员";
        else
            $name = get_perm_name($usr["type"])." ".$usr["username"]; 
        
        if ($usr["type"] == 9 || 
                ($this->admin < 6 && $usr["type"] == 8) || 
                ($this->admin < 7 && $usr["type"] == 7))
            return $name;
        $reply = $usr["proxy_id"].",".$usr["sid"].",".$usr["type"].",".$sender;
        return '<a href="#" url="'.U("Message/outBox")."&form=add&reply=".$reply.'"
                 pop="{'.$this->getPop("add").'}">'.$name.'</a>';
    }
    
    //处理发件箱数据列表中收件人显示
    public function getRecverInfo($data, $txt = "") 
    {
        switch ($data["recv_type"])
        {
        	case 0:
                if ($data["grp"] === "0" || 
                    $data["grp"] === ",1,2,3,6," || 
                    $data["grp"] === ",6,1,2,3,")
                    $ret = "所有组";
                else
                {
                    $grp = explode(",", $data["grp"]);
                    foreach ($grp as $gid)
                    {
                        if ($gid)
                           $ret .= get_perm_name($gid)." ";
                    }
                    $ret = rtrim($ret, ' ');
                }
        	    break; 
        	case 1:
                $usrname = sqlCol("select u.username from message m, message_text t, users u where 
                                   u.id=m.recv_id and m.mess_id=t.id and t.id=".$data["id"]);
                $ret = get_perm_name(ltrim(rtrim($data["grp"], ","), ","))." ".$usrname;
        	    break;
        	case 2:
                $ret = "所有人";
                break;
        	default:
        	    break;
        }
        if ($data["proxy"] === "0")
            $data["proxy"] = "所有代理商";
        else
            $data["proxy"] = sqlCol("select proxy_sub_name from users where id=".$data["proxy"]);
        
        if ($data["sid"] === "0")
            $data["sub"] = "所有分站";
        else
            $data["sub"] = sqlCol("select proxy_sub_name from users where id=".$data["sid"]);
        
        $data["sender"] = $this->getSenderName($data["sender"]);
        
        return $ret;
    }
    
    //处理收件箱数据列表中发件人显示
    public function getInBoxInfo($data, $txt = "")
    {
        if ($data["status"] == 1)
            $data["read_time"] = "未读信件";
        return $this->getSenderName($data["sender"]);
    }
    
    //站内信通用查询界面代码
    private function _commonSearch()
    {
        $this->setFind("item 0", array("name" => "mess_type", "type" => "select",
                "default" => "所有类型", "defval" => 0,
                "list" => parse_select_list("array", $this->mtype, $this->mtype)));
        $this->setFind("item 1", array("name" => "status", "type" => "select",
                "default" => "所有状态", "defval" => -1,
                "list" => parse_select_list("array", array(1, -2), 
                        array("未读", "已读"))));
        $this->setFind("item 2", array("name" => "search_type", "type" => "select", 
                "default" => "内容", "defval" => "content", 
                "list" => parse_select_list("array", array("title"), 
                        array("标题"))));
    }
    
    //收件箱公共代码  即主站收件箱代码
    private function _commonInBox()
    {
        //设置页面标题
        $this->setNav("&nbsp;->&nbsp;站内信&nbsp;->&nbsp;收件箱");
        //初始化并且设置数据库表名
        $this->mainPage("message");
        
        $this->setTool("tool_btn_down", array("txt" => "发件箱", "icon" => "send", 
              "class" => "btn-success", "url" => U("Message/outBox"),
               "tag" => "#body"));
        
        $this->_commonSearch();
        
        //////////////////////////////////////////////////////
        $this->setData("close_op", 1);
        $this->setData("close_chkall", 1);
        
        //因为要多表操作，所以要自定义sql所以以下代码是为了解析查询功能
        $sfind = session("find_where");
        if (!$sfind)
            $find_con = "1";
        else
        {
            foreach ($sfind as $key => $val) 
            {
                if ($key == "status")
                {
                    if ($val == -1)
                        $find_con .= " (m.status=0 or m.status=1) and ";
                    else if ($val == -2)
                        $find_con .= " m.status=0 and ";
                    else
                        $find_con .= " m.status=1 and ";
                    continue;
                }
                
                if (is_array($val))
                    $find_con .= " t.".$key." ".$val[0]." '".$val[1]."' and ";
                else
                    $find_con .= " t.".$key."='".$val."' and ";
            }
            $find_con = rtrim($find_con, " and ");
        }
//         dump($find_con);
        
        $sql = "select t.mess_type,t.title,t.sender,t.send_time,m.read_time,m.status,m.mess_id from 
                    message m, message_text t where t.id=m.mess_id and m.recv_id=".$this->uid.
                    " and ".$find_con." order by date_format(t.send_time, '%Y-%m-%d') desc, m.status desc ".$this->getPage();
//         dump($sql);
        
        $this->setData("data_list", sqlAll($sql));
        $this->setData("tooltip", 1);
        $this->setPage("total", sqlCol("select count(m.id) from message m, message_text t 
                                    where t.id=m.mess_id and ".$find_con." and m.recv_id=".$this->uid));
        
        $this->setTitle(array("信件类型", "信件标题", "发送人", "发送时间", "阅读时间"));
        $this->setField(array("mess_type", "title", "sender", "send_time", "read_time"));
        $this->setData("data_field 2 run", "Message/getInBoxInfo");
        $this->setData("data_field 1 class", "text-left");
        $this->setField("title", array("name" => 1, "url" => U("Message/outBox")."&form=info&read=1&where='id=[mess_id]'",
                "pop" => $this->getPop("info", "read")));
    }
    
    //发件箱公共代码
    private function _commonOutBox()
    {
        //设置页面标题
        $this->setNav("&nbsp;->&nbsp;站内信&nbsp;->&nbsp;发件箱");
        //初始化并且设置数据库表名
        $this->mainPage("message_text");
        
        $this->setTool("close_batch", 0);
        $btn_message = new FormElement("btn_message", "button", "收件箱", array("begin" => 0, 
                "over" => 0, "close_element_div" => 1, "icon" => "envelope", "class" => "btn-success",
                "url" => U("Message/inBox"), "tag" => "#body"
        ));
        
        /////////////////////////////////////////////
        //设置添加按钮弹出窗口
        $this->setTool("tool_btn_down", array("txt" => "写信", "icon" => "pencil", 
                                              "end" => "&emsp;",
                                              "url" => U()."&form=add",
                                              "pop" => $this->getPop("add")));
        
        $this->setTool("my_tool_batch", $btn_message->fetch());
        
        $this->_commonSearch();
        $this->setFind("item 1", array());
        
        //////////////////////////////////////////////////////
        $this->setForm("name", "messform");
//         $this->setForm("kajax", "false");
        $this->setForm("handle_run post", "Message/messSave");
        $this->setForm("handle_run info", "Message/messInfo");
        
        if (I("get.form") == "add")        
            $this->_createRecvList();
        
        ////////////////////////////////////////////////////////
        
        $this->setData("close_op", 1);
        $this->setData("close_chkall", 1);
        $this->setData("tooltip", 1);
        $this->setData("order", "send_time desc");
        
        $this->setTitle(array("信件类型", "信件标题", "接收人", "发送人", "所属分站", "所属代理", "发送时间"));
        $this->setField(array("mess_type", "title", "recver", "sender", "sub",  "proxy", "send_time"));
        $this->setData("data_field 2 run", "Message/getRecverInfo");
        $this->setData("data_field 1 class", "text-left");
        $this->setField("title", array("name" => 1, "url" => U()."&form=info&where='id=[id]'",
                "pop" => $this->getPop("info")));
    }
    
    
    //发件箱表单设置和刷新
    private function _outBoxDisplay()
    {
        if (I("get.form") == "add")        
        {
            //设置回复 自动选择收件人
            $usrlist = 0;
            $reply = I("get.reply");
            if ($reply)
            {
                $reply = explode(",", $reply);
                $grp = array(8 => 2, 6 => 3, 1 => 4, 2 => 5, 3 => 6);
                for ($i = 1; $i < 7; $i++)
                {
                    $this->recv_el["grp"][$i]->setOp("bool", "");
                }
                $this->recv_el["grp"][$grp[$reply[2]]]->setOp("bool", "checked");
                $usrlist = $reply[3];
            }
            
            //设置表单成员， 添加、编辑和信息的元素  uniq为不得重复
            $this->setElement("title", "string", "信件标题", array("bool" => "required", 
                    "label_cols" => 2, "maxlength" => 100));
            $mtype = $this->mtype;
            array_shift($mtype);
            $this->setElement("mess_type", "select", "信件类型", array("value" => "",
                    "label_cols" => 2, "bool" => "required", "element_cols" => 4,
                    "list" => parse_select_list("array", $mtype, $mtype), 
            ));
            $this->setElement("recv", "custom", "收件人", array("label_cols" => 2, 
                    "element_cols" => 9, "custom_html" => $this->getRecvWin()));
            $this->setElement("usrlist", "hidden", "", array("value" => $usrlist));
            $this->setElement("content", "textarea", "信件内容", array("id" => "summernote",
                    "label_cols" => 2, "element_cols" => 9));
                    
            $this->setForm("btn 0 txt", "发送");
            $this->setForm("js", "kyo_message");
        }
        $this->display();
    }
    
    //站内信首页， 默认调用收件箱
    public function index()
    {
        $this->inBox();
    }
    
    //收件箱首页 用于分权操作
    public function inBox()
    {
        $this->_commonInBox();
        $this->display();
    }
    
    
    //发件箱首页 用于分权操作
    public function outBox()
    {
        $this->_commonOutBox();
        
        switch ($this->admin)
        {
        	case 7:
                $this->assist();
        	    break;
        	case 8:
                $this->proxy();
        	    break;
        	case 6:
                $this->substation();
        	    break;
        	case 1:
                $this->finance();
        	    break;
        	case 3:
        	case 4:
        	case 2:
                $this->salesman();
        	    break;
        	default:
        	    break;
        }
    
        $this->_outBoxDisplay();
    }
    
    public function assist()
    {
        if (I("get.form") == "add")        
        {
            $this->recv_el["main"][2]->setOp("txt", "DYK管理员");
            $this->recv_el["main"][1]->setOp("pclass", "hidden");
        }
        $this->setData("where", "sender!=2");
    }
    
    public function proxy()
    {
        if (I("get.form") == "add")        
        {
        }
        $this->setData("where", "sender=".$this->uid." or sender in (select id from users where proxy_id=".$this->proxy.")");
        $this->setDataHide(array(5));
    }
    
    public function substation()
    {
        if (I("get.form") == "add")        
        {
            $this->recv_el["main"][0]->setOp("pclass", "hidden");
            $this->recv_el["main"][1]->setOp("bool", "");
            $this->recv_el["main"][4]->setOp(array("value" => $this->proxy, "bool" => "checked"));
            
            $this->recv_el["sub"][0]->setOp("pclass", "hidden");
            $this->recv_el["sub"][1]->setOp("bool", "");
            $this->recv_el["sub"][2]->setOp(array("value" => $this->sid, "bool" => "checked"));
            
            $this->recv_el["grp"][1]->setOp(array("pclass" => "hidden", "bool" => ""));
            $this->recv_el["grp"][3]->setOp(array("pclass" => "hidden", "bool" => ""));
        }
        $this->setData("where", "sender=".$this->uid." or sender in (select id from users where sid=".$this->sid.")");
        $this->setDataHide(array(4, 5));
    }
    
    public function finance()
    {
        $this->substation();
        if (I("get.form") == "add")        
        {
            $this->recv_el["grp"][1]->setOp(array("pclass" => "hidden", "bool" => ""));
            $this->recv_el["grp"][2]->setOp(array("pclass" => "hidden", "bool" => ""));
            $this->recv_el["grp"][3]->setOp(array("pclass" => "", "bool" => "checked"));
            $this->recv_el["grp"][4]->setOp(array("pclass" => "hidden", "bool" => ""));
        }
        $this->setData("where", "sender=".$this->uid);
        $this->setDataHide(array(3));
    }
    
    public function salesman()
    {
        $this->finance();
        if (I("get.form") == "add")        
        {
            $this->recv_el["grp"][4]->setOp(array("pclass" => "", "bool" => "checked"));
            $this->recv_el["grp"][5]->setOp(array("pclass" => "hidden", "bool" => ""));
            $this->recv_el["grp"][6]->setOp(array("pclass" => "hidden", "bool" => ""));
        }
    }
    
    //查看站内信窗口
    public function messInfo($con, & $formObj)
    {
    	$obj = & $formObj->form["dataObj"];
    	$el = & $formObj->form["element"];
    	$data = $obj->where($con)->select();
        $mes = $data[0];
        $el = array();
        if (I("get.read"))
        {
            $ret = M("message")->where("recv_id=".$this->uid." and mess_id=".$mes["id"]." and status=1")
                        ->save(array("status" => 0, "read_time" => getcurtime()));
            if ($ret)
            {
                $_SESSION["notice"]["msg"] = self::getMessage();
                echo js_head('$(".top_mess_num").html("'.$_SESSION["notice"]["msg"].'");');
            }
        }
        if (is_range($this->admin, array(1, 6, 7, 8)) && $mes["recv_type"] != 2)
            $sender = $this->getReplyLink($mes["sender"]);
        else
            $sender = $this->getSenderName($mes["sender"]);
        
//         dump($sender);
        
        $html = '<div class="col-md-12 text-center"><h3><b>'.$mes["title"].'</b></h3></div>';
        $html .= '<div class="col-md-6 text-left" style="border-bottom:1px #999 solid">';
        $html .= $sender.'</div>';
        $html .= '<div class="col-md-6 text-right" style="border-bottom:1px #999 solid">';
        $html .= '<em>'.$mes["send_time"].'</em></div>';
        $html .= '<div class="col-md-12" style="margin-top:10px;min-height:360px;">';
        $html .= $mes["content"].'</div>';
        $formObj->setElement("mes_info_el", "custom", "", array("close_label" => 1, "element_cols" => 12,
                 "custom_html" => $html));
    }    
    
    //创建收件人选择界面的元素
    public function _createRecvList()
    {
        $this->recv_el["main"][0] = new FormElement("main_sel", "custom", "", array("element_cols" => 12, 
                "pclass" => "main_class_sel", "begin"  => 0, "over" => 0, "custom_html" => ""));
        
        $this->recv_el["main"][1] = new FormElement("main_sel", "sradio", "所有人", array("element_cols" => 3, 
                "pclass" => "message_recv", "form" => "messform", "bool" => "checked", 
                "value" => 0,
        ));
        
        $this->recv_el["main"][2] = new FormElement("main_sel", "sradio", "超级助理", array("element_cols" => 3, 
                "pclass" => "message_recv", "form" => "messform", "id" => "assist_sel_id",
                "value" => 1,
        ));
        
        $this->recv_el["main"][3] = new FormElement("main_sel", "sradio", "所有代理", array("element_cols" => 3, 
                "pclass" => "message_recv", "form" => "messform", "id" => "proxy_all_sel_id",
                "value" => 2,
        ));
        
        $this->recv_el["main"][4] = new FormElement("main_sel", "sradio", "选择代理", array("element_cols" => 3, 
                "pclass" => "message_recv", "form" => "messform", "id" => "proxy_sel_id", 
                "ext" => 'url="'.U("Message/showGrpList").'&name=proxy&type=8"',
                "value" => 3,
        ));
        
        //------------------------------------------------------------------
        
        $this->recv_el["sub"][0] = new FormElement("sub_sel", "custom", "", array("element_cols" => 12, 
                "pclass" => "sub_class_sel", "begin"  => 0, "over" => 0, "custom_html" => ""));
        
        $this->recv_el["sub"][1] = new FormElement("sub_sel", "sradio", "所有分站", array("element_cols" => 3, 
                "pclass" => "message_recv", "form" => "messform", "bool" => "checked", 
                "id" => "sub_all_sel_id",
                "value" => 0,
        ));
        
        $this->recv_el["sub"][2] = new FormElement("sub_sel", "sradio", "选择分站", array("element_cols" => 3, 
                "value" => 1,
                "pclass" => "message_recv", "form" => "messform",
                "ext" => 'url="'.U("Message/showGrpList").'&name=sub&type=6"',
        ));
        
        //------------------------------------------------------------------
        $this->recv_el["grp"][0] = new FormElement("grp_sel", "custom", "", array("element_cols" => 12, 
                "pclass" => "grp_class_sel", "begin"  => 0, "over" => 0, "custom_html" => ""));
        
        $this->recv_el["grp"][1] = new FormElement("grp_sel", "scheckbox", "所有组", array("element_cols" => 2, 
                "pclass" => "message_recv", "bool" => "checked",  
                "id" => "grp_all_sel_id", "value" => 0,
        ));
        
        $this->recv_el["grp"][2] = new FormElement("grp_sel", "scheckbox", "代理商", array("element_cols" => 2, 
                "pclass" => "grp_proxy_sel message_recv", "form" => "messform", "bool" => "checked", 
                "value" => 8,
        ));
        
        $this->recv_el["grp"][3] = new FormElement("grp_sel", "scheckbox", "站长", array("element_cols" => 2, 
                "pclass" => "message_recv", "form" => "messform", "bool" => "checked", 
                "id" => "grp_sub_sel_id", "value" => 6,
        ));
        
        $this->recv_el["grp"][4] = new FormElement("grp_sel", "scheckbox", "财务", array("element_cols" => 2, 
                "pclass" => "message_recv", "form" => "messform", "id" => "grp_sel_fin_id", 
                "bool" => "checked", "value" => 1,
                "ext" => 'url="'.U("Message/showUserList").'&type=1"',
        ));
        
        $this->recv_el["grp"][5] = new FormElement("grp_sel", "scheckbox", "操作员", array("element_cols" => 2, 
                "pclass" => "message_recv", "form" => "messform", "id" => "grp_sel_oper_id", 
                "bool" => "checked", "value" => 2,
                "ext" => 'url="'.U("Message/showUserList").'&type=2"',
        ));
        
        $this->recv_el["grp"][6] = new FormElement("grp_sel", "scheckbox", "拓展员", array("element_cols" => 2, 
                "pclass" => "message_recv", "form" => "messform", "id" => "grp_sel_sales_id", 
                "bool" => "checked", "value" => 3,
                "ext" => 'url="'.U("Message/showUserList").'&type=3"',
        ));
        
//         $this->recv_el["grp"][7] = new FormElement("grp_sel", "scheckbox", "卡主雇员", array("element_cols" => 2, 
//                 "pclass" => "message_recv", "form" => "messform", "id" => "grp_sel_emp_id",
//                 "bool" => "checked", "value" => 4, 
//                 "ext" => 'url="'.U("Message/showUserList").'&type=4"',
//         ));    
    }
    
    //获取收件人用户的界面
    public function getRecvWin()
    {
        $ret = "";
        foreach ($this->recv_el as $key => $grp)
        {
            $html = "";
            foreach ($grp as $ekey => $val)
            {
                if ($ekey == 0)
                    continue;
                
                if ($val)
                    $html .= $val->fetch();
            }
            $this->recv_el[$key][0]->_set("custom_html", $html.'<div class="visible-xs-block" style="width:100%;border-bottom:1px #000 solid;"></div>');
            $ret .= $this->recv_el[$key][0]->fetch();
        }
//         dump($ret);
        
        return $ret;
    }
    
    //发送站内信保存接收者列表函数
    public function _saveRecv($mid)
    {
        $ulist = explode("|", $_POST["usrlist"]);
        
        foreach ($ulist as $uid)
        {
            if (!$uid)
                continue;
            $data = array();
            $data["mess_id"] = $mid;
            $data["recv_id"] = $uid;
            M("message")->add($data);
        }
    }
    
    //发送站内信保存到数据库函数
    public function messSave(& $formObj)
    {
        $form = & $formObj->form;
        $obj = & $form["dataObj"];
        
        //验证分组是否选择，如果没有选择报错
        if (!$_POST["grp_sel"])
            $this->ajaxReturn(array("echo" => 1, "info" => "请选择收件分组!"));
            
        //验证内容是否为空
        if ($_POST["content"] == "<p><br></p>" || $_POST["content"] == "")
            $this->ajaxReturn(array("echo" => 1, "info" => "请输入信件内容!"));
        
        $con_len = strlen($_POST["content"]);
        if ($con_len > 1048576)  //限制信息内容大小不得超过1M
            $this->ajaxReturn(array("echo" => 1, "info" => "文件内容过大，请将上传的图片进行缩放裁剪! len:".$con_len));
        
        //获取接受者ID列表字段的长度，因为选择组员会自动在前后加|, 所以如果此字段长度为1 则代表给所有人发或者给助理发送信息
        $ulen = strlen($_POST["usrlist"]);
        
        switch ($_POST["main_sel"])
        {
        	case 0:
                $_POST["recv_type"] = 2;
        	    break;
        	case 1:
                $_POST["recv_type"] = 1;
                $_POST["usrlist"] = $this->admin == 9 ? 3 : 2;
        	    break;
        	case 2:
        	case 3:
                $_POST["proxy"] = 0;
                $_POST["sid"] = 0;
        	default:
                $_POST["proxy"] = isset($_POST["proxy"]) ? 0 : $_POST["main_sel"];
                $_POST["sid"] = isset($_POST["sid"]) ? 0 : $_POST["sub_sel"];
                
                $gcount = strlen($_POST["grp_sel"]);
                if ($gcount == 0)
                {
                    if ($_POST["sid"] > 1)   //如果分站没有分站，分站选择了则代表给本站所有人发信息
                        $_POST["grp"] = ",1,2,3,6,";
                    else    //如果分站没有选择则代表给所有组发信息
                        $_POST["grp"] = 0;
                }
                else
                {
                    if ($_POST["grp_sel"] === "0,8,6,1,2,3" || $_POST["grp_sel"] === "8,6,1,2,3")
                        $_POST["grp"] = 0;
                    else
                        $_POST["grp"] = ",".$_POST["grp_sel"].",";
                }
                
                //如果是选择了代理并且选择了分组代理商，则给代理商本人发信息
                if ($_POST["proxy"] > 0 && $_POST["grp"] === ",8,")
                {
                    $_POST["test"] = "proxy";
                    $_POST["recv_type"] = 1;
                    $_POST["usrlist"] = $_POST["proxy"];
                }
                
                //如果是选择了分站并且 选择了分组站长，则给站长本人发信息
                if ($_POST["sid"] > 1 && $_POST["grp"] === ",6,")
                {
                    $_POST["test"] = "sid";
                    $_POST["recv_type"] = 1;
                    $_POST["usrlist"] = $_POST["sid"];
                }
                
                if ($ulen != 1 && $gcount == 1 && 
                        !strchr(substr($_POST["usrlist"], 1, $ulen - 2), "|"))
                {
                    $_POST["test"] = "usrlist";
                    $_POST["recv_type"] = 1;
                }
        	    break;
        }
        
        //调试语句
//         $this->ajaxReturn(array("echo" => 1, 
//                 "info" => "proxy: ".$_POST["proxy"]." sid: ".$_POST["sid"].
//                         " | grp_sel: ".strlen($_POST["grp_sel"])."  ".$_POST["grp_sel"].
//                         " | group: ".$_POST["grp"]." | recv_type: ".$_POST["recv_type"].
//                         " | usrlist: ".$_POST["usrlist"]." | test: ".$_POST["test"]));
        
        $_POST["send_time"] = getcurtime();
        $_POST["sender"] = $this->uid;
        
        $ret = $obj->create($_POST, 1);
        $ret = $ret ? $obj->add() : $ret;
        
        if ($ret)
        {
            if ($_POST["recv_type"] == 1 || $ulen != 1)
                $this->_saveRecv($ret);
            $form["return"]["info"] = "发送成功! ";
        }
        else
            $this->ajaxReturn(array("echo" => 1, "info" => "发送失败! ".$obj->getError()));
        
        $this->ajaxReturn($form["return"]);
    }
    
    //发送站内信显示代理商和站长列表函数  供ajax调用弹出选择框
    public function showGrpList($name, $type, $proxy = 0)
    {
        $val = I("get.val");
        
        $usr = sqlAll("select id,proxy_sub_name from users where proxy_id=".$proxy." and type=".$type);
//         dump(M()->getLastSql());
        $html = "";
        foreach ($usr as $vo)
        {
            $clk = "checked";
            if ($val != $vo["id"])
                $clk = "";
                
            $obj = new FormElement($name."mem_sel", "sradio", $vo["proxy_sub_name"], array(
                    "element_cols" => 4, "bool" => $clk, "value" => $vo["id"]));
            $html .= $obj->fetch();
        }
//         dump($html);
        echo $html;
    }
    
    //发送站内信显示用户列表函数  供ajax调用弹出选择框
    public function showUserList($type, $sid = 0, $proxy = 0)
    {
        $val = I("get.val");
        
        $usr = sqlAll("select id,username from users where proxy_id=".$proxy." and sid=".$sid." and type=".$type);
//         dump(M()->getLastSql());
        $html = "";
        $obj = new FormElement("grpmem_sel".$type, "scheckbox", "所有人", array("element_cols" => 4, 
                "bool" => "checked", "value" => 0, "id" => "grpmem_all_sel_id".$type));
        $html .= $obj->fetch();
        
        foreach ($usr as $vo)
        {
            $obj = new FormElement("grpmem_sel".$type, "scheckbox", $vo["username"], array("element_cols" => 4, 
                    "bool" => "checked", "value" => $vo["id"]));
            $html .= $obj->fetch();
        }
//         dump($html);
        echo $html;
    }
}

?>
