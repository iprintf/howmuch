<?php
namespace Pos\Controller;
use Common\Controller\ListPage;
use Common\Controller\FormElement;
use Common\Controller\Form;

class PosTypeController extends ListPage
{
    public function index()
    {
        $this->setNav("&nbsp;->&nbsp;POS&nbsp;->&nbsp;商户类型管理");
        $this->mainPage("pos_type");
        $this->setTool("tool_btn_down", array("txt" => "新增商户类型", "icon" => "plus", 
//                                 "url" => U("add"), 
                                   "url" => U()."&form=add",
                                "pop" => "w:600,h:500,n:'postypeadd',t:新增商户类型"
                        ));
        
        $this->setFind("typelist mcc", array("txt" => "商户MCC代码", "val" => "mcc"));//增加搜索选择
        $this->setFind("typelist name", array("txt" => "商户经营类别", "val" => "name"));
//         $this->setPage("size", 10);//配置界面显示多少条记录
        
//         $this->setBatch("删除所选", U()."&form=del", array("query" => true,
//                 'icon' => "remove", 'ext' => 'confirm="确定批量删除吗？"'));
        
        $this->setForm("name", "posform");
        //设置表单成员， 添加、编辑和信息的元素  uniq为不得重复
        $this->setElement("mcc", "string", "MCC代码", array("bool" => "uniq required"));
        $this->setElement("mcc_cost", "num", "MCC费率", array("bool" => "required", "addon" => "%"));
        $this->setElement("name", "string", "商户类型", array("bool" => "uniq required"));
        $this->setElement("month_max", "num", "月交易上限", array("bool" => "required", "addon" => "元"));
        
        $this->setForm("handle_run post", "PosType/post_handle");
        $this->setForm("handle_run show_edit", "PosType/show_edit_win");
        $this->setForm("handle_run del", "PosType/del_handle");
        $this->setForm("handle_run info", "PosType/info_handle");
        
        $addBtn = new FormElement("add_time_btn", "link", "", array("icon" => "plus", 
                                        "url" => U("time_win")."&form=add",
                                        "pop" => "w:460,h:360,n:'addtime',t:新增交易时间和金额区间"
                                    ));
        
        $this->setElement("dealtime", "static", "时间金额设置", array("sig_row" => true,
                 "value" => $addBtn->fetch()));
        
        $sort_max = $this->sqlCol("select max(sort_id) from pos_type");
        $this->setElement("sort_id", "num", "排序号", array("value" => ++$sort_max, "bool" => "required"));
        
        //设置表单添加或修改自动完成项  创建时间和更新时间不需要输入自动填写
        $this->setForm("auto", array(
                array("create_time", 'getcurtime', 1, "function"),
                array("update_time", 'getcurtime', 3, "function"),
        ));
        
        $this->setTitle(array("MCC代码", "MCC费率", "商户类别", "排序号", "更新时间"));
        $this->setField(array("mcc", "mcc_cost", "name", "sort_id", "update_time"));
        $this->setData("data_field 1 fun", "field_conv_per");
        $this->setField("name", array("name" => "2", "url" => U()."&form=info&where='id=[id]'", 
                                        "pop" => "w:530,h:400,t:商户类型详细信息"));
        
        $this->setData("close_chkall", 1);
        $this->setOp("编辑", U()."&form=edit&where='id=[id]'",
                array("pop" => "w:600,h:500,n:'postypeedit',t:编辑商户类型"));
//         $this->setOp("删除", U()."&form=del&where='id=[id]'",
//                 array("query" => true, "ext" => 'confirm="确定删除吗？"'));
        $this->display();
    }
    
    public function info_handle($con, $formObj)
    {
        $obj = & $formObj->form["dataObj"];
        $data = $obj->where($con)->select();
        
        $formObj->formDataInfo($data);
        
        $formObj->form["element"]["mcc_cost"]["value"] = ($formObj->form["element"]["mcc_cost"]["value"] * 100)."%";
        $newid = substr(strchr($con, "="), 1);
//         $timelist = M("pos_time")->where("pcode='".$newid."'")->select();
//         $html = "";
//         foreach ($timelist as $time)
//         {
//             $html .= '<div id="'.$name.'" class="col-md-12" style="padding-left:0">';
//             $html .= "时间：".$time["start_time"]." - ".$time["end_time"]."&nbsp;&nbsp;";
//             $html .= "金额：".fill_nbsp($time["start_money"], 6)." - ".fill_nbsp($time["end_money"], 6)."&nbsp;&nbsp;";
//             $html .= '</div>';
//         }
       $formObj->form["element"]["dealtime"]["value"] = $this->get_time_list($newid, false);
//         $formObj->form["element"]["dealtime"]["value"] = $html;
    }
    
    public function del_handle($con, & $formObj)
    {
        $obj = & $formObj->form["dataObj"];
        $ret = $obj->where($con)->delete();
        $newid = strchr($con, "=");
        $newid = $newid ? $newid : strchr($con, "in");
        M("pos_time")->where("pcode ".$newid)->delete();
        $formObj->form["return"]["info"] = $ret ? "删除成功!" : "没有匹配到要删除的数据!";
        if ($ret)
            return true;
        return false;
    }
    
    public function post_handle(& $formObj)
    {
        $form = & $formObj->form;
        $obj = & $form["dataObj"];
        
        if (!($_POST["start_time"] && $_POST["end_time"] &&
            $_POST["start_money"] && $_POST["end_money"]))
        {
            $form["return"]["info"] = "请填写交易时间和金额区间!"; 
            $ret = false;
        }
        else
        {
            $_POST["day_max"] = round($_POST["month_max"] / 30);
            $_POST["mcc_cost"] = $_POST["mcc_cost"] / 100;
            
            if ($_POST["id"])
            {
                $ret = $obj->auto($form["auto"])->create($_POST, 2);
                $form["return"]["info"] = $obj->getError();
                $ret = $ret ? $obj->where("id=".$_POST["id"])->save(): $ret;
//                 $form["return"]["info"] .= $obj->getLastSql();
                if ($ret)
                {
                    M("pos_time")->where("pcode='".$_POST["id"]."'")->delete();
                    $ret =  $_POST["id"];
                    $str = "编辑";
                }
            }
            else
            {
                $ret = $obj->validate($form["validate"])->create();
                $ret = $ret ? $obj->auto($form["auto"])->create($_POST, 1) : $ret;
                $form["return"]["info"] = $obj->getError();
                $ret = $ret ? $obj->add(): $ret;
                $str = "添加";
            }
            
            if ($ret)
            {
                $ind = count($_POST["start_time"]);
                for ($i = 0; $i < $ind; $i++)
                {
                    $data["pcode"] = $ret;
                    $data["start_time"] = $_POST["start_time"][$i];
                    $data["end_time"] = $_POST["end_time"][$i];
                    $data["start_money"] = $_POST["start_money"][$i];
                    $data["end_money"] = $_POST["end_money"][$i];
                    $pObj = M("pos_time");
                    $pObj->add($data);
                }
                $form["return"]["info"] .= $str."成功!";
            }
            else
                $form["return"]["info"] .= "输入数据格式有误!";
        }
        
        if (!$ret)
        {
            $form["return"]["close"] = 0;
            $form["return"]["url"] = "";
        }
            
        $this->ajaxReturn($form["return"]);
        exit(0);
    }
    
    static public function get_time_list($id, $type = true)
    {
        $timelist = M("pos_time")->where("pcode='".$id."'")->select();
        $html = "";
        foreach ($timelist as $time)
        {
            $name = "";
            if ($type)
            {
                $t = explode(":", $time["start_time"]);
                $t[0] = $t ? $t[0] : "00";
                $t[1] = isset($t[1]) ? $t[1] : "00";
                $edit_url = "&shour=".$t[0]."&smin=".$t[1];
                $t = explode(":", $time["end_time"]);
                $t[0] = $t ? $t[0] : "00";
                $t[1] = isset($t[1]) ? $t[1] : "00";
                $edit_url .= "&ehour=".$t[0]."&emin=".$t[1];
                $edit_url .= "&smoney=".$time["start_money"]."&emoney=".$time["end_money"];
                
                $name = "timelist".mt_rand(0, 99);
            }
            $html .= '<div id="'.$name.'" class="col-md-12" style="padding-left:0">';
            if ($type)
            {
                $html .= '<a href="#" url="'.U("PosType/time_win").'&form=add&name='.$name.$edit_url.'"';
                $html .= 'pop="{w:450,h:410,n:\'edittime\',t:编辑交易时间和金额区间}">';
                $html .= '<span class="glyphicon glyphicon-edit"></span></a>&nbsp;&nbsp;';
            }
            $html .= "时间：".$time["start_time"]." - ".$time["end_time"]."&nbsp;&nbsp;";
            $html .= "金额：".fill_nbsp($time["start_money"], 6)." - ".fill_nbsp($time["end_money"], 6)."&nbsp;&nbsp;";
            if ($type)
            {
                $html .= '<a href="#" me="time_minus"><span class="glyphicon glyphicon-minus"></span></a>';
                $html .= '<input type="hidden" kform="posform" name="start_time[]" value="'.$time["start_time"].'" />';
                $html .= '<input type="hidden" kform="posform" name="end_time[]" value="'.$time["end_time"].'" />';
                $html .= '<input type="hidden" kform="posform" name="start_money[]" value="'.$time["start_money"].'" />';
                $html .= '<input type="hidden" kform="posform" name="end_money[]" value="'.$time["end_money"].'" />';
            }
            $html .= '</div>';
        }
        return $html;
    }
    
    public function show_edit_win($con, & $formObj)
    {  
        $form = & $formObj->form;
        $obj = & $form["dataObj"];
        
        $data = $obj->where($con)->select();
        $formObj->formDataShowEdit($data);
        
        $form["element"]["mcc_cost"]["value"] = $form["element"]["mcc_cost"]["value"] * 100;
        $newid = substr(strchr($con, "="), 1);
        
        $form["element"]["dealtime"]["value"] = $this->get_time_list($newid).$form["element"]["dealtime"]["value"];
    }
    //时间金额选择窗口及处理
    public function time_win()
    {
        $this->mainPage("empty");
        $this->setForm("name", "timeform");
        
        if (IS_POST)
        {
            if ($_POST["start_hour"] > $_POST["end_hour"] || $_POST["start_money"] > $_POST["end_money"] ||
                ($_POST["start_hour"] == $_POST["end_hour"] && $_POST["start_min"] > $_POST["end_min"]))
            {
                $this->setForm("return echo", 1);
                $this->setForm("return close", 0);
                $this->setForm("return info", "请正确输入交易时间和金额范围值!");
                $this->setForm("return url", "");
                $this->setForm("return tag", "");
                $this->setForm("return html", "");
            	$this->ajaxReturn($this->getForm("return"));
                exit(0); 
            }
                
            $start_time = fill_zero(I("post.start_hour")).":".fill_zero(I("post.start_min"));
            $end_time = fill_zero(I("post.end_hour")).":".fill_zero(I("post.end_min"));
            $start_money = I("post.start_money");
            $end_money = I("post.end_money");
            
            $edit_url = "&shour=".I("post.start_hour")."&smin=".I("post.start_min");
            $edit_url .= "&ehour=".I("post.end_hour")."&emin=".I("post.end_min");
            $edit_url .= "&smoney=".I("post.start_money")."&emoney=".I("post.end_money");
            
            if (!I("post.op"))
            {
                $name = "timelist".mt_rand(0, 99);
                $html = '<div id="'.$name.'" class="col-md-12" style="padding-left:0">';
            }
            else
            {
                $name = I("post.op");
                $html = '';
            }
            
            $html .= '<a href="#" url="'.U().'&form=add&name='.$name.$edit_url.'"';
            $html .= 'pop="{w:450,h:410,n:\'edittime\',t:编辑交易时间和金额区间}">';
            $html .= '<span class="glyphicon glyphicon-edit"></span></a>&nbsp;&nbsp;';
            $html .= "时间：".$start_time." - ".$end_time."&nbsp;&nbsp;";
            $html .= "金额：".fill_nbsp($start_money, 6)." - ".fill_nbsp($end_money, 6)."&nbsp;&nbsp;";
            $html .= '<a href="#" me="time_minus"><span class="glyphicon glyphicon-minus"></span></a>';
            $html .= '<input type="hidden" kform="posform" name="start_time[]" value="'.$start_time.'" />';
            $html .= '<input type="hidden" kform="posform" name="end_time[]" value="'.$end_time.'" />';
            $html .= '<input type="hidden" kform="posform" name="start_money[]" value="'.$start_money.'" />';
            $html .= '<input type="hidden" kform="posform" name="end_money[]" value="'.$end_money.'" />';
            
            if (!I("post.op"))
            {
                $html .= '</div>';
                $this->setForm("return tag", "#dealtime_id");
                $this->setForm("return append", 1);
            }
            else
                $this->setForm("return tag", "#".$name);
            
            $this->setForm("return echo", 0);
            $this->setForm("return url", "");
            $this->setForm("return html", $html);
            
        	$this->ajaxReturn($this->getForm("return"));
            exit(0); 
        }
        
        if (I("get.name"))
            $this->setElement("op", "hidden", "", array("value" => I("get.name")));
            
        
        $this->setForm("btn 0 txt", "确定");
        
        $hour_list = parse_select_list("for", array(8, 23, 1, 1), "小时");
        $min_list = parse_select_list("for", array(0, 60, 5, 1), "分钟");
        
        $this->setElement("start_hour", "select", "起始时间", array("bool" => "required", "group" => "start",
                "value" => I("get.shour"), "list" => $hour_list));
        $this->setElement("start_min", "select", "", array("bool" => "required", 
                "group" => "end", "value" => I("get.smin"), "list" => $min_list));
        
        $this->setElement("end_hour", "select", "结束时间", array("bool" => "required", 
                "group" => "start", "value" => I("get.ehour"), "list" => $hour_list));
        $this->setElement("end_min", "select", "", array("bool" => "required", 
                "group" => "end",  "value" => I("get.emin"), "list" => $min_list));
        
        $this->setElement("start_money", "num", "起始金额", array("bool" => "required", 
                "addon" => "元", "value" => I("get.smoney")));
        $this->setElement("end_money", "num", "结束金额", array("bool" => "required",
                "addon" => "元", "value" => I("get.emoney")));
        $this->display();
    }
    
}