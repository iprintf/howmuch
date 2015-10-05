<?php
namespace Common\Controller;

class KyoMail 
{
    private $objMail;
    private $sid;
    private $uid;
    
    public function __construct()
    {
        $this->sid = get_user_info("sid");
        $this->uid = get_user_info("uid");
        
        import("Org.Util.PHPMailer");
        $this->objMail = new \PHPMailer();
        if (!$this->objMail || !$this->uid || !$this->sid)
            return false;
            
        $this->objMail->isSMTP();
        $this->objMail->Host = "218.107.63.213";  //163邮箱服务器
        $this->objMail->Port = 25;
        $this->objMail->CharSet = "UTF-8";
        $this->objMail->Encoding = "base64";
        $this->objMail->SMTPAuth = true;
        
        $code = sqlCol("select code from users where id=".$this->sid);
        $this->objMail->Username = "m".$code;
        $this->objMail->Password = $code;
        $this->objMail->setFrom("m".$code.'@163.com', 'System');
        $recv_mail = sqlRow("select username,email from users where id=".$this->uid);
        $this->objMail->addAddress($recv_mail["email"], $recv_mail["username"]);
        return true;
    }
    
    public function export($msgTitle = "导出报表", $title = "", $data = "")
    {
        header("Content-type: text/html;charset=utf-8");
        $path = RUNTIME_PATH.date("YmdHis").mt_rand(10,99).".xls";
        
        //生成报表文件
        $excel = new KyoExcel($title, $data, $path);
        if (!$excel)
            return "生成报表失败!";
        $str = $excel->export();
        if ($str)
            return $str;
        
        $this->objMail->Subject = $msgTitle;      //邮件标题
        $this->objMail->msgHTML($msgTitle);
//         $this->objMail->addAttachment(RUNTIME_PATH."2014081218192974.xls");    //添加附件
        $this->objMail->addAttachment($path);
        
        //发送邮件，并且 显示发送错误信息
        if (!$this->objMail->send())
            $str = "导出失败, 你检查网络或接受邮件账号是否错误!".$this->objMail->ErrorInfo;
        else 
            $str = "导出报表成功，请登录邮箱查收!";
        
        unlink($path);
        
        return $str;
    }
    
    public function printf($title, $html, $path)
    {
        //生成附件文件
        $fp = fopen($path, "w");
        fwrite($fp, $html);
        fclose($fp);
        
        $this->objMail->Subject = $title;      //邮件标题
        $this->objMail->msgHTML($html);
        $this->objMail->addAttachment($path);
        //发送邮件，并且 显示发送错误信息
        if (!$this->objMail->send())
            $str = "发送失败, 你检查网络或接受邮件账号是否错误!";//.$this->objMail->ErrorInfo;
        else 
            $str = "发送成功，请登录邮箱查收!";
        unlink($path);
        return $str;
    }
}
