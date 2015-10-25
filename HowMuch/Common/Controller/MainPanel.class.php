<?php
namespace Common\Controller;

// 主页数据列表类
class MainPanel extends SmallDataList
{
    public function __construct($name, $model = "", $option = "")
    {
        if (!isset($option["obj_type"]))
            $obj_type = 0;

        parent::__construct($name, $model, $obj_type, $option);

        return true;
    }

    static public function layout($min_html, $class = "", $col = 12)
    {
        $content = '<div class="col-md-'.$col.' '.$class.'">';
        $content .= $min_html;
        $content .= '</div>';

        return $content;
    }

    public function fetch($title = "", $option = array())
    {
//         dump($content);
        if (!isset($option["col"]) || $option["col"] == "")
            $option["col"] = 12;

        karray($option, array("class", "front", "title_class", "panel_class", "body_class", "body_front",
                    "body_end", "end"));

        $content = '<div class="col-md-'.$option["col"].' '.$option["class"].' main_first_row">'.$option["front"];
        $content .= '<div class="panel panel-default kyo_main_panel '.$option["panel_class"].'">';
        $content .='<div class="panel-heading '.$option["title_class"].'" style="text-align:left">'.$title.'</div>';
        $content .= '<div class="panel-body" class="'.$option["body_class"].'">'.$option["body_front"].parent::fetch().$option["body_end"].'</div>';
        $content .= '</div>'.$option["end"].'</div>';

        return $content;
    }
}
?>
