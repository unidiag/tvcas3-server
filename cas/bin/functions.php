<?

include __DIR__ . "/../../includes/config.php"; // общие настройки
include __DIR__ . "/../../includes/functions.php"; // общие функции

define("ECMG_SCS_MSG_CHANNEL_SETUP", 1);
define("ECMG_SCS_MSG_CHANNEL_TEST", 2);
define("ECMG_SCS_MSG_CHANNEL_STATUS", 3);
define("ECMG_SCS_MSG_CHANNEL_CLOSE", 4);
define("ECMG_SCS_MSG_CHANNEL_ERROR", 5);
define("ECMG_SCS_MSG_STREAM_SETUP", 257);
define("ECMG_SCS_MSG_STREAM_TEST", 258);
define("ECMG_SCS_MSG_STREAM_STATUS", 259);
define("ECMG_SCS_MSG_STREAM_CLOSE_REQUEST", 260);
define("ECMG_SCS_MSG_STREAM_CLOSE_RESPONSE", 261);
define("ECMG_SCS_MSG_STREAM_ERROR", 262);
define("ECMG_SCS_MSG_CW_PROVISION", 513);
define("ECMG_SCS_MSG_ECM_RESPONSE", 514);
define("ECMG_SCS_PARAM_SUPER_CAS_ID", 1);
define("ECMG_SCS_PARAM_SECTION_TSPKT_FLAG", 2);
define("ECMG_SCS_PARAM_DELAY_START", 3);
define("ECMG_SCS_PARAM_DELAY_STOP", 4);
define("ECMG_SCS_PARAM_TRANSITION_DELAY_START", 5);
define("ECMG_SCS_PARAM_TRANSITION_DELAY_STOP", 6);
define("ECMG_SCS_PARAM_ECM_REP_PERIOD", 7);
define("ECMG_SCS_PARAM_MAX_STREAMS", 8);
define("ECMG_SCS_PARAM_MIN_CP_DURATION", 9);
define("ECMG_SCS_PARAM_LEAD_CW", 10);
define("ECMG_SCS_PARAM_CW_PER_MSG", 11);
define("ECMG_SCS_PARAM_MAX_COMP_TIME", 12);
define("ECMG_SCS_PARAM_ACCESS_CRITERIA", 13);
define("ECMG_SCS_PARAM_ECM_CHANNEL_ID", 14);
define("ECMG_SCS_PARAM_ECM_STREAM_ID", 15);
define("ECMG_SCS_PARAM_NOMINAL_CP_DURATION", 16);
define("ECMG_SCS_PARAM_ACCESS_CRITERIA_TRANSFER_MODE", 17);
define("ECMG_SCS_PARAM_CP_NUMBER", 18);
define("ECMG_SCS_PARAM_CP_DURATION", 19);
define("ECMG_SCS_PARAM_CP_CW_COMBINATION", 20);
define("ECMG_SCS_PARAM_ECM_DATAGRAM", 21);
define("ECMG_SCS_PARAM_AC_DELAY_START", 22);
define("ECMG_SCS_PARAM_AC_DELAY_STOP", 23);
define("ECMG_SCS_PARAM_CW_ENCRYPTION", 24);
define("ECMG_SCS_PARAM_ECM_ID", 25);
define("ECMG_SCS_PARAM_ERROR_STATUS", 28672);
define("ECMG_SCS_PARAM_ERROR_INFORMATION", 28673);
define("EMMG_MUX_MSG_CHANNEL_SETUP", 17);
define("EMMG_MUX_MSG_CHANNEL_TEST", 18);
define("EMMG_MUX_MSG_CHANNEL_STATUS", 19);
define("EMMG_MUX_MSG_CHANNEL_CLOSE", 20);
define("EMMG_MUX_MSG_CHANNEL_ERROR", 21);
define("EMMG_MUX_MSG_STREAM_SETUP", 273);
define("EMMG_MUX_MSG_STREAM_TEST", 274);
define("EMMG_MUX_MSG_STREAM_STATUS", 275);
define("EMMG_MUX_MSG_STREAM_CLOSE_REQUEST", 276);
define("EMMG_MUX_MSG_STREAM_CLOSE_RESPONSE", 277);
define("EMMG_MUX_MSG_STREAM_ERROR", 278);
define("EMMG_MUX_MSG_STREAM_BW_REQUEST", 279);
define("EMMG_MUX_MSG_STREAM_BW_ALLOCATION", 280);
define("EMMG_MUX_MSG_DATA_PROVISION", 529);
define("EMMG_MUX_PARAM_CLIENT_ID", 1);
define("EMMG_MUX_PARAM_SECTION_TSPKT_FLAG", 2);
define("EMMG_MUX_PARAM_DATA_CHANNEL_ID", 3);
define("EMMG_MUX_PARAM_DATA_STREAM_ID", 4);
define("EMMG_MUX_PARAM_DATAGRAM", 5);
define("EMMG_MUX_PARAM_BANDWIDTH", 6);
define("EMMG_MUX_PARAM_DATA_TYPE", 7);
define("EMMG_MUX_PARAM_DATA_ID", 8);
define("EMMG_MUX_PARAM_ERROR_STATUS", 28672);
define("EMMG_MUX_PARAM_ERROR_INFORMATION", 28673);











/*

EEEEEEE  CCCCC  MM    MM     CCCCC  WW      WW 
EE      CC    C MMM  MMM    CC    C WW      WW 
EEEEE   CC      MM MM MM    CC      WW   W  WW 
EE      CC    C MM    MM    CC    C  WW WWW WW 
EEEEEEE  CCCCC  MM    MM     CCCCC    WW   WW  
                                               

*/




function ecm_cw_pack($table, $time, $cw1, $cw2, $_kk, $ac){
    
    $message = pack("N", $time);         // ‭5C 7E A4 C7‬
    $message .= hex2bin($cw1);           // 00 00 00 00 00 00 00 00
    $message .= hex2bin($cw2);           // 11 11 11 11 11 11 11 11
    $message .= hex2bin($ac);            // 00 00 11 11
    for($i=0; $i<23; $i++){
      $message .= pack("C", rand(0,255));
    }
    
    $csum = (array_sum(hex2arr(bin2hex($message))) % 256); // расчёт контрольной суммы (0...255)
    $message .= pack("C", $csum); // добавляем последним байтом 48-ой контрольную сумму


    $msg = array_values(unpack("C*", $message)); // массив
    ksort($msg);
    $s = "";
    foreach($msg as $v){
      $d = dechex($v);
      $s .= ((strlen($d)==1) ? "0{$d}" : $d);
    }
      
    $encmsg = slyspace(enc2gost($s, $_kk));
     // log_d('ecmg', "CRYP ::: " . $encmsg);
     // log_d('ecmg', "DATA ::: " . slyspace($s));
     // log_d('ecmg', "_KK ::: " . slyspace($_kk));
      
    $message = "";
    foreach(explode(" ", $encmsg) as $v){
      $message .= pack("C", hexdec($v)); // готовим криптованное сообщение
    }

    $encrypted_message = $message;

    $datagram = pack("CCC", $table, 112, 2 + 2 + ($len = strlen($encrypted_message)));
    $datagram .= pack("CC", 112, 2 + $len);
    $datagram .= pack("n", 25633);
    $datagram .= $encrypted_message;
    
    return $datagram;
}









/*


EEEEEEE MM    MM MM    MM      AAA   DDDDD   DDDDD   
EE      MMM  MMM MMM  MMM     AAAAA  DD  DD  DD  DD  
EEEEE   MM MM MM MM MM MM    AA   AA DD   DD DD   DD 
EE      MM    MM MM    MM    AAAAAAA DD   DD DD   DD 
EEEEEEE MM    MM MM    MM    AA   AA DDDDDD  DDDDDD  
                                                     

                                                            

*/
function emm_subscription_add_pack($log_id, $serial_no, $access_criteria, $emmkey, $pair, $type, $time_start, $time_stop){

    if( 0 ) {
        // log_d(($log_id . " %", "subscription_add {");
        // log_d(($log_id . " %", sprintf(" serial_no: 0x%08X (%u)", $serial_no, $serial_no));
        // log_d(($log_id . " %", " AC: {$access_criteria}");
        // log_d(($log_id . " %", " time_start: " . date("Y-m-d H:i:s", $time_start) . " (" . $time_start . "), time_stop: " . date("Y-m-d H:i:s", $time_stop) . " (" . $time_stop . ")");
        // log_d(($log_id . " %", "}");
    }
    
    $message = pack("N", $serial_no);                      // [0...3]
    $message .= pack("N", hexdec($access_criteria));       // [4...7]
    $message .= pack("N", $time_start);                    // [8...11]
    $message .= pack("N", $time_stop + 1);                 // [12...15]
    $message .= pack("n", slydate(t($time_start)));        // [16..17]
    $message .= pack("n", slydate(t($time_stop)));         // [18..19]
    $message .= pack("C", ($pair>0 ? 1 : 0));              // [20] - 0 / 1
    $message .= pack("C", ($type==1 ? 1 : 0));              // [21] - ON/OFF MAYBE ANTISHARA
    $message .= pack("C", 0);                              // [22] - 0x00 // reserve
    $csum = (array_sum(hex2arr(bin2hex($message))) % 256); // расчёт контрольной суммы (0...255)
    $message .=  pack("C", $csum);

    $md5 = md5($time_start.$csum).md5($time_stop.$csum);
    for($i=0; $i<23; $i++){ // реализуем стабильную последовательность
      $message .= pack("C", hexdec(substr($md5, $i*2, 2)));
    }

    $csum = (array_sum(hex2arr(bin2hex($message))) % 256); // расчёт контрольной суммы (0...255)
    $message .=  pack("C", $csum); // добавляем последним байтом контрольную сумму [47]  -- общий размер 48 байт


    $msg = array_values(unpack("C*", $message)); // массив
    ksort($msg);
    $s = "";
    foreach($msg as $v){
      $d = dechex($v);
      $s .= ((strlen($d)==1) ? "0{$d}" : $d);
    }
    $encmsg = slyspace(enc2gost($s, $emmkey));  
    $message = "";
    foreach(explode(" ", $encmsg) as $v){
      $message .= pack("C", hexdec($v)); // готовим криптованное сообщение
    }

    $encrypted_message = $message;

    $datagram = pack("CCC", 130, 112, 7 + 2 + 2 + ($len = strlen($encrypted_message))); // 0x82 70 2B=43 (len=32 байта)
    $datagram .= pack("CCCN", 0, 0, 0, $serial_no); // 0x00 00 00 3D 92 54 A9
    $datagram .= pack("CC", 112, 2 + $len); // 0x7022  (2+32=0x22)
    $datagram .= pack("n", 25616); // 0x6410
    $datagram .= $encrypted_message;
    
    return $datagram;
}


/*

EEEEEEE MM    MM MM    MM    RRRRRR  EEEEEEE MM    MM  OOOOO  VV     VV EEEEEEE 
EE      MMM  MMM MMM  MMM    RR   RR EE      MMM  MMM OO   OO VV     VV EE      
EEEEE   MM MM MM MM MM MM    RRRRRR  EEEEE   MM MM MM OO   OO  VV   VV  EEEEE   
EE      MM    MM MM    MM    RR  RR  EE      MM    MM OO   OO   VV VV   EE      
EEEEEEE MM    MM MM    MM    RR   RR EEEEEEE MM    MM  OOOO0     VVV    EEEEEEE 
                                                                                

*/

function emm_subscription_remove_pack($log_id, $encryption_iv, $encryption_key, $key_tag_slot, $serial_no, $subscription_id){

    if( $GLOBALS["debug"] ) 
    {
        // log_d(($log_id . " %", "subscription_remove {");
        // log_d(($log_id . " %", sprintf(" key_tag_slot: 0x%04X", $key_tag_slot));
        // log_d(($log_id . " %", sprintf(" serial_no: 0x%08X (%u)", $serial_no, $serial_no));
        // log_d(($log_id . " %", " subscription_id: " . $subscription_id);
        // log_d(($log_id . " %", "}");
    }

    //$message = pack("n", rand(0, 32767));
    $message = pack("n", 32767);
    $message .= pack("C", 66);
    $message .= pack("C", 3);
    $message .= pack("C", 4 + 4);
    $message .= pack("N", $serial_no);
    $message .= pack("N", $subscription_id);
    $message .= pack("n", 4660);
    $encrypted_message = $message;

    $datagram = pack("CCC", 130, 112, 7 + 2 + 2 + ($len = strlen($encrypted_message)));
    $datagram .= pack("CCCN", 0, 0, 0, $serial_no);
    $datagram .= pack("CC", 112, 2 + $len);
    $datagram .= pack("n", $key_tag_slot);
    $datagram .= $encrypted_message;
    return $datagram;
}


/*

EEEEEEE MM    MM MM    MM    MM    MM EEEEEEE  SSSSS   SSSSS    AAA     GGGG  EEEEEEE 
EE      MMM  MMM MMM  MMM    MMM  MMM EE      SS      SS       AAAAA   GG  GG EE      
EEEEE   MM MM MM MM MM MM    MM MM MM EEEEE    SSSSS   SSSSS  AA   AA GG      EEEEE   
EE      MM    MM MM    MM    MM    MM EE           SS      SS AAAAAAA GG   GG EE      
EEEEEEE MM    MM MM    MM    MM    MM EEEEEEE  SSSSS   SSSSS  AA   AA  GGGGGG EEEEEEE 
                                                                                      

*/

/*


function emm_message_pack($log_id, $encryption_iv, $encryption_key, $key_tag_slot, $serial_no, $duration, $repetition, $interval, $seq_no, $text){

  


    while( is_ascii($text) ) 
    {
        break;
    }
    if( is_cyrillic($text) && ($tmp = iconv("UTF-8", "ISO-8859-5", $text)) !== false ) 
    {
        $text = pack("C", 1) . $tmp;
        break;
    }

    $text = pack("C", 21) . $text;
    if( 0 ) 
    {
    }
    else
    {
        if( 141 < ($text_len = strlen($text)) ) 
        {
            // log_d(($log_id, "error #" . 384);
            return false;
        }

        $a = pack("CC", 1, $text_len) . $text;
        $a .= pack("CCC", 2, 1, $seq_no);
        if( $repetition < 1 ) 
        {
            $repetition = 1;
        }

        if( 255 < $repetition ) 
        {
            $repetition = 255;
        }

        if( $duration < 0 ) 
        {
            $duration = 0;
        }

        if( 65535 < $duration ) 
        {
            $duration = 65535;
        }

        if( $interval < 10 ) 
        {
            $interval = 10;
        }

        if( 2550 < $interval ) 
        {
            $interval = 2550;
        }

        while( $duration == 0 ) 
        {
            $a .= pack("CCn", 4, 2, $duration);
            break;
        }
        if( $repetition == 1 ) 
        {
            $a .= pack("CCn", 4, 2, $duration);
            break;
        }

        if( $interval <= $duration ) 
        {
            $duration = $interval - 1;
        }

        $a .= pack("CCn", 4, 2, $duration);
        $a .= pack("CCC", 5, 1, $repetition);
        $a .= pack("CCC", 6, 1, (int) ($interval / 10));
        if( 0 ) 
        {
        }
        else
        {
            $i = 150 - strlen($a);
            while( 0 < $i ) 
            {
                $a .= pack("C", 0);
                $i--;
            }
            $a = pack("CC", 16, strlen($a)) . $a;
            $a = pack("CC", 128, strlen($a)) . $a;
            //$message = pack("n", rand(0, 32767));
            $message = pack("n", 32767);
            $message .= pack("C", 66);
            $message .= pack("C", 5);
            $message .= pack("C", 4 + strlen($a));
            $message .= pack("N", $serial_no);
            $message .= $a;
            $message .= pack("n", 4660);
            $encrypted_message = $message;

            $datagram = pack("CCC", 130, 112, 7 + 2 + 2 + ($len = strlen($encrypted_message)));
            $datagram .= pack("CCCN", 0, 0, 0, $serial_no);
            $datagram .= pack("CC", 112, 2 + $len);
            $datagram .= pack("n", $key_tag_slot);
            $datagram .= $encrypted_message;
            return $datagram;
        }

    }

}*/

function get_ecmg_scs_parameter_name($value)
{
    switch( $value ) 
    {
        case 0:
            return "dvb_reserved_0000";
        case ECMG_SCS_PARAM_SUPER_CAS_ID:
            return "super_cas_id";
        case ECMG_SCS_PARAM_SECTION_TSPKT_FLAG:
            return "section_tspkt_flag";
        case ECMG_SCS_PARAM_DELAY_START:
            return "delay_start";
        case ECMG_SCS_PARAM_DELAY_STOP:
            return "delay_stop";
        case ECMG_SCS_PARAM_TRANSITION_DELAY_START:
            return "transition_delay_start";
        case ECMG_SCS_PARAM_TRANSITION_DELAY_STOP:
            return "transition_delay_stop";
        case ECMG_SCS_PARAM_ECM_REP_PERIOD:
            return "ecm_rep_period";
        case ECMG_SCS_PARAM_MAX_STREAMS:
            return "max_streams";
        case ECMG_SCS_PARAM_MIN_CP_DURATION:
            return "min_cp_duration";
        case ECMG_SCS_PARAM_LEAD_CW:
            return "lead_cw";
        case ECMG_SCS_PARAM_CW_PER_MSG:
            return "cw_per_msg";
        case ECMG_SCS_PARAM_MAX_COMP_TIME:
            return "max_comp_time";
        case ECMG_SCS_PARAM_ACCESS_CRITERIA:
            return "access_criteria";
        case ECMG_SCS_PARAM_ECM_CHANNEL_ID:
            return "ecm_channel_id";
        case ECMG_SCS_PARAM_ECM_STREAM_ID:
            return "ecm_stream_id";
        case ECMG_SCS_PARAM_NOMINAL_CP_DURATION:
            return "nominal_cp_duration";
        case ECMG_SCS_PARAM_ACCESS_CRITERIA_TRANSFER_MODE:
            return "access_criteria_transfer_mode";
        case ECMG_SCS_PARAM_CP_NUMBER:
            return "cp_number";
        case ECMG_SCS_PARAM_CP_DURATION:
            return "cp_duration";
        case ECMG_SCS_PARAM_CP_CW_COMBINATION:
            return "cp_cw_combination";
        case ECMG_SCS_PARAM_ECM_DATAGRAM:
            return "ecm_datagram";
        case ECMG_SCS_PARAM_AC_DELAY_START:
            return "ac_delay_start";
        case ECMG_SCS_PARAM_AC_DELAY_STOP:
            return "ac_delay_stop";
        case ECMG_SCS_PARAM_CW_ENCRYPTION:
            return "cw_encryption";
        case ECMG_SCS_PARAM_ECM_ID:
            return "ecm_id";
        case ECMG_SCS_PARAM_ERROR_STATUS:
            return "error_status";
        case ECMG_SCS_PARAM_ERROR_INFORMATION:
            return "error_information";
    }
    if( 26 <= $value || $value <= 111 || 28674 <= $value || $value <= 524287 ) 
    {
        return "dvb_reserved_" . sprintf("%04X", $value & 65535);
    }

    if( 32768 <= $value || $value <= 65535 ) 
    {
        return "user_defined_" . sprintf("%04X", $value & 65535);
    }

    return "unknown_" . sprintf("%04X", $value & 65535);
}

function get_emmg_mux_parameter_name($value)
{
    switch( $value ) 
    {
        case 0:
            return "dvb_reserved_0000";
        case EMMG_MUX_PARAM_CLIENT_ID:
            return "client_id";
        case EMMG_MUX_PARAM_SECTION_TSPKT_FLAG:
            return "section_tspkt_flag";
        case EMMG_MUX_PARAM_DATA_CHANNEL_ID:
            return "data_channel_id";
        case EMMG_MUX_PARAM_DATA_STREAM_ID:
            return "data_stream_id";
        case EMMG_MUX_PARAM_DATAGRAM:
            return "datagram";
        case EMMG_MUX_PARAM_BANDWIDTH:
            return "bandwidth";
        case EMMG_MUX_PARAM_DATA_TYPE:
            return "data_type";
        case EMMG_MUX_PARAM_DATA_ID:
            return "data_id";
        case EMMG_MUX_PARAM_ERROR_STATUS:
            return "error_status";
        case EMMG_MUX_PARAM_ERROR_INFORMATION:
            return "error_information";
    }
    if( 9 <= $value || $value <= 28671 || 28674 <= $value || $value <= 32767 ){
        return "dvb_reserved_" . sprintf("%04X", $value & 65535);
    }

    if( 32768 <= $value || $value <= 65535 ){
        return "user_defined_" . sprintf("%04X", $value & 65535);
    }

    return "unknown_" . sprintf("%04X", $value & 65535);
}

function get_parameter_name($message_type, $value)
{
    switch( $message_type ) 
    {
        case ECMG_SCS_MSG_CHANNEL_SETUP:
        case ECMG_SCS_MSG_CHANNEL_TEST:
        case ECMG_SCS_MSG_CHANNEL_STATUS:
        case ECMG_SCS_MSG_CHANNEL_CLOSE:
        case ECMG_SCS_MSG_CHANNEL_ERROR:
        case ECMG_SCS_MSG_STREAM_SETUP:
        case ECMG_SCS_MSG_STREAM_TEST:
        case ECMG_SCS_MSG_STREAM_STATUS:
        case ECMG_SCS_MSG_STREAM_CLOSE_REQUEST:
        case ECMG_SCS_MSG_STREAM_CLOSE_RESPONSE:
        case ECMG_SCS_MSG_STREAM_ERROR:
        case ECMG_SCS_MSG_CW_PROVISION:
        case ECMG_SCS_MSG_ECM_RESPONSE:
            return get_ecmg_scs_parameter_name($value);
        case EMMG_MUX_MSG_CHANNEL_SETUP:
        case EMMG_MUX_MSG_CHANNEL_TEST:
        case EMMG_MUX_MSG_CHANNEL_STATUS:
        case EMMG_MUX_MSG_CHANNEL_CLOSE:
        case EMMG_MUX_MSG_CHANNEL_ERROR:
        case EMMG_MUX_MSG_STREAM_SETUP:
        case EMMG_MUX_MSG_STREAM_TEST:
        case EMMG_MUX_MSG_STREAM_STATUS:
        case EMMG_MUX_MSG_STREAM_CLOSE_REQUEST:
        case EMMG_MUX_MSG_STREAM_CLOSE_RESPONSE:
        case EMMG_MUX_MSG_STREAM_ERROR:
        case EMMG_MUX_MSG_STREAM_BW_REQUEST:
        case EMMG_MUX_MSG_STREAM_BW_ALLOCATION:
        case EMMG_MUX_MSG_DATA_PROVISION:
            return get_emmg_mux_parameter_name($value);
    }
    switch( $value ) 
    {
        case 0:
            return "dvb_reserved_" . sprintf("%04X", $value & 65535);
        case 28672:
            return "error_status";
        case 28673:
            return "error_information";
    }
    return "unknown_" . sprintf("%04X", $value & 65535);
}

function get_ecmg_scs_parameter_type($value)
{
    switch( $value ) 
    {
        case 0:
            return 0;
        case ECMG_SCS_PARAM_SUPER_CAS_ID:
            return 4;
        case ECMG_SCS_PARAM_SECTION_TSPKT_FLAG:
            return 1;
        case ECMG_SCS_PARAM_DELAY_START:
            return 6;
        case ECMG_SCS_PARAM_DELAY_STOP:
            return 6;
        case ECMG_SCS_PARAM_TRANSITION_DELAY_START:
            return 6;
        case ECMG_SCS_PARAM_TRANSITION_DELAY_STOP:
            return 6;
        case ECMG_SCS_PARAM_ECM_REP_PERIOD:
            return 3;
        case ECMG_SCS_PARAM_MAX_STREAMS:
            return 3;
        case ECMG_SCS_PARAM_MIN_CP_DURATION:
            return 3;
        case ECMG_SCS_PARAM_LEAD_CW:
            return 2;
        case ECMG_SCS_PARAM_CW_PER_MSG:
            return 2;
        case ECMG_SCS_PARAM_MAX_COMP_TIME:
            return 3;
        case ECMG_SCS_PARAM_ACCESS_CRITERIA:
            return 9;
        case ECMG_SCS_PARAM_ECM_CHANNEL_ID:
            return 3;
        case ECMG_SCS_PARAM_ECM_STREAM_ID:
            return 3;
        case ECMG_SCS_PARAM_NOMINAL_CP_DURATION:
            return 3;
        case ECMG_SCS_PARAM_ACCESS_CRITERIA_TRANSFER_MODE:
            return 1;
        case ECMG_SCS_PARAM_CP_NUMBER:
            return 3;
        case ECMG_SCS_PARAM_CP_DURATION:
            return 3;
        case ECMG_SCS_PARAM_CP_CW_COMBINATION:
            return 11;
        case ECMG_SCS_PARAM_ECM_DATAGRAM:
            return 9;
        case ECMG_SCS_PARAM_AC_DELAY_START:
            return 6;
        case ECMG_SCS_PARAM_AC_DELAY_STOP:
            return 6;
        case ECMG_SCS_PARAM_CW_ENCRYPTION:
            return 9;
        case ECMG_SCS_PARAM_ECM_ID:
            return 3;
        case ECMG_SCS_PARAM_ERROR_STATUS:
            return 10;
        case ECMG_SCS_PARAM_ERROR_INFORMATION:
            return 8;
    }
    if( 26 <= $value || $value <= 111 || 28674 <= $value || $value <= 32767 ) 
    {
        return 0;
    }

    if( 32768 <= $value || $value <= 65535 ) 
    {
        return 0;
    }

    return 0;
}

function get_emmg_mux_parameter_type($value)
{
    switch( $value ) 
    {
        case 0:
            return 0;
        case EMMG_MUX_PARAM_CLIENT_ID:
            return 4;
        case EMMG_MUX_PARAM_SECTION_TSPKT_FLAG:
            return 1;
        case EMMG_MUX_PARAM_DATA_CHANNEL_ID:
            return 3;
        case EMMG_MUX_PARAM_DATA_STREAM_ID:
            return 3;
        case EMMG_MUX_PARAM_DATAGRAM:
            return 9;
        case EMMG_MUX_PARAM_BANDWIDTH:
            return 3;
        case EMMG_MUX_PARAM_DATA_TYPE:
            return 2;
        case EMMG_MUX_PARAM_DATA_ID:
            return 3;
        case EMMG_MUX_PARAM_ERROR_STATUS:
            return 10;
        case EMMG_MUX_PARAM_ERROR_INFORMATION:
            return 8;
    }
    if( 9 <= $value || $value <= 28671 || 28674 <= $value || $value <= 32767 ) 
    {
        return 0;
    }

    if( 32768 <= $value || $value <= 65535 ) 
    {
        return 0;
    }

    return 0;
}

function get_parameter_type($message_type, $value)
{
    switch( $message_type ) 
    {
        case ECMG_SCS_MSG_CHANNEL_SETUP:
        case ECMG_SCS_MSG_CHANNEL_TEST:
        case ECMG_SCS_MSG_CHANNEL_STATUS:
        case ECMG_SCS_MSG_CHANNEL_CLOSE:
        case ECMG_SCS_MSG_CHANNEL_ERROR:
        case ECMG_SCS_MSG_STREAM_SETUP:
        case ECMG_SCS_MSG_STREAM_TEST:
        case ECMG_SCS_MSG_STREAM_STATUS:
        case ECMG_SCS_MSG_STREAM_CLOSE_REQUEST:
        case ECMG_SCS_MSG_STREAM_CLOSE_RESPONSE:
        case ECMG_SCS_MSG_STREAM_ERROR:
        case ECMG_SCS_MSG_CW_PROVISION:
        case ECMG_SCS_MSG_ECM_RESPONSE:
            return get_ecmg_scs_parameter_type($value);
        case EMMG_MUX_MSG_CHANNEL_SETUP:
        case EMMG_MUX_MSG_CHANNEL_TEST:
        case EMMG_MUX_MSG_CHANNEL_STATUS:
        case EMMG_MUX_MSG_CHANNEL_CLOSE:
        case EMMG_MUX_MSG_CHANNEL_ERROR:
        case EMMG_MUX_MSG_STREAM_SETUP:
        case EMMG_MUX_MSG_STREAM_TEST:
        case EMMG_MUX_MSG_STREAM_STATUS:
        case EMMG_MUX_MSG_STREAM_CLOSE_REQUEST:
        case EMMG_MUX_MSG_STREAM_CLOSE_RESPONSE:
        case EMMG_MUX_MSG_STREAM_ERROR:
        case EMMG_MUX_MSG_STREAM_BW_REQUEST:
        case EMMG_MUX_MSG_STREAM_BW_ALLOCATION:
        case EMMG_MUX_MSG_DATA_PROVISION:
            return get_emmg_mux_parameter_type($value);
    }
    switch( $value ) 
    {
        case 0:
            return 0;
        case 28672:
            return 10;
        case 28673:
            return 8;
    }
    return 0;
}

function pack_array($a)
{
    $b = "";
    foreach( $a as $key => $value ) 
    {
        $b .= pack("C", $value);
    }
    return $b;
}

function pdu_unpack($pdu, &$protocol_version, &$message_type, &$message_length)
{
    list($protocol_version, $message_type, $message_length, $payload) = array_values(unpack("Cprotocol_version/nmessage_type/nmessage_length/a*payload", $pdu));
    switch( $message_type ) 
    {
        case ECMG_SCS_MSG_CHANNEL_SETUP:
        case ECMG_SCS_MSG_CHANNEL_TEST:
        case ECMG_SCS_MSG_CHANNEL_STATUS:
        case ECMG_SCS_MSG_CHANNEL_CLOSE:
        case ECMG_SCS_MSG_CHANNEL_ERROR:
        case ECMG_SCS_MSG_STREAM_SETUP:
        case ECMG_SCS_MSG_STREAM_TEST:
        case ECMG_SCS_MSG_STREAM_STATUS:
        case ECMG_SCS_MSG_STREAM_CLOSE_REQUEST:
        case ECMG_SCS_MSG_STREAM_CLOSE_RESPONSE:
        case ECMG_SCS_MSG_STREAM_ERROR:
        case ECMG_SCS_MSG_CW_PROVISION:
        case ECMG_SCS_MSG_ECM_RESPONSE:
        case EMMG_MUX_MSG_CHANNEL_SETUP:
        case EMMG_MUX_MSG_CHANNEL_TEST:
        case EMMG_MUX_MSG_CHANNEL_STATUS:
        case EMMG_MUX_MSG_CHANNEL_CLOSE:
        case EMMG_MUX_MSG_CHANNEL_ERROR:
        case EMMG_MUX_MSG_STREAM_SETUP:
        case EMMG_MUX_MSG_STREAM_TEST:
        case EMMG_MUX_MSG_STREAM_STATUS:
        case EMMG_MUX_MSG_STREAM_CLOSE_REQUEST:
        case EMMG_MUX_MSG_STREAM_CLOSE_RESPONSE:
        case EMMG_MUX_MSG_STREAM_ERROR:
        case EMMG_MUX_MSG_STREAM_BW_REQUEST:
        case EMMG_MUX_MSG_STREAM_BW_ALLOCATION:
        case EMMG_MUX_MSG_DATA_PROVISION:
            break;
        default:
            return false;
    }
    $payload_a = unpack("C*", $payload);
    $message = array(  );
    while( true ) 
    {
        if( count($payload_a) < 4 ) 
        {
            break;
        }

        $parameter_tag = (array_shift($payload_a) << 8 & 65280) + (array_shift($payload_a) & 255);
        $parameter_length = (array_shift($payload_a) << 8 & 65280) + (array_shift($payload_a) & 255);
        $parameter_value = "";
        $len = 0;
        while( $len < $parameter_length ) 
        {
            $parameter_value .= pack("C", array_shift($payload_a));
            $len++;
        }
        if( ($parameter_name = get_parameter_name($message_type, $parameter_tag)) === false || ($parameter_type = get_parameter_type($message_type, $parameter_tag)) === false ) 
        {
            continue;
        }

        if( !isset($message[$parameter_name]) ) 
        {
            $message[$parameter_name] = array( "tag" => $parameter_tag, "type" => $parameter_type );
        }

        switch( $parameter_type ) 
        {
            case 0:
            case 1:
            case 2:
                list($message[$parameter_name]["value"]) = array_values(unpack("C", $parameter_value));
                break;
            case 3:
                list($message[$parameter_name]["value"]) = array_values(unpack("n", $parameter_value));
                break;
            case 4:
                list($message[$parameter_name]["value"]) = array_values(unpack("N", $parameter_value));
                break;
            case 5:
                list($tmp) = array_values(unpack("C", $parameter_value));
                $message[$parameter_name]["value"] = (($tmp & 128) == 128 ? ($tmp & 127) - 128 : $tmp);
                break;
            case 6:
                list($tmp) = array_values(unpack("n", $parameter_value));
                $message[$parameter_name]["value"] = (($tmp & 32768) == 32768 ? ($tmp & 32767) - 32768 : $tmp);
                break;
            case 7:
                list($tmp) = array_values(unpack("N", $parameter_value));
                $message[$parameter_name]["value"] = (($tmp & 2147483648) == 2147483648 ? ($tmp & 2147483647) - 2147483648 : $tmp);
                break;
            case 8:
                $message[$parameter_name]["value"] = $parameter_value;
            case 9:
                $message[$parameter_name]["value"] = $parameter_value;
                break;
            case 10:
                list($message[$parameter_name]["value"]) = array_values(unpack("n", $parameter_value));
                break;
            case 11:
                $tmp = unpack("ncp/C" . (strlen($parameter_value) - 2), $parameter_value);
                $cp = $tmp["cp"];
                array_shift($tmp);
                $message[$parameter_name]["value"][] = array( "cp" => $cp, "cw" => pack_array($tmp) );
                break;
            default:
                $message[$parameter_name]["value"] = $parameter_value;
                break;
        }
    }
    return $message;
}
/*
function get_message_type($value)
{
    switch( $value ) 
    {
        case 0:
            return "dvb reserved";
        case ECMG_SCS_MSG_CHANNEL_SETUP:
            return "ecmg <> scs ; channel_setup";
        case ECMG_SCS_MSG_CHANNEL_TEST:
            return "ecmg <> scs ; channel_test";
        case ECMG_SCS_MSG_CHANNEL_STATUS:
            return "ecmg <> scs ; channel_status";
        case ECMG_SCS_MSG_CHANNEL_CLOSE:
            return "ecmg <> scs ; channel_close";
        case ECMG_SCS_MSG_CHANNEL_ERROR:
            return "ecmg <> scs ; channel_error";
        case EMMG_MUX_MSG_CHANNEL_SETUP:
            return "emmg <> mux ; channel_setup";
        case EMMG_MUX_MSG_CHANNEL_TEST:
            return "emmg <> mux ; channel_test";
        case EMMG_MUX_MSG_CHANNEL_STATUS:
            return "emmg <> mux ; channel_status";
        case EMMG_MUX_MSG_CHANNEL_CLOSE:
            return "emmg <> mux ; channel_close";
        case EMMG_MUX_MSG_CHANNEL_ERROR:
            return "emmg <> mux ; channel_error";
        case ECMG_SCS_MSG_STREAM_SETUP:
            return "ecmg <> scs ; stream_setup";
        case ECMG_SCS_MSG_STREAM_TEST:
            return "ecmg <> scs ; stream_test";
        case ECMG_SCS_MSG_STREAM_STATUS:
            return "ecmg <> scs ; stream_status";
        case ECMG_SCS_MSG_STREAM_CLOSE_REQUEST:
            return "ecmg <> scs ; stream_close_request";
        case ECMG_SCS_MSG_STREAM_CLOSE_RESPONSE:
            return "ecmg <> scs ; stream_close_response";
        case ECMG_SCS_MSG_STREAM_ERROR:
            return "ecmg <> scs ; stream_error";
        case EMMG_MUX_MSG_STREAM_SETUP:
            return "emmg <> mux ; stream_setup";
        case EMMG_MUX_MSG_STREAM_TEST:
            return "emmg <> mux ; stream_test";
        case EMMG_MUX_MSG_STREAM_STATUS:
            return "emmg <> mux ; stream_status";
        case EMMG_MUX_MSG_STREAM_CLOSE_REQUEST:
            return "emmg <> mux ; stream_close_request";
        case EMMG_MUX_MSG_STREAM_CLOSE_RESPONSE:
            return "emmg <> mux ; stream_close_response";
        case EMMG_MUX_MSG_STREAM_ERROR:
            return "emmg <> mux ; stream_error";
        case EMMG_MUX_MSG_STREAM_BW_REQUEST:
            return "emmg <> mux ; stream_bw_request";
        case EMMG_MUX_MSG_STREAM_BW_ALLOCATION:
            return "emmg <> mux ; stream_bw_allocation";
        case ECMG_SCS_MSG_CW_PROVISION:
            return "ecmg <> scs ; cw_provision";
        case ECMG_SCS_MSG_ECM_RESPONSE:
            return "ecmg <> scs ; ecm_response";
        case EMMG_MUX_MSG_DATA_PROVISION:
            return "emmg <> mux ; data_provision";
    }
    if( 6 <= $value && $value <= 16 || 22 <= $value && $value <= 32 || 263 <= $value && $value <= 272 || 281 <= $value && $value <= 288 || 515 <= $value && $value <= 528 || 530 <= $value && $value <= 544 || 545 <= $value && $value <= 32767 ) 
    {
        return "dvb reserved";
    }

    if( 32768 <= $value && $value <= 65535 ) 
    {
        return "user defined";
    }

    return "unknown";
}

function bin_to_hex($bin)
{
    $hex = array(  );
    foreach( unpack("C*", $bin) as $key => $value ) 
    {
        $hex[] = sprintf("%02X", $value);
    }
    return implode(" ", $hex);
}

function get_error_status($value)
{
    switch( $value ) 
    {
        case 0:
            return "dvb reserved";
        case 1:
            return "invalid message";
        case 2:
            return "unsupported protocol version";
        case 3:
            return "unknown message_type value";
        case 4:
            return "message too long";
        case 5:
            return "unknown super_cas_id value";
        case 6:
            return "unknown ecm_channel_id value";
        case 7:
            return "unknown ecm_stream_id value";
        case 8:
            return "too many channels on this ecmg";
        case 9:
            return "too many ecm streams on this channel";
        case 10:
            return "too many ecm streams on this ecmg";
        case 11:
            return "not enough cws to compute ecm";
        case 12:
            return "ecmg out of storage capacity";
        case 13:
            return "ecmg out of computational resources";
        case 14:
            return "unknown parameter_type value";
        case 15:
            return "inconsistent length for dvb parameter";
        case 16:
            return "missing mandatory dvb parameter";
        case 17:
            return "invalid value for dvb parameter";
        case 18:
            return "unknown ecm_id value";
        case 19:
            return "ecm_channel_id value already in use";
        case 20:
            return "ecm_stream_id value already in use";
        case 21:
            return "ecm_id value already in use";
        case 28672:
            return "unknown error";
        case 28673:
            return "unrecoverable error";
    }
    if( 22 <= $value || $value <= 28671 || 28674 <= $value || $value <= 32767 ) 
    {
        return "dvb reserved";
    }

    if( 32768 <= $value || $value <= 65535 ) 
    {
        return "user defined";
    }

    return "unknown";
}*/





/*
EEEEEEE NN   NN  CCCCC  RRRRRR  YY   YY PPPPPP  TTTTTTT    333333  DDDDD   EEEEEEE  SSSSS  
EE      NNN  NN CC    C RR   RR YY   YY PP   PP   TTT         3333 DD  DD  EE      SS      
EEEEE   NN N NN CC      RRRRRR   YYYYY  PPPPPP    TTT        3333  DD   DD EEEEE    SSSSS  
EE      NN  NNN CC    C RR  RR    YYY   PP        TTT          333 DD   DD EE           SS 
EEEEEEE NN   NN  CCCCC  RR   RR   YYY   PP        TTT      333333  DDDDDD  EEEEEEE  SSSSS  
                                                                                           
*/



function enc2gost($data, $key){
  //$key = "7E 33 A2 78 B3 61 7A 30 9B 18 22 2F 37 2A 23 19 07 AC 67 E0 8A 5F 12 C3";
  $data = slyspace(str_replace(" ", "", $data));
  //return $data;
  $key = str_replace(" ", "", $key); // K1 + K2
  $key .= substr($key, 0, 16); // K3 = K1
  $key = slyspace($key);
  $out = "";
  $_key = "";
  $_data = "";
  foreach(explode(" ", $key) as $v){
    $_key .= pack("C", hexdec($v));
  }
  foreach(explode(" ", $data) as $v){
    $_data .= pack("C", hexdec($v));
  }
  // блок по 8 байт (16 знаков 010203...AF)
  for($x=0; $x<strlen($_data); $x+=8){
    $block = substr($_data, $x, 8);
    $out .= substr(openssl_encrypt($block, "des-ede3", $_key, OPENSSL_RAW_DATA), 0, 8); 
  }
  return slyspace(chk(unpack("H*", $out), 1));
}





function pdu_dump($prefix, $pdu)
{
    if( ($message = pdu_unpack($pdu, $protocol_version, $message_type, $message_length)) === false ) 
    {
        return false;
    }

    // log_d(($prefix, "@ (" . strlen($pdu) . ") " . bin2hex($pdu));
    // log_d(($prefix, sprintf("%s (0x%04X) {", get_message_type($message_type), $message_type, $message_type));
    // log_d(($prefix, sprintf(" protocol_version: 0x%02X (%u)", $protocol_version, $protocol_version));
    // log_d(($prefix, sprintf(" message_length: 0x%04X (%u)", $message_length, $message_length));
    foreach( $message as $key => $value ) 
    {
        switch( $value["type"] ) 
        {
            case 0:
            case 1:
            case 2:
                // log_d(($prefix, sprintf("  %s (0x%04X): 0x%02X (%u)", $key, $value["tag"], $value["value"] & 255, $value["value"]));
                break;
            case 3:
                // log_d(($prefix, sprintf("  %s (0x%04X): 0x%04X (%u)", $key, $value["tag"], $value["value"] & 65535, $value["value"]));
                break;
            case 4:
                // log_d(($prefix, sprintf("  %s (0x%04X): 0x%08X (%u)", $key, $value["tag"], $value["value"] & 4294967295, $value["value"]));
                break;
            case 5:
                // log_d(($prefix, sprintf("  %s (0x%04X): 0x%02X (%d)", $key, $value["tag"], $value["value"] & 255, $value["value"]));
                break;
            case 6:
                // log_d(($prefix, sprintf("  %s (0x%04X): 0x%04X (%d)", $key, $value["tag"], $value["value"] & 65535, $value["value"]));
                break;
            case 7:
                // log_d(($prefix, sprintf("  %s (0x%04X): 0x%08X (%ld)", $key, $value["tag"], $value["value"] & 4294967295, $value["value"]));
                break;
            case 8:
                // log_d(($prefix, sprintf("  %s (0x%04X): %s", $key, $value["tag"], $value["value"]));
                break;
            case 9:
                // log_d(($prefix, sprintf("  %s (0x%04X): %s", $key, $value["tag"], bin2hex($value["value"])));
                break;
            case 10:
                // log_d(($prefix, sprintf("  %s (0x%04X): 0x%04X (%s)", $key, $value["tag"], $value["value"], get_error_status($value["value"])));
                break;
            case 11:
                foreach( $value["value"] as $key2 => $value2 ) 
                {
                    // log_d(($prefix, sprintf("  %s (0x%04X): cp - 0x%04X (%u), cw - %s", $key, $value["tag"], $value2["cp"], $value2["cp"], bin2hex($value2["cw"])));
                }
                break;
            default:
                // log_d(($prefix, sprintf("  %s (0x%04X): %s", $key, $value["tag"], bin2hex($value["value"])));
                break;
        }
    }
    // log_d(($prefix, "}");
    return true;
}

function pdu_pack($protocol_version, $message_type, $message)
{
    switch( $message_type ) 
    {
        case ECMG_SCS_MSG_CHANNEL_SETUP:
        case ECMG_SCS_MSG_CHANNEL_TEST:
        case ECMG_SCS_MSG_CHANNEL_STATUS:
        case ECMG_SCS_MSG_CHANNEL_CLOSE:
        case ECMG_SCS_MSG_CHANNEL_ERROR:
        case ECMG_SCS_MSG_STREAM_SETUP:
        case ECMG_SCS_MSG_STREAM_TEST:
        case ECMG_SCS_MSG_STREAM_STATUS:
        case ECMG_SCS_MSG_STREAM_CLOSE_REQUEST:
        case ECMG_SCS_MSG_STREAM_CLOSE_RESPONSE:
        case ECMG_SCS_MSG_STREAM_ERROR:
        case ECMG_SCS_MSG_CW_PROVISION:
        case ECMG_SCS_MSG_ECM_RESPONSE:
        case EMMG_MUX_MSG_CHANNEL_SETUP:
        case EMMG_MUX_MSG_CHANNEL_TEST:
        case EMMG_MUX_MSG_CHANNEL_STATUS:
        case EMMG_MUX_MSG_CHANNEL_CLOSE:
        case EMMG_MUX_MSG_CHANNEL_ERROR:
        case EMMG_MUX_MSG_STREAM_SETUP:
        case EMMG_MUX_MSG_STREAM_TEST:
        case EMMG_MUX_MSG_STREAM_STATUS:
        case EMMG_MUX_MSG_STREAM_CLOSE_REQUEST:
        case EMMG_MUX_MSG_STREAM_CLOSE_RESPONSE:
        case EMMG_MUX_MSG_STREAM_ERROR:
        case EMMG_MUX_MSG_STREAM_BW_REQUEST:
        case EMMG_MUX_MSG_STREAM_BW_ALLOCATION:
        case EMMG_MUX_MSG_DATA_PROVISION:
            break;
        default:
            return false;
    }
    $payload = "";
    foreach( $message as $key => $value ) 
    {
        if( ($parameter_type = get_parameter_type($message_type, $key)) === false ) 
        {
            continue;
        }

        switch( $parameter_type ) 
        {
            case 0:
            case 1:
            case 2:
                $payload .= pack("nnC", $key, 1, $value & 255);
                break;
            case 3:
                $payload .= pack("nnn", $key, 2, $value & 65535);
                break;
            case 4:
                $payload .= pack("nnN", $key, 4, $value & 4294967295);
                break;
            case 5:
                $payload .= pack("nnC", $key, 1, $value & 255);
                break;
            case 6:
                $payload .= pack("nnn", $key, 2, $value & 65535);
                break;
            case 7:
                $payload .= pack("nnN", $key, 4, $value & 4294967295);
                break;
            case 8:
                $payload .= pack("nna" . ($len = strlen($value)), $key, $len, $value);
                break;
            case 9:
                $payload .= pack("nna" . ($len = strlen($value)), $key, $len, $value);
                break;
            case 10:
                $payload .= pack("nnn", $key, 2, $value & 65535);
                break;
            case 11:
                //echo "todo\n";
                break;
            default:
                $payload .= pack("nna" . ($len = strlen($value)), $key, $len, $value);
                break;
        }
    }
    return pack("Cnna" . ($len = strlen($payload)), $protocol_version, $message_type, $len, $payload);
}

function pdu_pack_ecmg_scs_channel_error($protocol_version, $ecm_channel_id, $error_status)
{
    return pdu_pack($protocol_version, ECMG_SCS_MSG_CHANNEL_ERROR, array( ECMG_SCS_PARAM_ECM_CHANNEL_ID => $ecm_channel_id, ECMG_SCS_PARAM_ERROR_STATUS => $error_status ));
}

function pdu_pack_ecmg_scs_stream_error($protocol_version, $ecm_channel_id, $ecm_stream_id, $error_status)
{
    return pdu_pack($protocol_version, ECMG_SCS_MSG_STREAM_ERROR, array( ECMG_SCS_PARAM_ECM_CHANNEL_ID => $ecm_channel_id, ECMG_SCS_PARAM_ECM_STREAM_ID => $ecm_stream_id, ECMG_SCS_PARAM_ERROR_STATUS => $error_status ));
}

/*

function decrypt_message($message, $encryption_iv, $encryption_key, &$decrypted_message)
{
    $encryption_iv = unpack("C*", $encryption_iv);
    $block = "";
    foreach( unpack("C*", $encryption_key) as $key => $value ) 
    {
        $block .= pack("C", $value ^ $encryption_iv[($key - 1) % 8 + 1]);
    }
    $encryption_key = $block;
    $len = strlen($message);
    $block_size = mcrypt_get_block_size(MCRYPT_3DES, MCRYPT_MODE_ECB);
    $decrypted_message = "";
    $i = 0;
    while( $i < $len ) 
    {
        $block = substr($message, $i, $block_size);
        $encryption_iv_next = unpack("C*", $block);
        if( ($block = mcrypt_decrypt(MCRYPT_3DES, $encryption_key, $block, MCRYPT_MODE_ECB)) === false ) 
        {
            //echo "error #" . 1450 . "\n";
            return false;
        }

        $tmp = "";
        foreach( unpack("C*", $block) as $key => $value ) 
        {
            $tmp .= pack("C", $value ^ $encryption_iv[$key]);
        }
        $encryption_iv = $encryption_iv_next;
        $decrypted_message .= $tmp;
        $i += $block_size;
    }
    return true;
}


function is_ascii($text)
{
    foreach( unpack("C*", $text) as $value ) 
    {
        if( !(32 <= $value && $value <= 127) ) 
        {
            return false;
        }

    }
    return true;
}

function is_cyrillic($text)
{
    return preg_match("/[абвгдеёжзийклмнопрстуфхцчшщъыьэюяАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ]/iu", $text);
}
*/

?>