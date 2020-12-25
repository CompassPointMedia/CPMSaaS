<?php namespace App\Controllers;

/**
 * @todo:
 * work on the timeout logic
 * we want to log our queries so we get an idea of what's slow
 * curl calls - build them into CI - there must be a model
 *
 * pull everything from the procedural automation app and put it into public
 * commonize the passwords
 * commonize the security piece
 *
 *
 * Q: how do we log and what did I do at Kyra that was an improvement
 *
 *
 */

class Data extends BaseController {

    public function request($dataGroup){

        // test long-loads
        // sleep(5);

        $data = new \Data_model();
        //$this->load->model('Data_model');

        $data->inject($dataGroup);

        //CI3
        //$request = $this->input->post();

        $request = $this->request->getPost();

        $results = $data->request($request);

        $status_header = empty($results['status_header']) ? 200 : $results['status_header'];


        //header('Access-Control-Allow-Origin: *');

        return $this->output
            ->set_content_type('application/json')
            ->set_status_header($status_header)
            ->set_output(json_encode(
                $results
            ));
    }

    public function update($dataGroup){

        $this->load->model('Data_model');
        $update = new \Data_model();

        $update->inject($dataGroup);

        $request = $this->input->post();

        $changes = $update->update($request);

        $status_header = empty($changes['status_header']) ? 200 : $changes['status_header'];

        return $this->output
            ->set_content_type('application/json')
            ->set_status_header($status_header)
            ->set_output(json_encode(
                $changes
            ));
    }

    public function insert($dataGroup){

        //header('Access-Control-Allow-Origin: *');

        $this->load->model('Data_model');
        $insert = new \Data_model();

        $insert->inject($dataGroup);

        $request = $this->input->post();

        $changes = $insert->insert($request);

        $status_header = empty($changes['status_header']) ? 200 : $changes['status_header'];

        return $this->output
            ->set_content_type('application/json')
            ->set_status_header($status_header)
            ->set_output(json_encode(
                $changes
            ));
    }

    public function file(){

        /* temp function needs replaced by C# */

        //header('Access-Control-Allow-Origin: *');
        $file = $this->input->post();

        $contents = $file['file_contents'];

        $this->load->model('Data_model');
        $insert = new \Data_model();
        $insert->inject('exelon-macs');
        $date_added = date('Y-m-d H:i:s');
        if($file['mac_address']){
            $result = $insert->inject([
                'mac_address' => $file['mac_address'],
                'date_added' => $date_added,
                'added_by' => 'sfullman',
                'comments' => 'Single MAC added via Emma',
            ], ['force_insert' => true]);
        }else{
            $str = $file['file_contents'];
            $str = preg_split('/[\n\r]+/', $str);
            $i = 0;
            foreach($str as $v){
                $i++;
                if($i === 1) continue;
                if(!filter_var($v, FILTER_VALIDATE_MAC)){
                    continue;
                }
                $result = $insert->insert(
                    [ 'mac_address' => trim($v),
                        'date_added' => $date_added,
                        'added_by' => 'sfullman',
                        'comments' => 'Bulk MAC added via Emma',
                    ], ['force_insert' => true]);
            }
        }


        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode(
                $result
            ));

    }

    public function delete($dataGroup){


        //header('Access-Control-Allow-Origin: *');

        $this->load->model('Data_model');
        $insert = new \Data_model();

        $insert->inject($dataGroup);

        $delete = $this->input->post();

        $changes = $insert->delete($delete);

        $status_header = empty($changes['status_header']) ? 200 : $changes['status_header'];

        return $this->output
            ->set_content_type('application/json')
            ->set_status_header($status_header)
            ->set_output(json_encode(
                $changes
            ));
    }

    public function fieldsJson(){
        $this->load->model('Data_model');
        $fields = new \Data_model();

        $request = $this->input->get();

        if(!$request) exit('Specify a table');

        $str = 'var focus = {'."\n";
        $structure = $fields->structure($request['db'] . '.' . $request['table']);


        foreach($structure as $n=>$null){
            $str .= '    '.$n.': "",'."\n";
        }
        $str .= '}';

        echo $str;
    }

    public function clearNullStrings(){
        $this->load->model('Data_model');
        $fields = new \Data_model();

        $request = $this->input->get();

        if(!$request) exit('Specify a table');

        $str_a = 'UPDATE `'.$request['db'].'`.`'.$request['table'].'` SET ' . "\n";
        $str_b = '';
        $structure = $fields->structure($request['db'] . '.' . $request['table']);


        foreach($structure as $field=>$null){
            $str_b .= ($str_b ? ",\n" : '') . "`". $field . "` = IF(`" . $field . "` = 'NULL', NULL, `" . $field . "`)";
        }
        echo $str_a . $str_b;
    }


    public function create_table_from_csv($table, $file, $db = 'Infrastructure'){
        /**
         * 2018-10-26 <sfullman@presidio.com> This now does a pretty good job of creating a MYSQL table from CSV data, and indexes columns where there's not that much variety in the fields.
         */

        //exit('legacy code to import table from CSV file' . "\n");

        // - now passed as param; not needed - $table = 'cmdb_ips';

        set_time_limit(30 * 60);
        $this->load->model('Data_model');
        $data = new \Data_model();
        $cnx = $data->cnx;

        // - now passed as param; not needed - $file = (APPPATH  . '../../tmp/IPs.csv');
        $result = $data->find_minimum_fitting_structure($file, [
            'date_microtimes' => true,
            'sample_size' => 5000,
        ]);

        $cnx->query("DROP TABLE IF EXISTS `$db`.`$table`");

        $str = "CREATE TABLE `$db`.`$table`(\n";
        $str .= "  ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, \n";
        if(!is_cli()) echo '<pre>';
        echo 'start at ' . round($start = microtime(true), 5) . "s\n";
        echo 'analysis elapsed: ' . $result['elapsed'];
        echo "\n";
        foreach($result['structure'] as $n => $v){
            $str .= "  ". $v['name'];
            if($v['type'] === 'datetime'){
                $str .= ' DATETIME NULL DEFAULT NULL';
            }else if($v['type'] === 'char'){
                $str .= ' CHAR(' . $v['_maxlength'] . ')' . ($v['null'] ? ' NULL' : '');
            }else{
                $str .= ' CHAR(255)';
            }
            $str .= "," . (!empty($v['values']) && count($v['values']) < 21 ? '/* '. count($v['values']) . ' distinct' . ' */' : '') . "\n";
        }
        foreach($result['structure'] as $n => $v){
            if(isset($v['values']) && count($v['values']) < 21){
                $str .= "  INDEX(" . $v['name'] . "),\n";
            }
        }
        $str = rtrim($str, ",\n") . "\n";
        $str .= ")ENGINE = myISam;";
        print_r($str);
        echo "\n";
        $said = '';

        $cnx->query($str);

        //run inserts
        $fp = fopen($file, 'r');
        $i = 0;
        $rand = md5(microtime(true));

        while($row = fgetcsv($fp)){
            $i++;
            if($i === 1){
                $header = $row;
                continue;
            }
            if(!fmod($i, 1000)){
                if(!$said){
                    echo $said = '(Each dot = 1000 records..)' . "\n";
                }
                echo '. ';
            }
            $sql = "INSERT INTO `$db`.`$table` SET ";
            foreach($row as $n=>$v){
                if($v === '\\' . 'N' || $v === '\\' . 'n') $v = $rand;
                $v = str_replace('00:00.000', '00:00', $v);
                if(strstr($v, '0000-00-00')) $v = $rand;
                $sql .= "\n" .
                    $header[$n] .
                    ' = ' .
                    ($v === $rand ? 'NULL' : "'" . str_replace("'", "\\'", $v) . "'") .
                    ',';
            }
            $sql = rtrim($sql, ',');
            //echo $sql . "\n";
            $cnx->query($sql);
        }
        fclose($fp);
        echo "\n" . 'end at ' . ($end = round(microtime(true) - $start, 5)) . 's'."\n";
    }

    public function stringTest(){

        /*
        $str = '--- BEGIN CHARACTER TEST MD5="b6cdebfa9ab06dbb46bb6863df015262" ---
___0:;1:;2:;3:;4:;5:;6:;7:;8:;9:	;10:
;11:;12:;13:
;14:;15:;16:;17:;18:;19:;20:;21:;22:;23:;24:;25:;26:;27:;28:;29:;30:;31:;32: ;33:!;34:";35:#;36:$;37:%;38:&;39:\';40:(;41:);42:*;43:+;44:,;45:-;46:.;47:/;48:0;49:1;50:2;51:3;52:4;53:5;54:6;55:7;56:8;57:9;58::;59:;;60:<;61:=;62:>;63:?;64:@;65:A;66:B;67:C;68:D;69:E;70:F;71:G;72:H;73:I;74:J;75:K;76:L;77:M;78:N;79:O;80:P;81:Q;82:R;83:S;84:T;85:U;86:V;87:W;88:X;89:Y;90:Z;91:[;92:\;93:];94:^;95:_;96:`;97:a;98:b;99:c;100:d;101:e;102:f;103:g;104:h;105:i;106:j;107:k;108:l;109:m;110:n;111:o;112:p;113:q;114:r;115:s;116:t;117:u;118:v;119:w;120:x;121:y;122:z;123:{;124:|;125:};126:~;127:;128:€;129:;130:‚;131:ƒ;132:„;133:…;134:†;135:‡;136:ˆ;137:‰;138:Š;139:‹;140:Œ;141:;142:Ž;143:;144:;145:‘;146:’;147:“;148:”;149:•;150:–;151:—;152:˜;153:™;154:š;155:›;156:œ;157:;158:ž;159:Ÿ;160: ;161:¡;162:¢;163:£;164:¤;165:¥;166:¦;167:§;168:¨;169:©;170:ª;171:«;172:¬;173:­;174:®;175:¯;176:°;177:±;178:²;179:³;180:´;181:µ;182:¶;183:·;184:¸;185:¹;186:º;187:»;188:¼;189:½;190:¾;191:¿;192:À;193:Á;194:Â;195:Ã;196:Ä;197:Å;198:Æ;199:Ç;200:È;201:É;202:Ê;203:Ë;204:Ì;205:Í;206:Î;207:Ï;208:Ð;209:Ñ;210:Ò;211:Ó;212:Ô;213:Õ;214:Ö;215:×;216:Ø;217:Ù;218:Ú;219:Û;220:Ü;221:Ý;222:Þ;223:ß;224:à;225:á;226:â;227:ã;228:ä;229:å;230:æ;231:ç;232:è;233:é;234:ê;235:ë;236:ì;237:í;238:î;239:ï;240:ð;241:ñ;242:ò;243:ó;244:ô;245:õ;246:ö;247:÷;248:ø;249:ù;250:ú;251:û;252:ü;253:ý;254:þ;255:ÿ;___
--- END CHARACTER TEST ---';
        */
        $str = '--- BEGIN CHARACTER TEST MD5="313be99bdf83d44856b8bef08ca862e7" ---
___65:A;66:B;67:C;___
--- END CHARACTER TEST ---';
        $str = preg_replace('/---[^-]+---/', '', $str);
        $str = trim($str);
        $str = ltrim($str, '_');
        $str = rtrim($str, '_');
        exit(md5($str));


        $str = '___';
        $md5Str = '';
        for($i = 65; $i<= 67; $i++){
            $str .= $i . ':' . ($i < 128 ? chr($i) : '&#'.$i . ';') . ';';
            $md5Str .= $i . ':' . chr($i) . ';';
        }
        $str .= '___';

        $str = '--- BEGIN CHARACTER TEST MD5="' .md5($md5Str) . '" ---' . "\n" .
            $str . "\n" .
            '--- END CHARACTER TEST ---' . "\n";
        echo '<meta http-equiv="content-type" content="text/html;charset=UTF-8" />';
        echo '<pre>';
        echo $str;
    }
}
