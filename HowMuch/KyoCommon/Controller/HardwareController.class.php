<?php
namespace KyoCommon\Controller;
use Think\Controller;
use Common\Controller\ListPage;
use Common\Controller\Form;
use Common\Controller\FormElement;

class HardwareController extends ListPage
{
    private $admin;
    private $sid;
    private $uid;
    private $hwType = array("软件", "电脑", "平板", "手机");
    
    //构造函数
    public function __construct()
    {
        parent::__construct();
        $this->admin = get_user_info("admin");
        if (!($this->admin == 0 || $this->admin == 9 || $this->admin == 7))
        {
            header("Location:".ERROR_URL);
            exit(0); 
        }
    }    
    
   //获取客户相关弹窗口信息
    static public function getPop($type, $title = "")
    {
        $pop = "";
        switch ($type)
        {
        	case "add":
                if (!$title)
                    $title = "新增终端";
        		$pop = "w:650,h:540,n:'hwadd',t:".$title;
        	    break;
        	case "edit":
                if (!$title)
                    $title = "编辑终端";
        		$pop = "w:650,h:540,c:1,n:'hwedit',t:".$title;
        	    break;
        	case "info":
                if (!$title)
                    $title = "终端信息";
        		$pop = "w:900,h:580,c:1,n:'hwinfo',t:".$title;
        	    break;
        	case "model_add":
                if (!$title)
                    $title = "新增终端型号";
        		$pop = "w:950,h:450,n:'modeladd',t:".$title;
        	    break;
        	case "model_edit":
                if (!$title)
                    $title = "编辑终端型号";
        		$pop = "w:950,h:450,c:1,n:'modeledit',t:".$title;
        	    break;
        	default:
        	    break;
        }
        return $pop;
    }
    
    public function hwField(& $data, $key)
    {
        $val = "";
        switch ($key)
        {
        	case "proxy":
                $val = ($data[$key] == 0) ? "所有代理商" : sqlCol("select proxy_sub_name from users where id=".$data[$key]);
                break;
        	case "sid":
                $val = ($data[$key] == 0) ? "所有分站" : sqlCol("select proxy_sub_name from users where id=".$data[$key]);
        	    break;
        	case "gid":
                $perm_name = C("PERM_NAME");
                $val = ($data[$key] == -1) ? "所有分组" : $perm_name[$data[$key]];
                break;
        	case "uid":
                $val = ($data["uid"] == 0) ? "所有用户" : sqlCol("select username from users where id=".$data["uid"]);
                break;
        	default:
                $val = $data[$key];
        	    break;
        }
        return $val;
    }
    
    public function hwOp($data, $oplist = array())
    {
        $ophtml = "";
        $index = 1;
        foreach ($oplist as $op)
        {
            parse_link($op["url"], $data);
            parse_link($op["pop"], $data);
            parse_link($op["link"], $data);
            $opObj = new FormElement($index++, "link", $op["txt"], $op);
            $ophtml .= $opObj->fetch()."&emsp;";
        }
        $change = new FormElement("btn_change", "link", '换机', array(
                "query" => true,
                "ext" => 'confirm="换机将会清除密钥，确定这样做吗？" 
                            url="'.U("Hardware/hwKeyReset").'&id='.$data["id"].'"',
        ));
        $ophtml .= $change->fetch();
        
        $txt = "锁定";
        $status = LOCK;
        
        if ($data["state"] == LOCK)
        {
            $txt = '解锁';
            $color = 'kyo_red';
            $status = NORMAL;
        }
        
        $lock = new FormElement("btn_lock", "link", '<span class="'.$color.'">'.$txt.'</span>', array(
                "ext" => 'confirm="确定'.$txt.'吗？" 
                    url ="'.U("Hardware/hwLock")."&id=".$data["id"]."&status=".$status.'"',
        ));
        
        return $lock->fetch()."&emsp;".$ophtml;
    }
    
    public function hwLock($id, $status = LOCK)
    {
        $return = array("echo" => 1,"info" => "锁定成功!","url" => session("prev_urlhw"),"tag" => ".hw");
        M("hw")->where("id=".$id)->setField("state", $status);
        $hw = sqlRow("select proxy,sid,gid,uid from hw where id=".$id);
        
        sqlCol("delete from kyo_session where session_id in (select session_id from users where 
                (proxy_id=".$hw["proxy"]." or ".$hw["proxy"]."=0) and (".$hw["sid"]."=0 or sid=".$hw["sid"].") 
                and (".$hw["gid"]."=-1 or type=".$hw["gid"].") and (".$hw["uid"]."=0 or id=".$hw["uid"]."))");
        sqlCol("update kyo_session set session_id=null where 
                (proxy_id=".$hw["proxy"]." or ".$hw["proxy"]."=0) and (".$hw["sid"]."=0 or sid=".$hw["sid"].") 
                and (".$hw["gid"]."=-1 or type=".$hw["gid"].") and (".$hw["uid"]."=0 or id=".$hw["uid"].")"); 
        
        if ($status != LOCK)
            $return["info"] = "解锁成功!";
        
        $this->ajaxReturn($return);
    }
    
    public function index()
    {
        $hwType = $this->hwType;
        
        $this->setNav("&nbsp;->&nbsp;"."终端管理");
        $this->mainPage("hw");
        
        $this->setFind("item sid", array("name" => "sid", "type" => "select", "default" => "所有分站",
                "list" => parse_select_list("select id,proxy_sub_name from users where type=6")));
        $this->setFind("item 1", array("name" => "type", "type" => "select", "default" => "所有类型",
                "list" => parse_select_list("array", $hwType, $hwType)));
        $this->setFind("item 2", array("name" => "search_type", "type" => "select", 
                "default" => "内部代码", "defval" => "code", 
                "list" => parse_select_list("array", array("sn", "model"), array("产品序号", "终端名称")),
        ));
        
		//设置添加按钮弹出窗口
        $this->setTool("tool_btn_down", array("txt" => "新增终端", "icon" => "plus",
        		"url" => U()."&form=add",
        		"pop" => $this->getPop("add")));
        
        $this->setForm("name", "hwform");
        $this->setForm("handle_run post", "Hardware/hwSave");
        $this->setForm("handle_run show_edit", "Hardware/hwEdit");
        
        $this->setElement("model", "select", "终端型号", array("bool" => "required",
                "list" => parse_select_list("select id,hw_name from hw_model")));
        $this->setElement("sn", "string", "产品序号", array("bool" => "required", "maxlength" => 50));
		$this->setElement("proxy", "select", "绑定代理", array("value" => 0,
                "url" =>  U("Hardware/getSub"),
                "tag" => ".sel_sub_id",
                "list" => parse_select_list("select id,proxy_sub_name from users where type=8", 
                        "", "", "所有代理", "0")));
        $this->setElement("sid", "select", "绑定分站", array("value" => 0, "pclass" => "sel_sub_id",
                    "list" => array(array("val" => "0", "txt" => "所有分站"))));
        $this->setElement("gid", "select", "绑定分组", array("value" => -1,
                "list" => parse_select_list("array", array_keys(C("PERM_NAME")), C("PERM_NAME"), "所有分组", "-1")));
        $this->setElement("uid", "select", "绑定人员", array("value" => 0, "pclass" => "sel_subuser_id",
                    "list" => array(array("val" => "0", "txt" => "所有用户"))));
		/*添加隐藏提交字段*/
        
        $js = '$("#gid_id").change(function(){
                    var sid = $("#sid_id").val();
                    var gid = $("#gid_id").val();
                    if (sid == 0 || gid == -1)
                        return false;
                    var url = "'.U("Hardware/getSubUser").'&sid="+sid+"&gid="+gid;
                    partial_refresh(url, ".sel_subuser_id"); 
                });';
        $this->setElement("remark", "textarea", "补充说明", array("back_ext" => js_head($js)));
                //设置表单添加或修改自动完成项  创建时间和更新时间不需要输入自动填写
        $this->setForm("auto", array(
        	   array("create_time", 'getcurtime', 1, "function"),
        	   array("update_time", 'getcurtime', 3, "function"),
        ));
        
        $this->setOp("编辑", U()."&form=edit&where='id=[id]'", array("pop" => $this->getPop("edit")));
        
        $this->setData("tooltip", 1);
        $this->setData("order", "update_time desc");
    	$this->setTitle(array("内部代码", "产品序号", "终端名称", "绑定代理", "绑定分站", "绑定分组", "绑定用户", "补充说明"));
    	$this->setField(array("code", "sn", "model", "proxy", "sid", "gid", "uid", "remark"));
        $this->setField("code", array("name" => 0, "url" => U("Hardware/hwKeyInfo")."&id=[id]",
                "pop" => $this->getPop("info", "[code] 密钥详细信息")));
        $this->setData("formatfield", "Hardware/hwField");
        $this->setData("op_call", array("run", "hardware/hwOp"));
        
    	$this->display();
    }
    
    public function hwEdit($con, & $formObj)
    {
        $form = & $formObj->form;
        $el = & $form["element"];
        $obj = & $form["dataObj"];
        
        $data = $obj->where($con)->select();
        $hw = $data[0];
        $formObj->formDataShowEdit($data);
        
        $el["model"]["value"] = sqlCol("select id from hw_model where hw_name='".$hw["model"]."' and hw_type='".$hw["type"]."'");
        
        if ($hw["proxy"])
            $el["sid"]["list"] = parse_select_list("select id,proxy_sub_name from users where type=6 and proxy_id=".$hw["proxy"]);
        if ($hw["sid"] && $hw["gid"] != -1)
            $el["uid"]["list"] = parse_select_list("select id,username from users where type=".$hw["gid"]." 
                            and sid=".$hw["sid"]);
    }
    
    public function hwKeyReset($id)
    {
        sqlCol("update `hw` set `hw_key`=null where id=".$id);
        $this->ajaxReturn(array("echo" => 1, "info" => "清除密码成功，请换机登录!"));
    }
    
    public function hwKeyInfo($id)
    {
        $hw = sqlRow("select code,hw_key from hw where id=".$id);
        $info = new Form("");
        $info->set("close_btn_down", 1);
        $info->setElement("info_code", "static", "内部代码", array("value" => $hw["code"]));
        $pwd_info = "<br />&emsp;&emsp;".str_replace("|", "<br />&emsp;&emsp;", $hw["pwd"]);
        $pwd_info = str_replace(":", ":&nbsp;", $pwd_info);
        $info->setElement("info_pwd", "static", "相关密码", array("value" => $pwd_info));
        
        $info->setElement("info_key", "static", "硬件密钥", array("value" => $hw["hw_key"]));
        $info->setElement("url_key", "static", "URL密钥", array(
                "value" => think_encrypt($hw["code"]."|".$hw["hw_key"], date("Y-m-d").UC_AUTH_KEY)));
        $hw_info = str_replace("|", "<br />", think_decrypt($hw["hw_key"], UC_AUTH_KEY));
        $hw_info = str_replace("_", "<br />&emsp;&emsp;", $hw_info);
        $hw_info = str_replace(":", ":<br />&emsp;&emsp;", $hw_info);
        
        $info->setElement("info_hw", "static", "解密信息", array("value" => $hw_info));
        echo $info->fetch();
        exit(0);
    }
    
    //硬件保存函数
    public function hwSave(& $formObj)
    {
        $form = & $formObj->form;
        $obj = & $form["dataObj"];
        
        $model = sqlRow("select hw_type,hw_name from hw_model where id=".$_POST["model"]);
        $_POST["type"] = $model["hw_type"];
        $_POST["model"] = $model["hw_name"];
        
        if ($_POST["id"])  //修改
        {
            $ret = $obj->auto($form["auto"])->create($_POST, 2);
            $ret = $ret ? $obj->where("id=".$_POST["id"])->save() : $ret;
            $form["return"]["info"] = $ret ? "提交成功!" : "输入数据格式有误!";
        }
        else  //添加
        {
            while (1)
            {
                $_POST["code"] = myhex(mt_rand(256, 4095)).myhex(mt_rand(256, 4095));
                if (!sqlCol("select id from hw where code='".$_POST["code"]."'"))
                    break;
            }
            $_POST["pwd"] = "bios:".mt_rand(100000, 999999).
                                "|usr:".rand_pwd(8)."|root:".rand_pwd(8);
        
            $ret = $obj->auto($form["auto"])->create($_POST, 1);
            if (!$ret)
                $form["return"]["info"] = $obj->getError();
            else
            {
                $ret = $obj->add();
                $form["return"]["info"] = $ret ? "提交成功!" : "输入数据格式有误!".M()->getLastSql();
            }
        }
        
        if (!$ret)
        {
            $form["return"]["close"] = 0;
            $form["return"]["url"] = "";
        }
        
        $this->ajaxReturn($form["return"]);
    }    
    
    //获取所属代理的分站列表
    public function getSub()
    {
        $sub = new FormElement("sid", "select", "", array("close_label" => 1, "close_element_div" => 1, 
                "value" => 0, "bool" => "required", "pclass" => "sel_sub_id", "begin" => 0, 
                "over" => 0, "form" => "hwform",
                "list" => parse_select_list("select id,proxy_sub_name from users where type=6 and proxy_id=".I("get.val"), 
                              "", "", "所有分站", "0")));
        echo $sub->fetch();
    }
    
    //获取所属代理的分站用户列表
    public function getSubUser()
    {
        $user = new FormElement("uid", "select", "", array("close_label" => 1, "close_element_div" => 1, 
                "value" => 0, "bool" => "required", "pclass" => "sel_subuser_id", "begin" => 0, 
                "over" => 0, "form" => "hwform",
                "list" => parse_select_list("select id,username from users where type=".I("get.gid")." and sid=".I("get.sid"), 
                              "", "", "所有用户", "0")));
        echo $user->fetch();
    }
        
    
    public function model()
    {
        $hwType = $this->hwType;
        
        $this->setNav("&nbsp;->&nbsp;"."型号管理");
        $this->mainPage("hw_model");
        
        $this->setFind("item 0", array("name" => "hw_type", "type" => "select", "default" => "所有类型",
                "list" => parse_select_list("array", $hwType, $hwType)));
        $this->setFind("item 1", array("name" => "search_type", "type" => "select", 
                "default" => "型号名称", "defval" => "hw_name"));
        
		//设置添加按钮弹出窗口
        $this->setTool("tool_btn_down", array("txt" => "新增型号", "icon" => "plus",
        		"url" => U()."&form=add",
        		"pop" => $this->getPop("model_add")));
        
        $this->setData("tooltip", 1);
		$this->setForm("cols",2);
        $this->setForm("name", "hwmodelform");
        
        $this->setElement("hw_name", "string", "型号名称", array("bool" => "required", "maxlength" => 30));
		$this->setElement("hw_type", "select", "终端类别", array("bool" => "required", 
                "list" => parse_select_list("array", $hwType, $hwType),
		));
        $this->setElement("cpu", "string", "CPU", array("maxlength" => 30));
        $this->setElement("mem", "string", "内存", array("maxlength" => 30));
        $this->setElement("disk", "string", "存储", array("maxlength" => 30));
        $this->setElement("display", "string", "显示", array("maxlength" => 30));
// 		$this->setElement("img", "file", "实物图片", array("bool" => "required"));		
        $this->setElement("remark", "textarea", "补充说明");
        //设置表单添加或修改自动完成项  创建时间和更新时间不需要输入自动填写
        $this->setForm("auto", array(
        	   array("create_time", 'getcurtime', 1, "function"),
        	   array("update_time", 'getcurtime', 3, "function"),
        ));
        
        $this->setOp("编辑", U()."&form=edit&where='id=[id]'", array("pop" => $this->getPop("model_edit")));
        
    	$this->setTitle(array("类别", "名称", "CPU", "内存", "存储", "显示", "说明"));
    	$this->setField(array("hw_type", "hw_name", "cpu", "mem", "disk", "display", "remark"));
        
    	$this->display();
    
    }
}
