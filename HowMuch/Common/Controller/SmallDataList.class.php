<?php
namespace Common\Controller;

// 主页数据列表类
class SmallDataList extends DataList
{
    private $name;

    public function __construct($name, $model = "", $obj_type = 0, $option = array())
    {
        parent::__construct($model, $obj_type, $option);

        $this->name = $name;

        $this->set("close_top_page", 1);
        $this->set("close_chkall", 1);
        $this->set("close_op", 1);
        $this->set("data_class", "kyo_index_".$name);
        // $this->set("data_table_class", "table-condensed");
        if (isset($option["page"]) && !$option["page"]["size"])
            $this->setPage("size", 8);
        $this->setPage("tag", ".kyo_index_".$name);
        $this->setPage("param", "small=".$name);
        
        return true;
    }

    public function fetch()
    {
        if (isset($_GET["small"]) && $_GET["small"] == $this->name)
        {
            echo parent::fetch(); 
            exit(0);
        }
        return parent::fetch();
    }
}
?>
