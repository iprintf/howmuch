<?php

function show_pic_scal($width, $height, $picpath)
{
    $imginfo = getimagesize($picpath);
    $imgw = $imginfo[0];
    $imgh = $imginfo[1];

    $ra1 = number_format(($imgw / $imgh), 1); // 宽高比
    $ra2 = number_format(($imgh / $imgw), 1); // 高宽比

    if ($imgw > $width or $imgh > $height)
    {
        if ($imgw < $imgh)
        {
            $newHeight = $height;
            $newWidth = round($newHeight / $ra2);
        }
        else
        {
            $newWidth = $width;
            $newHeight = round($newWidth / $ra1);
        }
    }
    else
    {
        $newHeight = $imgh;
        $newWidth = $imgw;
    }
    $newsize[0] = $newWidth;
    $newsize[1] = $newHeight;

    return $newsize;
}

// 创建图片，返回资源类型
// @param string $src 图片路径
// @return resource $im 返回资源类型
function img_create($src)
{
    $info = getimagesize($src);
    switch ($info[2])
    {
    	case 1:
    	    $im = imagecreatefromgif($src);
    	    break;
    	case 2:
    	    $im = imagecreatefromjpeg($src);
    	    break;
    	case 3:
    	    $im = imagecreatefrompng($src);
    	    break;
    }
    return $im;
}

// 缩略图主函数
// @param string $src 图片路径
// @param int $w 缩略图宽度
// @param int $h 缩略图高度
// @return mixed 返回缩略图路径
function resize($src, $w, $h)
{
    $temp = pathinfo($src);
    $name = $temp["basename"]; // 文件名
    $dir = $temp["dirname"]; // 文件所在的文件夹
    $extension = $temp["extension"]; // 文件扩展名
    $savepath = "{$dir}/{$name}"; // 缩略图保存路径,新的文件名为*.thumb.jpg

    $newsize = show_pic_scal($w, $h, $src);
    $w = $newsize[0];
    $h = $newsize[1];

    // 获取图片的基本信息
    $info = getimagesize($src);
    $width = $info[0]; // 获取图片宽度
    $height = $info[1]; // 获取图片高度
    $per1 = round($width / $height, 2); // 计算原图长宽比
    $per2 = round($w / $h, 2); // 计算缩略图长宽比
     
    // 计算缩放比例
    if ($per1 > $per2 || $per1 == $per2)
    {
        // 原图长宽比大于或者等于缩略图长宽比，则按照宽度优先
        $per = $w / $width;
    }
    if ($per1 < $per2)
    {
        // 原图长宽比小于缩略图长宽比，则按照高度优先
        $per = $h / $height;
    }
    $temp_w = intval($width * $per); // 计算原图缩放后的宽度
    $temp_h = intval($height * $per); // 计算原图缩放后的高度
    $temp_img = imagecreatetruecolor($temp_w, $temp_h); // 创建画布
    $im = img_create($src);
    imagecopyresampled($temp_img, $im, 0, 0, 0, 0, $temp_w, $temp_h, $width,
    $height);
    if ($per1 > $per2)
    {
        imagejpeg($temp_img, $savepath, 100);
        imagedestroy($im);
        return addBg($savepath, $w, $h, "w");
        // 宽度优先，在缩放之后高度不足的情况下补上背景
    }
    if ($per1 == $per2)
    {
        imagejpeg($temp_img, $savepath, 100);
        imagedestroy($im);
        return $savepath;
        // 等比缩放
    }
    if ($per1 < $per2)
    {
        imagejpeg($temp_img, $savepath, 100);
        imagedestroy($im);
        return addBg($savepath, $w, $h, "h");
        // 高度优先，在缩放之后宽度不足的情况下补上背景
    }
}

// 添加背景
// @param string $src 图片路径
// @param int $w 背景图像宽度
// @param int $h 背景图像高度
// @param String $first 决定图像最终位置的，w 宽度优先 h 高度优先 wh:等比
// @return 返回加上背景的图片
function addBg($src, $w, $h, $fisrt = "w")
{
    $bg = imagecreatetruecolor($w, $h);
    $white = imagecolorallocate($bg, 255, 255, 255);
    imagefill($bg, 0, 0, $white); // 填充背景

    // 获取目标图片信息
    $info = getimagesize($src);
    $width = $info[0]; // 目标图片宽度
    $height = $info[1]; // 目标图片高度
    $img = img_create($src);
    if ($fisrt == "wh")
    {
        // 等比缩放
        return $src;
    }
    else
    {
        if ($fisrt == "w")
        {
            $x = 0;
            $y = ($h - $height) / 2; // 垂直居中
        }
        if ($fisrt == "h")
        {
            $x = ($w - $width) / 2; // 水平居中
            $y = 0;
        }
        imagecopymerge($bg, $img, $x, $y, 0, 0, $width, $height, 100);
        imagejpeg($bg, $src, 100);
        imagedestroy($bg);
        imagedestroy($img);
        return $src;
    }
}

?>
