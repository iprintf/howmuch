<?php
namespace KyoCommon\Controller;
use Think\Controller;
use Common\Controller\ListPage;
use Common\Controller\Form;
use Common\Controller\FormElement;
use Common\Controller\TabPage;
use Common\Controller\SmallDataList;

class InspectController extends Controller
{
    private $admin;
    private $sid;
    private $uid;
    
    //构造函数
    public function __construct()
    {
        parent::__construct();
        if (!is_login())
            $this->redirect("Home/Index/index");
        $this->admin = get_user_info("admin");
        $this->sid = get_user_info("sid");
        $this->uid = get_user_info("uid");
    }    
    
    //获取核查数据查询的信用卡基本信息
    public function getBaseInfo($card)
    {
        $base = new Form("", array("cols" => 2));
        $base->set("close_btn_down", 1);
        
        $lnk_basis = new FormElement("lnk_basis", "link", $card["name"], array(
                "url" => U("Card/Basis/index")."&form=info&list=1&where='id=".$card["bid"]."'",
                "pop" => \Card\Controller\BasisController::getPop("info")));
                
        
        $lnk_card = new FormElement("lnk_card", "link", format_dis_field($card["card"]), array(
            "url" => U("Card/Card/index")."&form=info&list=1&bid=".$card["bid"]."&where='id=".$card["id"]."'",
            "pop" => \Card\Controller\CardController::getPop("info")));
        
        $cdate = explode("-", $card["effective_date"]);
        
        $base->setElement("static", "static", "卡片状态", array("value" => get_status_txt($card["status"])));
        $base->setElement("proxy", "static", "所属代理商", array("value" => get_sub_name($card["proxy_id"])));
        $base->setElement("code", "static", "内部代码", array("value" => $card["code"]));
        $base->setElement("sub", "static", "所属分站", array("value" => $card["proxy_sub_name"]));
        $base->setElement("bname", "static", "卡主姓名", array("value" => $lnk_basis->fetch()));
        $base->setElement("eid", "static", "所属拓展员", array("value" => $card["username"]));
        $base->setElement("bank", "static", "发卡银行", array("value" => $card["bank"]));
        $base->setElement("opid", "static", "所属操作员", array("value" => get_username($card["opid"])));
        $base->setElement("card", "static", "卡号", array("value" => $lnk_card->fetch()));
        $base->setElement("cost", "static", "服务费率", array("value" => ($card["cost"] * 100)." %"));
        $base->setElement("amount", "static", "授信额度", array("value" => $card["amount"]." 元"));
        $base->setElement("rising_cost", "static", "增值费率", array("value" => ($card["rising_cost"] * 100)." %"));
        $base->setElement("bill", "static", "出账单日", array("value" => $card["bill"]." 号"));
        $base->setElement("agreement", "static", "服务期数", array("value" => $card["agreement"]." 期"));
        $base->setElement("end_date", "static", "最后还款日", array("value" => "T + ".$card["finally_repayment_date"]));
        $base->setElement("times", "static", "签约日期", array("value" => $card["times"]));
        $base->setElement("cdate", "static", "卡片有效期", array("value" => $cdate[1]." / ".$cdate[0]));
        $base->setElement("due_date", "static", "到期日期", array("value" => $card["due_date"]));
        
        return $base->fetch();
    }
    
    public function getRepayInfo($card, $date_sql)
    {
  		$repay = new SmallDataList("card_repayment", "repayment_record", 0, array("page" => array("size" => 12)));
        $repay->set("where", "put_card='".$card."' and repay_time ".urldecode($date_sql)." ");
        $repay->set("order", "repay_time desc");
        $repay->setPage("url", "KyoCommon/Inspect/getRepayInfo");
        $repay->setPage("param", "code=".$code."&date_sql=".$date_sql);
		$repay->setTitle(array("账号类型", "划款户名", "划款银行", "划款卡号", "应划金额", "实划金额", "划款时间", "操作人员"));
		$repay->setField(array("account_type", "out_name", "out_bank", "out_card", "put_amount", "fact_amount", "repay_time", "opid"));  
        $repay->set("subtotal field", array(4, 5));
        return $repay->fetch();
    }
    
    public function getPeriodInfo($code)
    {
  		$period = new SmallDataList("card_installment_list", "card_installment", 0, array("page" => array("size" => 12)));
        $period->set("tooltip", 1);
        $period->set("where", "code='".$code."'");
        $period->setPage("url", "KyoCommon/Inspect/getPeriodInfo");
        $period->setPage("param", "code=".$code);
		$period->setTitle(array("期数起始", "总期数", "随机期数", "还款日期"));
		$period->setField(array("start_date", "total", "installment", "days"));  
        return $period->fetch();
    }
    
    public function getDealInfo($card, $date_sql)
    {
  		$deal = new SmallDataList("card_deal", "transaction", 0, array("page" => array("size" => 12)));
        $deal->set("where", "card='".$card."' and dates ".urldecode($date_sql)." ");
        $deal->set("order", "dates desc");
        $deal->setPage("url", "KyoCommon/Inspect/getDealInfo");
        $deal->setPage("param", "card=".$card."&date_sql=".$date_sql);
		$deal->setTitle(array("POS代码", "POS简码", "小票名称", "交易金额", "交易时间"));
		$deal->setField(array("pcode", "sub_code", "pname", "rmb", "dates"));  
        $deal->set("data_field 1 run", "Inspect/getPosMes");
        $deal->setField("pname", array("name" => "2", "url" => U("Pos/Pos/index")."&form=info&list=1&where='code='[pcode]'",
                "pop" => \Pos\Controller\PosController::getPop("info", "[terminal] POS详细信息")));
        $deal->set("subtotal field", array(3));
        return $deal->fetch();
    }
    
    public function getDealPushInfo($code, $date_sql)
    {
  		$deal_push = new SmallDataList("card_deal_push", "deal_inspect", 0, array("page" => array("size" => 12)));
        $deal_push->set("where", "code='".$code."' and deal_time ".urldecode($date_sql)." ");
        $deal_push->set("order", "deal_time desc");
        $deal_push->setPage("url", "KyoCommon/Inspect/getDealPushInfo");
        $deal_push->setPage("param", "code=".$code."&date_sql=".$date_sql);
		$deal_push->setTitle(array("POS代码", "POS简码", "小票名称", "建议金额", "推送时间"));
		$deal_push->setField(array("pcode", "sub_code", "pname", "rmb", "deal_time"));  
        $deal_push->set("data_field 1 run", "Inspect/getPosMes");
        $deal_push->setField("pname", array("name" => "2", "url" => U("Pos/Pos/index")."&form=info&where='code='[pcode]'",
                "pop" => \Pos\Controller\PosController::getPop("info", "POS详细信息")));
        $deal_push->set("subtotal field", array(3));
        return $deal_push->fetch();
    }
    
    public function getFinaceInfo($code)
    {
  		$finance = new SmallDataList("card_finance", "card_finance", 0, array("page" => array("size" => 12)));
        $finance->setPage("url", "KyoCommon/Inspect/getFinaceInfo");
        $finance->setPage("param", "code=".$code);
        $finance->setCustomList("pro_card_balance", true, array("NULL", "NULL", 1, "NULL", "'code'", "'".$code."'"));
        $finance->setTitle(array("所处期数","是否逾期","当期授信", "已还款额","剩余应还","还款已用","还款未用","交易笔数", "交易成本"));
        $finance->setField(array("dates","expire", "amount", "repay_rmb","remaining_pay_rmb","expend_rmb","remaining_rmb","pen_num","pos_cost_rmb"));
        $finance->set("subtotal field", array(3, 4, 5, 6, 7, 8));
        return $finance->fetch();
    }
    
    public function getPosMes(& $data, $txt = "")
    {
        if ($data["sub_code"])
        {
            $start_time = date("Y-m-d H:i:s", strtotime("-30 minute", strtotime($data["date"])));
            $end_time = date("Y-m-d H:i:s", strtotime("+30 minute", strtotime($data["date"])));
            $bool = sqlCol("select count(id) from deal_inspect where pcode='".$data["pcode"]."' and 
                    code in (select code from card where card='".$data["card"]."') and rmb=".$data["rmb"]." 
                    and (deal_time between '".$start_time."' and '".$end_time."')");
            if ($bool)
                $data["rmb"] = '<span class="kyo_red">'.sprintf("%.1f", $data["rmb"]).'</span>';
        }
        else
        {
           $pos = sqlRow("select sub_code,abbr_name from pos where code='".$data["pcode"]."'"); 
           $data["sub_code"] = $pos["sub_code"];
           $data["pname"] = $pos["abbr_name"];
        }
        return $data["sub_code"];
    }
    
    //卡片数据核查功能
    public function index()
    {
        if (IS_POST)
        {
//             $this->ajaxReturn(array("echo" => 1, "info" => "hello".$_POST["search_key"]." ".$_POST["start_date"]));
            $this->ajaxReturn(array("tag" => "#data_id", 
                    "url" => U()."&key=".$_POST["search_key"]."&sdate=".$_POST["start_date"].
                            "&edate=".$_POST["end_date"]));
        }
        
        if (!($_GET["key"] && $_GET["sdate"] && $_GET["edate"]))
        {
            $form = new Form("", array("cols" => 2, "class" => "form-horizontal col-sm-12 col-md-12 main_first_row"));
            $form->set("close_btn_down", 1);
            $form->setElement("find_group", "group", "信用卡核查数据查询选项");
            $form->setElement("start_date", "date", "查询选项", array("group" => "start", "value" => date("Y-m-d"),
                    "placeholder" => "请选择核查起始日期", "label_cols" => 2, "element_cols" => 2));
            $form->setElement("end_date", "date", "", array("group" => "mid", "placeholder" => "请选择核查结束日期", 
                    "element_cols" => 2, "value" => date("Y-m-d")));
            $form->setElement("search_key", "string", "", array("group" => "mid", "bool" => "required",
                    "maxlength" => "25", "placeholder" => "请输入要核查的信用卡内部代码/卡号"));
            $form->setElement("btn_search", "button", "核查数据", array("group" => "end", "ext" => 'id="btn_search_id" type="submit"'));
            $form->setElement("data", "static", "", array("close_label" => 1, "element_cols" => 12));
            echo $form->fetch().js_head('
                        $("#btn_search_id").click(function(){
                            $("#search_key_id").val($("#search_key_id").val().replace(/ /g, ""));
                        });');
        }
        else
        {
            $key = $_GET["key"];
            $start_date = $_GET["sdate"];
            $end_date = $_GET["edate"];
            if ($start_date == $end_date)
                $end_date = date("Y-m-d", strtotime("+1 day", strtotime($end_date)));
            $date_sql =  "BETWEEN '".$start_date."' AND '".$end_date."'";
            $date_sql = urlencode($date_sql);
//             dump(urlencode($date_sql));
            
            $card = sqlRow("select c.id,c.status,c.code,c.card,c.opid,c.proxy_id,c.agreement,c.amount,c.bill,
                            c.finally_repayment_date, c.times,c.due_date,c.bid,c.eid,c.bank,c.cost,c.rising_cost,
                            c.effective_date, b.name,u.username, u.proxy_sub_name from card c,basis b,users u where 
                            c.bid=b.id and c.eid=u.id and (c.code='".$key."' or c.card='".$key."')");
            
            if (!$card)
            {
                echo '<h1 style="color:red;font-weight:bold;">您输入的内部代码/卡号&emsp;'.$key.'&emsp;不存在!</h1>';
                exit(0); 
            }
            
//             $finance = new Form("", array("cols" => 2));
//             $finance->set("close_btn_down", 1);
//             $fdata = sqlAll("call pro_card_balance(0, 100, NULL, 'code', '".$card["code"]."')");
//             $fdata = $fdata[0];
//             $finance->setElement("cur_period", "static", "所处期数", array("value" => $fdata["dates"]));
//             $finance->setElement("repay", "static", "已还款额", array("value" => $fdata["repay_rmb"]." 元"));
//             $finance->setElement("excess_repay", "static", "剩余应还", array("value" => $fdata["remaining_pay_rmb"]." 元"));
// //             $finance->setElement("excess_days", "static", "可还款天", array("value" => $fdata["repay_rmb"]." 天"));
//             $finance->setElement("deal_rmb", "static", "还款已消费", array("value" => $fdata["expend_rmb"]." 元"));
//             $finance->setElement("deal_not_rmb", "static", "还款未消费", array("value" => $fdata["remaining_rmb"]." 元"));
//             $finance->setElement("deal_num", "static", "交易笔数", array("value" => $fdata["pen_num"]." 笔"));
//             $finance->setElement("deal_costing", "static", "交易成本", array("value" => $fdata["pos_cost_rmb"]." 元"));
            
            $tab = new TabPage();
            $tab->setTab("base", "基本信息", $this->getBaseInfo($card));
            $tab->setTab("finance", "财务信息", $this->getFinaceInfo($card["code"]));
            $tab->setTab("period", "期数信息", $this->getPeriodInfo($card["code"]));
            $tab->setTab("oper", "操作信息", R("Index/showOpRecord", array($card["code"], "", $date_sql)));
            $tab->setTab("repay", "还款信息", $this->getRepayInfo($card["card"], $date_sql));
            $tab->setTab("deal", "交易信息", $this->getDealInfo($card["card"], $date_sql));
            $tab->setTab("deal_push", "交易推送信息", $this->getDealPushInfo($card["code"], $date_sql));
            echo $tab->fetch();    
        }
    }
}
