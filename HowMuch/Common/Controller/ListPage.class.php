<?php

namespace Common\Controller;
use Think\Controller;

class ListPage extends Controller
{
    private $cur = array();     //主数据页公共设置
//         nav = "";          //主页导航栏
//         tool               工具栏内容
//         data               数据栏内容
//         close_nav = 0;     //开关主页导航栏
//         close_tool = 0;     //开关主页工具栏
//         close_data = 0;     //开关主页数据栏
//         nav_class           自定义导航样式
//         data_tpl             数据自定义模板
//         tool_tpl             工具自定义模板
    public $tool = "";
    public $data = "";
    public $form = "";

    public function __construct()
    {
        parent::__construct();
        //if (!is_login())
            //$this->redirect("Home/Index/index");
    }

    protected function _initialize()
    {
//         dump(__SELF__);
//         dump( "/home/".strtolower(get_platfrom_name())."/index");
//         session("prev_url", null);
        init_array($this->cur, array("nav", "tool", "data", "nav_class", "tool_class", "data_class",
            "data_tpl", "tool_tpl", "main_ext"));
        init_array($this->cur, array("close_nav", "close_tool", "close_data", "close_body_top",
            "close_top_ctrl"), 0);
    }

    public function __destruct()
    {
        //如果是平台主页 则不记录上一页
        if (!strstr(__SELF__, "/home/".strtolower(get_platfrom_name())."/index"))
            session("prev_url", __SELF__);
    }

    public function index()
    {
        $this->display();
    }

    static public function sqlCol($sql)
    {
        $data = M()->query($sql);
        foreach ($data[0] as $val)
        {
            return $val;
        }
        return false;
    }

    static public function sqlAll($sql)
    {
        return M()->query($sql);
    }

    static public function sqlRow($sql)
    {
        $data = M()->query($sql);
        return $data[0];
    }

    protected function display($layout = false, $templatefile = '', $charset = '', $contenttype = '', $content = '', $prefix = '')
    {
        if (I("get.form"))
        {
            $this->show($this->form->fetch());
            return;
        }

        if (!file_exists(T($templatefile)))
        {
            if ($this->tool)
                $this->cur["tool"] = $this->tool->fetch($this->cur["tool_tpl"]);
            if ($this->data)
                $this->cur["data"] = $this->data->fetch($this->cur["data_tpl"]);
//             dump($this->cur);
            $this->assign($this->cur);
            $templatefile = T('Public/list_main');
//             dump(parent::fetch($templatefile));
        }
        else
            $templatefile = T($templatefile);

        if ($layout)
        {
            if ($content == "")
                $content = $this->fetch($templatefile);
            $content = '<extend name="Public/base" />
                        <block name="nav_list">
                            <include file="nav" />
                        </block>
                        <block name="body_content">'.$content;
            $content = $content."</block>";
            $this->show($content);
        }
        else
            parent::display($templatefile, $charset, $contenttype, $content, $prefix);
    }

    protected function set($name = "", $value = "")
    {
        if ("" == $name && "" == $value)
        {
            $this->cur["close_nav"] = 0;
            $this->cur["close_tool"] = 0;
            $this->cur["close_data"] = 0;
            $this->cur["nav"] = get_platfrom_name(true);
        }
        else
            $this->cur[$name] = $value;
    }

    protected function setNav($path = "")
    {
        if (!isset($this->cur["nav"]) || $this->cur["nav"] == "")
            $this->cur["nav"] = get_platfrom_name(true);

        if ($path != "")
            $this->cur["nav"] = $this->cur["nav"].$path;
    }

    protected function getNav()
    {
        return $this->cur["nav"];
    }

    protected function navFetch($templatefile = "")
    {
        if ($templatefile == "")
        {
            $this->assign("nav", $this->cur["nav"]);
            $this->assign("close_nav", $this->cur["close_nav"]);
            $templatefile = "Public/list_nav";
        }
        return $this->fetch($templatefile);
    }

    protected function mainPage($obj, $obj_type = 0, $option = "")
    {
        if (!$obj)
            return false;

        $this->tool = new PageTool();
        $this->data = new DataList($obj, $obj_type, $option);
        $this->form = new Form($this->data);
        $this->tool->set("close_tool_div", 0);
        $this->tool->set("close_data_div", 0);
        $this->data->set();
    }

    //////////////////////////////////////////////////////////////

    protected function getForm($name)
    {
        return $this->form->get($name);
    }

    protected function setForm($name, $value)
    {
        return $this->form->set($name, $value);
    }

    protected function setElementSort($name)
    {
        return $this->form->setElementSort($name);
    }

    protected function setElement($name, $type, $txt, $option = array())
    {
        return $this->form->setElement($name, $type, $txt, $option);
    }

    protected function setFormBtn($txt, $url, $option = array())
    {
        return $this->form->setBtn($txt, $url, $option);
    }

    //////////////////////////////////////////////////////////////

    protected function getTool($name)
    {
        return $this->tool->get($name);
    }

    protected function setTool($name = "", $value = "")
    {
        return $this->tool->set($name, $value);
    }

    protected function setBtn($name, $url, $option = array())
    {
        return $this->tool->setBtn($name, $url, $option);
    }

    protected function setLink($name, $txt, $url, $option = array())
    {
        return $this->tool->setLink($name, $txt, $url, $option);
    }

    protected function setBatch($name, $url, $option = array())
    {
        return $this->tool->setBatch($name, $url, $option);
    }

    protected function setFind($name, $value)
    {
        return $this->tool->setFind($name, $value);
    }

    //////////////////////////////////////////////////////////////
    protected function getData($name)
    {
        return $this->data->get($name);
    }

    protected function DataObj()
    {
        return $this->data->Obj();
    }

    protected function setData($name = "", $value = "")
    {
        return $this->data->set($name, $value);
    }

    protected function setDataHide($ind)
    {
        return $this->data->hide($ind);
    }

    protected function setPage($name, $value)
    {
        return $this->data->setPage($name, $value);
    }

    protected function getPage()
    {
        return $this->data->getPage();
    }

    protected function setCustomList($name, $total = true, $arg = "")
    {
        return $this->data->setCustomList($name, $total, $arg);
    }

    protected function setTitle($title, $option = array())
    {
        return $this->data->setTitle($title, $option);
    }

    protected function setField($field, $option = array())
    {
        return $this->data->setField($field, $option);
    }

    protected function setOp($txt, $url, $option = array())
    {
        return $this->data->setOp($txt, $url, $option);
    }

    protected function readTpl($templatefile, $tplVal = "")
    {
        return $this->data->readTpl($templatefile, $tplVal);
    }
}

?>
