<?php
namespace KyoCommon\Controller;
use Think\Controller;
use Common\Controller\ListPage;
use Common\Controller\Form;
use Common\Controller\FormElement;
use Common\Controller\SmallDataList;

class DealController extends ListPage
{
    private $admin;
    private $sid;
    private $uid;
    private $repay_type_name = array("0" => "POS账户","1" => "备付金账户");
    //构造函数
    public function __construct()
    {
        parent::__construct();
        $this->admin = get_user_info("admin");
        $this->sid = get_user_info("sid");
        $this->uid = get_user_info("uid");
    }    
    
    public function index()
    {
        switch ($this->admin)
        {
        	case 6:
        	case 1:
        	case 2:
                $this->sub_deal();
                break;
        	case 3:
                $this->salesman();
        	    break;
        	default:
                $this->deal();
        	    break;
        }
    }
    
    // 获取客户相关弹窗口信息
    static public function getPop($type, $title = "")
    {
        $pop = "";
        switch ($type)
        {
            case "risingInfo":
                if (!$title)
                    $title = "客户增值详细信息";
                $pop = "w:450,h:530,n:'risingInfo',t:" . $title;
                break;
            case "bonus":
                if (!$title)
                    $title = "佣金明细";
                $pop = "w:820,h:450,c:1,n:'bonuswin',t:" . $title;
                break;
            case "abnormal":
                if (!$title)
                    $title = "补全交易卡信息";
                $pop = "w:1000,h:600,c:1,n:'abnormalwin',t:" . $title;
                break;
            default:
                break;
        }
        return $pop;
    }
    
    static public function parse_find_date($name, & $sdate, & $edate)
    {
        if (!$sdate)
            $sdate = date("Y-m-d");
        if (!$edate)
            $edate = date("Y-m-d", strtotime("+1 day", strtotime($sdate)));
        
        if (IS_POST && I("get.find"))
        {
            if ($_POST[$name])
            {
                $find_date = explode(",", $_POST[$name]);
                $_POST[$name] = "";
                $sdate = $find_date[0];
                $edate = $find_date[1];
            }
        }
        else
        {
            if (I("get.sdate"))
                $sdate = I("get.sdate");
            
            if (I("get.edate"))
                $edate = I("get.edate");
        }
    }
    
    public function deal($display = true, $title = "交易数据查询")
    {
        $this->parse_find_date("date", $sdate, $edate);
        
    	$this->setNav("&nbsp;->&nbsp;".$title);
    	$this->mainPage("transaction");
        
        $this->setFind("item date", array("name" => "date", "type" => "date", 
                        "sval" => $sdate, "eval" => $edate));
        $this->setFind("item 1", array("name" => "sid", "type" => "select", "default" => "所有分站",
                "list" => parse_select_list("select id,proxy_sub_name from users where type=6")));
        $this->setFind("item 2", array("name" => "search_type", "type" => "select", 
                "default" => "扣款卡号", "defval" => "card", 
                "list" => parse_select_list("array", array("pcode", "terminal"), 
                        array("内部代码", "POS终端"))));
        
        if ($this->admin == 0 || $this->admin == 9 || $this->admin == 7)
            $kopen = "kopen";
        
        $this->setTool("tool_btn_down", array("txt" => "导出报表", "icon" => "cloud-download",
                "url" => U("KyoCommon/Index/excelExport"),  "bool" => $kopen));
        
        $title = array("pcode" => "内部代码", "sid" => "所属分站", "terminal" => "终端号", "pname" => "商户名称", 
                "bank" => "扣款银行", "bid" => "扣款户名", "card" => "扣款卡号", "rmb" => "交易金额", "date" => "交易时间");
    	$this->setTitle($title);
    	$this->setField(array_keys($title));
        $this->setData("where", "(dates BETWEEN '".$sdate."' and '".$edate."') and bid<>-1");
        
        $this->setData("excel", array("name" => "交易查询报表", "title" => $title));
        $this->setData("subtotal field", array(7));
        $this->setPage("param", "&sdate=".$sdate."&edate=".$edate);
//         $this->setData("data_title 7 sort", "rmb");
        $this->setData("order", "date desc");
    	$this->setData("close_op", 1);
    	$this->setData("close_chkall", 1);
        
        if ($display)
        	$this->display();
    }
    
    public function getSubCode($data, $txt)
    {
        return sqlCol("select sub_code from pos where code='".$data["pcode"]."'");
    }
    
    public function sub_deal()
    {
//         if (I("get.find") && IS_POST)
//         {
//             findIn("sub_code", "pcode", "pos", "code");
//         } 
        
        if ($this->admin == 1 || $this->admin == 6)
            $this->deal(false, "综合查询&nbsp;->&nbsp;交易数据查询");
        else
            $this->deal(false);
        $this->setFind("item 1", array());
        $this->setFind("item 2", array("name" => "search_type", "type" => "select", 
                "default" => "扣款卡号", "defval" => "card", 
                "list" => parse_select_list("array", array("sub_code", "terminal"), 
                        array("POS简码", "POS终端"))));
//         $this->setData("data_field 0 run", "Deal/getSubCode");
        if ($this->admin == 2)
            $this->setData("where", $this->getData("where")." and opid=".$this->uid);
        else
            $this->setData("where", $this->getData("where")." and sid=".$this->sid);
        
        $title = array("sub_code" => "POS简码", "terminal" => "终端号", "pname" => "商户名称", 
                "bank" => "扣款银行", "bid" => "扣款户名", "card" => "扣款卡号", "rmb" => "交易金额", "date" => "交易时间");
        $this->setData("data_title", array());
        $this->setData("data_field", array());
        $this->setData("excel", array("name" => "交易查询报表", "title" => $title));
    	$this->setTitle($title);
    	$this->setField(array_keys($title));
        $this->setData("subtotal field", array(6));
        
        $this->display();
    }
    
    public function salesman()
    {
        $this->deal(false, "交易数据查询");
        $this->setFind("item 1", array());
        $this->setFind("item 2", array("name" => "search_type", "type" => "select", 
                "default" => "扣款卡号", "defval" => "card"));
        $this->setTool("close_btn_down", 1);
        $this->setData("data_title", array());
        $this->setData("data_field", array());
    	$this->setTitle(array("商户名称", "扣款卡号", "发卡行", "交易金额", "交易时间"));
    	$this->setField(array("pname", "card", "bank", "rmb", "date"));
    	$this->setData("close_op", 1);
    	$this->setData("close_chkall", 1);
        $this->setData("order", "date desc");
        $this->setData("where", $this->getData("where")." and eid=".$this->uid);
        $this->setData("subtotal field", array(3));
    	$this->display();    
    }
    
    //划款历史客户增值详细信息弹出框
    public function histroyRisingInfo($code, $rid, $rmb)
    {
        $rf = new Form("");
        $rf->set("close_btn_down", 1);
        
        $card = sqlRow("select b.name,c.card, c.rising_amount_num, c.rising_num, 
                        (c.amount + c.rising_amount_num) as amount, r.amount as cur_rising_amount, 
                        r.rising_date from basis b, card c, rising r 
                        where b.id=c.bid and r.id=".$rid." and c.code='".$code."'");
//         dump(M()->getLastSql());
        
        $rf->setElement("name", "static", "客户姓名", array("label_cols" => 4, "value" => $card["name"]));
        $rf->setElement("card", "static", "增值卡号", array("label_cols" => 4, "value" => format_dis_field($card["card"])));
        $rf->setElement("amount", "static", "现总额度", array("label_cols" => 4, "value" => $card["amount"]." 元"));
        $rf->setElement("rising_amount", "static", "增值额度", array("label_cols" => 4, "value" => $card["rising_amount_num"]." 元"));
        $rf->setElement("rising_amount_num", "static", "增值次数", array("label_cols" => 4, "value" => $card["rising_num"]." 次"));
        $rf->setElement("cur_rising_amount", "static", "本次增值额度", array("label_cols" => 4, "value" => $card["cur_rising_amount"]." 元"));
        $rf->setElement("cur_rmb", "static", "本次实划金额", array("label_cols" => 4, "value" => $rmb." 元"));
        $rf->setElement("rising_date", "static", "本次增值时间", array("label_cols" => 4, "value" => $card["rising_date"]));
        
        echo $rf->fetch();
    }
    
    
    //划账历史字段处理
    public function histroyField(& $data, $txt = "")
    {
//         $data["out_card"] = format_dis_field($data["out_card"]);
        $data["out_card"] = substr($data["out_card"], -4);
        if (!$data["fact_amount"])
        {
            $data["fact_amount"] = "未还款";
            $data["repay_time"] = $data["create_time"];
        }
        
        $rtype = C("REPAYTYPE_TEXT");
        $data["account_type"] = $this->repay_type_name[$data["account_type"]];
        
        if ($data["rtype"] >= 3)
        {
            $rp = new FormElement("link_pop", "link", $data["put_amount"], array(
                       "url" => U("Home/Public/statementBonus")."&typeid=3&histroy=1&date_id=".$data["out_name"], 
                    "pop" => $this->getPop("bonus", $data["put_name"]." ".$rtype[$data["rtype"]]."明细")));
            
            if ($data["rtype"] == 5)
                $rp->_set("url", U("Home/Public/statementBonus")."&typeid=5&histroy=1".$data["put_code"]);
            $data["put_amount"] = $rp->fetch();
            $data["out_name"] = "";
            $data["put_code"] = "";
        }
        else if ($data["rtype"] == 2)
        {
            $rp = new FormElement("link_pop", "link", $data["put_amount"], array(
                       "url" => U("histroyRisingInfo")."&code=".$data["put_code"].
                                    "&rid=".$data["out_name"]."&rmb=".$data["put_amount"], 
                    "pop" => $this->getPop("risingInfo")));
            $data["put_amount"] = $rp->fetch();
            $data["put_code"] = "";
            $data["out_name"] = "";
        }
        $data["rtype"] = $rtype[$data["rtype"]];
        
        return $data["account_type"];
    }
    
    //导出报表格式化字段的回调函数
    public function excelFormatField($data)
    {
        if ($data["fact_amount"] == "")
        {
            $data["fact_amount"] = "未还款";
//             $data["repay_time"] = $data["create_time"];
        }
        
        if ($data["rtype"] >= 2)
        {
            $data["out_name"] = "";
            $data["put_code"] = "";
        }
        $rtype = C("REPAYTYPE_TEXT");
        $data["rtype"] = $rtype[$data["rtype"]];
    }

    //划账历史
    public function repayment()
    {
        $sdate = "";
        $edate = "";
        $this->parse_find_date("repay_time", $sdate, $edate);
        
        if ($this->admin == 1)
            $this->setNav("&nbsp;->&nbsp;财务管理&nbsp;->&nbsp;划账历史查询");
        else if ($this->admin == 6)
            $this->setNav("&nbsp;->&nbsp;综合查询&nbsp;->&nbsp;划账历史查询");
        else
            $this->setNav("&nbsp;->&nbsp;划账历史查询");
        $this->mainPage("repayment_record");
        
        $rtype = C("REPAYTYPE_TEXT");
        
        $this->setFind("item repay_time", array("name" => "repay_time", "type" => "date", 
                        "sval" => $sdate, "eval" => $edate));
        $this->setFind("item 1", array("name" => "sid", "type" => "select", "default" => "所有分站",
                "list" => parse_select_list("select id,proxy_sub_name from users where type=6")));
        $this->setFind("item 2", array("name" => "rtype", "type" => "select", "default" => "所有类型",
                "list" => parse_select_list("array", array_keys($rtype), $rtype)));
        $this->setFind("item 3", array("name" => "search_type", "type" => "select", 
                "default" => "划款卡号", "defval" => "out_card", 
                "list" => parse_select_list("array", array("out_name", "put_name", "put_card"), 
                        array("划款户名", "收款户名", "收款卡号"))));
        
        if ($this->admin == 0 || $this->admin == 9 || $this->admin == 7)
            $kopen = "kopen";
        
        $this->setTool("tool_btn_down", array("txt" => "导出报表", "icon" => "cloud-download",
                "url" => U("KyoCommon/Index/excelExport"),  "bool" => $kopen));
        
        $this->setData("close_op", 1);
        
        $this->setData("order", "repay_time desc");
        $this->setData("close_chkall", 1);
        $this->setTitle(array("所属分站", "账号类型","划款户名","划款银行","划款账号","划账类型", "收款户名","收款银行","收款账号","应划金额","实划金额","划账时间"));
        $this->setField(array("sid", "account_type","out_name","out_bank","out_card","rtype", "put_name","put_bank","put_card","put_amount","fact_amount","repay_time"));
        $this->setData("where", "(create_time BETWEEN '".$sdate."' and '".$edate."')");
        
        if ($this->admin > 0 && $this->admin < 7)
        {
            $this->setFind("item 1", array());
            $this->setData("where", "(repay_time BETWEEN '".$sdate."' and '".$edate."') and sid=".$this->sid." and state=0");
            $this->setDataHide(array(0));
            $this->setData("subtotal field", array(8,9));
        }
        else
            $this->setData("subtotal field", array(9,10));
        $this->setData("data_field 1 run", "Deal/histroyField");
        $this->setData("excel", array("name" => "划款历史报表", "call" => array("run", "KyoCommon/Deal/excelFormatField")));
        $this->setData("data_field 9 class", "text-right");
        $this->setData("data_field 10 class", "text-right");
        $this->setPage("param", "&sdate=".$sdate."&edate=".$edate);
        $this->display();
    }
    
    //拓展员查询划账历史
    public function salesRepayment()
    {
        $sdate = "";
        $edate = "";
        $this->parse_find_date("repay_time", $sdate, $edate);
        
        $this->setNav("&nbsp;->&nbsp;划款历史查询");
        $this->mainPage("repayment_record");
        
        $this->setFind("item repay_time", array("name" => "repay_time", "type" => "date", 
                        "sval" => $sdate, "eval" => $edate));
        $this->setFind("item 3", array("name" => "search_type", "type" => "select", 
                "default" => "卡主姓名", "defval" => "put_name", 
                "list" => parse_select_list("array", array("put_card", "put_code"), array("卡号", "内部代码"))));
        
        $this->setData("close_op", 1);
        
        $this->setData("order", "repay_time desc");
        $this->setData("close_chkall", 1);
        $this->setTitle(array("内部代码", "卡主姓名", "发卡行", "卡号", "划款金额","还款时间"));
        $this->setField(array("put_code","put_name","put_bank", "put_card", "fact_amount", "repay_time"));
        $this->setData("where", "(repay_time BETWEEN '".$sdate."' and '".$edate."') and sid=".$this->sid." 
                and state=0 and rtype=1 and put_card<>1 and fact_amount is not null and fact_amount<>0 and 
                put_code in (select code from card where eid=".$this->uid.")");
        $this->setData("subtotal field", array(4));
        $this->setData("data_field 4 class", "text-right");
        $this->setPage("param", "&sdate=".$sdate."&edate=".$edate);
        $this->display();
    }

    
    //前6后四同分站同pos交易记录匹配不到卡的数据  选择此交易卡信息
    public function abnormalHandle($tid)
    {
        if (I("get.code"))
        {
            $card = sqlRow("select bid,eid,opid,bank,card from card where code='".I("get.code")."'");
            if (!$card)
                $this->ajaxReturn(array("echo" => 1, "info" => "非法操作!"));
            $ret = M("transaction")->where("id=".$tid)->save($card);
            $this->ajaxReturn(array("echo" => 1, "close" => 1,  "info" => "成功匹配此交易记录!", 
                    "url" => session("prev_urltransaction"), "tag" => ".transaction"));
            exit(0);
        }
        
        $deal = sqlRow("select u.proxy_sub_name,t.sub_code,t.pname,t.business,t.terminal,t.rmb,t.pcode,
                    t.card,t.date,t.dates from users u,transaction t where u.id=t.sid and t.id=".$tid);
//         dump(M()->getLastSql());
        $form = new Form("");
        $form->set("cols", 2);
        $form->set("close_btn_down", 1);
        $form->setElement("deal_group", "group", "交易信息");
        $form->setElement("sid_info", "static", "所属分站", array("value" => $deal["proxy_sub_name"]));
        $form->setElement("sub_code_info", "static", "POS简码", array("value" => $deal["sub_code"]));
        $form->setElement("pname_info", "static", "商户名称", array("value" => $deal["pname"]));
        $form->setElement("business_info", "static", "商户号", array("value" => $deal["business"]));
        $form->setElement("terminal_info", "static", "终端号", array("value" => $deal["terminal"]));
        $form->setElement("rmb_info", "static", "交易金额", array("value" => $deal["rmb"]));
        $form->setElement("deal_time_info", "static", "交易时间", array("value" => $deal["date"]));
        $form->setElement("create_time_info", "static", "抓取时间", array("value" => $deal["dates"]));
        
        $data = new SmallDataList("abnormal", "", 0, array("page" => array("size" => 100), "close_down_page" => 1));
        $sql = "select b.name,u.username,c.bank,c.card,c.code,d.rmb,d.deal_time 
                    from basis b,users u,card c,deal_inspect d where b.id=c.bid and u.id=c.opid and 
                    c.card like '".$deal["card"]."' and d.pcode='".$deal["pcode"]."' and c.code=d.code
                    and DATE_FORMAT(d.deal_time,'%Y-%m-%d')='".date("Y-m-d")."'";
        $inspect = sqlAll($sql);
        $data->set("data_list", $inspect);
        $data->set("close_op", 0);
        $data->setTitle(array("内部代码", "卡主姓名","发卡行","卡号", "交易金额","推送时间","操作人员"));
        $data->setField(array("code","name","bank","card","rmb","deal_time","username"));
        $data->setOp("选择匹配", U()."&tid=".$tid."&code=[code]", array("query" => true));
        
		$form->setElement("card_info", "group", "卡片列表", array("class" => "text-center", 
				"back_ext" => '<div class="col-md-12">'.$data->fetch()."</div>",
		));
        
        echo $form->fetch();
    }
    
    //前6后四同分站同pos交易记录匹配不到卡的数据 多半情况为操作员没有按推荐金额刷
    public function abnormalData()
    {
        $this->setNav("&nbsp;->&nbsp;交易异常列表");
        $this->mainPage("transaction");
        $this->set("close_tool", 1);
        $this->set("close_top_ctrl", 1);
        
        $this->setData("order", "date desc");
        $this->setData("close_chkall", 1);
        
        $this->setOp("异常处理", U("Deal/abnormalHandle")."&tid=[id]",
                array("pop" => $this->getPop("abnormal")));
        $this->setTitle(array("所属分站", "POS简码", "商户名称", "商户号", "终端号", "交易金额", "交易时间"));
        $this->setField(array("sid", "sub_code", "pname", "business", "terminal", "rmb", "date"));
        $this->setData("where", "bid=-1 and DATE_FORMAT(date,'%Y-%m-%d')='".date("Y-m-d")."'");
        $this->display();    
    }
}
