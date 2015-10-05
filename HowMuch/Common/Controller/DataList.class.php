<?php
namespace Common\Controller;

// 数据列表类
class DataList
{
    private $obj;       //数据对象
    private $page;      //分页对象
    private $index;
//            "data_title"     记录title数组下标 个数
//            "data_field"     记录field数组下标个数
//            "data_op"        记录Op的数组下标个数
    private $list = array();

//     $obj_type;          //数据对象类型  0 为表名  1为 模型
    public function __construct($name, $obj_type = 0, $option = "")
    {
        if ($obj_type == 0)
            $this->obj = M($name);
        else if ($obj_type == 1)
            $this->obj = D($name);
        else
            return false;

        $this->set();

        $this->list["did"] = $name;

        $this->list["obj_type"] = $obj_type;

        if ($option != "" && is_array($option))
        {
            foreach ($option as $key => $val)
            {
                $this->list[$key] = $val;
            }
        }

        if (isset($_GET["find"]) && $_GET["find"] != "" && IS_POST)   //如果是查询表单提交则把查询条件保存到session find_where变量里
        {
            session("find_where", null);  //只要是查询重新提交则清空find_where变量
            $sess = array();
            foreach (I("post.") as $key => $val)
            {
                if ($key == "search_key" || $key == "search_type")
                    continue;
                if (strstr($val, ","))
                    $sess[$key] = array('between', $val);
                else if ($val != "")
                    $sess[$key] = $val;
            }
            if (is_array(I("post.search_key")))
                $sess[I("post.search_type")] = I("post.search_key");
            else if (I("post.search_type") && I("post.search_key"))
                $sess[I("post.search_type")] = array("like", '%'.I("post.search_key").'%');
            session("find_where", $sess);
        }
        //如果即没有find又没有p代表即没有查询而且是在首页 则清空初始化find_where变量
        else if (!I("get.find") && !I("get.p") && !I("get.form") && !I("get.order"))
            session("find_where", null);

//         session("prev_url".$name, __SELF__);
        if (I("get."))
        {
            if (!I("get.form"))
                session("prev_url".$this->list["did"], __SELF__);
            if (I("get.ibody"))
                session("prev_url".$this->list["did"], str_replace(".html", "/p/1.html", __SELF__));
        }
        else
            session("prev_url".$this->list["did"], str_replace(".html", "/p/1.html", __SELF__));

        //设置默认分页局部刷新目标为本次数据列表的描述符
        if (!isset($this->list["page"]["tag"]))
            $this->list["page"]["tag"] = ".".$this->list["did"];
//         dump($this->list["page"]["tag"]);

        $this->page = new KyoPage($this->list["page"]);

        return true;
    }

    public function __destruct()
    {
//         session("prev_url".$this->id, null);
    }

    //设置数据列表配置  不支持赋数组值
    public function set($name = "", $value = "")
    {
        if ($name == "" && "" == $value)
        {
//             $this->list["did"] = "";
            $this->list["close_top_page"] = 0;
            $this->list["close_down_page"] = 0;
            $this->list["close_num"] = 0;
            $this->list["close_chkall"] = 0;
            $this->list["close_op"] = 0;
            $this->list["close_data_div"] = 0;
            $this->list["close_op_info"] = 1;
            $this->list["close_op_edit"] = 1;
            $this->list["close_op_del"] = 1;
            $this->list["data_class"] = "";
            $this->list["data_title"] = "";
            $this->list["tooltip"] = 0;
            $this->list["my_data_title"] = "";
            $this->list["front"] = "";
            $this->list["end"] = "";
            $this->list["data_list"] = "";
            $this->list["my_data_list"] = "";
            $this->list["data_table_class"] = "table-bordered table-hover table-condensed";
            $this->list["page"] = "";
            $this->list["page"]["size"] = C("PAGE_SIZE");
            $this->list["use_sql"] = false;
            $this->list["empty_html"] = '<td class="empty_data_html" colspan="100">暂无数据!</td>';
            $this->list["chkVal"] = false;
            $this->list["title_ext_front"] = "";
            $this->list["title_ext_end"] = "";
            $this->list["data_field"] = "";
            $this->list["title_call"] = "";
            $this->list["op_call"] = "";
            $this->list["field_call"] = "";
            $this->list["chk_call"] = "";
            $this->list["opchk_call"] = "";
            $this->list["data_op"] = "";
            $this->list["where"] = "1";
            $this->list["order"] = "";
            $this->list["excel"] = "";
            $this->list["subtotal"] = "";
            $this->list["data_cols"] = 0;
            $this->list["formatfield"] = "";

            $this->index["data_title"] = 0;
            $this->index["data_field"] = 0;
            $this->index["data_op"] = 0;
            return true;
        }

        $name_sel = explode(" ", $name);
        $name_len = count($name_sel);
        if ($name_len == 1)
            $this->list[$name] = $value;
        else
        {
            $obj = & $this->list;
            for ($i = 0; $i < $name_len; $i++)
            {
                $obj = & $obj[$name_sel[$i]];
            }

            $obj = $value;
        }

        if ($name == "did")
            $this->list["page"]["tag"] = ".".$value;

        if (!strstr($name, "my_data_list") && strstr($name, "data_list"))
            $this->list["use_sql"] = true;
    }

    //设置数据标题和字段隐藏
    public function hide($field_ind)
    {
        if (is_array($field_ind))
        {
            foreach ($field_ind as $index)
            {
                $this->set("data_title $index hide", true);
                $this->set("data_field $index hide", true);
            }
        }
        $this->set("data_title $field_ind hide", true);
        $this->set("data_field $field_ind hide", true);
    }

    //获取模型对象, 为了支持空模型操作或外部sql操作
    public function Obj()
    {
        return $this->obj;
    }

    //获取数据列表类配置参数,为了支持自定义操作
    public function get($name = "")
    {
        if ($name == "")
            return $this->list;

        $name_sel = explode(" ", $name);
        $name_len = count($name_sel);
        if ($name_len == 1)
            return $this->list[$name];

        $obj = & $this->list;
        for ($i = 0; $i < $name_len; $i++)
        {
            $obj = & $obj[$name_sel[$i]];
        }

        return $obj;
    }

    private function parse_title_sort()
    {
        if (!I("get."))
            session("orderBy", null);
        if ($this->list["order"])
            session("orderBy", $this->list["order"]);
        if (I("get.order"))
            session("orderBy", I("get.order"));
        foreach ($this->list["data_title"] as $key => $val)
        {
            if (isset($val["sort"]) && $val["sort"] != "")
            {
                if (!isset($this->list["data_title"][$key]["ext"]))
                    $this->list["data_title"][$key]["ext"] = "";
                $this->list["data_title"][$key]["url"] = U();
                $this->list["data_title"][$key]["tag"] = ".".$this->list["did"];
                $this->list["data_title"][$key]["ext"] .= 'sort="'.$val["sort"].'"';
                $caret_up = strstr(session("orderBy"), " desc") ? "" : "caret_up";
                $this->list["data_title"][$key]["end"] = '<span class="caret '.$caret_up.'"></span>';
            }
        }
    }

    //组合导出报表数据
    public function parse_excel_data()
    {
//         dump($_SESSION["excel"]);
        $_SESSION["excel"] = null;
//         session("excel", null);
        if (!$_SESSION["excel"])
        {
            karray($this->list["excel"], array("name", "title", "sql", "call"));
            $_SESSION["excel"]["filename"] = $this->list["excel"]["name"];
            if (!$this->list["excel"]["title"])
            {
                $title = array();
                foreach ($this->list["data_title"] as $key => $row)
                {
                    $title[$this->list["data_field"][$key]["txt"]] = $row["txt"];
                }
                $_SESSION["excel"]["title"] = $title;
            }
            else
                $_SESSION["excel"]["title"] = $this->list["excel"]["title"];
            if ($this->list["excel"]["sql"])
                $_SESSION["excel"]["sql"] = $this->list["excel"]["sql"];
            if (!$this->list["excel"]["data"])
            {
                $sql = str_replace("COUNT(*) AS tp_count", "*", $this->list["excel"]["sql"]);
                $_SESSION["excel"]["sql"] = str_replace("LIMIT 1", "", $sql);
            }
            else
                $_SESSION["excel"]["data"] = $this->list["excel"]["data"];
            $_SESSION["excel"]["callback"] = $this->list["excel"]["call"];
//             dump($_SESSION["excel"]);
        }
    }

    //组合数据列表类Html代码, 支持外部模板
    public function fetch()
    {
        $this->parse_title_sort();

        if (!$this->list["use_sql"])
        {
//             dump(session("find_where"));
//             dump($this->list["where"]);
            $this->list["data_list"] = $this->obj
                        ->where(session("find_where"))
                        ->where($this->list["where"])
                        ->order(session("orderBy"))
                        ->page(I("get.p", 1).','.$this->list["page"]["size"])
                        ->select();
//             dump($this->obj->getLastSql());
//             exit(0);

            $this->list["page"]["total"] = $this->obj->where(session("find_where"))
                                            ->where($this->list["where"])->count();
            if ($this->list["excel"] != "" && !$this->list["excel"]["sql"])
                $this->list["excel"]["sql"] = $this->obj->getLastSql();
//             dump($this->obj->getLastSql());
        }
        parse_link_html($this->list["data_title"]);
        parse_link_html($this->list["data_field"]);
        parse_link_html($this->list["data_op"]);
//         dump($this->list["use_sql"]);

        $this->page->set($this->list["page"]);
        $this->list["data_page"] = $this->page->fetch();

//         dump($this->list["excel"]);
        if ($this->list["excel"])
            $this->parse_excel_data();

//         if (isset($this->list["my_data_list"]))
//         {
//             $this->assign($this->list);
//             $this->list["my_data_list"] = parent::fetch($this->list["my_data_list"]);
//         }

//         if ($templatefile == "")
//             $templatefile = T('KyoCommon@Public/list_data');

        //如果url有带p或find代表查询或分页，只需要提供数据
        if ((I("get.p") || I("get.find") || I("get.order") ||
                (I("get.by") && I("get.data"))) && !I("get.small"))
        {
//             dump(I("get.p"));
            $this->list["close_data_div"] = 1;
//             $this->assign($this->list);
//         dump($this->html());
            echo $this->html();
//             echo parent::fetch($templatefile);
            exit(0);
        }
//         else
//             $this->assign($this->list);
        session("orderBy", null);
//         dump(parent::fetch($templatefile));

        //dump($this->html());
        return $this->html();
//         return parent::fetch($templatefile);
    }

    //设置分页配置

    public function setPage($name, $value)
    {
        if ($name == "" || $value == "")
            return false;

        if ($name == "size")
        {
            $option["size"] = $value;
            $this->page->set($option);
        }

        $this->list["page"][$name] = $value;

        return true;
    }

    public function getPage()
    {
        return " limit ".$this->page->firstRow.",".$this->page->listRows;
    }

    public function setCustomList($pro_name, $total = true, $pro_arg = "")
    {
        if ($pro_arg && is_array($pro_arg))
        {
            $new_arg = "";
            foreach ($pro_arg as $val)
            {
                $new_arg .= $val.",";
            }
            $pro_arg = rtrim($new_arg, ",");
        }

        if ($total)
        {
            $str = $this->page->firstRow.",".$this->page->listRows;
            $tol = "0,99";
            if ($pro_arg)
            {
                $str .= ",".$pro_arg;
                $tol .= ",".$pro_arg;
            }
            $sqlTotal = $this->obj->query("call ".$pro_name."(".$tol.")");
            $this->list["page"]["total"] = $sqlTotal[0]["total"];
//             dump($this->list["page"]["total"]);
//        dump("total:".$this->obj->getLastSql()); //开启调试代码
        }
        else
        {
            $str = "0,10,".$pro_arg;
            $this->list["page"]["total"] = 0;
        }

        $this->set("data_list", $this->obj->query("call ".$pro_name."(".$str.")"));
        if ($this->list["excel"])
            $this->list["excel"]["data"] = $this->obj->query("call ".$pro_name."(0,10000,".$pro_arg.")");
//         dump($this->list["excel"]);
//         dump($this->obj->getLastSql()); //开启调试代码

        return true;
    }

    private function _setCommon($opName, $title, $option = array())
    {
        if (is_array($title))
        {
            foreach ($title as $key => $val)   //第一个参数为数组 则循环数组  setTitle(array(...));
            {
                if (is_array($val))
                {
                    //数组的值是否为数组  setTitle(array(array(...)...));
                    foreach ($val as $opkey => $opval)
                    {
                        //如果第二维数组中有key值为name，则为此二维的句柄, 如果没有name则二维的句柄为数组数字下标从0开始
                        if ($opkey == "name")
                            $key = $opval;
                        else
                            $this->list[$opName][$key][$opkey] = $opval;
                    }
                }
                else  //如果值不为数组，则开辟二维数组
                    $this->list[$opName][$key]["txt"] = $val;

                $this->index[$opName]++;    //记录有多少个成员
            }
        }
        else
        {
            $index = $this->index[$opName]++;
            foreach ($option as $key => $val)
            {
                if ($key == "name")
                    $index = $val;
                else
                    $this->list[$opName][$index][$key] = $val;
            }
            $this->list[$opName][$index]["txt"] = $title;
        }

        return true;
    }

    //设置数据标题
    // 支持单个标题设置
    // 支持批量标题设置
    // 支持单个标题批量配置
    public function setTitle($title, $option = array())
    {
        return $this->_setCommon("data_title", $title, $option);
    }

    //设置数据列表显示字段
    public function setField($field, $option = array())
    {
        return $this->_setCommon("data_field", $field, $option);
    }

    public function setOp($txt, $url, $option = array())
    {
        if ("" == $txt || "" == $url)
            return false;

        $option["url"] = $url;

        return $this->_setCommon("data_op", $txt, $option);
    }

    public function readTpl($templatefile, $tplVal = "")
    {
        if ("" == $tplVal)
            $this->assign($this->list);
        else
            $this->assign($tplVal);
        return parent::fetch($templatefile);
//         $templatefile = MODULE_PATH."View/".$templatefile.".html";
//         dump($templatefile);
//         return file_get_contents($templatefile);
    }

    //脱离模板生成数据列表的标题
    public function title_html()
    {
        $l = $this->list;
        $html = "";

        if ($l["my_data_title"])
            $html .= $l["my_data_title"];
        else
        {
            $html .= $l["title_ext_front"];
            $html .= '<tr>';
            if ($l["close_num"] != 1)
            {
                $this->list["data_cols"]++;
                $html .= '<th>序号</th>';
            }
            if ($l["title_call"])
            {
                if ($l["title_call"][0] == "run")
                    $html .= R($l["title_call"][1], array($l["data_title"]));
                else
                    $html .= $l["title_call"][1]($l["data_title"]);
            }
            else
            {
                foreach ($l["data_title"] as $vo)
                {
                    karray($vo, array("class", "front", "txt", "end", "hide"));
                    if ($vo["hide"])
                        continue;
                    if (isset($vo["url"]))
                        $html .= '<th class="'.$vo["class"].'"><a href="'.$vo["href"].
                        '" '.$vo["link"].'>'.$vo["front"].''.$vo["txt"].''.$vo["end"].'</a></th>';
                    else
                        $html .= '<th class="'.$vo["class"].'">'.$vo["front"].''.$vo["txt"].''.$vo["end"].'</th>';
                    $this->list["data_cols"]++;
                }
            }
            if ($l["close_op"] != 1)
            {
                $this->list["data_cols"]++;
                $html .= '<th>操作</th>';
            }

            if ($l["close_chkall"] != 1)
            {
                $this->list["data_cols"]++;
                $html .= '<th><input id="chkall_id" type="checkbox" chktype="'.$l["chkVal"].'" name="chkall" /></th>';
            }
            $html .= '</tr>'.$l["title_ext_end"];
        }

        return $html;
    }

    static public function formatField($data, $key, $flag = "")
    {
        $val = "";
        switch ($key)
        {
        	case "identity":
                $val = format_dis_field($data[$key], array(6, 8, 4));
        	    break;
        	case "finally_repayment_date":
                if (strstr($data[$key], "T"))
                    $val = $data[$key];
                else
                    $val = "T + ".$data[$key];
        	    break;
        	case "phone1":
        	case "phone2":
        	case "phones":
        	case "uphones":
        	case "bphones":
                $val = format_dis_field($data[$key], array(3, 4, 4));
        	    break;
        	case "account_type":
                $repay_type_name = array("0" => "POS账户","1" => "备付金账户");
                if ($data[$key] == 0 || $data[$key] == 1)
                    $val = $repay_type_name[$data[$key]];
                else
                    $val = $data[$key];
                break;
        	case "card":
        	case "rcard":
        	case "out_card":
                $val = format_dis_field($data[$key]);
                break;
        	case "put_card":
                if ($data[$key] == 1 || $data[$key] == "备付金账户")
                    $val = "备付金账户";
                else
                    $val = format_dis_field($data[$key]);
        	    break;
        	case "sex":
                $val = get_sex_txt($data[$key]);
        	    break;
        	case "input":
                $val = get_bool_txt($data[$key]);
        	    break;
        	case "status":
                $val = get_status_txt($data[$key]);
                if ($data[$key] == ERROR)
                    $val = '<span class="kyo_red">'.$val.'</span>';
        	    break;
        	case "sid":
                $val = get_sub_name($data[$key]);
        	    break;
        	case "eid":
        	case "opid":
        	case "typing":
                $val = get_username($data[$key]);
        	    break;
        	case "bid":
                $val = get_basisname($data[$key]);
        	    break;
        	case "signing":
        	case "award":
        	case "bonus":
        	case "cost":
        	case "rising_cost":
        	case "expand_cost":
        	case "renewal_cost":
        	case "card_cost":
        	case "pos_cost":
        	case "bonus_cost":
        	case "rising_cost":
                $val = field_conv_per($data[$key]);
        	    break;
        	case "rmb":
                if (is_numeric($data[$key]))
            	    $val = sprintf("%.1f", $data[$key]);
                else
                    $val = $data[$key];
                break;
        	case "remark":
        	case "bank_addr":
                if ($flag == "excel")
                    $val = $data[$key];
                else
                    $val = show_big_txt($data[$key]);
        	    break;
        	case "txt":
        	case "title":
                $val = show_big_txt($data[$key], 30);
        	    break;
        	case "days":
                $val = ltrim($data[$key], "|");
                $val = str_replace("|", "&emsp;", $val);
        	    $val = show_big_txt($val, 122);
                break;
        	default:
                $val = $data[$key];
        	    break;
        }
        return $val;
    }

    public function field_html($vo)
    {
        $l = $this->list;
        $html = "";

        if (isset($l["field_call"]) && $l["field_call"] != "")
        {
            if (isset($l["field_call"][0]) && $l["field_call"][0] == "run")
                $html .= isset($l["field_call"][1]) ? R($l["field_call"][1], array($vo, $l["data_field"])) : "";
            else
                $html .= isset($l["field_call"][1]) ? $l["field_call"][1]($vo, $l["data_field"]): "";
        }
        else
        {
            $fkey = -1;
            foreach ($l["data_field"] as $fd)
            {
                karray($fd, array("class", "front", "href", "link", "end", "fun", "run", "txt"));
                if (!isset($fd["hide"]) || $fd["hide"] != 1)
                {
                    $fkey++;
                    $html .= '<td class="'.$fd["class"].'">';
                    parse_link($fd["front"], $vo);
                    $html .= $fd["front"];

                    if ($fd["link"] || ($fd["href"] && $fd["href"] != "#"))
                    {
                        parse_link($fd["link"], $vo);
                        parse_link($fd["href"], $vo);
                        $html .= '<a href="'.$fd["href"].'" '.$fd["link"].'>';
                    }

                    if ($fd["fun"])
                        $html .= $fd["fun"]($vo[$fd["txt"]]);
                    else if ($fd["run"])
                        $html .= R($fd["run"], array(& $vo, $fd["txt"]));
                    else
                    {
                        if ($l["formatfield"])
                            $html .= R($l["formatfield"], array(& $vo, $fd["txt"]));
                        else
                            $html .= $this->formatField($vo, $fd["txt"]);
                    }

                    if ($fd["link"])
                        $html .= '</a>';

                    parse_link($fd["end"], $vo);
                    $html .= $fd["end"];

                    if ($l["subtotal"] && is_range($fkey, $l["subtotal"]["field"]))
                    {
                        $match = array();
                        $val = $vo[$fd["txt"]];
//                         dump($fkey." ".$val);
                        if (!preg_match("/^[\\-0-9. ]+$/", $val))
                        {
                            preg_match_all("/\>([\\-0-9. ]+)\</", $val, $match);
                            if (count($match[1]) == 1)
                                $val = $match[1][0];
                            else
                                continue;
                        }
                        if (strlen($val) > 11)
                            continue;

                        if (!isset($this->list["subtotal"]["total"][$fkey]))
                            $this->list["subtotal"]["total"][$fkey] = $val;
                        else if (!strstr($val, "-"))
                            $this->list["subtotal"]["total"][$fkey] += $val;
                    }
                }
            }
        }

        return $html;
    }

    public function op_html($vo)
    {
        $l = $this->list;
        $html = "";

        if ($l["opchk_call"])
        {
            if ($l["opchk_call"][0] == "run")
                $html .= R($l["opchk_call"][1], array($vo, $l["data_op"]));
            else
                $html .= $l["opchk_call"][1]($vo, $l["data_op"]);
        }
        else
        {
            //列表操作链接
            if ($l["close_op"] != 1)
            {
                $html .= '<td>';
                if ($l["op_call"])
                {
                    if ($l["op_call"][0] == "run")
                        $html .= R($l["op_call"][1], array($vo, $l["data_op"]));
                    else
                        $html .= $l["op_call"][1]($vo, $l["data_op"]);
                }
                else
                {
                    $j = 0;
                    if ($l["data_op"])
                    {
                        foreach ($l["data_op"] as $op)
                        {
                            karray($op, array("class", "href", "link", "txt", "end"));
                            if (++$j != 1)  //如果不是第一个操作则先打印两个空格
                                $html .= "&nbsp;&nbsp;";
                            parse_link($op["href"], $vo);
                            parse_link($op["link"], $vo);
                            parse_link($op["front"], $vo);
                            parse_link($op["end"], $vo);
                            $html .= $op["front"];
                            $html .= '<a href="'.$op["href"].'" class="'.$op["class"].'" '.
                                    $op["link"].'>'.$op["txt"].'</a>'.$op["end"];
                        }
                    }
                }
                $html .= '</td>';
            }

            //列表选择框
            if ($l["close_chkall"] != 1)
            {
                $html .= '<td>';
                if  ($l["chk_call"])
                {
                    if ($l["chk_call"][0] == "run")
                        $html .= R($l["chk_call"][1], array($vo));
                    else
                        $html .= $l["chk_call"][1]($vo);
                }
                else
                {
                    if ($l["chkVal"] == 1)
                        $html .= '<input type="checkbox" name="opChkId" value="'.$vo["code"].'" />';
                    else
                        $html .= '<input type="checkbox" name="opChkId" value="'.$vo["id"].'" />';
                }
                $html .= "</td>";
            }
        }
        return $html;
    }

    //脱离模板生成html代码
    public function html()
    {
        $l = $this->list;
        $html = "";

        if ($l["close_data_div"] != 1)
            $html .= '<div class="kyo_data_list '.$l["did"].' '.$l["data_class"].'">';

        if ($l["close_top_page"] != 1)
            $html .= '<div class="hidden-xs">'.$l["data_page"].'</div>';

        $html .= '<div class="table-responsive">';
        $html .= '<table class="table '.$l["data_table_class"].' kyo_table_list">';
        $html .=  '<thead>';
        $html .= '</thead><tbody>';

        $html .= $this->title_html();
//         return $html;

        if ($l["my_data_list"])
            $html .= $l["my_data_list"];
        else
        {
            if ($l["data_list"])
            {
                $i = 0;
                foreach ($l["data_list"] as $vo)
                {
                    if (isset($l["tr_call"]))
                    {
                        if ($l["tr_call"][0] == "run")
                            $html .= R($l["tr_call"][1], array($vo));
                        else
                            $html .= $l["tr_call"][1]($vo);
                    }
                    else
                        $html .= '<tr>';

                    if ($l["close_num"] != 1)
                        $html .= '<td>'.++$i.'</td>';

                    $html .= $this->field_html($vo);

                    $html .= $this->op_html($vo);
//                     return $html;
//                     exit(0);
                }
                if ($l["subtotal"])
                {
                    $html .= '<tr><td>小计</td>';
                    $data_cols = $this->list["data_cols"] - 1;
                    for ($y = 0; $y < $data_cols; $y++)
                    {
                        $html .= '<td>';
                        if ($this->list["subtotal"]["total"][$y])
                            $html .= '<span class="kyo_red">'.$this->list["subtotal"]["total"][$y].'</span>';
                        $html .= '</td>';
                    }
                    $html .= '</tr>';
                }
            }
            else
                $html .= '<tr>'.$l["empty_html"].'</tr>';

        }
        $html .= '</tbody></table></div>';

        if ($l["close_down_page"] != 1)
            $html .= $l["data_page"];

        $html .= $l["end"];

        if ($l["close_data_div"] != 1)
            $html .= '</div>';

        if ($l["tooltip"])
        {
            $js_code = '$(".bigtxt_tooltip").jBox("Tooltip", {
//                             trigger: "click",
//                             closeOnClick:"body",
                            theme: "TooltipBorder",
                            animation: "pulse"
                         });';
            $html .= js_head($js_code);
        }

        return $html;
    }
}

?>
