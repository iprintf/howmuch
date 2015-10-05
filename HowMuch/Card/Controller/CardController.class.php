<?php
namespace Card\Controller;
use Common\Controller\ListPage;
use Common\Controller\FormElement;
use Common\Controller\Form;
use Common\Controller\SmallDataList;

class CardController extends ListPage
{
    private $admin;
    private $sid;
    private $uid;
    private $input;
    public static $el_customer = array("customer_group", "bname", "eid_name", "card_group");
    public static $el_relate = array("relate_group", "alipay", "autopay", "aging", "tenpay", "insure", 
            "temp_amount", "wxpay", "quick_pay", "auto_aging", "overdue", "affiliate", "exceed");
    private $el_items = array(
                "opid", 
                "cost", 
                "bank",
                "rising_cost", 
                "card_type", 
                "pay_type",
                "card_type_name", 
                "fee",
                "card", 
                "agreement", 
                "amount", 
                "save_amount",
                "pay_pwd", 
                "fact_save",
                "query_pwd", 
                "counts", 
                "cvv2", 
                "costing_op",
                "cdate_month", "cdate_year", 
                "costing_per", "costing", 
                "bill", 
                "card_img1",
                "finally_repayment_date",
                "card_img2",
                "year_fee",
                "sign_img", 
                "remark",
    );
    private $sel_items = array(
                "bank",
                "cost", 
                "card", 
                "rising_cost", 
                "amount", 
                "pay_type",
                "pay_pwd", 
                "fee",
                "query_pwd", 
                "agreement", 
                "cdate_month", "cdate_year", 
                "save_amount",
                "bill", 
                "card_img1",
                "finally_repayment_date",
                "card_img2",
                "counts", 
                "sign_img", 
                "year_fee",
                "remark",
                "costing_op",
            );
    
    //构造函数
    public function __construct()
    {
        parent::__construct();
        if (!IS_AJAX)
            header("Location:".ERROR_URL);
        karray_insert($this->el_items, 0, self::$el_customer);
        karray_insert($this->sel_items, 0, self::$el_customer);
        karray_cat($this->el_items, self::$el_relate);
        karray_cat($this->sel_items, self::$el_relate);
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
                    $title = "添加信用卡";
                $pop = "w:1100,h:1280,n:'cardadd',t:".$title;
        	    break;
        	case "edit":
                if (!$title)
                    $title = "编辑信用卡资料";
                $pop = "w:1100,h:1280,c:1,n:'cardedit',t:".$title;
        	    break;
        	case "medit":
                if (!$title)
                    $title = "编辑信用卡资料";
                $pop = "w:600,h:500,c:1,n:'cardmedit',t:".$title;
        	    break;
        	case "info":
                if (!$title)
                    $title = "信用卡详细信息";
                $pop = "w:1000,h:1400,n:'cardinfo',t:".$title;
        	    break;
        	case "salesadd":
                if (!$title)
                    $title = "添加信用卡";
                $pop = "w:1100,h:1130,n:'cardadd',t:".$title;
        	    break;
        	case "salesedit":
                if (!$title)
                    $title = "编辑信用卡资料";
                $pop = "w:1100,h:1130,c:1,n:'cardedit',t:".$title;
        	    break;
        	case "salesinfo":
                if (!$title)
                    $title = "信用卡详细信息";
                $pop = "w:1000,h:1100,n:'cardinfo',t:".$title;
        	    break;
        	case "auditinfo":
                if (!$title)
                    $title = "信用卡审核详细信息";
                $pop = "w:1000,h:1200,n:'auditcardinfo',t:".$title;
        	    break;
        	case "subauditinfo":
                if (!$title)
                    $title = "信用卡初审详细信息";
                $pop = "w:1000,h:1130,n:'subauditcardinfo',t:".$title;
        	    break;
        	case "auditimgwin":
                if (!$title)
                    $title = "信用卡图片验证";
                if ($title == 1)
                    $pop = "w:1200,h:670,c:1,n:'auditimgwin',t:信用卡图片验证";
                else
                    $pop = "w:1200,h:670,n:'auditimgwin',t:".$title;
        	    break;
        	case "agreement":   //主站平台查看信用卡期数详细信息
                if (!$title)
                    $title = "信用卡生成期数详细信息";
                $pop = "w:1100,h:900,n:'agreementInfo',t:".$title;
        	    break;
         	case "audit":
                if (!$title)
                    $title = "信用卡审核";
                $pop = "w:480,h:360,c:1,n:'cardaudit',t:".$title;
        	    break;                
         	case "cardtypelist":
                if (!$title)
                    $title = "卡片名称选择";
                $pop = "w:1200,h:800,n:'cardtypelist',t:".$title;
        	    break;                
        	default:
        	    break;
        }
        return $pop;
    }
    
    static public function commonHead($obj, $title = "客户管理&nbsp;->&nbsp;信用卡")
    {
        //预处理特殊查询选项
        if (I("get.find") && IS_POST)
        {
            if (I("post.status") == -1)
                $_POST["status"] = 0;
            else if (I("post.status") == RESCIND)
            {
                $_POST["status"] = NORMAL;
                $_POST["temp_status"] = RESCIND;
            }
            findIn("name", "bid", "basis");
            findIn("username", "eid", "users", "id", "(type=3 or type=4)");
            findIn("proxy_sub_name", "sid", "users", "id", "type=6");
        }
        
        $obj->setNav("&nbsp;->&nbsp;".$title);
        $obj->mainPage("card");
        
        //设置添加按钮弹出窗口
        $obj->setFind("item 0", array());
        $obj->setFind("item 1", array("name" => "status", "type" => "select",
            "default" => "所有状态", "defval" => 0,
            "list" => parse_select_list("array", array(-1, DRAFT, SUB_AUDIT, AUDIT, NO_PASS, LOCK, RESCIND, SEARCH), 
                    array("正常状", "草稿件", "待初审", "待终审", "不通过", "锁定状", "待解约", "临查状"))));
        $obj->setFind("item 2", array("name" => "search_type", "type" => "select", 
                "default" => "卡号", "defval" => "card", 
                "list" => parse_select_list("array", array("username", "name", "code"), 
                        array("拓展员", "卡主姓名", "内部代码"))));
    }
    
    //站长导出报表
//     public function excelExport()
//     {
//         if ($this->admin != 6)  
//             return false;
        
//         $mail = new \Common\Controller\KyoMail();
//         if (!$mail)
//             $this->ajaxReturn(array("echo" => 1, "info" => "信用卡:导出报表失败!"));
//         $this->ajaxReturn(array("echo" => 1, "info" => $mail->export("站内所有信用卡信息报表", $_SESSION["excel"]["title"], $_SESSION["excel"]["sql"])));
//     }
    
    
    public function commonManage($title = "客户管理&nbsp;->&nbsp;信用卡")
    {
        $this->commonHead($this, $title);
        
        if ($this->admin == 0)
        {
            $this->setBtn("计算到期日期", U("CardOp/testDueDate"), 
                    array("pop" => "w:640,h:430,c:1,n:'cardaudit',t:测试计算到期日期"));
            $this->setBtn("测试生成期数", U("CardOp/testInstallment"), 
                    array("pop" => "w:1100,h:820,c:1,n:'cardinstallment',t:测试生成期数"));
        }
        
        if ($this->admin != 6)
            $this->setTool("tool_btn_down", array("txt" => "新增卡片", "icon" => "plus",
                    "url" => U()."&form=add",
                    "pop" => $this->getPop("add")));
        
        //设置批量删除操作
        if ($this->admin == 6 || $this->admin == 0 || $this->admin == 9 || $this->admin == 7)
        {
            $excel["title"] = array("status" => "状态","bid" => "姓名", "bank" => "发卡行", 
                "card" => "卡号 ", "amount" => "授信额度", "bill" => "出账单日", "finally_repayment_date" => "最后还款日", 
                "effective_date" => "卡片有效期", "times" => "签约日期", "agreement" => "服务期限", "cost" => "服务费率",
                "rising_cost" => "增值费率");
            $excel["name"] = "站内所有信用卡信息报表";
            if ($this->admin == 6)
                $this->setBatch("全部导出", U("KyoCommon/Index/excelExport"), array("query" => true, 
                        "name" => "excel", 'icon' => "cloud-download"));
            else
                $this->setBatch("全部导出", U("KyoCommon/Index/excelExport"), array("query" => true, 
                        "name" => "excel", "bool" => "kopen", 'icon' => "cloud-download"));
        }
        
        if ($this->admin == 0 || $this->admin == 9 || $this->admin == 7)
        {
            $this->setBatch("条码打印", U("KyoCommon/Index/barcode"), array("query" => true, "bool" => "kopen",
                     'icon' => "print"));
            $this->setBatch("全部打印", U("KyoCommon/Index/barcode")."&op=all", array("query" => true, 
                    "bool" => "kopen", "name" => "print", 'icon' => "print"));
        }
        else
        {
            $this->setBatch("条码导出", U("KyoCommon/Index/barcode"), array("query" => true, 'icon' => "print"));
            $this->setBatch("全部打印", U("KyoCommon/Index/barcode")."&op=all", array("query" => true, 
                    "name" => "print", 'icon' => "print"));
        }
        
        $this->setForm("cols",2);
//         $this->setForm("kajax","false");
        $this->setForm("name", "cardform");
        $this->setForm("handle_run post", "CardOp/cardHandle");
        $this->setForm("handle_run show_edit", "CardOp/cardEdit");
        $this->setForm("handle_run info", "CardOp/cardInfo");
        $this->setForm("handle_run del", "CardOp/cardDel");
        
        $sid = get_user_info("sid");
        
        //设置表单成员， 添加、编辑和信息的元素  uniq为不得重复
        $this->setElement("customer_group", "group", "客户基本信息");
        $this->getBasis(I("get.bid"));
        
        $this->setElement("card_group", "group", "信用卡基本信息");
        $this->setElement("bank", "autocomplete", "发卡银行", array("bool" => "required",
                           "ext" => 'bpop="'.$this->getPop("cardtypelist").'" 
                                   val_callback="getcardtype(\''.U("CardOp/cardTypeWin").'\')"',
                          "list" => parse_autocomplete("select name from bank order by sort_id")));
        $this->setElement("opid", "autocomplete", "所属操作员", array("bool" => "required",
                    "ext" => 'count="2" val_callback="opid_input_dis()"',
                    "list" => parse_autocomplete("select username,card_num,id from users where type=2 and sid=".$sid),
        ));
        $this->setElement("card_type_name", "string", "卡片名称", array("bool" => "required", 
                            "pclass" => "sel_cardtypename"
        ));
        
        $pay_type_txt = C("PAYTYPE_TEXT");
        $this->setElement("pay_type", "select", "支付方式", array("bool" => "required", 
                "ext" => 'gurl="'.U("CardOp/getAgreement").'"',
                "list" => parse_select_list("array", array_keys($pay_type_txt), $pay_type_txt, "请选择支付方式"),
        ));
        $this->setElement("fee", "num", "服务费用", array("bool" => "required",
                "hint" => "money", "addon" => "元"));
        $this->setElement("agreement", "num", "服务期限", array("bool" => "readonly required", 
                "pclass" => "sel_agreement", "addon" => "月"));
        
        $this->setElement("save_amount", "num", "保底金额", array("bool" => "readonly required", 
                "addon" => "元"));
        $this->setElement("fact_save", "num", "实保额", array("bool" => "required", 
                "hint" => "money", "addon" => "元"));
        
        $this->setElement("card_type", "string", "卡片类别", array("bool" => "required", 
                            "pclass" => "sel_cardtype", 
        ));
        $this->setElement("cost", "select", "服务费率", array("bool" => "required",
                "list" => parse_select_list("for", array(1.2, 5.1, 0.1, 1), "请选择服务费率"),
                "addon" => "%",
        ));
//         $this->setElement("cost", "autocomplete", "服务费率", array("bool" => "required",
//                 "list" => parse_autocomplete(array("for", 1.2, 5.1, 0.1, 1)),
//                 "addon" => "%",
//         ));
        $this->setElement("card", "string", "卡号", array("bool" => "uniq required", "hint" => "card", 
                "min" => 13, "maxlength" => 16));
        $this->setElement("rising_cost", "select", "增值费率", array("bool" => "required",
                "list" => parse_select_list("for", array(0, 25, 1, 1), "请选择增值费率"),
                "addon" => "%",
        ));
//         $this->setElement("rising_cost", "autocomplete", "增值费率", array("bool" => "required",
//                 "list" => parse_autocomplete(array("for", 0, 25, 1, 1)),
//                 "addon" => "%",
//         ));
        $this->setElement("amount", "string", "授信额度", array("bool" => "required", 
                "hint" => "money", "addon" => "元"));
        
        
        $this->setElement("counts", "autocomplete", "刷卡笔数", array("bool" => "required",
            "list" => parse_autocomplete(array("for", 12, 120, 2)),
            "addon" => "笔/月"));
        $this->setElement("pay_pwd", "string", "支付密码", array("bool" => "required", "hint" => "num", 
                "min" => 6, "maxlength" => 6));
        $this->setElement("query_pwd", "string", "电话密码", array("bool" => "required", "hint" => "num", 
            "min" => 6, "maxlength" => 6));
        
        $this->setElement("costing_op", "select", "操作选项", array("bool" => "required", 
                "list" => parse_select_list("array", array(1, 2, 3, 4), array("常规服务", "低值服务", "高值服务", "保值服务")),
        ));
        
        $this->setElement("costing_per", "select", "交易成本", array("bool" => "required", "element_cols" => 2,
                "group" => "start", "addon" => "%",
                "list" => parse_select_list("for", array(0.4, 1.2, 0.1, 1, 3), "选择成本")
        ));
        $this->setElement("costing", "string", "", array("bool" => "readonly required", "group" => "end",
//                   "addon" => "元",
                 "element_cols" => 1));
        $this->setElement("cvv2", "string", "CVN2码", array("bool" => "required", "hint" => "num", 
                "min" => 3, "maxlength" => 7));
        $this->setElement("card_img1", "file", "信用卡正面图片", array("bool" => "required", "cat_title" => "信用卡正面图片信息"));
        $this->setElement("bill", "num", "月出账单日", array("bool" => "required", "min" => 1, 
                "ext" => 'max="28"', "addon" => "号"));
        $this->setElement("card_img2", "file", "信用卡背面图片", array("bool" => "required", "cat_title" => "信用卡背面图片信息"));
        $this->setElement("finally_repayment_date", "select", "最后还款日", array("bool" => "required", 
                "list" => parse_select_list("array", array("", 15,16,17,18,19,20,21,22,23,24,25,26), 
                        array("请选择最后还款日", "T + 15", "T + 16", "T + 17", "T + 18", "T + 19", "T + 20", "T + 21", "T + 22", "T + 23", "T + 24", "T + 25", "T + 26"))
        ));
        $this->setElement("year_fee", "num", "信用卡年费", array("bool" => "required", "addon" => "元", "hint" => "money"));
        $this->setElement("sign_img", "file", "信用卡电子签名", array("bool" => "required", 
                "cat_title" => "信用卡电子签名信息"));
        $this->setElement("cdate_month", "select", "卡片有效期", array("bool" => "required", "element_cols" => 1, 
                "group" => "start", "title" => "卡有效期月",
                "list" => parse_select_list("for", array(1, 12, 1, 1), "月"),
        ));
        $this->setElement("cdate_year", "select", "", array("bool" => "required", "element_cols" => 2, 
                "group" => "end", "title" => "卡有效期年", 
                "list" => parse_select_list("for", array(2014, 2020, 1, 1), "年"),
        ));
        
        $this->setElement("relate_group", "group", "信用卡关联业务状态");
        $this->setElement("alipay", "radio", "支付宝绑定", array("bool" => "required", 
                    "group" => "start",
                    "list" => parse_select_list("array", array(1, 2), array("是", "否")),
        ));
        $this->setElement("autopay", "radio", "自动还款绑定", array("bool" => "required", 
                    "list" => parse_select_list("array", array(1, 2), array("是", "否")),
                    "close_label" => 2,
                    "group" => "end",
        ));
        $this->setElement("aging", "radio", "已做分期", array("bool" => "required", 
                    "list" => parse_select_list("array", array(1, 2), array("是", "否")),
                    "label_cols" => 2,
                    "element_cols" => 2,
        ));
        $this->setElement("tenpay", "radio", "财付通绑定", array("bool" => "required", 
                    "list" => parse_select_list("array", array(1, 2), array("是", "否")),
                    "group" => "start",
        ));
        $this->setElement("insure", "radio", "商业保险绑定", array("bool" => "required", 
                    "list" => parse_select_list("array", array(1, 2), array("是", "否")),
                    "close_label" => 2,
                    "group" => "end",
        ));
        $this->setElement("temp_amount", "radio", "临时调额", array("bool" => "required", 
                    "list" => parse_select_list("array", array(1, 2), array("是", "否")),
                    "element_cols" => 2,
                    "label_cols" => 2,
        ));
        
        $this->setElement("wxpay", "radio", "微信付绑定", array("bool" => "required", 
                    "list" => parse_select_list("array", array(1, 2), array("是", "否")),
                    "group" => "start",
        ));
        $this->setElement("quick_pay", "radio", "快捷支付绑定", array("bool" => "required",
                    "list" => parse_select_list("array", array(1, 2), array("是", "否")),
                    "close_label" => 2,
                    "group" => "end",
        ));
        $this->setElement("auto_aging", "radio", "自动分期", array("bool" => "required",
                    "list" => parse_select_list("array", array(1, 2), array("是", "否")),
                    "label_cols" => 2,
                    "element_cols" => 2,
        ));
        $this->setElement("overdue", "radio", "已逾期卡片", array("bool" => "required",
                    "group" => "start",
                    "list" => parse_select_list("array", array(1, 2), array("是", "否")),
        ));
        $this->setElement("affiliate", "radio", "有无附属卡片", array("bool" => "required",
                    "close_label" => 2,
                    "group" => "end",
                    "list" => parse_select_list("array", array(1, 2), array("是", "否")),
        ));
        $this->setElement("exceed", "radio", "已超限额", array("bool" => "required",
                    "list" => parse_select_list("array", array(1, 2), array("是", "否")),
                    "label_cols" => 2,
                    "element_cols" => 2,
        ));
        
        $this->setElement("remark", "textarea", "备注");
        
        $this->setElementSort($this->el_items);
        
        
        //继续添加卡片，先保存本卡到数据库，状态为初审状态，然后再弹出卡片添加窗口
        $this->setForm("btn 0", array("name" => "add_card", "txt" => "继续添加", "end" => "&nbsp;&nbsp;",
                "bool" => "me", "ext" => 'id="card_add_card", type="button"',
        ));
        //保存草稿，将卡片资料以草稿状态保存到数据库，关闭卡片添加窗口, 提示保存草稿成功
        $this->setForm("btn 1", array("txt" => "保存草稿", "end" => "&nbsp;&nbsp;",
                "bool" => "me", "ext" => 'id="card_save_draft" type="button"',
        ));
        //将客户资料以初审状态保存到数据库中，关闭卡片添加窗口，提示保存成功
        $this->setForm("btn 2", array("txt" => "确定提交",
                "ext" => 'id="card_submit" type="submit"'));
        
        if (I("get.form") == "add" || I("get.form") == "edit")
            $this->setForm("js", "kyo_card");
        
        /*添加隐藏提交字段*/
        $this->setElement("btnID", "hidden", "");
        $this->setElement("code", "hidden", "");
        $this->setElement("status", "hidden", "", array("value" => 0));
        //设置表单添加或修改自动完成项  创建时间和更新时间不需要输入自动填写
        $this->setForm("auto", array(
                array("proxy_id", get_user_info("proxy_id")),
                array("sid", get_user_info("sid")),
                array("create_time", 'getcurtime', 1, "function"),//1添加时生效，2修改时生效，3以上都生效
                array("update_time", 'getcurtime', 3, "function"),
        ));
//         $this->setOp("条码", U("KyoCommon/Index/barcode")."&code=[code]",
//                 array("target" => "_blank"));
        $this->setOp("编辑", U()."&form=edit&bid=[bid]&where='id=[id]'",
                array("pop" => $this->getPop("edit")));

        $this->setData("close_op", 1);
        $this->setData("where", "status<>8 and sid=".$this->sid);
        $this->setData("order", "update_time desc");
        $this->setTitle(array("状态", "内部代码", "客户姓名", "发卡行", "卡号", "授信额度", "服务期限", "增值次数", "增值总额", "续约次数", "所属拓展员"));
        $this->setField(array("card", "code", "bid", "bank", "card", "amount", "agreement", "rising_num", "rising_amount_num", "renewal_num", "eid"));
//         $this->setData("data_title 5 sort", "amount");  //手动排序
//         $this->setData("subtotal field", array(5));     //字段本页小计
        $this->setData("excel", $excel);
        $this->setData("data_field 0 run", "KyoCommon/Index/statusLink");
        $this->setData("data_field 9 run", "Card/Card/getRenewalNum");
        $this->setField("code", array("name" => 1, "url" => U()."&form=info&bid=[bid]&where='id=[id]'",
                "pop" => $this->getPop("info")));
    }

    public function index()
    {
        switch ($this->admin)
        {
        	case 3:
        	case 4:
                $this->salesman(false);
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
    
    public function audit()
    {
        if ($this->admin == 6)
            $this->master_audit();
        else if ($this->admin == 1)
            $this->finance_audit();
    	$this->display();
    }
    
    //获取客户手机号码
    public function auditFormatField($data, $txt)
    {
        if (!$data["bid"])
            return "";
        $basis = sqlRow("select phone1 from basis where id=".$data["bid"]);
        
        if (is_repay_date($data, $end_num))
            $data["audit_date"] = "随时";
        else
        {
            if (date("d") > $data["bill"])
                $data["audit_date"] = date("Y-m-", strtotime("+1 month")).fill_zero($data["bill"]);
            else
                $data["audit_date"] = date("Y-m-").fill_zero($data["bill"]);
        }
        
        return format_dis_field($basis["phone1"], array(3, 4, 4));
    }
    
    public function master_audit($title = "审核&nbsp;->&nbsp;卡片终审列表")
    {
        $this->commonManage($title); 
        $this->setFind("item 1", array());
        $this->setTool("close_btn_down", 1);
        $this->setTool("close_batch", 1);
        
        $this->setData("where", "status=".AUDIT." and temp_status=".NORMAL." and sid=".$this->sid);
    	$this->setData("close_op", 1);
    	$this->setData("close_chkall", 1);
        $this->setData("data_title", array()); 
        $this->setData("data_field", array()); 
    	$this->setTitle(array("状态", "内部代码", "客户姓名", "客户电话", "发卡行", "卡号", "额度", "建议终审日", "所属拓展员", "录入时间"));
    	$this->setField(array("status", "code", "bid",  "phone1", "bank", "card", "amount", "audit_date", "eid", "create_time"));
        $this->setData("data_field 0 run", "KyoCommon/Index/statusLink");
        $this->setData("data_field 3 run", "Card/auditFormatField");
        $this->setData("data_field 7 class", "kyo_red");
        $this->setField("code", array("name" => 1, 
                "url" => U("Card/index")."&form=info&audit=1&bid=[bid]&where='id=[id]'",
                "pop" => $this->getPop("auditinfo")));
    }
    
    public function finance_audit()
    {
        $this->master_audit("卡片操作&nbsp;->&nbsp;卡片初审列表"); 
        $this->set("close_tool", 1);
        $this->set("close_top_ctrl", 1);
        
        $this->setData("where", "status=".SUB_AUDIT." and sid=".$this->sid); 
        $this->setField("code", array("name" => 1, "url" => U("Card/Card/salesman").
                "&form=info&audit=1&bid=[bid]&where='id=[id]'",
                "pop" => $this->getPop("subauditinfo")));
    }
    
    //获取此卡续约次数
    public function getRenewalNum($data, $txt = "")
    {
        return sqlCol("select count(id) from expand_bonus where stype=6 and card_code='".$data["code"]."'");
    }
    
    
    //主站平台 查看卡期数生成情况
    public function agreementInfo($cid)
    {
        if (I("get.op") == "reset")
        {
            $card = sqlRow("select * from card where id=".I("get.cid"));
            update_card_installment("", $card, false, $card["times"]); 
            $this->ajaxReturn(array("echo" => 1, "close" => 1, "info" => "操作成功，点击服务期数重新查看!"));
        }
        
        $card = sqlRow("select b.name,c.card,c.bank,c.agreement,c.bill,c.finally_repayment_date,c.times,
                    c.due_date,c.code,c.status from basis b, card c where b.id=c.bid and c.id=".$cid);
        if ($card["status"] != NORMAL)
        {
            echo '<h1 style="color:red;font-weight:bold;">此卡状态不为正常状态，无法查看期数信息!</h1>';
            exit(0);
        }
            
        $period = sqlAll("select start_date,total,installment,days from card_installment 
                            where code='".$card["code"]."'");
        $pf = new Form("", array("cols" => 2));
        $pf->setElement("card_group", "group", "信用卡基本信息");
        $pf->setElement("bname", "static", "卡主", array("value" => $card["name"]));
        $pf->setElement("times", "static", "签约日期", array("value" => $card["times"]));
        $pf->setElement("card", "static", "卡号", array("value" => format_dis_field($card["card"])));
        $pf->setElement("due_date", "static", "到期日期", array("value" => $card["due_date"]));
        $pf->setElement("bank", "static", "发卡行", array("value" => $card["bank"]));
        $pf->setElement("bill", "static", "出账单日", array("value" => $card["bill"]." 号"));
        $pf->setElement("agreement", "static", "服务期数", array("value" => $card["agreement"]." 期"));
        $pf->setElement("end_date", "static", "最后还款日", array("value" => "T + ".$card["finally_repayment_date"]));
		$data = new SmallDataList("card_installment_list", "", 0, array("page" => array("size" => 15)));
		$data->set("data_list", $period);
        $data->set("tooltip", 1);
		$data->setTitle(array("期数起始", "总期数", "随机期数", "还款日期"));
		$data->setField(array("start_date", "total", "installment", "days"));
        $pf->setElement("installment", "static", "", array("close_label" => 1, "element_cols" => 12,
                "sig_row" => 1, "value" => $data->fetch()));
        
        $pf->set("btn 0", array("txt" => "重新生成期数", "ext" => 'type="button"', 
                "ext" => 'confirm="确定重新生成期数吗?"', "url" => U("")."&op=reset&cid=".$cid));
        
        echo $pf->fetch();
    }
    
    //主站查看卡片列表
    public function main()
    {
        $this->commonManage("分站&nbsp;->&nbsp;卡片列表"); 
        $this->setTool("close_btn_down", 1);
//         $this->setTool("close_batch", 1);
        $this->setFind("item 0", array("name" => "sid", "type" => "select", "default" => "所有分站",
                "list" => parse_select_list("select id,proxy_sub_name from users where type=6")));
//         $this->setData("close_chkall", 1);
        $this->setData("where", "1");
        $this->setData("data_title 10 txt", "所属分站");
        $this->setData("data_field 10 txt", "sid");
        $this->setField("agreement", array("name" => 6, "url" => U("Card/agreementInfo")."&cid=[id]",
                "pop" => $this->getPop("agreement")));
        $this->setForm("handle_run info", "CardOp/adminCardInfo");
    }
    
    public function progressRepay($cday="", $cend="", $code="")
    {
        $data = new SmallDataList("progress_reapy", "", 0, array("page" => array("size" => 12)));
        $data->setCustomList("pro_card_balance", true, array("'".$cday."'", "'".$cend."'", 3, "NULL", "NULL", "'".$code."'"));
//         dump(M()->getLastSql());
        $data->setPage("param", "small=progress_deal&cday=".$cday."&cend=".$cend."&code=".$code);
        $data->setTitle(array("客户姓名","发卡行","卡号","还款推送日期","当期授信额度","当日建议还款","当日实际还款","当日还款差额"));
        $data->setField(array("bname","bank","card","dates","total_amount","amount","put_amount","poor_amount"));
        echo $data->fetch();
    }
    
    public function progressDeal($bill="", $code="")
    {
        $data = new SmallDataList("progress_deal", "", 0, array("page" => array("size" => 12)));
        $data->setCustomList("pro_card_balance", true, array("'".$bill."'", "'".$bill."'", 2, "NULL", "NULL", "'".$code."'"));
//         dump(M()->getLastSql());
        $data->setPage("param", "small=progress_deal&bill=".$bill."&code=".$code);
        $data->setTitle(array("客户姓名","发卡行","卡号","交易推送日期","推荐笔数", "实际笔数","推送金额","实际交易","卡内剩余","所属操作员"));
        $data->setField(array("bname","bank","card","dates","day_pen","s_pen_num", "day_amount","s_rmb","poor_amount","opname"));
        echo $data->fetch();
    }
    
    //进度临查功能
    public function progress()
    {
        $sType = "NULL";
        $sVal = "NULL";
        if (IS_POST && $_POST["search_type"] && $_POST["search_key"])
        {
            $sType = "'".$_POST["search_type"]."'";
            $sVal = "'".$_POST["search_key"]."'";
        }
        
        $this->setNav("&nbsp;->&nbsp;综合查询&nbsp;->&nbsp;卡片监查");
        $this->mainPage("card_progress");
        $this->setFind("item 1", array("name" => "search_type", "type" => "select",
        		"default" => "客户姓名", "defval" => "name",
        		"list" => parse_select_list("array", array("card"),
        				array("卡片卡号"))));
        
        
        $this->setData("close_op", 1);
        $this->setData("close_chkall", 1);
        $this->setCustomList("pro_card_balance", true, array("NULL", "NULL", 1, $this->sid, $sType, $sVal));
        
        $this->setTitle(array("客户姓名","卡号","所处期数","当期授信","已还款额","剩余应还","还款已用","还款未用","交易笔数","交易成本","还款状","交易状"));
        $this->setField(array("name","card","dates","amount","repay_rmb","remaining_pay_rmb","expend_rmb","remaining_rmb", "pen_num","pos_cost_rmb","expire","tran"));
        $this->setField("card", array("name" => 1, "url" => U("Card/index")."&form=info&list=1&bid=[bid]&where='id=[cid]'",
                "pop" => CardController::getPop("info")));
        $this->setField("repay_rmb", array("name" => 4, "url" => U("Card/Card/progressRepay")."&cday=[cday]&cend=[cend]&code=[ccode]",
                "pop" => "w:1100,h:550,n:'repayinfo',t:[ccode] 还款明细"));
        $this->setField("expend_rmb", array("name" => 6, "url" => U("Card/Card/progressDeal")."&bill=[bill]&code=[ccode]",
                "pop" => "w:1100,h:550,n:'dealinfo',t:[ccode] 交易明细"));
        
        $this->display();
    }
    
    
    //拓展员平台下获取卡片状态文本，如果是不通过则链接显示原因 -----没有用到
//     static public function salesGetStatus($data, $txt = "")
//     {
//         if (I("get.code"))
//         {
//             echo sqlCol("select remark from operating_record where code='".I("get.code")."' 
//                     and type=".CARD_AUDIT_NO_PASS." order by oper_time desc");
//             exit(0); 
//         }
//         $stxt = get_status_txt($data["status"]); 
//         if ($data["status"] == NO_PASS)
//         {
//             $r = new FormElement("nopass", "link", $stxt, array(
//                     "begin" => 0,
//                     "over" => 0,
//                     "class" => "text-danger",
//                     "close_element_div" => 1,
//                     "url" => U()."&code=".$data["code"], 
//                     "pop" => "w:480,h:360,n:'nopass',t:审核拒绝通过原因"));
//             return $r->fetch();
//         }
//         return $stxt;    
//     }
    
    public function salesman($display = true)
    {
        $this->commonManage(); 
        $this->setTool("close_batch", 1);
        $this->setData("close_chkall", 1);
        $this->setTool("tool_btn_down pop", $this->getPop("salesadd"));
        if ($this->input == 2)
            $this->setTool("close_btn_down", 1);
        $this->setFind("item 2 list", parse_select_list("array", array("name", "code"), 
                        array("客户姓名", "内部代码")));
        
        $this->setElementSort($this->sel_items);
//         $sql = "select b.name,b.phone1,b.identity,b.id,b.eid,u.username from 
//                     basis b, users u where b.eid=u.id and b.status=0 and 
//                     b.eid=".$this->uid." and b.sid=".$this->sid;
        $sql = "call card_find_basis(".$this->sid.", ".$this->uid.", null)";
        
        $pay_type_txt = C("PAYTYPE_TEXT");
        array_pop($pay_type_txt);
        $this->setForm("element pay_type list", 
                parse_select_list("array", array_keys($pay_type_txt), $pay_type_txt, "请选择支付方式"));
        
        $this->setForm("element bname list", parse_autocomplete($sql));
        $this->setForm("element bank ext", "");
        $this->setForm("element cost type", "num");
        $this->setForm("element costing_op type", "hidden");
        $this->setForm("element costing_op value", "1");
        $this->setForm("element cost maxlength", 3);
//         $this->setForm("element save_amount bool", "required");
        $this->setForm("element rising_cost type", "num");
        $this->setForm("element eid_name value", get_user_info("name"));
        $this->setForm("element eid", array("name" => "eid", "type" => "hidden", "value" => $this->uid));
        $this->setData("where", "eid=".$this->uid." and sid=".$this->sid);
        $this->setData("data_title 10 hide", true);
        $this->setData("data_field 10 hide", true);         
        $this->setData("data_field 1 pop", $this->getPop("salesinfo"));         
        $this->setForm("handle_run post", "CardOp/cardHandle");
        $this->setForm("handle_run show_edit", "CardOp/cardEdit");
        $this->setForm("handle_run info", "CardOp/salesCardInfo");
        if ($display)
            $this->display();
    }
    
    
    //获取客户信息
    public function getBasis($bid = "")
    {
        $basis = array();
        karray($basis, array("name", "username", "eid"));
        
        if ($bid)
        {
            $basis = sqlRow("call card_find_basis(".$this->sid.", null, ".$bid.")");
//             dump(M()->getLastSql());
//             dump($basis);
            $this->setElement("bname", "static", "客户姓名", 
                    array("value" => $basis["name"]."&emsp;".$basis["phone1"]));
            $this->setElement("eid_name", "static", "所属拓展员", array("value" => $basis["username"]));
            if ($basis["cost"])
                $this->setElement("cost", "hidden", "", array("value" => $basis["cost"] * 100));
            if ($basis["rising_cost"])
                $this->setElement("rising_cost", "hidden", "", array("value" => $basis["rising_cost"] * 100));
        }
        else
        {
//             $sql = "select b.name,b.phone1,b.identity,b.id,b.eid,u.username from 
//                     basis b, users u where b.eid=u.id and b.status=0 and b.sid=".$this->sid;
            $sql = "call card_find_basis(".$this->sid.", null, null)";
//             dump(parse_autocomplete($sql));
//             dump(M()->getLastSql());
            $this->setElement("bname", "autocomplete", "客户姓名", array("bool" => "required",
                    "placeholder" => "请输入客户姓名/联系电话/身份证号",
                    "list" => parse_autocomplete($sql),
                    "ext" => 'count="3" val_callback="get_basis()"',
                    "form" => "noform",
                    "element_cols" => 6,
            ));
            $this->setElement("eid_name", "static", "所属拓展员", array("value" => "", 
                    "label_cols" => 2, "element_cols" => 2,
            ));
        }
        
        $this->setElement("bid", "hidden", "所属客户", array("bool"=> "required", "value" => $bid));
        $this->setElement("eid", "hidden", "", array("value" => $basis["eid"]));
    }
    
    /*逾期卡片提醒
     注释：站点和财务使用的平台，呈现当期卡片离最后还款日还有2天的数据*/
   public function due_reminder()
    {
    	$sType = "NULL";
    	$sVal = "NULL";
    	if (IS_POST && $_POST["search_type"] && $_POST["search_key"])
    	{
    	$sType = "'".$_POST["search_type"]."'";
    	$sVal = "'".$_POST["search_key"]."'";
    	}
    
    	$this->setNav("&nbsp;->&nbsp;逾期提醒");
    	$this->mainPage("due_reminder");
    	$this->setFind("item 1", array("name" => "search_type", "type" => "select",
    			"default" => "客户姓名", "defval" => "name",
    			"list" => parse_select_list("array", array("card"),
    					array("卡片卡号"))));
    
    
    	$this->setData("close_op", 1);
    	$this->setData("close_chkall", 1);
    	$this->setCustomList("pro_card_balance", true, array("NULL", "NULL", 4, $this->sid, $sType, $sVal));
    
    	$this->setTitle(array("客户姓名","卡号","首期账单日","最后还款日","当期授信","已还额度","剩余未还","距离最后还款日还剩余","备注"));
    	$this->setField(array("name","card","bill","cend","amount","repay_rmb","remaining_rmb","datediff","state"));
    	$this->setField("card", array("name" => 1, "url" => U("Card/index")."&form=info&list=1&bid=[bid]&where='id=[cid]'",
    	 "pop" => CardController::getPop("info")));
    	$this->setData("data_field 7 class", "kyo_red");
    	/*$this->setField("repay_rmb", array("name" => 4, "url" => U("Card/Card/progressRepay")."&cday=[cday]&cend=[cend]&code=[ccode]",
    			"pop" => "w:1100,h:550,n:'repayinfo',t:[ccode] 还款明细"));
    	$this->setField("expend_rmb", array("name" => 6, "url" => U("Card/Card/progressDeal")."&bill=[bill]&code=[ccode]",
    			"pop" => "w:1100,h:550,n:'dealinfo',t:[ccode] 交易明细"));*/
    
    	$this->display();
    }
    
    
    
    
}
?>