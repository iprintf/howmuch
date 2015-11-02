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

    static public function getPop($type, $title = "")
    {
        $pop = "";
        switch ($type)
        {
        	case "addpay":
                if (!$title)
                    $title = "新增付款信息";
                $pop = "w:450,h:300,n:'payadd',t:".$title;
        	    break;
        	case "editpay":
                if (!$title)
                    $title = "编辑付款信息";
                $pop = "w:450,h:300,n:'paydit',t:".$title;
        	    break;
        	default:
        	    break;
        }
        return $pop;
    }


    public function post()
    {
        if (!$_POST["owner"])
            $this->ajaxReturn(array("echo" => 1, "info" => "所属者没有选择!"));

        // $this->ajaxReturn(array("echo" => 1, "info" => "商品ID : ".$_POST["gid"]));

        $goods = sqlRow("select * from goods where id=".$_POST["gid"]);

        // if (!$goods)
            // $this->ajaxReturn(array("echo" => 1, "info" => "商品选择有误!"));

        //如果拼音码 名称 商家没有改变 但是单价或单位改变则更新商品信息
        if ($goods["code"] == $_POST["code"] && $goods["name"] == $_POST["name"]
            && $goods["merchant"] == $_POST["merchant"]
            && ($goods["unit"] != $_POST["unit"]
            || $goods["unit_price"] != $_POST["unit_price"]
            || $goods["label"] != $_POST["label"]))
        {
            $data = array();
            $data["unit_price"] = $_POST["unit_price"];
            $data["unit"] = $_POST["unit"];
            $data["label"] = $_POST["label"];
            $ret = M("goods")->where("id=".$goods["id"])->save($data);
            if (!$ret)
                $this->ajaxReturn(array("echo" => 1, "info" => "更新商品失败!"));
        }
        //如果拼音码 名称  商家其中一个改变则添加新商品
        else if (!$goods || $goods["code"] != $_POST["code"] || $goods["name"] != $_POST["name"]
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

        if ($_POST["id"])
        {
            $ret = M("transaction_detail")->where("id=".$_POST["id"])->save($data);
            if (!$ret)
                $this->ajaxReturn(array("echo" => 1, "info" => "操作失败!"));

            if ($_POST["old_total"] != $_POST["total"])
                M("transaction")->where("id=".$_POST["tid"])->setInc("total", ($_POST["total"] - $_POST["old_total"]));
            SqlAll(" call update_balance(".$_POST["tid"]."); ");
        }
        else
        {
            $ret = M("transaction_detail")->add($data);
            if (!$ret)
                $this->ajaxReturn(array("echo" => 1, "info" => "操作失败!"));
            M("transaction")->where("id=".$_POST["tid"])->setInc("total", $_POST["total"]);
            SqlAll(" call update_balance(".$_POST["tid"].");");
        }

        $this->ajaxReturn(array("echo" => 1, "info" => "操作成功!", "url" => U("Detail/index", "id=".$_POST["tid"])));
    }

    public function add_detail()
    {
        if (IS_POST)
            $this->post();

        if (isset($_GET["id"]))
        {
            $detail = sqlRow("select t.id, t.tid, g.name as name, t.unit_price as price,
                    t.quantity, g.unit as unit, t.total, g.merchant as merchant, owner
                    ,code,label,gid
                  from transaction_detail t, goods g where g.id=t.gid and t.id=".$_GET["id"]);
        }
        else
        {
            $detail["tid"] = $_GET["tid"];
        }
        $ts = sqlRow("select * from transaction where id=".$_GET["tid"]);

        $form = new Form("", array("action" => U(), "class" => "form-horizontal main_first_row"));
        $form->setElement("add_transaction_group", "group", "添加交易明细");
        $form->setElement("code", "autocomplete", "拼音码", array("bool" => "required",
            "input_val" => $detail["code"],
            "value" => $detail["code"],
            "list" => parse_autocomplete("select code,name,merchant,unit_price,unit,label,id,name as bname from goods"),
            "ext" => 'count="2"'));
        $form->setElement("name", "string", "名称", array("bool" => "required", "value" => $detail["name"]));
        $form->setElement("merchant", "string", "商家", array("bool" => "required", "value" => $detail["merchant"]));
        $form->setElement("label", "string", "标签", array("bool" => "required", "value" => $detail["label"]));
        $form->setElement("unit_price", "num", "单价", array("bool" => "required", "addon" => "元", "value" => $detail["price"]));
        $form->setElement("unit", "string", "单位", array("bool" => "required", "value" => $detail["unit"]));
        $form->setElement("quantity", "num", "数量", array("bool" => "required", "value" => $detail["quantity"]));
        $form->setElement("total", "num", "金额", array("bool" => "required", "addon" => "元", "value" => $detail["total"]));
        $form->setElement("owner", "multiselect", "所属者", array(
            "value" => $detail["owner"],
            "list" => parse_select_list("select id,name from user where id in (".substr($ts["attender"], 1, strlen($ts["attender"]) - 2).")")));
        $form->setElement("gid", "hidden", "", array("value" => $detail["gid"]));
        $form->setElement("tid", "hidden", "", array("value" => $ts["id"]));
        $form->setElement("id", "hidden", "", array("value" => $detail["id"]));
        $form->setElement("old_total", "hidden", "", array("value" => $detail["total"]));
        $form->setBtn("返回", U("index", "id=".$_GET["tid"]),
                 array("bool" => "blink","ext" => 'type="button"'));
        $form->set("js", "detail");

        if (isset($_GET["id"]))
            $form->set("btn 0 txt", "保存");

        $info = TransactionController::info($ts);
        $info->set("close_btn_down", 1);

        // dump($form->fetch());

        $this->show($form->fetch());
    }

    public function del()
    {
        $total = SqlCol("select total from transaction_detail where id=".$_GET["id"]);
        M("transaction")->where("id=".$_GET["tid"])->setDec("total", $total);
        $ret =M("transaction_detail")->where("id=".$_GET["id"])->delete();
        if (!$ret)
            $this->ajaxReturn(array("echo" => 1, "info" => "删除失败!"));

        SqlAll("call update_balance(".$_GET["tid"].");");

        $this->ajaxReturn(array("echo" => 1, "info" => "删除成功!", "url" => U("index", "id=".$_GET["tid"])));
    }

    static public function detail_list($tid, $info = false)
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
        if ($info)
        {
            $data->set("close_op", 1);
            $data->set("close_down_page", 1);
            return $data;
        }
        $data->setOp("编辑", U("add_detail")."&tid=[tid]&id=[id]", array("tag" => "#body"));
        $data->setOp("删除", U("del")."&id=[id]&tid=[tid]", array("query" => true, "ext" => 'confirm="确定删除吗？"'));
        // dump($data->fetch());

        return $data;
    }

    static public function repay_list($tid, $info = false)
    {
        $data = new SmallDataList("detail", "", 0, array("page" => array("size" => 10000)));
        $dl = sqlAll("select id,tid,uid,amount from payment where tid=".$tid);
        $data->set("data_list", $dl);
        $data->set("close_op", 0);
        $data->setPage("total", count($dl));
        $data->setTitle(array("付款人", "付款金额"));
        $data->setField(array("uid", "amount"));
        $data->set("data_field 0 run", "Detail/getpayname");
        if ($info)
        {
            $data->set("close_op", 1);
            $data->set("close_down_page", 1);
            return $data;
        }
        $data->setOp("编辑", U("addPay")."&tid=[tid]&id=[id]", array("pop" => $this->getPop("editpay")));
        $data->setOp("删除", U("delPay")."&id=[id]&tid=[tid]", array("query" => true, "ext" => 'confirm="确定删除吗？"'));

        return $data;
    }

    static public function getpayname($uid)
    {
        if (is_array($uid))
            $uid = $uid["uid"];
        return sqlCol("select name from user where id=".$uid);
    }

    public function delPay()
    {
        $ret =M("payment")->where("id=".$_GET["id"])->delete();
        if (!$ret)
            $this->ajaxReturn(array("echo" => 1, "info" => "删除失败!"));

        SqlAll("call update_balance(".$_GET["tid"].");");

        $this->ajaxReturn(array("echo" => 1, "info" => "删除成功!", "url" => U("index", "id=".$_GET["tid"])));
    }

    public function addPay()
    {
        if (IS_POST)
        {
            $data = array();
            $data["tid"] = $_POST["tid"];
            $data["uid"] = $_POST["uid"];
            $data["amount"] = $_POST["amount"];

            if ($_POST["id"])
            {
                $ret = M("payment")->where("id=".$_POST["id"])->save($data);
            }
            else
                $ret = M("payment")->add($data);

            if (!$ret)
                $this->ajaxReturn(array("echo" => 1, "info" => "操作失败!"));

            SqlAll("call update_balance(".$_POST["tid"].");");

            $this->ajaxReturn(array("echo" => 1, "info" => "操作成功!", "close" => 1, "url" => U("index", "id=".$_POST["tid"])));
        }

        $ts = SqlRow("select id,attender from transaction where id=".$_GET["tid"]);

        $user = sqlAll("select name,id from user where id in (".
                substr($ts["attender"], 1, strlen($ts["attender"]) - 2).")");

        $userlist = "";
        foreach ($user as $u)
        {
            $userlist .= $u["name"].",".$u["id"]."|";
        }
        $userlist = rtrim($userlist, "|");
        // dump($userlist);

        if ($_GET["id"])
            $rs = SqlRow("select id,amount,uid from payment where id=".$_GET["id"]);

        $form = new Form("", array("action" => U(), "class" => "form-horizontal kyo_form"));
        $form->setElement("uid", "autocomplete", "付款人", array("bool" => "required",
            "input_val" => self::getpayname($rs["uid"]),
            "value" => $rs["uid"],
            "list" => $userlist));
        $form->setElement("amount", "num", "付款金额", array("bool" => "required", "addon" => "元", "value" => $rs["amount"]));
        $form->setElement("tid", "hidden", "", array("value" => $ts["id"]));
        $form->setElement("id", "hidden", "", array("value" => $rs["id"]));

        $form->set("btn 0 txt", "保存");
        // dump($form->fetch());

        echo $form->fetch();
    }

    public function endPay()
    {
        $data = array();
        $data["state"] = 2;
        $data["finish_time"] = date("Y-m-d H:i:s");
        M("transaction")->where("id=".$_GET["id"])->save($data);
        $this->redirect("Transaction/index");
    }

    public function index()
    {
        $ts = sqlRow("select * from transaction where id=".$_GET["id"]);
        $html = "";

        $form = TransactionController::info($ts);

        $btn = '<button class="btn btn-primary" url="'.U("Transaction/add", "id=".$ts["id"]."&did=1").'" tag="#body">编辑</button>&emsp;';
        $btn .= '<button class="btn btn-primary" url="'.U("Transaction/del", "id=".$ts["id"]).'" confirm="确定删除吗？">删除</button>&emsp;';
        $btn .= '<button class="btn btn-primary" type="button" url="'.U("Transaction/index").'" blink="blink">返回</button>';

        $form->setElement("detail_op_custom", "custom", "", array(
                "close_label" => 1, "element_cols" => 12,
                "pclass" => "text-center",
                "custom_html" => $btn));

        $detail_btn_html = '<a href="#" url="'.U("add_detail", "tid=".$ts["id"]).'" tag="#body"><span class="glyphicon glyphicon-plus pull-right" style="margin-right:30px;" title="添加明细信息">&nbsp;添加明细</span></a>';
        $form->setElement("detail_group", "group", "明细列表".$detail_btn_html);
        $form->setElement("custom_detail", "custom", "", array("close_label" => 1,
            "element_cols" => "12",
            "custom_html" => $this->detail_list($_GET["id"])->fetch()
        ));

        $repay_btn_html = '<a href="#" url="'.U("addPay", "tid=".$ts["id"]).'" pop="{'.$this->getPop("addpay").'}"><span class="glyphicon glyphicon-plus pull-right" style="margin-right:30px;" title="添加付款信息">&nbsp;添加付款</span></a>';

        $form->setElement("repay_group", "group", "付款列表".$repay_btn_html);
        $form->setElement("custom_repay", "custom", "", array("close_label" => 1,
            "element_cols" => "12",
            "custom_html" => $this->repay_list($_GET["id"])->fetch()
        ));

        $form->set("btn 0 txt", "结账");
        $form->set("btn 0 url", U("endPay")."&id=".$ts["id"]);
        $form->set("btn 0 bool", "blink");
        $form->set("btn 0 ext", 'type="button" confirm="结账后不能再对交易进行操作，确定结账吗？"');

        $html .= $form->fetch();

        $this->assign("main_body", $html);
        $this->display(false, "Home@Public/index");
    }
}

?>
