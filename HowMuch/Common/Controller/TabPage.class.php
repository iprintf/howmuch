<?php
namespace Common\Controller;
use Think\Controller;

// 标签页类
class TabPage extends Controller
{
    public $tab = array();
        //id
    	//default
        //list

    public function __construct($id = "mytab", $option = array())
    {
        parent::__construct();
        $this->tab["id"] = $id;
        $this->tab["default"] = "";
        return true;
    }

    //设置配置
    public function set($name, $value)
    {
        if ($name == "")
            return false;

        $name_sel = explode(" ", $name);
        $name_len = count($name_sel);
        if ($name_len == 1)
            $this->tab[$name] = $value;
        else
        {
            $obj = & $this->tab;
            for ($i = 0; $i < $name_len; $i++)
            {
                $obj = & $obj[$name_sel[$i]];
            }

            $obj = $value;
        }

        return true;
    }

    //获取配置参数,为了支持自定义操作
    public function get($name = "")
    {
        if ($name == "")
            return $this->tab;

        $name_sel = explode(" ", $name);
        $name_len = count($name_sel);
        if ($name_len == 1)
            return $this->tab[$name];

        $obj = & $this->tag;
        for ($i = 0; $i < $name_len; $i++)
        {
            $obj = & $obj[$name_sel[$i]];
        }

        return $obj;
    }

    public function setTab($name, $txt, $content)
    {
        if ($name == "" || $txt == "")
            return false;
        if (!$this->tab["default"])
            $this->tab["default"] = $name;
        $this->tab["list"][$name]["name"] = $name;
        $this->tab["list"][$name]["txt"] = $txt;
        $this->tab["list"][$name]["content"] = $content;
        return true;
    }

    public function fetch($templatefile = "", $content = "", $prefix = "")
    {
        $tab = $this->tab;
        $html = "";

        $html = '<ul id="'.$tab["id"].'" class="nav nav-tabs" role="tablist">';
        foreach ($this->tab["list"] as $data)
        {
            if ($this->tab["default"] == $data["name"])
                $active = "active";
            else
                $active = "";
            $html .= '<li class="'.$active.'">';
            $html .= '<a href="#'.$data["name"].'" role="tab" data-toggle="tab">'.$data["txt"].'</a>';
            $html .= '</li>';
        }
        $html .= '</ul>';
        $html .= '<div id="'.$tab["id"].'Content" class="tab-content">';
        foreach ($this->tab["list"] as $data)
        {
            if ($this->tab["default"] == $data["name"])
                $active = "in active";
            else
                $active = "";
            $html .= '<div class="tab-pane fade '.$active.'" style="padding:10px;" id="'.$data["name"].'">'.$data["content"].'</div>';
        }
        $html .= '</div>';
//         dump($html);

        return $html;
    }
}
