<?php
// -------&&&&&&主站平台&&&&&&---------
namespace Home\Controller;

use Common\Controller\ListPage;
// use Common\Controller\DataList;
use Common\Controller\MainPanel;
use Common\Controller\SmallDataList;
// use Common\Controller\PageTool;
use Common\Controller\Form;
use Common\Controller\FormElement;
use KyoCommon\Controller\ReportController;

class MainController extends ListPage
{
    private $admin;
    public function __construct()
    {
        parent::__construct();
        $admin = get_user_info("admin");
        if (!($admin == 9 || $admin == 0))
            $this->redirect("Index/index");
    }

    public function report()
    {
        $report = new ReportController();
        
        $report->set("group", array("明细报表 "));
        // 第一个参数: 报表标识符 保证唯一性
        // 第二个参数: 报表标题
        // 第三个参数: 报表属性哪个组, 从零开始计起对应关系
        PublicController::commonReport($report);

        $this->show($report->fetch());
    }

    public function index()
    {
        // -----------------------------index-content1-全站汇总简报---------------------
        $data = new MainPanel("day_profits", "", 
                array("close_num" => 1,"page" => array("size" => 5)));
        $data->setCustomList("Report_sub", false, 
                array(17,get_user_info("sid")));
        $data->setTitle(
                array("日期","合作分站","应操作卡","应交易额","今已交易","应还款卡","应还款额","今已还款","新增卡片","新增卡额"));
        $data->setField(
                array("dates","sub_sid","oper_num","oper_amount","trmb","repay_num","repay_amount","s_amount","card_add_num","card_add_amount"));
        $data->setField("dates", array("name" => 0, "url" => U("sub_day_card"),
        		"pop" => "w:1000,h:500,n:'opernuminfo',t:合作分站-汇总简报"));
        $data->set("data_field 4 class", "kyo_red");
        $data->set("data_field 7 class", "kyo_red");
        $html .= $data->fetch("平台汇总简报");
        
        
        // -----------------------------index-content2-卡片综合简报---------------------
        $data = new MainPanel("day_profits", "",
        		array("close_num" => 1,"page" => array("size" => 5)));
        $data->setCustomList("Report_sub", false, array(19,get_user_info("sid")));
        $data->setTitle(
        		array("日期","合作分站","卡片总数","卡额总额","正常卡片","增值卡片","待初审卡","待终审卡","解约卡片","SOS锁卡","POS故障"));
        $data->setField(
        		array("dates","sub_sid","card_num","card_amount","normal_card","rising_num","start_audit","end_audit","break_card","lock_card","fault_pos"));
        $data->setField("dates", array("name" => 0, "url" => U("sub_card_num"),
        		"pop" => "w:1000,h:500,n:'opernuminfo',t:合作分站-实时数据"));
        $html .= $data->fetch("平台实时数据");
        
        
        $this->assign("main_body", $html);
        $this->display(false, "Home@Public/index");
    }
    
    //全站汇总简报——弹出来的各分站统计信息
    public function sub_day_card()
    {
        $data = new SmallDataList("sub_day_card", "", 0, array("page" => array("size" => 10)));
      	$data->setCustomList("Report_sub", false, array(18,'NULL'));
        $data->set("tooltip", 1);
        $data->setTitle(
                array("日期","合作分站","应操作卡","应交易额","今已交易","应还款卡","应还款额","今已还款","新增卡片","新增卡额"));
        $data->setField(
                array("dates","sub_name","oper_num","oper_amount","trmb","repay_num","repay_amount","s_amount","card_add_num","card_add_amount"));
        $data->set("data_field 4 class", "kyo_red");
        $data->set("data_field 7 class", "kyo_red");
//         $data->set("data_title 5 class", "kyo_red");
        echo $data->fetch();
    }
	
    //全站卡片综合简报——弹出来的各分站统计信息
    public function sub_card_num()
    {
    	$data = new SmallDataList("sub_card_num", "", 0, array("page" => array("size" => 10)));
    	$data->setCustomList("Report_sub", false, array(20,'NULL'));
    	$data->set("tooltip", 1);
    	 $data->setTitle(
        		array("日期","合作分站","卡片总数","卡额总额","正常卡片","增值卡片","待初审卡","待终审卡","解约卡片","SOS锁卡","POS故障"));
        $data->setField(
        		array("dates","sub_name","card_num","card_amount","normal_card","rising_num","start_audit","end_audit","break_card","lock_card","fault_pos"));
    	echo $data->fetch();
    }
    
    
    public function remark()
    {
        $record_txt = C("RECORD_TEXT");
        $by = I("get.by");
        
        $this->setNav(
                "&nbsp;->&nbsp;系统设置&nbsp;->&nbsp;备注管理&nbsp;->&nbsp;" .
                         $record_txt[$by]);
        $this->mainPage("sel_remark");
        
        // dump(session("prev_urlsel_remark"));
        // 设置添加按钮弹出窗口
        $this->setTool("tool_btn_down", 
                array("txt" => "新增备注文本","icon" => "plus","url" => U() .
                         "&form=add&by=" . $by,"pop" => "w:450,h:350,n:'remarkadd',t:" .
                         $record_txt[$by] . "备注"));
        
        $this->setLink(CUST_AUDIT_NORMAL, "客户审核通过", "", 
                array("end" => "&emsp;|&emsp;","tag" => "#body"));
        $this->setLink(CUST_AUDIT_NO_PASS, "客户审核拒绝", "", 
                array("end" => "&emsp;|&emsp;","tag" => "#body"));
        $this->setLink(CARD_AUDIT, "卡片审核通过", "", 
                array("end" => "&emsp;|&emsp;","tag" => "#body"));
        $this->setLink(CARD_AUDIT_NO_PASS, "卡片审核拒绝", "", 
                array("end" => "&emsp;|&emsp;","tag" => "#body"));
        $this->setLink(CARD_SOS_LOCK, "卡片锁定", "", 
                array("end" => "&emsp;|&emsp;","tag" => "#body"));
        $this->setLink(CARD_SOS_UNLOCK, "卡片解锁", "", 
                array("end" => "&emsp;|&emsp;","tag" => "#body"));
        $this->setLink(POS_ERROR, "故障申报", "", 
                array("end" => "&emsp;|&emsp;","tag" => "#body"));
        $this->setLink(POS_ERROR_END, "故障解决", "", 
                array("end" => " ","tag" => "#body"));
        
        $this->setFind("typelist txt", array("txt" => "备注文本","val" => "txt")); // 增加搜索选择
                                                                                
        // 设置批量删除操作
        $this->setBatch("删除所选", U() . "&form=del&by=" . $by, 
                array("query" => true,'icon' => "remove",'ext' => 'confirm="确定批量删除吗？"'));
        
        // 如果是POS故障要选择支付公司
        if ($by == POS_ERROR)
        {
            $this->setFind("item pay", 
                    array("name" => "type_name","type" => "select","list" => parse_select_list(
                            "select name from pay"),"default" => "所有支付公司"));
            
            $this->setElement("type_name", "select", "支付公司", 
                    array("bool" => "required","list" => parse_select_list(
                            "select name from pay order by sort_id")));
            $rows = 3;
            $this->setTitle(array("支付公司","备注文本","创建时间","更新时间"));
            $this->setField(
                    array("type_name","txt","create_time","update_time"));
        }
        else
        {
            $this->setElement("type_name", "hidden", "", 
                    array("value" => $record_txt[$by]));
            $rows = 5;
            $this->setTitle(array("备注文本","创建时间","更新时间"));
            $this->setField(array("txt","create_time","update_time"));
        }
        // 设置表单成员， 添加、编辑和信息的元素 uniq为不得重复
        $sort_max = $this->sqlCol(
                "select max(sort_id) from sel_remark where type_id=" . $by);
        if (!$sort_max)
            $sort_max = 0;
        $this->setElement("sort_id", "num", "排序号", 
                array("value" => ++$sort_max,"bool" => "readonly required","maxlength" => 4));
        $this->setElement("txt", "textarea", "备注文本", 
                array("bool" => "required","rows" => $rows));
        $this->setElement("type_id", "hidden", "", array("value" => $by));
        
        // 设置表单添加或修改自动完成项 创建时间和更新时间不需要输入自动填写
        $this->setForm("auto", 
                array(array("create_time",'getcurtime',1,"function"),array("update_time",'getcurtime',3,"function")));
        
        $this->setOp("上移", 
                U("KyoCommon/Index/listMove") .
                         "&table=sel_remark&where='type_id=" . $by .
                         " and sort_id<[sort_id]'", 
                        array("query" => true,"ext" => 'lconfirm="确定向上移动吗?"'));
        $this->setOp("下移", 
                U("KyoCommon/Index/listMove") .
                         "&table=sel_remark&where='type_id=" . $by .
                         " and sort_id>[sort_id]'", 
                        array("query" => true,"ext" => 'lconfirm="确定向下移动吗?"'));
        $this->setOp("编辑", U() . "&form=edit&by=" . $by . "&where='id=[id]'", 
                array("pop" => "w:450,h:350,n:'remarkedit',t:修改" .
                         $record_txt[$by] . "信息"));
        $this->setOp("删除", U() . "&form=del&by=" . $by . "&where='id=[id]'", 
                array("query" => true,"ext" => 'confirm="确定删除吗？"'));
        
        $this->setData("tooltip", 1);
        $this->setData("order", "sort_id");
        $this->setData("where", "type_id=" . $by);
        $this->setPage("param", "by=" . $by);
        
        $this->display();
    }
    
    // -----------------------------支付公司-code-----------------------------
    public function pay()
    {
        $this->setNav("&nbsp;->&nbsp;系统设置&nbsp;->&nbsp;支付公司管理");
        $this->mainPage("pay");
        
        // 设置添加按钮弹出窗口
        $this->setTool("tool_btn_down", 
                array("txt" => "新增支付公司","icon" => "plus","url" => U() .
                         "&form=add","pop" => "w:450,h:300,n:'payadd',t:添加支付公司"));
        
        $this->setFind("typelist name", 
                array("txt" => "支付公司名称","val" => "name")); // 增加搜索选择
                                                                                            
        // 设置批量删除操作
        // $this->setBatch("删除所选", U()."&form=del",/ array("query" =>  true, 'icon' => "remove",  'ext' =>  'confirm="确定批量删除吗？"'));
                                                                                            
        // 设置表单成员， 添加、编辑和信息的元素 uniq为不得重复
        $this->setElement("name", "string", "支付公司名称", 
                array("bool" => "uniq required","maxlength" => 10));
        $sort_max = $this->sqlCol("select max(sort_id) from pay");
        $this->setElement("sort_id", "num", "排序号", 
                array("value" => ++$sort_max,"bool" => "readonly required","maxlength" => 4));
        
        // 设置表单添加或修改自动完成项 创建时间和更新时间不需要输入自动填写
        $this->setForm("auto", 
                array(array("create_time",'getcurtime',1,"function"),array("update_time",'getcurtime',3,"function")));
        
        $this->setData("close_chkall", 1);
        $this->setData("order", "sort_id");
        $this->setOp("上移", 
                U("KyoCommon/Index/listMove") .
                         "&table=pay&where='sort_id<[sort_id]'", 
                        array("query" => true,"ext" => '#confirm="确定向上移动吗?"'));
        $this->setOp("下移", 
                U("KyoCommon/Index/listMove") .
                         "&table=pay&where='sort_id>[sort_id]'", 
                        array("query" => true,"ext" => '#confirm="确定向下移动吗?"'));
        $this->setOp("编辑", U() . "&form=edit&where='id=[id]'", 
                array("pop" => "w:450,h:300,n:'bankadd',t:修改支付公司信息"));
        // $this->setOp("删除", U()."&form=del&where='id=[id]'",
        // array("query" => true, "ext" => 'confirm="确定删除吗？"'));
        
        $this->setTitle(array("支付公司名称","排序号","创建时间"));
        $this->setField(array("name","sort_id","create_time"));
        
        $this->display();
    }
    
    // 假日设置处理函数
    public function holiday_handle()
    {
        $holiday_name = array(1 => "元旦","春节","清明节","劳动节","端午节","中秋节","国庆节");
        $obj = M("holiday");
        
        $obj->execute("truncate table holiday");
        $holiday = array();
        
        foreach ($holiday_name as $key => $name)
        {
            $data = array();
            $start = $_POST["start_" . $key];
            $day = $_POST["day_" . $key];
            $fill1 = $_POST["fill1_" . $key];
            $fill2 = $_POST["fill2_" . $key];
            
            for ($i = 0; $i < $day; $i++)
            {
                $data["holiday"] = date("Y-m-d", 
                        strtotime("+$i day", strtotime($start)));
                $data["type"] = $key;
                $data["is_fill"] = 0;
                $obj->add($data);
                $holiday[$data["holiday"]] = "0";
            }
            
            if ($fill1 != "")
            {
                $data["holiday"] = $fill1;
                $data["is_fill"] = 1;
                $obj->add($data);
                $holiday[$fill1] = "1";
            }
            
            if ($fill2 != "")
            {
                $data["holiday"] = $fill2;
                $obj->add($data);
                $holiday[$fill2] = "1";
            }
        }
        
        $card_all = sqlAll(
                "select code,installment,repayment,bill,finally_repayment_date from card where status=0");
        
        foreach ($card_all as $card)
        {
            if (!update_card_installment($holiday, $card))
                $this->ajaxReturn(array("echo" => 1,"info" => "设置失败!"));
        }
        $return["close"] = 0;
        $return["echo"] = 1;
        $return["info"] = "设置成功!";
        $return["url"] = U("Main/holiday");
        $return["tag"] = "#body";
        $return["callback"] = 0;
        $this->ajaxReturn($return);
        exit(0);
    }
    
    // 假日设置
    public function holiday()
    {
        // $card["code"] = "######";
        // $card["bill"] = 25;
        // $card["repayment"] = 2;
        // $card["due_date"] = "2014-11-23";
        // $card["finally_repayment_date"] = 20;
        // update_card_installment("", $card, true);
        // exit(0);
        $holiday_name = array(1 => "元旦","春节","清明节","劳动节","端午节","中秋节","国庆节");
        
        $hForm = new Form("", 
                array("action" => U("holiday_handle"),"kajax" => "true","class" => "form-horizontal col-sm-12 col-md-12 main_first_row"));
        
        $hForm->setElement("holiday_title", "group", 
                get_platfrom_name("name") .
                         "平台&nbsp;->&nbsp;系统设置&nbsp;->&nbsp;假日设置");
        
        foreach ($holiday_name as $key => $name)
        {
            $start_val = $this->sqlCol(
                    "select holiday from holiday where type=$key");
            $day_val = $this->sqlCol(
                    "select count(id) from holiday where type=$key and is_fill=0");
            $fill_array = $this->sqlAll(
                    "select holiday from holiday where type=$key and is_fill=1");
            $fill1_val = $fill_array ? $fill_array[0]["holiday"] : "";
            $fill2_val = isset($fill_array[1]) ? $fill_array[1]["holiday"] : "";
            
            $hForm->setElement("start_" . $key, "date", $name, 
                    array("group" => "start","bool" => "required","label_cols" => 3,"value" => $start_val,"placeholder" => "请选择起始日期","title" => "起始日期","element_cols" => 2));
            $hForm->setElement("day_" . $key, "select", "放假天数", 
                    array("group" => "mid","bool" => "required","element_cols" => 2,"addon" => "天","value" => $day_val,"list" => parse_select_list(
                            "for", array(1,15,1,1), "放假天数")));
            $hForm->setElement("fill1_" . $key, "date", "周末补假日期", 
                    array("group" => "mid","value" => $fill1_val,"element_cols" => 2));
            $hForm->setElement("fill2_" . $key, "date", "周末补假日期", 
                    array("group" => "end","value" => $fill2_val,"element_cols" => 2));
        }
        $hForm->set("btn 0 txt", "设置");
        
        $hForm->setBtn("返回主页", U("Home/Index/index"), 
                array("bool" => "blink","ext" => 'type="button"'));
        
        $day = date("d");
        if (!(date("m") == 12 && ($day >= 1 && $day <= 5)) ||
                 M("holiday")->count() != 0 ||
                 sqlCol(
                        "select id from holiday where holiday='" . date("Y") .
                         "-05-01' limit 1"))
        {
            $hForm->setElement("hint_txt", "static", "", 
                    array("close_label" => 1,"element_cols" => 10,"value" => "*注：本年假日已经设置完成，请在12月1号到12月5号之间设置下一年假日!","class" => "col-md-offset-5"));
            $hForm->set("btn 0 class", "hidden");
        }
        
        $this->show($hForm->fetch());
    }
    
    // 获取图片列表
    public function getDirList($path, & $list)
    {
        $fd = array("basis" => "id_img='[]' or card_img='[]'","card" => "card_img1='[]' or card_img2='[]' or sign_img='[]'","users" => "id_img1='[]' or id_img2='[]' or card_img='[]'","card_type" => "img='[]'","rising" => "img='[]'");
        
        if (is_dir($path))
        {
            $dp = opendir($path);
            static $i = 0;
            while (($file = readdir($dp)) != false)
            {
                if ($file == "." || $file == "..")
                    continue;
                $new_path = $path . '/' . $file;
                if (is_dir($new_path))
                    $this->getDirList($new_path, $list);
                else
                {
                    $new_path = str_replace(__UP__ . "/", "", $new_path);
                    $table = explode("/", $new_path);
                    $data_path = str_replace("/", ",", $new_path);
                    
                    $where = str_replace("[]", $data_path, $fd[$table[0]]) .
                             " or " .
                             str_replace("[]", $new_path, $fd[$table[0]]);
                    $ret = sqlCol("select id from " . $table[0] . " where " .
                             $where);
                    if ($ret)
                        continue;
                    $list[$i]["id"] = $i;
                    $list[$i]["table"] = $table[0];
                    $list[$i]["path"] = __UP__ . $new_path;
                    $list[$i]["size"] = (floor(
                            filesize(__UP__ . $new_path) / 1024)) . "K";
                    $list[$i]["ctime"] = date("Y-m-d H:i:s", 
                            filectime(__UP__ . $new_path));
                    $i++;
                }
            }
            closedir($dp);
        }
    }
    
    // 残图管理准备没有链接图片列表
    public function parseImgList()
    {
        F("imglist", null);
        $list = array();
        
        $this->getDirList(__UP__, $list);
        clearstatcache();
        F("imglist", $list);
    }
    
    // 残图管理图片显示
    public function disImg($data, $txt)
    {
        return '<img src="' . $data["path"] . '" width="150px" height="80px" />';
    }
    
    // 残图管理
    public function images()
    {
        $del = I("get.del");
        if ($del)
        {
            $data = F("imglist");
            if ($del == "all")
            {
                foreach ($data as $val)
                {
                    unlink($val["path"]);
                }
                F("imglist", null);
                $this->ajaxReturn(
                        array("echo" => 1,"info" => "删除成功!","url" => U(
                                "Main/images") . "&delall=1","tag" => "#body"));
            }
            else if ($del == "sel")
            {
                $where = explode("(", I("get.where"));
                $where = explode(")", $where[1]);
                $where = explode(",", $where[0]);
                $js = "";
                
                foreach ($where as $val)
                {
                    unlink($data[$val]["path"]);
                    $js .= '$("#imgbtn' . $val . '").closest("tr").remove();';
                }
                
                $this->ajaxReturn(
                        array("echo" => 1,"info" => "删除成功!","callback" => $js));
            }
            else
            {
                unlink($data[$del]["path"]);
                $this->ajaxReturn(
                        array("echo" => 1,"info" => "删除成功!","callback" => '
                        $("#imgbtn' . $del .
                                 '").closest("tr").remove();'));
            }
        }
        
        if (!I("get."))
            $this->parseImgList();
        
        $this->setNav("&nbsp;->&nbsp;系统设置&nbsp;->&nbsp;残图管理");
        $this->mainPage("images");
        
        // 设置添加按钮弹出窗口
        $this->setTool("tool_btn_down", 
                array("txt" => "一键清除","icon" => "asterisk","url" => U() .
                         "&del=all"));
        $this->setTool("close_tool_find", 1);
        
        // 设置批量删除操作
        $this->setBatch("删除所选", U() . "&del=sel", 
                array("query" => true,'icon' => "remove",'ext' => 'confirm="确定批量删除吗？"'));
        
        $this->setOp("删除", U() . "&del=[id]", 
                array("query" => true,"ext" => 'confirm="确定删除吗？" id="imgbtn[id]"'));
        $this->setData("data_list", F("imglist"));
        $this->setTitle(array("目录名","图片预览","文件大小","上传时间"));
        $this->setField(array("table","path","size","ctime"));
        $this->setData("data_field 1 run", "Main/disImg");
        
        $this->display();
    }
    
    // 失联的图片
    public function noLinkImg()
    {
        $list = sqlAll("select * from card_type");
        $nlist = array();
        
        foreach ($list as $key => $row)
        {
            $img = str_replace(",", "/", $row["img"]);
            if (!file_exists(__UP__ . $img))
                $nlist[$key] = $row;
        }
        
        $data = new SmallDataList("nolnkimglist", "", 0);
        $data->set("data_list", $nlist);
        $title = array("发卡银行","卡种名称","卡片类型","授信上限","更新时间");
        $field = array("bank","name","type","amount","update_time");
        $_SESSION["excel"]["title"] = array();
        foreach($title as $key => $val)
        {
            $_SESSION["excel"]["title"][$field[$key]] = $val;
        }
        $_SESSION["excel"]["data"] = $nlist;
        $_SESSION["excel"]["filename"] = "失链图片报表";
        $data->setTitle($title);
        $data->setField($field);
        $excel = new FormElement("btn_excel", "button", "下载报表", array("icon" => "cloud-download",
                "pclass" => "text-right kyo_bottom_margin", 
                "ext" => 'type="button" kopen="kopen" url="'.U("KyoCommon/Index/excelExport").'"
                 callback=\'$(this).prop("disabled", true);\''));
        echo $excel->fetch().$data->fetch();
    }
    
    // -----------------------------上传数据-code-----------------------------
    public function upData()
    {
        $up = new Form("");
        $up->setElement("upgroup", "group", "上传交易数据");
        $up->setElement("input_excel", "file", "交易数据路径", 
                array("element_cols" => 4,"label_cols" => 4,"accept" => "application/vnd.ms-excel","action" => U(
                        "KyoCommon/Index/excelImport")));
        $up->set("close_btn_down", 1);
        echo "<br />" . $up->fetch();
        
        // ////////////////////////////////////////////////
        
        // $list = new SmallDataList("card_deal", "pos");
        // $list->setPage("size", 10);
        // $list->set("close_num", 1);
        // $list->set("where", "status=0");
        // $list->setTitle(array("上传时间", "上传结果"));
        // $list->setField(array("code", "models"));
        
        // $up->setElement("recordgroup", "group", "上传记录");
        // $up->setElement("recordlist", "static", "", array("sig_row" => 1,
        // "close_label" => 1, "element_cols" => 12, "value" =>
    // $list->fetch()));
        // $list->set("close_chkall", 1);
        
        // $this->assign("body", $up->fetch());
        // $this->display();
    }
}

