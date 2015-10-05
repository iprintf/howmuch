<?php
namespace Common\Controller;

// 表单元素类
class FormElement
{
    private $element = array();

    public function __construct($name, $type, $txt = "", $option = array())
    {

        init_array($this->element, array("close_label", "close_element_div", "gclass", "pclass", "lclass",
            "hclass", "class", "label_cols", "element_cols", "type", "txt", "icon",  "href", "id", "name",
            "value", "maxlength", "placeholder", "form", "title", "max", "min", "hint", "list", "url",
            "action", "file_upload", "accept", "p_upload", "cat_title", "del", "table", "row", "input_val",
            "custom_html", "addon", "bool", "ext", "link", "back_ext", "formname"
        ));

        $this->element["begin"] = 1;
        $this->element["over"] = 1;
        $this->element["addon_dir"] = 0;

        $this->setElement($name, $type, $txt, $option);

        return true;
    }

    //设置数据列表配置
    public function _set($name, $value)
    {
        if ($name == "")
            return false;

        $name_sel = explode(" ", $name);
        $name_len = count($name_sel);
        if ($name_len == 1)
            $this->element[$name] = $value;
        else
        {
            $obj = & $this->element;
            for ($i = 0; $i < $name_len; $i++)
            {
                $obj = & $obj[$name_sel[$i]];
            }

            $obj = $value;
        }
        return true;
    }

    //批量设置表单元素参数
    public function setOp($name, $value = "")
    {
        if (is_array($name))
        {
            foreach ($name as $key => $val)
            {
                $this->element[$key] = $val;
            }
        }
        else
            return $this->_set($name, $value);

        return true;
    }

    //获取数据列表类配置参数,为了支持自定义操作
    public function get($name = "")
    {
        if ($name == "")
            return $this->element;

        $name_sel = explode(" ", $name);
        $name_len = count($name_sel);
        if ($name_len == 1)
            return $this->element[$name];

        $obj = & $this->element;
        for ($i = 0; $i < $name_len; $i++)
        {
            $obj = & $obj[$name_sel[$i]];
        }

        return $obj;
    }

    public function setElement($name, $type, $txt, $option = array())
    {
        if ($name == "" || $type == "")
            return false;

        $this->element["name"] = $name;
        $this->element["type"] = $type;
        $this->element["old_type"] = $type;
        if ($txt != "")
        {
            $this->element["txt"] = $txt;
            $this->element["title"] = $txt;
            $this->element["placeholder"] = "请输入".$txt;
        }
        $this->element["id"] = $name."_id";

        //默认所有输入框输入最大数为11个字符
        if (($type == "string" || $type == "num") && !isset($option["maxlength"]))
            $option["maxlength"] = 15;

        foreach ($option as $key => & $val)
        {
            $this->element[$key] = & $val;
        }
    }


    //处理上传文件默认设置和检测文件存在自动显示查看图片或上传图片
    private function _setElementFile()
    {
        $el = & $this->element;

        karray($el, array("ext", "bool", "info", "cat_title", "value"));

        if ($el["cat_title"] == "")
            $el["cat_title"] = "图片信息";

        if ($el["value"] && file_exists(__UP__.str_replace(",", "/", $el["value"])))
        {
            $el["file_upload"] = "hidden";
            $el["p_upload"] = "";

            $el["value"] = str_replace("/", ",", $el["value"]);

            //如果文件已经上传取消特殊扩展
            if ($el["ext"])
                $el["ext"] = "";

            if ($el["bool"])
                $el["bool"] = "";

            if ($el["url"] == "")
                $el["url"] = U('KyoCommon/Upload/upload_show', 'path='.$el["value"]);

            if ($el["info"])
                $el["reset"] = 1;
            else
            {
                $el["url"] = U('KyoCommon/Upload/upload_show', 'path='.$el["value"]);
                if ($el["del"] == true)
                    $el["del"] = U('KyoCommon/Upload/upload_del', 'path='.$el["value"]);
            }
        }
        else
        {
            if ($el["info"]) //如果是信息显示， 图片路径找不到则提示的信息
            {
                $el["type"] = "static";
                $el["value"] = "图片没有上传或丢失!";
                return true;
            }

            $el["value"] = "";
            if (!$el["file_upload"])
                $el["file_upload"] = "";
            if (!$el["p_upload"])
                $el["p_upload"] = "hidden";
        }
        if (!isset($el["filename"]))
            $filename = dechex(mt_rand(16, 99)).date("YmdHi").dechex(mt_rand(16, 99));
        else
            $filename = $el["filename"];

        if (!$el["accept"])
            $el["accept"] = "image/gif, image/jpeg, image/png";

        if (!$el["action"])
            $el["action"] = U("KyoCommon/Upload/Index",
                    "name=".$el["name"]."&table=".$this->element["table"]."&filename=".$filename);
    }

    private function _setElementCommon()
    {
        $el = & $this->element;

        if (!$el["element_cols"])
            $el["element_cols"] = "";

        if (!$this->element["formname"] && !$el["close_label"])
            $el["close_label"] = 1;

        if ($el["hint"])
            $el["hclass"] = "kyo_hint";

        if (!$this->element["formname"] && !$el["element_cols"])
            $el["element_cols"] = "col-sm-12 col-md-12";

        if ($this->element["formname"] && !$el["form"])
            $el["form"] = $this->element["formname"];

        //兼容标题列和元素列占多少份的设置操作，可以col-md-?或 直接数字 赋值
        if ($el["element_cols"] && !strstr($el["element_cols"], "col"))
            $el["element_cols"] = "col-sm-".$el["element_cols"]." col-md-".$el["element_cols"];

        if ($el["label_cols"] && !strstr($el["label_cols"], "col"))
            $el["label_cols"] = "col-sm-".$el["label_cols"]." col-md-".$el["label_cols"];

        //多元素在同列显示操作 开始元素，取消列结束
        if (kempty($el, "group", "start"))
            $el["over"] = 0;

        //多元素在同列显示，中间元素没有行首行尾
        if (kempty($el, "group", "mid"))
        {
            $el["close_label"] = $el["close_label"] ? $el["close_label"] : 1;
            $el["begin"] = 0;
            $el["over"] = 0;
        }
        //多元素在同列显示，最后元素没有行首，要行结束
        if (kempty($el, "group", "end"))
        {
            $el["close_label"] = $el["close_label"] ? $el["close_label"] : 1;
            $el["begin"] = 0;
//             $el["over"] = 1;
        }

        if ($el["type"] == "file")
        {
            $url = $el["url"];
            parse_form_link_html($el);
            $el["url"] = $url;
        }
        else if ($el["type"] != "radio" && $el["type"] != "checkbox")
            parse_form_link_html($el);
    }

    private function _parse_element()
    {
        $el = & $this->element;

        switch ($el["type"])
        {
        	case "file":
                $this->_setElementFile();
        	    break;
        	case "textarea":
                if (!isset($el["rows"]))
                    $el["rows"] = 3;
                break;
        	case "radio":
        	case "checkbox":
                parse_form_link_html($el);
                $i = 0;
                foreach ($el["list"] as $key => $val)
                {
                    if (!isset($el[$key]["list"]["id"]))
                        $el["list"][$key]["id"] = $el["name"]."_id".$key;

                    if (!isset($el[$key]["list"]["link"]))
                    {
                        if ($el["type"] == "radio" || $el["type"] == "checkbox" && $i == 0)
                            $el["list"][$key]["link"] = $el["link"];
                    }

                    else
                        parse_form_list_html($el["list"][$key]);
                    $i++;
                }
                break;
        	case "sradio":
        	case "scheckbox":
                $el["close_label"] = 1;
                $el["begin"] = 0;
                $el["over"] = 0;
        	    break;
        	case "group":
                if (!$el["close_label"])
                    $el["close_label"] = 1;
                $el["element_cols"] = "cols-sm-12 cols-md-12";
                $el["over"] = 1;
                break;
        	case "hidden":
                $el["begin"] = 0;
                $el["over"] = 0;

                if (!$el["close_label"])
                    $el["close_label"] = 1;

                if (!$el["close_element_div"])
                    $el["close_element_div"] = 1;
                break;
        	case "link":
                $el["begin"] = 0;
                $el["over"] = 0;
                $el["close_element_div"] = 1;
                $el["close_label"] = 1;
                break;
        	case "static":
                $el["addon"] = "";
                break;
        	default:
        	    break;
        }

        $this->_setElementCommon();
    }

    //组合数据列表类Html代码, 支持外部模板
    public function fetch()
    {
        //预处理表单元素的参数，主要是设置默认值等
        $this->_parse_element();
//         dump($this->html());
        return $this->html();
    }

    //文本输入框
    public function input($type, $el)
    {
        $html = '<input type="'.$type.'" id="'.$el["id"].'" name="'.$el["name"].'" ';
        $html .= 'class="form-control '.$el["hclass"].' '.$el["class"].'" value="'.$el["value"].'" min="'.$el["min"].'" ';
        $html .= 'maxlength="'.$el["maxlength"].'" placeholder="'.$el["placeholder"].'" ';
        if ($el["hint"])
            $html .= ' hint="'.$el["hint"].'" ';
        $html .= 'kform="'.$el["form"].'" autocomplete="off" '.$el["ext"].' title="'.$el["title"].'" />';
        return $html;
    }

    //所有表单控件html代码生成
    public function html()
    {
        $html = "";
        $el = $this->element;

        if ($el["begin"])
            $html .= '<div class="form-group form_element_row '.$el["gclass"].'">';

        if ($el["close_label"] != 1)
            $html .= '<label for="'.$el["id"].'" class="'.$el["label_cols"].
            ' control-label form_element_title '.$el["lclass"].' hidden-ss">'.$el["txt"].':</label>';

        if ($el["close_element_div"] != 1)
            $html .= '<div class="kyo_'.$el["type"].' '.$el["element_cols"].' '.$el["pclass"].'">';

        if ($el["hint"])
            $html .= '<div id="hint_'.$el["name"].'" class="hint_show"></div>';

        if ($el["addon"])
        {
            $html .=  '<div class="input-group">';
            if ($el["addon_dir"] == 1)
                $html .= '<span class="input-group-addon">'.$el["addon"].'</span>';
        }

        switch ($el["type"])
        {
        	case "group":
                $html .= '<div class="page-header kyo_form_group '.$el["class"].'">'.$el["txt"].'</div>';
        	    break;
        	case "password":
                $html .= $this->input("password", $el);
        	    break;
        	case "identity":
                $el["maxlength"] = 18;
                $html .= $this->input("text", $el);
        	    break;
        	case "sradio":
                $html .= '<label class="radio-inline '.$el["class"].'">';
                $html .= '<input type="radio" kform="'.$el["form"].'" title="'.$el["title"].'"
                            id="'.$el["id"].'" name="'.$el["name"].'" value="'.$el["value"].'" '.$el["link"];
                $html .= ' /><span id="label_'.$el["id"].'">'.$el["txt"].'</span></label>';
                break;
        	case "scheckbox":
                $html .= '<label class="checkbox-inline '.$el["class"].'">';
                $html .= '<input type="checkbox" kform="'.$el["form"].'" title="'.$el["title"].'"
                            id="'.$el["id"].'" name="'.$el["name"].'" value="'.$el["value"].'" '.$el["link"];
                $html .= ' /><span id="label_'.$el["id"].'">'.$el["txt"].'</span></label>';
                break;
        	case "radio":
                if ($el["list"])
                {
                    foreach ($el["list"] as $ro)
                    {
                        karray($ro, array("class", "id", "txt", "val", "link"));
                        $html .= '<label class="radio-inline '.$ro["class"].'">';
                        $html .= '<input type="radio" kform="'.$el["form"].'" title="'.$el["title"].'""
                                    id="'.$ro["id"].'" name="'.$el["name"].'" value="'.$ro["val"].'" '.$ro["link"];
                        if ($ro["val"] == $el["value"])
                            $html .= ' checked="checked" ';
                        $html .= " />".$ro["txt"].'</label>';
                    }
                }
        	    break;
        	case "checkbox":
                if ($el["list"])
                {
                    foreach ($el["list"] as $key => $cb)
                    {
                        karray($cb, array("class", "id", "txt", "val", "link"));
                        $html .= '<label class="checkbox-inline '.$cb["class"].'">';
                        $html .= '<input type="checkbox" kform="'.$el["form"].'" title="'.$el["title"].'"
                               id="'.$cb["id"].'" name="'.$el["name"].'" value="'.$cb["val"].'" '.$cb["link"];
                        $hmtl .= ' />'.$cb["txt"].'</label>';
                    }
                }
        	    break;
        	case "select":
                $html .= '<select id="'.$el["id"].'" name="'.$el["name"].'" title="'.$el["title"].'"
                        kform="'.$el["form"].'" class="form-control '.$el["class"].'" '.$el["link"].'>';

                if ($el["list"])
                {
//                     dump($el["value"]);
                    foreach ($el["list"] as $key => $sl)
                    {
                        karray($sl, array("val", "txt"));
                        if ($sl["val"] === "" || !($el["value"] === "") && (string)$sl["val"] == (string)$el["value"])
                        {
                            $html .= '<option value="'.$sl['val'].'" selected="selected">'.$sl['txt'].'</option>';
                        }
                        else
                            $html .= '<option value="'.$sl['val'].'">'.$sl['txt'].'</option>';
                    }
                }
                $html .= '</select>';
                break;
        	case "static":
                $html .= '<p id="'.$el["id"].'" class="form-control-static '.$el["class"].'">'.$el["value"].'</p>';
        	    break;
        	case "file":
                $html .= '<input type="file" id="file_'.$el["id"].'" name="file_'.$el["name"].'" action="'.$el["action"].'"
                            class="form-control form_file '.$el["file_upload"].' '.$el["class"].'"
                            maxlength="'.$el["maxlength"].'" placeholder="'.$el["placeholder"].'"
                            autocomplete="off" accept="'.$el["accept"].'" title="'.$el["title"].'"
                            '.$el["ext"].' />';
                $html .= '<p class="form-control-static '.$el["p_upload"].'">';
                $html .= '<a title="'.$el["cat_title"].'" url="'.$el["url"].'" class="link_btn cat_img">查看图片</a>&nbsp;&nbsp;';
                if (!isset($el["reset"]))
                    $html .= '<a del="'.$el["del"].'" class="link_btn reset_upload">重新上传</a>';
                $html .= '</p>';
                $html .= '<input type="hidden" id="'.$el["id"].'" name="'.$el["name"].'" '.$el["ext"].' kform="'.$el["form"].'"
                            title="'.$el["title"].'"  value="'.$el["value"].'" />';
        	    break;
        	case "textarea":
                $html .= '<textarea id="'.$el["id"].'" name="'.$el["name"].'" kform="'.$el["form"].'"
                            placeholder="'.$el["placeholder"].'"
                             title="'.$el["title"].'" class="form-control '.$el["class"].'" rows="'.$el["rows"].'"
                             '.$el["ext"].'>'.$el["value"].'</textarea>';
        	    break;
        	case "hidden":
                $html .= '<input type="hidden" id="'.$el["id"].'" name="'.$el["name"].'"
                        kform="'.$el["form"].'" value="'.$el["value"].'" />';
                break;
        	case "combobox":
                $html .= '<div class="input-group">';
                $html .= '<input type="text" id="'.$el["name"].'_input" class="form-control
                            input_autocomplete '.$el["class"].'" value="'.$el["input_val"].'"
                            placeholder="'.$el["placeholder"].'" maxlength="'.$el["maxlength"].'"
                            autocomplete="off" '.$el["link"].' title="'.$el["title"].'" />';
                $html .= '<span class="input-group-btn">';
                $html .= '<button class="btn btn-default input_autocomplete_btn" type="button">
                            <span class="caret"></span></button>';
                $html .= '</span>';
                $html .= '</div>';
                $html .= '<input type="hidden" id="'.$el["id"].'" name="'.$el["name"].'" kform="'.$el["form"].'"
                            '.$el["ext"].' title="'.$el["title"].'" value="'.$el["value"].'" />';
                $html .= '<div id="'.$el["name"].'_show" class="form-control autocomplete_show"></div>';
                $html .= '<div id="'.$el["name"].'_data" class="hidden">'.$el["list"].'</div>';
                break;
        	case "autocomplete":
                $html .= '<input type="text" id="'.$el["name"].'_input" class="form-control
                                input_autocomplete '.$el["class"].'" value="'.$el["input_val"].'"
                                placeholder="'.$el["placeholder"].'" maxlength="'.$el["maxlength"].'"
                                autocomplete="off" '.$el["link"].' title="'.$el["title"].'" />';
                if ($el["addon"] && !$el["addon_dir"])
                    $html .= '<span class="input-group-addon">'.$el["addon"].'</span></div>';
                $html .= '<input type="hidden" id="'.$el["id"].'" name="'.$el["name"].'" '.$el["ext"].'
                             kform="'.$el["form"].'" title="'.$el["title"].'" value="'.$el["value"].'" />';
                $html .= '<div id="'.$el["name"].'_show" class="form-control autocomplete_show"></div>';
                $html .= '<div id="'.$el["name"].'_data" class="hidden">'.$el["list"].'</div>';
        	    break;
            case "multiselect":
                $html .= '<select id="'.$el["id"].'" name="'.$el["name"].'" title="'.$el["title"].'"
                        kform="'.$el["form"].'" class="form-control '.$el["class"].'" '.$el["link"].'
                        multiple="multiple">';

                if ($el["list"])
                {
//                     dump($el["value"]);
                    foreach ($el["list"] as $key => $sl)
                    {
                        karray($sl, array("val", "txt"));
                        if ($sl["val"] === "" || !($el["value"] === "") && (string)$sl["val"] == (string)$el["value"])
                        {
                            $html .= '<option value="'.$sl['val'].'" selected="selected">'.$sl['txt'].'</option>';
                        }
                        else
                            $html .= '<option value="'.$sl['val'].'">'.$sl['txt'].'</option>';
                    }
                }
                $html .= '</select>';
                $html .= '<script>';
                $html .= '$("#'.$el["id"].'").multiselect({';
                $html .= 'buttonWidth: "230px",';
                $html .= 'includeSelectAllOption: true,';
                $html .= 'nonSelectedText: "请选择'.$el["txt"].'...",';
                $html .= 'enableFiltering: true';
                $html .= '});';
                $html .= '</script>';
                break;
        	case "custom":
                $html .= $el["custom_html"];
        	    break;
        	case "button":
                $html .= '<button class="btn btn-primary '.$el["class"].'" '.$el["link"].'>';
                if ($el["icon"] != "")
                    $html .= '<span class="glyphicon glyphicon-'.$el["icon"].'"></span>&nbsp;';
                $html .= '<span id="'.$el["name"].'_txt">'.$el["txt"].'</span></button>';
        	    break;
        	case "link":
                $html .= '<a href="'.$el["href"].'" class="'.$el["class"].'" '.$el["link"].'>';
                if ($el["icon"])
                    $html .= '<span class="glyphicon glyphicon-'.$el["icon"].'"></span>&nbsp;';
                $html .= '<span id="'.$el["name"].'_txt">'.$el["txt"].'</span></a>';
        	    break;
//                 $html .= '<input type="text" id="'.$el["name"].'_input" class="form-control
//                                 input_autocomplete '.$el["class"].'" value="'.$el["value"].'"
//                                 placeholder="'.$el["placeholder"].'" maxlength="'.$el["maxlength"].'"
//                                 autocomplete="off" '.$el["link"].' readonly="readonly" title="'.$el["title"].'" />';
//                 break;
        	case "phone":
                $el["maxlength"] = 11;
        	case "card":
        	case "email":
        	case "date":
        	case "num":
        	case "string":
        	default:
                $html .= $this->input("text", $el);
                break;
        }

        if ($el["type"] != "autocomplete" && $el["addon"])
        {
            if (!$el["addon_dir"])
                $html .= '<span class="input-group-addon">'.$el["addon"].'</span>';
            $html .= '</div>';
        }

        if ($el["close_element_div"] != 1)
            $html .= '</div>';

        $html .= $el["back_ext"];

        if ($el["over"])
            $html .= "</div>";


        return $html;
    }
}

?>
