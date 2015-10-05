<?php
namespace User\Controller;
use Think\Controller;
use Common\Controller\Form;
use Common\Controller\ListPage;
use Common\Controller\FormElement;

class SalesmanController extends ListPage 
{
    private $admin;
    private $sid;
    
    public function __construct()
    {
        parent::__construct(); 
        if (!IS_AJAX)
            header("Location:".ERROR_URL);
        $this->admin = get_user_info("admin");
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
                    $title = "新增拓展员";
        		$pop = "w:950,h:670,n:'salesadd',t:".$title;
        	    break;
        	case "edit":
                if (!$title)
                    $title = "编辑拓展员";
        		$pop = "w:950,h:670,c:1,n:'salesedit',t:".$title;
        	    break;
        	case "info":
                if (!$title)
                    $title = "拓展员详细信息";
                $pop = "w:950,h:750,n:'salesinfo',t:".$title;
        	    break;
        	default:
        	    break;
        }
        return $pop;
    }
    
    public function index()
    {
        switch ($this->admin)
        {
        	case 6:
                $this->commonManage();
        	    break;
        	default:
                $this->salesList();
        	    break;
        }
        $this->display();
    }
    
    static public function commonHead($obj, $title = "站内设置&nbsp;->&nbsp;拓展员管理")
    {
        $obj->setNav("&nbsp;->&nbsp;".$title);
        $obj->mainPage("users");
        
        $obj->setFind("item 0", array());
        $obj->setFind("item 1", array("name" => "search_type", "type" => "select", 
                "default" => "姓名", "defval" => "username", 
                "list" => parse_select_list("array", array("phone1", "code"), 
                        array("电话号码", "内部代码" ))));
    }    
    
    public function salesOp($data, $oplist = array())
    {
        $ophtml = "";
        $index = 1;
        foreach ($oplist as $op)
        {
            parse_link($op["url"], $data);
            $opObj = new FormElement($index++, "link", $op["txt"], $op);
            $ophtml .= $opObj->fetch()."&emsp;";
        }
        
        $txt = "锁定";
        $status = LOCK;
        
        if ($data["status"] == LOCK)
        {
            $txt = "解锁";
            $status = NORMAL;
        }
        
        $lock = new FormElement("btn_lock", "link", $txt, array(
                "ext" => 'confirm="确定'.$txt.'吗？" 
                    url ="'.U("User/Index/lock")."&id=".$data["id"]."&status=".$status.'"',
        ));
        
        return $ophtml.$lock->fetch();
    }
    
    //获取拓展员签约卡总额度
    public function cardAmount($data, $txt)
    {
        return sqlCol("select sum(amount) from card where (status=0 or status>5) and eid=".$data["id"]);
    }
    
//-----------------------------拓展员管理-code-----------------------------
    public function commonManage($display = false, $title = "站内设置&nbsp;->&nbsp;拓展员管理")
    {
        $this->commonHead($this, $title);
		//设置添加按钮弹出窗口
        $this->setTool("tool_btn_down", array("txt" => "新增拓展员", "icon" => "plus",
        		"url" => U()."&form=add",
        		"pop" => $this->getPop("add")));
    	//设置批量删除操作
        $this->setBatch("重置登录信息", U("User/Index/reset"), array("query" => true, 
                'icon' => "flash", 'ext' => 'confirm="确定重置登录用户名和密码吗？" chktype="1"'));
        $this->setData("chkVal", 1);
        
		$this->setForm("cols",2);
        $this->setForm("name", "salesform");
        $this->setForm("handle_run post", "Salesman/salesSave");
        $this->setForm("handle_run show_edit", "Salesman/salesEdit");
        
		$this->setElement("username", "string", "姓名", array("bool" => "required"));
		$this->setElement("signing", "select", "签单佣金", array("bool" => "required",
                "addon" => "%", "value" => 0,
		        "list" => parse_select_list("for", array(2,8,1,1), "", "零佣金", 0)));	
		$this->setElement("sex", "radio", "性别", array("bool" => "required","list" => parse_select_list("array", array(1, 2), array("男", "女"))));
		$this->setElement("bonus", "autocomplete", "服务佣金", array("bool" => "required",
                "addon" => "%", 
                "list" => parse_autocomplete(array("for", 0.00, 0.41, 0.01,4))));
// 		        "list" => parse_select_list("for", array(0.03,0.08,0.01,1,4), "", "零佣金", 0)));
		$this->setElement("identity", "identity", "身份证号", array("bool" => "required", "maxlength" => 18, "hint" => "id"));
		$this->setElement("award", "select", "增值佣金", array("bool" => "required",
                "addon" => "%", "value" => 0,
		        "list" => parse_select_list("for", array(30,65,5,2), "", "零佣金", 0)));
		$this->setElement("id_img1", "file", "持证合照", array("bool" => "required"));
		$this->setElement("renewal_cost", "select", "续单佣金", array("bool" => "required",
                "addon" => "%", "value" => 0,
		        "list" => parse_select_list("for", array(1,6,1,1), "", "零佣金", 0)));
		$this->setElement("phone1", "phone", "联系电话", array("bool" => "required", "hint" => "phone"));
		$this->setElement("signing_min", "select", "最低服务签约费率", array("bool" => "required",
                "addon" => "%",
		        "list" => parse_select_list("for", array(1.2,1.7,0.1,3), "", "最低服务签约费率")));
		$this->setElement("input", "radio", "开通录件", array("bool" => "required","list" => parse_select_list("array", array(1,2), array("是", "否"))));
		$this->setElement("award_min", "select", "最低增值签约费率", array("bool" => "required",
                "addon" => "%",
		        "list" => parse_select_list("for", array(3,20,1,2), "", "最低增值签约费率")));
	 	$this->setElement("expand_users_name", "group", "结算账户信息",array("class" => "text-center"));
        $this->setElement("bank", "combobox", "银行名称", array("bool" => "required", 
                    "ext" => 'jbox="450,210,.bank_addr_label" 
                            url="'.U("KyoCommon/Index/showBankBranchSel").'&form=salesform"',
                    "list" => parse_autocomplete("select name from bank order by sort_id")));        
		$this->setElement("card_name", "string", "开户姓名", array("bool" => "required"));
		
        $this->setElement("bank_addr", "string", "开户支行", array("bool" => "required readonly", 
                "pclass" => "sel_bank_addr", "lclass" => "bank_addr_label"));        
		$this->setElement("card", "string", "银行账号", array("bool" => "required", "hint" => "card", "min" => 16,
				"maxlength" => 20));
		$this->setElement("card_img", "file", "借记卡照", array("bool" => "required"));		
		/*添加隐藏提交字段*/
		$this->setElement("proxy_id", "hidden", "", array("value" => get_user_info("proxy_id")));
		$this->setElement("sid", "hidden", "", array("value" => get_user_info("sid")));
		//设置表单添加或修改自动完成项  创建时间和更新时间不需要输入自动填写
        $this->setForm("auto", array(
        		array("create_time", 'getcurtime', 1, "function"),//1添加时生效，2修改时生效，3以上都生效
        		array("update_time", 'getcurtime', 3, "function"),	
				array("type", "3"),
        ));
		$this->setForm("handle_run info", "Salesman/info");
        
        $this->setOp("编辑", U()."&form=edit&where='id=[id]'",
                array("pop" => $this->getPop("edit")));
        $this->setData("op_call", array("run", "Salesman/salesOp"));
        
        $this->setData("where", "type=3 and sid=".get_user_info("sid"));
    	$this->setTitle(array("内部代码", "拓展员", "拓展员电话", "签约客户", "签约卡片", "签约额度", "签约佣金", "服务佣金", "增值佣金", "续单佣金", "录件功能"));
    	$this->setField(array("code", "username", "phone1", "basis_num", "card_num", "card_amount", "signing", "bonus", "award", "renewal_cost", "input"));
        $this->setData("data_field 5 run", "Salesman/cardAmount");
		$this->setField("username", array("name" => "1", "url" => U()."&form=info&where='id=[id]'",
    			"pop" => $this->getPop("info")));
        
        if ($display)
        	$this->display();
    }
    
    public function salesSave(& $formObj)
    {
        $form = & $formObj->form;
        $obj = & $form["dataObj"];
        
        if ($_POST["username"] != $_POST["card_name"])
            $this->ajaxReturn(array("echo" => 1, "info" => "输入的开户姓名必需与拓展员姓名一致!"));
        
        if ($_POST["renewal_cost"] != 0 && $_POST["renewal_cost"] >= $_POST["signing"])
            $this->ajaxReturn(array("echo" => 1, "info" => "选择的续单佣金不得大于等于签单佣金!"));
        
//         if ($_POST["award_min"] <= $_POST["award"])
//             $this->ajaxReturn(array("echo" => 1, "info" => "输入的最低签单佣金或最低增值佣金不正确!"));
        
        $_POST["signing"] /= 100;
        $_POST["renewal_cost"] /= 100;
        $_POST["signing_min"] /= 100;
        $_POST["award"] /= 100;
        $_POST["award_min"] /= 100;
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
            $_POST["code"] = build_code(3, $_POST["sid"]);
            $_POST["login_name"] = $_POST["code"];
            $_POST["pwd"] = think_md5($_POST["login_name"], UC_AUTH_KEY);
        
            $ret = $obj->auto($form["auto"])->create($_POST, 1);
            if (!$ret)
                $form["return"]["info"] = $obj->getError();
            else
            {
                $ret = $obj->add();
                parse_umax("salesman", $this->sid);
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
    
    public function salesEdit($con, & $formObj)
    {
        $form = & $formObj->form;
        $el = & $form["element"];
        $obj = & $form["dataObj"];
        
        $data = $obj->where($con)->select();
        $card = $data[0];
        $formObj->formDataShowEdit($data);
        
        $el["signing_min"]["value"] *= 100;
        $el["renewal_cost"]["value"] *= 100;
        $el["signing"]["value"] *= 100;
        $el["award_min"]["value"] *= 100;
        $el["bonus"]["input_val"] *= 100;
        $el["bonus"]["value"] *= 100;
        $el["award"]["value"] *= 100;
    }
    
    public function getAmount($data, $txt = "")
    {
        $data["status"] = get_status_txt($data["status"]);
        return sqlCol("select sum(amount) from card where status=0 and eid=".$data["id"]);
    }
    
    public function salesList()
    {
        if ($this->admin == 1)
            $this->commonManage(false, "拓展员列表");
        else 
            $this->commonManage(false, "分站&nbsp;->&nbsp;拓展员列表");
        $this->setTool("close_btn_down", 1);
        $this->setTool("close_batch", 1);
        $this->setFind("item 0", array("name" => "sid", "type" => "select", "default" => "所有分站",
                "list" => parse_select_list("select id,proxy_sub_name from users where type=6")));
        if ($this->admin == 1)
        	$this->setData("where","type=3 and sid=".get_user_info("sid"));
        else
        	$this->setData("where","type=3");
		$this->setData("close_op", 1);
		$this->setData("close_chkall", 1);
        $this->setData("data_title", array());
        $this->setData("data_field", array());
    	$this->setTitle(array("内部代码", "姓名", "联系电话", "签约客户数", "签约卡片数", "签约总额度", "所属分站"));
    	$this->setField(array("code", "username", "phone1", "basis_num", "card_num", "amount", "proxy_sub_name"));
		$this->setField("username", array("name" => "1", "url" => U()."&form=info&where='id=[id]'",
    			"pop" => $this->getPop("info")));
        $this->setData("data_field 5 run", "Salesman/getAmount");
        if ($this->admin == 1)
        {
            $this->setData("data_title 6 hide", true);
            $this->setData("data_field 6 hide", true);
        }
    }
    
//-----------------------------拓展员管理之拓展员详细信息-info-----------------------------
    public function info($con, $formObj)
    {
        $el = & $formObj->form["element"];
    	$obj = & $formObj->form["dataObj"];
    	$data = $obj->where($con)->select();
        $sales = $data[0];
        
    	$formObj->formDataInfo($data, $ext);
        $el["id_img1"]["cat_title"] = $sales["username"]."&emsp;".
                        format_dis_field($sales["identity"], array(6, 8, 4));
        $el["card_img"]["cat_title"] = $sales["bank"]."&emsp;".format_dis_field($sales["card"]);
        
		$formObj->setElement("sub_expand_num", "group", "签约客户信息",array("class" => "text-center"));
    	$formObj->setElement("expand_basis_num", "static", "客户个数", array("value" => $sales["basis_num"]));
		$formObj->setElement("expand_card_num", "static", "卡片个数", array("value" => $sales["card_num"]));
        $el["bonus"]["value"] = field_conv_per($sales["bonus"]);
        $el["renewal_cost"]["value"] = field_conv_per($sales["renewal_cost"]);
        $el["signing_min"]["value"] = field_conv_per($sales["signing_min"]);
        $el["award_min"]["value"] = field_conv_per($sales["award_min"]);
        $el["award"]["value"] = field_conv_per($sales["award"]);
        $el["signing"]["value"] = field_conv_per($sales["signing"]);
        $el["input"]["value"] = get_bool_txt($sales["input"]);
    }
}