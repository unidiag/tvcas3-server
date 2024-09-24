<?

// если вход не с индексного файла
if(!@$en){
  header("Location: /cas");
}




/*

MM    MM  OOOOO  DDDDD     AAA   LL         EEEEEEE DDDDD   IIIII TTTTTTT    FFFFFFF  OOOOO  RRRRRR  MM    MM 
MMM  MMM OO   OO DD  DD   AAAAA  LL         EE      DD  DD   III    TTT      FF      OO   OO RR   RR MMM  MMM 
MM MM MM OO   OO DD   DD AA   AA LL         EEEEE   DD   DD  III    TTT      FFFF    OO   OO RRRRRR  MM MM MM 
MM    MM OO   OO DD   DD AAAAAAA LL         EE      DD   DD  III    TTT      FF      OO   OO RR  RR  MM    MM 
MM    MM  OOOO0  DDDDDD  AA   AA LLLLLLL    EEEEEEE DDDDDD  IIIII   TTT      FF       OOOO0  RR   RR MM    MM 
                                                                                                              

*/

if(@$_POST['op']=='frm'){
  ob_get_clean();
  
  $o = array();
  $id = intval(@$_POST['val']);
  
  $row = mysql_fetch_assoc(mysql_query2("SELECT `info`, `host`, `port`, `client_id`, `timeout` FROM `tvcas_emmg` " . ($id>0 ? "WHERE `id`={$id};" : "ORDER BY `id` DESC LIMIT 1;")));
  if(empty($row)) $row = array('info' => "MY NEW EMMG", 'host' => "192.168.1.2", 'port' => "41000", 'client_id' => 184549377, 'timeout' => 60);
  $row['client_id'] = numbFormat(dechex($row['client_id']), 8);
  
  
  
  // edit form
  if($id > 0){
    $title = "<i class='fas fa-edit'></i> Edit EMMG_{$id}";
    $o = array("<input type='hidden' name='op' value='edit' />");
    $o[] = "<input type='hidden' name='id' value='{$id}' />";  
  // add form
  }else{
    $o = array("<input type='hidden' name='op' value='add' />");
    $o[] = '<div class="alert alert-warning alert-dismissible fade show" role="alert"><strong>Attention!</strong> For convenience, adding data in fields is filled from the last added smartcard.<button class="close" type="button" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button></div>';
    $title = "<i class='fas fa-plus-circle'></i> Add new EMMG";
  }
  
  
  $i = 1;
  foreach($row as $k=>$v){
    $o[] = "<div class='input-group mb-3'><div class='input-group-prepend'><span id='basic-addon{$i}' class='input-group-text'>" . strtoupper($k) . "</span></div><input name='{$k}' placeholder='{$v}' value='{$v}' class='form-control' aria-describedby='basic-addon{$i}' type='text' autocomplete='off' /></div>";
    $i++;
  }
  
  echo json_encode(array('status' => "ok", 'title' => $title, 'body' => implode("", $o), 'footer' => "<button class='btn btn-primary'>" . ($id>0?"<i class='fas fa-edit'></i> Edit":"<i class='fas fa-plus-circle'></i> Add") . " EMMG</button>"));
  exit;

/*



  AAA   DDDDD   DDDDD      EEEEEEE MM    MM MM    MM   GGGG  
 AAAAA  DD  DD  DD  DD     EE      MMM  MMM MMM  MMM  GG  GG 
AA   AA DD   DD DD   DD    EEEEE   MM MM MM MM MM MM GG      
AAAAAAA DD   DD DD   DD    EE      MM    MM MM    MM GG   GG 
AA   AA DDDDDD  DDDDDD     EEEEEEE MM    MM MM    MM  GGGGGG 
                                                             



*/
}else if(@$_POST['op'] == 'add'){
  unset($_POST['op']);
  $_POST['c_time'] = time();
  $_POST['m_time'] = time();
  $_POST['client_id'] = hexdec($_POST['client_id']);

  foreach($_POST as $k=>$v){
    unset($_POST[$k]);
    $_POST["`".reredos($k)."`"] = "'" . reredos($v) . "'";
  }
  
  $sql = "INSERT INTO `tvcas_emmg` (" . implode(", ", array_keys($_POST)) . ") VALUES (" . implode(", ", $_POST) . ");";
  // echo $sql;
  mysql_query2($sql);
  list($newid) = mysql_fetch_row(mysql_query2("SELECT `id` FROM `tvcas_emmg` ORDER BY `id` DESC LIMIT 1;"));
  logs("Add new EMMG_{$newid}. Info: {$_POST['info']}");
  
  /*
  


RRRRRR  EEEEEEE MM    MM  OOOOO  VV     VV EEEEEEE    EEEEEEE MM    MM MM    MM   GGGG  
RR   RR EE      MMM  MMM OO   OO VV     VV EE         EE      MMM  MMM MMM  MMM  GG  GG 
RRRRRR  EEEEE   MM MM MM OO   OO  VV   VV  EEEEE      EEEEE   MM MM MM MM MM MM GG      
RR  RR  EE      MM    MM OO   OO   VV VV   EE         EE      MM    MM MM    MM GG   GG 
RR   RR EEEEEEE MM    MM  OOOO0     VVV    EEEEEEE    EEEEEEE MM    MM MM    MM  GGGGGG 
                                                                                        



*/
}else if(@$_POST['op'] == 'remove'){
  
  ob_get_clean();
  $id = intval($_POST['id']);
  mysql_query2("DELETE FROM `tvcas_emmg` WHERE `id`={$id};");
  stop_gen("emmg", $id);
  if(status_gen("emmg", $id))logs("Remove EMMG_{$id}");
  die("ok");
  
  
  /*



EEEEEEE DDDDD   IIIII TTTTTTT    EEEEEEE MM    MM MM    MM   GGGG  
EE      DD  DD   III    TTT      EE      MMM  MMM MMM  MMM  GG  GG 
EEEEE   DD   DD  III    TTT      EEEEE   MM MM MM MM MM MM GG      
EE      DD   DD  III    TTT      EE      MM    MM MM    MM GG   GG 
EEEEEEE DDDDDD  IIIII   TTT      EEEEEEE MM    MM MM    MM  GGGGGG 
                                                                   



*/
}else if(@$_POST['op'] == 'edit'){
  unset($_POST['op']);
  $id = intval($_POST['id']);
  $_POST['m_time'] = time();
  $_POST['client_id'] = hexdec($_POST['client_id']);
  unset($_POST['id']);

  $o = array();
  foreach($_POST as $k=>$v){
    $o[] = "`" . reredos($k) . "`='" . reredos($v) . "'";
  }
  
  $sql = "UPDATE `tvcas_emmg` SET " . implode(", ", $o) . " WHERE `id`={$id};";
  // echo $sql;
  mysql_query2($sql);
  logs("Edit EMMG_{$id}. {$_POST['host']}:{$_POST['port']}, Info: {$_POST['info']}");
  reload_emm();


/*

 OOOOO  NN   NN        //     OOOOO  FFFFFFF FFFFFFF    EEEEEEE  CCCCC  MM    MM   GGGG  
OO   OO NNN  NN       ///    OO   OO FF      FF         EE      CC    C MMM  MMM  GG  GG 
OO   OO NN N NN      ///     OO   OO FFFF    FFFF       EEEEE   CC      MM MM MM GG      
OO   OO NN  NNN     ///      OO   OO FF      FF         EE      CC    C MM    MM GG   GG 
 OOOO0  NN   NN    ///        OOOO0  FF      FF         EEEEEEE  CCCCC  MM    MM  GGGGGG 
                                                                                         

*/

}else if(@$_GET['sw']){
    $id = intval($_GET['id']);
    if($_GET['sw']=='on'){
      start_gen("emmg", $id);
      mysql_query2("UPDATE `tvcas_emmg` SET `enable`=1 WHERE `id`={$id};");
    }elseif($_GET['sw']=='off'){
      stop_gen("emmg", $_GET['id']);
      mysql_query2("UPDATE `tvcas_emmg` SET `touch_time`=0, `stream_time_open`=0, `datagram_time_last`=0, `datagram_count`=0, `queue_size`=0, `enable`=0 WHERE `id`={$id};");
    }
    usleep(100000); // 0.1 sec
    header("Location: /cas/?op=emmg");
    
}

























/*

PPPPPP    AAA     GGGG  EEEEEEE 
PP   PP  AAAAA   GG  GG EE      
PPPPPP  AA   AA GG      EEEEE   
PP      AAAAAAA GG   GG EE      
PP      AA   AA  GGGGGG EEEEEEE 
                                

*/

$result = mysql_query2("SELECT `id`,`info`,`host`,`port`,`timeout`,`client_id`,`touch_time`,`stream_time_open`,`datagram_time_last`,`datagram_count`,`queue_size` FROM `tvcas_emmg` ORDER BY `c_time`;");


$a = array();
while($row = mysql_fetch_assoc($result)){
  $state = status_gen('emmg', $row['id']);
  $touch_time = $row['touch_time'];
  $stream_time_open = $row['stream_time_open'];
  $datagram_time_last = $row['datagram_time_last'];
  $row['client_id'] = "0x" . numbFormat(dechex($row['client_id']), 8);
  $row['touch_time'] = ($row['touch_time']==0?"-":daynow(date("d.m.Y H:i:s", t($row['touch_time']))));
  $row['stream_time_open'] = ($row['stream_time_open']==0?"-":daynow(date("d.m.Y H:i:s", t($row['stream_time_open']))));
  $row['datagram_time_last'] = ($row['datagram_time_last']==0?"-":daynow(date("d.m.Y H:i:s", t($row['datagram_time_last']))));
  if($row['datagram_count']==0) $row['datagram_count'] = "-";
  if($row['queue_size']==0) $row['queue_size'] = "-";
  
  if($touch_time<time()-60) $row['touch_time'] = "<span style='color:#aaa;'>{$row['touch_time']}</span>";
  if($stream_time_open<time()-60) $row['stream_time_open'] = "<span style='color:#aaa;'>{$row['stream_time_open']}</span>";
  if($datagram_time_last<time()-60) $row['datagram_time_last'] = "<span style='color:#aaa;'>{$row['datagram_time_last']}</span>";
  
  $row['OPER'] = "<a href='/cas/?op=emmg&id={$row['id']}&sw=" . ($state?"off' title='Switch Off'":"on' title='Switch On'") . "'><i class='fas fa-circle text-" . ($state ? "success" : "danger") . "'></i></a>&nbsp;&nbsp;&nbsp;<a href='#' class='getModal' op='frm' val='{$row['id']}' title='Edit ECMG'><i class='fas fa-edit'></i></a>&nbsp;&nbsp;&nbsp;&nbsp;<a href='#' class='remove' rel='{$row['id']}' title='Remove ECMG'><i class='fas fa-trash-alt'></i></a>";

  $a[] = $row;
}



echo "<div style='text-align:right; margin: 10px 20px;'><a class='btn btn-primary getModal' op='frm' val='0' href='#'><i class='fas fa-plus-circle'></i> Add new</a></div>";


echo tablesorter($a);
echo modalwindow(1); // большое модальное окно

?>