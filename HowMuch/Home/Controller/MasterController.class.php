<?php

namespace Home\Controller;

use Common\Controller\ListPage;
use KyoCommon\Controller\ReportController;
use Common\Controller\DataList;
use Common\Controller\MainPanel;
use Common\Controller\SmallDataList;
// use Common\Controller\PageTool;
use Common\Controller\Form;
use Common\Controller\FormElement;

class MasterController extends ListPage
{

    public function __construct()
    {
        parent::__construct();
        if (get_user_info("admin") != 6)
            $this->redirect("Index/index");
    }

    public function report()
    {
        $report = new ReportController();
        
        $report->set("group", array("明细报表"));
        
        PublicController::commonReport($report);
        
        $this->show($report->fetch());
    }

    public function index()
    {
        //财务站长通用报表
        $html = PublicController::commonBriefing();

//         // -----------------------------index-content5-今日POS入账表------------------------------------------
//         $data = new MainPanel("day_profits", "", 
//                 array("close_num" => 1,"page" => array("size" => 5)));
//         $data->setCustomList("Report_sub", false, 
//                 array(3,get_user_info("sid")));
//         $data->setTitle(
//                 array("交易日期","商户名","交易金额","MCC支出","应入账金额","入账日期"));
//         $data->setField(
//                 array("dates1","pos_name","drmb","pos_cost","prmb","aacc"));
//         // $data->setField("sub", array("name" => 0, "url" =>
//         // U("Main/sub_day_profits"."?small=day_profits"), "pop" =>
//         // "w:600,h:500,n:customer,t:各分站成本利润统计"));
//         $html .= $data->fetch("今日POS入账表");
        
        $this->assign("main_body", $html);
        $this->display(false, "Home@Public/index");
    }
}
