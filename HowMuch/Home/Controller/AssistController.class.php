<?php
namespace Home\Controller;
use Common\Controller\ListPage;
use Common\Controller\KyoExcel;
use Common\Controller\Form;
use Common\Controller\FormElement;

class AssistController extends ListPage 
{
    public function __construct()
    {
        parent::__construct();
        if (get_user_info("admin") != 7)
            $this->redirect("Index/index");
    }

    public function index()
    {
        $js = '$.ajax({url:"'.U("KyoCommon/Message/index").'", async:true, dataType:"html", 
                        success:function(data)
                        { 
                            $("#body").html(data);
                        }});';
        $this->display(false, "Home@Public/index");
        echo js_head($js);
    }
    
    
    public function filingFail($code)
    {
        if (IS_POST)
        {
            $return = array("echo" => 1, "close" => 1, "info" => "操作成功!",
                    "url" => session("prev_urlcard"), "tag" => ".card", "callback" => "");
            
            M("card")->where("code='".$_POST["code"]."'")
                    ->save(array("status" => NO_PASS, "temp_status" => NORMAL, "update_time" => getcurtime()));
            
            save_operating_record($_POST["code"], CARD_AUDIT_NO_PASS);
            
            $this->ajaxReturn($return);
            exit(0);
        }
        
        $aut = new Form("", array("name" => "filingform"));
        $aut->setElement("remark", "textarea", "", array("close_label" => 1, "element_cols" => 12, 
                "rows" => 8, "bool" => "required"));
        $aut->setElement("code", "hidden", "", array("value" => I("get.code")));
        $aut->set("btn 0 txt", "提交");
        echo $aut->fetch();    
    }
    
    //信用卡报备操作选项
    public function filingOp($data)
    {
        $fail = new FormElement("btn_fail", "link", '报备失败', array("url" => U("Assist/filingFail")."&code=".$data["code"], 
                "pop" => "w:480,h:360,n:'fadd',t:报备失败提交"));
        
        $path = __UP__.str_replace(",", "/", $data["card_img1"]);
        if (!file_exists($path))
            return $fail->fetch().'&emsp;<span class="kyo_red">图片缺失</span>';
        
        $lnk = new FormElement("btn_img", "link", '查看图片', array("query" => false, "bool" => "kopen",
                "ext" => 'url ="'.$path.'" target="_blank"',
        ));
        
        return $fail->fetch()."&emsp;".$lnk->fetch();
    }
    
    //信用卡报备确认
    public function filingVerify($where)
    {
        $where = ltrim($where, "'");
        $where = rtrim($where, "'");
        M("card")->where($where)->save(array("update_time" => getcurtime(), "temp_status" => NORMAL));
        $this->ajaxReturn(array("echo" => 1, "info" => "确认成功!",
                "url" => session("prev_urlcard"), "tag" => ".card"));
    }
    
    //信用卡报备功能
    public function filing()
    {
        if (I("get.down"))
        {
            $root = "Down/";
            $filepath = $root."filing_".date("Y-m-d").".zip";
            unlink($root."filing*.zip");
            
            $title = array("name" => "卡主姓名", "bank" => "发卡行", "card" => "卡号", "code" => "分站代码", "proxy_sub_name" => "所属分站", "create_time" => "录入时间");
            $card = sqlAll("select b.name,u.proxy_sub_name,u.code,c.card,c.bank,c.create_time,c.card_img1
                        from card c, users u, basis b where c.bid=b.id and c.sid=u.id 
                        and c.temp_status=".FILING." and c.create_time < '".date("Y-m-d")."'");
            if (!$card)
            {
                echo ("<h1>没有可报备的卡!</h1>");
                exit(0); 
            }
                
            $xls_path = $root."filing.xls";
            
            $excel = new KyoExcel($title, $card, $xls_path, 1);
            $excel->export();
            
            import("Org.Util.PclZip");
            $zip = new \PclZip($filepath);
            $zip->add($xls_path);
            
            $imglist = array();
            $imgNum = 0;
            foreach ($card as $cd)
            {
                $path = __UP__.str_replace(",", "/", $cd["card_img1"]);
                if (!file_exists($path))
                    continue;
                if (!file_exists($root.$cd["code"]))
                    mkdir($root.$cd["code"]);
                $new_path = $root.$cd["code"]."/".$cd["card"].".jpg";
                copy($path, $new_path);
                $imglist[$imgNum++] = $new_path;
                $zip->add($new_path);
            }
            $zip->extract();
            
            unlink($xls_path);
            foreach ($imglist as $img)
            {
                unlink($img);
            }
            header("Location:".$filepath);
            
            exit(0);
        }
        
        $this->setNav("&nbsp;->&nbsp;新卡报备");
        $this->mainPage("card");
        $this->setFind("item 1", array("name" => "sid", "type" => "select", "default" => "所有分站",
                "list" => parse_select_list("select id,proxy_sub_name from users where type=6")));
        $this->setFind("item 2", array("name" => "search_type", "type" => "select", 
                "default" => "卡号", "defval" => "card"));
        
        $this->setTool("tool_btn_down", array("txt" => "打包下载", "url" => U()."&down=1", 
                "query" => true, "name" => "excel", "bool" => "kopen", 'icon' => "cloud-download"));
        
        $this->setBatch("确认报备", U("Assist/filingVerify")."&op=verify", array('icon' => "lock", 
                "query" => "true", 'ext' => 'chktype="1"'));
        
        $this->setTitle(array("卡主姓名", "发卡行", "卡号", "所属分站", "录入时间"));
        $this->setField(array("bid", "bank", "card", "sid", "create_time"));
        $this->setData("where", "temp_status=".FILING." and create_time < '".date("Y-m-d")."'");
        $this->setData("op_call", array("run", "Assist/filingOp"));
        $this->display();    
    }
}
