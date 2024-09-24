<?








function implode_url($a){
  foreach($a as $k=>$v){
    $o[] = "{$k}={$v}";
  }
  return implode("&", $o);
}





/*

 CCCCC   SSSSS  VV     VV    IIIII MM    MM PPPPPP   OOOOO  RRRRRR  TTTTTTT 
CC    C SS      VV     VV     III  MMM  MMM PP   PP OO   OO RR   RR   TTT   
CC       SSSSS   VV   VV      III  MM MM MM PPPPPP  OO   OO RRRRRR    TTT   
CC    C      SS   VV VV       III  MM    MM PP      OO   OO RR  RR    TTT   
 CCCCC   SSSSS     VVV       IIIII MM    MM PP       OOOO0  RR   RR   TTT   
                                                                            

*/
function import_csv($mode=0){
  global $csv_rows;
  $rows = explode(";", ($mode ? $csv_rows['admin'] : $csv_rows['oper']));
  $file = "/tmp/import.csv";
  if(move_uploaded_file($_FILES['loadfile']['tmp_name'], $file)){
    
    $rows2 = array();
    $msg = array("<div class='text-success'><i class='fas fa-check'></i> File upload success!</div>");
    $csv = file($file);
    $rows2 = explode(";", str_replace(array("\n", "\r"), "", array_shift($csv)));
    $cnt = 0; // счётчик ошибок
    $cnt2 = 0; // количество новых строк
    $cnt3 = 0; // количество отредактированных строк
    $timestart = microtime(1);
    
    if(count($rows)==count($rows2)){
      foreach($csv as $k=>$v){
        $v = str_replace(array("\n", "\r"), "", $v);
        $vv = explode(";", $v);
        if($cnt < 10){
          if(count($vv)==count($rows2)){
            //if($vv[0]>2100000000 and $vv[0]>2109999999){
            if(preg_match("/^210[0-9]{7}$/", $vv[0])){
              if($mode and !preg_match("/[0-9A-Fa-f]{32}/", $vv[3])){
                $msg[] = "<div class='text-danger'><i class='fas fa-exclamation-triangle'></i> Line " . ($k+2) . ". Key number must be HEX and length 16 bytes (32 symbols).</div>";
                $cnt++;
                continue;
              }
              $foradd = array('c_time'=>time()); // новый массив для добавлений
              $forupd = array();
              foreach($vv as $kkk=>$vvv){ // перебераем в строке значения через ;
                $kkkk = $rows[$kkk];
                if($kkkk=='start' or $kkkk=='finish') $vvv = intval($vvv);
                if($kkkk=='key') $vvv = strtoupper($vvv);
                if($kkkk=='edit') $vvv = time();
                $foradd["`{$kkkk}`"] = "'" . reredos($vvv) . "'";
                $forupd[] = "`{$kkkk}`='" . reredos($vvv) . "'";
              }
              if(mysql_num_rows(mysql_query2("SELECT `id` FROM `tvcas_smartcards` WHERE `serial_no`='{$vv[0]}'"))){
                  $sql = "UPDATE `tvcas_smartcards` SET " . implode(", ", $forupd) . " WHERE `serial_no`='{$vv[0]}';";
                  $cnt3++;
              }else{
                  $sql = "INSERT INTO `tvcas_smartcards` (" . implode(",", array_keys($foradd)) . ") VALUES (" . implode(", ", $foradd) . ");";
                  $cnt2++;
              }
              mysql_query2($sql);
            }else{
              $msg[] = "<div class='text-danger'><i class='fas fa-exclamation-triangle'></i> Line " . ($k+2) . ". Serial number must be from 2100000000 till 2109999999 (not <b>{$vv[0]}</b>)</div>";
              $cnt++;
            }
          }else{
            $msg[] = "<div class='text-danger'><i class='fas fa-exclamation-triangle'></i> Line " . ($k+2) . ". The quantity fields does not match!</div>";
            $cnt++;
          }
        }else{
          $msg[] = "<div class='text-danger'><i class='fas fa-exclamation-triangle'></i> <b>Import break!</b></div>";
          break;
        }
        
        $vals = explode(";", str_replace(array("\n", "\r"), "", $v));
        
      }
    }else{
      $msg[] = "<div class='text-danger'><i class='fas fa-exclamation-triangle'></i> Count of fields in the imported (" . count($rows2) . ") file does not match the exported (" . count($rows) . ")</div>";
      $msg[] = "<div class='text-danger'><i class='fas fa-exclamation-triangle'></i> <b>Import break!</b></div>";
    }
    $ttt = round(microtime(1)-$timestart,3);
    $msg[] = "<hr /><code>Add new: {$cnt2}<br />Update items: {$cnt3}<br />Total: {$ttt} sec.</code>";
    logs("Import CSV, add: {$cnt2}, upd: {$cnt3}. Total: {$ttt} sec.");
    alert(implode("", $msg)); 
    @unlink($file);
  }else{
    alert("<div class='text-danger'><i class='fas fa-exclamation-triangle'></i> File upload error!</div>");
  }
  
  echo '<script type="text/javascript"> window.parent.onReloadPage(); </script> '; 
}








/*

 CCCCC   SSSSS  VV     VV      GGGG  EEEEEEE NN   NN EEEEEEE RRRRRR    AAA   TTTTTTT EEEEEEE 
CC    C SS      VV     VV     GG  GG EE      NNN  NN EE      RR   RR  AAAAA    TTT   EE      
CC       SSSSS   VV   VV     GG      EEEEE   NN N NN EEEEE   RRRRRR  AA   AA   TTT   EEEEE   
CC    C      SS   VV VV      GG   GG EE      NN  NNN EE      RR  RR  AAAAAAA   TTT   EE      
 CCCCC   SSSSS     VVV        GGGGGG EEEEEEE NN   NN EEEEEEE RR   RR AA   AA   TTT   EEEEEEE 
                                                                                             

*/

function generate_csv($mode=0){
  global $csv_rows;
  $file = "/tmp/" . date("Y-m-d_His", t()) . "_" . ($mode?"admin":"oper") . ".csv";
  ob_get_clean();
  if($mode){
    // для админа
    $o = array($csv_rows['admin']);
  }else{
    // для опера
    $o = array($csv_rows['oper']);
  }
  
  $rows = array();
  foreach(explode(";", $o[0]) as $v){
    $rows[] = "`{$v}`";
  }
  $sql = "SELECT " . implode(",", $rows) . " FROM `tvcas_smartcards` ORDER BY `serial_no`";
  $result = mysql_query2($sql);
  while($row = mysql_fetch_assoc($result)){
    $o[] = implode(";", $row);
  }
  
  
  file_put_contents($file, implode("\n", $o));
  logs("Export CSV, " . intval(filesize($file)/1024) . " KB, " . count($o) . " items.");
  file_force_download($file);
  die("Unknown error!");
}






function file_force_download($file) {
  if (file_exists($file)) {
    // сбрасываем буфер вывода PHP, чтобы избежать переполнения памяти выделенной под скрипт
    // если этого не сделать файл будет читаться в память полностью!
    if (ob_get_level()) {
      ob_end_clean();
    }
    // заставляем браузер показать окно сохранения файла
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . basename($file));
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    // читаем файл и отправляем его пользователю
    readfile($file);
    @unlink($file);
    exit;
  }
}











/*

PPPPPP    AAA     GGGG  IIIII NN   NN   AAA   TTTTTTT IIIII  OOOOO  NN   NN 
PP   PP  AAAAA   GG  GG  III  NNN  NN  AAAAA    TTT    III  OO   OO NNN  NN 
PPPPPP  AA   AA GG       III  NN N NN AA   AA   TTT    III  OO   OO NN N NN 
PP      AAAAAAA GG   GG  III  NN  NNN AAAAAAA   TTT    III  OO   OO NN  NNN 
PP      AA   AA  GGGGGG IIIII NN   NN AA   AA   TTT   IIIII  OOOO0  NN   NN 
                                                                            

*/

function pagenation($page, $numstories, $limit=30){

  $numpages = ceil($numstories / $limit);
  $s = block_get('search');
  if($numstories<=$limit) return;
  ob_start();


  $url = parse_url($_SERVER['REQUEST_URI']);

  $q = explode("&", $url['query']);
  foreach($q as $k=>$v){
    if(strpos($v, "p=")===0) unset($q[$k]);
    if(strpos($v, "s=")===0) unset($q[$k]);
  }
  $url = $url['path'] . "?" . ((count($q)>0 and $q[0]!='') ? implode("&", $q) . "&" : "");
  
  if($page>$numpages){
    header("Location: {$url}");
    exit;
  }
  
  

  if($numpages>1){
    echo "<nav aria-label='Page navigation'><ul class='pagination justify-content-end' style='font:11px/1em Verdana;'>";
    echo "<li class='page-item" . ($page>1?"":" disabled") . "'><a title='Prev' class='page-link' href='{$url}p=" . ($page-1) . ($s ? "&s=" . urlencode($s) : "") . "' tabindex='-1'><i class='fas fa-backward'></i></a></li>";
      
    for ($i = 1; $i < $numpages+1; $i++) {
      if ($i == $page) echo "<li class='page-item active'><a class='page-link' href='#' onclick='return!1;'>{$i}</a></li>";
        else if ((($i > ($page - 8)) && ($i < ($page + 8))) OR ($i == $numpages) || ($i == 1))
          echo "<li class='page-item'><a class='page-link' href='{$url}p={$i}" . ($s ? "&s=" . urlencode($s) : "") . "'>{$i}</a></li>";
          
      if ($i < $numpages) {
        if (($i > ($page - 9)) && ($i < ($page + 8))) echo " ";
        if (($page > 9) && ($i == 1)) echo "<li class='page-item disabled'><a class='page-link' href='#'>...</a></li>";
        if (($page < ($numpages - 8)) && ($i == ($numpages - 1))) echo "<li class='page-item disabled'><a class='page-link' href='#'>...</a></li>";
      }
    }
      
    echo "<li class='page-item" . ($page<$numpages?"":" disabled") . "'><a title='Next' class='page-link' href='{$url}p=" . ($page+1) . ($s ? "&s=" . urlencode($s) : "") . "'><i class='fas fa-forward'></i></a></li>";
    echo "</ul></nav><style>.page-link{padding:3px 8px;} ul.pagination{margin-right:20px;}</style>";
  }

  return ob_get_clean();
}












function slyspace($s, $n=2, $sep=' '){
  $s = str_replace(" ", "", $s);
  $ss = "";
  for($i=0; $i<strlen($s); $i++){
    if($i % $n === 0 && $i!=0) $ss .= $sep;
    $ss .= $s{$i};
  }
  return strtoupper($ss);
}


// 0 - обший, 1 - важный
function logs($msg, $type=0){
  $ip = (@$_SERVER['REMOTE_ADDR']?reredos($_SERVER['REMOTE_ADDR']):"127.0.0.1");
  $user = (@$_SERVER['PHP_AUTH_USER']?reredos($_SERVER['PHP_AUTH_USER']):"cron");
  mysql_query2("INSERT INTO `tvcas_logs` (`msg`, `type`, `user`, `time`, `ip`) VALUES ('" . reredos($msg) . "', {$type}, '{$user}', " . time() . ", '{$ip}');");
}


// убИраем кавычки
function reredos($s){
  return str_replace(array("`", "'", ";", "%"), "", $s);
}



$_blocks = array();


function block_set($name, $value){
  global $_blocks;
  $_blocks[$name] = $value;
  return;
}

function block_get($name, $default = ''){
  global $_blocks;
  return (isset($_blocks[$name]) ? $_blocks[$name] : $default);
}


function daynow($date){
  $d = explode(" ", $date);
  if($d[0] == date("d.m.Y", t())){
    $d[0] = "<code>Today</code>";
  }elseif($d[0] == date("d.m.Y", t()-86400)){
    $d[0] = "Yesterday";
  }elseif($d[0] == date("d.m.Y", t()+86400)){
    $d[0] = "Tomorrow";
  }
    
  return implode(" at ", $d);
}


function t($time=''){
  global $config;
  if($time=='')$time=time();
  $offset = $config['zone'];
  if(empty($offset)){
    $offset = chk(block_get('config'), 'zone');
  }
  $utc = $time - date("Z"); // UTC по гринвичу
  $z = intval(substr($offset, 1, 2))*3600 + intval(substr($offset, 3, 2))*60;
  if(substr($offset, 0, 1)=="-") $z = 0-$z;
  $time = ($utc+$z);
  return $time;
}

// BLOCKS
function chk($v, $k, $d = null) {
  return isset($v[$k]) ? $v[$k] : $d;
}


function head(){
  if(@$_SESSION['user'] != $_SERVER['PHP_AUTH_USER']){
    $_SESSION['user'] = $_SERVER['PHP_AUTH_USER'];
    logs("The user logged in to the {$_SESSION['user']} panel");
  }
  $o = '<!doctype html>
<html class="no-js" lang="en">
    <head>
        <meta charset="utf-8">
        <title>' . block_get('title') . ' | TVCAS.COM</title>
        <link rel="stylesheet" href="/sources/bootstrap.min.css">
        <script src="/sources/jquery.min.js"></script>
        <script src="/sources/script.js"></script>
        <script src="/sources/popper.min.js"></script>
        <script src="/sources/bootstrap.min.js"></script>
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.0/css/all.css" integrity="sha384-lZN37f5QGtY3VHgisS14W3ExzMWZxybE1SJSEsQp9S+oqd12jhcu+A56Ebc1zFSJ" crossorigin="anonymous">
    </head>
    <body class="document">';

  return $o;
}


function foot(){
  global $config;
  $o = "";
  if(@$_SESSION['alert']){
    if(@$_SESSION['alertn']>0){
    $o .= '<div class="modal fade " id="alertModal" tabindex="-1" role="dialog" aria-labelledby="alertModalLabel" aria-hidden="true"><div class="modal-dialog " role="document"><div class="modal-content "><div class="modal-header"><h5 class="modal-title" id="remindModalLabel"><i class="fas fa-info-circle"></i> Information</h5><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><div class="modal-body">' . $_SESSION['alert'] . '<br />&nbsp;</div></div></div></div><script>$(function(){$("#alertModal").modal();});</script>';
    unset($_SESSION['alert']);
    unset($_SESSION['alertn']);    
    }else{
      $_SESSION['alertn'] = 1;
    }
  }
  
  
  $o .= "<div style='text-align:center; color:gray; font:11px/1.2em Verdana; margin-bottom:30px;'>Localtime: " . date("H:i:s", t()) . " (" . $config['zone'] . "). Page generated at " . round(microtime(1)-$config['time_start'], 3) . " sec.<br />&copy2018-" . date("Y") . " Copyright by <a href='https://tvcas.com' style='color:gray;' target='_blank'>TVCAS.COM</a> ({$config['ver']})" . ($_SERVER['PHP_AUTH_USER'] == "admin" ? "<br /><i class='fas fa-download'></i>&nbsp;Download uploader: <a href='https://tvcas.com/uploader_win64.exe'>Windows</a> | <a href='https://tvcas.com/uploader_x64'>Linux</a>" : "") . "</div></body></html><style>.log{min-height:400px;background-color:#333; color:#eee;padding:10px 20px; font:13px/1.1 Courier New;}.ccc0{color:#00FFFF}.ccc1{color:#FF00FF}.ccc2{color:#00FF00}.ccc3{color:#FF0000}.ccc4{color:#008080}.ccc5{color:#FFFF00}.ins{background-color:yellow;} .log .ins{color:#111;} .ac{cursor:pointer; color:#007bff;} .ac:hover{border-bottom:1px dashed #007bff;}</style>";
  return $o;
}


function alert($str){
  $_SESSION['alert'] = $str;
}


function modalwindow($lg=0){
  $out = "<form action='' method='post'>
          <!-- infoModal -->
          <div class='modal fade' id='infoModal' tabindex='-1' role='dialog' aria-labelledby='infoModalLabel' aria-hidden='true'><div class='modal-dialog" . ($lg?" modal-lg":"") . " role='document'><div class='modal-content'><div class='modal-header'><h5 class='modal-title' id='infoModalLabel'></h5><button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button></div><div class='modal-body'></div><div class='modal-footer'></div></div></div></div>
          </form>

 <script>
$(function(){
  $('.getModal').click(function(){
    $.post('', {'op':$(this).attr('op'), 'val':$(this).attr('val')}, function(r){
      if(r.status=='ok'){
        $('#infoModal .modal-title').html(r.title);
        $('#infoModal .modal-body').html(r.body);
        $('#infoModal .modal-footer').html(r.footer);
        $('[data-toggle=\"popover\"]').popover({ trigger: 'focus' });
      }
      $('#infoModal').modal();
    }, 'json');
    return!1;
  });
  
});
</script>";
  return $out;
}














function tablesorter($arr, $id='id', $limit=50){
  $p = (isset($_GET['p'])) ? intval($_GET['p']) : 1;
  ob_start();
  echo "<table class='tablesorter'><thead><tr>";
  $th = array(); 
  $td = array();
  $rel = "";
  $i = 1;
  foreach($arr as $v){
    $o = "<tr><td>" . ($i + ($p-1)*$limit). "</td>";
    if($i==1) $th[] = "<th scope='col'>#</th>";
    foreach($v as $kk=>$vv){
      if($i==1) $th[] = "<th scope='col'>{$kk}</th>";
      $o .= "<td>{$vv}</td>";
      if($kk==$id) $rel = $vv;
    }
    $o .= "</tr>";
    $td[] = str_replace("<tr>", "<tr rel='{$rel}'>", $o);
    $i++;
  }
  echo implode("", $th) . "</tr></thead><tbody>" . implode("", $td) . "</tbody></table>";
?>
<script type="text/javascript" src="/sources/tablesorter/jquery.tablesorter.js"></script>
<script>
$(function(){
     $("table").tablesorter({widgets: ['zebra']});
});
</script>
<style>
table.tablesorter {
	font-family:Verdana;
	background-color: #CDCDCD;
	margin:10px 0pt 15px;
	font-size: 8pt;
	width: 100%;
	text-align: left;
}
table.tablesorter thead tr th, table.tablesorter tfoot tr th {
	border: 1px solid #FFF;
	font-size: 8pt;
	padding: 4px;
	padding-right: 20px;
}
table.tablesorter thead tr .header {
	background-image: url(/sources/tablesorter/tablesorter-bg.gif);
	background-repeat: no-repeat;
	background-position: center right;
	cursor: pointer;
}
table.tablesorter tbody td {
	color: #3D3D3D;
	padding: 4px;
	background-color: #FFF;
	vertical-align: top;
}
table.tablesorter tbody tr.odd td {
	background-color:#e9f4ff;
}
table.tablesorter thead tr .headerSortUp {
	background-image: url(/sources/tablesorter/tablesorter-asc.gif);
}
table.tablesorter thead tr .headerSortDown {
	background-image: url(/sources/tablesorter/tablesorter-desc.gif);
}
table.tablesorter thead tr .headerSortDown, table.tablesorter thead tr .headerSortUp {
	background-color: #b5b5b5;
}
</style>
<?
  
  return ob_get_clean();
}






/*

DDDDD     AAA   TTTTTTT EEEEEEE     2222      TTTTTTT IIIII MM    MM EEEEEEE 
DD  DD   AAAAA    TTT   EE         222222       TTT    III  MMM  MMM EE      
DD   DD AA   AA   TTT   EEEEE          222      TTT    III  MM MM MM EEEEE   
DD   DD AAAAAAA   TTT   EE          2222        TTT    III  MM    MM EE      
DDDDDD  AA   AA   TTT   EEEEEEE    2222222      TTT   IIIII MM    MM EEEEEEE 
                                                                             

*/

function datetotime($date){
  // вводим дату 30.05.2019 10:45:23 и получаем UTC
  $a = explode(" ", $date);
  $d = explode(".", $a[0]);
  $t = explode(":", $a[1]);
  $time = mktime(intval(@$t[0]), intval($t[1]), intval(@$t[2]), intval(@$d[1]), intval(@$d[0]), intval(@$d[2]));
  $offset = chk(block_get('config'), 'zone'); 
  $z = intval(substr($offset, 1, 2))*3600 + intval(substr($offset, 3, 2))*60;
  if(substr($offset, 0, 1)=="-") $z = 0-$z;
  $time = $time - $z + date("Z");
  return $time;
}









function reload_emm(){
  mysql_query2("UPDATE `tvcas_emmg` SET `reload`=1, `version`=`version`+1 WHERE `enable`=1;");
}




function start_gen($type='', $num=0){
  exec("sudo " . __DIR__ . "/../cas/bin/{$type}.php --generator-id={$num} 1>/dev/null 2>&1 &");
  logs("Start " . strtoupper($type) . "_{$num}");
  return true;
}



function stop_gen($type='', $num=0){
  $o = array();
  $ps = exec("ps aux | grep {$type}.php", $o);
  $return = "";
  foreach($o as $k=>$v){
    if(preg_match("/^root(.*?)([0-9]{3,5}) (.*?) \-\-generator\-id={$num}/i", $v, $m)){
      $s = "sudo /bin/kill -9 {$m[2]}";
      $return .= "{$s}\n";
      exec($s);
    }
  }  
  exec("sudo /bin/rm /var/run/{$type}_{$num}.pid");
  logs("Stop " . strtoupper($type) . "_{$num}", 1);
  return $return;
}




function status_gen($type='', $num=0){
  $file = "/var/run/{$type}_{$num}.pid";
  if(is_file($file)){
    return true;
  }
  return false;
}


























// хитрые даты для карты
function slydate($d){
  $o = decbin(floor(round(date("Y", $d)-1990) / 10)); // десятилетие 3 бита
  $o .= numbFormat(decbin(date("d", $d)), 5); // пять битов числа дня
  $o .= numbFormat(decbin(date("Y", $d) % 10), 4); // четыре бита последней цифры года
  $o .= numbFormat(decbin(date("m", $d)), 4); // четыре бита номера месяца
  return bindec($o);
}



// из str 010203AABB в массив dec = array(1,2,3,170,187);
function hex2arr($str){
  $o = array();
  for($i=0; $i<strlen($str); $i += 2){
    $o[] = hexdec($str{$i}.$str{$i+1});
  }
  return $o;
}


function arr2hex($arr){
  $str = "";
  foreach($arr as $v){
    if($v <= 16)
    $str .= (($v <= 16) ? "0" : "") . dechex($v);
  }
  return $str;
}






function numbFormat($digit, $width) {
    while(strlen($digit) < $width)
      $digit = '0' . $digit;
      return $digit;
}


function is_empty($var){
    return !isset($var) || empty($var) && !is_numeric($var);
}

function mysql_query2($query){
    global $config;
    $GLOBALS["mysql_handle"] = mysqli_connect($config['mysql_server'], $config['mysql_user'], $config['mysql_pass'], $config['mysql_base']);
    $GLOBALS['mysql_handle']->set_charset("utf8");    

    if(!($res = mysqli_query($GLOBALS["mysql_handle"], $query))){
        $GLOBALS["mysql_error"] = mysqli_error($GLOBALS['mysql_handle']);
        mysql_close2();
    }

    return $res;
}



if(!function_exists('mysql_num_rows')){
  function mysql_num_rows($a){
    return mysqli_num_rows($a);
  }
}

if(!function_exists('mysql_fetch_assoc')){
  function mysql_fetch_assoc($a){
    return mysqli_fetch_assoc($a);
  }
}

if(!function_exists('mysql_fetch_row')){
  function mysql_fetch_row($a){
    return mysqli_fetch_row($a);
  }
}



function mysql_last_error($unset = true){
    if( !isset($GLOBALS["mysql_error"]) ) 
    {
        return "no error";
    }

    $rv = $GLOBALS["mysql_error"];
    if($unset){
        unset($GLOBALS["mysql_error"]);
    }

    return $rv;
}

function mysql_close2(){
    if( !isset($GLOBALS["mysql_handle"])){
        return NULL;
    }

    if( $GLOBALS["mysql_handle"]){
        mysqli_close($GLOBALS["mysql_handle"]);
    }

    unset($GLOBALS["mysql_handle"]);
}

function make_random($len){
    $o = "";
    $i = 1;
    while($i <= $len){
        $o .= pack("C", rand(0,255));
        $i++;
    }
    return $o;
}

function implode_kv($glue, $pieces){
    $tmp = array(  );
    foreach( $pieces as $key => $value ) 
    {
        $tmp[] = $key . " => " . $value;
    }
    return implode($glue, $tmp);
}


function log_d($id, $message){
    echo date("Y-m-d H:i:s") . " " . $id . " " . $message . "\n";
    syslog(LOG_DEBUG, $id . " " . $message);
}




?>