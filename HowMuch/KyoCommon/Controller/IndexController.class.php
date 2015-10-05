<?php
namespace KyoCommon\Controller;
use Think\Controller;
use Common\Controller\ListPage;
use Common\Controller\Form;
use Common\Controller\FormElement;
use Common\Controller\TabPage;
use Common\Controller\SmallDataList;

class IndexController extends ListPage
{
    private $admin;

    public function __construct()
    {
        $this->admin = get_user_info("admin");
    }

    public function update($name = "", $all = "", $grp = "", $one = "")
    {
        if (is_numeric($all))
        {
            $dtype = sqlCol("select type from hw where code='".$name."'");
            $url = sqlCol("select url from kyo_version where vtype=1 and dtype='".$dtype."' and vid=".($all + 1));
            if ($url)
                header("Location:".$url);
        }

        if (is_numeric($grp))
        {
            $gid = sqlRow("select proxy,sid,type from hw where code='".$name."'");
            if (!$gid)
                return -1;
            $who = sqlAll("select input from users where id in (".$gid["proxy"].", ".$gid["sid"].")");
            $who = $who[0][0].$who[1][0];
            $url = sqlCol("select url from kyo_version where dtype='".$gid["type"]."' and vtype=2 and vid=".($grp + 1)." and who='".$who."'");
            if ($url)
                header("Location:".$url);
        }

        if (is_numeric($one))
        {
            $dtype = sqlCol("select type from hw where code='".$name."'");
            $url = sqlCol("select url from kyo_version where vtype=3 and dtype='".$dtype."' and vid=".($one + 1)." and who='".$name."'");
            if ($url)
                header("Location:".$url);
        }
        return 0;
    }

    public function index()
    {
    }

    public function excelExport()
    {
        if (!is_login())
            $this->redirect("Home/Index/index");

//         $this->ajaxReturn(array("echo" => 1, "info" => "不能连续重复导出相同报表!---".$_SESSION["excel"]["filename"]));

        if (!$_SESSION["excel"]["filename"])
        {
            if (IS_AJAX)
                $this->ajaxReturn(array("echo" => 1, "info" => "不能连续重复导出相同报表!"));
            echo js_head('alert("不能连续重复导出相同报表!");window.close();');
            echo '<center><h1 style="color:red;font-weight:bold;"><p>不能连续重复导出相同报表!</p></h1></center>';
            exit(0);
        }
//         exit(0);

        if ($this->admin == 9 || $this->admin == 7 || $this->admin == 0)
        {
            $excel = new \Common\Controller\KyoExcel();
            $excel->export();
            return true;
        }

        $mail = new \Common\Controller\KyoMail();
        if (!$mail)
            $this->ajaxReturn(array("echo" => 1, "info" => "导出失败!"));
        $this->ajaxReturn(array("echo" => 1, "info" => $mail->export($_SESSION["excel"]["filename"])));
    }

    public function excelImport()
    {
        $pay = array("fuiou" => array("pname" => 1, "shop_id" => 2, "models" => 3, "rmb" => 4, "date" => 5,
                                       "card" => 7, "state" => 12),
                     "globebill" => array("pname" => 9, "shop_id" => 8, "models" => 10, "rmb" => 2,
                                           "date" => 7, "card" => 0, "state" => 3),
                     "ygww" => array("pname" => 2, "shop_id" => 4, "models" => 0, "rmb" => 17,
                                           "date" => 10, "card" => 6, "state" => 27),
                     );

        $js = '<script type="text/javascript">
            window.parent.upload_complete("input_excel", "", "[error]", "", "");
            </script>';

        //根据支付公司不同选择不同的列方案
        $eCol = explode("_", $_FILES["file_input_excel"]["name"]);
        $eCol = $pay[$eCol[0]];

        if (!$eCol)
        {
            echo str_replace("[error]", "不支持上传的报表数据!", $js);
            exit(0);
        }

        $excelRet = \Common\Controller\KyoExcel::import();
        if ($excelRet["error"])
        {
            echo str_replace("[error]", $excelRet["error"], $js);
            exit(0);
        }

        $r = 0;  //交易数据
        $models_not = '非该系统的POS机交易数据：<font color="red">';
        $models_not_num = 0;
        $redo = '重复的POS机交易数据：<font color="red">';
        $redo_num = 0;
        $err = '插入失败的交易数据：<font color="red">';
        $err_num = 0;
        $total = 0;   //插入成功数据个数


        foreach ($excelRet["data"] as $row)
        {
            $r++;
            //第一行为标题行，过滤
            if ($r == 1)
                continue;

            //判断是否为交易失败， 如果交易失败的记录不做处理直接过滤
            $state = $row[$eCol["state"]];

            //测试输出开始
//             dump($state);
//             dump($row[$eCol["models"]]);
//             dump($row[$eCol["shop_id"]]);
//             dump($row[$eCol["pname"]]);
//             dump(date("Y-m-d H:i:s", strtotime($row[$eCol["date"]])));
//             dump($row[$eCol["card"]]);
//             dump($row[$eCol["rmb"]]);
            //测试输出结束

            if (!($state == "成功" || $state == "交易成功" || $state == "交易被冲正"))
                continue;
            $deal_rmb = str_replace(",", "", $row[$eCol["rmb"]]);
            if (!strcmp($state,"交易被冲正"))
                $deal_rmb = ($deal_rmb * -1);

//             dump($state);

            //过滤非本代理的Pos机数据  商户号和终端号必须在本系统同一条pos记录内 返回此pos所属分站ID
            $pos = sqlRow("select status,code,proxy,sid,sub_code,abbr_name from pos where models='".$row[$eCol["models"]]."' and shop_id='".$row[$eCol["shop_id"]]."'");
            if (!$pos || $pos["status"] == LOCK)
            {
//                 dump(M()->getLastSql());
                $models_not = $models_not . $i . " ";
                $models_not_num++;
                continue;
            }
//             dump($pos);

            //验证此交易卡是否为同属POS机分站
            $ecard = str_replace("*", "%", $row[$eCol["card"]]);
            //获取前六后4的和pos同分站的卡
            $cardall = sqlAll("select code,bid,eid,opid,card,bank from card where sid=".$pos["sid"]." and card like '".$ecard."'");
            if (!$cardall)  //如果获取数据为空则记录错误，跳过此交易记录
            {
//                 dump(M()->getLastSql());
                $models_not = $models_not . $i . " ";
                $models_not_num++;
                continue;
            }
            else if (count($cardall) > 1)   //如果获取的卡有多张，代表此分站有多张相同的前六后4的卡
            {
                $findCard = array();
                $findIndex = 0;


                foreach ($cardall as $ci)   //循环遍历这些卡
                {
                    //到推送交易记录表中查找此卡今天是否在此Pos机上交易
                    $dealall = sqlAll("select code,rmb from deal_inspect where code='".$ci["code"]."' and pcode='".$pos["code"]."'
                            and DATE_FORMAT(deal_time,'%Y-%m-%d')='".date("Y-m-d")."'");
                    if (!$dealall)
                        continue;

                    $findCard[$findIndex] = $ci;  //保存今天在此pos有交易的卡
                    $findCard[$findIndex]["rmb"] = "|";  //并且保存今天此卡在此pos机上的交易金额
                    foreach ($dealall as $deal)  //如果此卡有多条今天在此pos机上的交易，循环保存这些金额
                    {
                        $findCard[$findIndex]["rmb"] .= $deal["rmb"]."|";
                    }
                    $findIndex++;
                }
//                 dump($findIndex);
//                 dump($findCard);

                if ($findIndex == 1)  //如果只找到一条记录，则代表已经找到此交易记录的卡
                    $card = $findCard[0];
                else if ($findIndex == 0)  //如果一条记录都没有找到，代表此交易作废
                {
                    $models_not = $models_not . $i . " ";
                    $models_not_num++;
                    continue;
                }
                else  //如果今天有多张卡在此pos机上交易则比对此交易的金额
                {
                    $findrmbCard = array();
                    $findIndex = 0;
                    foreach ($findCard as $ci)
                    {
//                         dump($ci["rmb"].", |".str_replace("-", "", $deal_rmb)."|");
                        if (strstr($ci["rmb"], "|".str_replace("-", "", $deal_rmb)."|"))
                            $findrmbCard[$findIndex++] = $ci;
                    }
//                     dump($findrmbCard);

                    if ($findIndex == 1)  //如果只找到一条记录，则代表已经找到此交易记录的卡
                        $card = $findrmbCard[0];
                    else //如果一条记录都没有找到或找到多条记录，代表操作员没有按照推荐金额刷或系统刚好推荐的金额一样，添加到异常数据列表中
                    {
                        $card["eid"] = -1;
                        $card["opid"] = -1;
                        $card["bid"] = -1;
                        $card["bank"] = -1;
                        $card["card"] = $ecard;
                    }
                }
            }
            else
                $card = $cardall[0];

            if (!$card["card"])
                $card["card"] = -1;

            //过滤重复的的数据
            if (sqlCol("select id from transaction where date='".$row[$eCol["date"]]."' and card='".$card["card"]."'"))
            {
                $redo = $redo . $i . " ";
                $redo_num++;
                continue;
            }

            //组合字段往数据库里写入
            $data = array();
            $data["sid"] = $pos["sid"];
            $data["proxy"] = $pos["proxy"];
            $data["pcode"] = $pos["code"];
            $data["sub_code"] = $pos["sub_code"];
            $data["eid"] = $card["eid"];
            $data["opid"] = $card["opid"];
            $data["bid"] = $card["bid"];
            $data["bank"] = $card["bank"];
            $data["card"] = $card["card"];
//             $data["pname"] = $row[$eCol["pname"]];
            $data["pname"] = $pos["abbr_name"];
            $data["business"] = $row[$eCol["shop_id"]];
            $data["terminal"] = $row[$eCol["models"]];
            $data["rmb"] = $deal_rmb;
            $data["date"] = date("Y-m-d H:i:s", strtotime($row[$eCol["date"]]));
            $data["dates"] = getcurtime();
//             if ($ecard == "625961%%%%9103")
//                 dump($data);
            $obj = M("transaction");
            $ret = $obj->add($data);
//             $ret = 1;
//             dump($data);
//             M("pos")->where("code='".$data["pcode"]."'")->setField("close_time", $data["dates"]);
            //添加失败，记录失败数据
            if (!$ret)
            {
                $err = $err . $i . " ";
                $err_num++;
                $sql = $obj->getLastSql();
                if (!$data["card"])
                {
                    $data["eid"] = -1;
                    $data["opid"] = -1;
                    $data["bid"] = -1;
                    $data["bank"] = -1;
                    $obj->add($data);
                }
                $msg = "发现一笔&emsp;".get_sub_name($pos["sid"])."分站&emsp;".$pos["sub_code"]."&emsp;POS机&emsp;";
                $msg .= "上的交易记录上传失败! <br /> 请及时处理! : ".$sql;
                auto_send_msg("异常交易数据", $msg, "0,7,9");
            }
            else
            {
                $total++;
                if ($card["bid"] == -1)
                {
                    $msg = "发现一笔&emsp;".get_sub_name($pos["sid"])."分站&emsp;".$pos["sub_code"]."&emsp;POS机&emsp;";
                    $msg .= "上未匹配到信用卡的交易记录! <br /> ";
                    $msg .= "请点击&emsp;".parse_msg_link(U("KyoCommon/Deal/abnormalData"), $ecard)."&emsp;对此进行处理!";
                    auto_send_msg("异常交易数据", $msg, "0,7,9");
                }
            }
        }
        //如果有数据则调用推荐系统交易汇聚
        if ($total > 0)
            sqlCol("call pro_disburse_hours;");

        $models_not = $models_not . "</font>";
        $redo = $redo . "</font>";
        $err = $err . "</font>";

        $error = "非系统交易数据有 $models_not_num 条， 重复交易数据有 $redo_num 条, 插入失败交易数据有 $err_num 条， 导入成功的交易数据有 $total 条!";

        echo str_replace("[error]", $error, $js);

        exit(0);
    }

    //打印卡片条形码
    public function barcode()
    {
        if (!is_login())
            $this->redirect("Home/Index/index");

        header("Content-type: text/html; charset=utf-8");

        if ($_GET["op"] == "all")
            $all = sqlAll(str_replace("*", "code,card,bank,pay_pwd,query_pwd,bid ", $_SESSION["excel"]["sql"]));
        else
        {
            $con = "c.".substr($_GET["where"], 1, strlen($_GET["where"]) - 2);
    //         dump($con);
            $all = sqlAll("select c.code,c.card,c.bank,c.pay_pwd,c.query_pwd,b.name from
                            card c, basis b where c.bid=b.id and ".$con);
        }
        if (!$all)
        {
            if ($this->admin == 9 || $this->admin == 0 || $this->admin == 7)
                echo "数据获取失败!";
            else
                $this->ajaxReturn(array("echo" => 1, "info" => "数据发送失败!"));
        }

        $html = "<!DOCTYPE html><html><head>";
        $html .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
        $html .= '<title>_</title></head><body onload="window.print()">';

        foreach ($all as $card)
        {
//              $card["code"] = "A112233445552";
//              $card["card"] = "6543234567895467";
//              $name = "欧阳修人";
//              $card["pay_pwd"] = "124567";
            if (isset($card["bid"]))
                $name = get_basisname($card["bid"]);
            else
                $name = $card["name"];

            $html .= '<table style="width:200px;height:55px;background:white;border:1px black solid;font-size:12px;font-weight:bold;float:left;margin:5px">';
            $html .= '    <tr>';
            $html .= '    <td style="height:15px;text-align:left" colspan="2">&nbsp;'.format_dis_field($card["card"]).'</td>';
            $html .= '    </tr>';
            $html .= '    <tr>';
//             $html .= '    <td style="width:135px;"><img src="'.$_SERVER['SERVER_NAME'].__ROOT__.'/Public/barcode/barcode.php?text='.$card["code"].'" style="width:135px;height:35px;" /></td>';
            $html .= '    <td style="width:135px;"><img src="http://120.83.3.20:918/Public/barcode/barcode.php?text='.$card["code"].'" style="width:135px;height:35px;" /></td>';
            $html .= '    <td style="height:35px;text-align:center;vertical-align:top;">';
            $html .= '    <span style="font-size:10px">'.$name.'<br />';
            $html .= '    <span style="font-size:16px">'.$card["pay_pwd"].'</span></td>';
            $html .= '    </tr>';
            $html .= '</table>';
        }

        $html .= '</body></html>';

        if ($this->admin == 9 || $this->admin == 0 || $this->admin == 7)
        {
            echo $html;
            exit(0);
        }

        $excel = new \Common\Controller\KyoMail();
        $this->ajaxReturn(array("echo" => 1,
                "info" => $excel->printf("信用卡条形码导出列表", $html, RUNTIME_PATH."信用卡条形码导出列表.html")));
    }

    //数据列表上移下移功能
    public function listMove($table, $where)
    {
        if (!is_login())
            $this->redirect("Home/Index/index");

        $where = str_replace("'", "", $where);
        $id = explode("sort_id>", $where);
        if (!$id)
            $id = explode("sort_id<", $where);
        $id = $id[1];
        $obj = M($table);
        $data = "";

        if (strchr($where, ">"))
        {
            $data = $obj->where($where)->order("sort_id")->limit('1')->find();
            $where = str_replace(">", "=", $where);
            if (!$data)
                $this->ajaxReturn(array("echo" => 1, "info" => "到底了!"));
        }
        else
        {
            $data = $obj->where($where)->order("sort_id desc")->limit('1')->find();
            $where = str_replace("<", "=", $where);
            if (!$data)
                $this->ajaxReturn(array("echo" => 1, "info" => "到顶了!"));
        }

//         $this->ajaxReturn(array("echo" => 1, "info" => "移动位置失败!".$where." ".$data["sort_id"]." ".$id));
        $mdata = $obj->where($where)->find();
        $obj->where("id=".$data["id"])->setField("sort_id", $mdata["sort_id"]);
        $obj->where("id=".$mdata["id"])->setField("sort_id", $data["sort_id"]);

        $this->ajaxReturn(array("echo" => 0, "info" => "移动位置成功!",
                "url" => session("prev_url".$table)."&data=1", "tag" => ".".$table));
    }

   //系统状态处理
    static public function statusLink($data = "", $txt = "")
    {
        $class = "";
        $code = I("get.code");
        if ($code)
        {
            echo sqlCol("select remark from operating_record where code='".$code."'
                    and type=".CUST_AUDIT_NO_PASS." order by oper_time desc");
            exit(0);
        }
        else
            $code = $data["code"];
        $stxt = get_status_txt($data["status"]);
        if ($data["status"] == AUDIT && sqlCol("select count(id) from operating_record where
                    code='".$code."' and type=".CARD_AUDIT_NO_PASS))
        {
            $class = "kyo_red";
            $stxt = "再终审";
        }
//         dump(M()->getLastSql());
//         if ($data["status"] == NO_PASS)
//         {
//             $r = new FormElement("nopass", "link", $stxt, array(
//                     "begin" => 0,
//                     "over" => 0,
//                     "class" => "text-danger",
//                     "close_element_div" => 1,
//                     "url" => U()."&code=".$data["code"],
//                     "pop" => "w:480,h:360,n:'nopass',t:审核拒绝通过原因"));
//             return $r->fetch();
//         }
        //站长提交解约操作，状态为待解约状态
        if ($data["status"] == NORMAL && $data["temp_status"] == RESCIND)
        {
            $class = "kyo_red";
            $stxt = "待解约";
        }

        //增值提交后，临时状态会改变
        if ($data["status"] == NORMAL && $data["temp_status"] == RISING)
        {
            $class = "kyo_black";
            $stxt = "增值中";
        }
        //续约提交后，临时状态会改变
        if ($data["status"] == NORMAL && ($data["temp_status"] == APPLY || $data["temp_status"] == NO_PASS))
        {
            $class = "kyo_black";
            $stxt = "续约中";
        }

        if ($data["status"] == NO_PASS || $data["status"] == LOCK)
            $class = "kyo_red";

        if ($data["status"] == SEARCH)
            $class = "kyo_black";

        if ($txt == "card")
            $op_title = format_dis_field($data["card"]);
        else
            $op_title = $data[$txt];
        $r = new FormElement("oper", "link", $stxt, array(
                "begin" => 0,
                "over" => 0,
                "class" => $class,
                "close_element_div" => 1,
                "url" => U("KyoCommon/Index/showOpRecord")."&code=".$data["code"],
                "pop" => "w:1000,h:1000,n:'oprecord't:".$op_title." 操作记录"));
        return $r->fetch();
//         return $stxt;
    }

    //显示操作日志
    public function showOpRecord($code, $type="", $op_date = "")
    {
        if (!is_login())
            $this->redirect("Home/Index/index");

        $html = "";

        if ($code)
            $code = "code='".$code."'";

        if ($type)
            $type = " and type=".$type;

        if ($op_date)
            $oper_time = " and oper_time ".urldecode($op_date);

        $record = sqlAll("select * from operating_record where ".$code." ".$type." ".$oper_time." order by oper_time desc");

        $record_txt = C("RECORD_TEXT");

        foreach ($record as $r)
        {
            if (!($this->admin == 0 || $this->admin == 9 || $this->admin == 7) &&
                ($r["oper_group"] == 7 || $r["oper_group"] == 9 || $r["oper_group"] == 0))
                $grptxt = "系统管理员";
            else
                $grptxt = get_perm_name($r["oper_group"]).' '.get_username($r["opid"]);

            $html .= '<table class="table table-bordered text-left" style="border:2px #999 solid;margin-bottom:2px;">';
            $html .= '    <tr>';
            $html .= '        <td class="active text-right col-xs-1">操作类型:</td>';
            $html .= '        <td class="col-xs-3">'.$record_txt[$r["type"]].'</td>';
            $html .= '        <td class="active text-right col-xs-1">操作人员:</td>';
            $html .= '        <td class="col-xs-2">'.$grptxt.'</td>';
            $html .= '        <td class="active text-right col-xs-1">操作时间:</td>';
            $html .= '        <td class="">'.$r["oper_time"].'</td>';
            $html .= '    </tr>';
            $html .= '    <tr>';
            $html .= '        <td class="active text-right">操作说明:</td>';
            $html .= '        <td colspan="5" style="height:50px">'.$r["remark"].'</td>';
            $html .= '    </tr>';
            $html .= '</table>';
        }
        if ($html == "")
            $html = '<h1 style="color:red;font-weight:bold;">没有操作记录!</h1>';
        else
            $html = '<div class="table-responsive">'.$html.'</div>';
        if ($op_date)
            return $html;
        echo $html;
    }

    //显示支行选择省份
    public function showBankBranchSel()
    {
        if (!is_login())
            $this->redirect("Home/Index/index");

        $form = "&form=".I("get.form");
        $bank = I("get.bank");
        $province = I("get.province");
        $val = I("get.val");
        $html = "";
        if (!$bank)
        {
            $province = new FormElement("province", "autocomplete", "支行省份", array("form" => "bankform",
                    "element_cols" => 6, "begin" => 1, "over" => 0,
                    "url" => U("Index/showBankBranchSel").$form."&bank=".$val,
                    "tag"  => ".sel_city_id",
                    "list" => parse_autocomplete("select DISTINCT province from bank_branch where bank='".$val."'"),
            ));
            if ($province->get("list") == "")
            {
                $js = '$("#bank_addr_id").prop("readonly", false);';
                $js .= '$("#bank_addr_input").prop("readonly", false);';
                $js .= '$("#bank_addr_id").val("");';
                $js .= '$("#bank_addr_input").val("");';
                $js .= '$("#bank_addr_data").empty();';
                echo '<h3 class="text-center">不支持 '.$val.' 的支行选择，请更换借记卡或手动填写开户支行!</h3>'.js_head($js);
                return false;
            }

            $html = $province->fetch();
            $city = new FormElement("city", "string", "支行城市", array("form" => "bankform",
                    "pclass" => "sel_city_id", "element_cols" => 6, "begin" => 0, "over" => 1, "bool" => "readonly"));
        }
        else
        {
            if ($province)
            {
                $branch = new FormElement("bank_addr", "autocomplete", "开户支行", array("form" => I("get.form"),
                        "close_element_div" => 1, "begin" => 0, "over" => 0, "bool" => "required autofocus sync",
                        "list" => parse_autocomplete("select bname from bank_branch where bank='".$bank."'
                                         and province='".$province."' and city='".$val."'"),
                ));
//                 dump($branch->fetch());
                $js = '$("#bank_addr_input").click();';
                echo $branch->fetch().js_head($js);
                return;
            }
            $city = new FormElement("city", "autocomplete", "支行城市", array("form" => "bankform",
                    "close_element_div" => 1, "begin" => 0, "over" => 0,
                    "url" => U("Index/showBankBranchSel").$form."&bank=".$bank."&province=".$val,
                    "tag"  => ".sel_bank_addr", "ext" => 'val_callback=\'$("#body").click();\'',
                    "list" => parse_autocomplete("select DISTINCT city from bank_branch where bank='".$bank."'
                                     and province='".$val."'"),
                ));
        }
        $html .= $city->fetch();
//         dump($html);

        $js = '$("#bank_addr_id").val("");';
        $js .= '$("#bank_addr_input").val("");';
        $js .= '$("#bank_addr_data").empty();';
        echo js_head($js).$html;
    }
}
