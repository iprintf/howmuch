<?php
namespace Home\Controller;
use Think\Controller;
use Common\Controller\ListPage;
use Common\Controller\Form;
use Common\Controller\SmallDataList;

class IndexController extends ListPage
{
    public function __construct()
    {
        parent::__construct();
    }

    static public function getPop($type, $title = "")
    {
        $pop = "";
        switch ($type)
        {
        	case "add":
                if (!$title)
                    $title = "新增用户";
                $pop = "w:450,h:380,n:'useradd',t:".$title;
        	    break;
        	case "edit":
                if (!$title)
                    $title = "编辑用户";
                $pop = "w:450,h:380,n:'useredit',t:".$title;
        	    break;
        	case "goodsadd":
                if (!$title)
                    $title = "新增商品";
                $pop = "w:550,h:480,n:'goodsadd',t:".$title;
        	    break;
        	case "goodsedit":
                if (!$title)
                    $title = "编辑商品";
                $pop = "w:550,h:480,n:'goodsedit',t:".$title;
        	    break;
        	default:
        	    break;
        }
        return $pop;
    }

    static public function get_userlist($attender)
    {
        if (is_array($attender))
            $attender = $attender["owner"];

        $user = sqlAll("select name from user where id in (".
                substr($attender, 1, strlen($attender) - 2).")");
        $userlist = "";
        foreach ($user as $u)
        {
            $userlist .= $u["name"].", ";
        }
        $userlist = rtrim($userlist, ", ");

        return $userlist;
    }

    public function index()
    {
        $this->redirect("Transaction/index");
    }

    public function did_transaction()
    {

    }

    public function end_transaction()
    {

    }

    public function user()
    {
        //设置页面标题
        $this->setNav("&nbsp;->&nbsp;用户管理");
        //初始化并且设置数据库表名
        $this->mainPage("user");

        /////////////////////////////////////////////
        //设置添加按钮弹出窗口
        $this->setTool("tool_btn_down", array("txt" => "新增用户", "icon" => "plus",
                                              "url" => U()."&form=add",
                                              "pop" => $this->getPop("add")));
        //设置查询选项 只能按银行名称查询
        $this->setFind("typelist name", array("txt" => "姓名", "val" => "name"));
        $this->setFind("typelist cellphone", array("txt" => "电话", "val" => "cellphone"));

        //////////////////////////////////////////////////////
        //设置表单成员， 添加、编辑和信息的元素  uniq为不得重复
        $this->setElement("name", "string", "姓名", array("bool" => "required",
                "maxlength" => 10));

		$this->setElement("sex", "radio", "性别", array("bool" => "required","list" => parse_select_list("array", array(1, 2), array("男", "女"))));
		$this->setElement("cellphone", "phone", "手机号", array("bool" => "required"));

        //设置列表标题
        $this->setTitle(array("姓名", "性别", "手机号"));

        //设置列表字段名
        $this->setField(array("name", "sex", "cellphone"));
        $this->setData("close_chkall", 1);
        //设置数据操作链接
        $this->setOp("编辑", U()."&form=edit&where='id=[id]'",
                array("pop" => $this->getPop("edit", "编辑[name]")));
        $this->setOp("删除", U()."&form=del&where='id=[id]'",
                array("query" => true, "ext" => 'confirm="确定删除吗？"'));
        $this->display();
    }

    public function goods()
    {
        $this->setNav("&nbsp;->&nbsp;商品管理");
        $this->mainPage("goods");

        $this->setTool("tool_btn_down", array("txt" => "新增商品", "icon" => "plus",
                                              "url" => U()."&form=add",
                                              "pop" => $this->getPop("goodsadd")));

        $this->setFind("typelist code", array("txt" => "拼音码", "val" => "code"));
        $this->setFind("typelist name", array("txt" => "名称", "val" => "name"));
        $this->setFind("typelist label", array("txt" => "标签", "val" => "label"));
        $this->setFind("typelist merchant", array("txt" => "商家", "val" => "merchant"));

        $this->setElement("name", "string", "名称", array("bool" => "required",
                "maxlength" => 30));

        $this->setElement("code", "string", "拼音码", array("bool" => "uniq required",
                "maxlength" => 30));

		$this->setElement("label", "string", "标签", array("bool" => "required", "placeholder" => "请输入标签 标签以逗号分隔"));
		$this->setElement("unit_price", "num", "单价", array("bool" => "required", "addon" => "元"));
		$this->setElement("unit", "select", "单位", array("bool" => "required",
            "list" => parse_select_list("array", array("斤", "公斤", "个", "只"), array("斤", "公斤", "个", "只"))));
		$this->setElement("merchant", "string", "商家", array("bool" => "required"));

		//$this->setElement("unit_price", "num", "商品单位", array("bool" => "required"));

        $this->setTitle(array("名称", "标签", "单价", "单位", "商家"));

        $this->setField(array("name", "label", "unit_price", "unit", "merchant"));
        $this->setData("close_chkall", 1);

        $this->setOp("编辑", U()."&form=edit&where='id=[id]'",
                array("pop" => $this->getPop("goodsedit", "编辑[name]")));
        $this->setOp("删除", U()."&form=del&where='id=[id]'",
                array("query" => true, "ext" => 'confirm="确定删除吗？"'));
        $this->display();
    }
}

?>
