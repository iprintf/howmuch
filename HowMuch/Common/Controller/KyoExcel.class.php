<?php
namespace Common\Controller;

class KyoExcel 
{
    private $title;
    private $data;
    private $path;
    private $format;
    private $filename;
    private $objExcel;
    
    public function __construct($title = "", $data = "", $path = "", $format = 0)
    {
        //兼容旧版本参数
        if ($title == "" && $data == "")
        {
            if (!isset($_SESSION["excel"]) || $_SESSION["excel"] == "")
                return false;
            
            $title = $_SESSION["excel"]["title"];
            if ($_SESSION["excel"]["data"])
                $data = $_SESSION["excel"]["data"];
            else
                $data = $_SESSION["excel"]["sql"];
            $this->filename = $_SESSION["excel"]["filename"];
//             dump($this->filename);
//             dump($data);
//             exit(0);
        }
        else
            $this->filename = date("YmdHis").mt_rand(10, 99);
        
        $this->title = $title;
        if (is_array($data))
            $this->data = $data;
        else
            $this->data = sqlAll($data);
        $this->path = $path;
        
        $this->format = $format;
        
        import("Org.Util.PHPExcel");
        $this->objExcel = new \PHPExcel();
        if (!$this->objExcel)
            return false;
           
        return true;
    }
    
    //准备excel显示的数据
    public function parseExcelData()
    {
        $obj = $this->objExcel;
        $index = "A";
        //设置标题
        foreach ($this->title as $key => $title)
        {
            if ($title == "操作" || $title == "选择")
            {
                unset($this->title[$key]);
                continue;
            }
            $obj->getActiveSheet()->setCellValue($index."1", $title);
            $obj->getActiveSheet()->getStyle($index."1")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $obj->getActiveSheet()->getStyle($index."1")->getFont()->setBold(true);
            $obj->getActiveSheet()->getColumnDimension($index)->setWidth(20);
            //     $obj->getActiveSheet()->getStyle($index."1")->getNumberFormat()->setFormatCode("@");
            $index++;
        }
        
        //设置数据
        $l = 2;
        foreach ($this->data as $row)
        {
            $index = "A";
            if ($_SESSION["excel"]["callback"])
            {
                if ($_SESSION["excel"]["callback"][0] == "run")
                {
//                     dump($row);
//                     R("KyoCommon/Deal/excelFormatField", array(& $row));
                    R($_SESSION["excel"]["callback"][1], array(& $row));
                }
                else
                    $_SESSION["excel"]["callback"][1]($row);
//                 dump($row);
            }
            foreach ($this->title as $name => $title)
            {
                if ($this->format == 0)
                    $val = DataList::formatField($row, $name, "excel");
                else
                    $val = $row[$name];
//                 dump($row);
//                 dump($name." ".$val);
                if (is_float($val))
                    $obj->getActiveSheet()->setCellValueExplicit($index++.$l, $val,
                             \PHPExcel_Cell_DataType::TYPE_FORMULA);
                else if (is_integer($val))
                    $obj->getActiveSheet()->setCellValueExplicit($index++.$l, $val, 
                            \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                else
                    $obj->getActiveSheet()->setCellValueExplicit($index++.$l, $val, 
                            \PHPExcel_Cell_DataType::TYPE_STRING);
            }
            $l++;
        }
//         exit(0);
    }
    
    //导出报表之下载文件
    public function exportDown()
    {
        ob_end_clean();
        header("Content-type: text/html;charset=utf-8");
//         dump(session("excel"));
//         exit(0);

        $this->parseExcelData();
        
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=".$this->filename.".xls");
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');
        
        // If you're serving to IE over SSL, then the following may be needed
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        
        $objwrite = \PHPExcel_IOFactory::createWriter($this->objExcel, 'Excel5');
//         $objwrite = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel2007');
        $objwrite->save("php://output");
        
        $_SESSION["excel"]["filename"] = null;
        echo "<script>window.close();</script>";
        exit(0);
    }
    
    //导出报表
    public function export()
    {
        if (!$this->data)
            return "报表暂无数据，无法导出!";
        
        //如果没有路径代表要下载文件
        if (!$this->path)
            $this->exportDown();
        
        //如果有路径代表自动生成在某个路径
        $this->parseExcelData();
        $objwrite = \PHPExcel_IOFactory::createWriter($this->objExcel, 'Excel5');
        $objwrite->save($this->path);
        $_SESSION["excel"]["filename"] = null;
        return "";
    }
    
    //导入报表
    static public function import($name = "file_input_excel")
    {
        $ret = array("error" => "", "data" => "");
        
        if (!IS_POST)
        {
            $ret["error"] = "非法操作!";
            return $ret;
        }
        
        $type = $_FILES[$name]["type"];
        if ($type != "application/vnd.ms-excel")
        {
            $ret["error"] = "格式出错，请选择excel表格文件!";
            return $ret;
        }
        import("Org.Util.PHPExcel");
        $objExcel = \PHPExcel_IOFactory::load($_FILES[$name]["tmp_name"]);
        if (!$objExcel)
        {
            $return["error"] = "excel文件格式不兼容!";
            return $ret;
        }
        $ret["data"] = $objExcel->getSheet(0)->toArray();
        if (!$ret["data"])
            $return["error"] = "excel文件sheet1中没有数据!";
        
        return $ret;
    }
}
