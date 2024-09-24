<?

include __DIR__ . "/includes/config.php";
include __DIR__ . "/cas/bin/functions.php";

//api.php?api_key=XXXXXXXXX&serial_no=2100000000&set[name]=Vasya%20Pupkin&set[info]=Moscow%20Pupkin%20st&set[start]=123123123&set[finish]=12342134123&set[access_criteria]=00000001


if($config['api_key']!=@$_GET['api_key']){
  sleep(3);    // антиперебор
  ddie("NOT_VALID_API_KEY");
}

$_SERVER['PHP_AUTH_USER'] = "API";


$db = mysqli_connect($config['mysql_server'], $config['mysql_user'], $config['mysql_pass'], $config['mysql_base']);
$db->set_charset("utf8");


$sql = "SELECT `serial_no`, `name`, `info`, `access_criteria`, `pair`, `start`, `finish` FROM `tvcas_smartcards` WHERE `serial_no`=" . intval($_GET['serial_no']) . ";";
if($r = mysqli_fetch_assoc($db->query($sql)) and $r['serial_no']){

  $s = array();
  if(is_array($_GET['set'])){
    foreach($_GET['set'] as $k=>$v){
      if(!isset($r[$k])) ddie('UNKNOWN_SET_PARAMETER');
      if($k=='access_criteria' and !preg_match("/[0-9A-F]{8}/i", $v)) ddie("ACCESS_CRITERIA_ERROR");
      if($k=='pair' and !preg_match("/[0-1]{1}/", $v)) ddie("PAIR_ERROR");
      if($k=='start' and !preg_match("/[0-9]{10}/", $v)) ddie("START_ERROR");
      if($k=='finish' and !preg_match("/[0-9]{10}/", $v)) ddie("FINISH_ERROR");
      $s[] = "`{$k}`='" . $db->real_escape_string(urldecode($v)) . "'";
    }
    $ssql = "UPDATE `tvcas_smartcards` SET " . implode(", ", $s) . " WHERE `serial_no`={$r['serial_no']};";
    $db->query($ssql);
    logs("Smartcard {$r['serial_no']}. " . implode("; ", $s));
    reload_emm();
  }
  
  echo json_encode(mysqli_fetch_assoc($db->query($sql)));
  exit;
  
}



ddie("SMARDCARD_NOT_FOUND");



function ddie($s){
  header('HTTP/1.0 400 Bad Request');
  die($s);
}

?>