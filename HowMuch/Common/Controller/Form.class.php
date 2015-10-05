<?php
namespace Common\Controller;
use Think\Controller;

// 表单类
class Form extends Controller
{
    public $form = array();
    public $index;
    public $col = array(
            "1" => array("label" => "col-xs-3 col-sm-3 col-md-3", "element" => "col-ss-12 col-xs-8 col-sm-8 col-md-8", "group" => "col-sm-4 col-md-4"),
            "2" => array("label" => "col-sm-2 col-md-2", "element" => "col-sm-3 col-md-3", "group" => "col-sm-2 col-md-2"),
    );

    public function __construct($data = "", $option = array())
    {
        parent::__construct();
        init_array($this->form, array("name", "action", "target", "class", "me", "callback", "validity",
                        "btn", "cols", "js", "type", "auto", "return", "dataObj", "table", "handle_run",
                        "element", "counter"));
        init_array($this->form, array("close_btn_up", "close_btn_down"), 0);
        $this->form["name"] = "kform";
//         $this->form["action"] = U();

        $this->form["cols"] = 1;
        $this->form["close_btn_up"] = 1;
        $this->form["close_btn_down"] = 0;
        $this->form["class"] = "form-horizontal kyo_form";
        $this->form["kajax"] = "true";
        $this->form["ktype"] = "form";
        $this->form["return"] = array();
        $this->form["auto"] = array();
        $this->form["validate"] = array();
        $this->form["handle_run"] = array();

        if ($data)
        {
            $this->form["dataObj"] = $data->Obj();
            $this->form["table"] = $data->get("did");
            $this->form["return"]["url"] = session("prev_url".$data->get("did"));
            if (strstr(session("prev_url".$data->get("did")), "by=") &&
                  !strstr(session("prev_url".$data->get("did")), "&data=1"))
                $this->form["return"]["url"] .= "&data=1";

            //dump($this->form["return"]["url"]);

            $this->form["return"]["tag"] = ".".$data->get("did");
        }
        else
            $this->form["table"] = "Others";

        //dump($this->form["return"]["url"]);

        $this->form["return"]["echo"] = 1;
        $this->form["return"]["close"] = 1;

        foreach ($option as $key => $val)
        {
            $this->form[$key] = $val;
        }

        if (kempty($option, "type", "info"))

        {
            $this->form["class"] .= " kyo_info_form";
            $this->form["ktype"] = "info";
        }

        $this->index["btn"] = 0;
        $this->index["element"] = 0;

        $this->setBtn("添加", "add", array("ext" => 'type="submit"'));

        return true;
    }

    //设置数据列表配置
    public function set($name, $value)
    {
        if ($name == "")
            return false;

        $name_sel = explode(" ", $name);
        $name_len = count($name_sel);
        if ($name_len == 1)
            $this->form[$name] = $value;
        else
        {
            $obj = & $this->form;
            for ($i = 0; $i < $name_len; $i++)
            {
                $obj = & $obj[$name_sel[$i]];
            }

            $obj = $value;
        }

        if ($name == "cols")
            $this->form["label_cols"] = $this->col[$this->form["cols"]]["label"];

        return true;
    }

    //获取数据列表类配置参数,为了支持自定义操作
    public function get($name = "")
    {
        if ($name == "")
            return $this->form;

        $name_sel = explode(" ", $name);
        $name_len = count($name_sel);
        if ($name_len == 1)
            return $this->form[$name];

        $obj = & $this->form;
        for ($i = 0; $i < $name_len; $i++)
        {
            $obj = & $obj[$name_sel[$i]];
        }

        return $obj;
    }

    //设置表单元素显示顺序
    public function setElementSort($sort)
    {
        $new_array = array();

        foreach ($sort as $vo)
        {
            $new_array[$vo] = $this->form["element"][$vo];
        }
        foreach ($this->form["element"] as $el)
        {
            if ($el["type"] == "hidden")
                $new_array[$el["name"]] = $el;
        }

        $this->form["element"] = $new_array;
    }

    public function setInfoElement($name, $title, $value, $option = array())
    {
        $def_options = array(
            "value"  => '<span style="font-weight:bold;">'.$title.':</span>&emsp;'.$value,
            "close_label" => 1,
            "pclass" => "kyo_element_info col-ss-10 col-xs-10 col-sm-8 col-md-8 col-xs-offset-2 col-sm-offset-4 col-md-offset-4"
        );
        foreach ($option as $key => $val)
        {
            $def_options[$key] = $val;
        }
        $this->setElement($name, "static", "", $def_options);
    }

    public function setElement($name, $type, $txt, $option = array())
    {
        if ($name == "" || $type == "")
            return false;

        if (strstr($name, " "))
            return $this->set("element ".$name, $type);

        $this->form["element"][$name]["name"] = $name;
        $this->form["element"][$name]["type"] = $type;
        $this->form["element"][$name]["old_type"] = $type;
        $this->form["element"][$name]["txt"] = $txt;

        foreach ($option as $key => $val)
        {
            if ($key == "bool" && strchr($val, "uniq"))
                $this->form["validate"][$name] = array($name, "", $txt."已经存在!", 0, 'unique', 3);

            $this->form["element"][$name][$key] = $val;
        }
    }

    private function _setCommon($opName, $txt, $url, $option = array())
    {
        $index = $this->index[$opName]++;

        foreach ($option as $key => $val)
        {
            if ($key == "name")
                $index = $val;
            else
                $this->form[$opName][$index][$key] = $val;
        }
        $this->form[$opName][$index]["txt"] = $txt;
        $this->form[$opName][$index]["url"] = $url;

        return true;
    }

    public function setBtn($txt, $url, $option = array())
    {
        $this->form["close_btn_down"] = 0;
        if (!isset($option["end"]))
            $option["end"] = "&nbsp;&nbsp;";
        return $this->_setCommon("btn", $txt, $url, $option);
    }

    //专门针对表单操作表相关计数器操作
    private function countHandle($dataObj, $con, $op = "add")
    {
        $obj = $dataObj;
        $num = 1;
        if ($op == "add")
            $conf = "addNum";
        else
            $conf = "decNum";

        if (is_array($con))
            $data = $con;
        else
            $data = $dataObj->where($con)->find();

        foreach ($this->form[$conf] as $row)
        {
//             0 == table
//             1 == name
//             2 == where
//             3 == op
//             4 == num
            if (kempty($row, 0) || kempty($row, 2))
                continue;

            parse_link($row[2], $data);

            if (isset($row[0]) && $row[0] != "")
                $obj = M($row[0]);

            if (isset($row[4]))
                $num = $row[4];

            if ($op == "add")
            {
                if(isset($row[3]) && $row[3] == 2)
                    $obj->where($row[2])->setDec($row[1], $num);
                else
                    $obj->where($row[2])->setInc($row[1], $num);
            }
            else
            {
                if(isset($row[3]) && $row[3] == 1)
                    $obj->where($row[2])->setInc($row[1], $num);
                else
                    $obj->where($row[2])->setDec($row[1], $num);
            }
        }
        return true;
    }

    //添加用户时产生内部编码和登录信息
    private function buildUserBase()
    {
        $usr_name = array(1 => "finance", 2 => "operator", 3 => "salesman", 4 => "employee",
                            5 => "customer", 6 => "sub", 8 => "proxy");
        if (isset($_POST["type"]) && $this->form["table"] == "users")
        {
            $type = $_POST["type"];
            $tid = "";

            if ($type >= 1 && $type <= 5)
            {
                $tid = $_POST["sid"];
                $_POST["proxy_sub_name"] = get_sub_name($_POST["sid"]);
            }
            else if ($type == 6)
            {
                $tid = $_POST["proxy_id"];
                $input = sqlCol("select max(input) from users where type=6 and proxy_id=".$tid);
                $_POST["input"] = $input ? $input + 1 : 1;
                $_POST["umax"] = "finance:0,operator:0,customer:0,salesman:0,employee:0";
            }
            else if ($type == 8)
            {
                $input = sqlCol("select max(input) from users where type=8");
                $_POST["input"] = $input ? $input + 1 : 1;
                $_POST["umax"] = "sub:0,pos:0";
            }

            $_POST["code"] = build_code($type, $tid);
            $_POST["code_id"] = $tid;
            $_POST["code_name"] = $usr_name[$type];   //为添加成功自增加序号使用
            $_POST["login_name"] = $_POST["code"];
            $_POST["pwd"] = think_md5($_POST["login_name"], UC_AUTH_KEY);
        }
    }

    private function formPostHandle($dataObj)
    {
        $ret = $dataObj->validate($this->form["validate"])->create();
        if (!$ret)
            $this->ajaxReturn(array("echo" => 1, "info" => $dataObj->getError()));

        if (I("post.id"))
        {
            if (isset($this->form["handle_run"]["edit"]))
                R($this->form["handle_run"]["edit"], array(& $this));
            $ret = $dataObj->auto($this->form["auto"])->create($_POST, 2);
            $ret = $ret ? $dataObj->save() : $ret;
            $this->form["return"]["info"] = $ret ? "修改成功!" : "没有改变数据或输入数据格式有误!";
        }
        else
        {
            if (isset($this->form["handle_run"]["add"]))
                R($this->form["handle_run"]["add"], array(& $this));

            //判断用户权限自动生成内部代码、登录名和密码等
            $this->buildUserBase();

            $ret = $dataObj->auto($this->form["auto"])->create($_POST, 1);
            if (!$ret)
                $this->form["return"]["info"] = $dataObj->getError();
            else
            {
                $ret = $dataObj->add();
                if ($ret)
                {
                    if (isset($_POST["type"]) && $this->form["table"] == "users")
                        parse_umax($_POST["code_name"], $_POST["code_id"]);
                    $this->countHandle($dataObj, $_POST);
                }
                $this->form["return"]["info"] .= $ret ? "添加成功!" : "输入数据格式有误!";
            }
        }

        if (!$ret)
        {
            $this->form["return"]["close"] = 0;
            $this->form["return"]["url"] = "";
        }
//             $this->form["return"]["info"] = $ret."hello".$dataObj->getError().$dataObj->getLastSql();
//             $this->ajaxReturn($this->form["return"]);

        //dump($this->form["return"]["url"]);
        $this->ajaxReturn($this->form["return"]);
        exit(0);
    }

    private function formCommonDel($dataObj)
    {
        $this->form["return"]["close"] = 0;
        $con = I("get.where");

        if ($con)
        {
            $con = ltrim($con, "'");
            $con = substr($con, 0, -1);

            if (isset($this->form["handle_run"]["del"]))
                $ret = R($this->form["handle_run"]["del"], array($con, & $this));
            else
            {
                $imgRs = $dataObj->field("img")->where($con)->select();
                if ($imgRs)   //判断删除表是否有Img字段
                {
                    foreach ($imgRs as $row)
                    {
                        $imgSp = explode("|", $row["img"]);  //判断Img字段是否有多条路径
                        if ($imgSp)
                        {
                            foreach ($imgSp as $path)
                            {
                                unlink(__UP__.str_replace(",", "/", $path));
                            }
                        }
                        else
                            unlink(__UP__.str_replace(",", "/", $row["img"]));
                    }
                }
                $this->countHandle($dataObj, $con, "dec");
                $ret = $dataObj->where($con)->delete();
                $this->form["return"]["info"] = $ret ? "删除成功!" : "没有匹配到要删除的数据!";
    //                     $this->form["return"]["info"] = "删除成功!".$dataObj->getLastSql();
            }
        }
        else
        {
            $this->form["return"]["info"] = "非法操作!";
            $ret = false;
        }

        if (!$ret)
            $this->form["return"]["url"] = "";

        $this->ajaxReturn($this->form["return"]);
        exit(0);
    }

    public function formDataShowEdit($data, $ext = "")
    {
        foreach ($data[0] as $key => $val)
        {
            if (isset($this->form["element"][$key]))
            {
                $this->form["element"][$key]["value"] = $val;

                if ($this->form["element"][$key]["type"] == "combobox" ||
                    $this->form["element"][$key]["type"] == "autocomplete")
                    $this->form["element"][$key]["input_val"] = $val;
            }
        }
        if (!isset($ext["close_hidden"]))
            $this->setElement("id", "hidden", "", array("value" => $data[0]["id"]));
        if (!isset($ext["close_btn"]))
            $this->set("btn 0 txt", "修改");
    }

    private function formCommonEdit($dataObj)
    {
        $con = I("get.where");
        if (!$con)
        {
            echo "非法操作!";
            exit(0);
        }
        $con = ltrim($con, "'");
        $con = rtrim($con, "'");

        if (isset($this->form["handle_run"]["show_edit"]))
            R($this->form["handle_run"]["show_edit"], array($con, & $this));
        else
        {
            $data = $dataObj->where($con)->select();
            $this->formDataShowEdit($data);
        }
    }

    private function infoFormat($data, $key)
    {
        $val = "";

        switch ($key)
        {
        	case "sex":
                $val = get_sex_txt($data[$key]);
        	    break;
        	case "input":
                $val = get_bool_txt($data[$key]);
        	    break;
        	case "sid":
                $val = get_sub_name($data[$key]);
        	    break;
        	case "bid":
                $val = get_basisname($data[$key]);
        	    break;
        	case "cvv2":
                $val = format_dis_field($data[$key], array(4,3));
        	    break;
        	case "pay_pwd":
        	case "query_pwd":
                $val = format_dis_field($data[$key], array(3, 3));
        	    break;
        	case "opid":
        	case "eid":
                $val = get_username($data[$key]);
        	    break;
        	case "phone1":
        	case "phone2":
        	case "phones":
                $val = format_dis_field($data[$key], array(3,4,4));
        	    break;
        	case "identity":
                $val = format_dis_field($data[$key], array(6,8,4));
        	    break;
        	case "card":
                $val = format_dis_field($data[$key]);
        	    break;
        	default:
        	    $val = $data[$key];
        }

        return $val;
    }

    public function formDataInfo($data, $ext = array())
    {
        $show_element = array();
        if (is_array($data[0]))
            $data = $data[0];

        foreach ($this->form["element"] as $key => $el)
        {
            //获取此元素是否有扩展
            $ext_true = array_key_exists($key, $ext);

            if ($ext_true && $ext[$key] == "del")
                continue;

            $show_element[$key] = $el;
            $type = & $show_element[$key]["type"];
            $value = & $show_element[$key]["value"];

            $value = isset($data[$key]) ? $data[$key] : "";

            $value = $this->infoFormat($data, $key);

            //扩展信息显示, 判断当前元素下标在扩展中是否存在
            if ($ext_true)
            {
                //判断查找到的扩展元素是为此元素后面新增新表单元素还是修改此元素信息
                //扩展元素数组中的数组第一个元素为标识是新增或修改模式
                if ($ext[$key][0] && $ext[$key][0] == "add")
                {
                    //循环在此元素后面新增的元素并将其加入实现表单元素列表中
                    foreach ($ext[$key] as $extkey => $extval)
                    {
                        if ($extkey == 0)
                            continue;
                        if ($ext[$key][$extkey]["name"] &&
                        $ext[$key][$extkey]["name"] != $show_element[$key]["name"])
                            $show_element[$ext[$key][$extkey]["name"]]	= $ext[$key][$extkey];
                    }
                }
                else
                {
                    //修改扩展元素信息，不删除原有信息，只会修改和新增属性
                    foreach ($ext[$key] as $extkey => $extval)
                    {
                        $show_element[$key][$extkey] = $extval;
                    }
                    //如果修改了type则说明不必须再做以下判断操作
                    if ($ext[$key]["type"])
                        continue;
                }
            }

            if ($type == "file")
            {
                $show_element[$key]["info"] = true;
                continue;
//                 if ($value && file_exists(__UP__.str_replace(",", "/", $value)))
//                     continue;
//                 else
//                     $value = "图片没有上传或丢失!";
            }

            if ($type == "hidden" || $type == "group")
                continue;

            $type = "static";
        }
        $this->form["element"] = $show_element;
    }

    private function formCommonInfo($dataObj)
    {
        $con = I("get.where");

        $this->set("close_btn_down", 1);
        $this->form["class"] .= " kyo_info_form";
        $this->form["ktype"] = "info";

        if (!$con)
        {
            echo "非法操作!";
            exit(0);
        }
        $con = ltrim($con, "'");
        $con = rtrim($con, "'");


        if (isset($this->form["handle_run"]["info"]))
            R($this->form["handle_run"]["info"], array($con, & $this));
        else
        {
            $data = $dataObj->where($con)->select();
            $this->formDataInfo($data);
        }
    }

    private function formHandle($dataObj)
    {
        if (IS_POST)
        {
            if (isset($this->form["handle_run"]["post"]))
                R($this->form["handle_run"]["post"], array(& $this));
            else
                $this->formPostHandle($dataObj);
        }

        switch (I("get.form"))
        {
        	case "del":
                $this->formCommonDel($dataObj);
                break;
        	case "info":
                $this->formCommonInfo($dataObj);
                break;
        	case "edit":
                $this->formCommonEdit($dataObj);
//                 dump($this->form["element"]);
            default:
                break;
        }
    }

    //预处理表单，主要控制器布局
    private function _parse_form_element()
    {
        $prevElement = "";

        foreach ($this->form["element"] as $key => & $el)
        {
            //记录用户是否设置element_cols和label_cols
            $element_cols = false;
            $label_cols = false;

            if (!isset($el["label_cols"]) || $el["label_cols"] == "")
            {
                $label_cols = true;
                $el["label_cols"] = $this->col[$this->form["cols"]]["label"];
            }

            if (!isset($el["element_cols"]) || $el["element_cols"] == "")
            {
                $element_cols = true;
                $el["element_cols"] = $this->col[$this->form["cols"]]["element"];
            }

            if ($this->index["element"] % $this->form["cols"] == 0)
                $el["begin"] = 1;
            else
                $el["begin"] = 0;

            if ($this->index["element"] % $this->form["cols"] == $this->form["cols"] - 1)
                $el["over"] = 1;
            else
                $el["over"] = 0;

            //如果上一个是textarea或上一个元素占一行 则此时元素为第一列元素
//             if ($prevElement["old_type"] == "textarea" || $prevElement["sig_row"] == true)
            if (kempty($prevElement, "old_type", "textarea") || kempty($prevElement, "sig_row", true))
            {
                $el["begin"] = 1;
                $el["over"] = 0;
                if (($this->index["element"] + 1) % $this->form["cols"] == 0)
                    $this->index["element"]++;
            }

            switch ($el["old_type"])
            {
            	case "textarea":
                    $prevElement["over"] = 1;  //上一个有效元素结束行
                    $el["label_cols"] = $label_cols ? $this->col[$this->form["cols"]]["label"] : $el["label_cols"];
                    if ($this->form["cols"] == 2)
                        $el["element_cols"] = $element_cols ? "col-sm-9 col-md-9" : $el["element_cols"];
                    $el["begin"] = 1;
                    $el["over"] = 1;
            	    break;
            	case "date":
                    $el["bool"] .= " readonly ";
            	    break;
            	case "group":
                    $prevElement["over"] = 1;  //上一个有效元素结束行
                    if (($this->index["element"] + 1) % $this->form["cols"] == 0)
                        $this->index["element"]++;
                    $el["begin"] = 1;
                    $el["over"] = 1;
                    break;
            	default:
            	    break;
            }

            //此元素独占一行
            if (isset($el["sig_row"]))
            {
                $prevElement["over"] = 1;
                $el["element_cols"] = $element_cols ? "col-sm-8 col-md-8" : $el["element_cols"] ;
                $el["begin"] = 1;
                $el["over"] = 1;
            }

//             dump($el);
            if ($this->form["cols"] == 2 && kempty($el, "over", 1) &&
                    isset($el["begin"]) && $el["begin"] != 1 &&
                    (!isset($prevElement["group"]) || $prevElement["group"] == "end"))
                $el["label_cols"] = $label_cols ? "col-sm-3 col-md-3" : $el["label_cols"];

            $el["formname"] = $this->form["name"];
            $el["table"] = $this->form["table"];

            if (kempty($el, "group", "start") || kempty($el, "group", "end"))
                $el["element_cols"] = $element_cols ? $this->col[$this->form["cols"]]["group"] : $el["element_cols"];

            if (kempty($el, "type", "group") || kempty($el, "type", "hidden") ||
                    kempty($el, "group", "start") || kempty($el,"group", "mid"))
                continue;

            $this->index["element"]++;
            $prevElement = & $this->form["element"][$key];
        }
        //最后一个元素不管什么情况都要有行结束
        $prevElement["over"] = 1;

        foreach ($this->form["element"] as & $el)
        {
            if (I("get.form") == "info")
                $el["name"] = "info_".$el["name"];
            $elObj = new FormElement($el["name"], $el["type"], $el["txt"], $el);
            $el["html"] = $elObj->fetch();
        }
    }

    //组合数据列表类Html代码, 支持外部模板
    public function fetch($templatefile = "", $content = "", $prefix = "")
    {
        //处理按钮的特殊链接
        parse_link_html($this->form["btn"]);

        if (!$this->form["action"])
            $this->form["action"] = __SELF__;

        if (isset($this->form["dataObj"]))
            $this->formHandle($this->form["dataObj"]);

        //解析element配置操作
        $this->_parse_form_element();

        //dump($this->html());
        return $this->html();

//         dump($this->form["btn"]);
//         dump($this->form["element"]);
//         if ($templatefile == "")
//             $templatefile = T('KyoCommon@Public/form_main');

//         $this->assign($this->form);
//         dump(parent::fetch($templatefile));
//         return parent::fetch($templatefile);
    }

    //不依赖模板输出表单的html代码
    public function html()
    {
        $form = $this->form;

        $html = "";

        $html .= '<form id="'.$form["name"].'_id" name="'.$form["name"].'" kajax="'.$form["kajax"].'"
                    action="'.$form["action"].'" method="post" enctype="multipart/form-data"
                    target="'.$form["target"].'" class="'.$form["class"].'" me="'.$form["me"].'"
                    ktype="'.$form["ktype"].'"  callback="'.$form["callback"].'" vay="'.$form["validity"].'">';

        if ($form["close_btn_up"] != 1)
        {
            $html .= '<div class="form-group form_ctrl_btn form_ctrl_btn_up">';
            if ($form["my_btn"])
                $html .= $form["my_btn"];
            else
            {
                foreach ($form["btn"] as $vo)
                {
                    karray($vo, array("front", "end", "class", "txt", "end", "icon"));

                    $html .= $vo["front"];
                    $html .= '<button class="btn btn-primary '.$vo["class"].'" '.$vo["link"].'>';
                    if ($vo["icon"])
                        $html .= '<span class="glyphicon glyphicon-'.$vo["icon"].'"></span>&nbsp;';
                    $html .= $vo["txt"].'</button>'.$vo["end"];
                }
            }
            $html .= '</div>';
        }

        foreach ($form["element"] as $el)
        {
            $html .= $el["html"];
        }

        if ($form["close_btn_down"] != 1)
        {
            $html .= '<div class="form-group form_ctrl_btn form_ctrl_btn_down">';
            if (isset($form["my_btn"]))
                $html .= $form["my_btn"];
            else
            {
                foreach ($form["btn"] as $vo)
                {
                    karray($vo, array("front", "end", "class", "txt", "end", "icon"));

                    $html .= $vo["front"];
                    $html .= '<button class="btn btn-primary '.$vo["class"].'" '.$vo["link"].'>';
                    if ($vo["icon"])
                        $html .= '<span class="glyphicon glyphicon-'.$vo["icon"].'"></span>&nbsp;';
                    $html .= $vo["txt"].'</button>'.$vo["end"];
                }
            }
            $html .= '</div>';
        }
        $html .= '</form>';

        if ($form["js"])
            $html .= '<script type="text/javascript" src="'.__ROOT__.'/Public/js/'.$form["js"].'.js"></script>';

        return $html;
    }
}
