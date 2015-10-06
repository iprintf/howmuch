<?php
namespace User\Controller;
use Common\Controller\ListPage;
use Common\Controller\FormElement;

class SubstationController extends ListPage
{
    static public function getPop($type, $title = "")
    {
        $pop = "";
        switch ($type)
        {
        	case "add":
                if (!$title)
                    $title = "新增分站";
                $pop = "w:1000,h:550,n:'subadd',t:".$title;
        	    break;
        	case "edit":
                if (!$title)
                    $title = "编辑分站";
                $pop = "w:1000,h:550,n:'subedit',t:".$title;
        	    break;
        	case "info":
                if (!$title)
                    $title = "分站详细信息";
                $pop = "w:1000,h:680,n:'subinfo',t:".$title;
        	    break;
        	default:
        	    break;
        }
        return $pop;
    }

    public function subOp($data, $oplist = array())
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
        
        $txt = "锁定";
        $status = LOCK;
        
        if ($data["status"] == LOCK)
        {
            $txt = '解锁';
            $color = 'kyo_red';
            $status = NORMAL;
        }
        
        $lock = new FormElement("btn_lock", "link", '<span class="'.$color.'">'.$txt.'</span>', array(
                "ext" => 'confirm="确定'.$txt.'吗？" 
                    url ="'.U("User/Index/lock")."&id=".$data["id"]."&status=".$status.'"&sid=1',
        ));
        
        return $ophtml.$lock->fetch();
    }
        
//-----------------------------分站管理-code-----------------------------
    public function index()
    {
    	$this->setNav("&nbsp;->&nbsp;分站&nbsp;->&nbsp;分站管理");
    	$this->mainPage("users");
    	//设置添加按钮弹出窗口
        $this->setTool("tool_btn_down", array("txt" => "新增分站", "icon" => "plus",
        		"url" => U()."&form=add",
        		"pop" => $this->getPop("add")));
    	$this->setFind("typelist proxy_sub_name", array("txt" => "分站名称", "val" => "proxy_sub_name"));//增加搜索选择
    	$this->setFind("typelist username", array("txt" => "站长姓名", "val" => "username"));
    	$this->setFind("typelist code", array("txt" => "站长内部代码", "val" => "code"));
    	//设置批量删除操作
//         $this->setBatch("删除所选", U()."&form=del", array("query" => true,
//         		'icon' => "remove", 'ext' => 'confirm="确定批量删除吗？"'));
        $this->setBatch("重置登录信息", U("User/Index/reset"), array("query" => true, 
                'icon' => "flash", 'ext' => 'confirm="确定重置登录用户名和密码吗？" chktype="1"'));
        $this->setData("chkVal", 1);
		$this->setForm("cols",2);
		//设置表单成员， 添加、编辑和信息的元素  uniq为不得重复
        $this->setElement("proxy_id", "select", "所属代理", array("bool" => "required", 
                "list" => parse_select_list("select id,proxy_sub_name from users where type=8", "", "", "请选择代理商")));
		$this->setElement("identity", "identity", "身份证号码", array("bool" => "required", 
		        "maxlength" => 18, "hint" => "id"));
        
        $this->setElement("proxy_sub_name", "string", "分站名称", array("bool" => "required"));
		$this->setElement("id_img1", "file", "身份证正面", array("bool" => "required"));
        
		$this->setElement("addr", "string", "分站地址", array("bool" => "required"));
		$this->setElement("id_img2", "file", "持证合拍照", array("bool" => "required"));
        
		$this->setElement("username", "string", "站长姓名", array("bool" => "required"));
        $this->setElement("contact", "string", "紧急联系人", array("bool" => "required"));
        
		$this->setElement("phone1", "phone", "联系电话", array("bool" => "required", "hint" => "phone"));
        $this->setElement("phones", "string", "紧急人电话", array("bool" => "required", "hint" => "phone"));
        
        $this->setElement("email", "email", "电子邮箱", array("bool" => "required"));
        
		$this->setElement("remark", "textarea", "备注", array("rows" => 3,"element_cols" => "col-md-9"));
		/*添加隐藏提交字段*/
		$this->setElement("type", "hidden", "", array("value" => "6"));
		//设置表单添加或修改自动完成项  创建时间和更新时间不需要输入自动填写
        $this->setForm("auto", array(
        		array("create_time", 'getcurtime', 1, "function"),//1添加时生效，2修改时生效，3以上都生效
        		array("update_time", 'getcurtime', 3, "function"),
		));
        $this->setForm("handle_run info", "Substation/info");
        $this->setForm("handle_run post", "Substation/subSave");
        $this->setOp("编辑", U()."&form=edit&where='id=[id]'",
        		array("pop" => $this->getPop("edit", "编辑[proxy_sub_name]")));
//         $this->setOp("删除", U()."&form=del&where='id=[id]'",
//         		array("query" => true, "ext" => 'confirm="确定删除吗？"'));
        $this->setData("op_call", array("run", "Substation/subOp"));
        
        $this->setData("where", "type=6");
        $this->setData("order", "update_time desc");
    	$this->setTitle(array("分站名称", "站长内部代码", "站长姓名", "联系电话", "分站创建日期"));
    	$this->setField(array("proxy_sub_name", "code", "username", "phone1", "create_time"));
    	$this->setField("code", array("name" => "1", "url" => U()."&form=info&where='id=[id]'",
    			"pop" => $this->getPop("info", "[proxy_sub_name]详细信息")));
    	$this->display();
    }
    
    public function subSave(& $formObj)
    {
        $form = & $formObj->form;
        $obj = & $form["dataObj"];
        
        if ($_POST["id"])  //修改
        {
            $ret = $obj->auto($form["auto"])->create($_POST, 2);
            $ret = $ret ? $obj->where("id=".$_POST["id"])->save() : $ret;
            M("users")->where("sid=".$_POST["id"])->setField("proxy_sub_name", $_POST["proxy_sub_name"]);
            $form["return"]["info"] = $ret ? "提交成功!" : "输入数据格式有误!";
        }
        else  //添加
        {
            $input = sqlCol("select max(input) from users where type=6 and proxy_id=".$_POST["proxy_id"]);
            if ($input >= 15)
                $this->ajaxReturn(array("echo" => 1, "info" => "此代理商下属分站已满，请另选代理商!"));
            
            $_POST["input"] = $input ? $input + 1 : 1;
            $_POST["umax"] = "finance:0,operator:0,customer:0,salesman:0,employee:0";
            
            $_POST["code"] = build_code($_POST["type"], $_POST["proxy_id"]);
            $_POST["login_name"] = $_POST["code"];
            $_POST["pwd"] = think_md5($_POST["login_name"], UC_AUTH_KEY);
        
            $ret = $obj->auto($form["auto"])->create($_POST, 1);
            if (!$ret)
                $form["return"]["info"] = $obj->getError();
            else
            {
                $ret = $obj->add();
                sqlCol("update users set sid=".$ret." where id=".$ret);
                parse_umax("sub", $_POST["proxy_id"]);
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
    
//-----------------------------分站管理之分站详细信息-info-----------------------------
    public function info($con, $formObj)
    {
    	$obj = & $formObj->form["dataObj"];
    	$data = $obj->where($con)->select();
		$formObj->formDataInfo($data);
    	$formObj->setElement("sub_sum_name", "group", "分站信息统计",array("class" => "text-center"));
        
        $sid = "s".$con;
        $fin = sqlCol("select count(code) from users where type=1 and ".$sid);
        $oper = sqlCol("select count(code) from users where type=2 and ".$sid);
        $sales = sqlCol("select count(code) from users where type=3 and ".$sid);
        $em = sqlCol("select count(code) from users where type=4 and ".$sid);
    	$pos = sqlCol("select count(code) as total from pos where ".$sid);
    	$basis = sqlCol("select count(code) as total from basis where ".$sid);
    	$card = sqlCol("select count(code) as total from card where ".$sid);
        
    	$formObj->setElement("sub_financial_num", "static", "财务主管", array("value" => $fin));
    	$formObj->setElement("sub_pos_num", "static", "POS台数", array("value" => $pos));	
    	$formObj->setElement("sub_operator_num", "static", "操作人员", array("value" => $oper));
    	$formObj->setElement("sub_basis_num", "static", "客户数量", array("value" => $basis));
    	$formObj->setElement("sub_expand_num", "static", "拓展人员", array("value" => $sales));
    	$formObj->setElement("sub_card_num", "static", "卡片数量", array("value" => $card));
    }
}
