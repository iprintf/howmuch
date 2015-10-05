<?php
namespace Card\Controller;
use Common\Controller\ListPage;
use Common\Controller\SmallDataList;
use Common\Controller\Form;
use Common\Controller\FormElement;

class BasisController extends ListPage
{
    private $admin;
    private $sid;
    private $uid;
    private $input;
    
    private $el_items = array();
    private $sel_items = array("name", "company", "sex", "addr", 
                "identity",
                "contact", 
                "phone1", 
                "phones", 
                "id_img",
                "bank_group",
                "bank", 
                "card_name", 
                "bank_addr", 
                "card", 
                "card_img", 
                "remark"
    );
    private $sel_info = array("info_code", "info_status", "name", "company", 
                "sex", "addr", "phone1", "contact",  "identity", "phones", "remark", "basis_card_info"
    );
    
    private $mel_info = array("info_code", 
            "info_status",
            "name",
            "eid", 
            "sex",
            "company",
            "identity",
            "addr",
            "id_img",
            "contact", 
            "phone1", 
            "phones", 
            "bank_group",
            "bank", 
            "card_name", 
            "bank_addr", 
            "card", 
            "remark",
            "img"
    );
    
    //构造函数
    public function __construct()
    {
        parent::__construct();
        if (!IS_AJAX)
            header("Location:".ERROR_URL);
        $this->admin = get_user_info("admin");
        $this->sid = get_user_info("sid");
        $this->uid = get_user_info("uid");
        $this->input = get_user_info("input");
    }
    
    //获取客户相关弹窗口信息
    static public function getPop($type, $title = "")
    {
        $pop = "";
        switch ($type)
        {
        	case "add":
                if (!$title)
                    $title = "添加客户";
                $pop = "w:1000,h:750,n:'basisadd',t:".$title;
        	    break;
        	case "edit":
                if (!$title)
                    $title = "编辑客户资料";
                $pop = "w:1000,h:750,c:1,n:'basisedit',t:".$title;
        	    break;
        	case "info":
                if (!$title)
                    $title = "客户详细信息";
                    $pop = "w:1000,h:1000,n:'basisinfo',t:".$title;
        	    break;
        	case "salesinfo":
                if (!$title)
                    $title = "客户详细信息";
                    $pop = "w:1000,h:750,n:'basisinfo',t:".$title;
        	    break;
        	case "auditinfo":
                if (!$title)
                    $title = "客户审核详细信息";
                $pop = "w:1000,h:860,n:'basisauditinfo',t:".$title;
        	    break;
        	case "audit":
                if (!$title)
                    $title = "客户审核";
                $pop = "w:480,h:360,c:1,n:'basisaudit',t:".$title;
        	    break;
        	default:
        	    break;
        }
        return $pop;
    }

    static public function commonHead($obj, $title = "客户管理&nbsp;->&nbsp;客户")
    {
        //预处理特殊查询选项
        if (I("get.find") && IS_POST)
        {
            if ($_POST["status"] == -1)
                $_POST["status"] = 0;
            findIn("username", "eid", "users");
//             dump($_POST);
        }
        
        $obj->setNav("&nbsp;->&nbsp;".$title);
        $obj->mainPage("basis");
        
        $obj->setFind("item 0", array());
        $obj->setFind("item 1", array("name" => "status", "type" => "select",
                "default" => "所有状态", "defval" => 0,
                "list" => parse_select_list("array", array(-1, DRAFT, AUDIT, NO_PASS), 
                        array("正常状", "草稿件", "待终审", "不通过"))));
        $obj->setFind("item 2", array("name" => "search_type", "type" => "select", 
                "default" => "客户姓名", "defval" => "name", 
                "list" => parse_select_list("array", array("username", "phone1", "code"), 
                        array("拓展员", "客户电话", "内部代码" ))));
    }
    
//     //站长导出报表
//     public function excelExport()
//     {
//         $title = array("name" => "姓名", "sex" => "性别", "identity" => "身份证号 ", 
//                 "phone1" => "联系电话", "company" => "工作单位", "card_num" => "信用卡数量", "amount_num" => "信用卡总额度");
//         $sql = "select name, (case sex when 1 then '男' when 2 then '女' end) as sex,
//                             identity,phone1,company,card_num,amount_num from basis where sid=".$this->sid;
//         $excel = new \Common\Controller\KyoMail();
//         $this->ajaxReturn(array("echo" => 1, "info" => $excel->export("站内所有客户信息报表", $title, $sql)));
//     }    
    
    //通用客户管理
    public function commonManage($title = "客户管理&nbsp;->&nbsp;客户")
    {
        $this->commonHead($this, $title);
        
        //设置添加按钮弹出窗口
        if ($this->admin != 6)
            $this->setTool("tool_btn_down", array("txt" => "新增客户", "icon" => "plus",
                    "url" => U()."&form=add",
                    "pop" => $this->getPop("add")));
        
        if ($this->admin == 6 || $this->admin == 0 || $this->admin == 9 || $this->admin == 7)
        {
            $excel["title"] = array("status" => "状态", "name" => "姓名", "sex" => "性别", "identity" => "身份证号 ", 
                    "phone1" => "联系电话", "company" => "工作单位", "card_num" => "信用卡数量", "amount_num" => "信用卡总额度");
            $excel["name"] = "站内所有客户信息报表";
            if ($this->admin == 6)
                $this->setBatch("全部导出", U("KyoCommon/Index/excelExport"), array("query" => true, 
                        "name" => "excel", 'icon' => "cloud-download"));
            else
                $this->setBatch("全部导出", U("KyoCommon/Index/excelExport"), array("query" => true, 
                        "name" => "excel", "bool" => "kopen", 'icon' => "cloud-download"));
        }
        
        $this->setForm("cols",2);
        $this->setForm("name", "basisform");
        
        $this->setForm("handle_run post", "Basis/basisHandle");
        $this->setForm("handle_run show_edit", "Basis/basisEdit");
        $this->setForm("handle_run del", "Basis/basisDel");
        $this->setForm("handle_run info", "Basis/basisInfo");
        //设置表单成员， 添加、编辑和信息的元素  uniq为不得重复
        $this->setElement("basis_start", "hidden", "");
        
        $this->setElement("name", "string", "客户姓名", array("bool" => "required"));
        $this->setElement("eid", "autocomplete", "所属拓展员", array("bool" => "required", 
                "list" => parse_autocomplete("select username,id from users where type=3 and sid=".$this->sid)));
        
        $this->setElement("sex", "radio", "性别", array("bool" => "required",
                    "list" => parse_select_list("array", array(1, 2), array("男", "女"))));
        $this->setElement("company", "string", "工作单位", array("bool" => "required"));
        
        $this->setElement("identity", "identity", "身份证号", array("bool" => "uniq required", "hint" => "id"));
        $this->setElement("addr", "string", "单位地址", array("bool" => "required", "maxlength" => 15));
        
        $this->setElement("id_img", "file", "身份证照", array("bool" => "required", "cat_title" => "身份证照信息"));
        $this->setElement("contact", "string", "紧急联系人", array("bool" => "required"));
        
        $this->setElement("phone1", "phone", "联系电话", array("bool" => "required", "hint" => "phone"));
        $this->setElement("phones", "phone", "紧急人电话", array("bool" => "required", "hint" => "phone"));
        
        $this->setElement("bank_group", "group", "借记卡信息");
        $this->setElement("bank", "combobox", "银行名称", array("bool" => "required", 
                    "ext" => 'jbox="450,210,.bank_addr_label" 
                            url="'.U("KyoCommon/Index/showBankBranchSel").'&form=basisform"',
                    "list" => parse_autocomplete("select name from bank order by sort_id")));        
        $this->setElement("card_name", "string", "开户姓名", array("bool" => "required"));
        $this->setElement("bank_addr", "string", "开户支行", array("bool" => "required readonly", 
                "pclass" => "sel_bank_addr", "lclass" => "bank_addr_label"));        
        $this->setElement("card", "string", "银行卡号", array("hint" => "card", "bool" => "required", "min" => 16,
                "maxlength" => 20));
        $this->setElement("card_img", "file", "借记卡照", array("bool" => "required", "cat_title" => "借记卡照信息"));
        
        $this->setElement("remark", "textarea", "备注事项", array("rows" => 3,"element_cols" => "col-md-9"));
        
        //添加卡片完成后对此修改用
        $this->setElement("btnID", "hidden", "");
        $this->setElement("id", "hidden", "", array("id" => "basis_id"));
        $this->setElement("status", "hidden", "", array("id" => "basis_status"));
        $this->setElement("card_num", "hidden", "", array("id" => "basis_cardnum"));
        
        //设置表单添加或修改自动完成项  创建时间和更新时间不需要输入自动填写
        $this->setForm("auto", array(
                array("proxy_id", get_user_info("proxy_id")),
                array("sid", $this->sid),
                array("create_time", 'getcurtime', 1, "function"),//1添加时生效，2修改时生效，3以上都生效
                array("update_time", 'getcurtime', 3, "function"),
        ));
        
        //保存草稿，将客户资料以草稿状态保存到数据库，关闭客户添加窗口, 提示保存草稿成功
        $this->setForm("btn 0", array("txt" => "保存草稿", "end" => "&nbsp;&nbsp;", 
                 "bool" => "me", "ext" => 'id="basis_save_draft" type="button"',
        ));
        //将客户资料以初审状态保存到数据库中，关闭客户添加窗口，提示保存成功，如果此客户没有添加卡片，则提示没有添加卡片，访问是否要添加 
        //如果是则弹出添加卡片窗口，如果否，则提示访问是否继续添加客户
        //如果客户有添加卡片并且卡片资料不完善，则不允许添加
        $this->setForm("btn 1", array("txt" => "确定提交", 
                "ext" => 'id="basis_submit" type="submit"'));
        
        $this->setForm("js", "kyo_basis");
        
        $this->setData("close_chkall", 1);
        $this->setData("close_op", 1);
        $this->setTitle(array("状态", "内部代码", "客户姓名", "客户电话", "签约日期", "签约卡数", "签约总额", "所属拓展员", "录入时间"));
        $this->setField(array("name", "code", "name", "phone1", "times", "card_num", "amount_num", "eid", "create_time"));
        $this->setData("where", "sid=".$this->sid);
        $this->setData("excel", $excel);
        $this->setData("order", "update_time desc");
        $this->setData("data_field 0 run", "KyoCommon/Index/statusLink");
        $this->setField("code", array("name" => 1, "url" => U()."&form=info&where='id=[id]'",
                "pop" => $this->getPop("info")));
    }
    
    public function index()
    {
        switch ($this->admin)
        {
        	case 3:
        	case 4:
                $this->salesman();
        	    break;
        	case 9:
        	case 7:
        	case 0:
                $this->main();
        	    break;
        	case 6:
        	case 1:
        	default:
                $this->commonManage(); 
        	    break;
        }
        $this->display();
    }
    
    public function main()
    {
        $this->commonManage("分站&nbsp;->&nbsp;客户列表"); 
        $this->setTool("close_btn_down", 1);
        $this->setFind("item 0", array("name" => "sid", "type" => "select", "default" => "所有分站",
                "list" => parse_select_list("select id,proxy_sub_name from users where type=6")));
        $this->setData("where", "1");
        $this->setData("data_title 8 txt", "所属分站");
        $this->setData("data_field 8 txt", "sid");
        $this->setData("data_title 9 txt", "录入时间");
        $this->setData("data_field 9 txt", "create_time");
    }
    
    public function salesman()
    {
        $this->commonManage(); 
        if ($this->input == 2)
            $this->setTool("close_btn_down", 1);
        $this->setFind("item 2 list", parse_select_list("array", array("phone1", "code"), 
                array("客户电话", "内部代码" )));
        $this->setForm("element eid type", "hidden");
        $this->setForm("element eid value", $this->uid);
        $this->setElementSort($this->sel_items);     
        $this->setData("data_field 1 pop", $this->getPop("salesinfo"));
        $this->setData("where", "eid=".$this->uid." and sid=".$this->sid);
        $this->setData("data_title 7 hide", true);
        $this->setData("data_field 7 hide", true);
    }
    
    public function cardList($bid)
    {
		$data = new SmallDataList("basis_card_list", "", 0, array("close_num" => 1, 
		        "page" => array("size" => 5)));
		$list = sqlAll("SELECT id,bid,status,bank,card,rising_amount_num,amount,
		                      rising_amount_num + amount as total_amount,due_date,rising_num 
		        FROM card where bid =".$bid." ".$data->getPage());
		$data->set("data_list", $list);
        $data->setPage("total", sqlCol("SELECT count(id) FROM card where bid =".$bid));
        $data->setPage("param", "form=info&where='id=".$bid."'&small=basis_card_list");
		$data->setTitle(array("状态", "发卡行", "卡号", "原额度", "服务到期日期", "增值次数", "新增额度", "现总授信额", "续约次数"));
		$data->setField(array("status", "bank", "card", "amount", "due_date", "rising_num", "rising_amount_num", "total_amount", "renewal_num"));
        $data->set("data_field 0 fun", "get_status_txt");
        $data->set("data_field 8 run", "Card/Card/getRenewalNum");
        if ($this->admin == 3 || $this->admin == 4)
            $infopop = "salesinfo";
        else
            $infopop = "info";
		$data->setField("card", array("name" => 2, "url" => U("Card/Index")."&form=info&list=1&bid=[bid]&where='id=[id]'", 
		        "pop" => CardController::getPop($infopop)));
        return $data->fetch();
    }
    
//------------------------------分站客户管理info-----------------------------
    public function basisInfo($con, & $formObj)
    {
    	$obj = & $formObj->form["dataObj"];
    	$el = & $formObj->form["element"];
    	$data = $obj->where($con)->select();
        $usr = $data[0];
        
        $code = new FormElement("info_code", "static", "内部代码", array("value" => $data[0]["code"]));
        $el_status = new FormElement("info_status", "static", "客户状态", 
                    array("value" => \KyoCommon\Controller\IndexController::statusLink($usr, "name")));
        $ext = array("basis_start" => array("add", $code->get(), $el_status->get()));
        if ($this->admin == 6)
        {
            $el_typing = new FormElement("info_typing", "static", "录件人", array("value" => get_username($data[0]["typing"])));
            $ext["phone2"] = array("add", $el_typing->get());
        }
    	if (I("get.audit"))
            return $this->auditInfo($formObj, $ext, $data);
        
    	$formObj->formDataInfo($data, $ext);
        
		$formObj->setElement("basis_card_info", "group", "客户卡信息",array("class" => "text-center", 
				"back_ext" => '<div class="col-md-12">'.$this->cardList($data[0]["id"])."</div>",
		));
        
		if (!is_range($this->admin, array(1, 6, 3, 4)))
            return true;
        
        
		if ($this->admin == 3 || $this->admin == 4)
		{
            $formObj->setElementSort($this->sel_info);
    		if ($this->input == 2) 
                return true;
		}
        
		if (I("get.list"))
            return true;
        
        $status = $data[0]["status"];
        $edit_index = 0;
        $formObj->set("close_btn_down", 0);
        $formObj->form["btn"] = array();
        
        if ($status == NORMAL && $this->admin != 6)
        {
    		$formObj->set("btn 0", array("txt" => "添加卡片", "ext" => 'type="button"', "end" => "&nbsp;&nbsp;",
                    "url" => U("Card/Index")."&form=add&bid=".$data[0]["id"],
                    "pop" => CardController::getPop("add")));
            $edit_index = 1;
        }
        
        if ($status != NORMAL && $data[0]["typing"] != get_user_info("uid"))
            return true;
        
        if (($this->admin == 6 && $status == NORMAL) || $status == DRAFT || $status == NO_PASS)
        {
    		$formObj->set("btn ".$edit_index, array("txt" => "编辑客户", "ext" => 'type="button"', 
    		        "end" => "&nbsp;&nbsp;",
                    "url" => U()."&form=edit&where='id=".$data[0]["id"]."'",
                    "pop" => $this->getPop("edit")));
            $edit_index = 2;
            
            if ($status == DRAFT || $status == NO_PASS)
        		$formObj->set("btn ".$edit_index, array("txt" => "删除客户", "ext" => 'type="button"', 
        		        "end" => "&nbsp;&nbsp;",
                        "class" => "del_btn",
                        "ext" => 'confirm="确定删除吗?"',
                        "url" => U()."&form=del&where='id=".$data[0]["id"]."'"));
        }
            
        parse_link_html($formObj->form["btn"]);
    }
    
    public function audit()
    {
        $this->commonHead($this, "审核&nbsp;->&nbsp;客户审核列表");
        $this->setFind("item 1", array());
        //设置添加按钮弹出窗口
    	$this->setData("close_op", 1);
    	$this->setData("close_chkall", 1);
    	$this->setTitle(array("内部代码", "客户姓名", "客户电话", "所属拓展员", "录件人", "录入时间"));
    	$this->setField(array("code", "name", "phone1", "eid", "typing", "create_time"));
        $this->setField("code", array("name" => 0, "url" => U("Card/Basis/index")."&form=info&audit=1&where='id=[id]'",
                "pop" => $this->getPop("auditinfo")));
        $this->setData("where", "status=".AUDIT." and sid=".$this->sid);
        $this->setData("order", "update_time desc");
    	$this->display();    
    }
    
    public function basisAudit()
    {
        if (IS_POST)
        {
            $return = array("echo" => 1, "close" => 1, "info" => "操作成功!",
                    "url" => session("prev_urlbasis"), "tag" => ".basis", "callback" => "");
            
            $audit = $_POST["audit"];
            
            $oper_type = $audit == 2 ? CUST_AUDIT_NO_PASS : CUST_AUDIT_NORMAL;
            
            $data["status"] = $audit == 2 ? NO_PASS : NORMAL;
            if ($audit == 1)
                $data["times"] = date("Y-m-d");
            M("basis")->where("id=".$_POST["bid"])->save($data);
//             dump(M()->getLastSql());
            
            $ret = save_operating_record($_POST["code"], $oper_type);
            
            if ($audit == 1)
            {
                M("users")->where("id=".$_POST["eid"])->setInc("basis_num");
                M("card")->where("status=".ADD_LOCK." and bid=".$_POST["bid"])->save(array("status" => AUDIT, "update_time" => getcurtime()));
                $return["callback"] = '';
            }
            
            $this->ajaxReturn($return);
            exit(0);
        }
        
        $aut = new Form("", array("name" => "auditform"));
        
//         $aut->set("kajax", "false");
        $tl = "通过备注";
        $type_id = CUST_AUDIT_NORMAL;
        $value = "";
        
        if (I("get.audit") == 2)
        {
            $tl = "拒绝原因";
            $type_id = CUST_AUDIT_NO_PASS;
        }
        else
            $value = sqlCol("select txt from sel_remark where type_id=".$type_id);
        
        $aut->setElement("audittype", "autocomplete", $tl, array(
                "placeholder" => "自己写".$tl."!",
                "list" => parse_autocomplete("select txt from sel_remark where type_id=".$type_id)));
        $aut->setElement("remark", "textarea", "", array("close_label" => 1, "element_cols" => 12, 
                "rows" => 6, "bool" => "required", "value" => $value));
        $aut->setElement("code", "hidden", "", array("value" => I("get.code")));
        $aut->setElement("audit", "hidden", "", array("value" => I("get.audit")));
        $aut->setElement("eid", "hidden", "", array("value" => I("get.eid")));
        $aut->setElement("bid", "hidden", "", array("value" => I("get.bid")));
        $aut->set("btn 0 txt", "提交");
        echo $aut->fetch();
    }
    
    private function auditInfo($formObj, $ext, $data)
    {
        $imgcss = 'style="width:380px;height:230px"';
        $ext["card_img"] = "del";
        $imghtml = '<img src="'.__UP__.str_replace(",", "/", $data[0]["id_img"]).'" '.$imgcss.' />&nbsp;&nbsp;';
        $imghtml .= '<img src="'.__UP__.str_replace(",", "/", $data[0]["card_img"]).'" '.$imgcss.' />';
        $img = new FormElement("img", "static", '', array("close_label" => 1, "element_cols" => 12,
               "class" => "text-center",  "value" => $imghtml));
        $ext["remark"] = array("add", $img->get()); 
    	$formObj->formDataInfo($data, $ext);
        
        $formObj->setElementSort($this->mel_info);     
        
        $formObj->set("btn 0", array("txt" => "审核通过", "end" => "&nbsp;&nbsp;", 
                "ext" => 'type="button"',
                "url" => U("Card/Basis/basisAudit")."&audit=1&eid=".$data[0]["eid"].
                            "&bid=".$data[0]["id"]."&code=".$data[0]["code"],
                "pop" => $this->getPop("audit", "审核通过备注"),
        ));
        $formObj->set("btn 1", array("txt" => "拒绝通过", "ext" => 'type="button"', 
                "ext" => 'type="button"',
                "url" => U("Card/Basis/basisAudit")."&audit=2&eid=".$data[0]["eid"].
                            "&bid=".$data[0]["id"]."&code=".$data[0]["code"],
                "pop" => $this->getPop("audit", "拒绝通过原因"),
        ));
        $formObj->set("close_btn_up", 1);
        $formObj->set("close_btn_down", 0);
        parse_link_html($formObj->form["btn"]);
    }
    
    private function editRemark($id)
    {
        $usr = sqlRow("select phone1, phone2, eid, bank, card, card_name, bank_addr, company, addr, 
                            contact, phones, card_img from basis where id=".$id);
        $remark = "";
        
        if ($_POST["phone1"] != $usr["phone1"])
            $remark .= "&emsp;联系电话1由&nbsp;".$usr["phone1"]."&nbsp;修改为&nbsp;".$_POST["phone1"]."<br />";
        if ($_POST["phone2"] != $usr["phone2"])
            $remark .= "&emsp;联系电话2由&nbsp;".$usr["phone2"]."&nbsp;修改为&nbsp;".$_POST["phone2"]."<br />";
        
        if ($_POST["eid"] != $usr["eid"])
        {
            $eid_name = sqlAll("select username as name from users where id=".$_POST["eid"]." or id=".$usr["eid"]);
            $remark .= "&emsp;拓展员由&nbsp;".$eid_name[0]["name"]."&nbsp;修改为&nbsp;".$eid_name[1]["name"]."<br />";
        }
        
        if ($_POST["company"] != $usr["company"])
            $remark .= "&emsp;工作单位由&nbsp;".$usr["company"]."&nbsp;修改为&nbsp;".$_POST["company"]."<br />";
        if ($_POST["addr"] != $usr["addr"])
            $remark .= "&emsp;单位地址由&nbsp;".$usr["addr"]."&nbsp;修改为&nbsp;".$_POST["addr"]."<br />";
        if ($_POST["contact"] != $usr["contact"])
            $remark .= "&emsp;紧急联系人由&nbsp;".$usr["contact"]."&nbsp;修改为&nbsp;".$_POST["contact"]."<br />";
        if ($_POST["phones"] != $usr["phones"])
            $remark .= "&emsp;紧急人电话由&nbsp;".$usr["phones"]."&nbsp;修改为&nbsp;".$_POST["phones"]."<br />";
        
        if ($_POST["card_img"] != $usr["card_img"])
            $remark .= "&emsp;借记卡图片重新上传,原图已经删除!<br />";
        
        if ($_POST["bank"] != $usr["bank"])
            $remark .= "&emsp;借记卡银行名由&nbsp;".$usr["bank"]."&nbsp;修改为&nbsp;".$_POST["bank"]."<br />";
        if ($_POST["card"] != $usr["card"])
            $remark .= "&emsp;借记卡账户由&nbsp;".$usr["card"]."&nbsp;修改为&nbsp;".$_POST["card"]."<br />";
        if ($_POST["bank_addr"] != $usr["bank_addr"])
            $remark .= "&emsp;借记卡开户支行由&nbsp;".$usr["bank_addr"]."&nbsp;修改为&nbsp;".$_POST["bank_addr"]."<br />";
        
        if ($remark == "")
            return false;
        
        save_operating_record($usr["code"], CUST_EDIT, $remark);
        
//         $remark = "--------------- ".date("Y-m-d H:i:s")." ---------------<br />客户资料变更情况：<br />".
//         $remark."------------------------------------------------------<br />";
        
//         $_POST["remark"] = $remark.$_POST["remark"];
        
        return true;
    }
    
    private function basisSave(& $formObj, $ajax = true)
    {
        $form = & $formObj->form;
        $obj = & $form["dataObj"];
        
        if (!$_POST["eid"] || !is_numeric($_POST["eid"]))
            $this->ajaxReturn(array("echo" => 1, "info" => "所属拓展员不能为空或不存在!"));
        
        $ret = $obj->validate($form["validate"])->create();
        if (!$ret)
            $this->ajaxReturn(array("echo" => 1, "info" => $obj->getError()));     
        
        if ($this->admin == 6 && ($_POST["old_status"] == "" || $_POST["old_status"] == DRAFT))
            $_POST["times"] = date("Y-m-d");
        
        if ($_POST["id"])
        {
            //如果是站长，并且此客户为正常状态，代表此是站长要编辑此客户信息
            if ($this->admin == 6 && $_POST["old_status"] == NORMAL)
                $this->editRemark($_POST["id"]);
            
            $ret = $obj->auto($form["auto"])->create($_POST, 2);
            $ret = $ret ? $obj->where("id=".$_POST["id"])->save() : $ret;
            $form["return"]["info"] = $ret ? "提交成功!" : "输入数据格式有误!";
        }
        else
        {
            $_POST["code"] = build_code(5, $this->sid);
            $_POST["typing"] = $this->uid;
            
            $ret = $obj->auto($form["auto"])->create($_POST, 1);
            if (!$ret)
                $form["return"]["info"] = $obj->getError();
            else
            {
                $ret = $obj->add();
                if ($ret)
                {
                    save_operating_record($_POST["code"], CUST_ADD);
                    parse_umax("customer", $this->sid);
                }
                $form["return"]["info"] = $ret ? "提交成功!" : "输入数据格式有误!";
            }
        }
        
        if (!$ret)
        {
            $form["return"]["close"] = 0;
            $form["return"]["url"] = "";
        }
        
        if ($ajax)
            $this->ajaxReturn($form["return"]);
        return $ret;
    }
    
    public function basisHandle(& $formObj)
    {
        $return = & $formObj->form["return"];
        switch ($_POST["btnID"])
        {
        	case "save_draft":
                $this->save_draft($formObj);
        	    break;
        	default:    //确定提交和确定编辑的处理
                $_POST["old_status"] = $_POST["status"];
                if ($this->admin == 6)
                    $_POST["status"] = NORMAL;
                else
                    $_POST["status"] = AUDIT;
                
                $bid = $this->basisSave($formObj, false);
                if ($bid && $this->admin == 6 && 
                    ($_POST["old_status"] = "" || $_POST["old_status"] == DRAFT))
                {
                    M("users")->where("id=".$_POST["eid"])->setInc("basis_num");
                    M("card")->where("status=".ADD_LOCK." and bid=".$bid)->setField("status", AUDIT);
                }
                if (!$_POST["id"] && $bid)
                {
                    $info_url = U("Basis/index")."&form=info&where='id=".$bid."'";
                    $info_pop = "{".$this->getPop("info")."}";
                    
                    $card_url = U("Card/Index")."&form=add&bid=".$bid;
                    $card_pop = "{".CardController::getPop("add")."}";
                    
                    $list_url = session("prev_url");
                    $return["callback"] = 'basis_add_finish("'.$info_url.'", "'.$info_pop.'", "'.$list_url.'", "'.$card_url.'", "'.$card_pop.'")';
                }
                $this->ajaxReturn($return);
        	    break;
        }
    }
    
    public function basisDel($con, & $formObj)
    {
        $obj = & $formObj->form["dataObj"];
        
        $basis = $obj->field("id,id_img,card_img")->where($con)->select();
        $basis = $basis[0];
        unlink(__UP__.str_replace(",", "/", $basis["id_img"]));
        unlink(__UP__.str_replace(",", "/", $basis["card_img"]));
        $ret = $obj->where($con)->delete();
        M("card")->where("bid=".$basis["id"])->delete();
        $formObj->form["return"]["callback"] = '$(".del_btn").closest(".pop_win").find(".pop_close").click()';
        $formObj->form["return"]["info"] = $ret ? "删除成功!" : "没有匹配到要删除的数据!".$con;
        if ($ret)
            return true;
        return false;    	    
    }
    
    public function basisEdit($con, & $formObj)
    {
        $form = & $formObj->form;
        $el = & $form["element"];
        $obj = & $form["dataObj"];
        
        $data = $obj->where($con)->select();
        $basis = $data[0];
        $formObj->formDataShowEdit($data);    
        if ($basis["status"] == NORMAL)
        {
            $el["name"]["bool"] .= " disabled";
            $el["sex"]["bool"] .= " disabled";
            $el["identity"]["bool"] .= " disabled";
            $el["card_name"]["bool"] .= " disabled";
            $el["eid"]["bool"] .= " disabled";
            $el["id_img"]["info"] = 1;
        }
        $el["eid"]["input_val"] = get_username($basis["eid"]);
        
        if ($basis["status"] == DRAFT)
            $formObj->set("btn 0 txt", "保存草稿");
        else
        {
            $formObj->set("btn 0 class", "hidden");
            $formObj->set("btn 1 txt", "确定编辑");
        }
    }
    
    public function save_draft(& $formObj)
    {
        if (!$_POST["eid"] || !is_numeric($_POST["eid"]))
            $this->ajaxReturn(array("echo" => 1, "info" => "所属拓展员不能为空或不存在!"));
        
        $return = & $formObj->form["return"];
        $_POST["status"] = DRAFT;
        $bid = $this->basisSave($formObj, false);        
        if ($bid)
            $return["info"] = "保存草稿成功!";
        $this->ajaxReturn($return);
    }
}