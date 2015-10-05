<?php
namespace Install\Controller;
use Think\Controller;
use Common\Controller\Form;
use Common\Controller\FormElement;

class IndexController extends Controller 
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function run($cmd = "")
    {
        if (!is_login() || session("user_master") != 1)
            $this->redirect("Home/Index/index");
        
        switch ($cmd)
        {
        	case "svn":
                $cmdstr = "svn up file:///home/svndata/cardv2.0/ /cardms1.0/";
                break;
        	case "local_sync":
                $cmdstr = "/usr/local/bin/my local";
                break;
        }
        exec($cmdstr, $ret); 
        if (!$ret)
            $this->ajaxReturn(array("echo" => 1, "info" => "操作失败!"));
        $html = "";
        foreach ($ret as $row)
        {
            $html .= $row."<br />";
        }
        $this->ajaxReturn(array("echo" => 1, "info" => "操作成功!", "callback" => '$("#cmdRet_id").html("'.$html.'")'));
    }
    
    public function test()
    {
        if (!is_login() || session("user_master") != 1)
            $this->redirect("Home/Index/index");
        
        if (IS_POST)
        {
            eval($_POST["phpcode"]);
//             $this->ajaxReturn(array("echo" => 1, "info" => "执行成功!"));
        }
        
        $deg = new Form("", array("kajax" => "false", "target" => "_blank"));
        $deg->setElement("phpcode", "textarea", "", array("bool" => "required", 
                "close_label" => 1, "rows" => 8, "element_cols" => 12));
        $deg->set("btn 0 txt", "执行");
        echo $deg->fetch();
        
        exit(0);
    }
    
    public function index()
    {
        if (!is_login() || session("user_master") != 1)
            $this->redirect("Home/Index/index");
            
        $value = "";
        $list = "";
        $dpop = "{w:640,h:500,n:'debinfo',t:调试代码}";
        
//         if (!isset($_SESSION["notice"]["msg"]))
//             $_SESSION["notice"]["msg"] = 0;
//         else
//             $_SESSION["notice"]["msg"]++;
//         dump($_SESSION["notice"]["msg"]);
        
        $type = get_user_info("admin");
        
        $perm = new Form("", array("cols" => 2, "kajax" => "true", "action" => U("Index/perm_handle")));
        $perm->setElement("perm_group", "group", "权限选择");
        $perm->setElement("perm_type", "select", "权限类型", array("group" => "start", "value" => get_user_info("admin"),
                "list" => parse_select_list("array", array_keys(C("PERM_NAME")), C("PERM_NAME"))));
        if ($type > 0 && $type < 7)
        {
            $pclass = "";
            $list = parse_select_list("select id,proxy_sub_name from users where type=6");
            $value = get_user_info("sid");
        }
        else
            $pclass = "hidden";
        
            
        $perm->setElement("sub", "select", "所属分站", array("group" => "mid", "element_cols" => 2, "value" => $value,
                "pclass" => $pclass, "list" => $list, "ext" => 'url="'.U("Index/getSub").'"'));
        
        if ($type > 0 && $type < 6)
        {
            $pclass = "";
            $list = parse_select_list("select id,username from users where sid=".get_user_info("sid")." and type=".$type);
            $value = get_user_info("uid");
        }
        else
            $pclass = "hidden";
        
        $perm->setElement("member", "select", "人员选择", array("group" => "mid", "element_cols" => 2, "value" => $value,
                "pclass" => $pclass, "list" => $list, "ext" => 'url="'.U("Index/getMember").'"'));
        $perm->setElement("submit_btn", "button", "更新权限", array('group' => "end", "element_cols" => 1,
                "ext" => 'id="btn_submit" type="submit" url="'.U("Home/Index/index").'"',
        ));
        $perm->setElement("admin_group", "group", "后台管理");
        $perm->setElement("sync_local", "button", "本地同步", array("group" => "start", "element_cols" => 1, 
                "close_label" => 1, "pclass" => "col-xs-offset-1", "class" => "btn_global",
                "ext" => 'id="btn_sync" type="button" url="'.U("Index/run").'&cmd=local_sync"',
        ));
        $perm->setElement("svn", "button", "备份数据", array("group" => "mid", "element_cols" => 1, 
                "ext" => 'id="btn_build" type="button" url="'.U("AutoBuild/Index").'"',
        ));
        $perm->setElement("debug", "button", "调试代码", array("group" => "mid", "element_cols" => 1, 
                "class" => "btn_global", "ext" => 'id="btn_debug" type="button" url="'.U("Index/test").'" 
                pop="'.$dpop.'"',
        ));
        $perm->setElement("back_code", "button", "备份代码", array("group" => "mid", "element_cols" => 1, 
                "ext" => 'id="btn_build" type="button" url="'.U("AutoBuild/Index").'"',
        ));
        $perm->setElement("sync_code", "button", "SVN同步", array("group" => "mid", "element_cols" => 1, 
                "class" => "btn_global",
                "ext" => 'id="btn_sync" type="button" url="'.U("Index/run").'&cmd=svn"',
        ));
        $perm->setElement("build", "button", "生成数据", array("group" => "mid", "element_cols" => 1, 
                "ext" => 'id="btn_build" type="button" url="'.U("AutoBuild/Index").'"',
        ));
        $perm->setElement("home", "button", "进入后台", array("group" => "end", "element_cols" => 1,
                "ext" => 'id="btn_home" type="button" url="'.U("Home/Index/index").'"',
                "back_ext" => '<a href="" target="_blank" class="hidden"><span id="alink">helo</span></a>',
        ));
        $perm->setElement("cmdRet", "static", "命令执行结果", array("sig_row" => 1));
        
        $perm->set("btn 0 class", "hidden");
        
        // =======================================================
        
        $form = new Form("", array("name" => "masterform", "cols" => 2, "action" => U("Index/base_form_handle")));
        $obj = M("users");
        
        $form->set("dataObj", $obj);
        $usr = $obj->field("login_name,pwd,umax")->limit(0, 8)->select();
        if ($usr)
            $form->setElement("op", "hidden", "", array("value" => "edit"));
        $this->_parse_usr_data($usr);
//         dump($usr);
        
        $form->setElement("group[0]", "group", "超级管理员");
        $form->setElement("name[0]", "string", "超级管理员登录码", 
                array("bool" => "required", "value" => $usr[0]["login_name"]));
        $form->setElement("show_pwd[0]", "password", "超级管理员密码", 
                array("bool" => "required", "value" => $usr[0]["show_pwd"]));
        $form->setElement("id[0]", "hidden", "", array("value" => 1));
        
        $form->setElement("group[1]", "group", "主站管理员");
        $form->setElement("name[1]", "string", "主站管理员登录码",
                array("bool" => "required", "value" => $usr[1]["login_name"]));
        $form->setElement("show_pwd[1]", "password", "主站管理员密码",
                array("bool" => "required", "value" => $usr[1]["show_pwd"]));
        $form->setElement("umax[assist]", "num", "超级助理序号",
                array("bool" => "required", "value" => $usr[1]["assist"]));
        $form->setElement("umax[proxy]", "num", "代理商序号",
                array("bool" => "required", "value" => $usr[1]["proxy"]));
        $form->setElement("id[1]", "hidden", "", array("value" => 2));
        
        $form->setElement("group[2]", "group", "超级助理");
        $form->setElement("name[2]", "string", "超级助理登录码",
                array("bool" => "required", "value" => $usr[2]["login_name"]));
        $form->setElement("show_pwd[2]", "password", "超级助理密码",
                array("bool" => "required", "value" => $usr[2]["show_pwd"]));
        $form->setElement("id[2]", "hidden", "", array("value" => 3));
        
        $form->setElement("group[3]", "group", "POS代理商");
        $form->setElement("name[3]", "string", "POS代理商登录码",
                array("bool" => "required", "value" => $usr[3]["login_name"]));
        $form->setElement("show_pwd[3]", "password", "POS代理商密码",
                array("bool" => "required", "value" => $usr[3]["show_pwd"]));
        $form->setElement("umax[sub]", "num", "分站序号",
                array("bool" => "required", "value" => $usr[3]["sub"]));
        $form->setElement("umax[pos]", "num", "POS机序号",
                array("bool" => "required", "value" => $usr[3]["pos"]));
        $form->setElement("id[3]", "hidden", "", array("value" => 4));
        
        $form->setElement("group[4]", "group", "站长");
        $form->setElement("name[4]", "string", "站长登录码",
                array("bool" => "required", "value" => $usr[4]["login_name"]));
        $form->setElement("show_pwd[4]", "password", "站长密码",
                array("bool" => "required", "value" => $usr[4]["show_pwd"]));
        $form->setElement("umax[finance]", "num", "财务序号",
                array("bool" => "required", "value" => $usr[4]["finance"]));
        $form->setElement("umax[operator]", "num", "操作员序号",
                array("bool" => "required", "value" => $usr[4]["operator"]));
        $form->setElement("umax[salesman]", "num", "拓展员序号",
                array("bool" => "required", "value" => $usr[4]["salesman"]));
        $form->setElement("umax[employee]", "num", "卡主雇员序号",
                array("bool" => "required", "value" => $usr[4]["employee"]));
        $form->setElement("umax[customer]", "num", "客户序号",
                array("bool" => "required", "value" => $usr[4]["customer"]));
        $form->setElement("id[4]", "hidden", "", array("value" => 5));
        
        $form->setElement("group[5]", "group", "财务主管");
        $form->setElement("name[5]", "string", "财务主管登录码",
                array("bool" => "required", "value" => $usr[5]["login_name"]));
        $form->setElement("show_pwd[5]", "password", "财务主管密码",
                array("bool" => "required", "value" => $usr[5]["show_pwd"]));
        $form->setElement("id[5]", "hidden", "", array("value" => 6));
                
        $form->setElement("group[6]", "group", "操作员");
        $form->setElement("name[6]", "string", "操作员登录码",
                array("bool" => "required", "value" => $usr[6]["login_name"]));
        $form->setElement("show_pwd[6]", "password", "操作员密码",
                array("bool" => "required", "value" => $usr[6]["show_pwd"]));
        $form->setElement("id[6]", "hidden", "", array("value" => 7));
        
        $form->setElement("group[7]", "group", "拓展员");
        $form->setElement("name[7]", "string", "拓展员登录码",
                array("bool" => "required", "value" => $usr[7]["login_name"]));
        $form->setElement("show_pwd[7]", "password", "拓展员员密码",
                array("bool" => "required", "value" => $usr[7]["show_pwd"]));
        $form->setElement("id[7]", "hidden", "", array("value" => 8));
        
        $form->set("btn 0 txt", "设置");
        
        $this->assign("body", $perm->fetch()."<br />".$form->fetch());
        $this->display(T("Install@Index/index"));     
    }
    
    public function perm_handle()
    {
        if (IS_POST)
        {
            $perm_id = array(1, 0, 0, 0, 0, 0, 0, 3, 4, 2);
            $return["url"] = U("Index/index");
            $return["echo"] = 0;
            $return["close"] = 0;
            $return["tag"] = "";
            
            $type = I("post.perm_type");
            $sid = I("post.sub");
            $uid = I("post.member");
            
            if ($type == 6)
                $id = $sid;
            else if ($type > 0 && $type < 6)
                $id = $uid;
            else
                $id = $perm_id[$type];
            
            $user = M("users")->where("id=".$id)->select();
            if (!$user)
            {
                $return["info"] = "设置失败!";// type=".$type.", sid=".$sid." uid=".$uid;
                $return["url"] = "";
                $this->ajaxReturn($return);
            }
            $user = $user[0];
            $user["proxy_id"] = $user["proxy_id"] ? $user["proxy_id"] : sqlCol("select id from users where type=8");
            $user["sid"] = $user["sid"] ? $user["sid"] : sqlCol("select id from users where type=6");
            if ($user["type"] == 8)
                $user["proxy"] = $user["id"];
            if ($user["type"] == 6)
                $user["sid"] = $user["id"];
                
            $auth = array(
                    'uid'        => $user['id'],
                    'code'        => $user['code'],
                    'name'       => $user['username'],
                    'login_name' => $user['login_name'],
                    'login_pwd'  => $user['pwd'],
                    'admin'      => $user['type'],
                    'sid'        => $user["sid"],
                    'input'      => $user["input"],
                    'proxy_id'   => $user["proxy_id"],
                    'login_time' => $user['last_login_time'],
                    'login_addr' => $user['last_login_addr'],
            );
                
            session('user_auth', $auth);
            session('user_auth_sign', data_auth_sign($auth));
            session('notice', null);
        }
        $return["info"] = "设置成功!";
        $return["callback"] = "";
//         $return["callback"] = "window.open('".U("Home/Index/index")."', '_blank', 'fullscreen=yes');";
        
        $this->ajaxReturn($return);
    }
    
    public function getSub()
    {
        $sub = new FormElement("sub", "select", "所属分站", array("group" => "mid", "form" => "kform",
                 "close_element_div" => 1, "ext" => 'url="'.U("Index/getSub").'"', 
                "list" => parse_select_list("select id,proxy_sub_name from users where type=6", "", "", "请选择分站")));
        echo $sub->fetch();
    }
    
    public function getMember()
    {
        $member = new FormElement("member", "select", "人员选择", array("group" => "mid", "form" => "kform", 
                "close_element_div" => 1, "ext" => 'url="'.U("Index/getMember").'"', 
                "list" => parse_select_list("select id,username from users where type=".I("get.type")." and sid=".I("get.sid"))));
        echo $member->fetch();
    }
    
    private function _parse_usr_data(& $usr)
    {
        $perm_name = array("master", "main", "ast", "proxy", "sub", "fin", "oper", "sales");
        if (!$usr)
        {
            foreach ($perm_name as $key => $name)
            {
                $usr[$key]["login_name"] = $name;
                $usr[$key]["show_pwd"] = 123;
            }
            $usr[1]["proxy"] = 1;
            $usr[1]["assist"] = 1;
            $usr[3]["sub"] = 1;
            $usr[4]["finance"] = 1;
            $usr[4]["operator"] = 1;
            $usr[4]["salesman"] = 1;
            $usr[3]["pos"] = 0;
            $usr[4]["employee"] = 0;
            $usr[4]["customer"] = 0;
            return;
        }
        foreach ($usr as $key => $row)
        {
            $usr[$key]["show_pwd"] = "******";
            if ($row["umax"])
            {
                $umax = explode(",", $row["umax"]);
                foreach ($umax as $val)
                {
                    $v = explode(":", $val);
                    $usr[$key][$v[0]] = $v[1];
                }
            }
        }
//         dump($usr);
    }
    
    private function _bcode($proxy, $type)
    {
        return dechex(mt_rand(1, 15)).SERVER_NUM.$proxy.dechex(mt_rand(1, 15)).
                $type.dechex(mt_rand(1, 15))."001".dechex(mt_rand(1, 15));
    }
    
    public function base_form_handle()
    {
        if (IS_POST)
        {
            if ($_POST["op"] == "edit")
                $add = false;
            else
                $add = true;
            $perm = array(0, 9, 7, 8, 6, 1, 2, 3);
            $username = array("超级管理员", "管理员", "助理", "代理商", "站长", "财务", "操作员", "拓展员");
            $code = array("00LZCARD000", $this->_bcode("0", "09"), $this->_bcode("0", "07"), 
                        $this->_bcode("0", "08"), $this->_bcode("1", "06"), $this->_bcode("1", "11"), 
                        $this->_bcode("1", "12"), $this->_bcode("1", "13"));
            $obj = M("users");
        
//         dump($_POST["name"]);
//         exit(0);
        
            foreach ($_POST["name"] as $key => $name)
            {
                $data["id"] = $_POST["id"][$key];
                $data["login_name"] = $name;
                $data["umax"] = "";
                if ($_POST["show_pwd"][$key] != "******")
                    $data["pwd"] = think_md5($_POST["show_pwd"][$key], UC_AUTH_KEY);
                
                $data["update_time"] = date("Ymdhis");
                
                if ($add)
                {
                    $data["input"] = 0;
                    $data["status"] = 0;
                    $data["type"] = $perm[$key];
                    $data["sid"] = 0;
                    $data["proxy_id"] = 0;
                    $data["proxy_sub_name"] = "";
                    $data["code"] = $code[$key];
                    $data["username"] = $username[$key];
                    $data["create_time"] = date("Ymdhis");
                }
                
                
                if ($key == 1)
                    $data["umax"] = "proxy:".$_POST["umax"]["proxy"].",assist:".$_POST["umax"]["assist"];
                else if ($key == 3)
                {
                    if ($add)
                    {
                        $data["input"] = 1;
                        $data["proxy_sub_name"] = "测试代理商";
                    }
                    $data["umax"] = "pos:".$_POST["umax"]["pos"].",sub:".$_POST["umax"]["sub"];
                }
                else if ($key == 4)
                {
                    $data["umax"] = "finance:".$_POST["umax"]["finance"].
                                    ",operator:".$_POST["umax"]["operator"].
                                    ",salesman:".$_POST["umax"]["salesman"].
                                    ",employee:".$_POST["umax"]["employee"].
                                    ",customer:".$_POST["umax"]["customer"];
                    if ($add)
                    {
                        $data["input"] = 1;
                        $data["proxy_id"] = 4;
                        $data["proxy_sub_name"] = "测试分站";
                    }
                }
                else if ($key > 4 && $add)
                {
                    $data["proxy_id"] = 4;
                    $data["sid"] = 5;
                    $data["proxy_sub_name"] = "测试分站";
                }
                    
                if ($add)
                    $ret = $obj->add($data);
                else
                    $ret = $obj->save($data);
                
                if (!$ret)
                    break;
            }
            
        }
        $return["info"] = $ret ? "设置成功!" : "设置失败:".$obj->getError();
        $return["url"] = U("Index/index");
        $return["echo"] = 1;
        $return["close"] = 0;
        $return["tag"] = "";
        if (!$ret)
            $return["url"] = "";
        
        $this->ajaxReturn($return);
    }
    
//-----------------------------上传数据-code-----------------------------
    public function upData()
    {
//         echo '<form action="'.U("KyoCommon/Index/excelImport").'" method="post" enctype="multipart/form-data">
//                      <input type="file" name="file_input_excel" /><input type="submit" /></form>';
//         exit(0);
//         $auth = array('name' => '上传报表', 'admin' => 10, 'uid' => 9999, 'sid'=> 0, 'code' => '#########', 'proxy_id' => 0);
//         session('user_auth', $auth);
//         session('user_auth_sign', data_auth_sign($auth));
    	$up = new Form("");
        $up->setElement("upgroup", "group", "上传交易数据");
        $up->setElement("input_excel", "file", "交易数据路径", array(
                "element_cols" => 4,
                "label_cols" => 4,
                "accept" => "application/vnd.ms-excel",
                "action" => U("KyoCommon/Index/excelImport"),
        ));
        $up->set("close_btn_down", 1);
        
        //////////////////////////////////////////////////
        
//         $list = new SmallDataList("card_deal", "pos");
//         $list->setPage("size", 10);
//         $list->set("close_num", 1);
//         $list->set("where", "status=0");
//         $list->setTitle(array("上传时间", "上传结果"));
//         $list->setField(array("code", "models"));
        
//         $up->setElement("recordgroup", "group", "上传记录");
//         $up->setElement("recordlist", "static", "", array("sig_row" => 1, 
//                 "close_label" => 1, "element_cols" => 12, "value" => $list->fetch()));
//         $list->set("close_chkall", 1);
        
        $this->assign("body", $up->fetch());
        $this->display("Home@Main/upData");
    }    
}