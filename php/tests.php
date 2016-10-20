<?php

require('android.php');

$adb = new adb;
$devices = $adb->devices;

var_dump($devices);

foreach ($devices as $id=>$device) {
  echo $device['name']." ($id)";
  $adb->wakeDevice($id);
  $adb->openURL('http://www.fbd.ie',$id);
  sleep(1);
  $adb->takeScreenshot($id,"$id.png");
}

?>
