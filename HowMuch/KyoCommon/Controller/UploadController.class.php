<?php
namespace KyoCommon\Controller;
use Think\Controller;

class UploadController extends Controller
{
    public function index()
    {
    	$uptypes = array("image/jpg", "image/png", "image/jpeg", "image/pjpeg", "image/gif", "image/bmp", "image/x-png");
    	$max_file_size = 5000000;
	
        $item = "file_".I("get.name");
        if (!$item)
        {
            $this->assign("error", "非法操作!");
            $this->display();
            exit(0);
        }
        
        $table = I("get.table") ? I("get.table") : "other";
        
        $auth = I("session.user_auth");
        if ($auth["admin"] == 9 || $auth["admin"] == 0 || $auth["admin"] == 7)
            $path = __UP__."/".$table;
        else
            $path = __UP__."/".$table."/".$auth["code"];
        if (!is_dir($path))
            mkdir($path, 0777, true);
        
        $path .= "/".$item."_".I("get.filename").".jpg";
        
    	$name = $_FILES[$item]["name"];
    	$type = $_FILES[$item]["type"];
    	$size = $_FILES[$item]["size"];
    	$tmp_name = $_FILES[$item]["tmp_name"]; 
        $error = $_FILES[$item]["error"];
//     	dump("error=".$_FILES[$item]["error"].", item = ".$item." path=".$path." name =".$name." type = ".$type." size = ".$size);
    	if ($error == 1 || $max_file_size < $size) 
    	    $error = "上传文件太大,请控制在5M以内!";
        else if (!in_array($type, $uptypes))
            $error = "上传文件类型不符".$type."!";
        else if (!move_uploaded_file($tmp_name, $path))
            $error = "上传失败!";
        else
        {
            resize($path, 510, 360);
            $error = "";
        }
        
        $this->assign("name", I("get.name"));
        $this->assign("error", $error);
        $path = str_replace(__UP__."/", "", $path);
        $this->assign("value", $path);
        $path = str_replace("/", ",", $path);
        $this->assign("url", U('KyoCommon/Upload/upload_show').'&path='.$path);
        $this->assign("del", U('KyoCommon/Upload/upload_del', 'path='.$path));
        $this->display("KyoCommon@Upload/index");
//         $cc = $this->fetch();
//         $this->show($cc);     
    }
    
    //旋转图片
    public function rotateImg($path)
    {
        if (!file_exists($path))
            return 1;
        
        $data = getimagesize($path);
        if ($data == false)
            return 1;
        
        switch ($data[2])
        {
        	case 1:
                $src = imagecreatefromgif($path);
                break;
        	case 2:
                $src = imagecreatefromjpeg($path);
        	    break;
        	case 3:
                $src = imagecreatefrompng($path);
                break;
        }
        if (!$src)
            return 1;
        
        unlink($path);
        $rotate = imagerotate($src, 90, 0);
        if (!$rotate)
            return 1;
        
        if (!imagejpeg($rotate, $path, 100))
            return 1;
        
        imagedestroy($rotate);
        return 0;
    }    
    
    public function upload_show()
    {
        //如果class参数值为kyo_img_linkop  则点击图片会旋转
        $path = __UP__.str_replace(",", "/", I("get.path"));
        $class = I("get.class");
        $html = '<img src="'.$path.'" style="width:500px;height:310px;cursor:pointer;"
                class="'.$class.'" confirm="旋转图片操作直接影响原图，确定此操作吗？"
                    url="'.U().'&rotate=1&class='.$class.'&path='.I("get.path").'" tag="#show_uploadimg_div" />';
//         dump($path);
        if (I("get.rotate"))
            $html .= $this->rotateImg($path); //." ".mt_rand(1000, 9999);
        else
            $html = '<div id="show_uploadimg_div">'.$html.'</div>';
//             $this->show('<div id="show_uploadimg_div"><img src="'.$path.'" style="max-width:500px;max-height:300px;" 
//                     class="lnk_img" url="'.U().'&rotate=1&path='.I("get.path").'" tag="#show_uploadimg_div" /></div>');
// dump($html);
        $this->show($html);
        exit(0);
    }
    
    public function upload_del()
    {
        $path = __UP__.str_replace(",", "/", I("get.path"));
        unlink($path);
    }
}

?>
