<?

// если вход не с индексного файла
if(!@$en){
  header("Location: /");
}



if(@$_GET['sw']){
    $id = intval($_GET['id']);
    if($_GET['sw']=='on'){
      start_gen("ecmg", $id);
      mysql_query2("UPDATE `tvcas_ecmg` SET `enable`=1 WHERE `id`={$id};");
    }elseif($_GET['sw']=='off'){
      stop_gen("ecmg", $_GET['id']);
      mysql_query2("UPDATE `tvcas_ecmg` SET `peers`='',`enable`=0 WHERE `id`={$id};");
      mysql_query2("DELETE FROM `tvcas_ecmg_log` WHERE `ecmg_id`={$id};");
    }
    usleep(100000); // 0.1 sec
    header("Location: /?op=ecmg");
    
}







/*

PPPPPP    AAA     GGGG  EEEEEEE 
PP   PP  AAAAA   GG  GG EE      
PPPPPP  AA   AA GG      EEEEE   
PP      AAAAAAA GG   GG EE      
PP      AA   AA  GGGGGG EEEEEEE 
                                

*/

$result = mysql_query2("SELECT `id`,`info`,`port`,`key`,`peers`,`touch_time` FROM `tvcas_ecmg` ORDER BY `c_time`;");


$a = array();
while($row = mysql_fetch_assoc($result)){
  $state = status_gen('ecmg', $row['id']);
  $peers = @unserialize($row['peers']);
  if(is_array($peers)){
    foreach($peers as $k=>$v){
      $peers[$k] = daynow(date("d.m.Y H:i:s", t($v['time']))) . " - {$k}, cw={$v['cw_count']}, ecm={$v['ecm_count']}";
      if($v['time']<time()-20) $peers[$k] = "<span style='color:#aaa;'>{$peers[$k]}</span>";
    }
  }

  $row['info'] .= (chk(block_get('config'), 'ecm_key') != $row['key'] ? "<i class='fas fa-exclamation-triangle' style='color:red; margin-left:10px;' title='ECM key doesnt match'></i>" : "");
  unset($row['key']);
  $row['peers'] = @implode("<br />", @$peers);
  $row['touch_time'] = ($row['touch_time']==0?"-":daynow(date("d.m.Y H:i:s", t($row['touch_time']))));
  $row['OPER'] = "<a href='/?op=ecmg&id={$row['id']}&sw=" . ($state?"off' title='Switch Off'":"on' title='Switch On'") . "'><i class='fas fa-circle text-" . ($state ? "success" : "danger") . "'></i></a>";

  $a[] = $row;
}



echo "<div style='text-align:right; margin: 10px 20px;height:30px;'></div>";


echo tablesorter($a);












?>