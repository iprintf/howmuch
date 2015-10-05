<?php

namespace User\Model;
use Think\Model;

class UsersModel extends Model 
{
    public function login($username, $password)
    {
        $user = sqlRow("select id,code,username,login_name,status,pwd,type,sid,proxy_id,last_login_time,
                        last_login_addr,proxy_sub_name,mac,input,session_id from users where login_name='".$username."'");
//         dump(M()->getLastSql());
        if (!is_array($user))
        {
//             save_user_record(USR_ERR_NOT, "", $username);
            return -1;
        }
        
        //限制一个终端登录用户情况
        $hw = session("login_hw");
        if (!$hw || !(($user["proxy_id"] == $hw["proxy"] || $hw["proxy"] == 0) &&
            ($user["sid"] == $hw["sid"] || $hw["sid"] == 0) &&
            ($user["type"] == $hw["gid"] || $hw["gid"] == -1) &&
            ($user["id"] == $hw["uid"] || $hw["uid"] == 0)))
        {
            save_user_record(USR_ERR_ILL, $user["id"], $username);
            return -6;
        }
        
        //限制登录时间
        $cur_hour = date("H");
        if ($user["sid"] != 125 && $user["type"] != 0 && $user["type"] != 9 && ($cur_hour > 22 || $cur_hour < 9))
        {
            save_user_record(USR_ERR_MAIN, $user["id"], $username);
            return -5;
        }
        
        //判断用户是否输入密码错误次数大于等于5次，则今日锁定不允许登录
        $errNum = sqlCol("select count(id) from users_record where op_type=".USR_ERR_PWD." and 
                    opid=".$user["id"]." and DATE_FORMAT(op_time,'%Y-%m-%d')='".date("Y-m-d")."'");
        
                            
        if ($user["type"] != 0 && $errNum >= 5)
            return -8;
        
        //判断用户是否在线 超级管理员除外
        if ($user["type"] != 0 && $user["session_id"])
        {
            save_user_record(USR_ERR_EXIST, $user["id"], $username);
            return -7;
        }
        
        if ($user["type"] == 6) //如果是站长则获取用户状态就是分站状态
            $sub_status = $user["status"];
        else
            $sub_status = sqlCol("select status from users where id=".$user["sid"]);
        if ($sub_status == LOCK)
        {
            save_user_record(USR_ERR_LOCK, $user["id"], $username);
            return -4;
        }
        
//         if ($user["mac"] != "" && $user["mac"] != get_cli_mac())
//             return -3;   //mac不为空，判断mac地址和本地mac是否相同，如果不同返回
        
        if (think_md5($password, UC_AUTH_KEY) === $user["pwd"])
        {
            $this->updateLogin($user);
            return $user["id"];
        }
        
        $ret = 5 - $errNum - 1;
        save_user_record(USR_ERR_PWD, $user["id"], $username);
        if ($ret == 0)
            return -8;
        return -(20 + $ret);  //密码错误
    }

    private function updateLogin($user)
    {
        /* 记录登录SESSION和COOKIES */
        $user["proxy_id"] = $user["proxy_id"] ? $user["proxy_id"] : sqlCol("select id from users where type=8");
        $user["sid"] = $user["sid"] ? $user["sid"] : sqlCol("select id from users where type=6");
        if ($user["type"] == 8)
            $user["proxy_id"] = $user["id"];
        if ($user["type"] == 6)
            $user["sid"] = $user["id"];
        
        $auth = array(
            'uid'        => $user['id'],
            'name'       => $user['username'],
            'code'       => $user['code'],
            'login_name' => $user['login_name'],
            'login_pwd'  => $user['pwd'],
            'admin'      => $user['type'],
            'sid'        => $user["sid"],
            'input'      => $user["input"],
            'proxy_id'   => $user["proxy_id"],
            'login_time' => $user['last_login_time'],
            'login_addr' => $user['last_login_addr'],
        );
        
        $last_ip = get_client_ip(1);
        $ip = new \Org\Net\IpLocation();
        $last_login_addr = $ip->getlocation($last_ip);
        $last_login_addr = $last_login_addr["country"] . $last_login_addr["area"];
        
        $data = array(
            'id'   =>  $user["id"],
            'last_login_time' => getcurtime(),
            'last_ip' => $last_ip,
            'last_login_addr' => $last_login_addr,
            'session_id'  => session_id(),
        ); 
        if ($user["mac"] == "")
            $data["mac"] = get_cli_mac();
        $this->save($data);
        save_user_record(USR_SUCCESS, $user["id"], $user["login_name"], "", $last_ip, $last_login_addr);

        session('user_auth', $auth);
        session('user_auth_sign', data_auth_sign($auth));
        
        if ($user["type"] == 0)  //如果为超级管理员则特殊标记
            session('user_master', 1);
            
        session('notice', null);
        
    }

    /**
     * 注销当前用户
     * @return void
     */
    public function logout()
    {
        save_user_record(USR_QUIT);
        sqlCol("update users set session_id=null where id=".get_user_info("uid"));        
        session('user_auth', null);
        session('user_auth_sign', null);
        session('notice', null);
        session('user_master', null);
    }
}

?>
