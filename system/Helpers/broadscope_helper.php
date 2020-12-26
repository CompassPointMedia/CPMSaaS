<?php
if (! function_exists('mylog')) {
    function mylog($message = '', $file = ''){
        if(!$file){
            $file = __FILE__;
            $file = explode('htdocs', $file);
            $file = $file[0] . 'htdocs/request_tracking/application/logs/mylog-' . (get_current_user() ? get_current_user() . '-' : '') . date('Y-m-d') . '.php';
        }
        $start = !file_exists($file);
        $fp = fopen($file, 'a');
        if($start){
            fwrite($fp, '<?php defined("BASEPATH") OR exit("No direct script access allowed");?>' . "\n\n");
            // chmod($file, '777');
        }
        $str = 'MYLOG - ' . date('Y-m-d H:i:s') . ' --> ';
        if(is_array($message)){
            $str .= print_r($message, true);
        }else{
            $str .= $message;
        }
        $str .= "\n";
        fwrite($fp, $str);
        fclose($fp);
    }
}

if (! function_exists('ai_color')) {
    function ai_color($title, $base = 170, $peak = 255){
        $title = md5(strtolower(preg_replace('/[^a-z0-9]/i', '', $title)));
        $title = base_convert($title, 16, 10);

        // range from 00 to 99
        $r = ltrim(substr($title, 4, 2),0)/100;
        $g = ltrim(substr($title, 6, 2),0)/100;
        $b = ltrim(substr($title, 8, 2),0)/100;
        $r = round($base + (($peak - $base) * $r));
        $g = round($base + (($peak - $base) * $g));
        $b = round($base + (($peak - $base) * $b));

        $color = '#' .
            str_pad(base_convert($r, 10, 16), '0', 2, STR_PAD_LEFT).
            str_pad(base_convert($g, 10, 16), '0', 2, STR_PAD_LEFT).
            str_pad(base_convert($b, 10, 16), '0', 2, STR_PAD_LEFT);
        return $color;
    }
}

/**
 * Get client URL - this is copied EXACTLY from the CAS client tools (_getClientUrl)
 * @return string
 */
if (! function_exists('get_client_url')) {
    function get_client_url($settings = []){
        extract($settings); //suppressPort
        $server_url = '';
        if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            // explode the host list separated by comma and use the first host
            $hosts = explode(',', $_SERVER['HTTP_X_FORWARDED_HOST']);
            $server_url = $hosts[0];
        } else if (!empty($_SERVER['HTTP_X_FORWARDED_SERVER'])) {
            $server_url = $_SERVER['HTTP_X_FORWARDED_SERVER'];
        } else {
            if (empty($_SERVER['SERVER_NAME'])) {
                $server_url = $_SERVER['HTTP_HOST'];
            } else {
                $server_url = $_SERVER['SERVER_NAME'];
            }
        }
        if (!strpos($server_url, ':') && empty($suppressPort)) {
            if (empty($_SERVER['HTTP_X_FORWARDED_PORT'])) {
                $server_port = $_SERVER['SERVER_PORT'];
            } else {
                $ports = explode(',', $_SERVER['HTTP_X_FORWARDED_PORT']);
                $server_port = $ports[0];
            }

            if ( (_is_https() && $server_port!=443)
                || (!_is_https() && $server_port!=80)
            ) {
                $server_url .= ':';
                $server_url .= $server_port;
            }
        }
        return $server_url;
    }
}

if (! function_exists('get_client_page')) {
    function get_client_page(){
        $page = !empty($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : $_SERVER['PHP_SELF'];
        $page .= !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
        return $page;
    }
}

/**
 * See get_client_url() above
 * @return bool
 */
if (! function_exists('_is_https')) {
    function _is_https()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            return ($_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        }
        if ( isset($_SERVER['HTTPS'])
            && !empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] != 'off'
        ) {
            return true;
        } else {
            return false;
        }
    }
}

if (! function_exists('get_page_from_uri')) {
    function get_page_from_uri($redirectURL){
        //assume variables available
        global $dbhost, $dbuser, $dbpass;
        $DbConnect = mysqli_connect($dbhost, $dbuser, $dbpass);
        if(!$DbConnect) return false;

        $str = preg_replace('/[-_ \']/', '', $redirectURL);
        $sql = "SELECT ID FROM `master`.`switch` 
        WHERE 
        PageTitle IS NOT NULL AND 
        PageTitle !='' AND 
        '$str' = REPLACE(REPLACE(REPLACE(REPLACE(PageTitle, ' ', ''), '-', ''), '_', ''), \"'\", '')";
        if($result = $DbConnect->query($sql)){
            $row = $result->fetch_array();
            return !empty($row['ID']) ? $row['ID'] : '';
        }
        return false;
    };
}

if (! function_exists('get_uri_from_page_title')) {
    function get_uri_from_page_title($title){
        $uri = str_replace("'", '', strtolower($title));
        $uri = str_replace('/', '', $uri);
        $uri = str_replace(' ', '-', $uri);
        $uri = preg_replace('/[-]+/', '-', $uri);
        return $uri;
    }
}

if (! function_exists('gmicrotime')) {
    function gmicrotime($marker='', $options=[]){
        #version 1.2, 2017-05-13

        extract($options);
        if(!isset($mem)) $mem = true; // || false, don't worry about memory
        if(!isset($format)) $format = 'array'; // || string

        global $mT;
        if($marker=='all') return $mT;

        $t = round(microtime(true), 6);

        if($format == 'string'){
            $value = $t;
        }else{
            $value = ['time'=>$t];
        }
        if($mem){
            $_mem = memory_get_usage();
            $_max = memory_get_peak_usage();
            if($format == 'string'){
                $value .= ":$_mem:$_max";
            }else{
                $value['memory'] = $_mem;
                $value['max'] = $_max;
            }
        }

        //store everything in this array
        $mT['all'][]=$value;

        //build associative 1-indexed array
        if(!empty($marker)){
            if(empty($mT['indexed'][$marker])){
                $mT['indexed'][$marker]=$value;
            }else{
                if(is_array($mT['indexed'][$marker])){
                    $mT['indexed'][$marker][ count($mT['indexed'][$marker])+1 ]=$value;
                }else{
                    $mT['indexed'][$marker][1]=array($mT['indexed'][$marker], $value);
                }
            }
        }
    }
    gmicrotime('initialize');
}

/**
 * Stringize: turn a normal HTML block into a single-quoted JS block, respecting newlines. Much easier to write VueJS templates.
 * Function grabs a source file once, but reads through the entire file till it gets to the stringized region.
 *
 * @param $token
 * @param $compile
 * @return string
 */
if (! function_exists('stringize')) {
    function stringize($token, $compile){
        //access FS I/O only one time - having the file in memory will only add a few KB of overhead
        static $stringize;
        if(empty($stringize[$compile])){
            $stringize[$compile] = file($compile);
            if(empty($stringize[$compile])) return '';
        }

        $inCode = false;
        $separator = 'a' . md5(time() . rand(0, 1000000));
        $str = '';
        foreach($stringize[$compile] as $line){
            if(stristr($line, '<!-- stringize:'.$token)){
                $inCode = true;
                $a = explode(DIRECTORY_SEPARATOR, $compile);
                $str .= '/* stringize:' . $token . ' compiled '.date('F jS, Y @g:iA').' from '.end($a).' */'. "\n";
                continue;
            }else if(stristr($line, '<!-- /stringize:'. $token)){
                $str =  rtrim($str, "\n +") . "\n" . '/* /stringize:' . $token . ' */';
                break;
            }else if(!$inCode){
                continue;
            }
            $line = rtrim($line);
            $line = preg_replace('/(^\s*)((.|\s)*$)/', '$1'.$separator.'$2'.$separator.' +', $line) . "\n";
            $line = str_replace("'", "\\'", $line);
            $line = str_replace($separator, "'", $line);
            $line = str_replace('<script', "<' + 'script", $line);
            $line = str_replace('</script', "</' + 'script", $line);
            $str .= $line;
        }
        return $str;
    }
}

if (! function_exists('stripslashes_deep')) {
    function stripslashes_deep($value) {
        return is_array($value) ?
            array_map('stripslashes_deep', $value) :
            stripslashes($value);
    }
}

if (! function_exists('addslashes_deep')) {
    function addslashes_deep($value) {
        return is_array($value) ?
            array_map('addslashes_deep', $value) :
            addslashes($value);
    }
}

//standard error reporting/display coding
if (! function_exists('set_app_env')) {
    function set_app_env(){
        if(!defined('APP_ENV')){
            $AppEnv = getenv('AppEnv');
            define('APP_ENV', $AppEnv ? : 'prod');
        }else{
            //this function has been called
            return;
        }

        error_reporting(E_ALL | E_STRICT);
        if(APP_ENV === 'prod' || APP_ENV === 'uat' || APP_ENV === 'qa'){
            ini_set('display_errors',false);
        }else if(APP_ENV == 'vagrant' || APP_ENV == 'develop') {
            ini_set('display_errors',true);
        }else{
            // for now
            ini_set('display_errors',true);
        }
    }
}

if (! function_exists('array_to_csv')) {
    $functionVersions['array_to_csv']=2.10;
    function array_to_csv($array, $showHeaders=true, $options=array()){
        /**
         * 2018-07-29 <sfullman@presidio.com> - this is a function I have had/used for years
         *
         * 2012-07-05: initiated options:
         *      delimiter (default = `,`)
         *      lastCol=[int] - if the array has more columns than we want, truncate the right side
         *      firstCol=[int] - similarly, truncate the left side
         *      function=[trim] for example
         */

        extract($options);
        $function = !empty($function) ? $function : '';
        $trim = !isset($options['trim']) ? true : $options['trim'];     // default true
        $firstCol = !empty($options['firstCol']) ? $options['firstCol'] : 1;
        $lastCol = !empty($options['lastCol']) ? $options['lastCol'] : '';
        $suppressQuote = isset($options['suppressQuote']) ? $options['suppressQuote'] : false;

        if($trim && !$function) $function='trim';

        if(!isset($qt))$qt='"';
        if(!isset($escQt))$escQt=$qt.$qt; //double it
        if(!isset($delimiter))$delimiter=',';
        if(!isset($nl))$nl="\n";

        $i = 0;
        $output = '';
        foreach($array as $idx => $row){
            $i++;
            if($i === 1 && $showHeaders){
                //insert headers
                $j=0;
                foreach($row as $idx2 => $field){
                    $j++;
                    if($firstCol && $j < $firstCol) continue;
                    if(!empty($lastCol) && $j > $lastCol) break;
                    $hbuffer[] = ( is_numeric($idx2) || $suppressQuote ? $idx2 : $qt.str_replace($qt,$escQt,$idx2).$qt );
                }
                $output .= implode($delimiter,$hbuffer).$nl;
            }
            if($i > 1) $output.=$nl;
            $buffer = [];

            $j=0;
            foreach($row as $idx2=>$field){
                $j++;
                if($firstCol && $j < $firstCol) continue;
                if($lastCol && $j > $lastCol) break;
                //this can be used to trim
                if($function) $field=$function($field);
                $buffer[] = ( is_numeric($field) || $suppressQuote ? $field : $qt.str_replace($qt,$escQt,$field).$qt );
            }
            $output .= implode($delimiter,$buffer);
        }
        return $output;
    }
}

if (! function_exists('enhanced_mail')) {
    function enhanced_mail($options){
        /*
         * Original parameters:
         * to, subject, body, from, mode, attachments, important, preHeaders, postHeaders, output, fSwitchEmail
         */
        //
        $enhanced_mail = [
            'idx' => 0,
            'errors' => '',
            'log_mail' => true,
        ];

        $enhanced_mail['errors'] = '';      //unset

        extract($options);
        if(empty($output)) $output='mail';
        if(empty($mode)) $mode='html';
        if(!isset($emTestAction)) $emTestAction = '';

        if(empty($to)){
            $enhanced_mail['errors']['input'] = 'No value passed for $to!';
            return false;
        }

        if(empty($from)){
            $enhanced_mail['errors']['input'] = 'No value passed for $from';
            return false;
        }

        if(!isset($fSwitchEmail)){
            $a = explode('<', rtrim($from, '>'));
            if(count($a) > 1){
                $fSwitchEmail = $a[1];
            }else{
                $fSwitchEmail = $a[0];
            }
        }

        if(!isset($subject)) $subject = ''; //this is allowed

        //2010-02-22: get log_mail var, true=log send in the database
        if(isset($log_mail)){
            //OK
        }else{
            $log_mail=$enhanced_mail['log_mail'];
        }

        $mode=strtolower($mode);

        //mime types, incomplete list
        $mimeTypes = [
            /*********** NOT SEPCIFIED **********/
            '(unspecified)' => 'application/octet-stream',

            /*********** basic files **********/
            'txt' => 'text/plain',
            'php' => 'application/octet-stream',
            'htm' => 'text/html',
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/octet-stream',
            'csv' => 'text/csv',

            /*********** image files **********/
            'gif' => 'image/gif',
            'jpg' => 'image/jpg',
            'jpe' => 'image/jpg',
            'png' => 'image/png',
            'tif' => 'image/tiff',
            'tiff' => 'image/tiff',
            // https://stackoverflow.com/questions/11918977/right-mime-type-for-svg-images-with-fonts-embedded
            'svg' => 'image/svg+xml',

            /*********** applications **********/
            'xls' => 'application/vnd.ms-excel',
            'doc' => 'application/msword',
            'mov' => 'video/quicktime',
            'pdf' => 'application/pdf',
            'exe' => 'application/msdownload'
        ];
        //for future encoding for quoted-printable
        $quotedPrintable=array('txt','php','htm','html','css','js');

        //mime boundary
        $mime_boundary = "==Multipart_Boundary_x".md5(time())."x";

        //attachments
        if(!empty($attachments)){
            global $fileArrayName;
            $i = 0;
            if(is_array($attachments)){
                $fArray=$attachments;
            }else{
                $fArray[]=$attachments;
            }
            //now set everything in array
            foreach($fArray as $v){
                //filter blank values
                if(!trim($v))continue;
                $i++;
                #get file name and path
                if(strstr($v,'/')){
                    $g=strrpos($v,'/');
                    $fileName=substr($v,$g+1,strlen($v)-$g);
                    $path=substr($v,0,$g);
                }else{
                    $fileName=$v;
                }
                #get extension
                $h=strrpos($fileName,'.');

                if(strlen($h)){ //it can even be at position zero
                    $ext=substr($fileName,-(strlen($fileName)-$h-1));
                }else{
                    $ext='(unspecified)';
                }

                //NOTE: added for the send_mail final script (note $fileArrayName) --------------
                $fileAttachments[$i]['name'] = ($fileArrayName[$v] ? $fileArrayName[$v] : $fileName) ;

                //NOTE: added for the send_mail final script --------------
                if(trim($fileArrayName[$v])!=''){
                    $a=explode('.', $fileArrayName[$v]);
                    $ext=$a[count($a)-1];
                }

                $fileAttachments[$i]['ext']  = $ext;
                // https://stackoverflow.com/questions/12539058/is-there-a-default-mime-type
                $fileAttachments[$i]['type'] = empty($mimeTypes[$ext]) ? 'application/octet-stream' : $mimeTypes[$ext];
                $fileAttachments[$i]['path'] = $path;
                $fileAttachments[$i]['full'] = $v;

                if(empty($enhanced_mail['fileMemory'][$fileAttachments[$i]['full']])){
                    ob_start();
                    $fp=@fopen($v,'r');
                    $data=@fread($fp,filesize($v));
                    $err=ob_get_contents();
                    ob_end_clean();
                    /* not doing this for now - this is an error state but one from which we could still send emails
                    if($err)mail($developerEmail, 'Error file '.__FILE__.', line '.__LINE__,get_globals('Unable to open file attachment '. $v),$fromHdrBugs);
                    */
                    $data = chunk_split(base64_encode($data));
                    @fclose($fp);
                    $fileAttachments[$i]['data'] = $data;
                    $enhanced_mail['fileMemory'][$fileAttachments[$i]['full']]=$data;
                }else{
                    $fileAttachments[$i]['data'] = $enhanced_mail['fileMemory'][$fileAttachments[$i]['full']];
                }
            }
        }

        /* STEP #1. Add the headers for a file attachment type email -- appears to work OK if no file attachment as well.


        */
        $headers=''; //initial condition
        if(!empty($preHeaders)){
            //postHeaders can be multi-line but should carry their own \r\n between lines
            $headers .= trim($preHeaders) . "\r\n";
            if(preg_match('/reply-to:(.+)/i', $preHeaders, $a)){
                $replyTo = trim($a[1]);
            }
        }
        $headers .= "From: ".preg_replace('/^From:\s+/i','',trim($from))."\r\n";
        $headers .= "MIME-Version: 1.0\r\n" .
            "Content-Type: multipart/mixed;\r\n" .
            " boundary=\"{$mime_boundary}\"\r\n";
        if(!empty($important)){
            $headers .= "X-Priority: 1 (Highest)\r\n";
            $headers .= "X-MSMail-Priority: High\r\n";
            $headers .= "Importance: High\r\n";
        }
        $headers .= "X-Mailer: PHP/" . phpversion(). "\r\n";
        if(!empty($postHeaders)){
            //postHeaders can be multi-line but should carry their own \r\n between lines
            $headers .= trim($postHeaders) . "\r\n";
            if(preg_match('/reply-to:(.+)/i', $postHeaders, $a)){
                $replyTo = trim($a[1]);
            }
        }

        // STEP #2. add a multipart boundary above the plain message
        $content = "This is a multi-part message in MIME format.\n\n" .
            "--{$mime_boundary}\n" .
            "Content-Type: text/$mode; charset=\"iso-8859-1\"\n" .
            "Content-Transfer-Encoding: 7bit\n\n" .
            $body . "\n\n";

        // STEP #3. add file attachments to the message
        if(!empty($fileAttachments)){
            foreach($fileAttachments as $index=>$v){
                $content .=  "--{$mime_boundary}\n" .
                    "Content-Type: {$v['type']};\n" .
                    " name=\"{$v['name']}\"\n" .
                    "Content-Disposition: attachment;\n" .
                    " filename=\"{$v['name']}\"\n" .
                    "Content-Transfer-Encoding: base64\n\n" .
                    $v['data'] . "\n\n";
            }
        }

        // STEP #4. cap the message off - key is the '--' at the end
        $content .=  "--{$mime_boundary}--";

        // STEP #5. testing parameters
        $outcome = '';
        $shunt = '';
        if($emTestAction){
            if(preg_match('/^returnParams(All)*/', $emTestAction, $a)){
                //add to return response; we assume that we've passed a single argument to the function as an array
                $response['args']=func_get_arg(0);
                if(empty($a[1])) unset($response['args']['body']);

                $outcome='testing, action='.$emTestAction;
            }else if(preg_match('/^shunt=/',$emTestAction)){
                $shunt=preg_replace('/^shunt=/','',$emTestAction);
            }
        }

        // STEP #6. Send the email
        $err = '';
        if(!$outcome){

            if($output === 'queue'){
                $outcome = 'queue';
                $response['queue'] = [
                    'send_to' => $shunt ? $shunt : $to,
                    'subject' => $subject,
                    'content' => $content,
                    'headers' => $headers,
                    'f_switch_email' => $fSwitchEmail,
                    //queue_time should default to now in data model
                ];
            }else{
                ob_start();
                if(mail(
                    $shunt ? $shunt : $to,
                    $subject,
                    $content,
                    $headers,
                    !empty($fSwitchEmail) ? "-f $fSwitchEmail" : ''
                )) $outcome = 'mail';
                //todo: elephant remove this
                $outcome = 'mail';
                $err=ob_get_contents();
                ob_end_clean();
            }
        }
        if(!$outcome) {
            $enhanced_mail['errors']['send'] = $err;
            return false;
        }else if($err){
            $enhanced_mail['errors']['unspecified'] = $err;
            return false;
        }else{
            $enhanced_mail['idx']++;
            $response['idx']=$enhanced_mail['idx'];
            $response['sendtime']=time();
            $response['sent']=$outcome;

            if($log_mail && !preg_match('/testing/',$outcome)){
                $a = explode('<', rtrim($to,'>'));
                if(count($a) > 2){
                    //error condition but we go with it
                    $MailedToName = '';
                    $MailedToEmail = $to;
                }else if(count($a) === 2){
                    $MailedToName = trim($a[0]);
                    $MailedToEmail = trim($a[1]);
                }else{
                    $MailedToName = '';
                    $MailedToEmail = $to;
                }

                $response['log_mail'] = [
                    'mailed_to_name' => empty($MailedToEmail) ? NULL : $MailedToName,
                    'mailed_to_email' => empty($MailedToEmail) ? NULL : $MailedToEmail,
                    'shunted_to_email' => empty($shunt) ? NULL : $shunt,
                    'mailed_by' => $from,
                    'subject' => !isset($subject) ? '' : $subject,
                    'content' => $content,
                    'headers' => $headers,
                    'from_as' => !isset($fSwitchEmail) ? NULL : $fSwitchEmail,
                    'reply_to' => empty($replyTo) ? '' : $replyTo,
                    'send_method' => ($mode=='plain' || $mode=='plaintext' ? 'Plaintext' : 'HTML'),
                    'attachments' => implode("\n", empty($fArray) ? [] : $fArray),
                    'notes' => empty($maillogNotes) ? '' : $maillogNotes
                ];
            }
            return $response;
        }
    }
}

if (! function_exists('subkey_sort')) {
    function subkey_sort($a, $key, $options = []){
        /*
        //2013-02-14: v3.00; allowing now for multiple subkey sorts
            subkey_sort($names, $key=array(LastName, FirstName), $options);
            function will sort by the last key, remove it and then call itself again :)

        //2010-06-13: v2.03; bugger didn't work in php 5 due to the behavior of array_merge()
        //2009-12-31: v2.02; had an error in thought, divided into TWO vars
            sort: asc and desc
            sortType: standard and natural(default)
        //2009-08-17: v2.01;
        #changed default sort to natural
        //2008-09-29: ability to reindex the array from start value of 1
        //must have array with values
        */

        //grab thisKey as last element
        if(!is_array($key))$key = [$key];
        $thisKey = array_pop($key);
        if(!empty($key)) $reindex=true;

        //handle 3rd-parameter=sort legacy
        if(!is_array($options)){
            $sort=$options;
        }else{
            extract($options);
        }
        if(empty($sort)) $sort='asc';
        if(empty($sortType)) $sortType='natural';



        if(!$a || !is_array($a) || !count($a)) return $a;
        $append=array();
        $pad=strlen(count($a));
        $i = 0;
        foreach($a as $n=>$v){
            $i++;
            $_n[$i]=$n; $_v[$i]=$v;
            if(!$v[$thisKey]){
                //for nodes without the subkey - this may not be desirable - same as sorting by alpha, blank values show first
                $append[$n]=$v;
                continue;
            }
            $ref[$i] = strtolower($v[$thisKey]) . (empty($suppressSortFix) ? '' : '-' . str_pad($n,$pad,'0',STR_PAD_LEFT));
        }
        $sort=strtolower($sort);
        if($sort!=='desc' && $sort!=='descending' && $sort!==-1){
            if($sortType === 'standard'){
                asort($ref);
            }else{
                @natcasesort($ref);
            }
        }else{
            if($sortType === 'standard'){
                arsort($ref);
            }else{
                @natcasesort($ref);
                #print_r($ref);
                //1. keep in this order
                foreach($ref as $n => $v){
                    $z[]=array($n,$v);
                }
                //2. resort
                for($i=count($z)-1; $i>=0; $i--){
                    $y[]=$z[$i];
                }
                //3. rebuild
                unset($ref);
                foreach($y as $v)$ref[$v[0]]=$v[1];
            }
        }
        ob_start();
        if(!empty($ref)) foreach($ref as $n=>$v)  $b[$_n[$n]]=$_v[$n];
        $err=ob_get_contents();
        ob_end_clean();

        if(count($append)){
            if($sort!=='desc' && $sort!=='descending' && $sort!==-1){
                if(count($b)){
                    //main b array gets added on to append
                    foreach($b as $n=>$v) $append[$n]=$v;
                }
                $b=$append;
            }else{
                foreach($append as $n=>$v) $b[$n]=$v;
            }
        }
        if(!empty($reindex)){
            $i=0;
            foreach($b as $v){
                $i++;
                $c[$i]=$v;
            }
            $b=$c;
        }
        if(empty($key))return $b;
        return subkey_sort($b,$key,$options);
    }
}

if (! function_exists('date_time_component')) {
    function date_time_component($date){
        // https://stackoverflow.com/questions/52700901/php-distinguish-between-only-date-or-only-time-as-well-as-both
        // if(strtotime($date) === false) return false; - let's assume only valid dates for speed
        if(strtotime($date, 86400) !== strtotime($date, 86400 * 3)) return 'time';
        if(strstr($date, ':')) return 'datetime';
        return 'date';
    }
}

if (! function_exists('valid_ips')) {
    function valid_ips($ips){
        //validate potentially multiple IP addresses
        if(!strlen($ips)) return false;
        foreach(preg_split('/[ ,\n\r\t]+/i', $ips) as $ip){
            if(!filter_var($ip, FILTER_VALIDATE_IP)) return false;
        }
        return true;
    }
}
