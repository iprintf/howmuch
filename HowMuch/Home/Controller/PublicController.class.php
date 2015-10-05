<?php
namespace Home\Controller;
use Think\Controller;
use Common\Controller\Form;
use Common\Controller\FormElement;
use Common\Controller\SmallDataList;
use Common\Controller\MainPanel;

class PublicController extends Controller
{
    private $admin;
    private $uid;
    private $sid;
    
    //构造函数
    public function __construct()
    {
        parent::__construct();
        if (!is_login())
            $this->redirect("Home/Index/index");
        $this->admin = get_user_info("admin");
        $this->uid = get_user_info("uid");
        $this->sid = get_user_info("sid");
    }   
    
    // --------------综合卡片表数据获取-------------------
    public function _cardOpCount($data, $txt)
    {
        $url = array("Lifecycle/index", "Lifecycle/expiring", "Lifecycle/expire", "Sos/index", 
                "Rescind/index", "Rising/index", 8 => "Surplus/index");
        foreach ($data as $key => $val)
        {
            if ($val == 0)
                continue;
            
            if ($this->admin == 3)
                $num = sqlCol("call pro_card_duemain(0,99,".$val.",null,".$this->uid.")");
            else
                $num = sqlCol("call pro_card_duemain(0,99,".$val.",".$this->sid.",null)");
//             dump($num);
            $obj = new FormElement($key."_lnk", "link", $num, array("url" => U("Card/".$url[$val - 1])."&ibody=1", "tag" => "#body"));
//             dump($obj->fetch());
            if ($key == "Sos")
                $obj->_set("class", "kyo_red");
            $data[$key] = $obj->fetch();
        }

        $perm = array(3 => 10, 1 => 11, 6 => 12);
        //审核，不通过等统计信息
        $total = sqlRow("call Report_sub(0, 10, ".$perm[$this->admin].", ".$this->uid.");");
//         dump(M()->getLastSql());
        foreach ($total as $key => $val)
        {
            $data[$key] = $val;
        }
        $obj = new FormElement("pos_lnk", "link", sqlCol("call pro_card_balance(0, 99, NULL, NULL, 4, ".$this->sid.", NULL, NULL);"), 
                array("url" => U("Card/Card/due_reminder")."&ibody=1&error=1", "tag" => "#body", "class" => "kyo_red"));
        $data["reminder"] = $obj->fetch();
        
        $obj = new FormElement("pos_lnk", "link", sqlCol("select count(id) from pos where sid=".$this->sid." and status=".ERROR), 
                array("url" => U("Pos/Pos/Index")."&ibody=1&error=1", "tag" => "#body"));
        $data["pos"] = $obj->fetch();
        
        return $data["card_2_num"]; 
    }
    
    static public function cardOpCount()
    {
        $admin = get_user_info("admin");
        $data = new MainPanel("cardOp", "", 
                array("close_num" => 1,"page" => array("size" => 5)));
        $field = array("card_2_num" => 0, "card_3_num" => 0, "card_4_num" => 0, "basis_3_num" => 0, "Sos" => 4, 
                "reminder" => 0, "surplus" => 9, "pos" => 0, "rising" => 6, "due" => 1, "expire" => 3, "expiring" => 2, "rescind" => 5);
        $data->set("data_list", array(0 => $field));
        $data->setTitle(array("待初审卡", "待终审卡", "未通过卡", "待审客户", "SOS锁卡", "逾期提醒", "残值验证", "POS故障", "增值卡片",
                                "即期卡片","到期卡片", "失效卡片","解约卡片"));
        $data->setField(array_keys($field));
        $data->set("data_field 0 run", "Public/_cardOpCount");
        if ($admin == 6)
            $data->hide(array(2));
        if ($admin == 3)
            $data->hide(array(5, 6));
        
        return $data->fetch("卡片综合简报");
    }
    
    // --------------昨日操作简报——卡操作差数弹出框list-------------------
    public function opPoorCard()
    {
        $data = new SmallDataList("reapypoor", "", 0, array("page" => array("size" => 10)));
        $data->setCustomList("Report_sub", true, array(8, $this->sid));
//         $data->setTitle(array("日期","所属操作员","客户姓名","发卡行","卡号","推荐笔数", "实际笔数","应交易额","实际交易","交易差额"));//,"实还额度"));
//         $data->setField(array("dates","opname","bname","bank","card","day_pen","s_pen_num", "day_amount","s_rmb","poor_amount"));//,"put_amount"));
        
        $data->setTitle(array("客户姓名","发卡行","卡号","交易推送日期","推荐笔数", "实际笔数","应交易额","实际交易","交易差额","所属操作员"));
        $data->setField(array("bname","bank","card","dates","day_pen","s_pen_num", "day_amount","s_rmb","poor_amount","opname"));
        echo $data->fetch();
    }    
    
    // --------------昨日还款简表——实还卡差数弹出框list-------------------
    public function repayPoorCard()
    {
        $data = new SmallDataList("dispoorcard", "", 0, array("page" => array("size" => 8)));
        $data->setCustomList("Report_sub", true, array(7, $this->sid));
//         $data->setTitle(array("推送日期","客户姓名","发卡行","卡号","建议额度","实还额度","还款差额"));
//         $data->setField(array("dates","bname","bank","card","advice","put_amount","poor_amount"));
        $data->setTitle(array("客户姓名","发卡行","卡号","还款推送日期","当期授信额度","昨日建议还款","昨日实际还款","昨日还款差额"));
        $data->setField(array("bname","bank","card","dates","total_amount","amount","put_amount","poor_amount"));
        echo $data->fetch();
    }    
    
    //今日POS入账弹出框
    public function posRmbInfo()
    {
        $data = new SmallDataList("posRmb", "", 0, array("page" => array("size" => 12)));
        $data->setCustomList("Report_sub", true, array(3, $this->sid));
        $data->setTitle(array("交易日期","POS简码","商户简称","交易金额","手续费","应入账金额", "入账卡号", "入账日期"));
        $data->setField(array("dates","sub_code","abbr_name","drmb","pos_cost_rmb","prmb","card", "aacc"));
        echo $data->fetch();
    }
    
    //应还卡个数详细信息弹出框
    public function repayCardNum()
    {
        $data = new SmallDataList("repaycard", "", 0, array("page" => array("size" => 10)));
        $data->setCustomList("Report_sub", true, array(13, $this->sid));
        
//         $data->setTitle(array("推送日期","客户姓名","发卡行","卡号","建议额度","实还额度","还款差额"));//,"实还额度"));
//         $data->setField(array("dates","bname","bank","card","amount","put_amount","poor_amount"));//,"put_amount"));
        $data->setTitle(array("客户姓名","发卡行","卡号","还款推送日期","当期授信额度","当日建议还款","当日实际还款","当日还款差额"));
        $data->setField(array("bname","bank","card","dates","total_amount","amount","put_amount","poor_amount"));
        echo $data->fetch();
    }
    
    //应操作卡个数详细信息弹出框
    public function operCardNum()
    {
    	$data = new SmallDataList("repaycard", "", 0, array("page" => array("size" => 10)));
    	$data->setCustomList("Report_sub", true, array(21, $this->sid));
    
//     	$data->setTitle(array("日期","所属操作员","客户姓名","发卡行","卡号","推荐笔数", "实际笔数","今应交易","实际交易","交易差额"));//,"实还额度"));
//     	$data->setField(array("dates","opname","bname","bank","card","day_pen","s_pen_num", "day_amount","s_rmb","poor_amount"));//,"put_amount"));
    	$data->setTitle(array("客户姓名","发卡行","卡号","交易推送日期","推荐笔数", "实际笔数","应交易额","实际交易","交易差额","所属操作员"));
    	$data->setField(array("bname","bank","card","dates","day_pen","s_pen_num", "day_amount","s_rmb","poor_amount","opname"));

    	echo $data->fetch();
    }
    
    
   //拓展员佣金明细弹出框代码
    public function statementBonus()
    {
        $typeID = I("get.typeid");
        $bonus_txt = array(6 => "续约", 3 => "签单", 5 => "服务", 4 => "增值");
        $txt = $bonus_txt[$typeID];
        $data = new SmallDataList("statementBonusList", "", 0, 
                array("close_num" => 1,"page" => array("size" => 8)));
        
        $type = 3;
        if (I("get.histroy"))
            $type = 4;
        
        if (I("get.date_id"))
            $data->setCustomList("pro_expand_bonus", true, array($type, I("get.date_id"), 'null'));
        else
            $data->setCustomList("pro_expand_bonus", true, array(2, I("get.dates"), I("get.uid")));
//         dump(M()->getLastSql());
        
        $data->setPage("param", "small=statementBonusList&histroy=".I("get.histroy")."&typeid=".$typeID.
                        "&date_id=".I("get.date_id")."&dates=".I("get.dates")."&uid=".I("get.uid"));
        $data->setTitle(array("数据生成日","客户姓名","发卡行","卡号",$txt."额度",$txt."费率",$txt."期数","佣金扣率","佣金金额"));
        $data->setField(array("dates","bname","bank","card","amount","card_cost", "agreement","bonus_cost","bonus_rmb"));
        if ($typeID == 4)
            $data->hide(array(6));
        
        echo $data->fetch();
    }    
    
    //站长财务通用报表
    static public function commonBriefing()
    {
        $sid = get_user_info("sid");
        // -----------------------------index-content1-今日工作简报---------------------
        $data = new MainPanel("day_work", "", array("close_num" => 1));
        $data->setCustomList("Report_sub", false, array(1, $sid));
        $data->setTitle(array("日期","应操作卡张数","应还款卡张数","应还款总额","今日POS入账","应新增资金"));
        $data->setField(array("dates","card_oper_num","card_num","amount","pos_rmb","new_rmb"));
        $data->setField("card_oper_num", array("name" => 1, "url" => U("Public/operCardNum"),
        		"pop" => "w:1100,h:550,n:'operinfo',t:今应操作卡-时时进度信息"));
        $data->setField("card_num", array("name" => 2, "url" => U("Public/repayCardNum"),
                "pop" => "w:1100,h:550,n:'repayinfo',t:应还款卡详细信息"));
        $data->setField("pos_rmb", array("name" => 4, "url" => U("Public/posRmbInfo"),
                "pop" => "w:1100,h:550,n:'posrmbinfo',t:今日POS入账详细信息"));
        $html = $data->fetch("今日工作简报");
        
        //卡片综合简报
        $html .= self::cardOpCount();
        
        // -----------------------------index-content2-昨日还款简报------------------------------------------
        $data = new MainPanel("yesterdayrepay", "", array("close_num" => 1));
        $data->setCustomList("Report_sub", false, array(5, $sid));
        $data->setTitle(array("日期","应还卡张数","还卡完成数","还卡未完成","应还款总额","实还款总额","还款差额"));
        $data->setField(array("dates","y_card_num","s_card_num","poor_card","y_amount","s_amount","poor_amount"));
        $data->setField("poor_card", array("name" => 3, "url" => U("Public/repayPoorCard"), 
                        "pop" => "w:1100,h:550,n:'dealinfo',t:昨日未还款卡详细信息"));
        $html .= $data->fetch("昨日还款简报");
        
        // -----------------------------index-content3-昨日操作简报------------------------------------------
        $data = new MainPanel("yesterdayop", "", array("close_num" => 1));
        $data->setCustomList("Report_sub", false, array(4, $sid));
        $data->setTitle(array("日期","应操作张数","操作完成数 ","操作未完成","应交易总额","实交易总额","交易差额"));
        $data->setField(array("dates","y_ccode","s_ccode","poor_card","y_amount","s_rmb","poor_amount"));
        $data->setField("poor_card", array("name" => 3,"url" => U("Public/opPoorCard"),
                "pop" => "w:1100,h:550,n:'yedayopinfo',t:昨日未操作卡详细信息"));
        $html .= $data->fetch("昨日操作简报");
        
        // -----------------------------index-content4-昨日收支简报-----------------------------------

        $data = new MainPanel("yesterdayincome", "", array("close_num" => 1));
        $data->setCustomList("Report_sub", false, array(15, $sid));
        $data->setTitle(array("日期","签约总收费","增值总收费", "拓展签单佣金", "拓展增值佣金","操作增值佣金", "签增减佣小计"));
        $data->setField(array("dates","signing_profit","rising_profit", "signing_cost_rmb", "rising_cost_rmb","oper_rising_rmb","sum"));
        
        $sub_data = new SmallDataList("yesterdayincome_bonus", "", 0, array("close_num" => 1));
        $sub_data->setCustomList("Report_sub", false, array(16, $sid));
        $sub_data->setTitle(array("日期","操作交易额","服务费收益","服务费--拓展", "服务费--操作", "POS交易成本","操作收益小计"));
        $sub_data->setField(array("dates","rmb","trmb","bonus_cost_rmb", "oper_cost_rmb", "pos_cost_rmb","sum"));
        
        $html .= $data->fetch("昨日收支简报", array("body_end" => "<br />".$sub_data->fetch()));
        
      
        return $html;   
    }
    
    static public function commonReport(& $report)
    {
        $report->setReport("basis", "客户/卡片/卡额报表", "0", 
                array("pro" => "Report_total",                // 此报表执行的存储过程名称
                "arg" => "[sdate],[edate],1,[proxy],[sid]",                // 此报表执行存储过程的参数
                                 // "url" => U(), //如果此报表显示有特殊情况 外链地址
                "dtitle" => array("日期","签约客户数","签约卡片数","签约额度","增值卡片数","增值总额","新增客户数","新增卡片数","新增额度"),
                "field" => array("dates","basis_num","card_num","card_amount","rising_num","rising_amount","basis_add_num","card_add_num","card_add_amount")));
        
        $report->setReport("repay", "财务还款明细报表", "0", 
                array("pro" => "Report_total",                // 此报表执行的存储过程名称
                "arg" => "[sdate],[edate],9,[proxy],[sid]",                // 此报表执行存储过程的参数
                                 // "url" => U(), //如果此报表显示有特殊情况 外链地址
                "dtitle" => array("日期","客户姓名","发卡行","卡号","应还额度","实还额度","总期数","剩余期数"),
                "field" => array("dates","bname","bank","card","amount","put_amount","installment","remaining_period")));
        
        $report->setReport("oper", "卡片操作明细报表", "0", 
                array("pro" => "Report_total",                // 此报表执行的存储过程名称
                "arg" => "[sdate],[edate],8,[proxy],[sid]",                // 此报表执行存储过程的参数
                "dtitle" => array("日期","所属操作员","卡内部编码","客户姓名","发卡行","卡号","推荐笔数", "实际笔数","今应交易","实际交易","交易差额", "实际成本"),
                "field" => array("dates","opname","ccode","bname","bank","card","day_pen","s_pen_num", "day_amount","s_rmb","poor_amount", "s_pos_cost")));
        
        $report->setReport("pos_deal", "POS交易/成本报表", "0", 
                array("pro" => "Report_total",                // 此报表执行的存储过程名称
//                 "excel" => true, 
                "arg" => "[sdate],[edate],3,[proxy],[sid]",                // 此报表执行存储过程的参数
                                 // "url" => U(), //如果此报表显示有特殊情况 外链地址
                "dtitle" => array("日期","POS简码","终端号","商户名","POS费率","交易金额","MCC支出"),
                "field" => array("dates","sub_code","models","pos_name","pos_cost","pos_rmb","pos_cost_rmb")));
        
        $report->setReport("earnings", "成本/收益/余额报表", "0", 
                array("pro" => "Report_total",                // 此报表执行的存储过程名称
                "arg" => "[sdate],[edate],5,[proxy],[sid]",                // 此报表执行存储过程的参数
                "num" => 0, "pagesize" => 0,
                "h" => 200,                // 报表窗口宽高, 默认宽840,高为550
                "dtitle" => array("日期","签约总佣金","增值总佣金","拓展签单佣金","拓展增值佣金","操作增值佣金","签单/增值佣金-小计","服务费收益","服务费-拓展","服务费-操作","POS交易成本","服务费佣金-小计"),
                "field" => array("dates","signing_profit","rising_profit","signing_cost_rmb","rising_cost_rmb","oper_rising_rmb","qd_sum","card_server","bonus_cost_rmb","oper_cost_rmb","pos_cost_rmb","ser_sum")));
        
        
        $report->setReport("salesman", "拓展员佣金报表", "0", 
                array("pro" => "Report_total",                // 此报表执行的存储过程名称
                "arg" => "[sdate],[edate],2,[proxy],[sid]",                // 此报表执行存储过程的参数
                "dtitle" => array("日期","拓展员","签约卡数","签约额度","签单佣金","增值卡数",
                        "增值佣金","续约卡数","续约佣金","服务佣金","佣金小计"),
                "field" => array("dates","ename","card_num","amount","signing_cost_rmb", 
                        "rising_card_num", "rising_cost_rmb", "continue_card_num", "continue_cost_rmb",
                         "bonus_cost_rmb", "sum")));
        
        
        $report->setReport("oper_bonus_rmb", "操作员佣金报表", "0",
        		array("pro" => "Report_total",                // 此报表执行的存储过程名称
        				"arg" => "[sdate],[edate],10,[proxy],[sid]",                // 此报表执行存储过程的参数
        				"dtitle" => array("日期","操作员","总交易卡数", "总交易笔数", "总交易金额", "工作佣金", "增值卡数","增值金额","增值佣金","佣金小计"),
        				"field" => array("dates","username","card_num", "total_card_num","trmb", "bonus_cost_rmb","rising_card_num","rising_amount","rising_cost_rmb","cost_sum")));
        
        $report->setReport("pos_cost_sum1", "同费率POS交易量", "0",
        		array("pro" => "Report_total",                // 此报表执行的存储过程名称
        				"arg" => "[sdate],[edate],11,[proxy],[sid]",                // 此报表执行存储过程的参数
        				"dtitle" => array("日期","合作分站","POS费率","POS数量","交易金额","支出手续费","可入帐金额"),
        				"field" => array("dates","sub_name","cost","models_num","trmb","pos_cost_server","sum")));
        
        $report->setReport("sub_month_tran", "分站月交易数据报表", "0",
        		array("pro" => "Report_total",                // 此报表执行的存储过程名称
        				"arg" => "[sdate],[edate],13,[proxy],[sid]",                // 此报表执行存储过程的参数
//                         "excel" => true,
        				"dtitle" => array("数据日期","合作分站","月交易总额","月新增卡数","月新增卡额","月增值卡数","月增值卡额","月解约卡数","月解约卡额"),
        				"field" => array("dates","sub_name","trmb","card_num","amount","rising_num","rising_amount","due_card_num","due_amount")));

    }
    
}