<?php
namespace Pos\Controller;
use Common\Controller\ListPage;
use Common\Controller\FormElement;
use Common\Controller\Form;

class PosController extends ListPage
{
    private $admin;
    private $sid;
    private $uid;
    private $sel_info = array("info_code", "info_status",
            "shop_id",
            "pos_name",
            "models",
            "abbr_name",
            "cost",
            "sub_code",
            "bank_group", "bank", "card_name", "bank_addr", "card");

    public function __construct()
    {
        parent::__construct();
        $this->admin = get_user_info("admin");
        if ($this->admin == 2 || $this->admin == 3)
        {
            header("Location:".ERROR_URL);
            exit(0);
        }
        $this->sid = get_user_info("sid");
        $this->uid = get_user_info("uid");
    }


    static public function getPop($type, $title = "")
    {
        $pop = "";
        switch ($type)
        {
        	case "add":
                if (!$title)
                    $title = "新增POS机";
                $pop = "w:950,h:800,n:'posadd',t:".$title;
        	    break;
        	case "edit":
                if (!$title)
                    $title = "编辑POS机";
                $pop = "w:950,h:830,n:'posedit',t:".$title;
        	    break;
        	case "info":
                if (!$title)
                    $title = "POS机信息";
                $pop = "w:950,h:800,n:'posinfo',t:".$title;
        	    break;
        	case "subinfo":
                if (!$title)
                    $title = "POS机信息";
                $pop = "w:950,h:570,n:'posinfo',t:".$title;
        	    break;
        	case "err":
                if (!$title)
                    $title = "POS机故障解决";
                $pop = "w:480,h:360,c:1,n:'poserr',t:".$title;
        	default:
        	    break;
        }
        return $pop;
    }

    static public function commonHead($obj, $title = "POS&nbsp;->&nbsp;POS机管理")
    {
        if (I("get.find") && IS_POST)
        {
            if (I("post.status") == -1)
                $_POST["status"] = 0;
        }
        $obj->setNav("&nbsp;->&nbsp;".$title);
        $obj->mainPage("pos");

        $obj->setFind("item pay", array("name" => "pay", "type" => "select",
                "list" => parse_select_list("select name from pay"), "default" => "所有支付公司"));

        $obj->setFind("item sid", array("name" => "sid", "type" => "select", "default" => "所有分站",
                "list" => parse_select_list("select id,proxy_sub_name from users where type=6")));

        $obj->setFind("item status", array("name" => "status", "type" => "select",
                "default" => "所有状态", "defval" => 0, "me" => "me",
                "list" => parse_select_list("array", array(-1, ERROR, LOCK),
                        array("正常状", "故障状", "锁定状"))));

        $list = parse_select_list("array", array("sub_code", "models", "cost", "code"),
                    array("POS简码", "POS终端号", "POS费率", "内部代码"));

        $obj->setFind("item search_type", array("name" => "search_type", "type" => "select",
                "list" => $list, "default" => "POS商户名", "defval" => "pos_name"));

        $list = parse_select_list("select DISTINCT cost, ROUND(cost*100,2) as cost_dis from pos
                                    where sid=".get_user_info("sid"), "[cost]", "[cost_dis]%");
        $obj->setFind("item cost", array("name" => "cost", "type" => "select",
                "class" => "hidden", "me" => "me",
                "list" => $list, "default" => "请选择费率"));
    }

    public function index()
    {
        switch ($this->admin)
        {
        	case 1:
        	case 6:
                $this->subPosList();
        	    break;
        	case 9:
        	case 7:
        	case 0:
                $this->commonManage();
        	    break;
        }
        $this->display();
    }

    //Pos列表字段格式化函数
    public function getPosStatus($data, $txt = "")
    {
        if ($data["status"] != ERROR)
            return get_status_txt($data["status"]);

        $obj = new FormElement("pos_lnk", "link", "故障状",
                array("url" => U("Pos/Pos/posErrInfo")."&code=".$data["code"],
                      "pop" => $this->getPop("err", "POS机故障原因"), "class" => "kyo_red"));

        return $obj->fetch();
    }


    //POS操作列表
    public function posOp($data, $oplist = array())
    {
        $ophtml = "";
        $index = 1;
        foreach ($oplist as $op)
        {
            parse_link($op["url"], $data);
            parse_link($op["pop"], $data);
            parse_link($op["link"], $data);
            $opObj = new FormElement($index++, "link", $op["txt"], $op);
            $ophtml .= $opObj->fetch()."&emsp;";
        }

        $txt = "关闭";
        $status = LOCK;

        if ($data["status"] == LOCK)
        {
            $txt = '开启';
            $color = 'kyo_red';
            $status = NORMAL;
        }

        $lock = new FormElement("btn_lock", "link", '<span class="'.$color.'">'.$txt.'</span>', array(
                "ext" => 'confirm="确定'.$txt.'吗？"
                    url ="'.U("Pos/posLock")."&id=".$data["id"]."&status=".$status.'"',
        ));

        return $ophtml.$lock->fetch();
    }

    //pos关闭开启操作
    public function posLock($id, $status = LOCK)
    {
        $return = array("echo" => 1,"info" => "关闭成功!","url" => session("prev_urlpos"),"tag" => ".pos");
        M("pos")->where("id=".$id)->setField("status", $status);

        if ($status != LOCK)
            $return["info"] = "开启成功!";

        $this->ajaxReturn($return);
    }

    //pos批量关闭操作
    public function posLockBatch()
    {
        $con = ltrim(I("get.where"), "'");
        $con = rtrim($con, "'");
        if (I("get.op") == "unlock")
        {
            M("pos")->where($con)->setField("status", NORMAL);
            $txt = "开启";
        }
        else
        {
            M("pos")->where($con)->setField("status", LOCK);
            $txt = "关闭";
        }
        $return = array("echo" => 1,"info" => "批量".$txt."成功!", "url" => session("prev_urlpos"),"tag" => ".pos");
        $this->ajaxReturn($return);
    }

    public function commonManage($title = "POS&nbsp;->&nbsp;POS机管理")
    {
        $this->commonHead($this, $title);
        //设置添加按钮弹出窗口
        $this->setTool("tool_btn_down", array("txt" => "新增POS机", "icon" => "plus",
                "url" => U()."&form=add",
                "pop" => $this->getPop("add")));

        //设置批量删除/关闭操作
//         $this->setBatch("删除所选", U()."&form=del", array("query" => true,
//                 'icon' => "remove", 'ext' => 'confirm="确定批量删除吗？"'));
        $this->setBatch("关闭POS", U("Pos/posLockBatch"), array("query" => true,
                'icon' => "eye-close", 'ext' => 'confirm="确定批量关闭POS吗？"'));
        $this->setBatch("开启POS", U("Pos/posLockBatch")."&op=unlock", array("query" => true,
                'icon' => "eye-open", 'ext' => 'confirm="确定批量开启POS吗？"'));
        $this->setBatch("简码打印", U("Pos/Pos/printfCode"), array("query" => true, "bool" => "kopen", 'icon' => "print"));
        $this->setBatch("全部打印", U("Pos/Pos/printfCode")."&op=all", array("query" => true, "name" => "excel",
                 "bool" => "kopen", 'icon' => "print"));

        $this->setForm("name", "posform");
        $this->setForm("handle_run post", "Pos/posSave");
        $this->setForm("handle_run show_edit", "Pos/posEdit");
        $this->setForm("handle_run info", "Pos/posInfo");
        $this->setForm("handle_run del", "Pos/posDel");
        $this->setForm("cols", 2);
        //设置表单成员， 添加、编辑和信息的元素  uniq为不得重复
        $this->setElement("pos_start", "hidden", "");
        $this->setElement("pay", "select", "支付公司", array("bool" => "required",
//                     "value" => "8",
                    "list" => parse_select_list("select id,name from pay order by sort_id", "", "", "请选择支付公司")));
        $this->setElement("cost", "num", "交易费率", array("bool" => "required", "addon" => "%"));


        $this->setElement("pos_name", "string", "商户名称", array("bool" => "uniq required"));
        $this->setElement("supplier", "select", "POS办理商", array("bool" => "required",
//                     "value" => "4",
                    "list" => parse_select_list("select id,proxy_sub_name from users where type=8",
                            "", "", "请选择POS办理商")));


        $this->setElement("abbr_name", "string", "小票名称", array("bool" => "required"));
        $this->setElement("proxy", "select", "POS使用商", array("bool" => "required",
//                     "value" => "4",
                    "url" =>  U("Pos/getSub"),
                    "tag" => ".sel_sub_id",
                    "list" => parse_select_list("select id,proxy_sub_name from users where type=8",
                            "", "", "请选择POS使用商")));

        $this->setElement("mcc", "autocomplete", "商户类型", array("bool" => "required me",
                "ext" => 'count="2" url="'.U("Pos/getPosType").'" callback="getPosType();"',
                "list" => parse_autocomplete("select mcc,name from pos_type order by sort_id")));
        $this->setElement("sid", "select", "POS布放站", array("bool" => "required", "pclass" => "sel_sub_id",
//                         "list" => array(array("val" => "5", "txt" => "测试分站"))));
                    "list" => array(array("val" => "", "txt" => "请选择POS布放站"))));



        $this->setElement("shop_id", "string", "商户编号", array("bool" => "uniq required", "hint" => "string",
                "maxlength" => 18));
        $this->setElement("day_max", "num", "日交易上限", array("bool" => "required readonly", "addon" => "元"));


        $this->setElement("models", "string", "终端编号", array("bool" => "uniq required", "hint" => "string",
                "maxlength" => 11));
        $this->setElement("month_max", "num", "月交易上限", array("bool" => "required", "addon" => "元"));
        $this->setElement("pwd", "num", "管理密码", array("bool" => "required", "value" => mt_rand(100000, 999999)));

        $this->setElement("bank_group", "group", "Pos结算账户");
        $this->setElement("bank", "combobox", "银行名称", array("bool" => "required",
                    "ext" => 'jbox="450,210,.bank_addr_label"
                            url="'.U("KyoCommon/Index/showBankBranchSel").'&form=posform"',
                    "list" => parse_autocomplete("select name from bank order by sort_id")));
        $this->setElement("card_name", "string", "开户姓名", array("bool" => "required"));

        $this->setElement("bank_addr", "string", "开户支行", array("bool" => "required readonly",
                "pclass" => "sel_bank_addr", "lclass" => "bank_addr_label"));
        $this->setElement("card", "string", "银行账号", array("bool" => "required", "hint" => "card", "min" => 16,
                "maxlength" => 20));

        $addBtn = new FormElement("add_time_btn", "link", "", array("icon" => "plus",
                                        "url" => U("PosType/time_win")."&form=add",
                                        "pop" => "w:450,h:360,n:'addtime',t:新增交易时间和金额区间"
                                    ));
        $this->setElement("dealtime", "static", "时间金额设置", array("sig_row" => true, "value" => $addBtn->fetch()));

        $this->setElement("remark", "textarea", "备注");
        $this->setForm("js", "kyo_pos");
        $this->setForm("auto", array(
                array("create_time", 'getcurtime', 1, "function"),
                array("update_time", 'getcurtime', 3, "function"),
        ));

        $this->setTitle(array("状态", "所属分站", "内部代码", "POS简码", "商户名", "POS终端", "商户名称", "交易费率", "支付公司", ));
        $this->setField(array("status", "sid", "code", "sub_code", "shop_id", "models", "pos_name", "cost", "pay"));
        $this->setData("chkVal", 1);
        $this->setData("data_field 0 run", "Pos/getPosStatus");
//         $this->setData("close_chkall", 1);
        $this->setData("order", "update_time desc");
        $this->setData("excel", array("name" => "POS简码导出"));
        $this->setField("pos_name", array("name" => "6", "url" => U()."&form=info&where='code='[code]'",
                "pop" => $this->getPop("info", "[models] POS详细信息")));
        $this->setOp("编辑", U()."&form=edit&where='id=[id]'",
                array("pop" => $this->getPop("edit", "编辑 [models] POS机具")));
        $this->setData("op_call", array("run", "Pos/posOp"));
//         $this->setOp("删除", U()."&form=del&where='code in ('[code]')'",
//                 array("query" => true, "ext" => 'confirm="确定删除吗？"'));
    }

    private function printfCodePermErr($info)
    {
        if ($this->admin == 9 || $this->admin == 0 || $this->admin == 7)
            echo $info;
        else
            $this->ajaxReturn(array("echo" => 1, "info" => $info));
        exit(0);
    }

    //批量打印POS简码
    public function printfCode()
    {
        header("Content-type: text/html; charset=utf-8");

        if ($_GET["op"] == "all")
            $all = sqlAll(str_replace("*", "sub_code,abbr_name ", $_SESSION["excel"]["sql"]));
        else
        {
            $con = substr($_GET["where"], 1, strlen($_GET["where"]) - 2);
            $all = sqlAll("select sub_code,abbr_name from pos where ".$con);
        }
        if (!$all)
            $this->printfCodePermErr("数据发送失败!");

//         echo M()->getLastSql();
//         echo '<body onload="window.print()">';
        $html = "<!DOCTYPE html><html><head>";
        $html .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
        $html .= '<title>_</title></head><body onload="window.print()">';

        $n = 0;
        foreach ($all as $pos)
        {
//              $pos["sub_code"] = "B01";
//              $pos["abbr_name"] = "蔚然服饰";
            if (!$pos["sub_code"] || !$pos["abbr_name"])
                continue;
            $html .= '<table style="width:170px;height:55px;background:white;border:1px black solid;font-size:12px;font-weight:bold;float:left;margin:5px">';
            $html .= '    <tr>';
            $html .= '    <td style="text-align:center;font-size:36px;font-weight:bold;color:red;">'.$pos["sub_code"].'</td>';
            $html .= '    </tr>';
            $html .= '    <tr>';
            $html .= '    <td style="text-align:center;vertical-align:bottom;">'.$pos["abbr_name"].'</td>';
            $html .= '    </tr>';
            $html .= '</table>';
            $n++;
        }
        if ($n == 0)
            $this->printfCodePermErr("pos机信息中没有简码和小票名称!");

        $html .= '</body></html>';

        if ($this->admin == 9 || $this->admin == 0 || $this->admin == 7)
        {
            echo $html;
            exit(0);
        }

        $excel = new \Common\Controller\KyoMail();
        $this->ajaxReturn(array("echo" => 1,
                "info" => $excel->printf("pos标签列表", $html, RUNTIME_PATH."pos标签列表.html")));
    }

    public function subPosList()
    {
        $this->commonManage("站内设置&nbsp;->&nbsp;POS机列表");
        $this->setTool("close_btn_down", 1);
        $this->setTool("tool_batch", array());
        $this->setBatch("简码导出", U("Pos/Pos/printfCode"), array("query" => true, 'icon' => "print"));
        $this->setFind("item pay", array());
        $this->setFind("item sid", array());
        $this->setFind("item search_type list", parse_select_list("array", array("sub_code", "models", "cost"),
                    array("POS简码", "POS终端号", "交易费率")));
        $this->setData("close_op", 1);
        $this->setData("close_chkall", 0);
        $this->setData("data_title", array());
        $this->setData("data_field", array());
        $this->setTitle(array("状态", "POS简码", "商户名称", "商户类型", "交易费率", "商户号", "终端号", "结算银行", "结算户名", "结算账号"));
        $this->setField(array("status", "sub_code", "pos_name", "mcc_name", "cost", "shop_id", "models", "bank", "card_name", "card"));
        $this->setData("data_field 0 run", "Pos/getPosStatus");
        if (I("error"))
            $this->setData("where", "status=".ERROR." and sid=".$this->sid);
        else
            $this->setData("where", "sid=".$this->sid);
        $this->setField("pos_name", array("name" => "2", "url" => U()."&form=info&where='code='[code]'",
                "pop" => $this->getPop("subinfo", "[models] POS详细信息")));
    }

    //pos添加和编辑处理
    public function posSave(& $formObj)
    {
        $form = & $formObj->form;
        $obj = & $form["dataObj"];
        $form["return"]["callback"] = "";

        if (!($_POST["start_time"] && $_POST["end_time"] &&
            $_POST["start_money"] && $_POST["end_money"]))
            $this->ajaxReturn(array("echo" => 1, "info" => "请填写交易时间和金额区间!"));

        $input_cost = $_POST["cost"];
        $_POST["cost"] = $_POST["cost"] / 100;
        if (!$_POST["id"] || $_POST["sid"] != $_POST["old_sid"])
        {
            $pos_subcode = C("POS_SUB_CODE");
            $_POST["sub_code"] = $pos_subcode[$input_cost];
            if ($_POST["sub_code"] == "")
                $_POST["sub_code"] = 'H';
            $pnum = sqlCol("select count(id) from pos where sid=".$_POST["sid"]." and cost='".$_POST["cost"]."'");
            $_POST["sub_code"] .= fill_zero(++$pnum);

//             $this->ajaxReturn(array("echo" => 1, "info" => $_POST["sub_code"].", ".$_POST["cost"]));
        }


        $pay = $_POST["pay"];
        $_POST["pay"] = M("pay")->field("name")->where("id=".$_POST["pay"])->select();
        $_POST["pay"] = $_POST["pay"][0]["name"];

        $mcc = explode(",", $_POST["mcc"]);
        $_POST["mcc"] = $mcc[0];
        $_POST["mcc_name"] = $mcc[1];

        $new_code = build_pos_code($pay, $_POST["sid"], $_POST["proxy"]);

        if ($_POST["id"])
        {
            //如果修改了支付公司和代理分站则要重新修改内部代码
            //截取新内部代码的支付公司和代理分站部分替换旧的内部代码
            //取出原代码中的随机数
            $rand = substr($_POST["code"], 4, 1);
            //组合新代码的支付公司随机数和代理分站字符串
            $new_code = substr($new_code, 2, 2).$rand.substr($new_code, 5, 2);
            //因为修改了内部代码所以要把pos_time表中的数据根据旧内部代码删除
            $old_code = $_POST["code"];
            //把组合后的代码覆盖原代码段
            $_POST["code"] = substr_replace($_POST["code"], $new_code, 2, 5);

            $ret = $obj->auto($form["auto"])->create($_POST, 2);
            $form["return"]["info"] = $obj->getError();
            $ret = $ret ? $obj->where("id=".$_POST["id"])->save(): $ret;
//                 $form["return"]["info"] .= $obj->getLastSql();
            if ($ret)
            {
                M("pos_time")->where("pcode='".$old_code."'")->delete();
                $ret =  $_POST["code"];
                $str = "编辑";
            }
        }
        else
        {
            $_POST["code"] = $new_code;
            $_POST["status"] = LOCK;
//             $_POST["pwd"] = mt_rand(100000, 999999);

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
                $data["pcode"] = $_POST["code"];
                $data["start_time"] = $_POST["start_time"][$i];
                $data["end_time"] = $_POST["end_time"][$i];
                $data["start_money"] = $_POST["start_money"][$i];
                $data["end_money"] = $_POST["end_money"][$i];
                M("pos_time")->add($data);
            }
            $form["return"]["info"] .= $str."成功!";
            if (!$_POST["id"])
            {
                //更新pos最大数到所属代理商
                parse_umax("pos", $_POST["proxy"]);
                $form["return"]["callback"] = "popUp('<div class=\"big_txt_hint\">".$_POST["pwd"]."</div>',
                    \"{w:350,h:200,n:'show_pwd',t:请设置&nbsp;".$_POST["models"]."&nbsp;机具管理密码}\");";
            }
        }
        else
            $form["return"]["info"] .= "输入数据格式有误!";

        if (!$ret)
        {
            $form["return"]["close"] = 0;
            $form["return"]["url"] = "";
        }

        $this->ajaxReturn($form["return"]);
        exit(0);
    }

    //pos编辑窗口

    public function posEdit($con, & $formObj)
    {
        $form = & $formObj->form;
        $obj = & $form["dataObj"];

        $data = $obj->where($con)->select();
        $formObj->formDataShowEdit($data);

        $form["element"]["sid"]["list"] = parse_select_list("select id,proxy_sub_name from users where type=6 and proxy_id=".$data[0]["proxy"]);
        $form["element"]["cost"]["value"] = $form["element"]["cost"]["value"] * 100;
        $code = $data[0]["code"];
        $form["element"]["pay"]["value"] = hexdec(substr($code, 2, 2));
        $form["element"]["mcc"]["value"] = $data[0]["mcc"].",".$data[0]["mcc_name"];
        $form["element"]["mcc"]["input_val"] = $data[0]["mcc"].",".$data[0]["mcc_name"];

        $form["element"]["dealtime"]["value"] = PosTypeController::get_time_list($code).$form["element"]["dealtime"]["value"];
        $formObj->setElement("code", "hidden", "", array("value" => $data[0]["code"]));
        $formObj->setElement("old_sid", "hidden", "", array("value" => $data[0]["sid"]));
    }

    //pos故障处理
    public function posErr()
    {
        if (IS_POST)
        {
            $return = array("echo" => 1, "close" => 1, "info" => "操作成功!", //.session("prev_urlpos"),
                    "url" => session("prev_urlpos"), "tag" => ".pos", "callback" => '');
//             $this->ajaxReturn($return);

            $obj = M("pos");
            $obj->where("code='".$_POST["code"]."'")->setField("status", NORMAL);

            save_operating_record($_POST["code"], POS_ERROR_END);

            $this->ajaxReturn($return);
            exit(0);
        }

        $err = new Form("", array("name" => "errform"));

        $err->setElement("audittype", "autocomplete", "故障解决方法", array(
                "placeholder" => "自己写故障解决方法!",
                "list" => parse_autocomplete("select txt from sel_remark where type_id=".POS_ERROR_END)));
        $err->setElement("remark", "textarea", "", array("close_label" => 1, "element_cols" => 12,
                "rows" => 6, "bool" => "required"));
        $err->setElement("code", "hidden", "", array("value" => I("get.code")));
        $err->set("btn 0 txt", "解决");
        echo $err->fetch();
        exit(0);
    }

    //Pos故障原因
    public function posErrInfo($code)
    {
        $err = new Form("", array("name" => "errform"));
        $un = sqlRow("select remark,oper_time from operating_record where code='".$code."' and
                        type=".POS_ERROR." order by oper_time desc");
        $err->setElement("remark", "textarea", "", array("close_label" => 1, "element_cols" => 12,
                "rows" => 6, "bool" => "readonly", "value" => $un["remark"]));
        $err->setElement("errtime", "static", "故障申报时间", array( "value" => $un["oper_time"]));
		$err->set("btn 0", array("txt" => "解决故障", "ext" => 'type="button"',
                "url" => U("Pos/posErr")."&code=".$code,
                "pop" => $this->getPop("err")));
        echo $err->fetch();
    }

    //Pos详细信息
    public function posInfo($con, & $formObj)
    {
        $obj = & $formObj->form["dataObj"];
        $el = & $formObj->form["element"];
        $poslist = $obj->where($con."'")->select();
        $data = $poslist[0];

        $code = new FormElement("info_code", "static", "内部代码", array("value" => $data["code"]));
        $status = new FormElement("info_status", "static", "POS状态", array("value" => $this->getPosStatus($data)));
        $sub_code = new FormElement("sub_code", "static", "POS简码", array("value" => $data["sub_code"]));
        $pwd = new FormElement("pwd", "static", "管理密码", array("value" => $data["pwd"]));

        $ext = array("pos_start" => array("add", $code->get(), $status->get()),
                "month_max" => array("add", $pwd->get(), $sub_code->get()));
        $formObj->formDataInfo($poslist, $ext);

        $usr = M("users")->field("id,proxy_sub_name")
                    ->where("id in (".$data["sid"].",".$data["proxy"].",".
                            $data["supplier"].")")->select();

        $el["cost"]["value"] = ($data["cost"] * 100)."%";
        $el["mcc"]["value"] .= ",".$data["mcc_name"];
//         $el["pos_name"]["element_cols"] = 4;

        foreach ($usr as $row)
        {
            if ($data["sid"] == $row["id"])
                $el["sid"]["value"] = $row["proxy_sub_name"];
            if ($data["proxy"] == $row["id"])
                $el["proxy"]["value"] = $row["proxy_sub_name"];
            if ($data["supplier"] == $row["id"])
                $el["supplier"]["value"] = $row["proxy_sub_name"];
        }

        if ($data["proxy"] == $data["supplier"])
            $el["supplier"]["value"] = $el["proxy"]["value"];

        $newid = ltrim(substr(strchr($con, "="), 1), "'");
        $el["dealtime"]["value"] = PosTypeController::get_time_list($newid, false);

        $status = $data["status"];
        if ($status == ERROR && !I("get.list"))
        {
            $formObj->set("close_btn_down", 0);
    		$formObj->set("btn 0", array("txt" => "解决故障", "ext" => 'type="button"',
                    "url" => U("Pos/posErr")."&code=".$data["code"],
                    "pop" => $this->getPop("err")));
            parse_link_html($formObj->form["btn"]);
        }

        if ($this->admin == 6 || $this->admin == 1)
            $formObj->setElementSort($this->sel_info);
    }

    //删除pos机
    public function posDel($con, & $formObj)
    {
        $obj = & $formObj->form["dataObj"];

        $ret = $obj->where($con)->delete();
        $newid = strchr($con, "=");
        $newid = $newid ? $newid : strchr($con, "in");
        M("pos_time")->where("pcode ".$newid)->delete();
        $formObj->form["return"]["info"] = $ret ? "删除成功!" : "没有匹配到要删除的数据!".$con;
        if ($ret)
            return true;
        return false;
    }

    //获取所属代理的分站列表
    public function getSub()
    {
        $sub = new FormElement("sid", "select", "", array("close_label" => 1, "close_element_div" => 1,
                     "bool" => "required", "pclass" => "sel_sub_id", "begin" => 0, "over" => 0, "form" => "posform",
                    "list" => parse_select_list("select id,proxy_sub_name from users where type=6 and proxy_id=".I("get.val"),
                              "", "", "请选择POS布放分站")));
        echo $sub->fetch();
    }

    //获取pos商户类型列表
    public function getPosType()
    {
        $id = explode(",", I("get.val"));
        $id = $id[0];
        $obj = M("pos_type");
        $data = $obj->field("id,month_max,mcc_cost")->where("mcc=".$id)->select();
        $data = $data[0];
        $data["mcc_cost"] = $data["mcc_cost"] * 100;
        $data["day_max"] = floor($data["month_max"] / 30 / 10)."0";
        $data["time"] = PosTypeController::get_time_list($data["id"]);

        $this->ajaxReturn($data);
    }
}
