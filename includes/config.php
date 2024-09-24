<?

session_start();

setlocale(LC_ALL, 'ru_RU.utf8');
#mb_internal_encoding('utf-8');
ini_set('max_execution_time', 120);
ini_set('register_globals', 0);
ini_set('magic_quotes_runtime', 0);
ini_set('magic_quotes_sybase', 0);
#ini_set('session.use_only_cookies', 1);
ini_set('url_rewriter.tags', '');
ini_set('error_log', 'error_log');
ini_set('log_errors', 1);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL & ~E_NOTICE);
header("Cache-Control: no-cache, no-store");
header("Content-Type: text/html; charset=utf-8");




$config = array(

  'ver' => "v.3.2",
  'zone' => date("O"),
  'ident' => "0B00",
  'ecm_key' => "7E33A278B3617A309B18222F372A231907AC67E08A5F12C3F74769DE8374C7AE",
  //'ecm_key' => "A903001610EA18020E7F554CC2180B00079D010B0033CE98D91714300B140D03",
  'pass_admin' => "zxspectrum123",
  'pass_oper' => "zxspectrum123",
  'mysql_server' => "localhost",
  'mysql_user' => "tvcas",
  'mysql_pass' => "zxspectrum128",
  'mysql_base' => "tvcas",
  'api_key' => "trololoapi",
  'trademark' => "TVCAS.COM",
  'time_start' => microtime(1)

);




$csv_rows = array(
  'admin' => "serial_no;name;info;key;access_criteria;type;pair;start;finish;edit",
  'oper' => "serial_no;name;info;access_criteria;pair;start;finish"
);



?>