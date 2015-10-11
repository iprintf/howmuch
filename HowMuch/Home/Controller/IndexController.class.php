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

    public function add_handle()
    {
        $data = array();
        $data["name"] = $_POST["name"];
        $data["total"] = $_POST["total"];
        $data["attender"] = ",".$_POST["attender"].",";
        $data["comment"] = $_POST["comment"];
        $data["create_time"] = date("Y-m-d H:i:s");

        $obj = M("transaction");
        $ret = $obj->add($data);
        if (!$ret)
            $this->ajaxReturn(array("info" => "添加失败!", "echo" => 1));

        $this->ajaxReturn(array("info" => "添加成功!", "echo" => 1, "url" => U("add"), "tag" => "#body"));
    }

    public function get_userlist($attender)
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

    public function add()
    {
        $form = new Form("", array("action" => U("add_handle"), "class" => "form-horizontal main_first_row"));
        $form->setElement("add_transaction_group", "group", "添加交易");
        $form->setElement("name", "string", "交易名称", array("bool" => "required"));
        $form->setElement("total", "num", "交易金额", array("bool" => "required", "addon" => "元"));
        $form->setElement("attender", "multiselect", "参与者", array("list" => parse_select_list("select id,name from user")));
        $form->setElement("comment", "textarea", "备注");
        //$form->setBtn("记账", "", array("ext" => 'onclick="location.href=\''.U("index").'\'"'));
         $form->setBtn("记账", U("Home/Index/index"),
                 array("bool" => "blink","ext" => 'type="button"'));
        $this->show($form->fetch());
    }

    public function transaction_del()
    {
        if (sqlCol("select id from transaction_detail where tid=".$_GET["id"]))
            $this->ajaxReturn(array("echo" => 1, "info" => "不能删除有明细的交易!"));
        M("transaction")->where("id=".$_GET["id"])->delete();;
        $this->ajaxReturn(array("echo" => 1, "info" => "删除成功!", "url" => U("index"), "tag" => "#body"));
    }

    public function add_detail()
    {
        if (IS_POST)
        {
            if (!$_POST["owner"])
                $this->ajaxReturn(array("echo" => 1, "info" => "所属者没有选择!"));
            $_POST["owner"] = ",".$_POST["owner"].",";

            $ret = M("transaction_detail")->add($_POST);

            if (!$ret)
                $this->ajaxReturn(array("echo" => 1, "info" => "添加失败!"));

            $this->ajaxReturn(array("echo" => 1, "info" => "添加成功!", "tag" => "#body", "url" => U("add_detail", "tid=".$_POST["tid"])));
        }

        $ts = sqlRow("select * from transaction where id=".$_GET["tid"]);

        $form = new Form("", array("action" => U(), "class" => "form-horizontal main_first_row"));
        $form->setElement("add_transaction_group", "group", "添加交易明细");
        $form->setElement("code", "autocomplete", "拼音码", array("bool" => "required",
            "list" => parse_autocomplete("select code,name,merchant,unit_price,unit,id from goods"),
            "ext" => 'count="2"'));
        $form->setElement("name", "string", "名称", array("bool" => "required"));
        $form->setElement("merchant", "string", "商家", array("bool" => "required"));
        $form->setElement("unit_price", "num", "单价", array("bool" => "required", "addon" => "元"));
        $form->setElement("unit", "string", "单位", array("bool" => "required"));
        $form->setElement("quantity", "num", "数量", array("bool" => "required"));
        $form->setElement("total", "num", "金额", array("bool" => "required", "addon" => "元"));
        $form->setElement("owner", "multiselect", "所属者", array(
            "list" => parse_select_list("select id,name from user where id in (".substr($ts["attender"], 1, strlen($ts["attender"]) - 2).")")));
        $form->setElement("gid", "hidden", "");
        $form->setElement("tid", "hidden", "", array("value" => $ts["id"]));
        $form->setBtn("返回", U("detail", "id=".$_GET["tid"]),
                 array("bool" => "blink","ext" => 'type="button"'));
        $form->set("js", "detail");

        $info = $this->transaction_info($ts);
        $info->set("close_btn_down", 1);

        $this->show($form->fetch().$info->fetch().$this->detail_list()->fetch());
    }

    public function detail_list()
    {
        $data = new SmallDataList("detail", "", 0, array("page" => array("size" => 10000)));
        $dl = sqlAll("select g.name as name, g.unit_price as price, t.quantity,
                        g.unit as unit, t.total, g.merchant as merchant, owner
                      from transaction_detail t, goods g where g.id=t.gid");
        $data->set("data_list", $dl);
        $data->setPage("total", count($dl));
        $data->setTitle(array("名称", "单价", "数量", "单位", "金额", "商家", "所属人"));
        $data->setField(array("name", "price", "quantity", "unit", "total", "merchant", "owner"));
        $data->set("data_field 6 run", "Index/get_userlist");

        return $data;
    }

    public function transaction_info($ts)
    {
        $userlist = $this->get_userlist($ts["attender"]);

        $form = new Form("", array("class" => "form-horizontal main_first_row"));
        $form->setElement("edit_group", "group", $ts["name"]);
        $form->setInfoElement("info_create_time", "创建时间", $ts["create_time"]);
        $form->setInfoElement("info_total", "总&nbsp;&nbsp;金&nbsp;&nbsp;额", $ts["total"]." 元");
        $form->setInfoElement("info_attender","参&nbsp;&nbsp;与&nbsp;&nbsp;者", $userlist);
        //$form->setInfoElement("remark", "备&emsp;&emsp;注", $row["comment"]);
        $form->setElement("info_remark", "static", "", array("close_label" => 1,
            "value" => $ts["comment"],
            "pclass" => "col-ss-10 col-xs-10 col-sm-10 col-md-10 col-ss-offset-1 col-xs-offset-2 col-sm-offset-2 col-md-offset-2 kyo_element_info"));

        return $form;
    }

    public function detail()
    {
        $ts = sqlRow("select * from transaction where id=".$_GET["id"]);
        $html = "";

        $form = $this->transaction_info($ts);
        $form->set("btn 0", array("txt" => "添加明细", "tag" => "#body",
            "url" => U("add_detail")."&tid=".$ts["id"], "ext" => 'type="button"'));
        $html .= $form->fetch();

        $html .= $this->detail_list()->fetch();

        $this->assign("main_body", $html);
        $this->display(false, "Home@Public/index");
    }

    public function index()
    {
        $html = '<div class="row main_first_row page-header">';
        $html .= '<h4 class="col-md-3 col-sm-2 col-xs-5">记账</h4>';
        $html .= '<span class="col-md-9 col-sm-10 col-xs-7 text-right">';
        $html .= '<button type="button" class="btn btn-primary" url="'.U("add").'" tag="#body">记一笔</button>';
        $html .= '</span></div>';

        $data = sqlAll("select id,name,total,create_time,comment,attender from transaction where state=1 order by create_time desc");

        foreach($data as $row)
        {
            $form = $this->transaction_info($row);
            $form->set("btn 0 txt", "明细");
            $form->set("btn 0 bool", "blink");
            $form->set("btn 0 url", U("detail")."&id=".$row["id"]);
            $form->set("btn 0 ext", 'type="button"');
            $form->setBtn("删除", U("transaction_del")."&id=".$row["id"],
                    array("ext" => 'type="button" confirm="确定删除?"'));
            $html .= $form->fetch();
        }

        $this->assign("main_body", $html);
        $this->display(false, "Home@Public/index");
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
