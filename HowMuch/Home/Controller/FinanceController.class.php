<?php

namespace Home\Controller;

use Common\Controller\ListPage;
use Common\Controller\DataList;
use Common\Controller\MainPanel;
use Common\Controller\SmallDataList;
// use Common\Controller\PageTool;
use Common\Controller\Form;
use Common\Controller\FormElement;
use KyoCommon\Controller\ReportController;

class FinanceController extends ListPage
{

    private $repay_type_name = array("0" => "POS账户","1" => "备付金账户");

    private $pdata = array();

    private $pdatalen = array("bank" => 0,"pos" => 100,"card" => 30);

    private $repaylist = array();

    private $temp = array("list" => "", "id" => 0, "count" => 0);

    private $list_count = 0;
    private $sid;
    private $uid;
    // $temp_list = array();
    // $temp_count = 0;
    // $temp_id = -1;
    private $rtypeTxt = array("签单佣金" => 3, "增值佣金" => 4, "服务佣金" => 5, "续约佣金" => 6);
            
    public function __construct()
    {
        parent::__construct();
        if (get_user_info("admin") != 1)
            $this->redirect("Index/index");
        $this->sid = get_user_info("sid");
        $this->uid = get_user_info("uid");
    }
    
    // 获取客户相关弹窗口信息
    static public function getPop($type, $title = "")
    {
        $pop = "";
        switch ($type)
        {
            case "histroy":
                if (!$title)
                    $title = "当日已还列表";
                $pop = "w:1200,h:550,n:'histroy',t:" . $title;
                break;
            case "risingInfo":
                if (!$title)
                    $title = "客户增值详细信息";
                $pop = "w:450,h:530,n:'risingInfo',t:" . $title;
                break;
            case "repay":
                if (!$title)
                    $title = "确认还款";
                $pop = "w:820,h:450,c:1,n:'repay',t:" . $title;
                break;
            case "bonus":
                if (!$title)
                    $title = "佣金明细";
                $pop = "w:820,h:450,c:1,n:'bonuswin',t:" . $title;
                break;
            default:
                break;
        }
        return $pop;
    }

    public function report()
    {
        $report = new ReportController();
        
        $report->set("group", array("明细报表 "));
        // 第一个参数: 报表标识符 保证唯一性
        // 第二个参数: 报表标题
        // 第三个参数: 报表属性哪个组, 从零开始计起对应关系
        PublicController::commonReport($report);

        $this->show($report->fetch());
    }

    public function index()
    {
        //财务站长通用报表
        $html = PublicController::commonBriefing();
        
        // -----------------------------index-content5-今日POS入账表------------------------------------------
//         $data = new MainPanel("day_profits", "", 
//                 array("close_num" => 1,"page" => array("size" => 5)));
//         $data->setCustomList("Report_sub", false, 
//                 array(3,get_user_info("sid")));
//         $data->setTitle(
//                 array("内部代码","终端号","商户名","交易日期","交易金额","MCC支出","实入账金额","入账日期"));
//         $data->setField(
//                 array("code","models","pos_name","dates1","drmb","pos_cost","prmb","aacc"));
//         // $data->setField("sub", array("name" => 0, "url" =>
//         // U("Main/sub_day_profits"."?small=day_profits"), "pop" =>
//         // "w:600,h:500,n:customer,t:各分站成本利润统计"));
//         $html .= $data->fetch("今日POS入账");
        
        $this->assign("main_body", $html);
        $this->display(false, "Home@Public/index");
    }
    
 
    // 查询银行统计表中是否有指定银行，如果有返回此银行数据，如果没有则返回false
    private function get_bank($find_bank, $flag = "key")
    {
        if (isset($this->pdata["bank"]))
        {
            foreach ($this->pdata["bank"] as $key => $bank)
            {
                // echo "find= ".$find_bank.", bank ,".$bank["bank"]." , key =
                // ".$key."<br />";
                if ($find_bank == $bank["bank"])
                {
                    return $flag == "key" ? $key : $bank;
                }
            }
        }
        return false;
    }
    
    // 排序数据
    private function kyo_sort(& $array, $name, $sort = "small")
    {
        $len = count($array);
        for ($i = 0; $i < $len; $i++)
        {
            for ($j = $i + 1; $j < $len; $j++)
            {
                if (($sort == "small" &&
                         $array[$i][$name] > $array[$j][$name]) ||
                         ($sort != "small" &&
                         $array[$i][$name] < $array[$j][$name]))
                {
                    $temp = $array[$i];
                    $array[$i] = $array[$j];
                    $array[$j] = $temp;
                }
            }
        }
    }
    
    // 还款列表添加最终显示列表
    private function add_list($type, $out, $put, $sub = true)
    {
        $list = & $this->repaylist;
        $count = & $this->list_count;
        $type_name = $this->repay_type_name;
        
        karray($put, 
                array("code","name","bank","card","advice"));
        karray($out, 
                array("id", "name","bank","card","prmb"));
        
        $list[$count]["id"] = $count;
        $list[$count]["type"] = $type;
        $list[$count]["account_type"] = $type_name[$type];
        $list[$count]["pos_id"] = $out["id"];  
        $list[$count]["out_name"] = $out["name"];  
        $list[$count]["out_bank"] = $out["bank"];
        $list[$count]["out_card"] = $out["card"];
        if ($out && $sub)
            $list[$count]["out_prmb"] = sprintf("%.1f", 
                    $out["prmb"] - $put["advice"]);
        
        $list[$count]["put_code"] = $put["code"];
        $list[$count]["put_name"] = $put["name"];
        $list[$count]["put_bank"] = $put["bank"];
        $list[$count]["put_card"] = $put["card"];
        if ($put["card"] == 1)
            $list[$count]["put_card_dis"] = "备付金账户";
        else
        {
            $list[$count]["put_card_dis"] = $put["card"];
            if (isset($put["remaining_period"]))
                $list[$count]["remaining_period"] = $put["remaining_period"];
        }
        
        $list[$count]["put_amount"] = sprintf("%.1f", $put["advice"]);
        $count++;
    }
    
    // 临时按pos银行名称存最终显示列表
    private function add_tmp_list($out, $put)
    {
        $list = & $this->temp["list"];
        $old_id = & $this->temp["id"];
        $count = & $this->temp["count"];
        $type_name = $this->repay_type_name;
        
        if ($old_id != $out["id"])
        {
            $count = 0;
            $old_id = $out["id"];
        }
        
        // $list[$out["bank"]][$count]["id"] = $count;
        $list[$out["id"]][$count]["type"] = 0;
        $list[$out["id"]][$count]["account_type"] = $type_name[0];
        $list[$out["id"]][$count]["pos_id"] = $out["id"];
        $list[$out["id"]][$count]["out_name"] = $out["name"];
        $list[$out["id"]][$count]["out_bank"] = $out["bank"];
        $list[$out["id"]][$count]["out_card"] = $out["card"];
        if ($out)
            $list[$out["id"]][$count]["out_prmb"] = sprintf("%.1f", 
                    $out["prmb"] - $put["advice"]);
        $list[$out["id"]][$count]["put_code"] = $put["code"];
        $list[$out["id"]][$count]["put_name"] = $put["name"];
        $list[$out["id"]][$count]["put_bank"] = $put["bank"];
        $list[$out["id"]][$count]["put_card"] = $put["card"];
        if (isset($put["remaining_period"]))
            $list[$out["id"]][$count]["remaining_period"] = $put["remaining_period"];
        $list[$out["id"]][$count]["put_amount"] = sprintf("%.1f", $put["advice"]);
        
        $count++;
    }
    
    // 处理Pos和卡列表相同银行
    private function bank_eq_handle(& $pos, $bank)
    {
        // 如果pos账户余额大于同银行卡总额
        if ($pos["prmb"] >= $bank["advice"])
        {
            // 循环卡对与pos相同银行卡进行还款记录 并且将此将在卡列表中删除
            foreach ($this->pdata["card"] as $key => $card)
            {
                if ($pos["bank"] != $card["bank"])
                    continue;
                $this->add_tmp_list($pos, $card);
                $pos["prmb"] = $pos["prmb"] - $card["advice"];
                unset($this->pdata["card"][$key]);
            }
        }
        else // 如果pos账户余额小于同银行卡总额 则同样记录相同银行卡还款，直到pos余额不够还任何一家同银行卡退出
        {
            foreach ($this->pdata["card"] as $key => $card)
            {
                if ($pos["bank"] != $card["bank"] ||
                         $pos["prmb"] < $card["advice"])
                    continue;
                $this->add_tmp_list($pos, $card);
                $pos["prmb"] = $pos["prmb"] - $card["advice"];
                unset($this->pdata["card"][$key]);
            }
        }
    }
    
    // 查询大于还款金额的卡号
    private function get_card($find_rmb)
    {
        foreach ($this->pdata["card"] as $key => $card)
        {
            if ($card["advice"] != 0 && $find_rmb >= $card["advice"])
                return $key;
        }
        return false;
    }
    
    // 获取基础数据 pos bank card
    private function getRepayBaseData()
    {
        $sid = $this->sid;
        $isParse = sqlAll("select id from repayment_record where rtype=1 and sid=".$sid." and
                            DATE_FORMAT(create_time,'%Y-%m-%d')='".date("Y-m-d")."'");
        if ($isParse && count($isParse) > 0)
        {
            $this->repaylist = sqlAll("select * from repayment_record where rtype=1 and state=1 and
                    sid=".$sid." and DATE_FORMAT(create_time,'%Y-%m-%d')='".date("Y-m-d")."' order by id");
            return 1; 
        }
//         dump($this->repaylist);
//         exit(0);
        
        $this->pdata["card"] = sqlPro("Payment_system", "", array(3,$sid));
        $this->pdata["bank"] = sqlPro("Payment_system", "", array(4,$sid));
        $this->pdata["pos"] = sqlPro("Payment_system", "", array(5,$sid));
        if (!count($this->pdata["card"]))  //如果没有卡，则当日没有还款列表
        {
            $this->pdata["pos"] = "";
            $this->pdata["bank"] = "";
        }
//        dump($this->pdata["card"]);
//         dump($this->pdata["bank"]);
//         dump($this->pdata["pos"]);
//         exit(0);
        
        // $bank_str = array("中国银行", "工商银行", "建设银行", "农业银行", "广发银行", "招商银行",
        // "平安银行", "民生银行", "光大银行");
        
        // function rand_name($len = 3)
        // {
        // $sex = array("刘", "朱", "张", "何", "王", "谷", "闰", "许", "魏", "黄", "欧阳",
        // "司马", "温", "库", "习");
        // $name = array("一", "二", "三", "四", "五", "六", "七", "八", "九", "十", "名",
        // "空", "新", "存", "巍", "思",
        // "龙", "湘", "东", "俊", "天", "雷", "军", "大", "小", "明", "韵", "雪", "冰", "兵",
        // "研", "国");
        // $new_name = $sex[mt_rand(0, 14)];
        // for ($i = 0; $i < $len - 1; $i++)
        // {
        // $new_name = $new_name.$name[mt_rand(0, 31)];
        // }
        // return $new_name;
        // }
        
        // for ($i = 0; $i < $this->pdatalen["card"]; $i++)
        // {
        // $this->pdata["card"][$i]["bank"] = $bank_str[mt_rand() % 9];
        // $this->pdata["card"][$i]["code"] = "F17Z500".mt_rand(100000, 999999);
        // $this->pdata["card"][$i]["name"] = rand_name(mt_rand(2, 3));
        // $this->pdata["card"][$i]["card"] = "62254320".mt_rand(11111111,
        // 99999999);
        // $this->pdata["card"][$i]["advice"] = mt_rand(10, 5000).".".mt_rand(0,
        // 99);
        
        // $bank_key = $this->get_bank($this->pdata["card"][$i]["bank"]);
        // if ($bank_key === false)
        // {
        // $this->pdata["bank"][$this->pdatalen["bank"]]["bank"] =
        // $this->pdata["card"][$i]["bank"];
        // $this->pdata["bank"][$this->pdatalen["bank"]]["advice"] =
        // $this->pdata["card"][$i]["advice"];
        // $this->pdata["bank"][$this->pdatalen["bank"]]["sum"] = 1;
        // $this->pdatalen["bank"]++;
        // }
        // else
        // {
        // $this->pdata["bank"][$bank_key]["advice"] =
        // $this->pdata["bank"][$bank_key]["advice"] +
        // $this->pdata["card"][$i]["advice"];
        // $this->pdata["bank"][$bank_key]["sum"] =
        // $this->pdata["bank"][$bank_key]["sum"] + 1;
        // }
        // }
        
        // for ($i = 0; $i < $this->pdatalen["pos"]; $i++)
        // {
        // $this->pdata["pos"][$i]["id"] = $i;
        // $this->pdata["pos"][$i]["bank"] = $bank_str[mt_rand() % 9];
        // $this->pdata["pos"][$i]["name"] = rand_name(mt_rand(2, 3));
        // $this->pdata["pos"][$i]["card"] = "62255690".mt_rand(11111111,
        // 99999999);
        // $this->pdata["pos"][$i]["prmb"] = mt_rand(10, 5000).".".mt_rand(0,
        // 99);
        // }
        
        $this->kyo_sort($this->pdata["pos"], "bank");
        // $this->kyo_sort($this->pdataa["bank"], "bank");
        $this->kyo_sort($this->pdata["card"], "advice", "big");
        
        $code = "";
        $ci = 0;
        foreach ($this->pdata["card"] as $key => $card)
        {
            $num = 0;
            foreach ($this->pdata["card"] as $r)
            {
                if ($card["card"] == $r["card"])
                {
                    $num++;
                    if ($num == 2)
                    {
//                         echo $card["card"].", ".$card["advice"]."<br />";
//                         $code[$ci++] = $card["code"];
                        unset($this->pdata["card"][$key]);
                    }
                }
            }
        }
        return 0;
    }
    
    // 处理基础数据生成还款列表
    private function parseRepayList()
    {
        if ($this->getRepayBaseData())
            return 1;
        
        
        // 循环pos账号查找相同银行的卡进行金额匹配还款
        foreach ($this->pdata["pos"] as $key => $pos)
        {
            $bank = $this->get_bank($pos["bank"], "edit");
            if ($bank)
                $this->bank_eq_handle($this->pdata["pos"][$key], $bank);
        }
        
        
        // 如果pos机银行在卡列表不存在处理函数 并且对相同pos排序
        foreach ($this->pdata["pos"] as $key => $pos)
        {
            // 判断此Pos机是否在上面相同银行处理中生成到临时还款列表中, 如果有则把此pos机在临时列表中的记录复制到还款记录列表中
            if (isset($pos["id"]) && isset($this->temp["list"][$pos["id"]]) &&
                     $this->temp["list"][$pos["id"]])
            {
                foreach ($this->temp["list"][$pos["id"]] as $tlist)
                {
                    // echo ("name= ".$tlist["out_name"].", bank=
                    // ".$tlist["out_bank"].
                    // ", card= ".$tlist["out_card"].", advice=
                    // ".$tlist["put_amount"]."<br />");
                    $this->add_list("0", $pos, 
                            array("code" => $tlist["put_code"],"name" => $tlist["put_name"],
                                   "bank" => $tlist["put_bank"],"card" => $tlist["put_card"],
                                    "remaining_period" => $tlist["remaining_period"],
                                    "advice" => $tlist["put_amount"]), 
                            false);
                }
            }
            
            
            // 判断此pos机中的余额还能还哪些不同银行的卡，直到余额还完或没有匹配的卡
            while (1)
            {
                $card_key = $this->get_card($pos["prmb"]);
                if ($card_key === false)
                    break;
                if ($this->pdata["card"][$card_key]["advice"] != 0)
                {
                    $this->add_list("0", $pos, $this->pdata["card"][$card_key]);
                    $pos["prmb"] = $pos["prmb"] -
                             $this->pdata["card"][$card_key]["advice"];
                    
                    unset($this->pdata["card"][$card_key]);
                }
            }
            
            // 此时pos账户余额已经不够还任何一张卡，但是还是有余额, 则把余额取整打到备付金账户中
//             if ($pos["prmb"] > 0)    //原始判断条件
            if (floor($pos["prmb"]) > 0)    //如果不足1元则不划款不显示
            {
                $this->add_list("0", $pos, 
                        array("card" => "1","advice" => $pos["prmb"]));
            }
            M("pos")->where("id=".$pos["id"])->save(array("amount" => $pos["prmb"]));
        }
       
//         dump($this->pdata["bank"]);
//         dump($this->pdata["pos"]);
        
        // 不匹配pos账号的卡列表处理 把pos还不了的卡，全用备付金账户来还
        foreach ($this->pdata["card"] as $card)
        {
            $this->add_list(1, "", $card, false);
        }
        
        foreach ($this->repaylist as $key => $list)
        {
            $data = array();
            $data["rtype"] = 1;
            $data["account_type"] = $list["type"];
            $data["out_name"] = $list["out_name"];
            $data["out_bank"] = $list["out_bank"];
            $data["out_card"] = $list["out_card"];
            $data["put_code"] = $list["put_code"];
            $data["put_name"] = $list["put_name"];
            $data["put_bank"] = $list["put_bank"];
            $data["put_card"] = $list["put_card"];
            $data["put_amount"] = $list["put_amount"];
            $data["remaining_period"] = $list["remaining_period"];
            $data["pos_id"] = $list["pos_id"];
            $data["state"] = 1;
            $data["sid"] = $this->sid;
            $data["opid"] = $this->uid;
            $data["create_time"] = getcurtime();
            $this->repaylist[$key]["id"] = M("repayment_record")->add($data);
        }
        
        $this->repaylist = sqlAll("select * from repayment_record where rtype=1 and state=1 and
                sid=".$this->sid." and DATE_FORMAT(create_time,'%Y-%m-%d')='".date("Y-m-d")."' order by id");
        
        return 0;
    }
    
    // 确认还款窗口
    public function repayment_verify()
    {
        if (IS_POST)
        {
            if ($_POST["fact_amount"] == "" || $_POST["fact_amount"] < 0)
                $this->ajaxReturn(array("echo" => 1, "info" => "你输入的实还金额有误!"));
                
            $state = sqlCol("select state from repayment_record where id=".$_POST["id"]);
            if ($state == 0)
                $this->ajaxReturn(array("echo" => 1, "info" => "此条数据已经其它分组财务完成操作!"));
            
            //如果是POS账户，则判断金额是否正确
            if ($_POST["account_type"] == 0)
            {
                if ($_POST["put_card"] == 1)
                {
//                 2. 划备付金
//                     划多了   不允许
//                     正常划/划少了   则把差值写到POS余额字段
                    if ($_POST["fact_amount"] > $_POST["put_amount"])
                        $this->ajaxReturn(array("echo" => 1, "info" => "POS余额不足!"));
                    $_POST["pos_amount"] = $_POST["put_amount"] - $_POST["fact_amount"];
                }
                else
                {
//                 1. 划信用卡
//                     划多了    应划金额加POS余额为此时POS的余额，实划不得大于此数
//                     正常划/划少了   应划金额和实划金额的差值加上POS的余额为此时POS的新余额
                    if ($_POST["fact_amount"] > $_POST["put_amount"] + $_POST["pos_amount"])
                        $this->ajaxReturn(array("echo" => 1, "info" => "POS余额不足!"));
                    
                    $_POST["pos_amount"] += $_POST["put_amount"] - $_POST["fact_amount"];
                }
            }
            
            $return["echo"] = 1;
            $return["close"] = 1;
            $return["info"] = "提交成功!";
            $return["url"] = "";
            $return["tag"] = "";
            $end_id = sqlCol("select max(id) from repayment_record where rtype=1 and sid=".$this->sid." and 
                        DATE_FORMAT(create_time,'%Y-%m-%d')='".date("Y-m-d")."");
            if ($_POST["id"] != $end_id)
                $return["callback"] = 'repayment_removeTr('.$_POST["id"].')';
            else
            {
                $return["url"] = U("Finance/repayment");
                $return["tag"] = "#body";
            }
            
            $obj = M("repayment_record");
            $ret = $obj->where("id=".$_POST["id"])->save(array("state" => "0", 
                    "fact_amount" => $_POST["fact_amount"], "repay_time" => getcurtime()));
            
            if ($_POST["account_type"] == 0)
                M("pos")->where("id=".$_POST["pos_id"])->save(array("amount" => $_POST["pos_amount"]));
                
            // echo $obj->getLastSql();
            if (!$ret)
            {
                $return["close"] = 0;
                $return["info"] = "提交失败!";
                $return["callback"] = "";
            }
            
            $this->ajaxReturn($return);
            exit(0);
        }
        
        $form = new Form("", 
                array("cols" => 2,"kajax" => "true","callback" => 'repayment_verify'));
        
        $value = sqlRow("select * from repayment_record where id=".I("get.id"));
        if (!$value)
        {
            echo '<h1 style="color:red;font-weight:bold;">非法操作,请重新登录!</h1>';
            exit(0);
        }
//         $bool = sqlCol("select id from repayment_record where rtype=1 and out_card='".$value["out_card"]."' and 
//                 put_card='".$value["put_card"]."' and DATE_FORMAT(repay_time,'%Y-%m-%d')='".date("Y-m-d")."'");
        if ($value["state"] == 0)
        {
            echo '<h1 style="color:red;font-weight:bold;">此条数据已经其它分组财务完成操作!</h1>';
            exit(0);
        }
        
        //获取此时POS机余额
        if ($value["pos_id"])
            $pos_amount = sqlCol("select amount from pos where id=".$value["pos_id"]);
        
        $form->setElement("type_name", "string", "账户类型", 
                array("value" => $this->repay_type_name[$value["account_type"]],"bool" => "readonly"));
        $form->setElement("put_code", "string", "内部代码", 
                array("value" => $value["put_code"],"bool" => "readonly"));
        $form->setElement("out_name", "string", "划款户名", 
                array("value" => $value["out_name"],"bool" => "readonly"));
        $form->setElement("put_name", "string", "收款户名", 
                array("value" => $value["put_name"],"bool" => "readonly"));
        $form->setElement("out_bank", "string", "划款银行", 
                array("value" => $value["out_bank"],"bool" => "readonly"));
        $form->setElement("put_bank", "string", "收款银行", 
                array("value" => $value["put_bank"],"bool" => "readonly"));
        $form->setElement("out_card", "string", "划款账号", 
                array("value" => $value["out_card"], "maxlength" => 35, "bool" => "readonly"));
        $put_card = $value["put_card"];
        if ($value["put_card"] == 1)
        {
//             if ($pos_amount < $value["put_amount"])
            $value["put_amount"] = $pos_amount;
            $put_card = "备付金账户";
        }
        $form->setElement("put_card_dis", "string", "收款账号", 
                array("value" => $put_card, "maxlength" => 35, "bool" => "readonly"));
        
        
        $form->setElement("put_amount", "string", "应划金额", array("value" => sprintf("%.2f", $value["put_amount"]),
                "bool" => "readonly required", "addon" => "元"));
        $form->setElement("fact_amount", "num", "实划金额", 
                array("value" => $value["put_amount"], "addon" => "元","bool" => "required"));
        
        //显示POS此时余额 POS账户下
        if ($value["account_type"] == 0)
            $form->setElement("pos_amount", "num", "POS余额", array("value" => $pos_amount,"addon" => "元", "bool" => "readonly"));
        
        $form->setElement("pos_id", "hidden", "", array("value" => $value["pos_id"]));
        $form->setElement("account_type", "hidden", "", array("value" => $value["account_type"]));
        $form->setElement("id", "hidden", "", array("value" => $value["id"]));
        $form->setElement("put_card", "hidden", "", array("value" => $value["put_card"]));
        
        $form->set("btn 0 txt", "提交");
        
        echo $form->fetch();
    }

    
    public function getDisList($excel = "")
    {
        $opdate = date("Y-m-d");
        foreach ($this->repaylist as $key => $row)
        {
            if (!$this->repaylist[$key]["op_date"])
                $this->repaylist[$key]["op_date"] = $opdate;
            if ($excel == "" && isset($this->repaylist[$key]["remaining_period"]) && 
                            $this->repaylist[$key]["remaining_period"] <= 2)
                $this->repaylist[$key]["remaining_period"] = '<span class="kyo_red">'.
                        $this->repaylist[$key]["remaining_period"].'</span>';
                
        }
//         dump($this->repaylist);
        return $this->repaylist;
    }
    
    public function exportExcel()
    {
        $title = array("op_date" => "推送日期", "account_type" => "账户类型","out_name" => "划款户名",
                    "out_bank" => "划款银行","out_card" => "划款账号","put_name" => "收款户名",
                    "put_bank" => "收款银行","put_card" => "收款账号","remaining_period" => "可还款天", "put_amount" => "收款金额");
         
        $mail = new \Common\Controller\KyoMail();
        $str = $mail->export("当日还款报表", $title, "select CURDATE() as op_date, r.* from repayment_record r where 
                    r.rtype=1 and r.state=1 and r.sid=".$this->sid." and DATE_FORMAT(r.create_time,'%Y-%m-%d')='".date("Y-m-d")."' order by r.id");
//         exit(0);
        $this->ajaxReturn(array("echo" => 1, "info" => $str));
    }
    
    //还款列表
    public function repayment()
    {
        // 清空核心表数据和执行核心存储过程
        // dump(sqlAll("truncate table core_deductive"));
        // dump(sqlAll("truncate table core_repayment"));
        // dump(sqlAll("truncate table core_disburse"));
        // dump(sqlAll("call pro_disburse"));
        // dump(sqlAll("call pro_deductive"));
        // dump(sqlAll("call pro_repayment"));
        // dump(sqlAll("call pro_disburse"));
        
        // truncate table core_deductive;
        // truncate table core_repayment;
        // truncate table core_disburse;
        // call pro_deductive; call pro_repayment; call pro_disburse;
        $this->parseRepayList();
//         dump($this->repaylist);
//         exit(0);
        
        $this->setNav('&nbsp;->&nbsp;财务管理&nbsp;->&nbsp;当日还款列表');
        $this->mainPage("repayment_record");
        $this->setTool("find_row_class", "hidden");
        $this->setTool("close_tool_find", 1);
        $this->setTool("tool_link_title", "");
        
        $this->setBtn("还款记录", U("Finance/repayment_histroy"), 
                array("pop" => $this->getPop("histroy"),"icon" => "record"));
        
//         $this->setBtn("导出报表", U("KyoCommon/Index/excelExport"), 
//                 array("front" => "&emsp;&nbsp;&nbsp;", "end" => "&emsp;","icon" => "cloud-download", 
//                 "ext" => 'callback=\'$(this).prop("disabled", true)\';'));
        
        $this->setBtn("导出报表", U("Finance/exportExcel"), 
                array("front" => "&emsp;&nbsp;&nbsp;", "end" => "&emsp;","icon" => "cloud-download", 
                "ext" => 'callback=\'$(this).prop("disabled", true)\';'));
        
        $this->setLink("btn_verify", 
                '开始还款&emsp;<span class="badge fin_repay_num">'.count($this->repaylist).'</span>', "#", 
                array("ext" => 'id="btn_repay_verify" startVal="'.$this->repaylist[0]["id"].'"',
                        "bool" => "me", "class" => "btn btn-primary","end" => "&nbsp;","icon" => "tower"));
        
        $title = array("op_date" => "推送日期", "account_type" => "账户类型","out_name" => "划款户名",
                    "out_bank" => "划款银行","out_card" => "划款账号","put_name" => "收款户名",
                    "put_bank" => "收款银行","put_card" => "收款账号","remaining_period" => "可还款天", "put_amount" => "收款金额");
        
        $this->setData("close_chkall", 1);
        $this->setData("data_list", $this->getDisList());
        $this->setOp("确认还款", U("Finance/repayment_verify") . "&id=[id]", array(
                "class" => "btn btn-danger btn-xs hidden","ext" => "id=verBtn[id]",
                "pop" => $this->getPop("repay")));
        $this->setData("empty_html", 
                '<td class="empty_data_html" colspan="100">今日无还款列表或还款工作已经完成， 可以点击&nbsp;
                 <a href="#" url="'.U("Finance/repayment_histroy").'" pop="{'.$this->getPop("histroy").'}">
                                    当日已还列表</a>&nbsp;查看!</td>');
        $this->setTitle($title);
        $this->setField(array_keys($title));
//         $this->setData("excel", array("name" => "当日还款列表", "title" => $title, "data" => $this->getDisList("excel")));
        $this->setData("subtotal field", array(9));
        $this->setData("data_field 9 class", "text-right");
        $this->set("main_ext", 
                '<script type="text/javascript" src="' . __ROOT__ .
                         '/Public/js/kyo_repayment.js"></script>');
        $this->display();
    }
    
    //当日已还列表
    public function repayment_histroy()
    {
        $this->mainPage("repayment_record");
        $this->set("close_nav", 1);
        $this->set("close_tool", 1);
        $this->set("close_top_ctrl", 1);
        $this->set("close_body_top", 1);
        
        $this->setData("close_op", 1);
        $this->setData("close_chkall", 1);
        $this->setCustomList("Payment_system", true, array(2,$this->sid));
        $this->setTitle(
                array("账号类型","划款户名","划款银行","划款账号","收款户名","收款银行","收款账号","应还金额","实还金额","还款时间"));
        $this->setField(
                array("account_type","out_name","out_bank","out_card","put_name","put_bank","put_card","put_amount","fact_amount","repay_time"));
        $this->setData("data_field 7 class", "text-right");
        $this->setData("data_field 8 class", "text-right");
        $this->display();
    }
    
    
    //划款历史客户增值详细信息弹出框
//     public function histroyRisingInfo($code, $rid, $rmb)
//     {
//         $rf = new Form("");
//         $rf->set("close_btn_down", 1);
        
//         $card = sqlRow("select b.name,c.card, c.rising_amount_num, c.rising_num, 
//                         (c.amount + c.rising_amount_num) as amount, r.amount as cur_rising_amount, 
//                         r.rising_date from basis b, card c, rising r 
//                         where b.id=c.bid and r.id=".$rid." and c.code='".$code."'");
// //         dump(M()->getLastSql());
        
//         $rf->setElement("name", "static", "客户姓名", array("label_cols" => 4, "value" => $card["name"]));
//         $rf->setElement("card", "static", "增值卡号", array("label_cols" => 4, "value" => format_dis_field($card["card"])));
//         $rf->setElement("amount", "static", "现总额度", array("label_cols" => 4, "value" => $card["amount"]." 元"));
//         $rf->setElement("rising_amount", "static", "增值额度", array("label_cols" => 4, "value" => $card["rising_amount_num"]." 元"));
//         $rf->setElement("rising_amount_num", "static", "增值次数", array("label_cols" => 4, "value" => $card["rising_num"]." 次"));
//         $rf->setElement("cur_rising_amount", "static", "本次增值额度", array("label_cols" => 4, "value" => $card["cur_rising_amount"]." 元"));
//         $rf->setElement("cur_rmb", "static", "本次实划金额", array("label_cols" => 4, "value" => $rmb." 元"));
//         $rf->setElement("rising_date", "static", "本次增值时间", array("label_cols" => 4, "value" => $card["rising_date"]));
        
//         echo $rf->fetch();
//     }
    
    
//     //划账历史字段处理
//     public function histroyField($data, $txt)
//     {
// //         $data["out_card"] = format_dis_field($data["out_card"]);
//         $data["out_card"] = substr($data["out_card"], -4);
//         if ($data["put_card"] == 1)
//             $data["put_card"] = "备付金账户";
//         else
//             $data["put_card"] = format_dis_field($data["put_card"]);
        
//         $rtype = C("REPAYTYPE_TEXT");
//         $data["account_type"] = $this->repay_type_name[$data["account_type"]];
        
//         if ($data["rtype"] >= 3)
//         {
//             $rp = new FormElement("link_pop", "link", $data["put_amount"], array(
//                        "url" => U("Public/statementBonus")."&histroy=1&date_id=".$data["out_name"], 
//                     "pop" => $this->getPop("bonus", $data["put_name"]." ".$rtype[$data["rtype"]]."明细")));
//             if ($data["rtype"] == 5)
//                 $rp->_set("url", U("statementBonus")."&histroy=1".$data["put_code"]);
//             $data["put_amount"] = $rp->fetch();
//             $data["out_name"] = "";
//             $data["put_code"] = "";
//         }
//         else if ($data["rtype"] == 2)
//         {
//             $rp = new FormElement("link_pop", "link", $data["put_amount"], array(
//                        "url" => U("histroyRisingInfo")."&code=".$data["put_code"].
//                                     "&rid=".$data["out_name"]."&rmb=".$data["put_amount"], 
//                     "pop" => $this->getPop("risingInfo")));
//             $data["put_amount"] = $rp->fetch();
//             $data["put_code"] = "";
//             $data["out_name"] = "";
//         }
//         $data["rtype"] = $rtype[$data["rtype"]];
        
//         return $data["account_type"];
//     }

// //     //划账历史
//     public function histroy()
//     {
//         $this->setNav("&nbsp;->&nbsp;账务管理&nbsp;->&nbsp;划账历史查询");
//         $this->mainPage("repayment_record");
//         $rtype = C("REPAYTYPE_TEXT");
//         $sdate = date("Y-m-d");
//         $edate = date("Y-m-d", strtotime("+1 day", strtotime($sdate)));
//         $this->setFind("item repay_time", array("name" => "repay_time", "type" => "date", 
//                         "sval" => $sdate, "eval" => $edate));
//         $this->setFind("item 1", array("name" => "rtype", "type" => "select", "default" => "所有类型",
//                 "list" => parse_select_list("array", array_keys($rtype), $rtype)));
//         $this->setFind("item 2", array("name" => "search_type", "type" => "select", 
//                 "default" => "划款卡号", "defval" => "out_card", 
//                 "list" => parse_select_list("array", array("out_name", "put_name", "put_card"), 
//                         array("划款户名", "收款户名", "收款卡号"))));
        
//         $this->setData("close_op", 1);
//         $this->setData("where", "sid=".$this->sid);
//         $this->setData("order", "repay_time desc");
//         $this->setData("close_chkall", 1);
//         $this->setTitle(array("账号类型","划款户名","划款银行","划款账号","划账类型", "收款户名","收款银行","收款账号","应划金额","实划金额","还款时间"));
//         $this->setField(array("aa","out_name","out_bank","out_card","rtype", "put_name","put_bank","put_card","put_amount","fact_amount","repay_time"));
//         $this->setData("data_field 0 run", "Finance/histroyField");
//         $this->setData("data_field 8 class", "text-right");
//         $this->setData("data_field 9 class", "text-right");
//         $this->display();
//     }
    
    //佣金结算列表弹出连接处理
    public function bonusLink($data, $txt)    
    {
        $type_id = $this->rtypeTxt[$data["state"]];
        $lnk = new FormElement("amount_link", "link", $data["card_total_amount"], array(
                    "url" => U("Public/statementBonus")."&date_id=".$data["date_id"]."&typeid=".$type_id,
                "pop" => $this->getPop("bonus", $data["username"]." ".$data["state"]."明细")));
        
        if ($type_id == 5)
            $lnk->_set("url", U("Public/statementBonus")."&uid=".$data["uid"]."&dates=".$data["dates"]."&typeid=".$type_id);
        
        return $lnk->fetch();
    }
    
    //佣金结算
    public function statement()
    {
        if (I("get.where"))
        {
            $curtime = getcurtime();
            $list = session("temp_data");
            $ids = explode("(", I("get.where"));
            $ids = explode(")", $ids[1]);
            $ids = explode(",", $ids[0]);
//             dump($list);
//             dump(I("get.where"));
//             exit(0);
            foreach ($ids as $id) 
            {
                //往还款记录表中写数据
                $record = $list[$id - 1];
                $state = $this->rtypeTxt[$record["state"]];
                
                $data = array();
                $data["rtype"] = $state;
                $data["account_type"] = 1;
                $data["repay_time"] = $curtime;
                $data["sid"] = $this->sid;
                $data["opid"] = $this->uid;
                $data["out_name"] = $record["date_id"];
                $data["put_code"] = "&uid=".$record["uid"]."&dates=".$record["dates"];
                $data["put_name"] = $record["username"];
                $data["put_bank"] = $record["bank"];
                $data["put_card"] = $record["card"];
                $data["put_amount"] = $record["bonus_rmb"];
                $data["fact_amount"] = $record["bonus_rmb"];
//                 dump($data);
//                 dump(M("rising")->where("id=".$record["rid"])->find());
//                 exit(0);
                M("repayment_record")->add($data);
                if ($state != 5)
                {
                    //佣金结算状态修改 
                    M("expand_bonus")->where("date_id='".$record["date_id"]."'")->save(array("state" => NORMAL, 
                                                "update_time" => $curtime));
                }
            }
            session("temp_data", null);
//             $this->ajaxReturn(array("echo" => 1, "info" => "结算成功，请到划账历史中查询!"));
//             exit(0); 
        }        
        
   	    $this->setNav("&nbsp;->&nbsp;财务管理&nbsp;->&nbsp;佣金结算");
    	$this->mainPage("bonus");
        $this->setTool("close_tool_find", 1);
        
        $this->setTool("tool_btn_down", array("txt" => "导出报表", "icon" => "cloud-download",
                "url" => U("KyoCommon/Index/excelExport"),  
                "ext" => 'callback=\'$(this).prop("disabled", true)\';'));
        
        $this->setBatch("确认划账", U(), array('icon' => "tower", "tag" => "#body", 
                "ext" => 'confirm="请仔细核对划账金额，确认无误请继续!"'));
            
        $this->setData("close_op", 1);
    	$this->setCustomList("pro_expand_bonus",false, array(1, $this->sid, "null"));
//         dump(M()->getLastSql());
        session('temp_data', $this->getData("data_list"));
        
        $title = array("username" => "拓展员姓名", "card_num" => "卡片数量", 
                    "card_total_amount" => "结算额度", "state" => "佣金类型", "expand_cost" => "佣金扣率", 
                    "bonus_rmb" => "佣金金额", "bank" => "结算银行", "card" => "结算账号", "dates" => "应划账日");
        $_SESSION["excel"]["filename"] = "佣金结算报表";
        $_SESSION["excel"]["title"] = $title;
        $_SESSION["excel"]["data"] = $this->getData("data_list");
        
    	$this->setTitle($title);
    	$this->setField(array_keys($title));
        $this->setData("data_field 2 run", "Finance/bonusLink");
        
    	//子表
    	   //客户姓名 发卡行 卡号  额度 签约费率 签约期数 佣金比率 佣金金额
           
    	//增值
    	   //客户姓名 发卡行 卡号  增值费率 增值额度 佣金比率 佣金金额
    	
    	$this->display();        
    }
    
}
