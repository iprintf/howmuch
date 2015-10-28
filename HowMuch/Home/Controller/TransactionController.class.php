<?php
namespace Home\Controller;
use Think\Controller;
use Common\Controller\ListPage;
use Common\Controller\Form;
use Common\Controller\FormElement;

class TransactionController extends ListPage
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $html = '<div class="row main_first_row page-header">';
        $html .= '<h4 class="col-md-3 col-sm-2 col-xs-5">记账</h4>';
        $html .= '<span class="col-md-9 col-sm-10 col-xs-7 text-right">';
        $html .= '<button type="button" class="btn btn-primary" url="'.U("add").'" tag="#body"><span class="glyphicon glyphicon-plus"></span>&nbsp;记一笔</button>';
        $html .= '</span></div>';

        $data = sqlAll("select id,name,total,create_time,comment,attender from transaction where state=1 order by create_time desc");

        foreach($data as $row)
        {
            $form = $this->info($row);
            $form->set("btn 0 txt", "明细");
            $form->set("btn 0 bool", "blink");
            $form->set("btn 0 url", U("Detail/index")."&id=".$row["id"]);
            $form->set("btn 0 ext", 'type="button"');
            $form->setBtn("编辑", U("add")."&id=".$row["id"], array("tag" => "#body"));
            $form->setBtn("删除", U("del")."&id=".$row["id"],
                    array("ext" => 'type="button" confirm="确定删除?"'));
            $html .= $form->fetch();
        }

        $this->assign("main_body", $html);
        $this->display(false, "Home@Public/index");
    }

    public function post()
    {
        $data = array();
        $data["name"] = $_POST["name"];
        $data["total"] = $_POST["total"];
        $data["attender"] = ",".$_POST["attender"].",";
        $data["comment"] = $_POST["comment"];

        $obj = M("transaction");
        if ($_POST["id"])
        {
            $ret = $obj->where("id=".$_POST["id"])->save($data);
            if (!$ret)
                $this->ajaxReturn(array("info" => "编辑失败!", "echo" => 1));

            $this->ajaxReturn(array("info" => "编辑成功!", "echo" => 1, "url" => U("index")));
        }
        else
        {
            $data["create_time"] = date("Y-m-d H:i:s");
            $ret = $obj->add($data);
            if (!$ret)
                $this->ajaxReturn(array("info" => "添加失败!", "echo" => 1));

            $this->ajaxReturn(array("info" => "添加成功!", "echo" => 1, "url" => U("add"), "tag" => "#body"));
        }
    }

    public function add()
    {
        if ($_GET["id"])
        {
            $transaction = sqlRow("select * from transaction where id=".$_GET["id"]);
            $title = "编辑交易";
        }
        else
        {
            $transaction["total"] = 0;
            $title = "添加交易";
        }

        $form = new Form("", array("action" => U("post"), "class" => "form-horizontal main_first_row"));
        $form->setElement("add_transaction_group", "group", $title);
        $form->setElement("name", "string", "交易名称", array("bool" => "required", "value" => $transaction["name"]));
        $form->setElement("total", "num", "交易金额", array("bool" => "readonly required", "addon" => "元", "value" => $transaction["total"]));
        $form->setElement("attender", "multiselect", "参与者", array("list" => parse_select_list("select id,name from user"), "value" => $transaction["attender"]));
        $form->setElement("comment", "textarea", "备注", array("value" => $transaction["comment"]));
        $form->setElement("id", "hidden", "", array("value" => $transaction["id"]));
        //$form->setBtn("记账", "", array("ext" => 'onclick="location.href=\''.U("index").'\'"'));
        if ($transaction)
            $form->set("btn 0 txt", "编辑");
        if ($_GET["did"])
            $url = U("Detail/index", "id=".$transaction["id"]);
        else
            $url = U("index");

        $form->setBtn("返回", $url,
                 array("bool" => "blink","ext" => 'type="button"'));
        $this->show($form->fetch());
    }

    public function del()
    {
        if (sqlCol("select id from transaction_detail where tid=".$_GET["id"]))
            $this->ajaxReturn(array("echo" => 1, "info" => "不能删除有明细的交易!"));
        M("transaction")->where("id=".$_GET["id"])->delete();;
        $this->ajaxReturn(array("echo" => 1, "info" => "删除成功!", "url" => U("index"), "tag" => "#body"));
    }

    static public function info($ts)
    {
        $userlist = IndexController::get_userlist($ts["attender"]);

        $form = new Form("", array("class" => "form-horizontal main_first_row"));
        if (!strstr(U(), "Transaction"))
            $return_btn_html = '<a href="'.U("Transaction/index").'"><span class="glyphicon glyphicon-home pull-right" style="margin-right:30px;" title="返回交易列表"></span></a>';

        $form->setElement("edit_group", "group", $ts["name"].$return_btn_html);
        $form->setInfoElement("info_create_time", "创建时间", $ts["create_time"]);
        $form->setInfoElement("info_total", "总&nbsp;&nbsp;金&nbsp;&nbsp;额", $ts["total"]." 元");
        $form->setInfoElement("info_attender","参&nbsp;&nbsp;与&nbsp;&nbsp;者", $userlist);
        //$form->setInfoElement("remark", "备&emsp;&emsp;注", $row["comment"]);
        $form->setElement("info_remark", "static", "", array("close_label" => 1,
            "value" => $ts["comment"],
            "pclass" => "col-ss-10 col-xs-10 col-sm-10 col-md-10 col-ss-offset-1 col-xs-offset-2 col-sm-offset-2 col-md-offset-2 kyo_element_info"));

        return $form;
    }
}

?>
