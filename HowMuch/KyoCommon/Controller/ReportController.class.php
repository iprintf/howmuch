<?php
namespace KyoCommon\Controller;
use Think\Controller;
use Common\Controller\Form;
use Common\Controller\FormElement;
use Common\Controller\SmallDataList;

class ReportController extends Controller
{
    private $form;
    private $admin; 
    private $proxy; 
    private $sid; 
    private $uid; 
    private $report = array();
//         col         报表列表一行显示几个报表
//         el_col      每个报表占多少列
//         group       报表种类列表
//         list        报表列表名称
    
    public function __construct($option = array())
    {
        parent::__construct();        
        if (!is_login())
            $this->redirect("Home/Index/index");
        
        $this->admin = get_user_info("admin");
        $this->proxy = get_user_info("proxy_id");
        $this->sid = get_user_info("sid");
        $this->uid = get_user_info("uid");
        $this->form = new Form("", array("action" => U("KyoCommon/Report/report"), "kajax" => "true", "name" => "reportform", 
                "class" => "form-horizontal col-sm-12 col-md-12 main_first_row", "cols" => 2));
        if ($this->admin != 9 && $this->admin != 0)
            $this->form->setElement("report_title", "group", get_platfrom_name("name")."平台&nbsp;->&nbsp;综合查询&nbsp;->&nbsp;报表查询");
        else
            $this->form->setElement("report_title", "group", get_platfrom_name("name")."平台&nbsp;->&nbsp;报表查询");
//         $this->form->set("btn 0 txt", "查询报表");
        $this->form->set("btn 0 class", "hidden");
        $this->form->set("js", "kyo_report");
        
        $this->report["col"] = 4;
        $this->report["el_col"] = "";
        $this->report["group"] = array("常用报表");
        
        foreach ($option as $key => $val)
        {
            $this->report[$key] = $val;
        }
        if (!isset($option["el_col"]) || $option["el_col"] == "")
            $this->report["el_col"] = 12 / $this->report["col"];
    }
    
    public function commonSearch()
    {
        $this->form->setElement("proxy", "select", "代理选择", array(
           "tag" => ".sel_sub_id",
           "url" => U("KyoCommon/Report/getSub"),
           "list" => parse_select_list("select id,proxy_sub_name from users where type=8", "", "", "所有代理商"))
        );
        
        $rday = new FormElement("rep_type", "sradio", "日报", array("element_cols" => 3, "value" => "day",
               "class" => "rep_type",
                "id" => "rep_type_id1",
                "bool" => "checked",
               "tag" => ".sel_date_id",
               "url" => U("KyoCommon/Report/getDate"),
        ));
        $rweek = new FormElement("rep_type", "sradio", "周报", array("element_cols" => 3, "value" => "week",
               "class" => "rep_type",
                "id" => "rep_type_id2",
               "tag" => ".sel_date_id",
               "url" => U("KyoCommon/Report/getDate"),
        ));
        $rmonth = new FormElement("rep_type", "sradio", "月报", array("element_cols" => 3, "value" => "month",
               "class" => "rep_type",
                "id" => "rep_type_id3",
               "tag" => ".sel_date_id",
               "url" => U("KyoCommon/Report/getDate"),
        ));
        $this->form->setElement("rep_type_row", "custom", "报表类型", array("label_cols" => 2, 
                "element_cols" => 4,
                "custom_html" => $rday->fetch().$rweek->fetch().$rmonth->fetch()
        ));
        
        $this->form->setElement("sub", "select", "分站选择", array(
                "pclass" => "sel_sub_id",
                "list" => parse_select_list("array", array(0), array("所有分站")),
        ));
        $now_date = date("Y-m-d");
        $this->form->setElement("rep_date_row", "custom", "日期选择", array("label_cols" => 2, 
                "pclass" => "sel_date_id", "element_cols" => 4, "custom_html" => $this->getDate(),
        ));
    }
    
    //设置数据列表配置
    public function set($name, $value)
    {
        if ($name == "" || $value == "")
            return false;
       
        $name_sel = explode(" ", $name);
        $name_len = count($name_sel);
        if ($name_len == 1)
            $this->report[$name] = $value;
        else
        {
            $obj = & $this->report;
            for ($i = 0; $i < $name_len; $i++)
            {
                $obj = & $obj[$name_sel[$i]];
            }
        
            $obj = $value;
        }
        return true;
    }
    
    //获取数据列表类配置参数,为了支持自定义操作
    public function get($name = "")
    {
        if ($name == "")
            return $this->report;
        
        $name_sel = explode(" ", $name);
        $name_len = count($name_sel);
        if ($name_len == 1)
            return $this->report[$name];
        
        $obj = & $this->report;
        for ($i = 0; $i < $name_len; $i++)
        {
            $obj = & $obj[$name_sel[$i]];
        }
        
        return $obj;
    }
    
    public function setReport($name, $title, $group, $option = array())
    {
        $this->report["list"][$group][$name]["title"] = $title;
        
        $rep = & $this->report["list"][$group][$name];
        $rep["url"] = U();
        $rep["w"] = "1100";
        $rep["h"] = "550";
        if ($option["excel"])
            $rep["h"] = "580";
            
        $rep["pop_title"] = $title;
        $rep["excel"] = false; 
        $rep["num"] = 1; 
        $rep["pagesize"] = 12;
        
        karray($rep, array("pop", "field", "dtitle", "pro", "arg", "small_title", "pop_title"));
        
        foreach ($option as $key => $val)
        {
            $rep[$key] = $val;
        }
        
        if ($rep["pop"] == "")
            $rep["pop"] = "w:".$rep["w"].",h:".$rep["h"].",n:'report_".$name."',t:".$title;
        
//         dump($rep);
        
    }
    
    public function report()
    {
        $sdate = I("get.sdate");
        $edate = I("get.edate");
        $sid = I("get.sid");
        $proxy = I("get.proxy");
        $name = I("get.small");
        $grp = I("get.grp");
        $rep = $this->report["list"][$grp][$name];
        
        foreach ($_GET as $key => $val)
        {
            $param .= $key."=".urlencode($val)."&";
        }
        
    	$data = new SmallDataList($name, "", 0, 
    	        array("close_num"=> !$rep["num"], "page" => array("size" => $rep["pagesize"])));
        if ($rep["small_title"])
        	$data->set("title_ext_front", '<tr><th colspan="'.count($rep["dtitle"]).'">'.$rep["small_title"].'</th></tr>');
        
        $arg = str_replace(" ", "", $rep["arg"]);
        $arg = str_replace("[sdate]", "'".$sdate."'", $arg);
        $arg = str_replace("[edate]", "'".$edate."'", $arg);
        $arg = str_replace("[uid]", $this->uid, $arg);
        if ($sid == "")
            $arg = str_replace("[sid]", $this->sid, $arg);
        else if ($sid == 0)
            $arg = str_replace("[sid]", "null", $arg);
        else
            $arg = str_replace("[sid]", $sid, $arg);
        
        if ($proxy == "")
            $arg = str_replace("[proxy]", $this->proxy, $arg);
        else if ($proxy == 0)
            $arg = str_replace("[proxy]", "null", $arg);
        else
            $arg = str_replace("[proxy]", $proxy, $arg);
        $arg = explode(",", $arg);
        
        if ($rep["excel"])
            $data->set("excel", array("name" => $rep["pop_title"]));
        
        if ($rep["pagesize"] == 0)
        	$data->setCustomList($rep["pro"], false, $arg);
        else
        {
        	$data->setCustomList($rep["pro"], true, $arg);
            $data->setPage("param", $param);
        }
//         dump(M()->getLastSql());
    	$data->setTitle($rep["dtitle"]);
    	$data->setField($rep["field"]);
    	
        if ($rep["excel"] && !$_GET["p"])
        {
//             $_SESSION["excel"]["title"] = array();
//             foreach($rep["dtitle"] as $key => $val)
//             {
//                 $_SESSION["excel"]["title"][$rep["field"][$key]] = $val;
//             }
//             $_SESSION["excel"]["data"] = $data->get("data_list");
//             $_SESSION["excel"]["filename"] = $rep["pop_title"];
            $kopen = "";
            if ($this->admin == 9 || $this->admin == 7 || $this->admin == 0)
                $kopen = "kopen";
            $excel = new FormElement("btn_excel", "button", "下载报表", array("icon" => "cloud-download",
                    "pclass" => "text-right kyo_bottom_margin", 
                    "ext" => 'type="button" kopen="'.$kopen.'" url="'.U("KyoCommon/Index/excelExport").'" callback=\'$(this).prop("disabled", true);\''));
            
            echo $excel->fetch();
        }
        echo $data->fetch();
        
        exit(0);
    }
    
    public function showGroup()
    {
        $row = 0;
        $report = $this->report;
        
        foreach ($report["group"] as $key => $group)
        {
            $this->form->setElement("report_group".$key, "group", $group);
            
            $html = "";
            $col = 1;
            foreach ($report["list"][$key] as $name => $l)
            {
                $rObj = new FormElement("rep_name", "sradio", $l["title"], array("element_cols" => $report["el_col"],
                        "value" => $name, "form" => "reportform", "bool" => "required", "url" => $l["url"],
                        "pop" => $l["pop"], "ext" => 'grp="'.$key.'"',
                ));
                $html .= $rObj->fetch();
                if ($col == $report["col"])
                {
                    $this->form->setElement("report_row".$row++, "custom", "", array("sig_row" => true, 
                        "close_label" => 1, "element_cols" => 12, "custom_html" => $html));
                    $html = "";
                    $col = 1;
                }
                else
                    $col++;
            }
            if ($html != "")
                $this->form->setElement("report_row".$row++, "custom", "", array("sig_row" => true, 
                    "close_label" => 1, "element_cols" => 12, "custom_html" => $html));
        }
    }
    
    public function fetch($templatefile = "", $content = "", $prefix = "")
    {
        if (isset($_GET["grp"]) && I("small"))
            $this->report();
            
        $this->commonSearch();
                
        switch ($this->admin)
        {
        	case 8:
                $this->form->set("element proxy value", $this->proxy);
                $this->form->set("element proxy bool", "readonly");
                $this->form->set("element sub list", parse_select_list("select id,proxy_sub_name from 
                                                            users where type=6 and proxy_id=".$this->proxy, 
                                                      "", "", "所有分站"));
        	    break;
        	case 1:
        	case 2:
        	case 3:
        	case 4:
        	case 5:
        	case 6:
                $this->form->setElementSort(array("report_title", "rep_type_row", "rep_date_row"));
                $this->form->set("element rep_date_row label_cols", 1);
                break;
        	default:
        	    break;
        }
        
        $this->showGroup();
        
        return $this->form->fetch();
    }
    
    //获取所属代理的分站列表
    public function getSub($val = "")
    {
        $sub = new FormElement("sub", "select", "", array("close_label" => 1, "close_element_div" => 1, 
            "pclass" => "sel_sub_id", "begin" => 0, "over" => 0, "form" => "reportform",
            "list" => parse_select_list("select id,proxy_sub_name from users where type=6 and proxy_id=".$val, 
                              "", "", "所有分站")));
        echo $sub->fetch();
    }    
    
    //获取报表日期
    public function getDate($type = "")
    {
        switch ($type)
        {
        	case "week":
            	$days = strtotime("1 week ago");
            	$s = date("w", $days);
            	if ($s == 0)
            		$s = 7;
            	$e = 7 - $s;
            	$s = $s - 1;
            	$sdate = date("Y-m-d", strtotime("-".$s." days", $days));
            	$edate = date("Y-m-d", strtotime($e." days", $days));
        	    break;
        	case "month":
    	        $sdate = date("Y-m", strtotime("1 month ago"))."-01";
        	    $edate = date("Y-m-d", strtotime("-1 day", strtotime("+1 month", strtotime($sdate))));
        	    break;
        	case "day":
        	default:
                $sdate = date("Y-m-d", strtotime("1 days ago"));
    	        $edate = $sdate;
        	    break;
        }
        $start_date = new FormElement("start_date", "date", "", array("close_label" => 1, 
            "bool" => "required readonly", "begin" => 0, "over" => 0, "element_cols" => 6,
            "form" => "reportform", "placeholder" => "请选择起始日期", "value" => $sdate));
        $end_date = new FormElement("end_date", "date", "", array("bool" => "required readonly",
                "begin" => 0, "over" => 0, "form" => "reportform",  "element_cols" => 6,
                "placeholder" => "请选择结束日期", "value" => $edate));
        
        $html = $start_date->fetch().$end_date->fetch();
        
        if ($type == "")
            return $html;
        else
            echo $html;
    }
}

?>
