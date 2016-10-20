<?php

class adb {
  public $devices = array();
  private $models = array(
    "KFFOWI"=>"Amazon Fire",
    "SM_T713"=>"Samsung Galaxy Tab",
    "XT1072"=>"Motorola G",
    "SM_G920F"=>"Samsung Galaxy S6",
  );

  function __construct() {
    shell_exec('adb start-server');
    $this->devices = $this->getDevices();
  }

  private function getDevices() {
    $output = explode("\n",shell_exec('adb devices -l'));
    $devices = array();
    array_shift($output);
    foreach ($output as $line) {
      $arr=array();
      $arr = explode('device ',$line);
      $id = rtrim($arr[0]);
      if ($id=="") continue;
      $devices[$id]=array();
      $data = explode(" ",$arr[1]);
      foreach ($data as $datum) {
        $pair = explode(":",$datum);
        $devices[$id][$pair[0]]=$pair[1];
        if ($pair[0]=='model') {
          if (array_key_exists($pair[1],$this->models)) {
            $devices[$id]['name'] = $this->models[$pair[1]];
          }
        }
      }
    }
    return $devices;
  }

  public function wakeDevice($id) {
    //$result =  shell_exec()
    $state = $this->getWakeStateByID($id);
    //return $awake;
    if (!$state['awake']) {
      $this->wakeDeviceByID($id);
      $state = $this->getWakeStateByID($id);
      if (!$state['awake']) die ("Could not wake device $id");
      if ($state['locked']) $this->unlockDeviceByID($id);
    }
    return true;
  }

  public function openURL($url,$ids=false) {
    if (!$ids) ($ids = array_keys($this->devices));
    elseif (is_string($ids)) $ids = array($ids);
    elseif (!is_array($ids)) die ("openURL expects \$ids to be an array of device ids");
    $status = array();
    foreach ($ids as $id) {
      $status[$id]=$this->openURLByID($url,$id);
    }
    return $status;
  }

  public function takeScreenshot($id,$filename) {
    return shell_exec("adb -s $id shell screencap -p | perl -pe ".escapeshellarg('s/\x0D\x0A/\x0A/g')." > $filename");
  }

  private function openURLByID($url,$id) {
    return shell_exec("adb -s $id shell am start -a android.intent.action.VIEW -d $url");
  }

  private function wakeDeviceByID($id) {
    return shell_exec("adb -s $id shell input keyevent KEYCODE_POWER");
  }

  private function unlockDeviceByID($id) {
    shell_exec("adb -s $id shell input keyevent 82");
  }

  private function getWakeStateByID($id) {
    //Power state
    $result = shell_exec("adb -s $id shell dumpsys power | grep \"Display Power:\"");
    $pos = strpos($result,'=');
    if ($pos < 1) die ("Could not get wake state for device $id./n".$result);
    $str = trim(substr($result,$pos+1));
    $awake = ($str=='OFF'?false:true);
    $locked = false;
    if ($awake) {
      //Lock state
      $result = shell_exec("adb -s $id shell dumpsys power | grep \"mUserActivityTimeoutOverrideFromWindowManager\"");
      $pos = strpos($result,'=');
      if ($pos < 1) die ("Could not get wake state for device $id./n".$result);
      $str = trim(substr($result,$pos+1));
      $locked = ($str=='-1'?false:true);
    }
    return array('awake'=>$awake,'locked'=>$locked);
  }
}

$adb = new adb;
$devices = $adb->devices;

foreach ($devices as $id=>$device) {
  echo $device['name']." ($id)";
  $adb->wakeDevice($id);
  $adb->openURL('http://www.fbd.ie',$id);
  sleep(100);
  $adb->takeScreenshot($id,"$id.png");
}
 ?>
