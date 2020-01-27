<?
header('Content-Type: image/png');
$file=$_GET["image"];
if(!file_exists($file)){$file="default.png";}
//print($file);
print_r(file_get_contents($file));
?>
