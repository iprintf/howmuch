<?php
namespace User\Controller;
use Think\Controller;
use Common\Controller\ListPage;
use Common\Controller\FormElement;

class UsersController extends ListPage
{
    private $admin;

   //构造函数
    public function __construct()
    {
        parent::__construct();
        $this->admin = get_user_info("admin");
    }

    public function index()
    {
        $admin = get_user_info("admin");
        if ($admin == 6)
            $this->manage();
        else
            $this->userList();
    }

    private function manage()
    {
        SalesmanController::commonHead($this, "站内设置&nbsp;->&nbsp;用户管理");
    	//设置添加按钮弹出窗口
        $this->setTool("tool_btn_down", array("txt" => "新增用户", "icon" => "plus",
        		"url" => U()."&form=add",
        		"pop" => "w:550,h:640,n:'payadd',t:添加用户"));

        $this->setBatch("重置登录信息", U("User/Index/reset"), array("query" => true,
                'icon' => "flash", 'ext' => 'confirm="确定重置登录用户名和密码吗？" chktype="1"'));
        $this->setData("chkVal", 1);

        $this->setForm("handle_run show_edit", "Users/usersEdit");
        $this->setForm("handle_run post", "Users/usersSave");
        $this->setForm("name", "userfrom");

    	//设置批量删除操作
		$this->setElement("username", "string", "姓名", array("bool" => "required"));
		$this->setElement("type", "select", "职务", array("bool" => "required",
                "url" => U("Users/getEmail"),
                "tag" => ".sel_email",
		        "list" => parse_select_list("array", array(1, 2), array("财务主管", "操作员"))));
		$this->setElement("sex", "radio", "性别", array("bool" => "required","list" => parse_select_list("array", array(1, 2), array("男", "女"))));
		$this->setElement("phone1", "phone", "联系电话", array("bool" => "required", "hint" => "phone"));
        $this->setElement("identity", "identity", "身份证号", array("bool" => "required", "maxlength" => 18, "hint" => "id"));
		$this->setElement("email", "email", "电子邮箱", array("bool" => "required", "pclass" => "sel_email"));
		$this->setElement("bonus", "select", "服务佣金", array("bool" => "required disabled",
                "addon" => "%", "value" => 0,
		        "list" => parse_select_list("for", array(0.01,0.05,0.01,1,4), "", "零佣金", 0)));
		$this->setElement("award", "select", "增值佣金", array("bool" => "required disabled",
                "addon" => "%", "value" => 0,
		        "list" => parse_select_list("for", array(3,10,1,1), "", "零佣金", 0)));
		$this->setElement("remark", "textarea", "备注", array("rows" => 3,"element_cols" => "col-md-8"));
		/*添加隐藏提交字段*/
		$this->setElement("proxy_id", "hidden", "", array("value" => get_user_info("proxy_id")));
		$this->setElement("sid", "hidden", "", array("value" => get_user_info("sid")));

		//设置表单添加或修改自动完成项  创建时间和更新时间不需要输入自动填写
        $this->setForm("auto", array(
        		array("create_time", 'getcurtime', 1, "function"),//1添加时生效，2修改时生效，3以上都生效
        		array("update_time", 'getcurtime', 3, "function"),
        ));
        $this->setOp("编辑", U("")."&form=edit&where='id=[id]'",
        		array("pop" => "w:550,h:640,n:'bankadd',t:修改用户信息"));

        $this->setData("op_call", array("run", "Salesman/salesOp"));

        $this->setData("where", "(type=1 or type=2) and sid=".get_user_info("sid"));
    	$this->setTitle(array("内部代码", "姓名", "身份证号", "性别", "联系电话", "电子邮箱", "服务佣金", "增值佣金",  "职位", "备注"));
    	$this->setField(array("code", "username", "identity", "sex", "phone1", "email", "bonus_cost", "rising_cost", "type", "remark"));
    	$this->setData("data_field 8 fun", "get_perm_name");
    	$this->display();
    }

    //只有职务才有邮箱录入
    public function getEmail($val)
    {
        $email = new FormElement("email", "email", "", array("close_label" => 1, "close_element_div" => 1,
                     "bool" => "required", "pclass" => "sel_email", "begin" => 0, "over" => 0,
                    "placeholder" => "请输入电子邮箱", "form" => "userfrom"));

        if ($val == 1)
        {
            $email->_set("bool", "required");
            $disabled = "true";
        }
        else
        {
//             $email->_set("bool", "disabled");
            $disabled = "false";
        }

        $js = '$("#bonus_id").prop("disabled", '.$disabled.');';
        $js .= '$("#award_id").prop("disabled", '.$disabled.');';

        echo js_head($js).$email->fetch();
    }

    public function usersSave(& $formObj)
    {
        $form = & $formObj->form;
        $obj = & $form["dataObj"];

        $_POST["award"] /= 100;
        $_POST["bonus"] /= 100;
        $_POST["proxy_sub_name"] = get_sub_name($_POST["sid"]);

        if ($_POST["id"])  //修改
        {
            $ret = $obj->auto($form["auto"])->create($_POST, 2);
            $ret = $ret ? $obj->where("id=".$_POST["id"])->save() : $ret;
            $form["return"]["info"] = $ret ? "提交成功!" : "输入数据格式有误!";
        }
        else  //添加
        {
            $_POST["code"] = build_code($_POST["type"], $_POST["sid"]);
            $_POST["login_name"] = $_POST["code"];
            $_POST["pwd"] = think_md5($_POST["login_name"], UC_AUTH_KEY);

            $ret = $obj->auto($form["auto"])->create($_POST, 1);
            if (!$ret)
                $form["return"]["info"] = $obj->getError();
            else
            {
                $ret = $obj->add();
                if ($_POST["type"] == 1)
                    parse_umax("finance", $_POST["sid"]);
                else if ($_POST["type"] == 2)
                    parse_umax("operator", $_POST["sid"]);
                $form["return"]["info"] = $ret ? "提交成功!" : "输入数据格式有误!";
            }
        }

        if (!$ret)
        {
            $form["return"]["close"] = 0;
            $form["return"]["url"] = "";
        }

        $this->ajaxReturn($form["return"]);
    }


    public function usersEdit($con, & $formObj)
    {
        $form = & $formObj->form;
        $el = & $form["element"];
        $obj = & $form["dataObj"];

        $data = $obj->where($con)->select();
        $usr = $data[0];
        $formObj->formDataShowEdit($data);
        if ($usr["type"] == 2)
        {
//             $el["email"]["bool"] = "disabled";
            $el["bonus"]["bool"] = "";
            $el["award"]["bool"] = "";
        }
        $el["bonus"]["value"] = $usr["bonus"] * 100;
        $el["award"]["value"] = $usr["award"] * 100;
        $el["type"]["type"] = "static";
        $el["type"]["value"] = get_perm_name($usr["type"]);
    }

    private function userList()
    {
        SalesmanController::commonHead($this, "分站&nbsp;->&nbsp;用户列表");
        $this->setFind("item 0", array("name" => "sid", "type" => "select", "default" => "所有分站",
                "list" => parse_select_list("select id,proxy_sub_name from users where type=6")));
    	//设置添加按钮弹出窗口
        $this->setData("close_op", 1);
        $this->setData("close_chkall", 1);
        $this->setData("where", "type=1 or type=2");
    	$this->setTitle(array("内部代码", "姓名", "身份证号", "性别", "联系电话", "电子邮箱", "服务佣金", "增值佣金",  "职位", "所属分站", "备注"));
    	$this->setField(array("code", "username", "identity", "sex", "phone1", "email", "bonus_cost", "rising_cost", "type", "proxy_sub_name", "remark"));
    	$this->setData("data_field 8 fun", "get_perm_name");
    	$this->display();
    }


    //用户管理
    public function kyoUser()
    {
        if ($this->admin != 0 && $this->admin != 9)
            $this->redirect("Home/Index/index");

        $this->kyoUserFindOption();

        $this->setNav("&nbsp;->&nbsp;"."系统用户管理");
        $this->mainPage("users");

        $this->setFind("item 0", array("name" => "sid", "type" => "select", "default" => "所有分站",
                "list" => parse_select_list("select id,proxy_sub_name from users where type=6")));
       $this->setFind("item 1", array("name" => "status", "type" => "select",
            "default" => "所有状态", "defval" => 0,
            "list" => parse_select_list("array", array(-1, -2, LOCK),
                    array("在线", "离线", "锁定"))));
       $this->setFind("item 2", array("name" => "type", "type" => "select",
            "default" => "所有分组", "defval" => 0,
            "list" => parse_select_list("array", array(6, 1, 3, 2, 8, 9, 7),
                    array("站长", "财务", "拓展员", "操作员", "代理商", "主站", "助理"))));
        $this->setFind("item 3", array("name" => "search_type", "type" => "select",
                "default" => "内部代码", "defval" => "code",
                "list" => parse_select_list("array", array("username", "last_login_addr"),
                        array("用户姓名", "登录地点"))));

        $batch_end = '</li><li class="divider">';
        $this->setBatch("全部用户锁定", U("User/Users/kyoUserBatchOp")."&name=lock&op=all", array(
                "query" => true, "end" => $batch_end, "name" => "lock", 'icon' => "lock",
                'ext' => 'confirm="确定对所有用户进行锁定吗？" chktype="1"'));
        $this->setBatch("全部用户解锁", U("User/Users/kyoUserBatchOp")."&name=unlock&op=all", array(
                "query" => true,  "end" => $batch_end, "name" => "unlock", 'icon' => "compressed",
                'ext' => 'confirm="确定对所有用户进行解锁吗？" chktype="1"'));
        $this->setBatch("全部重置登录", U("User/Users/kyoUserBatchOp")."&name=reset&op=all", array(
                "query" => true,  "end" => $batch_end, "name" => "reset", 'icon' => "flash",
                'ext' => 'confirm="确定重置所有用户的登录用户名和密码吗？" chktype="1"'));
        $this->setBatch("全部强制下线", U("User/Users/kyoUserBatchOp")."&name=down&op=all", array(
                "query" => true,  "end" => $batch_end, "name" => "down", 'icon' => "fire",
                'ext' => 'confirm="确定将所有用户进行强制下线吗？" chktype="1"'));
        $this->setBatch("批量用户锁定", U("User/Users/kyoUserBatchOp")."&name=lock", array(
                "query" => true,  "end" => $batch_end, 'icon' => "lock",
                'ext' => 'confirm="确定对所选用户进行锁定吗？" chktype="1"'));
        $this->setBatch("批量用户解锁", U("User/Users/kyoUserBatchOp")."&name=unlock", array(
                "query" => true,  "end" => $batch_end, 'icon' => "compressed",
                'ext' => 'confirm="确定对所选用户进行解锁吗" chktype="1"'));
        $this->setBatch("批量重置登录", U("User/Index/reset"), array("query" => true,  "end" => $batch_end,
                'icon' => "flash", 'ext' => 'confirm="确定对所选用户重置登录用户名和密码吗？" chktype="1"'));
        $this->setBatch("批量强制下线", U("User/Users/kyoUserBatchOp")."&name=down", array(
                "query" => true, 'icon' => "fire", 'ext' => 'confirm="确定对所选用户进行强制下线吗？" chktype="1"'));

        if ($this->admin == 0)
            $this->setData("where", "1 ".session("kyoUser_findstatus"));
        else
            $this->setData("where", "type<>0 ".session("kyoUser_findstatus"));

        $this->setData("order", "last_login_time desc");
        $this->setData("close_op", 1);
        $this->setData("excel", array("name" => "全部操作使用"));
        $this->setData("tooltip", 1);
    	$this->setTitle(array("状态", "内部代码", "用户姓名", "用户职位", "所属分站", "所属代理商", "最后登录地点", "最后登录时间"));
    	$this->setField(array("status", "code", "username", "type", "sid", "proxy_id", "last_login_addr", "last_login_time"));
        $this->setField("code", array("name" => 1, "url" => U("Users/kyoUserInfo")."&id=[id]",
                "pop" => "w:1000,h:1000,n:'useroprecord't:[username] 操作记录"));
        $this->setData("formatfield", "Users/kyoUserField");

    	$this->display();
    }

    //用户管理用户操作日志查看
    public function kyoUserInfo($id)
    {
        $record = sqlAll("select op_type,op_time,remark,op_addr from users_record
                            where opid=".$id." order by op_time desc");
        $record_txt = C("RECORD_TEXT");

        foreach ($record as $r)
        {
            $html .= '<table class="table table-bordered text-left" style="border:2px #999 solid;">';
            $html .= '    <tr>';
            $html .= '        <td class="active text-right col-xs-1">操作类型:</td>';
            $html .= '        <td class="col-xs-3">'.$record_txt[$r["op_type"]].'</td>';
            $html .= '        <td class="active text-right col-xs-1">操作地点:</td>';
            $html .= '        <td class="col-xs-3">'.$r["op_addr"].'</td>';
            $html .= '        <td class="active text-right col-xs-1">操作时间:</td>';
            $html .= '        <td class="">'.$r["op_time"].'</td>';
            $html .= '    </tr>';
            $html .= '    <tr>';
            $html .= '        <td class="active text-right">操作说明:</td>';
            $html .= '        <td colspan="5" style="height:50px">'.$r["remark"].'</td>';
            $html .= '    </tr>';
            $html .= '</table>';
        }
        echo $html;
    }

    //用户管理查询选项设置
    public function kyoUserFindOption()
    {
        if (!I("get."))
            session("kyoUser_findstatus", null);

        if (I("get.find") && IS_POST)
        {
            if (I("post.status") < 0)
            {
                if ($_POST["status"] == -2)
                    session("kyoUser_findstatus", " and status=0 and session_id is NULL ");
                else
                {
                    session("kyoUser_findstatus", null);
                    $_POST["session_id"] = array("neq", "");
                }
                unset($_POST["status"]);
            }
            else
                session("kyoUser_findstatus", null);
        }
    }

    //用户管理批量操作
    public function kyoUserBatchOp($name = "", $op = "", $where = "")
    {
        if ($op == "all")
        {
            $all = sqlAll(str_replace("*", "id ", $_SESSION["excel"]["sql"]));
            if (!$all)
                $this->ajaxReturn(array("echo" => 1, "info" => "操作出现异常，请刷新重新操作!"));
            $con = "id in (";
            foreach ($all as $data)
            {
                $con .= $data["id"].",";
            }
            $con = rtrim($con, ",").")";
        }
        else
            $con = substr($where, 1, strlen($where) - 2);

        switch ($name)
        {
        	case "lock":
                sqlCol("update users set status=".LOCK." where status=0 and ".$con);
        	    break;
        	case "unlock":
                sqlCol("update users set status=0 where status=".LOCK." and ".$con);
                sqlCol("delete from users where op_type=".USR_ERR_PWD." and DATE_FORMAT(op_time,'%Y-%m-%d')='".date("Y-m-d")."'
                         and opid in (select id from users where ".$con.")");
        	    break;
        	case "down":
                sqlCol("delete from kyo_session where session_id in (select session_id from users where ".$con.")");
                sqlCol("update users set session_id=null where ".$con);
        	    break;
        	case "reset":
                R("User/Index/reset", array($con));
        	    break;
        }
        $this->ajaxReturn(array("echo" => 1, "info" => "操作成功!",
                   "url" => session("prev_urlusers"), "tag" => ".users"));
    }

    public function kyoUserField($data, $key)
    {
        $val = "";
        switch ($key)
        {
        	case "status":
                if ($data["session_id"])
                    $val = "在线";
                else
                    $val = "离线";
                if ($data[$key] == LOCK)
                    $val = "锁定";
        	    break;
        	case "proxy_id":
                $val = (!$data[$key]) ? "无所属" : sqlCol("select proxy_sub_name from users where id=".$data[$key]);
                break;
        	case "sid":
                $val = (!$data[$key]) ? "无所属" : sqlCol("select proxy_sub_name from users where id=".$data[$key]);
        	    break;
        	case "type":
                $perm_name = C("PERM_NAME");
                $val = $perm_name[$data[$key]];
                break;
        	case "last_login_addr":
                if ($data[$key] && $data["last_ip"])
                    $val = $data[$key];
                else
                    $val = "从来没有登录过";
        	    break;
        	case "last_login_time":
                if ($data[$key])
                    $val = $data[$key];
                else
                    $val = "从来没有登录过";
        	    break;
        	default:
                $val = $data[$key];
        	    break;
        }
        return $val;
    }
}


