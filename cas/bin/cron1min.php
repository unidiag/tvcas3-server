#!/usr/bin/php
<?

require_once(__DIR__ . "/functions.php");



$time = time();
// emmg
$result = mysql_query2("SELECT * FROM `tvcas_emmg` WHERE `enable`=1;");
while($row = mysql_fetch_assoc($result)){
  $o = array();
  exec("ps aux | grep 'emmg.php --generator-id={$row['id']}'", $o);
  //if($row['reload_time']<($time-$row['reload_sec']) or $row['reload']){
  if(count($o)!=6){
    log_d(0, "Reload emmg #{$row['id']} : " . ($row['reload']?"reload=1":"timeout>{$row['reload_sec']}"));
    stop_gen('emmg', $row['id']);
    sleep(1);
    start_gen('emmg', $row['id']);
    mysql_query2("UPDATE `tvcas_emmg` SET `reload`=0, `reload_time`={$time} WHERE `id`={$row['id']};");
  }
}




// ecmg
// проверка enable при старте системы
$result = mysql_query2("SELECT `id` FROM `tvcas_ecmg` WHERE `enable`=1;");
while($row = mysql_fetch_assoc($result)){
  if(!status_gen('ecmg', $row['id'])){
    start_gen("ecmg", $row['id']);
  }
}

?>