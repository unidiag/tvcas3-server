<?

// если вход не с индексного файла
if(!@$en){
  header("Location: /cas");
}



// for pagination
$limit=50; // по 50 записей на одной странице
$page = (isset($_GET['p'])) ? intval($_GET['p']) : 1;
$offset = ($page - 1) * $limit;

if(@$_GET['clear']==1){
  mysql_query2("TRUNCATE `tvcas_logs`");
  header("Location: /cas/?op=log");
  exit;
}


$search = block_get('search');
$where = "";
if(!empty($search)){
  $where = " WHERE `msg` LIKE '%{$search}%' OR `user` LIKE '%{$search}%' OR `ip` LIKE '%{$search}%'";
}

$numstories = @mysql_num_rows(mysql_query2("SELECT `id` FROM `tvcas_logs`{$where}"));
$result = mysql_query2("SELECT * FROM `tvcas_logs`{$where} ORDER BY `id` DESC LIMIT {$offset},{$limit};");

$o = array();
while($row = mysql_fetch_assoc($result)){
  $tt = preg_replace("/({$search})/ui", "<span class='ins'>$1</span>", date("d.m.Y H:i:s", t($row['time'])) . " [{$row['user']}] {$row['msg']} ({$row['ip']})");
  $o[] = "<div" . ($row['type']?" class='ccc3'":"") . ">{$tt}</div>";
}


$pp = pagenation($page, $numstories, $limit);
echo "<div style='text-align:right; margin: 10px 20px;'><a class='btn btn-primary' href='/cas/?op=log&clear=1'><i class='fas fa-trash-alt'></i> Clear [{$numstories}]</a></div>{$pp}"; // пагинация
$ttt = implode("\n", $o);
$ttt = str_replace($search, "<span class='ins'>{$search}</span>", $ttt);
echo "<div style='margin:10px 20px;' class='log rounded'>{$ttt}</div>";
echo $pp; // пагинация

?>