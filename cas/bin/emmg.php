#!/usr/bin/php
<?



//$GLOBALS["debug"] = 0;
//error_reporting(($GLOBALS["debug"] ? E_ALL ^ E_NOTICE ^ E_WARNING ^ E_DEPRECATED : 0));
set_time_limit(0);
umask(23);
chdir(__DIR__);
require_once(__DIR__ . "/functions.php");
array_shift($_SERVER["argv"]);
parse_str(implode("&", $_SERVER["argv"]), $params); // склеивает в key=123&--generator-id=1
$generator_id = $params['--generator-id'] ? $params['--generator-id'] : 1;
$auto_renew = 0;
$ident = getenv("tvcas_ident");
$GLOBALS["pid_file"] = false;


$run_guard = (getenv("tvcas_emmg_guard") === false ? true : false);
if( !$run_guard ){
    unset($GLOBALS["pid_file"]);
}

openlog("emmg" . (($ident === false ? "" : "_" . $ident)) . "_" . $generator_id, LOG_ODELAY | LOG_PID, LOG_LOCAL0);
posix_setsid();
//pcntl_signal(SIGINT, "sig_handler");
//pcntl_signal(SIGTERM, "sig_handler");
//pcntl_signal(SIGHUP, SIG_IGN);
//pcntl_signal(SIGCHLD, SIG_IGN);
//pcntl_signal(SIGUSR1, SIG_IGN);

/*

RRRRRR  UU   UU NN   NN           GGGG  UU   UU   AAA   RRRRRR  DDDDD   
RR   RR UU   UU NNN  NN          GG  GG UU   UU  AAAAA  RR   RR DD  DD  
RRRRRR  UU   UU NN N NN         GG      UU   UU AA   AA RRRRRR  DD   DD 
RR  RR  UU   UU NN  NNN         GG   GG UU   UU AAAAAAA RR  RR  DD   DD 
RR   RR  UUUUU  NN   NN _______  GGGGGG  UUUUU  AA   AA RR   RR DDDDDD  
                                                                        

*/

if($run_guard){
  
    if($GLOBALS["pid_file"] === false){
        $GLOBALS["pid_file"] = "/var/run/" . (($ident === false ? "" : $ident . "_")) . "emmg_" . $generator_id . ".pid";
    }else{
      // log_d(0, "okay #pid_file: " . $GLOBALS["pid_file"]);
    }

    if(is_file($GLOBALS["pid_file"])){
        // log_d(0, "error #" . 1333 . ": pid file already exist");
        exit();
    }

    if(@file_put_contents($GLOBALS["pid_file"], @posix_getpid()) === false){
        // log_d(0, "error #" . 1338);
        exit();
    }else{
      // log_d(0, "okay #pid=" . @posix_getpid());
    }

    putenv("tvcas_emmg_guard=42");
    while(true){
      
        $GLOBALS["main_pid"] = pcntl_fork();
        if( $GLOBALS["main_pid"] == -1 ) 
        {
        }

        if( $GLOBALS["main_pid"] == 0 ){
            
            posix_setsid();
            pcntl_exec(__FILE__, $_SERVER["argv"]);
            
        }

        while( true ) 
        {
            if( pcntl_wait($status, WNOHANG) != 0 ){
                break;
            }
            sleep(1);
            //file_put_contents(__DIR__ . "/0.txt", date("H:i:s") . " " . $GLOBALS["main_pid"]);
        }
        // log_d(0, "main process exited, will restart in 10 sec. (status=" . $status . ")");
        sleep(10);
    }
}

/*

DDDDD    OOOOO  WW      WW NN   NN LL       OOOOO    AAA   DDDDD      KK  KK EEEEEEE YY   YY  SSSSS  
DD  DD  OO   OO WW      WW NNN  NN LL      OO   OO  AAAAA  DD  DD     KK KK  EE      YY   YY SS      
DD   DD OO   OO WW   W  WW NN N NN LL      OO   OO AA   AA DD   DD    KKKK   EEEEE    YYYYY   SSSSS  
DD   DD OO   OO  WW WWW WW NN  NNN LL      OO   OO AAAAAAA DD   DD    KK KK  EE        YYY        SS 
DDDDDD   OOOO0    WW   WW  NN   NN LLLLLLL  OOOO0  AA   AA DDDDDD     KK  KK EEEEEEE   YYY    SSSSS  
                                                                                                     

*/

//file_put_contents(__DIR__ . "/1.txt", date("H:i:s") . " " . posix_getpid());


$sql = "UPDATE `tvcas_emmg` SET `reload`='0', `touch_version`='0', `touch_time`='0' WHERE `id`='" . $generator_id . "'";
if( !mysql_query2($sql) ) exit();

$sql = "SELECT `id`, `network_id`, `host`, `port`, `protocol_version`, `packet_mode`, `bandwidth`, `repetition`, `period`, `period_messages`, `channel_test_delay`, `stream_test_delay`, `timeout`, `client_id`, `version`, `uid` FROM `tvcas_emmg` WHERE `status`='1' AND `d_id`='0' AND `id`='" . $generator_id . "'";
if( !($result = mysql_query2($sql)) ) exit();
if( mysql_num_rows($result) == 0 ) exit;

/*

EEEEEEE MM    MM MM    MM   GGGG          
EE      MMM  MMM MMM  MMM  GG  GG         
EEEEE   MM MM MM MM MM MM GG      ======= 
EE      MM    MM MM    MM GG   GG ======= 
EEEEEEE MM    MM MM    MM  GGGGGG         
                                          

*/

// log_d(0, "KYKY LYALYA");

$emmg = array( "config" => mysql_fetch_assoc($result), "slots" => (int)$config["slot_cnt"], "state" => 0, "status" => array( "channel_time_open" => 0, "stream_time_open" => 0, "error_time_last" => 0, "error_count" => 0, "datagram_time_last" => 0, "datagram_count" => 0, "queue_size" => 0), "keys" => array(), "pair" => array(), "type" => array(), "cache" => array(), "cache_max_id" => 0, "queue" => array(), "delay" => array(), "reload"=>0);
//$emmg['config']['id'] = 1;
$emmg["config"]["period"] *= 86400;
$emmg["config"]["period_messages"] *= 86400;
if( $emmg["config"]["repetition"] < 60 ){
    $emmg["config"]["repetition"] = 60;
}

if( $emmg["slots"] == 0 ){
    $emmg["slots"] = 128;
}


if(!keys_load(0, $emmg))exit();
if(!cache_load(0, $emmg))exit();

$GLOBALS["pid_main"] = posix_getpid();
$GLOBALS["pid_connect"] = 0;
$GLOBALS["cache_reload"] = false;
$GLOBALS["queue_reload"] = false;
//pcntl_signal(SIGHUP, "sig_handler");
//pcntl_signal(SIGCHLD, "sig_handler");
//pcntl_signal(SIGUSR1, "sig_handler");
mysql_close2();
$GLOBALS["pid_connect"] = pcntl_fork();
switch($GLOBALS["pid_connect"]){
    case -1:
        // log_d(0, "error #" . 1488);
        exit();
    case 0:
        // log_d(0, "probe DO_CONNECT()");
        if(!do_connect($emmg)){
            // log_d(0, "ERROR do_connect();");
            exit();
        }
        exit();
}



/*

WW      WW HH   HH IIIII LL      EEEEEEE       1       
WW      WW HH   HH  III  LL      EE       ((( 111 )))  
WW   W  WW HHHHHHH  III  LL      EEEEE   (((   11  ))) 
 WW WWW WW HH   HH  III  LL      EE      (((   11  ))) 
  WW   WW  HH   HH IIIII LLLLLLL EEEEEEE (((  111  ))) 
                                          (((     )))  

*/

mysql_query2("UPDATE `tvcas_emmg` SET `version`='1', `touch_version`='1' WHERE `id`='{$generator_id}';");

while(true){
    $sql = "UPDATE `tvcas_emmg` SET `touch_version`='{$emmg["config"]["version"]}', `touch_time`='" . time() . "' WHERE `id`='{$generator_id}';";
    if(!mysql_query2($sql)) break;
    sleep(60);
}
exit();









/*

KK  KK EEEEEEE YY   YY  SSSSS     LL       OOOOO    AAA   DDDDD   
KK KK  EE      YY   YY SS         LL      OO   OO  AAAAA  DD  DD  
KKKK   EEEEE    YYYYY   SSSSS     LL      OO   OO AA   AA DD   DD 
KK KK  EE        YYY        SS    LL      OO   OO AAAAAAA DD   DD 
KK  KK EEEEEEE   YYY    SSSSS     LLLLLLL  OOOO0  AA   AA DDDDDD  
                                                                  

*/







function keys_load($log_id, &$emmg)
{
  
  
  
    $keys =& $emmg["keys"];
    $pair =& $emmg["pair"];
    $type =& $emmg["type"];
    //$sql = "SELECT `serial_no`,`key` FROM `tvcas_smartcards` WHERE `network_id`='" . $emmg["config"]["network_id"] . "'";
    //$sql = "SELECT `serial_no`,`key`,`pair` FROM `tvcas_smartcards` WHERE `uid`='{$emmg['config']['uid']}'";
    $sql = "SELECT `serial_no`,`key`,`pair`,`type` FROM `tvcas_smartcards`;";
    
    $result = mysql_query2($sql);
    $total = mysql_num_rows($result);

    while($row = mysql_fetch_assoc($result)){
        $keys[$row["serial_no"]] = $row["key"];
        $pair[$row["serial_no"]] = $row['pair'];
        $type[$row["serial_no"]] = $row['type'];
    }
    
    // log_d($log_id, "operational keys - loaded successfully, items: " . $total);
    return true;
    
}





/*

 CCCCC    AAA    CCCCC  HH   HH EEEEEEE    LL       OOOOO    AAA   DDDDD   
CC    C  AAAAA  CC    C HH   HH EE         LL      OO   OO  AAAAA  DD  DD  
CC      AA   AA CC      HHHHHHH EEEEE      LL      OO   OO AA   AA DD   DD 
CC    C AAAAAAA CC    C HH   HH EE         LL      OO   OO AAAAAAA DD   DD 
 CCCCC  AA   AA  CCCCC  HH   HH EEEEEEE    LLLLLLL  OOOO0  AA   AA DDDDDD  
                                                                           

*/




function cache_load($log_id, &$emmg){
    $keys =& $emmg["keys"];
    $pair =& $emmg["pair"];
    $type =& $emmg["type"];
    $cache =& $emmg["cache"];
    $now = time();
    $total = 0;
    $total_start = microtime(true);
    $items = 0;
    $items_start = $total_start;

/*

  AAA   DDDDD   DDDDD   
 AAAAA  DD  DD  DD  DD  
AA   AA DD   DD DD   DD 
AAAAAAA DD   DD DD   DD 
AA   AA DDDDDD  DDDDDD  
                        

*/
        $diff = microtime(true) - $total_start;
        
        $sql = "SELECT * FROM `tvcas_smartcards` WHERE `edit`>" . ($now-86400*7) . " OR (`start`<{$now} AND `finish`>{$now}) ORDER BY `edit` DESC;";
        //$sql = "SELECT * FROM `tvcas_smartcards` ORDER BY `edit` DESC;";
        //$sql = "SELECT * FROM `tvcas_smartcards` WHERE `start`<{$now} AND `finish`>{$now};";
        $result = mysql_query2($sql);
        $total = mysql_num_rows($result);

        // добавление подписок
        if($total>0){

          while($row = mysql_fetch_assoc($result)){
            
              $row['subscription_id'] = 0;

              //// log_d($log_id, "#### serial_no={$row["serial_no"]}, access_criteria={$row['access_criteria']}");
              
              if( !($datagram = emm_subscription_add_pack($log_id, $row["serial_no"], $row['access_criteria'], $keys[$row["serial_no"]], $pair[$row["serial_no"]], $type[$row["serial_no"]], $row["start"], $row["finish"])) ) {
                  // log_d($log_id, "error #" . 260);
                  continue;
              }

              if( !emmg_mux_data_provision_pack($emmg, $datagram, $pdu, $pdu_len) ){
                  // log_d($log_id, "error #" . 272);
                  continue;
              }

              $cache[$row["serial_no"]][$row["subscription_id"]] = pack("CLL", 1, 0, $row["finish"]) . $pdu;
              
              if( ++$items % 1000 == 0 ){
                  $diff = microtime(true) - $items_start;
                  // log_d($log_id . " *", sprintf("cache (add subscriptions) - partialy loaded, items: %u of %u, time: %.4f sec (%.2f items/s)", $items, $total, $diff, 1000 / $diff));
                  $items_start = microtime(true);
              }
           }
           
        }
        return true;
}


















/*

 QQQQQ  UU   UU EEEEEEE UU   UU EEEEEEE    LL       OOOOO    AAA   DDDDD   
QQ   QQ UU   UU EE      UU   UU EE         LL      OO   OO  AAAAA  DD  DD  
QQ   QQ UU   UU EEEEE   UU   UU EEEEE      LL      OO   OO AA   AA DD   DD 
QQ  QQ  UU   UU EE      UU   UU EE         LL      OO   OO AAAAAAA DD   DD 
 QQQQ Q  UUUUU  EEEEEEE  UUUUU  EEEEEEE    LLLLLLL  OOOO0  AA   AA DDDDDD  
                                                                           

*/

function queue_load($log_id, &$emmg, $index)
{
    $cache =& $emmg["cache"];
    if( !isset($emmg["queue"][$index]) ) 
    {
        $emmg["queue"][$index] = array(  );
    }

    $queue =& $emmg["queue"][$index];
    $now = time();
    $time_start = microtime(true);
    $a = 0;
    $b = 0;
    foreach( $cache as $key1 => $value1 ) 
    {
        $c = array(  );
        $d = 0;
        $e = 0;
        krsort($value1);
        foreach( $value1 as $key2 => $value2 ) 
        {
            $item = unpack("Ca/Lp/Lt", $value2);
            if( $item["t"] < $now ) 
            {
                unset($cache[$key1][$key2]);
                continue;
            }

            if( $now < $item["p"] ) 
            {
                continue;
            }

            if( $item["a"] == 4 && 16 < ++$e ) 
            {
                unset($cache[$key1][$key2]);
                continue;
            }

            $c[++$d] =& $cache[$key1][$key2];
        }
        
        if(!@$d){
          $step = 0;
          $d =0;
        }
        
        if( $d!=0 and ($step = (100 / @$d) % 100) == 0 ) 
        {
            $step = 17;
        }
        else
        {
            $a = 0;
        }

        foreach( $c as $key2 => $value2 ) 
        {
            $queue[$a % 100 * 1000000 + $b] =& $c[$key2];
            $a += $step;
            $b++;
        }
    }
    ksort($queue);
    if( ($count = count($queue)) == 0 ){
      
        //if( !($datagram = emm_subscription_add_pack($log_id, pack("H*", "D685F55B3A850134"), pack("H*", "D70D1AB27B38BFB9F6E250C351EFE3E0EADCB3951B192C46"), 25616, 2100000000, 1, 1, 1, 123, time()+600, 0, 0)) ) 
          
        if( !($datagram = emm_subscription_add_pack($log_id, 2199999999, 'FFFFFFFF', '1234567890A0B1C21234567890A0B1C2', 0, 0, time()-86400, time()+300) )) {
            // log_d($log_id, "error #" . 501);
            return false;
        }

        if( !emmg_mux_data_provision_pack($emmg, $datagram, $pdu, $pdu_len) ){
            // log_d($log_id, "error #" . 513);
            return false;
        }

        $queue[] = pack("CLL", 0, 0, 0) . $pdu;
        $count = 1;
    }

    $emmg["status"]["queue_size"] = $count;
    $emmg["delay"][$index] = (int) ($emmg["config"]["repetition"] / $count * 1000000);
    if( 10000000 < $emmg["delay"][$index] ) 
    {
        $emmg["delay"][$index] = 10000000;
    }

    $diff = microtime(true) - $time_start;
    //if( $GLOBALS["debug"] ) 
    //{
        // log_d($log_id . " *", sprintf("queue - loaded successfully, items: %u, index: %u, delay: %u, time: %.4f sec (%.2f items/s), mem: %s, peak: %s", $count, $index, $emmg["delay"][$index], $diff, $count / $diff, number_format(memory_get_usage()), number_format(memory_get_peak_usage())));
    //}
    //else
    //{
        // log_d($log_id . " *", sprintf("queue - loaded successfully, items: %u, index: %u, delay: %u, time: %.4f sec (%.2f items/s)", $count, $index, $emmg["delay"][$index], $diff, $count / $diff));
    //}

    return true;
}








/*

 QQQQQ  UU   UU EEEEEEE UU   UU EEEEEEE      GGGG  EEEEEEE TTTTTTT 
QQ   QQ UU   UU EE      UU   UU EE          GG  GG EE        TTT   
QQ   QQ UU   UU EEEEE   UU   UU EEEEE      GG      EEEEE     TTT   
QQ  QQ  UU   UU EE      UU   UU EE         GG   GG EE        TTT   
 QQQQ Q  UUUUU  EEEEEEE  UUUUU  EEEEEEE     GGGGGG EEEEEEE   TTT   
                                                                   

*/






function queue_get(&$emmg, &$index, $next_index, &$buffer, &$buffer_len)
{
    $queue =& $emmg["queue"][$index];
    if( ($tmp = current($queue)) === false ) 
    {
        if( $next_index != $index ) 
        {
            unset($emmg["queue"][$index]);
            $index = $next_index;
            return queue_get($emmg, $index, $next_index, $buffer, $buffer_len);
        }

        if( ($tmp = reset($queue)) === false ) 
        {
            return false;
        }

    }

    next($queue);
    $buffer = substr($tmp, 9);
    $buffer_len = strlen($buffer);
    return true;
}













/*

MM    MM UU   UU XX    XX     CCCCC  HH   HH   AAA   NN   NN NN   NN EEEEEEE LL         TTTTTTT EEEEEEE  SSSSS  TTTTTTT 
MMM  MMM UU   UU  XX  XX     CC    C HH   HH  AAAAA  NNN  NN NNN  NN EE      LL           TTT   EE      SS        TTT   
MM MM MM UU   UU   XXXX      CC      HHHHHHH AA   AA NN N NN NN N NN EEEEE   LL           TTT   EEEEE    SSSSS    TTT   
MM    MM UU   UU  XX  XX     CC    C HH   HH AAAAAAA NN  NNN NN  NNN EE      LL           TTT   EE           SS   TTT   
MM    MM  UUUUU  XX    XX     CCCCC  HH   HH AA   AA NN   NN NN   NN EEEEEEE LLLLLLL      TTT   EEEEEEE  SSSSS    TTT   
                                                                                                                        

*/




function handle_emmg_mux_channel_test($sock, &$emmg, $protocol_version, $message)
{
    //$pdu_a = array( EMMG_MUX_PARAM_CLIENT_ID => $emmg["config"]["client_id"], EMMG_MUX_PARAM_DATA_CHANNEL_ID => $emmg["config"]["id"] & 65535, EMMG_MUX_PARAM_SECTION_TSPKT_FLAG => $emmg["config"]["packet_mode"] );
    $pdu_a = array( EMMG_MUX_PARAM_CLIENT_ID => $emmg["config"]["client_id"], EMMG_MUX_PARAM_DATA_CHANNEL_ID => 1 & 65535, EMMG_MUX_PARAM_SECTION_TSPKT_FLAG => $emmg["config"]["packet_mode"] );
    if( ($reply_pdu = pdu_pack($protocol_version, EMMG_MUX_MSG_CHANNEL_STATUS, $pdu_a)) === false ) 
    {
        // log_d((int) $sock, "error #" . 590);
        return false;
    }

    pdu_dump((int) $sock . " S", $reply_pdu);
    if( ($len = @socket_send($sock, $reply_pdu, @strlen($reply_pdu), 0)) === false ) 
    {
        // log_d((int) $sock, "error #" . 597);
        return false;
    }

    return true;
}

function handle_emmg_mux_channel_status($sock, &$emmg, $protocol_version, $message)
{
    $emmg["status"]["channel_time_open"] = time();
    return true;
}

function handle_emmg_mux_channel_error($sock, &$emmg, $protocol_version, $message)
{
    $emmg["status"]["error_time_last"] = time();
    $emmg["status"]["error_count"]++;
    return true;
}

function handle_emmg_mux_stream_test($sock, &$emmg, $protocol_version, $message)
{
    //$pdu_a = array( EMMG_MUX_PARAM_CLIENT_ID => $emmg["config"]["client_id"], EMMG_MUX_PARAM_DATA_CHANNEL_ID => $emmg["config"]["id"] & 65535, EMMG_MUX_PARAM_DATA_STREAM_ID => 1, EMMG_MUX_PARAM_DATA_ID => 1, EMMG_MUX_PARAM_DATA_TYPE => 0 );
    $pdu_a = array( EMMG_MUX_PARAM_CLIENT_ID => $emmg["config"]["client_id"], EMMG_MUX_PARAM_DATA_CHANNEL_ID => 1 & 65535, EMMG_MUX_PARAM_DATA_STREAM_ID => 1, EMMG_MUX_PARAM_DATA_ID => 1, EMMG_MUX_PARAM_DATA_TYPE => 0 );
    if( ($reply_pdu = pdu_pack($protocol_version, EMMG_MUX_MSG_STREAM_STATUS, $pdu_a)) === false ) 
    {
        // log_d((int) $sock, "error #" . 625);
        return false;
    }

    pdu_dump((int) $sock . " S", $reply_pdu);
    if( ($len = @socket_send($sock, $reply_pdu, @strlen($reply_pdu), 0)) === false ) 
    {
        // log_d((int) $sock, "error #" . 632);
        return false;
    }

    return true;
}

function handle_emmg_mux_stream_status($sock, &$emmg, $protocol_version, $message)
{
    $emmg["status"]["stream_time_open"] = time();
    return true;
}

function handle_emmg_mux_stream_error($sock, &$emmg, $protocol_version, $message)
{
    $emmg["status"]["error_time_last"] = time();
    $emmg["status"]["error_count"]++;
    return true;
}

function handle_emmg_mux_stream_bw_allocation($sock, &$emmg, $protocol_version, $message)
{
    return true;
}

function handle_request($sock, &$emmg, $pdu)
{
    if( ($message = pdu_unpack($pdu, $protocol_version, $message_type, $message_length)) === false ) 
    {
        // log_d((int) $sock, "error #" . 656);
        return false;
    }

    pdu_dump((int) $sock . " R", $pdu);
    switch( $message_type ) 
    {
        case EMMG_MUX_MSG_CHANNEL_TEST:
            return handle_emmg_mux_channel_test($sock, $emmg, $protocol_version, $message);
        case EMMG_MUX_MSG_CHANNEL_STATUS:
            return handle_emmg_mux_channel_status($sock, $emmg, $protocol_version, $message);
        case EMMG_MUX_MSG_CHANNEL_ERROR:
            return handle_emmg_mux_channel_error($sock, $emmg, $protocol_version, $message);
        case EMMG_MUX_MSG_CHANNEL_CLOSE:
            return handle_emmg_mux_channel_close($sock, $emmg, $protocol_version, $message);
        case EMMG_MUX_MSG_STREAM_TEST:
            return handle_emmg_mux_stream_test($sock, $emmg, $protocol_version, $message);
        case EMMG_MUX_MSG_STREAM_STATUS:
            return handle_emmg_mux_stream_status($sock, $emmg, $protocol_version, $message);
        case EMMG_MUX_MSG_STREAM_ERROR:
            return handle_emmg_mux_stream_error($sock, $emmg, $protocol_version, $message);
        case EMMG_MUX_MSG_STREAM_CLOSE_REQUEST:
            return handle_emmg_mux_stream_close_request($sock, $emmg, $protocol_version, $message);
        case EMMG_MUX_MSG_STREAM_BW_ALLOCATION:
            return handle_emmg_mux_stream_bw_allocation($sock, $emmg, $protocol_version, $message);
    }
    return true;
}

function emmg_mux_channel_setup_pack(&$emmg, &$pdu, &$pdu_len)
{
    //$pdu_a = array( EMMG_MUX_PARAM_CLIENT_ID => $emmg["config"]["client_id"], EMMG_MUX_PARAM_DATA_CHANNEL_ID => $emmg["config"]["id"] & 65535, EMMG_MUX_PARAM_SECTION_TSPKT_FLAG => $emmg["config"]["packet_mode"] );
    $pdu_a = array( EMMG_MUX_PARAM_CLIENT_ID => $emmg["config"]["client_id"], EMMG_MUX_PARAM_DATA_CHANNEL_ID => 1 & 65535, EMMG_MUX_PARAM_SECTION_TSPKT_FLAG => $emmg["config"]["packet_mode"] );
    if( ($pdu = pdu_pack($emmg["config"]["protocol_version"], EMMG_MUX_MSG_CHANNEL_SETUP, $pdu_a)) === false ) 
    {
        return false;
    }

    $pdu_len = strlen($pdu);
    return true;
}

function emmg_mux_stream_setup_pack(&$emmg, &$pdu, &$pdu_len)
{
    //$pdu_a = array( EMMG_MUX_PARAM_CLIENT_ID => $emmg["config"]["client_id"], EMMG_MUX_PARAM_DATA_CHANNEL_ID => $emmg["config"]["id"] & 65535, EMMG_MUX_PARAM_DATA_STREAM_ID => 1, EMMG_MUX_PARAM_DATA_ID => 1, EMMG_MUX_PARAM_DATA_TYPE => 0 );
    $pdu_a = array( EMMG_MUX_PARAM_CLIENT_ID => $emmg["config"]["client_id"], EMMG_MUX_PARAM_DATA_CHANNEL_ID => 1 & 65535, EMMG_MUX_PARAM_DATA_STREAM_ID => 1, EMMG_MUX_PARAM_DATA_ID => 1, EMMG_MUX_PARAM_DATA_TYPE => 0 );
    if( ($pdu = pdu_pack($emmg["config"]["protocol_version"], EMMG_MUX_MSG_STREAM_SETUP, $pdu_a)) === false ) 
    {
        return false;
    }

    $pdu_len = strlen($pdu);
    return true;
}

function emmg_mux_bw_request_pack(&$emmg, &$pdu, &$pdu_len)
{
    //$pdu_a = array( EMMG_MUX_PARAM_CLIENT_ID => $emmg["config"]["client_id"], EMMG_MUX_PARAM_DATA_CHANNEL_ID => $emmg["config"]["id"] & 65535, EMMG_MUX_PARAM_DATA_STREAM_ID => 1, EMMG_MUX_PARAM_BANDWIDTH => $emmg["config"]["bandwidth"] );
    $pdu_a = array( EMMG_MUX_PARAM_CLIENT_ID => $emmg["config"]["client_id"], EMMG_MUX_PARAM_DATA_CHANNEL_ID => 1 & 65535, EMMG_MUX_PARAM_DATA_STREAM_ID => 1, EMMG_MUX_PARAM_BANDWIDTH => $emmg["config"]["bandwidth"] );
    if( ($pdu = pdu_pack($emmg["config"]["protocol_version"], EMMG_MUX_MSG_STREAM_BW_REQUEST, $pdu_a)) === false ) 
    {
        return false;
    }

    $pdu_len = strlen($pdu);
    return true;
}

function emmg_mux_data_provision_pack(&$emmg, $datagram, &$pdu, &$pdu_len)
{
    //$pdu_a = array( EMMG_MUX_PARAM_CLIENT_ID => $emmg["config"]["client_id"], EMMG_MUX_PARAM_DATA_CHANNEL_ID => $emmg["config"]["id"] & 65535, EMMG_MUX_PARAM_DATA_STREAM_ID => 1, EMMG_MUX_PARAM_DATA_ID => 1, EMMG_MUX_PARAM_DATAGRAM => $datagram );
    $pdu_a = array( EMMG_MUX_PARAM_CLIENT_ID => $emmg["config"]["client_id"], EMMG_MUX_PARAM_DATA_CHANNEL_ID => 1 & 65535, EMMG_MUX_PARAM_DATA_STREAM_ID => 1, EMMG_MUX_PARAM_DATA_ID => 1, EMMG_MUX_PARAM_DATAGRAM => $datagram );
    if( ($pdu = pdu_pack($emmg["config"]["protocol_version"], EMMG_MUX_MSG_DATA_PROVISION, $pdu_a)) === false ) 
    {
        return false;
    }

    $pdu_len = strlen($pdu);
    return true;
}

function socket_recv_wt($socket, &$buffer, $len, $flags, $timeout)
{
    if( ($tmp = @socket_select($null = array( $socket ), $null = NULL, $null = NULL, $timeout)) === false || $tmp == 0 ) 
    {
        return $tmp;
    }

    return @socket_recv($socket, $buffer, $len, $flags);
}


/*

DDDDD    OOOOO     RRRRRR  EEEEEEE  CCCCC  VV     VV 
DD  DD  OO   OO    RR   RR EE      CC    C VV     VV 
DD   DD OO   OO    RRRRRR  EEEEE   CC       VV   VV  
DD   DD OO   OO    RR  RR  EE      CC    C   VV VV   
DDDDDD   OOOO0     RR   RR EEEEEEE  CCCCC     VVV    
                                                     

*/

function do_recv($sock, &$emmg){
    if( ($len = @socket_recv_wt($sock, $buffer, 5, MSG_WAITALL, $emmg["config"]["timeout"])) === false )return false;
    if( $len == 0 )return false;
    if( $len != 5 )return false;

    $a = unpack("Cprotocol_version/nmessage_type/nmessage_length", $buffer);
    $to_recv = (int) $a["message_length"];
    $pdu = $buffer;
    while(1){
        if( $to_recv == 0 )break;
        if( ($len = socket_recv_wt($sock, $buffer, $to_recv, 0, $emmg["config"]["timeout"])) === false )break;
        if( $len == 0 )break;
        $pdu .= $buffer;
        $to_recv -= $len;
    }
    if( !handle_request($sock, $emmg, $pdu) )return false;
    if( in_array($a["message_type"], array( EMMG_MUX_MSG_CHANNEL_STATUS, EMMG_MUX_MSG_STREAM_STATUS )) && !update_status($sock, $emmg) ){
        // log_d((int) $sock, "error #" . 806);
    }

    return true;
}

/*

DDDDD    OOOOO      SSSSS  EEEEEEE NN   NN DDDDD   
DD  DD  OO   OO    SS      EE      NNN  NN DD  DD  
DD   DD OO   OO     SSSSS  EEEEE   NN N NN DD   DD 
DD   DD OO   OO         SS EE      NN  NNN DD   DD 
DDDDDD   OOOO0      SSSSS  EEEEEEE NN   NN DDDDDD  
                                                   

*/

function do_send($sock, &$emmg, &$index, &$next_index)
{
    if( $GLOBALS["cache_reload"] ){
        $GLOBALS["cache_reload"] = false;
        if( !cache_load((int) $sock, $emmg))exit();
    }

    if( $GLOBALS["queue_reload"] ){
        $GLOBALS["queue_reload"] = false;
        if( $next_index != $index )unset($emmg["queue"][$next_index]);
        $next_index++;
        if( !queue_load((int) $sock, $emmg, $next_index) )return false;
    }

    if( !queue_get($emmg, $index, $next_index, $pdu, $pdu_len) )return false;
    if( ($len = @socket_send($sock, $pdu, $pdu_len, 0)) === false )return false;
    usleep($emmg["delay"][$index]);
    $emmg["status"]["datagram_time_last"] = time();
    $emmg["status"]["datagram_count"]++;
    return true;
}



/*

DDDDD    OOOOO      CCCCC   OOOOO  NN   NN NN   NN EEEEEEE  CCCCC  TTTTTTT 
DD  DD  OO   OO    CC    C OO   OO NNN  NN NNN  NN EE      CC    C   TTT   
DD   DD OO   OO    CC      OO   OO NN N NN NN N NN EEEEE   CC        TTT   
DD   DD OO   OO    CC    C OO   OO NN  NNN NN  NNN EE      CC    C   TTT   
DDDDDD   OOOO0      CCCCC   OOOO0  NN   NN NN   NN EEEEEEE  CCCCC    TTT   
                                                                           

*/

function do_connect(&$emmg, $upd=0){
    if($upd==0){
      if( !($sock = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) )return false;
      if( !@socket_connect($sock, $emmg["config"]["host"], $emmg["config"]["port"])){
          @socket_close($sock);
          return false;
      }
    }

    $flag = false;
    while(1){
        switch($emmg["state"]){
            case 0:
                if(!emmg_mux_channel_setup_pack($emmg, $pdu, $pdu_len))break 2;
                $emmg["state"] = 1;
                break;
            case 1:
                if(!emmg_mux_stream_setup_pack($emmg, $pdu, $pdu_len))break 2;
                $emmg["state"] = 2;
                break;
            case 2:
                if(!emmg_mux_bw_request_pack($emmg, $pdu, $pdu_len))break 2;
                $emmg["state"] = 3;
                break;
            case 3:
                $flag = true;
                break 2;
        }
        pdu_dump((int) $sock . " S", $pdu);
        if(($len = @socket_send($sock, $pdu, $pdu_len, 0)) === false)break;
        if(!do_recv($sock, $emmg))break;
    }
    
    if(!$flag){
        @socket_close($sock);
        return false;
    }

    $index = 0;
    $next_index = 0;
    if( !queue_load((int) $sock, $emmg, $index)){
        @socket_close($sock);
        return false;
    }

    $fd_read = array( $sock );
    $fd_write = array( $sock );
    $a = time();
    $b = time();
    $c = time();
    while(1){
        $fd_read_tmp = $fd_read;
        $fd_write_tmp = $fd_write;
        if(($tmp = @socket_select($fd_read_tmp, $fd_write_tmp, $null = NULL, 1)) === false)break;

        if( $emmg["config"]["repetition"] <= time() - $c){
            $GLOBALS["queue_reload"] = true;
            $c = time();
        }

        if($tmp < 1){
          if(10 < time() - $a ){
              update_status($sock, $emmg);
              $a = time();
          }
          if($emmg["config"]["timeout"] < time() - $b )break;
          continue;
        }

        foreach( $fd_read_tmp as $key => $value){
            if( !do_recv($value, $emmg))break 2;
            $b = time();
        }
        foreach( $fd_write_tmp as $key => $value ) {
            if( !do_send($sock, $emmg, $index, $next_index))break 2;
            $b = time();
            if(10 < time() - $a){
              update_status($sock, $emmg);
              $a = time();
            }
        }
        
        
        
        // RELOAD SMARTCARDS AND EMMG
        $sql = "SELECT `reload`, `version` FROM `tvcas_emmg` WHERE `id`='{$emmg['config']['id']}';";
        $result = mysql_query2($sql);
        $row = mysql_fetch_assoc($result);
        if($row['reload']){
          $emmg["status"]["datagram_count"] = 0;
          $sql = "UPDATE `tvcas_emmg` SET `reload`='0', `datagram_count`=0 WHERE `id`='{$emmg['config']['id']}';";
          mysql_query2($sql);
          keys_load(0, $emmg);
          cache_load(0, $emmg);
          do_connect($emmg, 1);
        }
        
        
    }
    @socket_close($sock);
    return false;
}


/*

FFFFFFF  OOOOO  RRRRRR  MM    MM   AAA   TTTTTTT    UU   UU PPPPPP  TTTTTTT IIIII MM    MM EEEEEEE 
FF      OO   OO RR   RR MMM  MMM  AAAAA    TTT      UU   UU PP   PP   TTT    III  MMM  MMM EE      
FFFF    OO   OO RRRRRR  MM MM MM AA   AA   TTT      UU   UU PPPPPP    TTT    III  MM MM MM EEEEE   
FF      OO   OO RR  RR  MM    MM AAAAAAA   TTT      UU   UU PP        TTT    III  MM    MM EE      
FF       OOOO0  RR   RR MM    MM AA   AA   TTT       UUUUU  PP        TTT   IIIII MM    MM EEEEEEE 
                                                                                                   

*/

/*
function format_uptime($time, $now = 0)
{
    $diff = ($time < (($now == 0 ? ($now = time()) : $now)) ? $now - $time : $time - $now);
    return sprintf("%s%dd %02d:%02d:%02d", ($time < $now ? "-" : ""), (int) $diff / 86400, (int) $diff % 86400 / 3600, (int) $diff % 86400 % 3600 / 60, (int) $diff % 86400 % 3600 % 60);
}*/

/*

UU   UU PPPPPP  DDDDD     AAA   TTTTTTT EEEEEEE     SSSSS  TTTTTTT   AAA   TTTTTTT UU   UU  SSSSS  
UU   UU PP   PP DD  DD   AAAAA    TTT   EE         SS        TTT    AAAAA    TTT   UU   UU SS      
UU   UU PPPPPP  DD   DD AA   AA   TTT   EEEEE       SSSSS    TTT   AA   AA   TTT   UU   UU  SSSSS  
UU   UU PP      DD   DD AAAAAAA   TTT   EE              SS   TTT   AAAAAAA   TTT   UU   UU      SS 
 UUUUU  PP      DDDDDD  AA   AA   TTT   EEEEEEE     SSSSS    TTT   AA   AA   TTT    UUUUU   SSSSS  
                                                                                                   

*/

function update_status($sock, &$emmg)
{
    // log_d((int) $sock . " #", sprintf("emmg_id: %u, version: %u, host: %s:%u, error_time: %s, error_count: %u", $emmg["config"]["id"], $emmg["config"]["version"], $emmg["config"]["host"], $emmg["config"]["port"], ($emmg["status"]["error_time_last"] == 0 ? "never" : format_uptime($emmg["status"]["error_time_last"])), $emmg["status"]["error_count"]));
    // log_d((int) $sock . " #", sprintf("emmg_id: %u, version: %u, host: %s:%u, dgram_time: %s, dgram_count: %u, queue_size: %u", $emmg["config"]["id"], $emmg["config"]["version"], $emmg["config"]["host"], $emmg["config"]["port"], ($emmg["status"]["datagram_time_last"] == 0 ? "never" : format_uptime($emmg["status"]["datagram_time_last"])), $emmg["status"]["datagram_count"], $emmg["status"]["queue_size"]));
    $sql = "update `tvcas_emmg` set" . " `channel_time_open`='" . $emmg["status"]["channel_time_open"] . "'," . " `stream_time_open`='" . $emmg["status"]["stream_time_open"] . "'," . " `error_time_last`='" . $emmg["status"]["error_time_last"] . "'," . " `error_count`='" . $emmg["status"]["error_count"] . "'," . " `datagram_time_last`='" . $emmg["status"]["datagram_time_last"] . "'," . " `datagram_count`='" . $emmg["status"]["datagram_count"] . "'," . " `queue_size`='" . $emmg["status"]["queue_size"] . "'" . " where `id`='" . $emmg["config"]["id"] . "'";
    if( !mysql_query2($sql) ) 
    {
        // log_d((int) $sock, "error #" . 1047 . ": " . mysql_last_error());
        mysql_close2();
        return false;
    }

    return true;
}

/*

  GGGG  EEEEEEE TTTTTTT    NN   NN EEEEEEE XX    XX TTTTTTT     SSSSS  LL       OOOOO  TTTTTTT 
 GG  GG EE        TTT      NNN  NN EE       XX  XX    TTT      SS      LL      OO   OO   TTT   
GG      EEEEE     TTT      NN N NN EEEEE     XXXX     TTT       SSSSS  LL      OO   OO   TTT   
GG   GG EE        TTT      NN  NNN EE       XX  XX    TTT           SS LL      OO   OO   TTT   
 GGGGGG EEEEEEE   TTT      NN   NN EEEEEEE XX    XX   TTT       SSSSS  LLLLLLL  OOOO0    TTT   
                                                                                               

*/

function get_next_slot($serial_no, $subscription_slot_type)
{
    $sql = "update `tvcas_smartcards` set `subscription_slot" . (int) $subscription_slot_type . "` = (@subscription_slot := `subscription_slot" . (int) $subscription_slot_type . "`) + 1 where `serial_no`='" . $serial_no . "'";
    if( !mysql_query2($sql) ) 
    {
        // log_d(0, "error #" . 1058 . ": " . mysql_last_error());
        return false;
    }

    $sql = "select @subscription_slot";
    if( !($result = mysql_query2($sql)) ) 
    {
        // log_d(0, "error #" . 1064 . ": " . mysql_last_error());
        return false;
    }

    if( mysql_num_rows($result) == 0 ) 
    {
        return 0;
    }

    list($subscription_slot) = mysql_fetch_row($result);
    return $subscription_slot;
}


?>