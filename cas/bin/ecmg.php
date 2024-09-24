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

















/*

RRRRRR  UU   UU NN   NN      GGGG  UU   UU   AAA   RRRRRR  DDDDD   
RR   RR UU   UU NNN  NN     GG  GG UU   UU  AAAAA  RR   RR DD  DD  
RRRRRR  UU   UU NN N NN    GG      UU   UU AA   AA RRRRRR  DD   DD 
RR  RR  UU   UU NN  NNN    GG   GG UU   UU AAAAAAA RR  RR  DD   DD 
RR   RR  UUUUU  NN   NN     GGGGGG  UUUUU  AA   AA RR   RR DDDDDD  
                                                                   

*/


openlog("ecmg_{$generator_id}", LOG_ODELAY | LOG_PID, LOG_LOCAL0);
posix_setsid();


// первый запуск скрипта [1]
if(!getenv("tvcas_ecmg_guard")){
    
    // записываем в файл pid материнского процесса
    file_put_contents("/var/run/ecmg_{$generator_id}.pid", @posix_getpid());
    
    // условность, что материнский процесс запущен)))
    putenv("tvcas_ecmg_guard=42");

    
    while(true){
      if(pcntl_fork() == 0 ){
         posix_setsid();
         pcntl_exec(__FILE__, $_SERVER["argv"]); // запускаем дочерный процесс
      }
        
      while(true){
        if(pcntl_wait($status, WNOHANG) != 0){
          break;
        }
        sleep(1);
      }
      // log_d(0, "main process exited, will restart in 10 sec. (status=" . $status . ")");
      sleep(10);
    }
    
}











mysql_query2("UPDATE `tvcas_ecmg` SET `touch_version`='0', `touch_time`='0' WHERE `id`='{$generator_id};'");
//mysql_query2("DELETE FROM `tvcas_ecmg_status` WHERE `ecmg_id`='{$generator_id}';");

$result = mysql_query2("SELECT `id`, `network_id`, inet_ntoa(`host`) AS `host`, `port`, `key`, `packet_mode`, `timeout`, `ac_delay_start`, `ac_delay_stop`, `delay_start`, `delay_stop`, `transition_delay_start`, `transition_delay_stop`, `repetition_period`, `min_cp_duration`, `max_compute_time`, `ecm_time1`, `ecm_time2`, `ecm_block_time`, `version` FROM `tvcas_ecmg` WHERE `status`='1' AND `id`='{$generator_id}';");  



$ecmg = array('config' => mysql_fetch_assoc($result), 'services' => array(), 'packages' => array());



load_ac($ecmg);

mysql_close2();




// первый цикл будет pid=18242, второй = 0
// дочерний процесс перезапуска
if(pcntl_fork() > 0){
  
  while(true){
      mysql_query2("UPDATE `tvcas_ecmg` SET `touch_version`='{$ecmg["config"]["version"]}', `touch_time`='" . time() . "' WHERE `id`='{$generator_id}';");

      $row = mysql_fetch_assoc(mysql_query2("SELECT `reload`, `version` FROM `tvcas_ecmg` WHERE `id`='{$generator_id}';"));
      if($row["version"] != $ecmg["config"]["version"]) break;
      if($row["reload"] == 1){
          mysql_query2("UPDATE `tvcas_ecmg` SET `reload`='0' WHERE `id`='{$generator_id}';");
          posix_kill(0, SIGHUP);
      }
      sleep(10);
  }  
  
}else{
  
  do_listen_loop($ecmg);
  
}

posix_kill(0, SIGTERM);
exit();
















/*

LL       OOOOO    AAA   DDDDD        AAA    CCCCC  
LL      OO   OO  AAAAA  DD  DD      AAAAA  CC    C 
LL      OO   OO AA   AA DD   DD    AA   AA CC      
LL      OO   OO AAAAAAA DD   DD    AAAAAAA CC    C 
LLLLLLL  OOOO0  AA   AA DDDDDD     AA   AA  CCCCC  
                                                   

*/







function load_ac(&$ecmg){
  
    $ecmg["services"] = array();
    $ecmg["packages"] = array();
    return true;

}






/*


 CCCCC  HH   HH   AAA   NN   NN NN   NN EEEEEEE LL          SSSSS  EEEEEEE TTTTTTT UU   UU PPPPPP  
CC    C HH   HH  AAAAA  NNN  NN NNN  NN EE      LL         SS      EE        TTT   UU   UU PP   PP 
CC      HHHHHHH AA   AA NN N NN NN N NN EEEEE   LL          SSSSS  EEEEE     TTT   UU   UU PPPPPP  
CC    C HH   HH AAAAAAA NN  NNN NN  NNN EE      LL              SS EE        TTT   UU   UU PP      
 CCCCC  HH   HH AA   AA NN   NN NN   NN EEEEEEE LLLLLLL     SSSSS  EEEEEEE   TTT    UUUUU  PP      
                                                                                                   

*/





function handle_ecmg_scs_channel_setup($sock, &$ecmg, $protocol_version, $message, $is_test = false)
{
    if( !isset($message["ecm_channel_id"])){
        // log_d((int) $sock, "error #" . 93);
        return false;
    }

    if( !$is_test && !isset($message["super_cas_id"])){
        // log_d((int) $sock, "error #" . 101);
        return false;
    }

    $reply = array( ECMG_SCS_PARAM_ECM_CHANNEL_ID => $message["ecm_channel_id"]["value"], ECMG_SCS_PARAM_SECTION_TSPKT_FLAG => $ecmg["config"]["packet_mode"], ECMG_SCS_PARAM_DELAY_START => $ecmg["config"]["delay_start"], ECMG_SCS_PARAM_DELAY_STOP => $ecmg["config"]["delay_stop"], ECMG_SCS_PARAM_TRANSITION_DELAY_START => $ecmg["config"]["transition_delay_start"], ECMG_SCS_PARAM_TRANSITION_DELAY_STOP => $ecmg["config"]["transition_delay_stop"], ECMG_SCS_PARAM_ECM_REP_PERIOD => $ecmg["config"]["repetition_period"], ECMG_SCS_PARAM_MAX_STREAMS => 256, ECMG_SCS_PARAM_MIN_CP_DURATION => $ecmg["config"]["min_cp_duration"], ECMG_SCS_PARAM_LEAD_CW => 1, ECMG_SCS_PARAM_CW_PER_MSG => 2, ECMG_SCS_PARAM_MAX_COMP_TIME => $ecmg["config"]["max_compute_time"] );
    
    if( $ecmg["config"]["ac_delay_start"] != 0){
        $reply[ECMG_SCS_PARAM_AC_DELAY_START] = $ecmg["config"]["ac_delay_start"];
    }

    if( $ecmg["config"]["ac_delay_stop"] != 0){
        $reply[ECMG_SCS_PARAM_AC_DELAY_STOP] = $ecmg["config"]["ac_delay_stop"];
    }

    if( ($reply_pdu = pdu_pack($protocol_version, ECMG_SCS_MSG_CHANNEL_STATUS, $reply)) === false){
        // log_d((int) $sock, "error #" . 129);
        return false;
    }

    pdu_dump((int) $sock . " S", $reply_pdu);
    if( ($len = @socket_send($sock, $reply_pdu, @strlen($reply_pdu), 0)) === false ) 
    {
        // log_d((int) $sock, "error #" . 136);
        return false;
    }

    if(!$is_test){
        $ecmg["status"]["channel_time_open"] = time();
        $ecmg["status"]["channel_protocol_version"] = $protocol_version;
        $ecmg["status"]["channel_ecm_channel_id"] = $message["ecm_channel_id"]["value"];
        $ecmg["status"]["channel_super_cas_id"] = $message["super_cas_id"]["value"];
    }

    return true;
}

function handle_ecmg_scs_channel_test($sock, &$ecmg, $protocol_version, $message)
{
    return handle_ecmg_scs_channel_setup($sock, $ecmg, $protocol_version, $message, true);
}

function handle_ecmg_scs_channel_close($sock, &$ecmg, $protocol_version, $message)
{
    $ecmg["status"]["channel_time_close"] = time();
    return true;
}










/*


 SSSSS  TTTTTTT RRRRRR  EEEEEEE   AAA   MM    MM     SSSSS  EEEEEEE TTTTTTT UU   UU PPPPPP  
SS        TTT   RR   RR EE       AAAAA  MMM  MMM    SS      EE        TTT   UU   UU PP   PP 
 SSSSS    TTT   RRRRRR  EEEEE   AA   AA MM MM MM     SSSSS  EEEEE     TTT   UU   UU PPPPPP  
     SS   TTT   RR  RR  EE      AAAAAAA MM    MM         SS EE        TTT   UU   UU PP      
 SSSSS    TTT   RR   RR EEEEEEE AA   AA MM    MM     SSSSS  EEEEEEE   TTT    UUUUU  PP      
                                                                                            

*/


function handle_ecmg_scs_stream_setup($sock, &$ecmg, $protocol_version, $message, $is_test = false)
{
    if( !isset($message["ecm_channel_id"]) || !isset($message["ecm_stream_id"]) ) 
    {
        // log_d((int) $sock, "error #" . 164);
        return false;
    }

    $reply = array( ECMG_SCS_PARAM_ECM_CHANNEL_ID => $message["ecm_channel_id"]["value"], ECMG_SCS_PARAM_ECM_STREAM_ID => $message["ecm_stream_id"]["value"] );
    if( isset($message["ecm_id"]) ) 
    {
        $reply[ECMG_SCS_PARAM_ECM_ID] = $message["ecm_id"]["value"];
    }

    $reply[ECMG_SCS_PARAM_ACCESS_CRITERIA_TRANSFER_MODE] = 1;
    if( ($reply_pdu = pdu_pack($protocol_version, ECMG_SCS_MSG_STREAM_STATUS, $reply)) === false ) 
    {
        // log_d((int) $sock, "error #" . 180);
        return false;
    }

    pdu_dump((int) $sock . " S", $reply_pdu);
    if( ($len = @socket_send($sock, $reply_pdu, @strlen($reply_pdu), 0)) === false ) 
    {
        // log_d((int) $sock, "error #" . 187);
        return false;
    }

    if( !$is_test ) 
    {
        if( $ecmg["status"]["stream_count"] == 0 ) 
        {
            $ecmg["status"]["stream_time_open"] = time();
        }

        $ecmg["status"]["stream_count"]++;
    }

    return true;
}

function handle_ecmg_scs_stream_test($sock, &$ecmg, $protocol_version, $message)
{
    return handle_ecmg_scs_stream_setup($sock, $ecmg, $protocol_version, $message, true);
}

function handle_ecmg_scs_stream_close_request($sock, &$ecmg, $protocol_version, $message)
{
    if( !isset($message["ecm_channel_id"]) || !isset($message["ecm_stream_id"]) ) 
    {
        // log_d((int) $sock, "error #" . 210);
        return false;
    }

    $ecmg["status"]["stream_count"]--;
    if( $ecmg["status"]["stream_count"] == 0 ) 
    {
        $ecmg["status"]["stream_time_close"] = time();
    }

    return true;
}














/*

 CCCCC  WW      WW    PPPPPP  RRRRRR   OOOOO  VV     VV IIIII  SSSSS  IIIII  OOOOO  NN   NN 
CC    C WW      WW    PP   PP RR   RR OO   OO VV     VV  III  SS       III  OO   OO NNN  NN 
CC      WW   W  WW    PPPPPP  RRRRRR  OO   OO  VV   VV   III   SSSSS   III  OO   OO NN N NN 
CC    C  WW WWW WW    PP      RR  RR  OO   OO   VV VV    III       SS  III  OO   OO NN  NNN 
 CCCCC    WW   WW     PP      RR   RR  OOOO0     VVV    IIIII  SSSSS  IIIII  OOOO0  NN   NN 
                                                                                            

*/


function handle_ecmg_scs_cw_provision($sock, &$ecmg, $protocol_version, $message){



    if( !isset($message["cp_cw_combination"]) || count($message["cp_cw_combination"]["value"]) != 2 ) 
    {
        // log_d((int) $sock, "error #" . 265);
        return false;
    }

    list($access_criteria) = @array_values(@unpack("N", $message["access_criteria"]["value"]));
    $packages = true;
    //if( (int) ($access_criteria & 2147483648) == (int) 2147483648 ){
    //    $access_criteria = $access_criteria & 2147483647;
    //    if( isset($ecmg["packages"][$access_criteria]) ) 
    //    {
    //        $packages = array( $access_criteria => $ecmg["packages"][$access_criteria] );
    //    }

    //} else {
      
        //$access_criteria = $access_criteria & 2147483647;
        if(isset($ecmg["services"][$access_criteria])){
            $packages = $ecmg["services"][$access_criteria];
        }

    //}
    

    

    $reply = array( ECMG_SCS_PARAM_ECM_CHANNEL_ID => $message["ecm_channel_id"]["value"], ECMG_SCS_PARAM_ECM_STREAM_ID => $message["ecm_stream_id"]["value"], ECMG_SCS_PARAM_CP_NUMBER => $message["cp_number"]["value"] );
    
    if( $packages !== false ){
        if($message["cp_number"]["value"] % 2){
            $_kk = substr($ecmg["config"]['key'], 32, 32);
            $table = 129; // 0x81
            $cw1 = numbFormat(bin2hex($message["cp_cw_combination"]["value"][0]["cw"]), 16);
            $cw2 = numbFormat(bin2hex($message["cp_cw_combination"]["value"][1]["cw"]), 16);
        }else{
            $_kk = substr($ecmg["config"]['key'], 0, 32);
            $table = 128; // 0x80
            $cw1 = numbFormat(bin2hex($message["cp_cw_combination"]["value"][1]["cw"]), 16);
            $cw2 = numbFormat(bin2hex($message["cp_cw_combination"]["value"][0]["cw"]), 16);
        }

        $ac = sprintf("%08X", $access_criteria);
        
        if(date("i")=='00'){ // каждый час в 00 минут очистка логов ecmg
          mysql_query2("TRUNCATE TABLE `tvcas_ecmg_log`;");
        }else{
          mysql_query2("INSERT INTO `tvcas_ecmg_log` (`access_criteria`, `cw1`, `cw2`, `sock`, `ecmg_id`, `time`) VALUES ('{$ac}', '" . strtoupper($cw1) . "', '" . strtoupper($cw2) . "', '" . (int)$sock . "', {$ecmg["config"]["id"]}, " . time() . ");");
        }
        
        
        //if(true){
        //    // log_d((int) $sock . " *", "access_criteria: {$ac}, cw1: {$cw1}, cw2: {$cw2}");
        //}

        //if( !($ecm_datagram = ecm_cw_pack((int) $sock, pack("H*", "6935bce2c9a65009"), pack("H*", "d066e67135e906b31fc576f72c97787b676e9df71c652086"), 25633, $table, time(), $cw1, $cw2, $packages, $message["ecm_stream_id"]["value"] << 16 | $message["cp_number"]["value"], $ecmg["config"]["ecm_time1"], $ecmg["config"]["ecm_time2"], $ecmg["config"]["ecm_block_time"], false))){
        
        if( !($ecm_datagram = ecm_cw_pack($table, time(), $cw1, $cw2, $_kk, $ac))){
            // log_d((int) $sock, "error #" . 326 . ": ecm_cw_pack()");
        }else{
            $reply[ECMG_SCS_PARAM_ECM_DATAGRAM] = $ecm_datagram;
        }

    }else{
        // log_d((int) $sock, "error #" . 331 . ": unknown access_criteria");
    }

    if( ($reply_pdu = pdu_pack($protocol_version, ECMG_SCS_MSG_ECM_RESPONSE, $reply)) === false ) 
    {
        // log_d((int) $sock, "error #" . 335);
        return false;
    }

    //if($GLOBALS["debug"]){
    //    pdu_dump((int) $sock . " S", $reply_pdu);
    //}

    if(($len = @socket_send($sock, $reply_pdu, @strlen($reply_pdu), 0)) === false){
        // log_d((int) $sock, "error #" . 344);
        return false;
    }

    $ecmg["status"]["cw_time_last"] = time();
    $ecmg["status"]["cw_count"]++;
    if( $packages !== false){
        $ecmg["status"]["ecm_time_last"] = time();
        $ecmg["status"]["ecm_count"]++;
    }

    return true;
}







/*


HH   HH   AAA   NN   NN DDDDD   LL      EEEEEEE    RRRRRR  EEEEEEE  QQQQQ  UU   UU EEEEEEE  SSSSS  TTTTTTT 
HH   HH  AAAAA  NNN  NN DD  DD  LL      EE         RR   RR EE      QQ   QQ UU   UU EE      SS        TTT   
HHHHHHH AA   AA NN N NN DD   DD LL      EEEEE      RRRRRR  EEEEE   QQ   QQ UU   UU EEEEE    SSSSS    TTT   
HH   HH AAAAAAA NN  NNN DD   DD LL      EE         RR  RR  EE      QQ  QQ  UU   UU EE           SS   TTT   
HH   HH AA   AA NN   NN DDDDDD  LLLLLLL EEEEEEE    RR   RR EEEEEEE  QQQQ Q  UUUUU  EEEEEEE  SSSSS    TTT   
                                                                                                           

*/




function handle_request($sock, &$ecmg, $pdu){
    if( ($message = pdu_unpack($pdu, $protocol_version, $message_type, $message_length)) === false ) 
    {
        // log_d((int) $sock, "error #" . 361);
        return false;
    }

    /*if( $GLOBALS["debug"] || $message_type != ECMG_SCS_MSG_CW_PROVISION ) 
    {
        pdu_dump((int) $sock . " R", $pdu);
    }*/

    $ecmg["status"]["a_time"] = time();
    switch( $message_type ) 
    {
        case ECMG_SCS_MSG_CHANNEL_SETUP:
            return handle_ecmg_scs_channel_setup($sock, $ecmg, $protocol_version, $message);
        case ECMG_SCS_MSG_CHANNEL_TEST:
            return handle_ecmg_scs_channel_test($sock, $ecmg, $protocol_version, $message);
        case ECMG_SCS_MSG_CHANNEL_CLOSE:
            return handle_ecmg_scs_channel_close($sock, $ecmg, $protocol_version, $message);
        case ECMG_SCS_MSG_STREAM_SETUP:
            return handle_ecmg_scs_stream_setup($sock, $ecmg, $protocol_version, $message);
        case ECMG_SCS_MSG_STREAM_TEST:
            return handle_ecmg_scs_stream_test($sock, $ecmg, $protocol_version, $message);
        case ECMG_SCS_MSG_STREAM_CLOSE_REQUEST:
            return handle_ecmg_scs_stream_close_request($sock, $ecmg, $protocol_version, $message);
        case ECMG_SCS_MSG_CW_PROVISION:
            return handle_ecmg_scs_cw_provision($sock, $ecmg, $protocol_version, $message);
    }
    return true;
}

function send_ecmg_scs_channel_test($sock, $protocol_version, $ecm_channel_id){
    $message = array( ECMG_SCS_PARAM_ECM_CHANNEL_ID => $ecm_channel_id );
    if( ($message_pdu = pdu_pack($protocol_version, ECMG_SCS_MSG_CHANNEL_TEST, $message)) === false ) 
    {
        // log_d((int) $sock, "error #" . 400);
        return false;
    }

    pdu_dump((int) $sock . " S", $message_pdu);
    if( ($len = @socket_send($sock, $message_pdu, @strlen($message_pdu), 0)) === false ) 
    {
        // log_d((int) $sock, "error #" . 407);
        return false;
    }

    return true;
}

function socket_recv_wt($socket, &$buffer, $len, $flags, $timeout){
    if( ($tmp = @socket_select($null = array( $socket ), $null = NULL, $null = NULL, $timeout)) === false || $tmp == 0 ) 
    {
        return $tmp;
    }

    return @socket_recv($socket, $buffer, $len, $flags);
}




/*

UU   UU PPPPPP  DDDDD     AAA   TTTTTTT EEEEEEE     SSSSS  TTTTTTT   AAA   TTTTTTT UU   UU  SSSSS  
UU   UU PP   PP DD  DD   AAAAA    TTT   EE         SS        TTT    AAAAA    TTT   UU   UU SS      
UU   UU PPPPPP  DD   DD AA   AA   TTT   EEEEE       SSSSS    TTT   AA   AA   TTT   UU   UU  SSSSS  
UU   UU PP      DD   DD AAAAAAA   TTT   EE              SS   TTT   AAAAAAA   TTT   UU   UU      SS 
 UUUUU  PP      DDDDDD  AA   AA   TTT   EEEEEEE     SSSSS    TTT   AA   AA   TTT    UUUUU   SSSSS  
                                                                                                   

                                                                                

*/


function update_status($sock, &$ecmg)
{
  
    list($peers) = mysql_fetch_row(mysql_query2("SELECT `peers` FROM `tvcas_ecmg` WHERE `id`={$ecmg["config"]["id"]};"));
    $peers = @unserialize($peers);
    if(is_array($peers)){
      foreach($peers as $k=>$v){
        if($v['time'] < time()-15) unset($peers[$k]);
      }
    }else{
      $peers = array();
    }
    $peers["{$ecmg["status"]["peer_host"]}:{$ecmg["status"]["peer_port"]}"] = array('time'=>time(), 'cw_count'=>$ecmg["status"]["cw_count"], 'ecm_count'=>$ecmg["status"]["ecm_count"], 'sock' => (int)$sock);
    mysql_query2("UPDATE `tvcas_ecmg` SET `peers`='" . serialize($peers) . "' WHERE `id`={$ecmg["config"]["id"]};");
    
    //// log_d((int) $sock . " #", sprintf("ecmg_id: %u, version: %u, peer: %s:%u, stream_count: %u, cw_count: %u, ecm_count: %u", $ecmg["config"]["id"], $ecmg["config"]["version"], $ecmg["status"]["peer_host"], $ecmg["status"]["peer_port"], $ecmg["status"]["stream_count"], $ecmg["status"]["cw_count"], $ecmg["status"]["ecm_count"]));
    
    //mysql_query2("insert into `tvcas_ecmg_status` set" . " `ecmg_id`='" . $ecmg["config"]["id"] . "'," . " `version`='" . $ecmg["config"]["version"] . "'," . " `peer_host`=inet_aton('" . $ecmg["status"]["peer_host"] . "')," . " `peer_port`='" . $ecmg["status"]["peer_port"] . "'," . " `peer_time_open`='" . $ecmg["status"]["peer_time_open"] . "'," . " `peer_time_close`='" . $ecmg["status"]["peer_time_close"] . "'," . " `channel_time_open`='" . $ecmg["status"]["channel_time_open"] . "'," . " `channel_time_close`='" . $ecmg["status"]["channel_time_close"] . "'," . " `channel_super_cas_id`='" . $ecmg["status"]["channel_super_cas_id"] . "'," . " `stream_time_open`='" . $ecmg["status"]["stream_time_open"] . "'," . " `stream_time_close`='" . $ecmg["status"]["stream_time_close"] . "'," . " `stream_count`='" . $ecmg["status"]["stream_count"] . "'," . " `cw_time_last`='" . $ecmg["status"]["cw_time_last"] . "'," . " `cw_count`='" . $ecmg["status"]["cw_count"] . "'," . " `ecm_time_last`='" . $ecmg["status"]["ecm_time_last"] . "'," . " `ecm_count`='" . $ecmg["status"]["ecm_count"] . "'," . " `a_time`='" . $ecmg["status"]["a_time"] . "'" . " on duplicate key update" . " `peer_time_open`='" . $ecmg["status"]["peer_time_open"] . "'," . " `peer_time_close`='" . $ecmg["status"]["peer_time_close"] . "'," . " `channel_time_open`='" . $ecmg["status"]["channel_time_open"] . "'," . " `channel_time_close`='" . $ecmg["status"]["channel_time_close"] . "'," . " `channel_super_cas_id`='" . $ecmg["status"]["channel_super_cas_id"] . "'," . " `stream_time_open`='" . $ecmg["status"]["stream_time_open"] . "'," . " `stream_time_close`='" . $ecmg["status"]["stream_time_close"] . "'," . " `stream_count`='" . $ecmg["status"]["stream_count"] . "'," . " `cw_time_last`='" . $ecmg["status"]["cw_time_last"] . "'," . " `cw_count`='" . $ecmg["status"]["cw_count"] . "'," . " `ecm_time_last`='" . $ecmg["status"]["ecm_time_last"] . "'," . " `ecm_count`='" . $ecmg["status"]["ecm_count"] . "'," . " `a_time`='" . $ecmg["status"]["a_time"] . "'");

    return true;
}














/*

RRRRRR  EEEEEEE  CCCCC  VV     VV    LL       OOOOO   OOOOO  PPPPPP  
RR   RR EE      CC    C VV     VV    LL      OO   OO OO   OO PP   PP 
RRRRRR  EEEEE   CC       VV   VV     LL      OO   OO OO   OO PPPPPP  
RR  RR  EE      CC    C   VV VV      LL      OO   OO OO   OO PP      
RR   RR EEEEEEE  CCCCC     VVV       LLLLLLL  OOOO0   OOOO0  PP      
                                                                     

*/


function do_recv_loop($sock, $peer_host, $peer_port, &$ecmg)
{
    $ecmg["status"] = array( "peer_host" => $peer_host, "peer_port" => $peer_port, "peer_time_open" => time(), "peer_time_close" => 0, "channel_time_open" => 0, "channel_time_close" => 0, "channel_protocol_version" => 0, "channel_ecm_channel_id" => 0, "channel_super_cas_id" => 0, "stream_time_open" => 0, "stream_time_close" => 0, "stream_count" => 0, "cw_time_last" => 0, "cw_count" => 0, "ecm_time_last" => 0, "ecm_count" => 0, "a_time" => 0 );
    $now = 0;
    $channel_test_sent = false;
    while( true ) 
    {
        if( ($len = socket_recv_wt($sock, $buffer, 5, MSG_WAITALL, $ecmg["config"]["timeout"])) === false ) 
        {
            if( socket_last_error() == 4 ) 
            {
                continue;
            }

            // log_d((int) $sock, "error #" . 509);
            break;
        }

        if( $len == 0 ) 
        {
            if( !$channel_test_sent && 0 < $ecmg["status"]["channel_time_open"] && $ecmg["status"]["channel_time_close"] == 0 ) 
            {
                $channel_test_sent = true;
                if( !send_ecmg_scs_channel_test($sock, $ecmg["status"]["channel_protocol_version"], $ecmg["status"]["channel_ecm_channel_id"]) ) 
                {
                    // log_d((int) $sock, "error #" . 522);
                    break;
                }

                continue;
            }

            // log_d((int) $sock, "connection closed (timeout)");
            break;
        }

        $channel_test_sent = false;
        if( $len != 5 ) 
        {
            // log_d((int) $sock, "error #" . 537);
            break;
        }

        $a = unpack("Cprotocol_version/nmessage_type/nmessage_length", $buffer);
        $to_recv = (int) $a["message_length"];
        $pdu = $buffer;
        while( true ) 
        {
            if( $to_recv == 0 ) 
            {
                break;
            }

            if( ($len = socket_recv_wt($sock, $buffer, $to_recv, 0, $ecmg["config"]["timeout"])) === false ) 
            {
                // log_d((int) $sock, "error #" . 552);
                break 2;
            }

            if( $len == 0 ) 
            {
                // log_d((int) $sock, "connection closed (timeout)");
                break 2;
            }

            $pdu .= $buffer;
            $to_recv -= $len;
        }
        if( !handle_request($sock, $ecmg, $pdu) ) 
        {
            // log_d((int) $sock, "error #" . 566);
            break;
        }

        if( in_array($a["message_type"], array( ECMG_SCS_MSG_CHANNEL_SETUP, ECMG_SCS_MSG_CHANNEL_CLOSE, ECMG_SCS_MSG_STREAM_SETUP, ECMG_SCS_MSG_STREAM_CLOSE_REQUEST )) || 10 < time() - $now ) 
        {
            if( !update_status($sock, $ecmg) ) 
            {
                // log_d((int) $sock, "error #" . 582);
            }

            $now = time();
        }

    }
    $ecmg["status"]["channel_time_close"] = time();
    $ecmg["status"]["stream_time_close"] = time();
    $ecmg["status"]["stream_count"] = 0;
    if( !update_status($sock, $ecmg) ) 
    {
        // log_d((int) $sock, "error #" . 593);
    }

    return false;
}





/*

LL      IIIII  SSSSS  TTTTTTT EEEEEEE NN   NN    LL       OOOOO   OOOOO  PPPPPP  
LL       III  SS        TTT   EE      NNN  NN    LL      OO   OO OO   OO PP   PP 
LL       III   SSSSS    TTT   EEEEE   NN N NN    LL      OO   OO OO   OO PPPPPP  
LL       III       SS   TTT   EE      NN  NNN    LL      OO   OO OO   OO PP      
LLLLLLL IIIII  SSSSS    TTT   EEEEEEE NN   NN    LLLLLLL  OOOO0   OOOO0  PP      
                                                                                 

*/




function do_listen_loop(&$ecmg)
{
    $s_sock = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP); // создаём сокет 
    socket_set_option($s_sock, SOL_SOCKET, SO_REUSEADDR, 1); // разрешаем использовать к нему нескослько tcp подключений
    socket_bind($s_sock, $ecmg["config"]["host"], $ecmg["config"]["port"]); // привязываем его к ip:port
    socket_listen($s_sock); // слушаем порт


    // log_d(0, "ecmg ready for connections (" . $ecmg["config"]["host"] . ":" . $ecmg["config"]["port"] . ")");
    $fd_set = array( $s_sock );
    
    
    while(true){
      
        $fd_set_tmp = $fd_set;
        if( ($tmp = @socket_select($fd_set_tmp, $null = NULL, $null = NULL, 1)) === false){
            if(socket_last_error() == 4) continue;
            break;
        }

        if($tmp < 1) continue;

        if( !($c_sock = @socket_accept($s_sock)) ) 
        {
            // log_d(0, "error #" . 644);
            continue;
        }

        if( !@socket_getpeername($c_sock, $peer_host, $peer_port) ) 
        {
            // log_d(0, "error #" . 649);
            @socket_close($c_sock);
            continue;
        }

        // log_d((int) $c_sock, "new connection accepted (peer=" . $peer_host . ":" . $peer_port . ")");
        switch( pcntl_fork() ) 
        {
            case -1:
                // log_d((int) $c_sock, "error #" . 658);
                @socket_close($c_sock);
                break;
            case 0:
                @socket_close($s_sock);
                if( !do_recv_loop($c_sock, $peer_host, $peer_port, $ecmg) ) 
                {
                    // log_d((int) $c_sock, "error #" . 665);
                    @socket_close($c_sock);
                    exit( -1 );
                }

                @socket_close($c_sock);
                exit( 0 );
        }
        @socket_close($c_sock);
    }
    @socket_close($s_sock);
    return false;
}




?>