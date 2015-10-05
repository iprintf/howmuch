<?php
namespace Install\Controller;
use Think\Controller;
use Common\Controller\Form;
use Common\Controller\FormElement;

//自动生成数据脚本
// 基础数据生成
//   超级管理员   主站管理员  超级助理

// 代理       个数  创建时间
// 分站       个数  创建时间
// 拓展员    个数  创建时间
// 操作员    个数 
// 财务       个数
// pos   个数
// 客户       个数
// 卡片
// 还款记录
// 交易数据
  
class AutoBuildController extends Controller 
{
    private $r = ""; //随机类的对象
    
    public function __construct()
    {
        parent::__construct();
        if (!is_login())
            $this->redirect("Home/Index/index");
        $this->r = new AutoRand();
    }
    
    public function index()
    {
        $perm = new Form("", array("cols" => 2, "me" => "me", "action" => U("AutoBuild/rand_handle")));
        $perm->set("kajax", false);
        
        $perm->setElement("base_group", "group", "数据随机");
        $perm->setElement("proxy", "num", "代理随机个数", array("value" => 2));
        $perm->setElement("sub", "num", "分站随机个数", array("value" => 3));
        $perm->setElement("pos", "num", "POS机随机个数", array("value" => 10));
        $perm->setElement("sales", "num", "拓展员随机个数", array("value" => 5));
        $perm->setElement("fin", "num", "财务随机个数", array("value" => 5));
        $perm->setElement("oper", "num", "操作员随机个数", array("value" => 5));
        $perm->setElement("basis", "num", "客户随机个数", array("value" => 10));
        $perm->setElement("card", "num", "卡片随机个数", array("value" => 10));
        $perm->setElement("deal", "num", "交易数据随机个数", array("value" => 10));
        $perm->setElement("repay", "num", "还款记录随机个数", array("value" => 10));
        $perm->setElement("start_date", "date", "客户创建起始日期", array("value" => date("Y-m-d", strtotime("-3 day"))));
        $perm->setElement("end_date", "date", "客户创建结束日期", array("value" => date("Y-m-d")));
        
        $perm->set("btn 0 txt", "生成");
        
        $this->assign("body", $perm->fetch());
        $this->display(T("Install@Index/index"));     
    }
    
    public function getID($table, $field, $where)
    {
        srand(uniqid());            
        $obj = M($table);
        $usr = $obj->field($field)->where($where)->select();
        if (!$usr)
            return false;
        while (1)
        {
            for ($i = 0; $i < 5; $i++)
            {
                $index = mt_rand(0, count($usr) - 1);
                usleep(1);
            }
            if ($usr[$index])
                return $usr[$index];
        }
    }
    
    
    public function build_basis($id = "")
    {
        if ($id)
            $data["id"] = $id;
        $usr = "";
        $u = 0;
        while (1)
        {
            $usr = $this->getID("users", "id,proxy_id,sid", "type=3");
            $data["proxy_id"] = $usr["proxy_id"];
            $data["sid"] = $usr["sid"];
            $data["eid"] = $usr["id"];
            $usr = $this->getID("users", "id", "sid=".$data["sid"]." and type=1");
            $data["typing"] = $usr["id"];
            if ($usr)
                break;
            if ($u++ == 11)
                return false;
        }
        
        $data["status"] = 0;
        $data["create_time"] = $this->r->date($_POST["start_date"], $_POST["end_date"]);
        $data["times"] = $data["create_time"];
        $data["update_time"] = $data["create_time"];
        
        $data["card_num"] = 0;
        $data["amount_num"] = 0;
        
        $data["code"] = build_code(5, $data["sid"]);
        $data["name"] = $this->r->uname();
        $data["sex"] = mt_rand(1, 2);
        $data["identity"] = $this->r->identity();
        $data["addr"] = $this->r->addr();
        $data["phone1"] = $this->r->phone();
        $data["phone2"] = $this->r->phone();
        $data["card_name"] = $this->r->uname();
        $data["bank"] = $this->r->bank();
        $data["card"] = $this->r->card();
        $data["contact"] = $this->r->uname();
        $data["phones"] = $this->r->phone();
        parse_umax("customer", $data["sid"]);
        
        M("users")->where("id=".$usr["id"])->setInc("basis_num", 1);
        
        return M("basis")->add($data);
    }
    
    public function build_pos()
    {
        $sub = $this->getID("users", "id,proxy_id", "type=6");
        $pro = $this->getID("users", "id", "type=8");
        $pay = array("富友", "钱宝", "支付宝", "快钱", "易宝支付", "财付通", "宝付", "银联在线", "通联支付", "讯付");
        $pay_id = array_rand($pay);
        $cost = array(0.38, 0.78, 1.25);
        
        $data["code"] = build_pos_code($pay_id, $sub["id"], $sub["proxy_id"]);
        parse_umax("pos", $sub["proxy_id"]);
        $data["status"] = 0;
        $data["pay"] = $pay[$pay_id];
        $data["proxy"] = $sub["proxy_id"];
        $data["supplier"] = $pro["id"];
        $data["sid"] = $sub["id"];
        $data["shop_id"] = $this->r->number(15);
        $data["models"] = mt_rand(1000000, 999999);
        $data["pwd"] = mt_rand(100000, 999999);
        $data["mcc"] = mt_rand(1000, 9999);
        $data["pos_name"] = $this->r->txt(mt_rand(3, 20));
        $data["cost"] = $cost[mt_rand(0, 2)] / 100;
        $data["month_max"] = mt_rand(2000, 100000);
        $data["day_max"] = $data["month_max"] / 30;
        $data["bank"] = $this->r->bank();
        $data["card"] = $this->r->card(2);
        $data["card_name"] = $this->r->uname();
        $data["amount"] = 0;
        $data["create_time"] = getcurtime();
        $data["update_time"] = $data["create_time"];
        M("pos")->add($data);
        
        $l = mt_rand(1, 4);
        $code = $data["code"];
        $data = array();
        $time = array(
                "shour" => array(8, 12, 17, 20),
                "ehour" => array(12, 17, 20, 23),
        );
        for ($i = 0; $i < $l; $i++)
        {
            $data["pcode"] = $code;
            $st = mt_rand($time["shour"][$i], $time["ehour"][$i]);
            $data["start_time"] = $st.":".mt_rand(0, 60);
            $data["end_time"] = mt_rand($st, $time["ehour"][$i]);
            $data["start_money"] = mt_rand(10, 2000);
            $data["end_money"] =mt_rand($data["start_money"], 5000);
            M("pos_time")->add($data);
        }
    }
    
    public function build_card()
    {
        $basis = $this->getID("basis", "id,sid,proxy_id,eid,code,card_num,times", "");
        $oper = $this->getID("users", "id", "type=2");
        $typing = $this->getID("users", "id", "type=1");
        
        $data["sid"] = $basis["sid"];
        $data["proxy_id"] = $basis["proxy_id"];
        $data["eid"] = $basis["eid"];
        $data["bid"] = $basis["id"];
        $data["opid"] = $oper["id"];
        $data["status"] = 0;
        $data["code"] = $basis["code"].dechex($basis["card_num"] + 1);
        $data["card"] = $this->r->card();
        $data["bank"] = $this->r->bank();
        $data["amount"] = mt_rand(3000, 100000);
        $data["card_type"] = $this->r->txt(mt_rand(3, 6));
        $data["effective_date"] = mt_rand(2015, 2020)."/".mt_rand(1, 12)."/1";
        $data["query_pwd"] = mt_rand(100000, 999999);
        $data["pay_pwd"] = mt_rand(100000, 999999);
        $data["cvv2"] = mt_rand(1000000, 9999999);
        $data["email"] = mt_rand("a", "z").mt_rand(100, 999)."@163.com";
        $data["bill"] = mt_rand(1, 5);
        $data["repayment"] = mt_rand(2, 3);
        $data["finally_repayment_date"] = mt_rand(19, 22);
        $data["installment"] = mt_rand(7, 13);
        $data["agreement"] = mt_rand(3, 12);
        $data["counts"] = mt_rand(22, 120);
        $data["cost"] = mt_rand(12, 20) / 1000;
        $data["costing_per"] = mt_rand(800, $data["cost"] * 0.85 * 1000 * 100) / 100000;
        $data["costing"] = floor($data["amount"] * $data["costing_per"]);
        $data["rising_cost"] = mt_rand(3, 15) / 100;
        $data["rising_num"] = 0;
        $data["rising_amount_num"] = 0;
        $data["times"] = $basis["times"];
        $data["due_date"] = date("Y-m-d", strtotime("+".$data["agreement"]." month", strtotime($basis["times"])));
        $data["typing"] = $typing["id"];
        $data["relate"] = "alipay=".mt_rand(1, 2)."|wxpay=".mt_rand(1, 2).
               "|temp_amount=".mt_rand(1, 2)."|tenpay=".mt_rand(1, 2).
               "|insure=".mt_rand(1, 2)."|autopay=".mt_rand(1, 2).
               "|quick_pay=".mt_rand(1, 2)."|auto_aging=".mt_rand(1, 2);
        $data["create_time"] = $basis["times"];
        $data["update_time"] = $data["create_time"];
        
        $obj = M("basis");
        $obj->where("id=".$basis["id"])->setInc("card_num", 1);
        $obj->where("id=".$basis["id"])->setInc("amount_num", $card["amount"]);
        $obj = M("users");
        $obj->where("id=".$oper["id"])->setInc("card_num", 1);
        $obj->where("id=".$basis["eid"])->setInc("card_num", 1);
        $obj = M("card"); 
        $obj->add($data);
        
        update_card_installment("", $data, true);
    }
    
    public function build_base()
    {
        $obj = M("users");
        $data["id"] = 1;
        $data["type"] = 0;
        $data["username"] = "超级管理员";
        $data["code"] = build_code(0);
        $data["login_name"] = "master";
        $data["pwd"] = think_md5("123", UC_AUTH_KEY);
        $data["create_time"] = getcurtime();
        $data["update_time"] = $data["create_time"];
        $obj->add($data);
        
        $data["id"] = 2;
        $data["type"] = 9;
        $data["username"] = "主站管理员";
        $data["code"] = build_code(9);
        $data["login_name"] = "main";
        $data["umax"] = "proxy:0,assist:0";
        $obj->add($data);
        
        $data["id"] = 3;
        $data["type"] = 7;
        $data["username"] = "超级助理";
        $data["code"] = build_code(7);
        $data["login_name"] = "assist";
        $data["umax"] = "";
        parse_umax("assist", 2);
        $obj->add($data);
    }
    
    public function build_proxy()
    {
        $data["proxy_sub_name"] = $this->r->proxy();
        $data["type"] = 8;
        $data["status"] = 0;
        $data["code"] = build_code(8);
        $data["username"] = $this->r->uname();
        $data["sex"] = mt_rand(1, 2);
        $data["identity"] = $this->r->identity();
        $data["phone1"] = $this->r->phone();
        $ret = parse_umax("proxy", 2);
        $data["input"] = $ret["old_umax"];
        $data["umax"] = "pos:0,sub:0";
        $data["pwd"] = think_md5("123", UC_AUTH_KEY);
        $data["login_name"] = "pro".$ret["old_umax"];
        $data["create_time"] = getcurtime();
        $data["update_time"] = $data["create_time"];
        return M("users")->add($data);
    }
    
    public function build_sub()
    {
        $pro = $this->getID("users", "id", "type=8");
        $data["proxy_sub_name"] = $this->r->sub();
        $data["proxy_id"] = $pro["id"];
        $data["type"] = 6;
        $data["status"] = 0;
        $data["code"] = build_code(6, $pro["id"]);
        $data["username"] = $this->r->uname();
        $data["sex"] = mt_rand(1, 2);
        $data["identity"] = $this->r->identity();
        $data["phone1"] = $this->r->phone();
        $ret = parse_umax("sub", $pro["id"]);
        $data["input"] = $ret["old_umax"];
        $data["umax"] = "finance:0,operator:0,customer:0,salesman:0,employee:0";
        $data["pwd"] = think_md5("123", UC_AUTH_KEY);
        $data["login_name"] = "sub".$ret["old_umax"];
        $data["create_time"] = getcurtime();
        $data["update_time"] = $data["create_time"];
        return M("users")->add($data);
    }
    
    public function build_user($type)
    {
        $typename = array(1 => "finance", 2 => "operator");
        $sub = $this->getID("users", "id,proxy_id,proxy_sub_name", "type=6");
        $data["proxy_sub_name"] = $sub["proxy_sub_name"];
        $data["proxy_id"] = $sub["proxy_id"];
        $data["sid"] = $sub["id"];
        $data["type"] = $type;
        $data["status"] = 0;
        $data["code"] = build_code($type, $sub["id"]);
        $data["username"] = $this->r->uname();
        $data["sex"] = mt_rand(1, 2);
        $data["identity"] = $this->r->identity();
        $data["phone1"] = $this->r->phone();
        $ret = parse_umax($typename[$type], $sub["id"]);
        $data["pwd"] = think_md5("123", UC_AUTH_KEY);
        $data["login_name"] = $typename[$type].mt_rand(10,99);
        $data["create_time"] = getcurtime();
        $data["update_time"] = $data["create_time"];
        return M("users")->add($data);
    }
    
    public function build_salesman()
    {
        $sub = $this->getID("users", "id,proxy_id,proxy_sub_name", "type=6");
        $data["proxy_sub_name"] = $sub["proxy_sub_name"];
        $data["proxy_id"] = $sub["proxy_id"];
        $data["sid"] = $sub["id"];
        $data["type"] = 3;
        $data["status"] = 0;
        $data["code"] = build_code(3, $sub["id"]);
        $data["username"] = $this->r->uname();
        $data["sex"] = mt_rand(1, 2);
        $data["identity"] = $this->r->identity();
        $data["phone1"] = $this->r->phone();
        $ret = parse_umax("salesman", $sub["id"]);
        $data["input"] = mt_rand(0, 1);
        $data["card_name"] = $data["username"];
        $data["bank"] = $this->r->bank();
        $data["card"] = $this->r->card(2);
        
        $data["bonus"] = mt_rand(3, 8) / 10000;
        $data["signing"] = mt_rand(3, 10) / 100; 
        $list = parse_select_list("for", array(1.0, 5.0, 0.5));
        $data["award"] = $list[mt_rand(0, count($list) - 1)]["val"] / 100;
        $data["award_min"] = mt_rand(2, 10) / 100;
        
        $data["pwd"] = think_md5("123", UC_AUTH_KEY);
        $data["login_name"] = "sales".mt_rand(10, 99);
        $data["create_time"] = getcurtime();
        $data["update_time"] = $data["create_time"];
        return M("users")->add($data);
    }
    
    public function build_transaction()
    {
        $c = 0;
        $p = 0;
        $pos = "";
        $card = "";
        while (1)
        {
            $pos = $this->getID("pos", "id,pos_name,models,sid", "");
            if (!$pos)
            {
                if (++$p == 5)
                    break;
                continue;
            }
            $p = 0;
            $card = $this->getID("card", "card,bank", "sid=".$pos["sid"]);
            if ($card || $c == 10)
                break;
            $c++;
        }
        
        $data["pname"] = $pos["pos_name"];
        $data["pid"] = $pos["id"];
        $data["terminal"] = $pos["models"];
        $data["rmb"] = mt_rand(100, 50000);
        $data["date"] = $this->r->date("2014-6-1 00:00:00", getcurtime(), true);
        $data["bank"] = $card["bank"];
        $data["card"] = $card["card"];
        $data["state"] = "成功";
        $data["dates"] = "2014-1-1 10:10:10";
        
        M("transaction")->add($data);
    }
    
    public function build_repayment_record()
    {
        $c = 0;
        $p = 0;
        while (1)
        {
            $pos = $this->getID("pos", "id,pos_name,models,sid,card_name,card,bank", "");
            if (!$pos)
            {
                if (++$p == 5)
                    break;
                continue;
            }
            $p = 0;
            $card = $this->getID("card", "bid,code,card,bank", "sid=".$pos["sid"]);
            if ($card || $c == 10)
                break;
            $c++;
        }
        
        $data["account_type"] = "0";
        $data["out_name"] = $pos["card_name"];
        $data["out_card"] = $pos["card"];
        $data["out_bank"] = $pos["bank"];
        $data["put_code"] = $card["code"];
        $basis_name = M("basis")->field("name")->where("id=".$card["bid"])->select();
        $data["put_name"] = $basis_name[0]["name"];
        $data["put_bank"] = $card["bank"];
        $data["put_card"] = $card["card"];
        $amount = mt_rand(100, 10000);
        $data["put_amount"] = $amount;
        $data["fact_amount"] = $amount;
        $data["repay_time"] = $this->r->date("2014-6-1", date("Y-m-d"));
        $data["sid"] = $pos["sid"];
        $data["opid"] = $pos["sid"];
        
        $obj = M("repayment_record");
        $obj->add($data);
//         dump($obj->getLastSql());
//         exit(0);
    }
    
    public function clear_old_data()
    {
        $db = M("pos_time");
        $db->execute("truncate table transaction");
        $db->execute("truncate table repayment_record");
        $db->execute("truncate table basis");
        $db->execute("truncate table users");
        $db->execute("truncate table card");
        $db->execute("truncate table basis_op");
        $db->execute("truncate table card_op");
        $db->execute("truncate table card_installment");
        $pos = sqlAll("select code from pos");
        foreach ($pos as $row)
        {
            $db->where("pcode='".$row["code"]."'")->delete();
        }
        $db->execute("truncate table pos");
    }
    
    public function rand_handle()
    {
        if (IS_POST)
        {
            header("Content-type: text/html; charset=utf-8");
            $this->clear_old_data();
            dump("清空原有数据成功!");
            
            $this->build_base();
            dump("基础数据生成成功!");
            for ($i = 0; $i < $_POST["proxy"]; $i++)
            {
                $this->build_proxy();
            }
            dump($_POST["proxy"]."个代理生成成功!");
            
            for ($i = 0; $i < $_POST["sub"]; $i++)
            {
                $this->build_sub();
            }
            dump($_POST["sub"]."个分站生成成功!");
            
            for ($i = 0; $i < $_POST["fin"]; $i++)
            {
                $this->build_user(1);
            }
            dump($_POST["fin"]."个财务生成成功!");
            
            for ($i = 0; $i < $_POST["oper"]; $i++)
            {
                $this->build_user(2);
            }
            dump($_POST["oper"]."个操作员生成成功!");
            
            for ($i = 0; $i < $_POST["sales"]; $i++)
            {
                $this->build_salesman();
            }
            dump($_POST["sales"]."个拓展员生成成功!");
            
            for ($i = 0; $i < $_POST["pos"]; $i++)
            {
                $this->build_pos();
            }
            dump($_POST["pos"]."个pos机生成成功!");
            
            for ($i = 0; $i < $_POST["basis"]; $i++)
            {
                $this->build_basis();
            }
            dump($_POST["basis"]."个客户机生成成功!");
            
            for ($i = 0; $i < $_POST["card"]; $i++)
            {
                $this->build_card();
            }
            dump($_POST["card"]."个信用卡生成成功!");
            
            for ($i = 0; $i < $_POST["deal"]; $i++)
            {
                $this->build_transaction();
            }
            dump($_POST["deal"]."条交易数据生成成功!");
            
            for ($i = 0; $i < $_POST["repay"]; $i++)
            {
                $this->build_repayment_record();
            }
            dump($_POST["repay"]."条还款记录生成成功!");
        }
        dump("生成完成!");
        echo '点击<a href="'.U("AutoBuild/Index").'">返回</a>到设置界面!';
        exit(0);
//         $return["close"] = 0;
//         $return["echo"] = 1;
//         $return["info"] = "设置成功!";
//         $return["tag"] = "";
//         $return["callback"] = "";
//         $return["url"] = "";
//         $this->ajaxReturn($return);
    }
}