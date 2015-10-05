<?php

function kempty($arr, $key, $val = "")
{
    if ($val && isset($arr[$key]) && $arr[$key] == $val)
        return true;

    if (!$val && (!isset($arr[$key]) || $arr[$key] == ""))
        return true;

    return false;
}

//判断如果值不存在则给赋值为空
function karray(& $arr, $key = array())
{
    foreach ($key as $v)
    {
        if (!isset($arr[$v]))
            $arr[$v] = "";
    }
}

//将新数组追加到原数组后面
function karray_cat(& $o, $n)
{
    $index = count($o);
    foreach ($n as $v)
    {
        $o[$index++] = $v;
    }
    return $o;
}

//将新数组添加到原数组指定位置
function karray_insert(& $o, $pos, $d)
{
    $n = array();
    $index = 0;

    foreach ($o as $v)
    {
        if ($pos <= 0 || $pos == $index)
        {
            foreach ($d as $dv)
            {
                $n[$index++] = $dv;
            }
            $pos++;
        }
        $n[$index++] = $v;
    }
    $o = $n;
}

//将数组中删除指定多个元素
function karray_del(& $o, $d)
{
    $n = array();

    foreach ($o as $k => $v)
    {
        if (!in_array($v, $d))
            $n[$key] = $v;
    }
    $o = $n;
}

//初始化数组所有元素为为
function init_array(& $arr, $key = array(), $val = "")
{
    foreach ($key as $v)
    {
        $arr[$v] = $val;
    }
}

/**
 * 系统非常规MD5加密方法
 * @param  string $str 要加密的字符串
 * @return string
 */
function think_md5($str, $key = 'KyoUserKey')
{
	return '' === $str ? '' : md5(sha1($str) . $key);
}

/**
 * 系统加密方法
 * @param string $data 要加密的字符串
 * @param string $key  加密密钥
 * @param int $expire  过期时间 (单位:秒)
 * @return string
 */
function think_encrypt($data, $key, $expire = 0)
{
	$key  = md5($key);
	$data = base64_encode($data);
	$x    = 0;
	$len  = strlen($data);
	$l    = strlen($key);
	$char =  '';

	for ($i = 0; $i < $len; $i++) {
		if ($x == $l) $x=0;
		$char  .= substr($key, $x, 1);
		$x++;
	}
	$str = sprintf('%010d', $expire ? $expire + time() : 0);
	for ($i = 0; $i < $len; $i++) {
		$str .= chr(ord(substr($data,$i,1)) + (ord(substr($char,$i,1)))%256);
	}
	return str_replace('=', '', base64_encode($str));
}

/**
 * 系统解密方法
 * @param string $data 要解密的字符串 （必须是think_encrypt方法加密的字符串）
 * @param string $key  加密密钥
 * @return string
 */
function think_decrypt($data, $key)
{
	$key    = md5($key);
	$x      = 0;
	$data   = base64_decode($data);
	$expire = substr($data, 0, 10);
	$data   = substr($data, 10);
	if($expire > 0 && $expire < time()) {
		return '';
	}
	$len  = strlen($data);
	$l    = strlen($key);
	$char = $str = '';
	for ($i = 0; $i < $len; $i++) {
		if ($x == $l) $x = 0;
		$char  .= substr($key, $x, 1);
		$x++;
	}
	for ($i = 0; $i < $len; $i++) {
		if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
			$str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
		}else{
			$str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
		}
	}
	return base64_decode($str);
}

/**
 * 检测验证码
 * @param  integer $id 验证码ID
 * @return boolean     检测结果
 */
function check_verify($code, $id = 1)
{
    $verify = new \Think\Verify();
    return $verify->check($code, $id);
}

/**
 * 检测用户是否登录
 * @return integer 0-未登录，大于0-当前登录用户ID
 */
function is_login()
{
    $user = session('user_auth');
    if (empty($user))
        return 0;
    else
        return session('user_auth_sign') == data_auth_sign($user) ? $user['uid'] : 0;
}

/**
 * 数据签名认证
 * @param  array  $data 被认证的数据
 * @return string       签名
 */
function data_auth_sign($data)
{
    //数据类型检测
    if(!is_array($data))
        $data = (array)$data;

    ksort($data); //排序
    $code = http_build_query($data); //url编码并生成query字符串
    $sign = sha1($code); //生成签名
    return $sign;
}

// 获取用户信息
function get_user_info($field = "name")
{
    $auth = session("user_auth");
    if (!$auth)
        return 0;
    return $auth[$field];
}

// 获取性别文本
function get_sex_txt($val)
{
    $sex = array(1 => "男", 2 => "女");
    return $sex[$val];
}

// 获取状态文本
function get_status_txt($val)
{
    $status = C("STATUS_TEXT");
    return $status[$val];
}

// 获取用户姓名
function get_username($id, $table = "users", $name = "username")
{
    if (!$id)
        return "";
    return sqlCol("select ".$name." from ".$table." where id=".$id);
}

// 获取客户姓名
function get_basisname($id)
{
    return get_username($id, "basis", "name");
}

// 获取操作记录类型名称
function get_oper_typename($val)
{
    $name = C("RECORD_TEXT");
    return $name[$val];
}

//获取是或否值
function get_bool_txt($val)
{
    $bool = array(1 => "是", 2 => "否");
    return $bool[$val];
}

//获取客户端MAC地址
function get_cli_mac()
{
    $arp_array = array();
    @exec("Public/arp -n", $arp_array);
    $tmp_array = array();

    foreach ($arp_array as $value)
    {
        if (strstr($value, $_SERVER['REMOTE_ADDR']) &&
        preg_match("/(:?[0-9a-f]{2}[:-]){5}[0-9a-f]{2}/i", $value, $tmp_array))
        {
            return $tmp_array[0];
        }
    }
    return false;
}

//获取上一页Url地址
function get_prev_url()
{
    $url = session("prev_url");
    if (!$url || strstr($url, "/home/".strtolower(get_platfrom_name())."/index"))
        return false;
    return $url;
}

//获取平台名称
function get_platfrom_name($name = false)
{
    if ($name)
        return "多少钱?";
    else
        return "Index";
}

//获取支付方式名称
function get_paytype($pid)
{
    $t = C("PAYTYPE_TEXT");
    return $t[$pid];
}

//解析字符串中的中括号对应数据库字段的值
//str 为要解析的字符串， row 为数据库一行数据
//处理链接URL, 把中括号里东西换成数据字段值
function parse_link(& $str, $row)
{
    if (!$str || $str == "")
        return false;
    preg_match_all("/\[([a-zA-Z_]+)\]/", $str, $match);
    foreach ($match[1] as $match_key => $match_v)
    {
        $str = str_replace($match[0][$match_key], $row[$match_v], $str);
    }
    return $str;
}

//解析表单中特殊链接和扩展操作
function parse_form_link_html(& $option)
{
    $option["link"] = "";
    $link = "";

    if ($option["bool"])
    {
        $conv_html = "";
        $bool = explode(" ", $option["bool"]);
        foreach ($bool as $val)
        {
            $conv_html .= ' '.$val.'="'.$val.'" ';
        }
        $option["ext"] .= $conv_html;
    }

    $option["link"] .= " ".$option["ext"]." ";

    if (!isset($option["url"]) || $option["url"] == "")
        return true;

    if (isset($option["pop"]))
        $link = ' pop="{'.$option["pop"].'}" ';

    if (isset($option["tag"]))
        $link = ' tag="'.$option["tag"].'" ';

    if ($link != "" && $option["type"] != "file")
        $option["link"] .= $link.' url="'.$option["url"].'" ';

}

//处理链接生成Html代码  解析按钮和链接的特殊扩展操作
function parse_link_html_single(& $option)
{
    $option["link"] = "";

    if (isset($option["url"]) && $option["url"] != "")
    {
        $option["href"] = "#";

        if (isset($option["pop"]))
            $option["link"] = ' pop="{'.$option["pop"].'}" ';

        if (isset($option["tag"]))
            $option["link"] = ' tag="'.$option["tag"].'" ';

        if ($option["link"] == "" && !isset($option["query"]))
        {
            $option["href"] = $option["url"];
            if (isset($option["target"]))
                $option["link"] = 'target="'.$option["target"].'" ';
        }

        $option["link"] .= ' url="'.$option["url"].'" ';
    }

    if (isset($option["bool"]))
    {
        $conv_html = "";
        $bool = explode(" ", $option["bool"]);
        foreach ($bool as $val)
        {
            $conv_html .= ' '.$val.'="'.$val.'" ';
        }
        $option["link"] .= $conv_html;
    }

    if (isset($option["ext"]))
        $option["link"] .= " ".$option["ext"]." ";
}

//解析按钮和链接的特殊扩展操作
//处理链接生成Html代码
function parse_link_html(& $data)
{
    if (!empty($data))
    {
        foreach ($data as $key => $option)
        {
            parse_link_html_single($data[$key]);
        }
    }

    return true;
}

//生成autocomplete的数据
//解析获取autocomplete的数据
function parse_autocomplete($data)
{
    $ret = "";

    if (is_array($data))
    {
//         $data[0]      列表模式  for 为有规则循环模式  array 为无规则数组模式
//         $data[1]      起始值 或 直接是数值
//         $data[2]      结束值
//         $data[3]      步长
//         $data[4]      对多少位对齐补零
        if ($data[0] == "for")
        {
            if (is_float($data[1]))
            {
                $zero_len = isset($data[4]) ? $data[4] : 3;
                $dir = STR_PAD_RIGHT;
//                 dump("float");
            }
            else
            {
                $zero_len = isset($data[4]) ? $data[4] : 2;
                $dir = STR_PAD_LEFT;
            }

            $list = "";

            for ($i = $data[1]; $i <= $data[2]; $i += $data[3])
            {
                if ($dir == STR_PAD_RIGHT && !strchr($i, "."))
                    $str = $i.".0";
                else
                    $str = $i;
                $list .= fill_zero($str, $zero_len, $dir)."|";
            }
            $list = rtrim($list, "|");

            return $list;
        }
    }
    else
        $data = M()->query($data);

    $total = count($data[0]);
    $i = 0;
    foreach ($data as $row)
    {
        if (is_array($row))
        {
            foreach($row as $key => $col)
            {
                $ret .= $row[$key];
                if ($i != $total - 1)
                    $ret .= ',';
                $i++;
            }
            $ret = rtrim($ret, ",");
        }
        else
            $ret .= $row;
        $ret .= '|';
    }
    $ret = rtrim($ret, "|");

    return $ret;
}

//生成select的数据
//解析获取select的list值 主要针对数据表操作
function parse_select_list($sql, $val = "", $txt = "", $default = "", $defval = "")
{
    $list = array();
    $index = 0;

    if ($default)
    {
        $list[0]["val"] = $defval;
        $list[0]["txt"] = $default;
        $index++;
    }

    //for循环，针对有规律的数字(整型和浮点都支持)
    if ($sql == "for")
    {
        //$val[0]   起始值
        //$val[1]   结束值
        //$val[2]   步长
        //$val[3]   下标起始值
        //$val[4]   数字又多少位对齐补零
        if (is_float($val[0]))
        {
            $zero_len = isset($val[4]) ? $val[4] : 3;
            $dir = STR_PAD_RIGHT;
        }
        else
        {
            $zero_len = isset($val[4]) ? $val[4] : 2;
            $dir = STR_PAD_LEFT;
        }
        if ($txt)
            $list[0]["txt"] = $txt;

        for ($i = $val[0], $j = $val[3] ? $val[3] : 0; $i <= $val[1]; $i += $val[2], $j++)
        {
            if ($dir == STR_PAD_RIGHT && !strchr($i, "."))
                $i = $i.".0";
            $list[$j]["val"] = $i;
            $list[$j]["txt"] = fill_zero($i, $zero_len, $dir);
        }
        return $list;
    }

    //针对没有规律的值和文本对应
    if ($sql == "array")
    {
        $flag = 0;
        foreach ($val as $key => $v)
        {
            $list[$index]["val"] = $v;
            if ($txt == "")
                $list[$index]["txt"] = $v;
            else
            {
                karray($txt, array($key, $v));
                //只有第一次验证值和文本是否有关联
                if ($flag == 0 && $txt[$key])
                    $flag = 1;  //值与文本有关联，如果第一个有关联才所有关联
                else if ($flag == 0)
                    $flag = 2;

                if ($flag == 1)
                    $list[$index]["txt"] = $txt[$key];
                else
                    $list[$index]["txt"] = $txt[$v];
            }
            $index++;
        }
        return $list;
    }

    //针对数据库数据读取
    $Rs = M()->query($sql);
//     dump(M()->getLastSql());
    if (!$Rs)
        return $list;

    if ($val == "" || $txt == "")
    {
        $key = array_keys($Rs[0]);
        $val = $val ? $val : "[".$key[0]."]";
        $txt = $txt ? $txt : count($key) == 1 ? "[".$key[0]."]" : "[".$key[1]."]" ;
    }

    foreach ($Rs as $row)
    {
        $list[$index]["val"] = $val;
        $list[$index]["txt"] = $txt;
        parse_link($list[$index]["val"], $row);
        parse_link($list[$index]["txt"], $row);
        $index++;
    }

    return $list;
}

//获取当前时间
//获取当前日期时间，用于自动完成时间填充
function getcurtime()
{
    return date("Y-m-d H:i:s");
}

//给字符串根据位数填充补零
//在前面补指定个数零
function fill_zero($str, $len = 2, $dir = STR_PAD_LEFT)
{
    return str_pad($str, $len, "0", $dir);
}

//给字符串根据位数填充补html空格
//显示的字符串补html空格
function fill_nbsp($str, $len = 2, $dir = STR_PAD_LEFT)
{
    $l = $len - strlen($str);
    if ($l <= 0)
        return $str;
    $space = "";
    for ($i = 0; $i < $l; $i++)
    {
        $space .= "&nbsp;&nbsp;";
    }
    if ($dir == STR_PAD_LEFT)
        return $space.$str;
    return $str.$space;
}

//字段显示转化百分比
function field_conv_per($num)
{
    return ($num * 100)."%";
}

function get_sub_name($id)
{
    if (!$id)
        return "";
    $sub = M("Users")->field("proxy_sub_name")->where("id=".$id)->select();
    return $sub[0]["proxy_sub_name"];
}

//随机十六进制值或转化十六进制值
function myhex($val = "", $bit = 1)
{
    if ($val)
        return strtoupper(str_pad(dechex($val), $bit, "0", STR_PAD_LEFT));
    return strtoupper(str_pad(dechex(mt_rand(1, 15)), $bit, "0", STR_PAD_LEFT));
}

//获取或组合umax值 $name 要获取的umax名称 $id则是存放umax用户的ID $put代表要组合新的值
//获取指定的umax值  自动对指定umax值自增  获取代理和分站的input序列号
function parse_umax($name, $id = 2, $plus = true)
{
    $ret = array();
    $obj = M("users");
    $data = $obj->field("umax,input,proxy_id")->where("id=".$id)->select();

    if (!preg_match_all("/$name:([0-9]+).*/", $data[0]["umax"], $match))
        return false;

    $ret["old_umax"] = $match[1][0] + 1;
    $ret["umax"] = str_pad(dechex($match[1][0] + 1), 3, "0", STR_PAD_LEFT);
    if ($name == "finance" || $name == "operator" || $name == "salesman" ||
            $name == "employee" || $name == "customer")
    {
        $proxy_id = $obj->field("input")->where("id=".$data[0]["proxy_id"])->select();
        $ret["proxy"] = dechex($proxy_id[0]["input"]);
        $ret["sub"] = dechex($data[0]["input"]);
    }
    else if ($name == "sub")
        $ret["proxy"] = dechex($data[0]["input"]);

    if ($plus)
    {
        $umax = array();
        $umax["umax"] = str_replace($name.":".$match[1][0], $name.":".($match[1][0] + 1), $data[0]["umax"]);
        $umax["id"] = $id;
        $obj->save($umax);
    }

    return $ret;
}

//自动生成内部代码
function build_code($type, $id = "")
{
    $code = dechex(mt_rand(1, 15)).SERVER_NUM;
    $r1 = dechex(mt_rand(1, 15));
    $r2 = dechex(mt_rand(1, 15));
    $r3 = dechex(mt_rand(1, 15));
    $pername = array("finance", "operator", "salesman", "employee", "customer");

    switch ($type)
    {
    	case "0":
            $code = "00LZCARD000";
            break;
    	case "card":
            if (strlen($id) == 11)
                $con = "code='".$id."'";
            else
                $con = "id=".$id."";
            $data = M("basis")->field("code,codeID")->where($con)->select();
            while (1)
            {
                $code = substr($data[0]["code"], 0, -1).myhex().dechex($data[0]["codeID"] + 1);
                if (!sqlCol("select id from card where code='".$code."'"))
                    break;
            }
            break;
    	case "8":
            $num = parse_umax("proxy", 2, false);
            $code .= "0".$r1."0".$type.$r2.$num["umax"].$r3;
            break;
    	case "9":
            $code .= "0".$r1."0".$type.$r2."001".$r3;
            break;
    	case "7":
            $num = parse_umax("assist",2, false);
            $code .= "0".$r1."0".$type.$r2.$num["umax"].$r3;
            break;
    	case "6":
            $num = parse_umax("sub", $id, false); //代理ID
            $code .= $num["proxy"].$r1."0".$type.$r2.$num["umax"].$r3;
            break;
    	default:
            $num = parse_umax($pername[$type - 1], $id, false);  //分站ID
            $code .= $num["proxy"].$r1.$num["sub"].$type.$r2.$num["umax"].$r3;
            break;
    }
    return strtoupper($code);
}

//生成pos内部代码
function build_pos_code($pay, $sid, $proxy = "")
{
    $code = myhex().SERVER_NUM.myhex($pay, 2).myhex();

    $usr = M("users");
    $data = $usr->field("umax,input")->where("id in (".$sid.",".$proxy.")")->select();
    $sub = $sid > $proxy ? 1 : 0;
    $pro = $sub ? 0 : 1;
    //         dump($usr->getLastSql());
    preg_match_all("/pos:([0-9]+).*/", $data[$pro]["umax"], $match);

    $code .= myhex($data[$pro]["input"]).myhex($data[$sub]["input"]).myhex();
    $code .= myhex($match[1][0] + 1, 3).myhex();

    return strtoupper($code);
}

//获取假日列表并存入数组中，假日日期为键名，是否为补假为数值
function get_holiday_array()
{
    $holiday_all = M()->query("select is_fill,holiday from holiday");

    $holiday = array();

    foreach ($holiday_all as $row)
    {
        $holiday[$row["holiday"]] = $row["is_fill"];
    }
    return $holiday;
}

//执行原生sql获取一列数据
function sqlCol($sql)
{
    $data = M()->query($sql);
    if (is_array($data) && isset($data[0]))
    {
        foreach ($data[0] as $val)
        {
            return $val;
        }
    }
    return false;
}

//执行原生sql获取二维数组
function sqlAll($sql)
{
    return M()->query($sql);
}

//执行原生sql区取一行数据
function sqlRow($sql)
{
	$obj = M();
    $data = $obj->query($sql);
    if (is_array($data))
        return $data[0];
//     dump($obj->getLastSql());
    return false;
}

//处理多表关联查询
function findIn($name, $new, $table, $field = "id", $where = 1)
{
    $stype = I("post.search_type");
    $skey = I("post.search_key");

    if (!$stype || !$skey || $stype != $name)
        return false;

    $ids = sqlAll("select ".$field." from ".$table." where ".$where." and ".$stype. " like '%".$skey."%'");
//     dump(M()->getLastSql());
    $in = "";
    foreach ($ids as $row)
    {
        $in .= $row[$field].",";
    }
    $in = rtrim($in, ',');

    $_POST["search_type"] = $new;
    $_POST["search_key"] = array("in", $in);

    return true;
}

//格式化显示金额
function format_money($s, $ch = "")
{
    $point = str_pad(substr(round($s - (int)$s, 2), 2), 2, "0", STR_PAD_RIGHT);
    return $ch.format_dis_field((int)$s, 3, ",").".".$point;
}

//格式化显示字段
function format_dis_field($s, $rule = 4, $ch = " ")
{
    $ret = "";
    $index = 0;
    $slen = strlen($s);

    if (!is_array($rule))
    {
        while (1)
        {
            $ret .= substr($s, $index, $rule);
            $index += $rule;
            if ($index >= $slen)
                return $ret;
            $ret .= $ch;
        }
    }

    foreach ($rule as $len)
    {
        $ret .= substr($s, $index, $len);
        $index += $len;
        if ($index >= $slen)
            return $ret;
        $ret .= $ch;
    }

    return $s;
}

//执行原生存储过程
function sqlPro($name, $option = "", $pro_arg = "")
{
    karray($option, array("total", "first","list", "type"));
    $obj = M();
    if ($pro_arg && is_array($pro_arg))
    {
        $new_arg = "";
        foreach ($pro_arg as $val)
        {
            $new_arg .= $val.",";
        }
        $pro_arg = rtrim($new_arg, ",");
    }

    if ($option["total"])
        $arg = "0, 0, ".$pro_arg;
    else if ($option["first"] && $option["list"])
        $arg = $option["first"].",".$option["list"].",".$pro_arg;
    else
        $arg = "0,10,".$pro_arg;

    $ret = $obj->query(" call ".$name."(".$arg.")");
//     dump($obj->getLastSql()); //开启调试代码

    if ($option["type"] == "col")
    {
        foreach ($ret[0] as $val)
        {
            return $val;
        }
    }
    else if ($option["type"] == "row")
        return $ret[0];

    return $ret;
}


//组合日期 处理出账单日或最后还款日30、29、31和月份对应关系
function combinationDate($year = "", $month = "", $day = "")
{
    if ($year == "" && $month == "" && $day == "")
        return date("Y-m-d");
    if ($year == "")
        $year = date("Y");
    if ($month == "")
        $month = date("m");
    if ($day == "")
        $day = date("d");

    return date("Y-m-d", strtotime("+".($day - 1)." day", strtotime($year."-"."$month"."-01")));
}

//中英混合字符串截取函数 utf8
function ksubstr($str, $len, $start = 0)
{
     return preg_replace('#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'.$start.'}'.
                         '((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'.$len.'}).*#s',
                         '$1',$str);

    //GB2312 汉字截取方法
//     $tmpstr = "";
//     $ch = "";
//     $slen = $start + $len;
//     for ($i = 0; $i < $slen; $i++)
//     {
//         $ch = substr($str, $i, 1);
//         if (ord($ch) > 0xA0)
//             $tmpstr .= substr($str, $i++, 2);
//         else
//             $tmpstr .= $ch;
//     }
//     return $tmpstr;
}

//获取中英字符串长度 只支持utf8
function kstrlen($str)
{
    preg_match_all("/./us", $str, $match);
    return count($match[0]);
}

//长文本在数据列表中显示情况
function show_big_txt($content, $size = 5)
{
//     dump(kstrlen($content));
    if (kstrlen($content) > $size)
    {
        //php没有把换行过滤，在js里换行必需要加\ 语法才能通过
        $str = str_replace("\n", "\\\n", $content);
        $val = '<span class="bigtxt_tooltip text-primary" style="cursor:pointer;" title="'.$str.'">';
        $val .= ksubstr($content, $size);
        $val .= '...</span>';
    }
    else
        $val = $content;
    return $val;
}

//过滤日期
function date_filter($holiday, $start_date, $start_num, $end_num)
{
    $days_array = array();  //保存期间财务工作日日期
    if (!$end_num)
        return NULL;

    if ($holiday == "")
        $holiday = get_holiday_array();

    //循环范围区间
    for ($i = $start_num, $total = 0; $i < $end_num; $i++)
    {
        //起始日期加上起始值得出要判断的时间戳、日期和星期
        $times = strtotime("+$i day", strtotime($start_date));
        $last_date = date("Y-m-d", $times);
        $week = date("w", $times);


        //如果此日期在假日列表中出现并且不为补假日期 或 此日期没有在假日列表中出现并且是星期五和星期六 则这些的日期过滤掉
        if ((array_key_exists($last_date, $holiday) && $holiday[$last_date] == "0") ||
        ($holiday[$last_date] != "1" && ($week == 5 || $week == 6)))
            continue;

        //保存此日期到数组中
        $days_array[$total++] = $last_date;
    }

    return $days_array;
}

//判断当前操作日是否在还款期并且获取到最后还款日还有多少天
function is_repay_date($card, & $end_num, $op_date = "")
{
    if ($op_date == "")
        $op_date = date("Y-m-d");
    $now_time = strtotime($op_date);
    $day = date("d", $now_time);

    //当前操作日大于等于出账单日，代表当前操作日可能在还款期或本期账单已经完成后在消费期
    if ($day >= $card["bill"])
    {
        $end_num = $card["finally_repayment_date"] - ($day - $card["bill"]);
        if ($end_num <= 0)  //本期账单已经结束的消费期内
            return 0;   //当前操作日不在还款期
        return 1;  //当前操作日在本月还款期
    }

    $cur_time = strtotime(date("Y", $now_time)."-".date("m", $now_time)."-".$card["bill"]);   //本期账单日时间戳
    $prev_time = strtotime("-1 month", $cur_time);  //上期账单日时间戳
    $prev_end_time = strtotime("+".$card["finally_repayment_date"]." day", $prev_time);  //上期账单日时间戳


    if ($now_time <= $prev_end_time)
    {
        $end_num = date("d", $prev_end_time - $now_time);
        return 2;  //当前操作日在上月还款期
    }
    $end_num = $card["finally_repayment_date"];  //如果账单日大于当前操作日，并且在消费期则新卡从本期账单开始
    return 0;  //当前操作日不在还款期
}

//根据假日列表更新指定信用卡的本年每个月就分期期数, $add为真代表首次录卡审核通过，为假代表假日设置，续约,编辑等
function update_card_installment($holiday, $card, $add = false, $op_date = "")
{
    $debug = false;
    $debug_return = array();  //调试返回生成的期数
    //如果假日没有传进来则获取假日列表
    if ($holiday == "" || $holiday == "test")
    {
        if ($holiday == "test")   //如果假日参数为test，则为调试测试模式，不会对数据库操作
            $debug = true;
        $holiday = get_holiday_array();
    }

    if ($op_date == "")
        $op_date = date("Y-m-d");
    $now_time = strtotime($op_date); //当前年月日时间戳
    $month = date("m", $now_time);  //当前月
    $year = date("Y", $now_time);  //当前年
    $day = date("d", $now_time);   //当前日

    $due_times = strtotime($card["due_date"]);  //获取到期日期时间戳
    $due_year = date("Y", $due_times);  //获取到期年
    $due_month = date("m", $due_times);  //获取到期月

    $obj = M("card_installment");

    //判断明年假日是否设置，如果假日设置了为真，否则为假
    $isholiday = isset($holiday[$year."-10-01"]) ? false : true;

    if (!$add)  //代表重新生成期数、续约或假日设置
    {
        //如果本月为12月并且没有设置假日，则不做任何操作，等待假日设置
        if (!$isholiday && $month == 12)
            return true;

        $save_date = $year."-".$month."-".$card["bill"];  //要保留的期数起始日期，默认为保留本月期数
        $save_time = strtotime($save_date);   //获取要保留的期数起始日期的时间戳

        $reset_isrepay = is_repay_date($card, $ispay, $op_date);
//         dump("判断还款消费期: ".$reset_isrepay.", 返回的间隔天数: ".$ispay);
        //如果$reset_isrepay == 1 代表当前操作日在本月还款期, 保留本月期数, 则生成期数月份从下月开始, 如果期数数据没有则为新卡录入，从本月开始
        if ($reset_isrepay == 1)
            $save_time = strtotime("+1 month", $save_time);
        else if ($reset_isrepay == 2)  //当前操作日在上月还款期, 保留上月期数, 则生成期数月份从本月开始, 如果期数数据没有则为新卡录入，从上月开始
            $save_date = date("Y-m-", strtotime("-1 month", $save_time)).$card["bill"];
        else   //is_repay_date返回值为0，代表在消费期
        {
//             if ($ispay <= 0)  //当前操作日为本月还款期已经结束的消费期内, 则从下月开始,
//                          但是由于要标识消费期，所以要从本月开始，但是本月没有数据作为标识
//                 $save_time = strtotime("+1 month", $save_time);
            //当前操作日在上一月的消费期内, 则从本月开始
            $add = true;
            $day = $card["bill"];
        }

        $year = date("Y", $save_time);
        $month = date("m", $save_time);

        if (!$add)   //如果在还款期内，才判断期数是否存在，如果存在，则按上面计算的月份，如果不存在，则让计算出的月份减一个月
        {
//             dump("续约要保留的期数起始日期：".$save_date);
            //查询要保留的期数是否存在，如果存在删除，不存在则当新卡操作
            if ($obj->where("code='".$card["code"]."' and start_date='".$save_date."'")->count())
            {
                //如果不为调试模式则删除其它期数
                if ($debug == false)
                //删除本卡期数并保留本期或上期期数
                    $obj->where("code='".$card["code"]."' and start_date!='".$save_date."'")->delete();
            }
            else
            {
                $add = true;    //如果要保留的期数不存在，则看成新卡操作
                if ($reset_isrepay == 1)
                {
                    $save_time = strtotime("-1 month", $save_time);
                    $year = date("Y", $save_time);
                    $month = date("m", $save_time);
                }
            }
        }
//         dump($year." ".$month);
    }

    if ($add && $debug == false)   //如果是新卡录入， 则删除所有此卡期数 , 防止重复开始日期
        $obj->where("code='".$card["code"]."'")->delete();

    //最后循环13个月 ,操作完一次月份自增一次
    for ($i = 1; $i <= 13; $i++, $month++)
    {
        //如果是新卡录入则判断本月是否生成期数
        if ($add && $i == 1)
        {
            $isrepay = is_repay_date($card, $end_num, $op_date);
            if ($isrepay)
                $bill_date = $year."-".$month."-".$day;
            else
                $bill_date = $year."-".$month."-".$card["bill"];
            $start_num = 1;
        }
        else
        {
            $bill_date = $year."-".$month."-".$card["bill"];
            $start_num = 1;//$card["repayment"]; // 在消费期T+1
            $end_num = $card["finally_repayment_date"];
        }

//         dump("bill_date: ".$bill_date);
        //过滤日期得出还款日期列表
        $days_array = date_filter($holiday, $bill_date, $start_num, $end_num);
        $total = count($days_array);

        if ($total == 0)
            continue;

        //随机范围为5个，起始数为8, 总数不大于8则不随机
        if ($total > 8)
        {
            $start_rand = $total - 5;
            if ($start_rand < 8)
                $start_rand = 8;
            $num = mt_rand($start_rand, $total);
        }
        else
            $num = $total;

        $array_key = array_rand($days_array, $num);
        $days = "";
        if (is_array($array_key))
        {
            foreach ($array_key as $key)
            {
                $days = $days."|".$days_array[$key];
            }
        }
        else
            $days = $days_array[$array_key];
//                 echo "$bill_date, num = $num, total = $total, $days <br />";
//                 echo "==============<br />";
        $data = array();
        $data["code"] = $card["code"];
        if ($add && $i == 1 && $isrepay == 2)    //如果首次录入操作日是在上期还款期内，则开始日期记录上期日期
        {
            if ($month == 1)
            {
                $year--;
                $month = 12;
            }
            else
                $month--;
        }
        $data["start_date"] = $year."-".$month."-".$card["bill"];
        $data["total"] = $total;
        $data["installment"] = $num;
        $data["days"] = $days;
        if ($debug)
            $debug_return[$i] = $data;
        else
        {
            $ret = $obj->add($data);
            if (!$ret)
            {
//                 echo ($obj->getLastSql());
//                 dump($data);
                return false;
            }
        }

        $cur_end_date = date("Y-m-d", strtotime("+ ".$card["finally_repayment_date"]." day",
                                strtotime($data["start_date"])));
        //期数只算到到期月，到期月也算在内, 所以如果到了到期年月则退出，如果明年设置未设置则到12月也退出
        //只要生成的期数当前有到期年月则退出
        if ($cur_end_date == $card["due_date"] || (!$isholiday && $month == 12))
//         if ((strstr($days, $due_year."-") && strstr($days, $due_month."-")) || (!$isholiday && $month == 12))
//         if (($due_year == $year && $due_month == $month) || (!$isholiday && $month == 12))
            break;
        //如果为12月，则下个月为下一年的一月，这里month为0, 是因为在for循环后面$month++
        if ($month == 12)
        {
            $year++;
            $month = 0;
        }
    }

    if ($debug)
        return $debug_return;
    return true;
}


//保存操作日志
//保存操作记录
function save_operating_record($code, $type, $remark = "")
{
    $data = array();
    $record_txt = C("RECORD_TEXT");
    $db = M("operating_record");

    $data["code"] = $code;
    $data["type"] = $type;
    if ($remark != "")
        $data["remark"] = $remark;
    else
        $data["remark"] = $_POST["remark"];

    if ($data["remark"] == "")
        $data["remark"] = $record_txt[$data["type"]];

    $data["sid"] = get_user_info("sid");
    $data["oper_group"] = get_user_info("admin");
    $data["opid"] = get_user_info("uid");
    $data["oper_time"] = date("Y-m-d H:i:s");

    if (sqlCol("select id from operating_record where code='".$data["code"]."' and opid=".$data["opid"]."
            and type='".$data["type"]."' and oper_time='".$data["oper_time"]."'"))
        return false;

    $ret = $db->create($data, 1);
    if (!ret || !$db->add())
        return false;
//     echo($db->getLastSql());
    return true;
}



//判断某个值是否在一个列表中
function is_range($val, $r = array())
{
    foreach ($r as $v)
    {
        if ($val == $v)
            return true;
    }
    return false;
}



//对js代码加js头信息
function js_head($code)
{
    $js = '<script type="text/javascript">';
    $js .= $code;
    $js .= '</script>';
    return $js;
}




//拓展员佣金结算记录推送
function sales_bonus($eid, $code, $amount, $type = 3)
{
    $data = array();
    $data["eid"] = $eid;
    $data["date_id"] = sqlCol("select date_id from expand_bonus where stype=".$type." and state=1 and eid=".$eid);
    if (!$data["date_id"])
        $data["date_id"] = date("ymdhis");
    $data["stype"] = $type;   //标识佣金类型   3为签单佣金  4为增值佣金 6 续约佣金
    $data["state"] = 1;    //标识为结算状态 1为未结算 0为已结算
    $data["amount"] = $amount;    //额度
    $data["card_code"] = $code;
    $data["create_time"] = getcurtime();
    $data["update_time"] = $data["create_time"];
    $obj = M("expand_bonus");
    $ret = $obj->create($data, 1);
    if (!ret || !$obj->add())
        return false;
    return true;
}



//自动发送站内信
function auto_send_msg($title, $content, $recv_group = 1)
{
    if ($content == "")
        $content = $title;
    $data = array();
    $data["mess_type"] = "系统消息";
    $data["title"] = $title;
    $data["content"] = $content;
    $data["sender"] = 2;
    $data["proxy"] = get_user_info("proxy_id");
    $data["sid"] = get_user_info("sid");
    $data["grp"] = ",".$recv_group.",";
    $data["recv_type"] = 0;
    $data["send_time"] = getcurtime();
    if (M("message_text")->add($data))
        return true;
    return false;
}



//组合站内信链接
function parse_msg_link($url, $txt ="点击这里", $tag = "#body")
{
    return '<a href="#" url="'.$url.'" tag="#body"
            callback=\'$("#pop_win1").find(".pop_close").click();\'>'.$txt.'</a>';
}


//保存用户操作记录  登录 登出 修改密码 被踢线等
function save_user_record($type, $opid = "", $login_name = "", $remark = "", $opIp = "", $opAddr = "")
{
    $data = array();
    $record_txt = C("RECORD_TEXT");
    $db = M("users_record");

    $data["op_type"] = $type;
    $data["login_name"] = $login_name;
    $data["remark"] = $remark;
    $data["opid"] = $opid;
    $data["op_ip"] = $opIp;
    $data["op_addr"] = $opAddr;

    if ($opid == "")
        $data["opid"] = get_user_info("uid");

    if ($login_name == "")
        $data["login_name"] = sqlCol("select login_name from users where id=".$data["opid"]);

    if ($data["remark"] == "")
        $data["remark"] = $record_txt[$data["op_type"]];

    if ($opIp == "")
        $data["op_ip"] = get_client_ip(1);
    if ($opAddr == "")
    {
        $ip = new \Org\Net\IpLocation();
        $op_addr = $ip->getlocation($data["op_ip"]);
        $op_addr = $op_addr["country"] . $op_addr["area"];
        $data["op_addr"] = $op_addr;
    }
    $data["op_time"] = date("Y-m-d H:i:s");

    $ret = $db->create($data, 1);
    if (!ret || !$db->add())
        return false;
//     echo($db->getLastSql());
    return true;
}


//随机密码
function rand_pwd($len = 6)
{
    $pwd = "";
    for ($i = 0; $i < $len; $i++)
    {
        $n = mt_rand(0, 2);
        if ($n == 0)
            $pwd .= chr(mt_rand(97, 123));
        else if ($n == 1)
            $pwd .= chr(mt_rand(65, 91));
        else
            $pwd .= mt_rand(0, 9);
    }
    return $pwd;
}

