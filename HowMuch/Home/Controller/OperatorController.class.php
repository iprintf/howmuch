<?php
namespace Home\Controller;
use Common\Controller\ListPage;
use Common\Controller\DataList;
use Common\Controller\MainPanel;
use Common\Controller\SmallDataList;
// use Common\Controller\PageTool;
use Common\Controller\Form;
use Common\Controller\FormElement;

class OperatorController extends ListPage 
{
    private $pos_pop = "w:1200,h:570,n:'posinfo',b:saveclosetime,t:推荐可用POS机列表";
    private $uid; 
    private $sid; 
    
    public function __construct()
    {
        parent::__construct();
        if (get_user_info("admin") != 2)
            $this->redirect("Index/index");
        $this->uid = get_user_info("uid");
        $this->sid = get_user_info("sid");
    }
    
    //操作首页报表弹出窗口  昨日新增信用卡列表
    public function newCardlist()
    {
        $data = new SmallDataList("newCardlist", "", 0, array("page" => array("size" => 10)));
        $data->setCustomList("M_oper_list", true, array(7, $this->uid, 'null'));
        $data->setTitle(array("内部代码", "客户姓名","发卡行","卡号","签约日期"));
        $data->setField(array("card_code", "bname","bank","card","times"));
        echo $data->fetch();
    }
    //操作首页报表弹出窗口  紧急锁定信用卡列表
    public function sosCardlist()
    {
        $data = new SmallDataList("sosCardlist", "", 0, array("page" => array("size" => 10)));
        $data->setCustomList("M_oper_list", true, array(6,  $this->uid, 'null'));
        $data->set("tooltip", 1);
        $data->setTitle(array("内部代码", "客户姓名","发卡行","卡号","锁卡原因","锁卡时间"));
        $data->setField(array("card_code", "bname","bank","card","remark","oper_time"));
        echo $data->fetch();
    }
    //操作首页报表弹出窗口  POS机故障列表列表
    public function posErrlist()
    {
        $data = new SmallDataList("posErrlist", "", 0, array("page" => array("size" => 10)));
        $data->setCustomList("M_oper_list", true, array(8,  $this->uid, 'null'));
        $data->set("tooltip", 1);
        $data->setTitle(array("POS简码","商户名称","故障原因","故障申报时间"));
        $data->setField(array("sub_code","abbr_name","remark","oper_time"));
        echo $data->fetch();
    }

    //操作员主页
    public function index()
    {
        // -----------------------------index-content1-操作员今天工作报表---------------------
        $data = new MainPanel("day_profits", "", array("close_num" => 1));
        $data->setCustomList("Report_sub", false, array(6, $this->uid));
        $data->setTitle(array("日期","今日应操作卡数", "今日新增卡数", "残值查询卡数", "SOS锁定卡数", "故障POS台数"));
        $data->setField(array("dates","open_card_num", "open_add_card","rising_2_card", "open_lock_card","open_lock_pos"));
        $data->setField("open_card_num", array("name" => 1, "url" => U("Operator/oper_deal_list"), "tag" => "#body"));
        $data->setField("open_add_card", array("name" => 2, "url" => U("newCardlist"),
        		"pop" => "w:1000,h:500,n:'opernuminfo',t:今日新增信用卡列表"));
        $data->setField("rising_2_card", array("name" => 3, "url" => U("Card/Surplus/query"), "tag" => "#body"));
        $data->setField("open_lock_card", array("name" => 4, "url" => U("sosCardlist"),
        		"pop" => "w:1000,h:500,n:'opernuminfo',t:SOS紧急锁定信用卡列表"));
        $data->setField("open_lock_pos", array("name" => 5, "url" => U("posErrlist"),
        		"pop" => "w:1000,h:500,n:'opernuminfo',t:POS机故障申报列表"));
        $html .= $data->fetch("今日工作报表");
        
        $data1 = new MainPanel("day_profits", "", array("close_num" => 1));
        $data1->setCustomList("pro_expand_bonus", false, array(30, 'null', $this->uid));
        $data1->setTitle(array("日期","总交易卡数", "总交易笔数", "总交易金额", "工作佣金", "增值卡数","增值金额","增值佣金","佣金小计"));
        $data1->setField(array("dates","card_num", "total_card_num","trmb", "bonus_cost_rmb","rising_card_num","rising_amount","rising_cost_rmb","cost_sum"));

        $html .= $data1->fetch("本月佣金简报");
        
        $this->assign("surplus_num", "0");
        $this->assign("main_body", $html);
        $this->display(false, "Home@Public/index");
    }
    
    
    //刷卡列表根据不同情况显示不同颜色代码
    public function setRowColor($data)
    {
        $html = "";
            //等待5分钟 或90分钟 状态颜色
        if ($data["card_status"] == 2 || $data["card_status"] == 3)
            $html .= '<tr class="text-muted">';
        else if ($data["card_status"] == 1)   //锁卡状态
            $html .= '<tr class="text-danger">';  
        else if ($data["card_status"] == 4)   //今日完成状态
            $html .= '<tr class="text-info">';
        else
            $html .= '<tr>';
        
        return $html;
    }
    
    //验证卡片是否今日要刷卡
    public function verifyCard()
    {
        $code = I("get.code");
        $obj = M("core_disburse");
        
        $data = $obj->field("id")->where("status=0 and ccode='".$code."'")->select();
        if ($data)
            echo '<span style="color:black">此卡今日需要操作!</span>';
        else
            echo "此卡今日无需操作!";
    }
    
    //操作员当日刷卡列表数据
    public function showDealList($div = true)
    {
        $list = new DataList("operlist");
        $list->setCustomList("M_oper_list",false,array(1, $this->uid, 'null'));
        if ($div)
            $list->set("close_data_div", 1);
        $list->set("close_chkall", 1);
        $list->set("tr_call", array("run", "Operator/setRowColor"));
        $list->setTitle(array("日期", "内部代码", "发卡行", "卡号", "客户姓名", "今应交易额", "实际交易额", "建议笔数", "实际笔数"));
        $list->setField(array("dates", "ccode", "bank", "card","bname", "day_amount", "trmb", "day_pen","pen_sum"));
        $list->setField("ccode", array("name" => 1, "url" => U("Operator/dealInfo")."&code=[ccode]&card=[card]",
                "pop" => "w:1000,h:600,n:'dealinfo',t:单卡交易详细信息"));
        
        $list->setOp("推荐POS",  U("Operator/recommendPos")."&code=[ccode]", array("pop" => $this->pos_pop));    	
        
        if ($div)
            echo $list->fetch();
        else
            return $list->fetch();
    }

//-----------------------------操作员刷卡列表-code-----------------------------
    public function oper_deal_list()
    {
    	$this->setNav("&nbsp;->&nbsp;工作列表");
        $this->set("close_tool", 1);
        
        $find = new Form("", array("name" => "operform", "kajax" => "false"));
        $find->set("close_btn_down", 1);
        
        $find->setElement("oper_find_type", "radio", "", array("group" => "start",
                "close_label" => 1,
                "element_cols" => 4,
                "pclass" => "text-right",
                "value" => 1,
                "list" =>  parse_select_list("array", array(1, 2), array("工作模式", "查询模式")),
        ));
        
        $find->setElement("oper_find_code", "string", "内部代码", array("group" => "mid",
                "close_label" => 2,
                "maxlength" => 15,
                "bool" => "autofocus required",
                "placeholder" => "请输入信用卡内部代码",
                "label_cols" => 1,
                "element_cols" => 2,
                "ext" => 'url="'.U("Operator/verifyCard").'"',
        ));
        $find->setElement("oper_find_btn", "button", "查询", array("group" => "mid",
                "element_cols" => 1,
                "icon" => "search",
                "ext" => 'me="me" type="button" id="oper_find_btn_id"',
                "url" => U("Operator/recommendPos"),
                "pop" => $this->pos_pop,
        ));
        $find->setElement("oper_find_txt", "static", "", array("group" => "end",
                "element_cols" => 3,
                "class" => "text-center oper_parse_hint",
        ));
        $find->set("js", "kyo_operator");
        
        $this->set("data", $find->fetch()."<br />".$this->showDealList(false));
        
    	$this->display();
    }
    
    //单卡交易明细列表标题
    public function deal_info_title($data)
    {
        $html = "<tr>";
        $html .= '<th rowspan="2">序号</th>';
        $html .= '<th rowspan="2">商户</th>';
        $html .= '<th rowspan="2">交易时间</th>';
        $html .= "<th>总金额</th>";
        $html .= "<th>已完成</th>";
        $html .= "<th>总笔数</th>";
        $html .= "<th>已完成</th>";
        $html .= "<th>总成本</th>";
        $html .= "<th>已用成本</th>";
        $html .= "</tr>";
        $html .= "<tr>";
        $html .= "<th>".$data["day_amount"]."</th>";
        $html .= '<th class="deal_info_num">'.$data["rmb"].'</th>';
        $html .= "<th>".$data["day_pen"]."</th>";
        $html .= '<th class="deal_info_num">'.$data["pen"].'</th>';
        $html .= "<th>".$data["day_cost"]."</th>";
        $html .= '<th class="deal_info_num">'.$data["pos_cost"].'</th>';
        $html .= "</tr>";
        
        return $html;
    }
    
    //单卡交易明细窗口代码
    public function dealInfo()
    {
        $info = new Form("", array("cols" => "2"));
        $info->set("close_btn_down", 1);
        
        $code = I("get.code");
        
        $data = sqlRow('call M_oper_list(0,10,4,null,"'.$code.'")');
        $info->setElement("info_group", "group", "基本信息");
        $info->setElement("code", "static", "卡内部编码", array("value" => $code));
        $info->setElement("card", "static", "信用卡卡号", array("value" => format_dis_field(I("get.card"))));
        $info->setElement("day_amount", "static", "应交易额度", array("value" => $data["day_amount"]." 元"));
        $info->setElement("rmb", "static", "已完成额度", array("value" => $data["rmb"]." 元"));
        $info->setElement("day_pen", "static", "应交易笔数", array("value" => $data["day_pen"]." 笔"));
        $info->setElement("pen", "static", "已完成笔数", array("value" => $data["pen"]." 笔"));
//         $info->setElement("day_cost", "static", "当日总成本", array("value" => $data["day_cost"]." 元"));
//         $info->setElement("pos_cost", "static", "已使用成本", array("value" => $data["pos_cost"]." 元"));
//         $info->setElement("operator", "static", "所属操作员", array("value" => get_user_info("name")));
        
        $list = new SmallDataList("card_deal", "pos");
        $list->setPage("size", 6);
        
        $list->setCustomList("M_oper_list",true,array(5,'null', '"'.$code.'"'));
//         $list->set("my_data_title", $this->deal_info_title($data));
        
        $list->setTitle(array("POS简码", "商户简称", "交易时间", "交易金额", "交易次数", "交易成本"));
        $list->setField(array("sub_code", "pos_name", "dates", "card_rmb", "pen_num", "pos_cost_rmb"));
//         $list->setField(array("pos_name", "dates", "j_amount", "rmb", "j_pen", "pen", "j_cost", "pos_cost"));
        
        echo $info->fetch()."<br />".$list->fetch();
    }
    
    //pos故障处理
    public function posErr()
    {
        if (IS_POST)
        {
            $return = array("echo" => 1, "close" => 1, "info" => "操作成功!",
                    "url" => "", "tag" => "", "callback" => '
                    $(".posinfo").prev().children(".pop_close").click();');
//                     $("#posErrBtn'.$_POST["code"].'").closest("tr").remove();   //只是删除Pos一条记录
            
            $ret = save_operating_record($_POST["code"], POS_ERROR);
            if (!$ret)
                $this->ajaxReturn(array("echo" => 1, "info" => "操作记录保存失败!"));
            
            $obj = M("pos");
            $obj->where("code='".$_POST["code"]."'")->setField("status", ERROR);
            $msg = "操作员 ".get_username($this->uid);
            $msg .= " 对 ".I("post.sub_code")." 这台POS进行了故障提交, ";
            $msg .= " 请 ".parse_msg_link(U("Pos/Pos/Index")."&ibody=1&error=1")." 对此台POS机处理!";
            auto_send_msg("POS机故障提交", $msg);
            
            $this->ajaxReturn($return);
            exit(0);
        }
        
        $err = new Form("", array("name" => "errform"));
        $code = I("get.code");
        
        $pos = sqlRow("select sub_code,pay from pos where code='".$code."'");
        $err->setElement("audittype", "autocomplete", "故障说明", array(
                "placeholder" => "自己写故障说明!",
                "list" => parse_autocomplete("select txt from sel_remark where type_name='".$pos["pay"]."' and type_id=".POS_ERROR)));
        $err->setElement("remark", "textarea", "", array("close_label" => 1, "element_cols" => 12, 
                "rows" => 6, "bool" => "required"));
        $err->setElement("code", "hidden", "", array("value" => $code));
        $err->setElement("sub_code", "hidden", "", array("value" => $pos["sub_code"]));
        $err->set("btn 0 txt", "申报");
        echo $err->fetch();
        exit(0);
    }
    
    //sos锁卡处理
    public function lockCard()
    {
        $sos = new \Card\Controller\SosController();
        $sos->lock(U("Operator/oper_deal_list"), "#body", '$(".posinfo").prev().children(".pop_close").click();');
        exit(0);
    }
    
    //重新查询或关闭窗口记录时间
    public function closeTime()
    {
        $card_code = I("get.code");
        $pos_code = I("get.pcode");
        $rmb = I("get.rmb");
        $times = getcurtime();
        if ($card_code)
            M("core_disburse")
                ->where("date_format(dates,'%Y-%m-%d')=curdate() and status=0 and ccode='".$card_code."'")
                ->setField("close_time", $times);
        if ($pos_code)
        {
            M("pos")->where("code='".$pos_code."'")->setField("close_time", $times);
            $data = array();
            $data["code"] = $card_code;
            $data["pcode"] = $pos_code;
            $data["rmb"] = $rmb;
            $data["deal_time"] = $times;
            M("deal_inspect")->add($data);
        }
        
        echo 'clostTime'.$card_code." ".$pos_code." ".$rmb;
        exit(0);
    }
    
    //模拟交易数据窗口
    public function simulation()
    {
        if (IS_POST)
        {
            $return = array("echo" => 1, "close" => 1, "info" => "交易成功!",
                    "url" => U("oper_deal_list"), "tag" => "#body", 
                    "callback" => '$(".posinfo").parent().find(".pop_close").click();');
            
            $data = array();
            $data["pcode"] = I("post.pcode");
            $pos = sqlRow("select sub_code,proxy,abbr_name,models,shop_id,sid from pos where code='".$data["pcode"]."'");
            $data["sub_code"] = $pos["sub_code"];
            $data["proxy"] = $pos["proxy"];
            $data["pname"] = $pos["abbr_name"];
            $data["terminal"] = $pos["models"];
            $data["business"] = $pos["shop_id"];
            $data["date"] = getcurtime();
            $data["dates"] = $data["date"];
            $data["sid"] = $pos["sid"];
            $card = sqlRow("select eid,bid,opid,bank,card from card where code='".I("post.ccode")."'");
            $data["eid"] = $card["eid"];
            $data["bid"] = $card["bid"];
            $data["opid"] = $card["opid"];
            $data["bank"] = $card["bank"];
            $data["card"] = $card["card"];
            $data["temp_save"] = I("post.old_rmb");
            $data["rmb"] = str_replace(",", "", I("post.rmb"));
            
            $obj = M("transaction");
            $ret = $obj->create($data, 1);
            if (!$ret || !$obj->add())
                $this->ajaxReturn(array("echo" => 1, "info" => "交易失败!"));
            
            sqlCol("call pro_disburse_hours;");
            
            $this->ajaxReturn($return);
        }
        
        $sim = new Form("", array("name" => "simform"));
        $code = I("get.code");
        $rmb = I("get.rmb");
        $sim->setElement("old_rmb", "num", "建议交易金额", array("value" => $rmb, "bool" => "readonly"));
        $sim->setElement("rmb", "num", "实际交易金额", array("value" => $rmb, "bool" => "required"));
        $sim->setElement("ccode", "hidden", "", array("value" => I("get.ccode")));
        $sim->setElement("pcode", "hidden", "", array("value" => $code));
        $sim->set("btn 0 txt", "生成交易");
        echo $sim->fetch();     
    }
    
    //判断卡的状态对应显示不同信息
    public function judgeCardStatus($code, & $card)
    {
//         kclose 代表是否要记录关闭时间   默认为不记录
//         sos   是否显示sos锁卡按钮   默认为不显示
//         txt   错误提示的信息  默认为空
        $s = array("kclose" => "", "sos" => "hidden", "txt" => "");
        if (!$card)   //没有找到数据
        {
            $find_card = sqlRow("select opid,sid from card where code='".$code."'");
            if ($find_card && $find_card["sid"] == $this->sid)
            {
                if ($this->uid != $find_card["opid"])
                    $s["txt"] = '<p>'.$code."</p>此卡所属其它操作员负责!";
                else
                    $s["txt"] = '<p>'.$code."</p>今日无需操作, 请操作其它信用卡!";
            }
            else
                $s["txt"] = '<p>'.$code."</p>此代码非本系统代码!";
            return $s;
        }
        
        if ($card["total_period"] && $card["day_pen"] - $card["collect_pen"] == 1)
        {
            $card["collect_pen"] = '<span style="color:red;font-weight:bold;">'.
                    $card["collect_pen"].'&nbsp;&nbsp;&nbsp;&nbsp;*注: 本次为本月最后一笔交易!<span>';
        }
        
        switch ($card["card_status"])
        {
        	case 1:   //锁卡
                $s["txt"] = '<p>'.format_dis_field($card["card"])."</p>此卡提交了锁卡，请耐心等待处理!";
        	    break;
        	case 5:   //残值临查
                $s["txt"] = '<p>'.format_dis_field($card["card"])."</p>此卡提交了残值临查，请到残值查询列表中对此卡提交残值!";
        	    break;
        	case 2:   //5分钟
                $s["txt"] = '<p>'.format_dis_field($card["card"])."</p>
                        <p>此卡刚做过操作正在等待交易数据!</p> 
                                                    请在&nbsp;".$card["close_time"]."&nbsp;后再继续操作!";
//                 $s["txt"] = "此卡刚做过操作正在等待交易数据!<br />请".$card["close_time"]."分钟后再继续操作!";
        	    break;
        	case 3:   //90分钟
                $s["txt"] = '<p>'.format_dis_field($card["card"])."</p>
                             <p>此卡刚做过交易并且交易成功!</p>
                                                                请&nbsp;".$card["deal_time"]."&nbsp;后再继续操作!";
        	    break;
        	case 4:   //完成
                $s["txt"] = '<p>'.format_dis_field($card["card"])."</p>此卡今日已经完成操作!";
        	    break;
            default:
                $s["kclose"] = "kclose";
                $s["sos"] = "";
                $s["txt"] = "";
                break;
        }
        
        return $s;
    }
    
    //推荐POS机列表窗口
    public function recommendPos()
    {
        $code = I("get.code");
        
        $card = sqlRow('call M_oper_list(0,10,3, '.$this->uid.', "'.$code.'")');
        // 打开调试信息
//         dump(M()->getLastSql());
//         dump($card);
//         $card = sqlRow("select * from card_disburse where code='f11715900362'");
//         dump(M()->getLastSql()."<br />".$code." ".$card);
        
        $status = $this->judgeCardStatus($code, $card);
        
        if ($status["kclose"])
            $pos = sqlAll("call Demo_pos_system(1, 10, ".$card["pen"].",".$card["amount"].",".$card["sid"].",\"".$code."\",".$card["advice"].");");
//         dump(M()->getLastSql());
//         dump($pos);
        
        $find = new Form("", array("name" => "cardfindform", "kajax" => false));
        $find->set("close_btn_down", 1);
        $find->setElement("pos_find_code", "string", "信用卡内部代码", array("group" => "start",
                "bool" => "autofocus required",
                "maxlength" => 15,
                "label_cols" => 4,
                "element_cols" => 4,
        ));
//         dump($pos[0]["code"]);
        
        //url为关闭窗口保存时间的链接  krefresh 为关闭窗口刷新刷卡列表链接
        $find->setElement("pos_find_btn", "button", "查询", array("group" => "mid",
                "element_cols" => 2,
                "icon" => "search",
                "bool" => "me ".$status["kclose"],
                "ext" => 'type="button" id="pos_find_btn_id" 
                    krefresh="'.U("Operator/showDealList").'" 
                    url="'.U("Operator/closeTime").'&code='.$code.'&pcode='.$pos[0]["code"].'&rmb='.$pos[0]["posadvice"].'"',
        ));
//         dump($pos[0]["posadvice"]);
        
        $find->setElement("pos_sos_btn", "button", "SOS紧急锁卡", array("group" => "end", 
                "element_cols" => 1, "class" => "btn-danger ".$status["sos"], "icon" => "lock",
                "url" => U("Operator/lockCard")."&code=".$code,
                "pop" => \Card\Controller\SosController::getPop("lock"),
                "ext" => 'type="button" confirm="锁卡操作会影响卡片正常工作，确定锁卡吗？"',
        ));
        
        if (!$status["kclose"])
        {
//             echo $find->fetch().'<div class="big_txt_hint">'.$status["txt"].'</div>';
            echo $find->fetch().'<h1 style="color:red;font-weight:bold;">'.$status["txt"].'</h1>';
            exit(0);
        }
        
        $info = new Form("", array("name" => "cardinfoform"));
        $info->set("close_btn_down", 1);
        $info->setElement("info_group", "group", "信用卡基本信息");
        $info->setElement("dates", "static", "交易日期", array("label_cols" => 4, "value" =>  $card["dates"]));
        $info->setElement("card", "static", "银行卡号", array("label_cols" => 4,"value" =>  format_dis_field($card["card"])));
        $info->setElement("day_amount", "static", "日应交易", array("label_cols" => 4,"value" =>  $card["day_amount"].'&nbsp;元'));
        $info->setElement("trmb", "static", "已交易", array("label_cols" => 4, "class" => "text-primary", "value" =>  $card["rmb"].'&nbsp;元'));
        $info->setElement("day_pen", "static", "建议笔数", array("label_cols" => 4,"value" =>  $card["day_pen"].'&nbsp;笔'));
        $info->setElement("pen_sum", "static", "已交易", array("label_cols" => 4, "class" => "text-primary", "value" =>  $card["collect_pen"].'&nbsp;笔'));
        
        $list = new SmallDataList("card_deal", "pos");
        $list->setPage("size", 8);
        $list->set("close_op", 0);
        $list->set("data_table_class", "table-bordered table-hover table-condensed operator_pos");
        
//         $list->setCustomList("Demo_pos_system",true,array($card["pen"], $card["amount"], $card["sid"], '"'.$code.'"',$card["advice"]));
        $list->set("data_list", $pos);
        
        $list->setTitle(array("POS简码", "推荐商户", "建议交易金额"));
        $list->setField(array("sub_code", "abbr_name", "posadvice"));
        $list->setOp("POS故障", U("Operator/posErr")."&code=[code]", array("ext" => 'id=posErrBtn[code]',
                "pop" => "w:480,h:360,n:'poserr',t:POS故障申报"));
        if ($this->sid == 125)
            $list->setOp("模拟交易", U("Operator/simulation")."&ccode=".$code."&code=[code]&rmb=[posadvice]", 
                    array("pop" => "w:480,h:260,n:'possim',t:模拟交易数据"));
        
        echo $find->fetch().'<br /><div class="col-md-3">'.$info->fetch().'</div><div class="col-md-9">'.$list->fetch().'</div>';
    }
    
//-----------------------------操作员刷卡报表-code-----------------------------
    public function oper_deal_report()
    {
    	$this->setNav("&nbsp;->&nbsp;操作报表");
    	$this->mainPage("card");
        $this->set("close_tool", 1);
    	$my_data = $this->setCustomList("M_oper_list",true,array(2, $this->uid, 'null'));
    	$this->setData("close_op", 1);
    	$this->setData("close_chkall", 1);
        $this->setData("tr_call", array("run", "Operator/setRowColor"));
    	$this->setTitle(array("日期", "内部代码", "发卡行", "卡号", "今应交易额", "实际交易额", "建议笔数", "实际笔数",  "实际成本"));
    	$this->setField(array("dates", "ccode", "bank", "card","day_amount","rmb","day_pen","pen_num","pos_cost_rmb"));
    	$this->display();
    }
}
