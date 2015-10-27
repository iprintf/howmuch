<?php
namespace Home\Controller;
use Think\Controller;
use Common\Controller\ListPage;
use Common\Controller\Form;
use Common\Controller\SmallDataList;

class DetailController extends ListPage
{
    public function __construct()
    {
        parent::__construct();
    }

    public function post()
    {
        if (!$_POST["owner"])
            $this->ajaxReturn(array("echo" => 1, "info" => "所属者没有选择!"));

        // $this->ajaxReturn(array("echo" => 1, "info" => "商品ID : ".$_POST["gid"]));

        $goods = sqlRow("select * from goods where id=".$_POST["gid"]);

        if (!$goods)
            $this->ajaxReturn(array("echo" => 1, "info" => "商品选择有误!"));

        //如果拼音码 名称 商家没有改变 但是单价或单位改变则更新商品信息
        if ($goods["code"] == $_POST["code"] && $goods["name"] == $_POST["name"]
            && $goods["merchant"] == $_POST["merchant"]
            && ($goods["unit"] != $_POST["unit"]
            || $goods["unit_price"] != $_POST["unit_price"]
            || $goods["label"] != $_POST["label"]))
        {
            $ret = M("goods")->where("id=".$goods["id"])
                ->setField(
                    array("unit_price", $_POST["unit_price"]),
                    array("unit", $_POST["unit"]),
                    array("label", $_POST["label"]));
            if (!$ret)
                $this->ajaxReturn(array("echo" => 1, "info" => "更新商品失败!"));
        }
        //如果拼音码 名称  商家其中一个改变则添加新商品
        else if ($goods["code"] != $_POST["code"] || $goods["name"] != $_POST["name"]
                    || $goods["merchant"] != $_POST["merchant"])
        {
            $data = array();
            $data["code"] = $_POST["code"];
            $data["name"] = $_POST["name"];
            $data["merchant"] = $_POST["merchant"];
            $data["unit"] = $_POST["unit"];
            $data["unit_price"] = $_POST["unit_price"];
            $data["label"] = $_POST["label"];

            $ret = M("goods")->add($data);
            if (!$ret)
                $this->ajaxReturn(array("echo" => 1, "info" => "添加商品失败!"));

            $_POST["gid"] = $ret;
        }

        $data = array();
        $data["tid"] = $_POST["tid"];
        $data["gid"] = $_POST["gid"];
        $data["total"] = $_POST["total"];
        $data["quantity"] = $_POST["quantity"];
        $data["unit_price"] = $_POST["unit_price"];
        $data["owner"] = ",".$_POST["owner"].",";

        $ret = M("transaction_detail")->add($data);

        if (!$ret)
            $this->ajaxReturn(array("echo" => 1, "info" => "添加失败!"));

        $this->ajaxReturn(array("echo" => 1, "info" => "添加成功!", "tag" => "#body", "url" => U("add_detail", "tid=".$_POST["tid"])));

    }

    public function add_detail()
    {
        if (IS_POST)
            $this->post();

        if (isset($_GET["id"]))
        {
            $detail = sqlRow("select t.id, t.tid, g.name as name, t.unit_price as price,
                    t.quantity, g.unit as unit, t.total, g.merchant as merchant, owner
                    ,code,label
                  from transaction_detail t, goods g where g.id=t.gid and t.id=".$_GET["id"]);
        }
        $ts = sqlRow("select * from transaction where id=".$_GET["tid"]);

        $form = new Form("", array("action" => U(), "class" => "form-horizontal main_first_row"));
        $form->setElement("add_transaction_group", "group", "添加交易明细");
        $form->setElement("code", "autocomplete", "拼音码", array("bool" => "required",
            "input_val" => $detail["code"],
            "list" => parse_autocomplete("select code,name,merchant,unit_price,unit,label,id from goods"),
            "ext" => 'count="2"'));
        $form->setElement("name", "string", "名称", array("bool" => "required", "value" => $detail["name"]));
        $form->setElement("merchant", "string", "商家", array("bool" => "required", "value" => $detail["merchant"]));
        $form->setElement("label", "string", "标签", array("bool" => "required", "value" => $detail["label"]));
        $form->setElement("unit_price", "num", "单价", array("bool" => "required", "addon" => "元", "value" => $detail["price"]));
        $form->setElement("unit", "string", "单位", array("bool" => "required", "value" => $detail["unit"]));
        $form->setElement("quantity", "num", "数量", array("bool" => "required", "value" => $detail["quantity"]));
        $form->setElement("total", "num", "金额", array("bool" => "required", "addon" => "元", "value" => $detail["total"]));
        $form->setElement("owner", "multiselect", "所属者", array(
            "list" => parse_select_list("select id,name from user where id in (".substr($ts["attender"], 1, strlen($ts["attender"]) - 2).")")));
        $form->setElement("gid", "hidden", "", array("value" => $detail["gid"]));
        $form->setElement("tid", "hidden", "", array("value" => $ts["id"]));
        $form->setElement("id", "hidden", "", array("value" => $detail["id"]));
        $form->setBtn("返回", U("index", "id=".$_GET["tid"]),
                 array("bool" => "blink","ext" => 'type="button"'));
        $form->set("js", "detail");

        $info = TransactionController::info($ts);
        $info->set("close_btn_down", 1);

        $this->show($form->fetch().$info->fetch().$this->detail_list($detail["tid"])->fetch());
    }

    public function detail_list($tid)
    {
        $data = new SmallDataList("detail", "", 0, array("page" => array("size" => 10000)));
        $dl = sqlAll("select t.id, t.tid, g.name as name, t.unit_price as price, t.quantity,
                        g.unit as unit, t.total, g.merchant as merchant, owner
                        from transaction_detail t, goods g where g.id=t.gid
                        and t.tid=".$tid);
        $data->set("data_list", $dl);
        $data->set("close_op", 0);
        $data->setPage("total", count($dl));
        $data->setTitle(array("名称", "单价", "数量", "单位", "金额", "商家", "所属人"));
        $data->setField(array("name", "price", "quantity", "unit", "total", "merchant", "owner"));
        $data->set("data_field 6 run", "Index/get_userlist");
        $data->setOp("编辑", U("add_detail")."&tid=[tid]&id=[id]", array("tag" => "#body"));
        $data->setOp("删除", U("del")."&id=[id]");

        return $data;
    }

    public function repay_list($tid)
    {
        $data = new SmallDataList("detail", "", 0, array("page" => array("size" => 10000)));
        $dl = sqlAll("select t.id, t.tid, g.name as name, t.unit_price as price, t.quantity,
                        g.unit as unit, t.total, g.merchant as merchant, owner
                        from transaction_detail t, goods g where g.id=t.gid
                        and t.tid=".$tid);
        $data->set("data_list", $dl);
        $data->set("close_op", 0);
        $data->setPage("total", count($dl));
        $data->setTitle(array("付款人", "付款金额"));
        $data->setField(array("name", "price"));
        $data->setOp("编辑", U("add_detail")."&tid=[tid]&id=[id]", array("tag" => "#body"));
        $data->setOp("删除", U("del")."&id=[id]");

        return $data;
    }

    public function index()
    {
        $ts = sqlRow("select * from transaction where id=".$_GET["id"]);
        $html = "";

        $form = TransactionController::info($ts);

        $btn = '<button class="btn btn-primary">编辑</button>&emsp;';
        $btn .= '<button class="btn btn-primary">删除</button>&emsp;';
        $btn .= '<button class="btn btn-primary">返回</button>';

        $form->setElement("detail_op_custom", "custom", "", array(
                "close_label" => 1, "element_cols" => 12,
                "pclass" => "text-center",
                "custom_html" => $btn));

        $detail_btn_html = '<a href="'.U("Transaction/index").'"><span class="glyphicon glyphicon-plus pull-right" style="margin-right:30px;" title="添加明细信息">&nbsp;添加明细</span></a>';
        $form->setElement("detail_group", "group", "明细列表".$detail_btn_html);
        $form->setElement("custom_detail", "custom", "", array("close_label" => 1,
            "element_cols" => "12",
            "custom_html" => $this->detail_list($_GET["id"])->fetch()
        ));

        $repay_btn_html = '<a href="'.U("Transaction/index").'"><span class="glyphicon glyphicon-plus pull-right" style="margin-right:30px;" title="添加付款信息">&nbsp;添加付款</span></a>';
        $form->setElement("repay_group", "group", "付款列表".$repay_btn_html);
        $form->setElement("custom_repay", "custom", "", array("close_label" => 1,
            "element_cols" => "12",
            "custom_html" => $this->repay_list($_GET["id"])->fetch()
        ));

        $form->set("btn 0 txt", "结账");
        $form->set("btn 0 url", U("add_detail")."&tid=".$ts["id"]);
        $form->set("btn 0 tag", "#body");
        $form->set("btn 0 ext", 'type="button"');
        // $form->setBtn("结账", U("Transaction/index"), array("bool" => 'blink'));
        // $form->setBtn("返回列表", U("Transaction/index"), array("bool" => 'blink'));

        $html .= $form->fetch();

        $this->assign("main_body", $html);
        $this->display(false, "Home@Public/index");
    }

}

?>
