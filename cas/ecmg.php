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
  
  $row = mysql_fetch_assoc(mysql_query2("SELECT `info`, `port`, `timeout` FROM `tvcas_ecmg` " . ($id>0 ? "WHERE `id`={$id};" : "ORDER BY `id` DESC LIMIT 1;")));
  if(empty($row)) $row = array('info' => "MY NEW ECMG", 'port' => "42000", 'timeout' => 60);
  
  // edit form
  if($id > 0){
    $title = "<i class='fas fa-edit'></i> Edit ECMG_{$id}";
    $o = array("<input type='hidden' name='op' value='edit' />");
    $o[] = "<input type='hidden' name='id' value='{$id}' />";  
  // add form
  }else{
    $o = array("<input type='hidden' name='op' value='add' />");
    $o[] = '<div class="alert alert-warning alert-dismissible fade show" role="alert"><strong>Attention!</strong> For convenience, adding data in fields is filled from the last added smartcard.<button class="close" type="button" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button></div>';
    $title = "<i class='fas fa-plus-circle'></i> Add new ECMG";
  }
  
  
  $i = 1;
  foreach($row as $k=>$v){
    if($k=='port' and $id==0){
      while(mysql_num_rows(mysql_query2("SELECT `id` FROM `tvcas_ecmg` WHERE `port`='{$v}'"))){
        $v++; // плюсуем порт, пока не будет свободным
      }
    }
    $o[] = "<div class='input-group mb-3'><div class='input-group-prepend'><span id='basic-addon{$i}' class='input-group-text'>" . strtoupper($k) . "</span></div><input name='{$k}' placeholder='{$v}' value='{$v}' class='form-control' aria-describedby='basic-addon{$i}' type='text' autocomplete='off' /></div>";
    $i++;
  }
  
  echo json_encode(array('status' => "ok", 'title' => $title, 'body' => implode("", $o), 'footer' => "<button class='btn btn-primary'>" . ($id>0?"<i class='fas fa-edit'></i> Edit":"<i class='fas fa-plus-circle'></i> Add") . " ECMG</button>"));
  exit;

/*


  AAA   DDDDD   DDDDD      EEEEEEE  CCCCC  MM    MM   GGGG  
 AAAAA  DD  DD  DD  DD     EE      CC    C MMM  MMM  GG  GG 
AA   AA DD   DD DD   DD    EEEEE   CC      MM MM MM GG      
AAAAAAA DD   DD DD   DD    EE      CC    C MM    MM GG   GG 
AA   AA DDDDDD  DDDDDD     EEEEEEE  CCCCC  MM    MM  GGGGGG 
                                                            


*/
}else if(@$_POST['op'] == 'add'){
  unset($_POST['op']);
  $_POST['c_time'] = time();
    $_POST['m_time'] = time();
  $_POST['key'] = $config['ecm_key'];

  foreach($_POST as $k=>$v){
    unset($_POST[$k]);
    if($k=='port'){
      if(mysql_num_rows(mysql_query2("SELECT `id` FROM `tvcas_ecmg` WHERE `port`='{$v}'"))){
        echo "<div class='mt-3 alert alert-danger' role='alert'><i class='fas fa-exclamation-triangle'></i> Port {$v} busy!</div><script>$(function(){ setTimeout(function(){ $('.alert-danger').fadeOut() }, 5000)  });</script>";
        $k = 'qqqq';
        logs("ERROR Add new ECMG on port {$v}");
      }else{
        logs("Add new ECMG on port {$v}");
      }
    }
    $_POST["`".reredos($k)."`"] = "'" . reredos($v) . "'";
  }
  
  $sql = "INSERT INTO `tvcas_ecmg` (" . implode(", ", array_keys($_POST)) . ") VALUES (" . implode(", ", $_POST) . ")";
  // echo $sql;
  mysql_query2($sql);
  
  
  /*
  



RRRRRR  EEEEEEE MM    MM  OOOOO  VV     VV EEEEEEE    EEEEEEE  CCCCC  MM    MM   GGGG  
RR   RR EE      MMM  MMM OO   OO VV     VV EE         EE      CC    C MMM  MMM  GG  GG 
RRRRRR  EEEEE   MM MM MM OO   OO  VV   VV  EEEEE      EEEEE   CC      MM MM MM GG      
RR  RR  EE      MM    MM OO   OO   VV VV   EE         EE      CC    C MM    MM GG   GG 
RR   RR EEEEEEE MM    MM  OOOO0     VVV    EEEEEEE    EEEEEEE  CCCCC  MM    MM  GGGGGG 
                                                                                       


*/
}else if(@$_POST['op'] == 'remove'){
  
  ob_get_clean();
  $id = intval($_POST['id']);
  list($port) = mysql_fetch_row(mysql_query2("SELECT `port` FROM `tvcas_ecmg` WHERE `id`={$id};"));
  mysql_query2("DELETE FROM `tvcas_ecmg` WHERE `id`={$id};");
  stop_gen("ecmg", $id);
  logs("Remove ECMG_{$id} which had port {$port}");
  die("ok");
  
  
  /*


EEEEEEE DDDDD   IIIII TTTTTTT    EEEEEEE  CCCCC  MM    MM   GGGG  
EE      DD  DD   III    TTT      EE      CC    C MMM  MMM  GG  GG 
EEEEE   DD   DD  III    TTT      EEEEE   CC      MM MM MM GG      
EE      DD   DD  III    TTT      EE      CC    C MM    MM GG   GG 
EEEEEEE DDDDDD  IIIII   TTT      EEEEEEE  CCCCC  MM    MM  GGGGGG 
                                                                  


*/
}else if(@$_POST['op'] == 'edit'){
  unset($_POST['op']);
  $id = intval($_POST['id']);
  $_POST['m_time'] = time();
  unset($_POST['id']);

  $o = array();
  foreach($_POST as $k=>$v){
    $o[] = "`" . reredos($k) . "`='" . reredos($v) . "'";
  }
  
  $sql = "UPDATE `tvcas_ecmg` SET " . implode(", ", $o) . " WHERE `id`={$id};";
  // echo $sql;
  mysql_query2($sql);
  logs("Edit ECMG_{$id}. Info: {$_POST['info']}. Port: {$_POST['port']}");
  // если включён, то перезапустим
  if(status_gen('ecmg', $id)){
    stop_gen('ecmg', $id);
    usleep(100000);
    start_gen('ecmg', $id);
    usleep(200000);
  }


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
      start_gen("ecmg", $id);
      mysql_query2("UPDATE `tvcas_ecmg` SET `enable`=1 WHERE `id`={$id};");
    }elseif($_GET['sw']=='off'){
      stop_gen("ecmg", $_GET['id']);
      mysql_query2("UPDATE `tvcas_ecmg` SET `peers`='',`enable`=0 WHERE `id`={$id};");
      mysql_query2("DELETE FROM `tvcas_ecmg_log` WHERE `ecmg_id`={$id};");
    }
    usleep(100000); // 0.1 sec
    header("Location: /cas/?op=ecmg");
    
/*

LL       OOOOO    GGGG     
LL      OO   OO  GG  GG    
LL      OO   OO GG         
LL      OO   OO GG   GG    
LLLLLLL  OOOO0   GGGGGG    
                           

*/    
}else if(@$_POST['op']=='log'){
  ob_get_clean();
  $id = @explode(":", @$_POST['val']);
  if(count($id)==2){
    
    $o = array();
    $o[] = get_ecmg_log($id[0], $id[1], 30);
    $o[] = "<script>
    clearInterval(i);
    $(function(){
      i = setInterval(function(){
        $.post('', {'log':'update','ecmg_id':'{$id[0]}','sock':'{$id[1]}'}, function(r){
          $('.log').html(r);
        });
      }, 3000);
    });
    </script>";
    
    echo json_encode(array('status' => "ok", 'title' => "<i class='fas fa-clipboard-list'></i> Requests LOG", 'body' => "<div class='log'>" . implode("", $o) . "</div>", 'footer' => ""));
  }
  exit;
  
  /*

UU   UU PPPPPP  DDDDD     AAA   TTTTTTT EEEEEEE    LL       OOOOO    GGGG  
UU   UU PP   PP DD  DD   AAAAA    TTT   EE         LL      OO   OO  GG  GG 
UU   UU PPPPPP  DD   DD AA   AA   TTT   EEEEE      LL      OO   OO GG      
UU   UU PP      DD   DD AAAAAAA   TTT   EE         LL      OO   OO GG   GG 
 UUUUU  PP      DDDDDD  AA   AA   TTT   EEEEEEE    LLLLLLL  OOOO0   GGGGGG 
                                                                           

*/
  
}else if(@$_POST['log']=='update'){
  ob_get_clean();
  echo get_ecmg_log(@$_POST['ecmg_id'], @$_POST['sock']);
  exit;
}



























/*

PPPPPP    AAA     GGGG  EEEEEEE 
PP   PP  AAAAA   GG  GG EE      
PPPPPP  AA   AA GG      EEEEE   
PP      AAAAAAA GG   GG EE      
PP      AA   AA  GGGGGG EEEEEEE 
                                

*/

$result = mysql_query2("SELECT `id`,`info`,`key`,`port`,`peers`,`c_time`,`m_time`,`touch_time`,`timeout` FROM `tvcas_ecmg` ORDER BY `c_time`;");


$a = array();
while($row = mysql_fetch_assoc($result)){
  $state = status_gen('ecmg', $row['id']);
  $peers = @unserialize($row['peers']);
  if(is_array($peers)){
    foreach($peers as $k=>$v){
      $peers[$k] = daynow(date("d.m.Y H:i:s", t($v['time']))) . " - {$k}, cw={$v['cw_count']}, ecm={$v['ecm_count']} [<a href='#' class='getModal' op='log' val='{$row['id']}:{$v['sock']}'>log</a>]";
      if($v['time']<time()-20) $peers[$k] = "<span style='color:#aaa;'>{$peers[$k]}</span>";
    }
  }
  
  $row['Create'] = daynow(date("d.m.Y H:i:s", t($row['c_time'])));
  $row['Moder'] = daynow(date("d.m.Y H:i:s", t($row['m_time'])));
  unset($row['c_time']);
  unset($row['m_time']);
  
  $row['info'] .= (chk(block_get('config'), 'ecm_key') != $row['key'] ? "<i class='fas fa-exclamation-triangle' style='color:red; margin-left:10px;' title='ECM key doesnt match'></i>" : "");
  unset($row['key']);
  $row['peers'] = @implode("<br />", @$peers);
  $row['touch_time'] = ($row['touch_time']==0?"-":daynow(date("d.m.Y H:i:s", t($row['touch_time']))));
  $row['OPER'] = "<a href='/cas/?op=ecmg&id={$row['id']}&sw=" . ($state?"off' title='Switch Off'":"on' title='Switch On'") . "'><i class='fas fa-circle text-" . ($state ? "success" : "danger") . "'></i></a>&nbsp;&nbsp;&nbsp;<a href='#' class='getModal' op='frm' val='{$row['id']}' title='Edit ECMG'><i class='fas fa-edit'></i></a>&nbsp;&nbsp;&nbsp;&nbsp;<a href='#' class='remove' rel='{$row['id']}' title='Remove ECMG'><i class='fas fa-trash-alt'></i></a>";

  $a[] = $row;
}



echo "<div style='text-align:right; margin: 10px 20px;'><a class='btn btn-primary getModal' op='frm' val='0' href='#'><i class='fas fa-plus-circle'></i> Add new</a></div>";


echo tablesorter($a);
echo modalwindow(1); // малое модальное окно


















function get_ecmg_log($ecmg_id, $sock, $num=30){
    $result = mysql_query2("SELECT * FROM `tvcas_ecmg_log` WHERE `ecmg_id`='{$ecmg_id}' AND `sock`='{$sock}' ORDER BY `time` DESC LIMIT {$num};");
    $o = array();
    $t = array();
    while($row = mysql_fetch_assoc($result)){
      if(!isset($t[$row['cw1']]))$t[$row['cw1']] = intval($row['cw1'])%6;
      if(!isset($t[$row['cw2']]))$t[$row['cw2']] = intval($row['cw2'])%6;
      $o[] = "<div>" . daynow(date("d.m.Y H:i:s", t($row['time']))) . ", Access criteria: {$row['access_criteria']}, cw1: <span class='ccc{$t[$row['cw1']]}'>{$row['cw1']}</span></b>, cw2: <span class='ccc{$t[$row['cw2']]}'>{$row['cw2']}</span></div>";
    }
    $o = array_reverse($o);
    return implode("", $o);
}



?>