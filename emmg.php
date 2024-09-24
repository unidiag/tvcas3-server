<?

// если вход не с индексного файла
if(!@$en){
  header("Location: /");
}



/*

 OOOOO  NN   NN        //     OOOOO  FFFFFFF FFFFFFF    EEEEEEE  CCCCC  MM    MM   GGGG  
OO   OO NNN  NN       ///    OO   OO FF      FF         EE      CC    C MMM  MMM  GG  GG 
OO   OO NN N NN      ///     OO   OO FFFF    FFFF       EEEEE   CC      MM MM MM GG      
OO   OO NN  NNN     ///      OO   OO FF      FF         EE      CC    C MM    MM GG   GG 
 OOOO0  NN   NN    ///        OOOO0  FF      FF         EEEEEEE  CCCCC  MM    MM  GGGGGG 
                                                                                         

*/

if(@$_GET['sw']){
    $id = intval($_GET['id']);
    if($_GET['sw']=='on'){
      start_gen("emmg", $id);
      mysql_query2("UPDATE `tvcas_emmg` SET `enable`=1 WHERE `id`={$id};");
    }elseif($_GET['sw']=='off'){
      stop_gen("emmg", $_GET['id']);
      mysql_query2("UPDATE `tvcas_emmg` SET `touch_time`=0, `stream_time_open`=0, `datagram_time_last`=0, `datagram_count`=0, `queue_size`=0, `enable`=0 WHERE `id`={$id};");
    }
    usleep(100000); // 0.1 sec
    header("Location: /?op=emmg");
    
}

























/*

PPPPPP    AAA     GGGG  EEEEEEE 
PP   PP  AAAAA   GG  GG EE      
PPPPPP  AA   AA GG      EEEEE   
PP      AAAAAAA GG   GG EE      
PP      AA   AA  GGGGGG EEEEEEE 
                                

*/

$result = mysql_query2("SELECT `id`,`info`,`host`,`port`,`touch_time`,`stream_time_open`,`datagram_time_last`,`datagram_count`,`queue_size` FROM `tvcas_emmg` ORDER BY `c_time`;");


$a = array();
while($row = mysql_fetch_assoc($result)){
  $state = status_gen('emmg', $row['id']);
  $touch_time = $row['touch_time'];
  $stream_time_open = $row['stream_time_open'];
  $datagram_time_last = $row['datagram_time_last'];
  $row['touch_time'] = ($row['touch_time']==0?"-":daynow(date("d.m.Y H:i:s", t($row['touch_time']))));
  $row['stream_time_open'] = ($row['stream_time_open']==0?"-":daynow(date("d.m.Y H:i:s", t($row['stream_time_open']))));
  $row['datagram_time_last'] = ($row['datagram_time_last']==0?"-":daynow(date("d.m.Y H:i:s", t($row['datagram_time_last']))));
  if($row['datagram_count']==0) $row['datagram_count'] = "-";
  if($row['queue_size']==0) $row['queue_size'] = "-";
  
  if($touch_time<time()-60) $row['touch_time'] = "<span style='color:#aaa;'>{$row['touch_time']}</span>";
  if($stream_time_open<time()-60) $row['stream_time_open'] = "<span style='color:#aaa;'>{$row['stream_time_open']}</span>";
  if($datagram_time_last<time()-60) $row['datagram_time_last'] = "<span style='color:#aaa;'>{$row['datagram_time_last']}</span>";
  
  $row['OPER'] = "<a href='/?op=emmg&id={$row['id']}&sw=" . ($state?"off' title='Switch Off'":"on' title='Switch On'") . "'><i class='fas fa-circle text-" . ($state ? "success" : "danger") . "'></i></a>";

  $a[] = $row;
}
 


echo "<div style='text-align:right; margin: 10px 20px;height:30px;'></div>";


echo tablesorter($a);

?>