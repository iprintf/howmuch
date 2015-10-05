<?php
namespace Home\Controller;
use Common\Controller\ListPage;
use Common\Controller\DataList;
use Common\Controller\MainPanel;
use Common\Controller\SmallDataList;
// use Common\Controller\PageTool;
use Common\Controller\Form;
use Common\Controller\FormElement;
use KyoCommon\Controller\ReportController;


class SalesmanController extends ListPage 
{
    private $sid;
    private $uid;
    
    public function __construct()
    {
        parent::__construct();
        if (get_user_info("admin") != 3)
            $this->redirect("Index/index");
        $this->sid = get_user_info("sid");
        $this->uid = get_user_info("uid");
    }

    public function report()
    {
        $report = new ReportController();
        
        $report->set("group", array("拓展员报表"));
       //第一个参数: 报表标识符 保证唯一性
       //第二个参数: 报表标题
       //第三个参数: 报表属性哪个组, 从零开始计起对应关系
        $report->setReport("repay", "签约拓展员报表", "0", 
                array("pro" => "Report_total",                // 此报表执行的存储过程名称
                "arg" => "[sdate],[edate],6,[proxy],[uid]",                // 此报表执行存储过程的参数
                "pagesize" => 0,                // 报表分页大小 如果为0则代表无分页
                                 // "url" => U(), //如果此报表显示有特殊情况 外链地址
                "w" => 1100,"h" => 500,                // 报表窗口宽高, 默认宽840,高为550
                "dtitle" => array("日期","签约卡数","签约额度","签单佣金","增值卡数",
                        "增值佣金","续约卡数","续约佣金","服务佣金","佣金小计"),
                "field" => array("dates","card_num","amount","signing_cost_rmb", 
                        "rising_card_num", "rising_cost_rmb", "continue_card_num", "continue_cost_rmb",
                         "bonus_cost_rmb", "sum")));
        
        $this->show($report->fetch());
    }    
    
    //拓展员未结算卡/已结算卡(签单/增值)详细信息 $type=3为未结算卡，$type=5 为已结算卡
    public function SettleDetail($type)
    {
        $data = new SmallDataList("unSettleDetail", "", 0, array("page" => array("size" => 10)));
        $data->setCustomList("pro_expand_bonus", true, array($type, 'null', $this->uid));
//         dump(M()->getLastSql());
        $data->setTitle(array("签约日期","客户姓名","发卡行","卡号","额度","客户费率","签约期数","佣金扣率","佣金金额"));
        $data->setField(array("dates","bname","bank","card","amount","card_cost","agreement","cost","card_cost_rmb"));
        $data->setPage("param", "small=unSettleDetail&type=".$type);
        if ($type == 13 || $type == 15 || $type == 17)
            $data->hide(array(6));
        echo $data->fetch();
    }
    
    public function index()
    {
        //综合卡片表
        $html = PublicController::cardOpCount();
        
        // -----------------------------index-content1-本月收益简报---------------------

        $data = new MainPanel("sales_sum_rmb", "", array("close_num" => 1));
        $data->setCustomList("Report_sub", false, array(14, $this->uid));
        $data->setTitle(array("日期","签约卡数","签约额度","签单佣金","增值卡数",
        		"增值佣金","续约卡数","续约佣金","服务佣金","佣金小计"),
        		$data->setField(array("dates","card_num","amount","signing_cost_rmb",
        				"rising_card_num", "rising_cost_rmb", "continue_card_num", "continue_cost_rmb",
        				"bonus_cost_rmb", "sum")));
        
        $data->setField("card_num", array("name" => 1, "url" => U("settleDetail")."&type=16",
        		"pop" => "w:1000,h:500,n:'settleinfo',t:月签约信用卡佣金明细"));
        $data->setField("rising_card_num", array("name" => 4, "url" => U("settleDetail")."&type=17",
        		"pop" => "w:1000,h:500,n:'settleinfo',t:月增值信用卡佣金明细"));
        $data->setField("continue_card_num", array("name" => 6, "url" => U("settleDetail")."&type=18",
        		"pop" => "w:1000,h:500,n:'settleinfo',t:月续约信用卡佣金明细"));
//         $data->setField("continue_card_num", array("name" => 6, "url" => U("Public/statementBonus")."&uid=".$this->uid."&dates=[dates]",
//         		"pop" => "w:1000,h:500,n:'posrmbinfo',t:月服务佣金明细"));
        $html .= $data->fetch("当月佣金简报");
        
        
        $data = new MainPanel("sales_rmb_not", "", array("close_num" => 1));
        $data->setCustomList("pro_expand_bonus", false, array(10, 'null', $this->uid));
        $data->setTitle(array("新签卡-未结","新签额","新签佣金-未结", "新增值卡-未结", "新增值佣金-未结","续约卡-未结","续约额","续约佣金-未结", "佣金小计"));
        $data->setField(array("card_num","amount","signing_cost_rmb", "rising_card_num", "rising_cost_rmb","renewal_card_num",
        				"renewal_amount","renewal_cost_rmb","total_card_rmb"));
        
        $data->setField("card_num", array("name" => 0, "url" => U("settleDetail")."&type=12",
                "pop" => "w:1000,h:500,n:'settleinfo',t:新签约信用卡未结算佣金明细"));
        $data->setField("rising_card_num", array("name" => 3, "url" => U("settleDetail")."&type=13",
                "pop" => "w:1000,h:500,n:'settleinfo',t:新增值信用卡未结算佣金明细"));
        $data->setField("renewal_card_num", array("name" => 5, "url" => U("settleDetail")."&type=19",
        		"pop" => "w:1000,h:500,n:'settleinfo',t:续约信用卡未结算佣金明细"));
        
        $sub_data = new SmallDataList("sales_rmb", "", 0, array("close_num" => 1));
        $sub_data->setCustomList("pro_expand_bonus", false, array(11, 'null', $this->uid));
      	$sub_data->setTitle(array("新签卡-已结","新签额","新签佣金-已结", "新增值卡-已结", "新增值佣金-已结","续约卡-已结","续约额","续约佣金-已结", "佣金小计"));
        $sub_data->setField(array("card_num","amount","signing_cost_rmb", "rising_card_num", "rising_cost_rmb","renewal_card_num",
        				"renewal_amount","renewal_cost_rmb","total_card_rmb"));
        $sub_data->setField("card_num", array("name" => 0, "url" => U("settleDetail")."&type=14",
                "pop" => "w:1000,h:500,n:'settleinfo',t:新签约信用卡已结算佣金明细"));
        $sub_data->setField("rising_card_num", array("name" => 3, "url" => U("settleDetail")."&type=15",
                "pop" => "w:1000,h:500,n:'settleinfo',t:新增值信用卡已结算佣金明细"));
        $sub_data->setField("renewal_card_num", array("name" => 5, "url" => U("settleDetail")."&type=20",
        		"pop" => "w:1000,h:500,n:'settleinfo',t:续约信用卡已结算佣金明细"));
        
        $html .= $data->fetch("佣金结算简报", array("body_end" => "<br />".$sub_data->fetch()));
        
    
    	$this->assign("main_body",$html);
    	$this->display(false, "Home@Public/index");
    } 
    
}
