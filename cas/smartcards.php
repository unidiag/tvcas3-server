<?

// если вход не с индексного файла
if(!@$en){
  header("Location: /cas");
}



// for pagination
$limit=30; // по 30 записей на одной странице
$page = (isset($_GET['p'])) ? intval($_GET['p']) : 1;
$offset = ($page - 1) * $limit;






/*

 SSSSS  EEEEEEE TTTTTTT    PPPPPP    AAA   IIIII RRRRRR  
SS      EE        TTT      PP   PP  AAAAA   III  RR   RR 
 SSSSS  EEEEE     TTT      PPPPPP  AA   AA  III  RRRRRR  
     SS EE        TTT      PP      AAAAAAA  III  RR  RR  
 SSSSS  EEEEEEE   TTT      PP      AA   AA IIIII RR   RR 
                                                         

*/

if(@$_POST['op']=='pair'){
  
  ob_get_clean();
  $id = intval(@$_POST['id']);
  $val = (@$_POST['val']=='true' ? 1 : 0);
  mysql_query2("UPDATE `tvcas_smartcards` SET `pair`={$val}, `start`=`start`+1, `edit`=" . time() . " WHERE `id`={$id};");
  $row = mysql_fetch_assoc(mysql_query2("SELECT `pair`, `serial_no` FROM `tvcas_smartcards` WHERE `id`={$id};"));
  logs("Edit PAIR={$row['pair']} for smartcard={$row['serial_no']}");
  echo $row['pair'];
  exit;
  
  
  
/*
 SSSSS  EEEEEEE TTTTTTT    TTTTTTT YY   YY PPPPPP  EEEEEEE 
SS      EE        TTT        TTT   YY   YY PP   PP EE      
 SSSSS  EEEEE     TTT        TTT    YYYYY  PPPPPP  EEEEE   
     SS EE        TTT        TTT     YYY   PP      EE      
 SSSSS  EEEEEEE   TTT        TTT     YYY   PP      EEEEEEE 
                                                           
*/  
}else if(@$_POST['op']=='type'){
  
  ob_get_clean();
  $id = intval(@$_POST['id']);
  $val = (@$_POST['val']=='true' ? 1 : 2);
  mysql_query2("UPDATE `tvcas_smartcards` SET `type`={$val}, `start`=`start`+1, `edit`=" . time() . " WHERE `id`={$id};");
  $row = mysql_fetch_assoc(mysql_query2("SELECT `type`, `serial_no` FROM `tvcas_smartcards` WHERE `id`={$id};"));
  logs("Edit TYPE={$row['type']} for smartcard={$row['serial_no']}");
  echo ($row['type']==1?1:0);
  exit;

/*

MM    MM  OOOOO  DDDDD     AAA   LL         EEEEEEE DDDDD   IIIII TTTTTTT    FFFFFFF  OOOOO  RRRRRR  MM    MM 
MMM  MMM OO   OO DD  DD   AAAAA  LL         EE      DD  DD   III    TTT      FF      OO   OO RR   RR MMM  MMM 
MM MM MM OO   OO DD   DD AA   AA LL         EEEEE   DD   DD  III    TTT      FFFF    OO   OO RRRRRR  MM MM MM 
MM    MM OO   OO DD   DD AAAAAAA LL         EE      DD   DD  III    TTT      FF      OO   OO RR  RR  MM    MM 
MM    MM  OOOO0  DDDDDD  AA   AA LLLLLLL    EEEEEEE DDDDDD  IIIII   TTT      FF       OOOO0  RR   RR MM    MM 
                                                                                                              

*/

}elseif(@$_POST['op']=='sm'){
  ob_get_clean();
  
  $o = array();
  $serial_no = intval(@$_POST['val']);
  
  $row = mysql_fetch_assoc(mysql_query2("SELECT `name`, `info`, `access_criteria`, `start`, `finish` FROM `tvcas_smartcards` " . ($serial_no>0 ? "WHERE `serial_no`={$serial_no};" : "ORDER BY `serial_no` DESC LIMIT 1;")));
  if(empty($row)) $row = array('name' => "Mr. Vladimir Putin", 'info' => "Moscow, Red Square 1-7, phone: +7(906)777-12-34", 'access_criteria' => "11111111", 'type' => 1, 'start' => mktime(0,0,0,1,1,2010), 'finish' => mktime(0,0,0,1,1,2050));
  
  // edit form
  if($serial_no > 0){
    $title = "<i class='fas fa-edit'></i> Edit smartcard #" . slyspace($serial_no, 3, "-");
    $o = array("<input type='hidden' name='op' value='edit' />");
    $o[] = "<input type='hidden' name='serial_no' value='{$serial_no}' />";  
  // add form
  }else{
    $o = array("<input type='hidden' name='op' value='add' />");
    $o[] = '<div class="alert alert-warning alert-dismissible fade show" role="alert"><strong>Attention!</strong> For convenience, adding data in fields is filled from the last added smartcard.<button class="close" type="button" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button></div>';
    $title = "<i class='fas fa-plus-circle'></i> Add new smartcard" . ($serial_no==-1?"s":"");
  }
  
  
  $i = 1;
  if($serial_no==-1)$row['quantity'] = 10;
  foreach($row as $k=>$v){
    if($k=='start' or $k=='finish'){
      $v = date("d.m.Y H:i", t($v));
      $o[] = "<div class='row'><div class='col-md-9'><div class='input-group mb-3'><div class='input-group-prepend'><span id='basic-addon{$i}' class='input-group-text'>" . strtoupper($k) . "</span></div><input name='{$k}' placeholder='{$v}' value='{$v}' class='form-control' aria-describedby='basic-addon{$i}' type='text' autocomplete='off' /></div></div><div class='col-md-3 mt-1'>";
      if($k=='start') $o[] = "<span class='start'>now</span>";
      if($k=='finish') $o[] = "<span class='finish'>+month</span>";
      $o[] = "</div></div>";
    }else{
      if($k=='access_criteria'){
        $th = "";
        $td = "";
        for($iii=31; $iii>=0; $iii--){
          $th .= "<th style='font-size:10px; text-align:center;'>{$iii}</th>";
          $td .= "<td style='text-align:center;'><input class='checkboxes_ac' type='checkbox' id='ch{$iii}' value='1' /></td>";
        }
        $o[] = "<table width='100%' class='chtable'><tr>{$th}</tr><tr>{$td}</tr></table>";
      }
      $o[] = "<div class='input-group mb-3'><div class='input-group-prepend'><span id='basic-addon{$i}' class='input-group-text'>" . strtoupper($k) . "</span></div><input name='{$k}' placeholder='{$v}' value='{$v}' class='form-control' aria-describedby='basic-addon{$i}' " . ($k=='access_criteria'?"maxlength=8 ":"") . "type='text' autocomplete='off' /></div>";
    }
    $i++;
  }
  
  $o[] = "<style>.start,.finish{color:red; border-bottom:1px dashed red;font:11px/1em Verdana;cursor:pointer;}</style>
  <script>$(function(){
    
    $('.start').click(function(){ $('input[name=start]').val('" . date("d.m.Y H:i", t()) . "'); });
    $('.finish').click(function(){ $('input[name=finish]').val('" . date("d.m.Y H:i", t()+date("t")*86400) . "'); });
  
    $('.checkboxes_ac').click(function(){
      var tt = '';
      $('.checkboxes_ac').each(function(){
        if($(this).is(':checked')){
          tt += '1';
        }else{
          tt += '0';
        }
      });
      tt = parseInt(tt, 2).toString(16).toUpperCase();
      while(tt.length !=8){
        tt = '0'+tt;
      }
      $('input[name=access_criteria]').val(tt);
    });
   
    hex2chk();   
    $('input[name=access_criteria]').keyup(function(){
      hex2chk();  
    });
    
    function hex2chk(){
      $('.chtable input:checkbox').prop('checked', false);
      var tthis = $('input[name=access_criteria]');
      var ttt = tthis.val();
      if(ttt.length<8){
        tthis.addClass('is-invalid');
      }else{
        tthis.removeClass('is-invalid');
      }
      
      ttt = parseInt(ttt, 16).toString(2);
      while(ttt.length !=32){
        ttt = '0'+ttt;
      }
      for(var i=0; i<ttt.length; i++){
        if(ttt[i]=='1') $('#ch'+(31-i)).prop('checked', true);
      }
    }
   
  
  });
  </script>";
  

  
  echo json_encode(array('status' => "ok", 'title' => $title, 'body' => implode("", $o), 'footer' => "<button class='btn btn-primary'>" . ($serial_no>0?"<i class='fas fa-edit'></i> Edit":"<i class='fas fa-plus-circle'></i> Add") . " smartcard" . ($serial_no==-1?"s":"") . "</button>"));
  exit;

/*

  AAA   DDDDD   DDDDD       SSSSS  MM    MM 
 AAAAA  DD  DD  DD  DD     SS      MMM  MMM 
AA   AA DD   DD DD   DD     SSSSS  MM MM MM 
AAAAAAA DD   DD DD   DD         SS MM    MM 
AA   AA DDDDDD  DDDDDD      SSSSS  MM    MM 
                                            

*/
}else if(@$_POST['op'] == 'add'){
  $q = @$_POST['quantity']?intval($_POST['quantity']):1;
  unset($_POST['quantity']);
  unset($_POST['op']);
  
  $_POST['c_time'] = time();
  list($last_sn) = mysql_fetch_row(mysql_query2("SELECT `serial_no` FROM `tvcas_smartcards` ORDER BY `serial_no` DESC LIMIT 1;"));
  if(empty($last_sn)) $last_sn = 2099999999;
  
  while($q>0){
    $last_sn++;
    $o = $_POST;
    $o['serial_no'] = $last_sn;
    $o['key'] = strtoupper(bin2hex(make_random(16)));
    $o['edit'] = time();
    $o['start'] = datetotime($_POST['start']);
    $o['finish'] = datetotime($_POST['finish']);
    $o['type'] = (intval($_POST['type']) > 0) ? $_POST['type'] : 1;
    if(!preg_match("/[0-9A-F]{8}/i", $_POST['access_criteria'])) $_POST['access_criteria'] = "00000000";
    $_POST['access_criteria'] = strtoupper($_POST['access_criteria']);
    
    foreach($o as $k=>$v){
      unset($o[$k]);
      $o["`".reredos($k)."`"] = "'" . reredos($v) . "'";
    }
    
    $sql = "INSERT INTO `tvcas_smartcards` (" . implode(", ", array_keys($o)) . ") VALUES (" . implode(", ", $o) . ")";
    //echo $sql;
    $result = mysql_query2($sql);
    list($sn, $n, $i) = mysql_fetch_row(mysql_query2("SELECT `serial_no`, `name`, `info` FROM `tvcas_smartcards` ORDER BY `id` DESC LIMIT 1;"));
    logs("Add new smartcard {$sn}. Name:{$n}. Info:{$i}.");
    $q--;
  }
  reload_emm();
  
  /*
  

RRRRRR  EEEEEEE MM    MM  OOOOO  VV     VV EEEEEEE     SSSSS  MM    MM 
RR   RR EE      MMM  MMM OO   OO VV     VV EE         SS      MMM  MMM 
RRRRRR  EEEEE   MM MM MM OO   OO  VV   VV  EEEEE       SSSSS  MM MM MM 
RR  RR  EE      MM    MM OO   OO   VV VV   EE              SS MM    MM 
RR   RR EEEEEEE MM    MM  OOOO0     VVV    EEEEEEE     SSSSS  MM    MM 
                                                                       

*/
}else if(@$_POST['op'] == 'remove'){
  
  ob_get_clean();
  $id = intval($_POST['id']);
  list($sn, $n,$i) = mysql_fetch_row(mysql_query2("SELECT `serial_no`,`name`,`info` FROM `tvcas_smartcards` WHERE `id`={$id};"));
  mysql_query2("DELETE FROM `tvcas_smartcards` WHERE `id`={$id};");
  logs("Remove smartcard {$sn}. Name:{$n}. Info:{$i}", 1);
  die("ok");
  
  
  /*

EEEEEEE DDDDD   IIIII TTTTTTT     SSSSS  MM    MM 
EE      DD  DD   III    TTT      SS      MMM  MMM 
EEEEE   DD   DD  III    TTT       SSSSS  MM MM MM 
EE      DD   DD  III    TTT           SS MM    MM 
EEEEEEE DDDDDD  IIIII   TTT       SSSSS  MM    MM 
                                                  

*/
}else if(@$_POST['op'] == 'edit'){
  unset($_POST['op']);
  $serial_no = intval($_POST['serial_no']);
  unset($_POST['serial_no']);
  $_POST['edit'] = time();
  $_POST['start'] = datetotime($_POST['start']);
  $_POST['finish'] = datetotime($_POST['finish']);
  //$_POST['type'] = (intval($_POST['type']) > 0) ? $_POST['type'] : 1;

  $_POST['access_criteria'] = strtoupper($_POST['access_criteria']);
  if(!preg_match("/[0-9A-F]{8}/i", $_POST['access_criteria'])) unset($_POST['access_criteria']);
  
  $o = array();
  foreach($_POST as $k=>$v){
    $o[] = "`" . reredos($k) . "`='" . reredos($v) . "'";
  }
  
  $sql = "UPDATE `tvcas_smartcards` SET " . implode(", ", $o) . " WHERE `serial_no`={$serial_no};";
  //echo $sql;
  mysql_query2($sql);
  logs("Edit smartcard {$serial_no}");
  reload_emm();
  /*

FFFFFFF IIIII RRRRRR  MM    MM WW      WW   AAA   RRRRRR  EEEEEEE 
FF       III  RR   RR MMM  MMM WW      WW  AAAAA  RR   RR EE      
FFFF     III  RRRRRR  MM MM MM WW   W  WW AA   AA RRRRRR  EEEEE   
FF       III  RR  RR  MM    MM  WW WWW WW AAAAAAA RR  RR  EE      
FF      IIIII RR   RR MM    MM   WW   WW  AA   AA RR   RR EEEEEEE 
                                                                  

*/
}else if(@$_GET['fm']>0){
   logs("Took the firmware for smartcard " . intval($_GET['fm']) . ";");
   generate_fw($_GET['fm']);
   exit;
   
   /*

EEEEEEE XX    XX PPPPPP   OOOOO  RRRRRR  TTTTTTT 
EE       XX  XX  PP   PP OO   OO RR   RR   TTT   
EEEEE     XXXX   PPPPPP  OO   OO RRRRRR    TTT   
EE       XX  XX  PP      OO   OO RR  RR    TTT   
EEEEEEE XX    XX PP       OOOO0  RR   RR   TTT   
                                                 

*/
}else if(@$_GET['csv'] == 'export'){
   generate_csv(1); // с одинкой - это для админа (чэсны полный CSV с емм-ключами)
   exit;
   
   /*
   

IIIII MM    MM PPPPPP   OOOOO  RRRRRR  TTTTTTT 
 III  MMM  MMM PP   PP OO   OO RR   RR   TTT   
 III  MM MM MM PPPPPP  OO   OO RRRRRR    TTT   
 III  MM    MM PP      OO   OO RR  RR    TTT   
IIIII MM    MM PP       OOOO0  RR   RR   TTT   
                                               

*/
}else if(@$_GET['csv'] == 'import'){
   import_csv(1); // с одинкой - для админа (чэсное импортирование, если нет чего-то - добавит)
   mysql_query2("UPDATE `tvcas_emmg` SET `reload`=1 WHERE `touch_time`>0;"); // перезапускаем еммг которые сейчас включены




/*

 SSSSS  WW      WW IIIII TTTTTTT  CCCCC  HH   HH      AAA    CCCCC  
SS      WW      WW  III    TTT   CC    C HH   HH     AAAAA  CC    C 
 SSSSS  WW   W  WW  III    TTT   CC      HHHHHHH    AA   AA CC      
     SS  WW WWW WW  III    TTT   CC    C HH   HH    AAAAAAA CC    C 
 SSSSS    WW   WW  IIIII   TTT    CCCCC  HH   HH    AA   AA  CCCCC  
                                                                    

*/
}else if(@$_POST['ac']!=''){
  ob_get_clean();
  
  $g = array('11111111', '00000000');
  $r = mysql_query2("SELECT DISTINCT(`access_criteria`) FROM `tvcas_smartcards`");
  while($rr = mysql_fetch_assoc($r)){
    if(!in_array($rr['access_criteria'], $g))$g[] = $rr['access_criteria'];
  }
  sort($g);
  $k = intval(array_search($_POST['ac'], $g))+1;
  if($k>count($g)-1) $k=0;
  
  $id = intval($_POST['id']);
  list($serial_no,$n,$i) = mysql_fetch_row(mysql_query2("SELECT `serial_no`,`name`,`info` FROM `tvcas_smartcards` WHERE `id`={$id};"));
  $newac = $g[$k];
  mysql_query2("UPDATE `tvcas_smartcards` SET `access_criteria`='{$newac}', `edit`='" . time() . "' WHERE `id`={$id};");
  logs("Smartcard {$serial_no}. Name:{$n}. Info:{$i} Change access_criteria={$newac}");
  reload_emm();
  echo $newac;
  exit;
  
  /*

PPPPPP  RRRRRR  IIIII NN   NN TTTTTTT    LL        AAA   BBBBB   EEEEEEE LL       SSSSS  
PP   PP RR   RR  III  NNN  NN   TTT      LL       AAAAA  BB   B  EE      LL      SS      
PPPPPP  RRRRRR   III  NN N NN   TTT      LL      AA   AA BBBBBB  EEEEE   LL       SSSSS  
PP      RR  RR   III  NN  NNN   TTT      LL      AAAAAAA BB   BB EE      LL           SS 
PP      RR   RR IIIII NN   NN   TTT      LLLLLLL AA   AA BBBBBB  EEEEEEE LLLLLLL  SSSSS  
                                                                                         

*/
  
  
}else if(@$_GET['print']=='1'){
  ob_get_clean();
  $r = mysql_query2("SELECT `serial_no` FROM `tvcas_smartcards` WHERE `c_time`>" . (time()-3600*3) . " ORDER BY `serial_no`;");
  $o = array();
  while($rr = mysql_fetch_row($r)){
    $o[] = slyspace($rr[0], 3, "-");
  }
  $o = @array_chunk($o, ceil(count($o)/5));
  if(count($o)){
    echo "These are the newly added smart cards in the last 3 hours.<br /><table style='border:1px solid #000;'><tr>";
    foreach($o as $v){
      echo "<td valign='top'>" . implode("<hr>", $v) . "</td>";
    }
    echo "</tr></table><style>table{font:bold 16px/1em Courier New;}td{padding:5px 20px;}hr{color:#000;}</style>";
  }else{
    echo "No new cards to print";
  }

  exit;
}






















/*

PPPPPP    AAA     GGGG  EEEEEEE 
PP   PP  AAAAA   GG  GG EE      
PPPPPP  AA   AA GG      EEEEE   
PP      AAAAAAA GG   GG EE      
PP      AA   AA  GGGGGG EEEEEEE 
                                

*/

$search = block_get('search');
$where = (!empty($search) ? " WHERE `serial_no` LIKE '%{$search}%' OR `name` LIKE '%{$search}%' OR `info` LIKE '%{$search}%' OR `access_criteria` LIKE '%{$search}%'" : "");

$numstories = mysql_num_rows(mysql_query2("SELECT `id` FROM `tvcas_smartcards`{$where}"));
$result = mysql_query2("SELECT * FROM `tvcas_smartcards`{$where} ORDER BY `serial_no` LIMIT {$offset},{$limit};");


$a = array();
while($row = mysql_fetch_assoc($result)){
  unset($row['subscription_slot1']);
  unset($row['subscription_slot2']);
  unset($row['uid']);
  unset($row['status']);
  unset($row['network_id']);
  unset($row['cn']);
  unset($row['key']);
  unset($row['c_time']);

  $row['start'] = daynow(date("d.m.Y H:i", t($row['start'])));
  $row['finish'] = daynow(date("d.m.Y H:i", t($row['finish']))) . (t($row['finish'])<t() ? "<i class='fas fa-exclamation-triangle' style='color:red; margin-left:10px;' title='No EMM'></i>" : "");
  $row['edit'] = daynow(date("d.m.Y H:i", t($row['edit'])));
  $row['OPER'] = "<a href='/cas/?op=smartcards&fm={$row['serial_no']}' title='Download BIN-config'><i class='fas fa-download'></i></a>&nbsp;&nbsp;<a href='#' class='getModal' op='sm' val='{$row['serial_no']}' title='Edit smartcard'><i class='fas fa-edit'></i></a>&nbsp;&nbsp;&nbsp;&nbsp;<a href='#' class='remove' rel='{$row['id']}' title='Remove smartcard'><i class='fas fa-trash-alt'></i></a>";
  $row['serial_no'] = (!empty($search) ? str_replace($search, "<span class='ins'>{$search}</span>", $row['serial_no']) : slyspace($row['serial_no'], 3, "-"));
  $row['name'] = preg_replace("/({$search})/ui", "<span class='ins'>$1</span>", $row['name']);
  $row['info'] = preg_replace("/({$search})/ui", "<span class='ins'>$1</span>", $row['info']);
  $row['access_criteria'] = preg_replace("/({$search})/ui", "<span class='ins'>$1</span>", $row['access_criteria']);
  $row['access_criteria'] = "<span class='ac' rel='{$row['id']}'>{$row['access_criteria']}</span>";
  $row['pair'] = "<center><input type='checkbox' class='pair'" . ($row['pair'] ? " checked='checked'" : "") . " rel='{$row['id']}' /></center>";
  $row['type'] = "<center><input type='checkbox' class='type'" . ($row['type']==1 ? " checked='checked'" : "") . " rel='{$row['id']}' /></center>";
  unset($row['id']);
  $a[] = $row;
}

$p = mysql_num_rows(mysql_query2("SELECT `serial_no` FROM `tvcas_smartcards` WHERE `c_time`>" . (time()-3600*3) . " ORDER BY `serial_no`;"));

echo "<div style='text-align:right; margin: 10px 20px;'><a class='btn btn-primary getModal' op='sm' val='0' href='#'><i class='fas fa-plus-square'></i> Add new</a>&nbsp;&nbsp;<a class='btn btn-primary getModal' op='sm' val='-1' href='#'><i class='far fa-plus-square'></i> Add multiple</a>&nbsp;&nbsp;" . ($p?"<a class='btn btn-outline-secondary' target='_blank' href='/cas/?op=smartcards&print=1'><i class='fas fa-print'></i> Print labels</a>&nbsp;&nbsp;":"") . "<a class='btn btn-success' href='/cas/?op=smartcards&csv=export'><i class='fas fa-cloud-upload-alt'></i> Export CSV</a>&nbsp;&nbsp;<a class='btn btn-success' href='#' onclick='FindFile();'><i class='fas fa-cloud-download-alt'></i> Import CSV</a></div>";

echo '<form action="/cas/?op=smartcards&csv=import" target="rFrame" method="POST" enctype="multipart/form-data">  
<div class="hiddenInput"><input type="file" id="my_hidden_file" accept="text/csv" name="loadfile" onchange="LoadFile();">  <input type="submit" id="my_hidden_load" style="display: none" value="Import"></div></form><iframe id="rFrame" name="rFrame" style="display: none"></iframe>

<script>
function FindFile() { document.getElementById("my_hidden_file").click(); }  
function LoadFile() { document.getElementById("my_hidden_load").click(); }
function onReloadPage(){ window.location.href="/cas/?op=smartcards"; }
$(function(){
  $(".pair").click(function(){
    var $this = $(this);
    $.post("",{"op":"pair", "id":$this.attr("rel"), "val":$this.prop("checked")}, function(r){
      if(r!="0" && r!="1") alert("Unknown error set PAIR!");
    });
  });
  $(".type").click(function(){
    var $this = $(this);
    $.post("",{"op":"type", "id":$this.attr("rel"), "val":$this.prop("checked")}, function(r){
      if(r!="0" && r!="1") alert("Unknown error set type!");
    });
  });
});
</script>

<style>.hiddenInput{ position:absolute;overflow: hidden;display:block;  height:0px;  width:0px;}</style>';


$pp = pagenation($page, $numstories, $limit);
echo $pp; // пагинация
echo tablesorter($a,'id', $limit);
echo $pp; // пагинация

echo modalwindow(1); // большое модальное окно














/*

FFFFFFF IIIII RRRRRR  MM    MM WW      WW   AAA   RRRRRR  EEEEEEE 
FF       III  RR   RR MMM  MMM WW      WW  AAAAA  RR   RR EE      
FFFF     III  RRRRRR  MM MM MM WW   W  WW AA   AA RRRRRR  EEEEE   
FF       III  RR  RR  MM    MM  WW WWW WW AAAAAAA RR  RR  EE      
FF      IIIII RR   RR MM    MM   WW   WW  AA   AA RR   RR EEEEEEE 
                                                                  

*/

function generate_fw($serial_no){
  global $config;
  $sn = intval($serial_no);
  $r = mysql_fetch_assoc(mysql_query2("SELECT * FROM `tvcas_smartcards` WHERE `serial_no`={$sn};"));
  $file = "/tmp/" . slyspace($sn, 3, "-") . ".bin";
  $out = array();
  $_sn = explode(" ", slyspace(numbFormat(dechex($sn), 8)));
  $out[0] = $_sn[0];   //0x7D  (serial number)
  $out[1] = $_sn[1];
  $out[2] = $_sn[2];
  $out[3] = $_sn[3];
  $_ua = explode(" ", slyspace(substr(md5($config['ecm_key']), 0, 6)));
  $ident = strtoupper($config['ident']);
  $out[4] = (strpos($ident, "0B")===0 ? substr($ident, 2, 2) : "00"); // 00
  $out[5] = $_ua[0];
  $out[6] = $_ua[1];
  $out[7] = $_ua[2];
  $_tm = substr($config['trademark'], 0, 15);
  for($i=0; $i<15; $i++){
    $out[$i+8] = ((strlen($_tm)>$i) ? bin2hex($_tm{$i}) : 20);
  }
  $_start = explode(" ", slyspace(numbFormat(dechex($r['start']), 8)));
  $out[23] = $_start[0];
  $out[24] = $_start[1];
  $out[25] = $_start[2];
  $out[26] = $_start[3];
  $_finish = explode(" ", slyspace(numbFormat(dechex($r['finish']), 8)));
  $out[27] = $_finish[0];
  $out[28] = $_finish[1];
  $out[29] = $_finish[2];
  $out[30] = $_finish[3];
  $_ac = explode(" ", slyspace($r['access_criteria']));
  $out[31] = $_ac[0];
  $out[32] = $_ac[1];
  $out[33] = $_ac[2];
  $out[34] = $_ac[3];
  $_slystart = explode(" ", slyspace(numbFormat(dechex(slydate($r['start'])), 4)));
  $out[35] = $_slystart[0];
  $out[36] = $_slystart[1];
  $_slyfinish = explode(" ", slyspace(numbFormat(dechex(slydate($r['finish'])), 4)));
  $out[37] = $_slyfinish[0];
  $out[38] = $_slyfinish[1];
  $out[39] = ($r['pair']!=0 ? "01" : "00"); // paring
  $out[40] = ($r['type']==1 ? "01" : "00"); // protect
  $out[41] = "00"; // reserve
  $n = 42;
  foreach(explode(" ", slyspace($config['ecm_key'])) as $v){
    $out[$n] = $v;
    $n++;
  }
  // n=75 (EMM)
  foreach(explode(" ", slyspace($r['key'])) as $v){
    $out[$n] = $v;
    $n++;
  }
  
  $str = "";
  foreach($out as $v){
    $str .= pack("C", hexdec($v));
  }
  
  file_put_contents("/tmp/" . slyspace($sn, 3, "-") . ".bin", $str);
  file_force_download("/tmp/" . slyspace($sn, 3, "-") . ".bin");
  die("Unknown error!");
}





?>