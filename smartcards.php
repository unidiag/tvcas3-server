<?

// если вход не с индексного файла
if(!@$en){
  header("Location: /");
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
  mysql_query2("UPDATE `tvcas_smartcards` SET `pair`={$val}, `edit`=" . time() . " WHERE `id`={$id};");
  $row = mysql_fetch_assoc(mysql_query2("SELECT `pair`, `serial_no` FROM `tvcas_smartcards` WHERE `id`={$id};"));
  logs("Edit PAIR={$row['pair']} for smartcard={$row['serial_no']}");
  echo $row['pair'];
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
  
  $row = mysql_fetch_assoc(mysql_query2("SELECT `name`, `info`, `access_criteria`, `start`, `finish` FROM `tvcas_smartcards` WHERE `serial_no`={$serial_no};"));
  
  // edit form
  if($serial_no > 0){
    $title = "<i class='fas fa-edit'></i> Edit smartcard #" . slyspace($serial_no, 3, "-");
    $o = array("<input type='hidden' name='op' value='edit' />");
    $o[] = "<input type='hidden' name='serial_no' value='{$serial_no}' />";  
  }
  
  
  $i = 1;
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
  

  
  echo json_encode(array('status' => "ok", 'title' => $title, 'body' => implode("", $o), 'footer' => "<button class='btn btn-primary'>" . ($serial_no>0?"<i class='fas fa-edit'></i> Edit":"<i class='fas fa-plus-circle'></i> Add") . " smartcard</button>"));
  exit;


  
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
  // echo $sql;
  mysql_query2($sql);
  logs("Edit smartcard {$serial_no}");
  reload_emm();

   
   /*

EEEEEEE XX    XX PPPPPP   OOOOO  RRRRRR  TTTTTTT 
EE       XX  XX  PP   PP OO   OO RR   RR   TTT   
EEEEE     XXXX   PPPPPP  OO   OO RRRRRR    TTT   
EE       XX  XX  PP      OO   OO RR  RR    TTT   
EEEEEEE XX    XX PP       OOOO0  RR   RR   TTT   
                                                 

*/
}else if(@$_GET['csv'] == 'export'){
   generate_csv(0); // с одинкой - это для админа (чэсны полный CSV с емм-ключами)
   exit;
   
   /*
   

IIIII MM    MM PPPPPP   OOOOO  RRRRRR  TTTTTTT 
 III  MMM  MMM PP   PP OO   OO RR   RR   TTT   
 III  MM MM MM PPPPPP  OO   OO RRRRRR    TTT   
 III  MM    MM PP      OO   OO RR  RR    TTT   
IIIII MM    MM PP       OOOO0  RR   RR   TTT   
                                               

*/
}else if(@$_GET['csv'] == 'import'){
   import_csv(0); // с одинкой - для админа (чэсное импортирование, если нет чего-то - добавит)
   reload_emm(); // перезапускаем еммг которые сейчас включены

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
  if($k==count($g)) $k=0;
  
  $id = intval($_POST['id']);
  list($serial_no,$n,$i) = mysql_fetch_row(mysql_query2("SELECT `serial_no`,`name`,`info` FROM `tvcas_smartcards` WHERE `id`={$id};"));
  $newac = $g[$k];
  mysql_query2("UPDATE `tvcas_smartcards` SET `access_criteria`='{$newac}', `edit`='" . time() . "' WHERE `id`={$id};");
  logs("Smartcard {$serial_no}. Name:{$n}. Info:{$i} Change access_criteria={$newac}");
  reload_emm();
  echo $newac;
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

$numstories = mysqli_num_rows(mysql_query2("SELECT `id` FROM `tvcas_smartcards`{$where}"));
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
  unset($row['type']);
  unset($row['c_time']);
  unset($row['edit']); 

  $row['start'] = daynow(date("d.m.Y H:i", t($row['start'])));
  $row['finish'] = daynow(date("d.m.Y H:i", t($row['finish']))) . (t($row['finish'])<t() ? "<i class='fas fa-exclamation-triangle' style='color:red; margin-left:10px;' title='No EMM'></i>" : "");
  $row['OPER'] = "<a href='#' class='getModal' op='sm' val='{$row['serial_no']}' title='Edit smartcard'><i class='fas fa-edit'></i></a>";
  $row['serial_no'] = (!empty($search) ? str_replace($search, "<span class='ins'>{$search}</span>", $row['serial_no']) : slyspace($row['serial_no'], 3, "-"));
  $row['name'] = preg_replace("/({$search})/ui", "<span class='ins'>$1</span>", $row['name']);
  $row['info'] = preg_replace("/({$search})/ui", "<span class='ins'>$1</span>", $row['info']);
  $row['access_criteria'] = preg_replace("/({$search})/ui", "<span class='ins'>$1</span>", $row['access_criteria']);
  $row['access_criteria'] = "<span class='ac' rel='{$row['id']}'>{$row['access_criteria']}</span>";
  $row['pair'] = "<center><input type='checkbox' class='pair'" . ($row['pair'] ? " checked='checked'" : "") . " rel='{$row['id']}' /></center>";
  unset($row['id']);
  $a[] = $row;
}



echo "<div style='text-align:right; margin: 10px 20px;'><a class='btn btn-primary' href='/?op=smartcards&csv=export'><i class='fas fa-cloud-upload-alt'></i> Export CSV</a>&nbsp;&nbsp;<a class='btn btn-primary' href='#' onclick='FindFile();'><i class='fas fa-cloud-download-alt'></i> Import CSV</a></div>";

echo '<form action="/?op=smartcards&csv=import" target="rFrame" method="POST" enctype="multipart/form-data">  
<div class="hiddenInput"><input type="file" id="my_hidden_file" accept="text/csv" name="loadfile" onchange="LoadFile();">  <input type="submit" id="my_hidden_load" style="display: none" value="Import"></div></form><iframe id="rFrame" name="rFrame" style="display: none"></iframe>

<script>
function FindFile() { document.getElementById("my_hidden_file").click(); }  
function LoadFile() { document.getElementById("my_hidden_load").click(); }
function onReloadPage(){ window.location.href="/?op=smartcards"; }
$(function(){
  $(".pair").click(function(){
    var $this = $(this);
    $.post("",{"op":"pair", "id":$this.attr("rel"), "val":$this.prop("checked")}, function(r){
      if(r!="0" && r!="1") alert("Unknown error set PAIR!");
    });
  });
});
</script>

<style>.hiddenInput{ position:absolute;overflow: hidden;display:block;  height:0px;  width:0px;}</style>';


$pp = pagenation($page, $numstories, $limit);
echo $pp; // пагинация
echo tablesorter($a, 'id',$limit);
echo $pp; // пагинация

echo modalwindow(1); // большое модальное окно





?>