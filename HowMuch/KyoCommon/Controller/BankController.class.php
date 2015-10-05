<?php
namespace KyoCommon\Controller;
use Think\Controller;
use Common\Controller\ListPage;
use Common\Controller\FormElement;

class BankController extends ListPage
{
    static public function getPop($type, $title = "")
    {
        $pop = "";
        switch ($type)
        {
        	case "add":
                if (!$title)
                    $title = "新增银行";
                $pop = "w:450,h:260,n:'bankadd',t:".$title;
        	    break;
        	case "edit":
                if (!$title)
                    $title = "编辑银行";
                $pop = "w:450,h:260,n:'bankedit',t:".$title;
        	    break;
        	case "branchadd":
                if (!$title)
                    $title = "新增支行";
                $pop = "w:450,h:420,n:'branchadd',t:".$title;
        	    break;
        	case "branchedit":
                if (!$title)
                    $title = "编辑支行";
                $pop = "w:450,h:420,n:'branchedit',t:".$title;
        	    break;
        	case "card":
                if (!$title)
                    $title = "卡种列表";
                $c = 'partial_refresh(\''.session("prev_urlbank").'\', \'.bank\')';
                $pop = "w:1000,h:550,n:'cardlist',b:$c,t:".$title;
        	    break;
        	case "cardadd":
                if (!$title)
                    $title = "添加卡种";
                $pop = "w:500,h:500,n:'cardadd',t:".$title;
        	    break;
        	case "cardedit":
                if (!$title)
                    $title = "编辑卡种";
                $pop = "w:500,h:500,n:'cardedit',t:".$title;
        	    break;
        	default:
        	    break;
        }
        return $pop;
    }
//-----------------------------银行管理-code-----------------------------
    public function index()
    {
        //设置页面标题
        $this->setNav("&nbsp;->&nbsp;系统设置&nbsp;->&nbsp;银行管理");
        //初始化并且设置数据库表名
        $this->mainPage("bank");
        /////////////////////////////////////////////
        //设置添加按钮弹出窗口
        $this->setTool("tool_btn_down", array("txt" => "新增银行", "icon" => "plus", 
                                              "url" => U()."&form=add",
                                              "pop" => $this->getPop("add")));
        //设置查询选项 只能按银行名称查询
        $this->setFind("typelist name", array("txt" => "银行名称", "val" => "name"));
        
        //设置批量删除操作
//         $this->setBatch("删除所选", U()."&form=del", array("query" => true, 
//                                     'icon' => "remove", 'ext' => 'confirm="确定批量删除吗？"'));
        
        //////////////////////////////////////////////////////
        //设置表单成员， 添加、编辑和信息的元素  uniq为不得重复
        $this->setElement("name", "string", "银行名称", array("bool" => "uniq required", 
                "maxlength" => 10));
        //执行原生sql获取排序最大值
        $sort_max = $this->sqlCol("select max(sort_id) from bank"); 
        $this->setElement("sort_id", "num", "排序号", array("value" => ++$sort_max, 
                "bool" => "readonly required", "maxlength" => 5));
        
        //设置表单添加或修改自动完成项  创建时间和更新时间不需要输入自动填写
        $this->setForm("auto", array(
        	   array("create_time", 'getcurtime', 1, "function"),
        	   array("update_time", 'getcurtime', 3, "function"),
        ));
        //////////////////////////////////////////////////////
        //设置列表排序字段
//         $this->setData("where", "name='中国银行'");
        $this->setData("order", "sort_id");
        
        //设置列表标题
        $this->setTitle(array("银行名称", "卡种数", "排序号", "更新时间"));
//         $this->setData("data_title 1 sort", "card_num");
//         $this->setData("data_title 2 sort", "sort_id");
        
        //设置列表字段名
        $this->setField(array("name", "card_num", "sort_id", "update_time"));
        $this->setData("close_chkall", 1);
        //设置数据操作链接
        $this->setOp("上移", U("KyoCommon/Index/listMove")."&table=bank&where='sort_id<[sort_id]'",
        		array("query" => true, "ext" => '#confirm="确定向上移动吗?"'));
        $this->setOp("下移", U("KyoCommon/Index/listMove")."&table=bank&where='sort_id>[sort_id]'",
        		array("query" => true, "ext" => '#confirm="确定向下移动吗?"'));
        $this->setOp("卡种", U("KyoCommon/Bank/minCardType")."&bank=[name]", 
                array("pop" => $this->getPop("card", "[name] 卡种列表")));
        $this->setOp("编辑", U()."&form=edit&where='id=[id]'", 
                array("pop" => $this->getPop("edit", "编辑[name]")));
//         $this->setOp("删除", U()."&form=del&where='id=[id]'", 
//                 array("query" => true, "ext" => 'confirm="确定删除吗？"'));
        $this->display();
    }
    
    public function branch()
    {
        $this->setNav("&nbsp;->&nbsp;系统设置&nbsp;->&nbsp;支行管理");
        $this->mainPage("bank_branch");
        
        $this->setFind("typelist bname", array("txt" => "支行名称", "val" => "bname"));
        $this->setFind("typelist affiliate", array("txt" => "电子联行号", "val" => "affiliate"));
        $this->setFind("typelist type", array("txt" => "银行名称", "val" => "bank"));
        
        $this->setTool("tool_btn_down", array("txt" => "新增支行", "icon" => "plus", 
                                              "url" => U()."&form=add",
                                              "pop" => $this->getPop("branchadd")));
        
        $this->setElement("bname", "string", "支行名称", array("bool" => "required", "maxlength" => 30));
        $this->setElement("affiliate", "string", "电子联行号", array("bool" => "required"));
        $this->setElement("bank", "autocomplete", "所属银行", array("bool" => "required",
                          "list" => parse_autocomplete("select name from bank order by sort_id")));        
        $this->setElement("province", "string", "所在省份", array("bool" => "required"));
        $this->setElement("city", "string", "所在城市", array("bool" => "required"));
        //设置表单添加或修改自动完成项  创建时间和更新时间不需要输入自动填写
        $this->setForm("auto", array(
        	   array("update_time", 'getcurtime', 3, "function")
        ));
        $this->setData("order", "update_time desc");
        //设置列表标题
        $this->setTitle(array("支行名称", "电子联行号", "银行名称", "省份", "城市", "更新时间"));
        $this->setField(array("bname", "affiliate", "bank", "province", "city", "update_time"));
        //设置数据操作链接
        $this->setData("close_chkall", 1);
        $this->setOp("编辑", U()."&form=edit&where='id=[id]'", 
                array("pop" => $this->getPop("branchedit")));
        
        $this->display();
    }
    
    //卡种管理
    public function cardType($display = true)
    {
        $this->setNav("&nbsp;->&nbsp;系统设置&nbsp;->&nbsp;卡种管理");
        $this->mainPage("card_type");
        
        $this->setFind("typelist bank", array("txt" => "发卡银行", "val" => "bank"));
        $this->setFind("typelist name", array("txt" => "卡种名称", "val" => "name"));
        $this->setFind("typelist type", array("txt" => "卡种类别", "val" => "type"));
        
        $this->setTool("tool_btn_down", array("txt" => "新增卡种", "icon" => "plus", 
                                              "url" => U()."&form=add",
                                              "pop" => $this->getPop("cardadd")));
        //设置批量删除操作
        $this->setBatch("删除所选", U()."&form=del", array("query" => true, 
                                    'icon' => "remove", 'ext' => 'confirm="确定批量删除吗？"'));
        
        $this->setElement("bank", "autocomplete", "发卡银行", array("bool" => "required",
                          "url" => U("KyoCommon/Bank/getSortNum"),
                          "tag" => ".sel_sort_id",
                          "list" => parse_autocomplete("select name from bank order by sort_id")));        
        $this->setElement("type", "autocomplete", "卡种类型", array("bool" => "required sync",
                          "list" => parse_autocomplete("select distinct type from card_type")));        
        $this->setElement("name", "string", "卡种名称", array("bool" => "required", "maxlength" => 20));
        $this->setElement("card", "string", "主账号", array("bool" => "required", "maxlength" => 16));
        $this->setElement("amount", "num", "授信上限", array("bool" => "required", "hint" => "money"));
        $this->setElement("img", "file", "卡种图片", array("bool" => "required"));
        $this->setElement("sort_id", "num", "排序号", array("bool" => "readonly required", "pclass" => "sel_sort_id"));
        //设置表单添加或修改自动完成项  创建时间和更新时间不需要输入自动填写
        $this->setForm("auto", array(
        	   array("create_time", 'getcurtime', 1, "function"),
        	   array("update_time", 'getcurtime', 3, "function")
        ));
        //设置自动计数配置
        $this->setForm("addNum", array(
                array("bank", "card_num", "name='[bank]'"),
        ));
        $this->setForm("decNum", array(
                array("bank", "card_num", "name='[bank]'"),
        ));
        $this->setData("order", "update_time desc");
        //设置列表标题
        $this->setTitle(array("发卡银行", "卡种名称", "卡片类型", "主账号", "授信上限", "更新时间"));
        $this->setField(array("bank", "name", "type", "card", "amount", "update_time"));
        //设置数据操作链接
        $this->setData("close_chkall", 1);
        $this->setOp("图片", U("KyoCommon/Upload/upload_show")."&path=[img]", 
                array("pop" => "w:560,h:430,n:'card_type_img',t:[name] 卡种图片"));
        $this->setOp("编辑", U()."&form=edit&where='id=[id]'", 
                array("pop" => $this->getPop("cardedit")));
        $this->setOp("删除", U()."&form=del&where='id=[id]'", 
                array("query" => true, "ext" => 'confirm="确定删除吗？"'));
        if ($display)
            $this->display();
    }
    
    //获取排序号
    public function getSortNum()
    {
        $sort_max = $this->sqlCol("select max(sort_id) from card_type where bank='".I("get.val")."'");
        $sform = new FormElement("sort_id", "num", "排序号", array("value" => ++$sort_max, 
                "close_element_div" => 1, "begin" => 0, "over" => 0, "close_label" => 1,
                "bool" => "readonly required"));
        echo $sform->fetch();
    }
    
    //银行所属卡种管理迷你版
    public function minCardType($bank = "")
    {
        $this->cardType(false);
        $this->set("close_nav", 1);
        $this->set("close_body_top", 1);
        $this->set("close_top_ctrl", 1);
        $this->setTool("close_tool_find", 1);
        $this->setTool("close_batch", 1);
        $this->setTool("tool_btn_down url", U()."&form=add&bank=".$bank);
        $this->setTool("tool_btn_down class", "kyo_bottom_margin");
        
        $this->setData("close_top_page", 1);
        $this->setPage("size", 10);
        $this->setPage("param", "bank=".$bank);
        $this->setElement("bank", "string", "发卡银行", array("bool" => "readonly required", "value" => $bank));
        $sort_max = $this->sqlCol("select max(sort_id) from card_type where bank='".$bank."'");
        $this->setElement("sort_id", "num", "排序号", array("bool" => "readonly required", "value" => ++$sort_max));
        
        $this->setForm("return tag", ".cardlist");
        $this->setData("where", "bank='".$bank."'");
        //设置数据操作链接
        $this->setData("close_chkall", 1);
        $this->display();
    }
}

?>
