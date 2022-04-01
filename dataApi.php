<?php
$data = array();
if(isset($_GET['temperatur'])){
    $data["temperatur"] = $_GET['temperatur'];
}
if(isset($_GET['humidity'])){
    $data["humidity"] = $_GET['humidity'];
}
if(count($data) > 1){
    $myfile = fopen("restdata/restdata.txt", "w") or die("Unable to open file!");
    fwrite($myfile, json_encode($data));
    fclose($myfile);
}
echo(file_get_contents("restdata/restdata.txt"));
?>