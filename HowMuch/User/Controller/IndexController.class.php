<?php

namespace User\Controller;

use Think\Controller;
use Common\Controller\Form;
use Common\Controller\FormElement;

class IndexController extends Controller
{

    public function index()
    {
        echo '<input type="file" />';
        exit(0);
//         dump(I("get."));
//         dump(base64_encode("123456"));
//         dump(think_encrypt("123456", UC_AUTH_KEY));

//         $key = ltrim(I("get.key"), "'");
//         $key = rtrim($key, "'");
//         $key = explode("|", think_decrypt($key, date("Y-m-d").UC_AUTH_KEY));
//         $code = $key[0];
//         $key = $key[1];
//         dump($code);
//         dump($key);
//         dump(str_replace("|", "<br />", think_decrypt($key, UC_AUTH_KEY)));

        $form = new Form("");
        $form->set("cols", 2);

        $form->setElement("card_group", "group", "信用卡基本信息");
        $form->setElement("bank", "autocomplete", "发卡银行", array("bool" => "required",
                          "list" => parse_autocomplete("select name from bank order by sort_id")));
        $form->setElement("card_type_name", "string", "卡片名称", array("bool" => "required",
                            "pclass" => "sel_cardtypename"
        ));

        $pay_type_txt = C("PAYTYPE_TEXT");
        $form->setElement("pay_type", "select", "支付方式", array("bool" => "required",
                "ext" => 'gurl="'.U("CardOp/getAgreement").'"',
                "list" => parse_select_list("array", array_keys($pay_type_txt), $pay_type_txt, "请选择支付方式"),
        ));
        $form->setElement("fee", "num", "服务费用", array("bool" => "required",
                "hint" => "money", "addon" => "元"));

        $form->setElement("card", "string", "卡号", array("bool" => "uniq required", "hint" => "card",
                "min" => 13, "maxlength" => 16));

        $form->setElement("pay_pwd", "string", "支付密码", array("bool" => "required", "hint" => "num",
                "min" => 6, "maxlength" => 6));

        $form->setElement("card_img1", "file", "信用卡正面图片", array("bool" => "required", "cat_title" => "信用卡正面图片信息"));
        $form->setElement("bill", "num", "月出账单日", array("bool" => "required", "min" => 1,
                "ext" => 'max="28"', "addon" => "号"));
        $form->setElement("cdate_month", "select", "卡片有效期", array("bool" => "required", "element_cols" => 1,
                "group" => "start", "title" => "卡有效期月",
                "list" => parse_select_list("for", array(1, 12, 1, 1), "月"),
        ));
        $form->setElement("cdate_year", "select", "", array("bool" => "required", "element_cols" => 2,
                "group" => "end", "title" => "卡有效期年",
                "list" => parse_select_list("for", array(2014, 2020, 1, 1), "年"),
        ));
        $content = '
        <extend name="Home@Public/base" />
        <block name="nav_list">
            <include file="Home@Main/nav" />
        </block>

        <block name="body_content">
                '.$form->fetch().'
        </block>';
        $this->show($content);
    }

    public function passwd()
    {
        $return["echo"] = 1;
        $return["close"] = 0;
        $return["url"] = U("Index/person");
        $return["tag"] = "#body";
        if (IS_POST)
        {
            if (think_md5(I("post.old_pwd"), UC_AUTH_KEY) !=
                     get_user_info("login_pwd"))
            {
                $return["info"] = "对不起，你输入的原密码不对！";
                $return["url"] = "";
                $return["tag"] = "";
                $this->ajaxReturn($return);
            }
            if (I("post.login_name") == get_user_info("login_name") &&
                     !I("post.pwd"))
            {
                $return["info"] = "没有设置登录名或新密码!";
                $return["url"] = "";
                $return["tag"] = "";
                $this->ajaxReturn($return);
            }

            $data["login_name"] = I("post.login_name");
            $input_pwd = I("post.pwd");
            if ($input_pwd)
            {
                $pwd_len = strlen($input_pwd);
                $lower = '/[a-z]/';
                $upper = '/[A-Z]/';
                $digit = '/[0-9]/';
                $special = '/[`~!@#$%^&*()_+\\-=\[\];\',.\/{}|:"<>? \\\]/';
                //密码长度必须为6到20个字符，密码必须由大小写字符、数字和特殊字符组合
                if ($pwd_len < 8 || $pwd_len > 20)
                    $this->ajaxReturn(array("echo" => 1, "info" => "你输入的新密码长度必须在8到20个字符之间!"));
                if (!((preg_match($lower, $input_pwd) || preg_match($upper, $input_pwd)) &&
                    preg_match($digit, $input_pwd) && preg_match($special, $input_pwd)))
                    $this->ajaxReturn(array("echo" => 1, "info" => "你输入的新密码必须要有大小写字母、数字和特殊字符!"));

                $data["pwd"] = think_md5($input_pwd, UC_AUTH_KEY);
            }
            $data["id"] = get_user_info("uid");
            $data["update_time"] = getcurtime();

            $obj = M("users");
            $ret = $obj->validate(array(array('login_name', '', '输入的登录名已存在!', 0, 'unique', 3)))->create($data, 2);
            if (!$ret)
                $this->ajaxReturn(array("echo" => 1, "info" => $obj->getError()));

            if ($obj->save())
            {
                $return["info"] = "修改成功!";
                $auth = session("user_auth");
                if ($auth["login_name"] != $data["login_name"])
                {
                    $remark = "登录名由 ".$auth["login_name"]." 修改为 ".$data["login_name"];
                    $auth["login_name"] = $data["login_name"];
                }
                if (I("post.pwd"))
                {
                    $auth["login_pwd"] = $data["pwd"];
                    $remark .= "<br />修改了登录密码!";
                }

                save_user_record(USR_MOD, $data["login_name"], $remark);

                session('user_auth', $auth);
                session('user_auth_sign', data_auth_sign($auth));
            }
            else
            {
                $return["info"] = "修改失败:" . $obj->getError();
                $return["url"] = "";
                $return["tag"] = "";
            }
        }

        $this->ajaxReturn($return);
    }

    public function person()
    {
        if (!is_login())
            $this->redirect("Home/Index/index");

        $usr = new Form("",
                array("action" => U("passwd"),"class" => "form-horizontal col-sm-8 col-md-8 col-sm-offset-2 col-md-offset-2 main_first_row"));
        $usr->setElement("info_group", "group", "个人信息");
        $usr->setElement("username", "static", "用户名",
                array("label_cols" => 4,"value" => get_user_info()));
        $usr->setElement("last_ip", "static", "最后登录地点",
                array("label_cols" => 4,"value" => get_user_info("login_addr")));
        $usr->setElement("last_login_time", "static", "最后登录时间",
                array("label_cols" => 4,"value" => get_user_info("login_time")));
        $usr->setElement("edit_group", "group", "修改信息");
        $usr->setElement("login_name", "string", "登录名",
                array("bool" => "required", "label_cols" => 4,"element_cols" => 6,"value" => get_user_info(
                        "login_name")));
        $usr->setElement("old_pwd", "password", "原密码",
                array("bool" => "required","label_cols" => 4,"element_cols" => 6));
        $usr->setElement("pwd", "password", "新密码",
                array("label_cols" => 4,"element_cols" => 6));
        $usr->set("btn 0 txt", "修改");
        $usr->setBtn("返回主页", U("Home/Index/index"),
                array("bool" => "blink","ext" => 'type="button"'));
        $this->show($usr->fetch());
    }


    public function hw_login($key = "")
    {
        $key = explode("|", think_decrypt($key, date("Y-m-d-H").UC_AUTH_KEY));
        $code = $key[0];
        $key = $key[1];

        if (!$key || !$code)
        {
            if (IS_POST)
                $this->ajaxReturn(array("status" => 1, "url" => U("Home/Index/error")));
            header("Location:".U("Home/Index/error"));
            exit(0);
        }

        //判断硬件标识符是否存在, 存在判断硬件码
        $hw = sqlRow("select code,proxy,sid,gid,uid,state,hw_key from hw where code='".$code."'");
        if (!$hw || $hw["state"] != NORMAL)
        {
            header("Location:".U("Home/Index/error"));
            exit(0);
        }


        if ($hw["hw_key"])
        {
            if ($hw["hw_key"] != $key)
            {
                header("Location:".U("Home/Index/error"));
                exit(0);
            }
        }
        else
        {
//             sqlCol("update hw set hw_key='".$key."' where code='".$code."'");
            M("hw")->where("code='".$code."'")->setField("hw_key", $key);
//             if (!$ret)
//             {
//                 header("Location:".U("Home/Index/error"));
//                 exit(0);
//             }
        }

//         dump(M()->getLastSql());
//         dump($key);
//         dump($code);
//         dump($hw);
//         exit(0);

        unset($hw["hw_key"]);
        unset($hw["hw_state"]);
        session("login_hw", $hw);
//         echo '<span class="kyo_red">'.$code."&emsp;".get_perm_name($hw["gid"])."</span>";
    }

    // 用户登录
    public function login($username = null, $password = null, $verify = null)
    {
//         echo think_md5("123456", UC_AUTH_KEY);
//         $auth = array('name' => '广州恒大', 'admin' => 3, 'uid' => 33, 'sid'=> 27,
//                 'code' => '311233B0038', 'proxy_id' => 4);
//         session("user_master", 1);
//         session('user_auth', $auth);
//         session('user_auth_sign', data_auth_sign($auth));
//         header("Location:".U("Home/Index/index"));

//         $key = ltrim(I("get.key"), "'");
//         $key = rtrim($key, "'");
//         if (!session("login_hw"))
//             $this->hw_login($key);

//         dump(session("login_hw"));
//         exit(0);
//         session("[destroy]");

        $hw = array("proxy" => 0, "sid" => 0, "gid" => -1, "uid" => 0);
        session("login_hw", $hw);

        if (IS_POST)
        {
            /* 检测验证码 TODO: */
            if (!check_verify($verify))
                $this->error('验证码输入错误！');

            /* 调用UC登录接口登录 */
            $User = D("Users");
            $uid = $User->login($username, $password);
            if (0 < $uid) // UC登录成功
                /* 登录用户 */
                // TODO:跳转到登录前页面
                $this->success('登录成功！', U('Home/Index/index'));
            else
            {
                // 登录失败
                switch ($uid)
                {
                    case -1:
                        $error = '用户不存在！';
                        break; // 系统级别禁用
                    case -21:
                    case -22:
                    case -23:
                    case -24:
                        $error = '密码错误, 今日还有 '.(($uid + 20) * -1).' 次机会！';
                        $this->ajaxReturn(array("info" => $error, "callback" => 'clear_input(1);'));
                        break;
                    case -3:
                        $this->success('登录失败！', 'http://www.baidu.com');
                        break;
                    case -4:
                        $error = '分站已被系统锁定，请联系站长！';
                        break;
                    case -5:
                        $error = '系统正在维护中...！';
                        break;
                    case -6:
                        $error = '非法用户！';
//                         $this->ajaxReturn(array("status" => 1, "url" => 'Home/Index/error'));
                        break;
                    case -7:
                        $error = '此用户已在线！';
//                         $this->ajaxReturn(array("status" => 1, "url" => 'Home/Index/error'));
                        break;
                    case -8:
                        $error = '你已连续5次输错密码，今日账号已被锁定!';
                        break;
                    default:
                        $error = '未知错误！';
                        break; // 0-接口参数错误（调试阶段使用）
                }
                // echo $error;
                $this->ajaxReturn(array("info" => $error, "callback" => 'clear_input(0);'));
                $this->error($error);
            }
        }
        else
        {
            if (is_login())
                $this->redirect('Home/Index/index');
            else
            {
                $grpchall = array("0" => "S", "1" => "F", "2" => "O", "3" => "T", "6" => "Z", "-1" => "Q", "7" => "A", "8" => "P");
                $shw = session("login_hw");
                $this->assign("hwID", $grpchall[$shw["gid"]].$shw["code"]);
                $this->display();
            }
        }
    }

    /* 退出登录 */
    public function logout()
    {
        $hw = session("login_hw");
        if (is_login())
        {
            D('Users')->logout();
//             session('[destroy]');
            session(null);
        }
        session("login_hw", $hw);
        $this->redirect('User/Index/login');
    }

    // 登录验证码
    public function verify()
    {
        $verify = new \Think\Verify();
        $verify->entry(1);
    }

    // 用户锁定
    public function lock($id, $status = LOCK, $sid = 0)
    {
        $data_id = "id";
        $return = array("echo" => 1,"info" => "锁定成功!","url" => session(
                "prev_urlusers"),"tag" => ".users");
        if ($sid)
            $data_id = "sid";

        sqlCol("delete from kyo_session where session_id in (select session_id from users where ".$data_id."=".$id.")");
        M("users")->where($data_id."=".$id)->setField(array("status" => $status, "session_id" => null));

        if ($status != LOCK)
            $return["info"] = "解锁成功!";

        $this->ajaxReturn($return);
    }

    // 重置用户密码
    public function reset($where)
    {
        $this->ajaxReturn(array("echo" => 1,"info" => "kyo重置成功!".$where));
        $codes = explode("(", $where);
        $codes = explode(")", $codes[1]);
        $codes = explode(",", $codes[0]);

        foreach ($codes as $vo)
        {
            $vo = str_replace("'", "", $vo);
            $data["login_name"] = $vo;
            $data["pwd"] = think_md5($vo, UC_AUTH_KEY);
            M("users")->where("code='" . $vo . "'")->save($data);
        }

        $this->ajaxReturn(array("echo" => 1,"info" => "重置成功!"));
    }

}

