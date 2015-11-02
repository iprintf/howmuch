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
        	case "info":
                if (!$title)
                    $title = "详细信息";
                $pop = "w:650,h:580,n:'transinfo',t:".$title;
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

    static public function parse_find_date($name, & $sdate, & $edate)
    {
        if (!$sdate)
            $sdate = date("Y-m-d");
        if (!$edate)
            $edate = date("Y-m-d", strtotime("+1 day", strtotime($sdate)));

        if (IS_POST && I("get.find"))
        {
            if ($_POST[$name])
            {
                $find_date = explode(",", $_POST[$name]);
                $_POST[$name] = "";
                $sdate = $find_date[0];
                $edate = $find_date[1];
            }
        }
        else
        {
            if (I("get.sdate"))
                $sdate = I("get.sdate");

            if (I("get.edate"))
                $edate = I("get.edate");
        }
    }

    static public function show_attender($data)
    {
        $user = SqlAll("select u.name,b.amount from balance b, user u where b.uid=u.id and tid=".$data["id"]);

        $u = "";
        foreach ($user as $r)
        {
            $u .= $r["name"].'(<span style="color:red">'.$r["amount"].'</span>), ';
        }
        $u = rtrim($u, ", ");

        return $u;
    }

    public function info()
    {
        $ts = SqlRow("select * from transaction where id=".$_GET["id"]);

        $form = new Form("", array("cols" => 2, "class" => "form-horizontal kyo_form"));
        $form->setElement("info_create_time", "static", "创建时间", array("value" => $ts["create_time"]));
        $form->setElement("info_finish_time", "static", "结账时间", array("value" => $ts["finish_time"]));
        $form->setElement("info_total", "static", "总&nbsp;&nbsp;金&nbsp;&nbsp;额", array("value" => $ts["total"]." 元"));
        $ulist = SqlAll("select u.name,b.amount from balance b, user u where b.uid=u.id and tid=".$ts["id"]);
        $u = "";
        foreach ($ulist as $r)
        {
            $u .= '<span style="display:inline-block;width:80px;">'.$r["name"].'</span> <span style="color:red"> '.$r["amount"].' </span><br />';
        }
        $u = rtrim($u, ", ");
        $form->setElement("info_attender", "static", "参&nbsp;&nbsp;与&nbsp;&nbsp;者", array("value" => $u));
        $form->setElement("info_remark", "static", "备注", array("close_label" => 1,
            "value" => $ts["comment"],
            "pclass" => "col-ss-10 col-xs-10 col-sm-10 col-md-10 col-ss-offset-1 col-xs-offset-2 col-sm-offset-2 col-md-offset-2 kyo_element_info"));
        $form->set("close_btn_down", 1);

        echo $form->fetch();
        echo "<br />";
        echo DetailController::detail_list($ts["id"], true)->fetch();
        echo "<br />";
        echo DetailController::repay_list($ts["id"], true)->fetch();
    }

    public function end_transaction()
    {
        $this->parse_find_date("date", $sdate, $edate);

        if (I("get.find") && IS_POST)
        {
            if (I("post.attender"))
            {
                $_POST["search_type"] = "attender";
                $_POST["search_key"] = ",".$_POST["attender"].",";
                $_POST["attender"] = "";
            }
        }

        $this->setNav("&nbsp;->&nbsp;旧账查询");
        $this->mainPage("transaction");

        $this->setFind("item finish_time", array("name" => "finish_time", "type" => "date",
                        "sval" => $sdate, "eval" => $edate));
        $this->setFind("item 1", array("name" => "search_type", "type" => "select",
                "default" => "交易名称", "defval" => "name",
                "list" => parse_select_list("array", array("attender"), array("参与者"))));

        $list = parse_select_list("select id,name from user");
        $this->setFind("item attender", array("name" => "attender", "type" => "select",
                "class" => "hidden", "me" => "me",
                "list" => $list, "default" => "请选择参与者"));

        //设置列表标题
        $this->setTitle(array("交易名称", "交易金额", "参与者", "结账时间"));
        //设置列表字段名
        $this->setField(array("name", "total", "attender", "finish_time"));
        $this->setData("data_field 2 run", "Index/show_attender");
        $this->setData("where", "state=2");
        $this->setField("name", array("name" => "0", "url" => U("Index/info")."&id=[id]",
                "pop" => $this->getPop("info", "[name] 详细信息")));
        $this->setData("subtotal field", array(1));
        $this->setData("close_chkall", 1);
        $this->setData("close_op", 1);

        $this->display();
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
