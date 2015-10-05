<?php
//自动生成条形码，text为生成条形码代码

$text = $_GET["text"];

require_once("BCGFontFile.php");
require_once("BCGColor.php");
require_once("BCGDrawing.php");
require_once("BCGcode128.barcode.php");


$font = new BCGFontFile("./Arial.ttf", 18);

$color_black = new BCGColor(0, 0, 0);
$color_white = new BCGColor(255, 255, 255);

$drawException = null;
try
{
    $code = new BCGcode128();
    $code->setScale(2);
    $code->setThickness(30);
    $code->setForegroundColor($color_black);
    $code->setBackgroundColor($color_white);
    $code->setFont($font);
    $code->parse($text);
}
catch (Exception $exception)
{
    $drawException = $exception;
}

$drawing = new BCGDrawing("", $color_white);
if ($drawException)
{
    $drawing->drawException($drawException);
}
else
{
    $drawing->setBarcode($code);
    $drawing->draw();
}

header("Content-Type: image/png");
header('Content-Disposition: inline; filename="barcode.png"');

$drawing->finish(BCGDrawing::IMG_FORMAT_PNG);

?>
