<?php
namespace Home\Controller;
use Common\Controller\ListPage;
use Common\Controller\FormElement;

class ManageController extends ListPage
{
    public function __construct()
    {
        parent::__construct();
        if (get_user_info("admin") != 0)
            $this->redirect("Home/Index/index");
    }

    public function index()
    {
//         $card["code"] = "2014098134";
//         $card["bill"] = 7;
//         $card["finally_repayment_date"] = 20;
//         $card["due_date"] = "2014-10-10";
        
//         dump(update_card_installment("test", $card, true, "2014-09-05"));
        
        //重新生成期数
//         $card_all = sqlAll("select * from card where sid=47 and status=0");
//         $num = 0;
//         foreach ($card_all as $card)
//         {
//             if (!sqlCol("select id from repayment_record where put_card='".$card["card"]."'"))
//             {
// //                 dump("内部代码：".$card["code"].", 出账单日：".$card["bill"].", 最后还款日：T + ".$card["finally_repayment_date"].", 签约日期：".$card["times"].", 到期日期：".$card["due_date"]);
//                 update_card_installment("", $card, true, $card["times"]);
//                 $num++;
//             }
//         }
//         dump($num);
        
        $this->display(false, "Home@Public/index");
    }

    static public function getPop($type, $title = "")
    {
        $pop = "";
        switch ($type)
        {
        	case "add":
                if (!$title)
                    $title = "新增更新版本";
        		$pop = "w:650,h:500,n:'vadd',t:".$title;
        	    break;
        	case "edit":
                if (!$title)
                    $title = "编辑更新版本信息";
        		$pop = "w:650,h:500,c:1,n:'vedit',t:".$title;
        	    break;
        	default:
        	    break;
        }
        return $pop;
    }
    
    public function versionField(& $data, $key)
    {
        $val = "";
        switch ($key)
        {
        	case "vtype":
                $vtype = C("VERSION_TEXT");
                $val = $vtype[$data[$key]];
                break;
        	case "who":
        	    if ($data[$key])
                    $val = $data[$key];
                else
                    $val = "所有终端";
                break;
        	default:
                $val = $data[$key];
        	    break;
        }
        return $val;
    }
    
    
    public function version()
    {
        $this->setNav("&nbsp;->&nbsp;"."更新管理");
        $this->mainPage("kyo_version");
        
        $vtype = C("VERSION_TEXT");
        
        $this->setFind("item 0", array("name" => "vtype", "type" => "select", "default" => "所有类型",
                "list" => parse_select_list("array", array_keys($vtype), $vtype)));
        $this->setFind("item 1", array("name" => "search_type", "type" => "select", 
                "default" => "更新说明", "defval" => "remark"));
        
		//设置添加按钮弹出窗口
        $this->setTool("tool_btn_down", array("txt" => "新增更新", "icon" => "plus",
        		"url" => U()."&form=add",
        		"pop" => $this->getPop("add")));
        
        $this->setForm("name", "versionform");
        
        $this->setElement("vtype", "select", "更新类型", array("bool" => "required",
                "url" => U("Manage/getVersionID"),
                "tag" => ".sel_vid_id",
                "list" => parse_select_list("array", array_keys($vtype), $vtype)));
        $this->setElement("dtype", "select", "终端类型", array("bool" => "required", 
                "list" => parse_select_list("array", array("电脑", "平板"),  array("电脑", "平板"))));
        $this->setElement("who", "string", "更新终端", array());
        $vid = sqlCol("select max(vid) from kyo_version where vtype=1");
        $vid = $vid + 1;
		$this->setElement("vid", "num", "更新版本", array("value" => $vid, "pclass" => "sel_vid_id"));
        $this->setElement("url", "string", "更新路径", array("maxlength" => 250, "value" => "http://u.cc/update/all_v".$vid.".tar.bz2"));
        $this->setElement("remark", "textarea", "更新说明", array());
        //设置表单添加或修改自动完成项  创建时间和更新时间不需要输入自动填写
        $this->setForm("auto", array(
        	   array("create_time", 'getcurtime', 1, "function"),
        	   array("update_time", 'getcurtime', 3, "function"),
        ));
        
        $this->setOp("编辑", U()."&form=edit&where='id=[id]'", array("pop" => $this->getPop("edit")));
//         $this->setOp("删除", U()."&form=del&where='id=[id]'", array("query" => true, 
//                 "ext" => 'confirm="确定删除吗？"'));
        
        $this->setData("tooltip", 1);
    	$this->setTitle(array("更新类型", "终端类型",  "更新终端", "更新代号", "资源地址", "更新说明"));
    	$this->setField(array("vtype", "dtype", "who", "vid", "url", "remark"));
        $this->setData("formatfield", "Manage/versionField");
        
    	$this->display();
    }
    
    public function getVersionID($val)
    {
        $vtype = array("1" => "all", "2" => "grp", "3" => "one");
        $vid = sqlCol("select max(vid) from kyo_version where vtype=".$val);
        $vid = $vid + 1;
        $v = new FormElement("vid", "num", "", array("close_label" => 1, "close_element_div" => 1, 
                "value" => $vid, "bool" => "required", "pclass" => "sel_vid_id", "begin" => 0, 
                "over" => 0, "form" => "versionform"));
        
        $url = "http://u.cc/update/".$vtype[$val]."_v".$vid.".tar.bz2";
        echo js_head('$("#url_id").val("'.$url.'");');
        echo $v->fetch();
    }
}
