<?php
namespace Common\Controller;
use Think\Controller;

class PageTool extends Controller
{
    private $obj;       //数据对象
    private $index;
    private $tool = array();

    public function __construct($option = array())
    {
        parent::__construct();

        init_array($this->tool, array("find_row_class", "tool_class", "my_tool_find"));
        init_array($this->tool["tool_find"], array("item", "typelist", "me", "url",
                        "tag", "input_class", "placeholder"));
        init_array($this->tool["tool_find"]["item"], array("name", "type", "list", "me",
                        "class", "default", "defval"));
        $this->set();

        foreach ($option as $key => $val)
        {
            $this->tool[$key] = $val;
        }

        return true;
    }

    public function set($name = "", $value = "")
    {
        if ($name == "" && $value == "")
        {
            $this->tool["close_btn_link"] = 1;
            $this->tool["close_btn"] = 1;
            $this->tool["close_btn_down"] = 1;
            $this->tool["close_link"] = 1;
            $this->tool["close_batch"] = 1;
            $this->tool["close_tool_div"] = 0;
            $this->tool["close_tool_find"] = 0;
            $this->tool["tool_link_title"] = '<span class="tool_link_title">视图：</span>';
            if (I("get.by"))
                $this->tool["tool_find"]["url"] = U().'&find=1&by='.I("get.by");
            else
                $this->tool["tool_find"]["url"] = U().'&find=1';
            $this->tool["tool_find"]["tag"] = ".kyo_data_list";
            $this->tool["tool_find"]["placeholder"] = "请输入要查询的关键词!";
            $this->tool["tool_find"]["item"] = "";


            $this->index["tool_batch"] = 0;
            $this->index["tool_find"] = 0;
            $this->index["tool_btn"] = 0;
            $this->index["tool_link"] = 0;
            return true;
        }

        $name_sel = explode(" ", $name);
        $name_len = count($name_sel);
        if ($name_len == 1)
            $this->tool[$name] = $value;
        else
        {
            $obj = & $this->tool;
            for ($i = 0; $i < $name_len; $i++)
            {
            $obj = & $obj[$name_sel[$i]];
            }

            $obj = $value;
        }

        if (strstr($name, "tool_btn_down"))
            $this->tool["close_btn_down"] = 0;
        return true;
    }

    public function get($name = "")
    {
        if ($name == "")
            return $this->tool;

        $name_sel = explode(" ", $name);
        $name_len = count($name_sel);
        if ($name_len == 1)
            return $this->tool[$name];

        $obj = & $this->tool;
        for ($i = 0; $i < $name_len; $i++)
        {
            $obj = & $obj[$name_sel[$i]];
        }

        return $obj;
    }

    private function _setCommon($opName, $txt, $url, $option = array())
    {
        if (isset($option["name"]))
            $index = $option["name"];
        else
            $index = $this->index[$opName]++;

        $this->tool[$opName][$index]["txt"] = $txt;
        $this->tool[$opName][$index]["url"] = $url;

        karray($this->tool[$opName][$index], array("front", "end", "url", "txt"));

        foreach ($option as $key => $val)
        {
            $this->tool[$opName][$index][$key] = $val;
        }

        if ($opName == "tool_batch" && !isset($option["name"]))
            $this->tool[$opName][$index]["name"] = "batch";

        return true;
    }

    public function setBtn($txt, $url, $option = array())
    {
        $this->tool["close_btn_link"] = 0;
        $this->tool["close_btn"] = 0;
        if (!$option["end"])
            $option["end"] = "&nbsp;";
        return $this->_setCommon("tool_btn", $txt, $url, $option);
    }

    public function setLink($name, $txt, $url = "", $option = array())
    {
        if ($txt == "" || $name == "")
            return false;

        $option["name"] = $name;

        if ($url == "")
            $option["url"] = U().'&by='.$name;
        else
            $option["url"] = $url;

        $this->tool["close_btn_link"] = 0;
        $this->tool["close_link"] = 0;

        if (!$option["end"])
            $option["end"] = "&nbsp;|&nbsp;";

        return $this->_setCommon("tool_link", $txt, $url, $option);
    }

    public function setBatch($txt, $url, $option = array())
    {
        $this->tool["close_batch"] = 0;
        return $this->_setCommon("tool_batch", $txt, $url, $option);
    }

    public function setFind($name, $value)
    {
        if ($name == "" || $value == "")
            return false;

        $name_sel = explode(" ", $name);
        $name_len = count($name_sel);
        if ($name_len == 1)
            $this->tool["tool_find"][$name] = $value;
        {
            $obj = & $this->tool["tool_find"];
            for ($i = 0; $i < $name_len; $i++)
            {
                $obj = & $obj[$name_sel[$i]];
            }

            $obj = $value;
        }

        return true;
    }

    public function fetch($templatefile = "", $content='',$prefix='')
    {
        parse_link_html($this->tool["tool_btn"]);
        parse_link_html($this->tool["tool_link"]);
        parse_link_html($this->tool["tool_batch"]);
        parse_link_html_single($this->tool["tool_btn_down"]);

        karray($this->tool["tool_btn_down"], array("front", "end"));

//         dump($this->tool["tool_find"]);
//         dump($this->tool["tool_link"]);

//         if ($templatefile == "")
//             $templatefile = T('KyoCommon@Public/list_tool');
//         $this->assign($this->tool);
//         dump(parent::fetch($templatefile));
//         return parent::fetch($templatefile);
        //dump($this->html());
        return $this->html();
    }

    private function link_row_html($tool)
    {
        $html = "";
        $html .= '<div class="row tool_nav_row '.$tool["nav_row_class"].'">';
        $html .= '<div class="col-md-12">';
        if ($tool["close_btn"] != 1)
        {
            if ($tool["my_tool_btn"])
                $html .= $tool["my_tool_btn"];
            else
            {
                foreach ($tool["tool_btn"] as $vo)
                {
                    $html .= $vo["front"];
                    $html .= '<button class="btn btn-primary '.$vo["class"].'" '.$vo["link"].'>';
                    if ($vo["icon"])
                        $html .= '<span class="glyphicon glyphicon-'.$vo["icon"].'"></span>&nbsp;';
                    $html .= $vo["txt"];
                    $html .= '</button>';
                    $html .= $vo["end"];
                }
            }
        }

        if ($tool["close_link"] != 1)
        {
            if ($tool["my_tool_link"])
                $html .= $tool["my_tool_link"];
            else
            {
                $html .= "&emsp;".$tool["tool_link_title"];
                foreach ($tool["tool_link"] as $vo)
                {
                    $html .= $vo["front"];
                    if ($vo["name"] == I("get.by"))
                        $html .= '<span class="tool_link_active">'.$vo["txt"].'</span>';
                    else
                    {
                        $html .= '<a href="'.$vo["href"].'" class="'.$vo["class"].'" '.$vo["link"].'>';
                        if ($vo["icon"])
                            $html .= '<span class="glyphicon glyphicon-'.$vo["icon"].'"></span>&nbsp;';
                        $html .= $vo["txt"].'</a>';
                    }
                    $html .= $vo["end"];
                }
            }
        }
        $html .= '</div></div>';
        return $html;
    }

    private function find_html(& $tool)
    {
        $html = "";
        if ($tool["my_tool_find"])
            $html .= $tool["my_tool_find"];
        else
        {
            $html .= '<form action="'.$tool["tool_find"]["url"].'" method="post">';
            $html .= '<ul class="kyo_search_win hidden-ss">';
            if ($tool["tool_find"]["typelist"])
            {
                $html .= '<li>';
                $html .= '<select name="search_type" me="'.$tool["tool_find"]["me"].'" class="form-control">';
                foreach ($tool["tool_find"]["typelist"] as $tl)
                {
                    if ($tl["sel"])
                        $html .= '<option value="'.$tl["val"].'" selected="selected">'.$tl["txt"].'</option>';
                    else
                        $html .= '<option value="'.$tl["val"].'">'.$tl["txt"].'</option>';

                }
                $html .= '</select></li>';
            }

            foreach ((array)($tool["tool_find"]["item"]) as $vo)
            {
                karray($vo, array("type", "class", "name", "sval", "eval", "list"));
                switch ($vo["type"])
                {
                	case 'date':
                        $html .= '<li class="'.$vo["class"].'">';
                        $html .= '<input type="text" id="'.$vo["name"].'_id_start" name="'.$vo["name"].'_start"';
                        $html .= ' placeholder="起始日期" value="'.$vo["sval"].'" class="form-control kyo_form_date" readonly="readonly" />';
                        $html .= '</li><li class="'.$vo["class"].'">';
                        $html .= '<input type="text" id="'.$vo["name"].'_id_end" name="'.$vo["name"].'_end"';
                        $html .= ' placeholder="结束日期" value="'.$vo["eval"].'" class="form-control kyo_form_date" readonly="readonly" />';
                        $html .= '</li>';
                        break;
                	case 'num':
                        $html .= '<li class="'.$vo["class"].'">';
                        $html .= '<input type="text" id="'.$vo["name"].'_id_start" name="'.$vo["name"].'_start"';
                        $html .= ' placeholder="起始值" value="'.$vo["sval"].'" class="form-control kyo_form_num" /></li>';
                        $html .= '<li class="'.$vo["class"].'">';
                        $html .= '<input type="text" id="'.$vo["name"].'_id_end" name="'.$vo["name"].'_end"';
                        $html .= ' placeholder="结束值" value="'.$vo["eval"].'" class="form-control kyo_form_num" /></li>';
                        break;
                	case 'select':
                	default:
                        if ($vo["default"])
                        {
                            $html .= '<li class="'.$vo["class"].'">';
                            $html .= '<select id="'.$vo["name"].'_id" name="'.$vo["name"].'" me="'.$vo["me"].'" class="form-control">';
                            $html .= '<option value="'.$vo["defval"].'" selected="selected">'.$vo["default"].'</option>';
                            if ($vo["list"])
                            {
                                foreach ($vo["list"] as $sl)
                                {
                                    $html .= '<option value="'.$sl["val"].'">'.$sl["txt"].'</option>';
                                }
                            }
                            $html .= '</select></li>';
                        }
                        break;
                }
            }
            $html .='<li><input type="text" name="search_key" class="form-control search_input ';
            $html .= $tool["tool_find"]["input_class"].'" placeholder="'.$tool["tool_find"]["placeholder"].'" autocomplete="off" />';
            $html .= '</li><li>';
            $html .= '<button class="btn btn-primary" id="find" type="submit" tag='.$tool["tool_find"]["tag"].'>';
            $html .= '<span class="glyphicon glyphicon-search"></span>&nbsp;查询 </button></li></ul></form>';
        }
        return $html;
    }

    public function html()
    {
        $tool = $this->tool;
        $html = "";

        if ($tool["close_tool_div"] != 1)
            $html .= '<div class="kyo_list_tool '.$tool["tool_class"].'">';

        if (isset($tool["close_btn_link"]) && $tool["close_btn_link"] != 1)
            $html .= $this->link_row_html($tool);

        $html .= '<div class="row tool_find_row '.$tool["find_row_class"].'">';

        if ($tool["close_btn_down"] == 0 && $tool["close_batch"] == 0)  //查询行按钮和批量操作都存在，查询列占9
            $html .= '<div class="col-md-9 col-sm-9 col-xs-9 col-ss-9">';
        else if ($tool["close_btn_down"] && $tool["close_batch"])  //按钮和批量操作两个都不存在，查询列占12
            $html .= '<div class="col-md-12 col-sm-12 col-xs-12 col-ss-12">';
        else if ($tool["close_btn_down"] == 0 || $tool["close_batch"] == 0)    //按钮和批量操作其中一个存在，查询列占10
            $html .= '<div class="col-md-10 col-sm-10 col-xs-10 col-ss-10">';

        if (isset($tool["close_tool_find"]) && $tool["close_tool_find"] != 1)
            $html .= $this->find_html($tool);
        $html .= '</div>';

        if ($tool["close_btn_down"] == 0 && $tool["close_batch"] == 0)
            $html .= '<div class="col-md-3 col-sm-3 col-xs-3 col-ss-12 text-right">';
        else if ($tool["close_btn_down"] == 0 || $tool["close_batch"] == 0)
            $html .= '<div class="col-md-2 col-sm-2 col-xs-2 col-ss-12 text-right">';

        if ($tool["close_btn_down"] != 1)
        {
            $html .= $tool["tool_btn_down"]["front"];
            $html .= '<button class="btn btn-primary '.$tool["tool_btn_down"]["class"].'" '.$tool["tool_btn_down"]["link"].'>';
            if ($tool["tool_btn_down"]["icon"])
                $html .= '<span class="glyphicon glyphicon-'.$tool["tool_btn_down"]["icon"].'"></span>&nbsp;';
            $html .= $tool["tool_btn_down"]["txt"].'</button>';
            $html .= $tool["tool_btn_down"]["end"]."&nbsp;";
        }

        if (isset($tool["close_batch"]) && $tool["close_batch"] != 1)
        {
            if ($tool["my_tool_batch"])
               $html .= $tool["my_tool_batch"];
            else
            {
                $html .= '<div class="btn-group text-left">';
                $html .= '<button class="btn btn-primary dropdown-toggle" data-toggle="dropdown">';
                $html .= '<span class="glyphicon glyphicon-list"></span>&nbsp;批量操作 ';
                $html .= '<span class="caret"></span></button><ul class="dropdown-menu" role="menu">';
                foreach ($tool["tool_batch"] as $vo)
                {
                    $html .= '<li class='.$vo["li_class"].'>'.$vo["front"];
                    $html .= '<a href="'.$vo["href"].'" name="'.$vo["name"].'" class="'.$vo["class"].'" '.$vo["link"].'>';
                    if ($vo["icon"])
                        $html .= '<span class="glyphicon glyphicon-'.$vo["icon"].'"></span>&nbsp;';
                    $html .= $vo["txt"].'</a>'.$vo["end"].'</li>';
                }
                $html .= '</ul></div>';
            }
        }

        $html .= '</div>';   //对应按钮和批量操作列结束符
        $html .= '</div>';   //对应查询行结束符

        if ($tool["close_tool_div"] != 1)
            $html .='</div>';

        return $html;
    }
}

?>
