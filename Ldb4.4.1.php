<?php
/* Ldb4 class [4th Generation of Ldb]
 * Ldb stands for Luthfie database
 * Authored by 9r3i a.k.a. Luthfie a.k.a. Abu Ayyub
 * Author-Email: luthfie@y7mail.com
 * Author-URI: https://github.com/9r3i
 * Filename: Ldb4.4.1.php
 * 
 * Started on December 22nd 2014 - Finished on December 23rd 2014
 * Version: 4.0.0 Alpha
 * 
 * Started on January 29th 2015 - Finished on January 30th 2015
 * Version: 4.1.0
 * + change @dir input by regEx
 * + fix the @root
 * + add portable optional
 * + add table rows in Ldb_data object
 * + add info function
 * 
 * Started on February 9th 2015 - Finished on February 9th 2015
 * Version: 4.2.0
 * + fix error gathering by function
 * + fix droping table and database
 * + change php compare version
 * + fix function select output key location
 * 
 * Started on February 13th 2015 - Finished on February 13th 2015
 * Version: 4.3.0
 * + improve show database function
 * + improve show tables function
 * + improve select function
 * 
 * Started on February 16th 2015 - Finished on February 16th 2015
 * Version: 4.4.0
 * + create new function selector
 * + improve function update by selector
 * + improve function delete by selector
 * + add refetch selected data
 * 
 * Started on February 18th 2015 - Finished on February 19th 2015
 * Version: 4.4.1
 * + improve/rollback show tables function - follow PHP version 5.3.28
 * + improve/rollback show database function - follow PHP version 5.3.28
 * + improve Lo9 function
 * 
 * 
 * PHP version
 * Created at version 5.4.7
 * Compare to version 5.3.28
 * Tested to version 5.5.20
 * 
 * Instructions file: Ldb4-api.txt
 * License: license.txt
 */

class Ldb4{
  public $version = '4.4.1';
  public $access = 'Access is not allowed yet';
  public $status = 'Not connected';
  public $errors = array();
  public $error = false;     /* show the last error; default = false; */
  protected $hashdir;
  protected $database;
  protected $table_options = array();
  private $start;
  private $connection = false;
  /* public function the information of Ldb4 class */
  public function info($array=false){
    $content = @file_get_contents(__FILE__);
    $content = trim(substr($content,strpos($content,'/*'),strpos($content,'*/')-strpos($content,'/*')+2));
    $data = array(
      'class'=>'Ldb4',
      'version'=>$this->version,
      'description'=>$content,
      'file'=>array(
        'name'=>substr(str_replace(dirname(__FILE__),'',__FILE__),1),
        'size'=>filesize(__FILE__),
        'type'=>'application/x-httpd-php',
        'content'=>$this->is_connected()?@file_get_contents(__FILE__):false,
      ),
      'php_version'=>PHP_VERSION,
      'memory_limit'=>ini_get('memory_limit'),
      'apache'=>function_exists('apache_get_version')?apache_get_version():false,
    );
    if($array){
      return $data;
    }else{
      return json_decode(json_encode($data));
    }
  }
  function __construct($dir=null,$portable=false){
    $compare = '5.3.28';
    if(version_compare(PHP_VERSION,$compare,'>=')){
      $this->start = microtime(true);
      $this->setting($dir);
    }else{
      $error = 'PHP version doesn\'t compare for Ldb4 class <br />'.PHP_EOL;
      $error .= 'Ldb4 compare to PHP version '.$compare.' or greater <br />'.PHP_EOL;
      $error .= 'Your PHP version is: '.PHP_VERSION;
      $this->error($error);
      die($error);
    }
    $memory_limit = ini_get('memory_limit');
    $mlimit = 100;
    if(preg_replace('/[^\d]+/i','',$memory_limit)<$mlimit){
      $error = 'The memory limit is not compatible for Ldb4 class <br />'.PHP_EOL;
      $error .= 'You need more space of memory to use the class, at least '.$mlimit.' MB or higher <br />'.PHP_EOL;
      $error .= 'Or you can set the limit by using ini_set(\'memory_limit\',\'128M\'); <br />'.PHP_EOL;
      $this->error($error);
      die($error);
    }
  }
  /* public functions without connection */
  public function default_primary_keys(){
    return array('AID','CID','PID','TIMESTAMP');
  }
  public function default_column_values(){
    return array('AID','CID','PID','DATE','TIME','DATETIME','TIMESTAMP');
  }
  public function create_db($db_name=null,$db_user=null,$db_pass=null){
    if(isset($db_name)&&preg_match('/^\w+$/i',$db_name,$akur)){
      if(isset($db_user,$db_pass)){
        if(preg_match('/^[a-z0-9]+$/i',$db_user)){
          if(isset($akur[0])&&!is_dir($this->dir().$akur[0])){
            mkdir($this->dir().$akur[0]);
            $this->htaccess($this->dir().$akur[0]);
            $data = array($db_user=>$this->hash($db_pass));
            $data_encode = $this->encode($data);
            if($data_encode){
              $this->write($this->dir().$akur[0].'/db_option.ldb',$data_encode);
              return true;
            }else{
              $this->error('Cannot encode database option data');
              return false;
            }
          }else{
            $this->error('Database has been existed');
            return false;
          }
        }else{
          $this->error('Database username format must be alphanumeric only');
          return false;
        }
      }else{
        $this->error('Database requires username and password to create one');
        return false;
      }
    }else{
      $this->error('Cannot create the database, probably the characters of database name didn\'t match to \w+');
      return false;
    }
  }
  public function connect($db_name=null,$db_user=null,$db_pass=null){
    if(isset($db_name,$db_user,$db_pass)){
      if(is_dir($this->dir().$db_name)){
        $option = $this->db_option($db_name);
        if(isset($option[$db_user])&&$option[$db_user]==$this->hash($db_pass)){
          $this->database = $db_name;
          $this->access = 'Access is granted';
          $this->status = 'Connected';
          $this->connection = true;
          return true;
        }else{
          $this->error('Database requires username and password');
          $this->access = 'Access has been denied';
          return false;
        }
      }else{
        $this->error('Database doesn\'t exist');
        return false;
      }
    }else{
      $this->error('Requires database name, database username and database password');
      return false;
    }
  }
  /* public function with connection requires */
  public function close(){
    if($this->is_connected()){
      $this->access = 'Access has beed closed';
      $this->status = 'Disconnected';
      $this->closed = microtime(true);
      $this->connection = false;
      foreach($GLOBALS as $key=>$value){
        if(is_object($value)&&isset($value->closed,$this->closed)&&$value->closed==$this->closed){
          unset($GLOBALS[$key]->connection);
          unset($GLOBALS[$key]->hashdir);
          unset($GLOBALS[$key]->database);
          unset($GLOBALS[$key]->table_options);
          unset($GLOBALS[$key]->start);
          break;
        }
      }
    }
    return true;
  }
  public function show_database(){
    if($this->is_connected()){
      $scan = @scandir($this->dir());
      $result = array();
      if(is_array($scan)){foreach($scan as $file){
        if(is_dir($this->dir().$file)&&preg_match('/^\w+$/i',$file)){
          $result[] = $file;
        }
      }}
      return $result;
    }else{
      $this->error('Access is denied to show database');
      return false;
    }
  }
  public function drop_database(){
    if($this->is_connected()){
      $force_delete = $this->delete_dir($this->dir().$this->database);
      return $this->close();
    }else{
      $this->error('Access is denied to drop database');
      return false;
    }
  }
  /* table functions */
  public function show_tables(){
    if($this->is_connected()){
      $scan = @scandir($this->dir().$this->database);
      $result = array();
      if(is_array($scan)){foreach($scan as $file){
        if(is_dir($this->dir().$this->database.'/'.$file)&&preg_match('/^\w+$/i',$file)){
          $result[] = $file;
        }
      }}
      return $result;
    }else{
      $this->error('Access is denied to show database tables');
      return false;
    }
  }
  public function create_table($table_name=null,$column=array(),$primary_key='AID',$options=array()){
    if($this->is_connected()){
      if(isset($table_name)&&preg_match('/^\w+$/i',$table_name,$akur)&&isset($akur[0])){
        if(!is_dir($this->dir().$this->database.'/'.$table_name)){
          $table_dir = $this->dir().$this->database.'/'.$table_name;
          mkdir($table_dir);
          $this->htaccess($table_dir);
          $data = array(
            'AID'=>0,
            'column'=>$this->default_column($column),
            'primary_key'=>(in_array($primary_key,$column)&&in_array($primary_key,$this->default_primary_keys())?$primary_key:null),
            'options'=>$options,
          );
          $this->table_options[$table_name] = $data;
          $table_data_dir = $this->dir().$this->database.'/'.$table_name.'/data';
          if(!is_dir($table_data_dir)){
            mkdir($table_data_dir);
            $this->htaccess($table_data_dir);
          }
          return $this->write_table_option($table_name,$data);
        }else{
          $error = 'Table '.$table_name.' is existed';
          $this->errors[] = $error;
          $this->error = $error;
          return false;
        }
      }else{
        $this->error('Cannot create the table, probably the characters of database name didn\'t match to \w+');
        return false;
      }
    }else{
      $this->error('Access is denied to create a table');
      return false;
    }
  }
  public function alter_table($table_name=null,$column=array(),$primary_key=null){
    if($this->is_connected()){
      if(isset($table_name)&&is_dir($this->dir().$this->database.'/'.$table_name)){
        $table_option = $this->table_option($table_name);
        $default_column = $this->default_column($column);
        if(isset($table_option['column'])){
          $table_option['column'] = $default_column;
        }
        if(isset($primary_key,$table_option['primary_key'])&&in_array($primary_key,$default_column)&&in_array($primary_key,$this->default_primary_keys())){
          $table_option['primary_key'] = $primary_key;
        }
        $this->write_table_option($table_name,$table_option);
        return $this->alter_table_data($table_name);
      }else{
        $this->error('Table '.$table_name.' doesn\'t exist');
        return false;
      }
    }else{
      $this->error('Access is denied to alter the table');
      return false;
    }
  }
  public function drop_table($table_name=null){
    if($this->is_connected()){
      if(isset($table_name)&&is_dir($this->dir().$this->database.'/'.$table_name)){
        $force_delete = $this->delete_dir($this->dir().$this->database.'/'.$table_name);
        unset($this->table_options[$table_name]);
        return true;
      }else{
        $this->error('Table '.$table_name.' doesn\'t exist');
        return false;
      }
    }else{
      $this->error('Access is denied to drop the table');
      return false;
    }
  }
  /* column function */
  public function show_columns($table_name=null){
    if($this->is_connected()){
      if(isset($table_name)&&is_dir($this->dir().$this->database.'/'.$table_name)){
        $table_option = $this->table_option($table_name);
        if(isset($table_option['column'])){
          return array_keys($table_option['column']);
        }else{
          $this->error('The table doesn\'t have a column');
          return false;
        }
      }else{
        $this->error('Table '.$table_name.' doesn\'t exist');
        return false;
      }
    }else{
      $this->error('Access is denied to show the table columns');
      return false;
    }
  }
  public function insert($table_name=null,$data=array()){
    if($this->is_connected()){
      if(isset($table_name)&&is_dir($this->dir().$this->database.'/'.$table_name)){
        $data_filter = $this->filter_insert($table_name,$data);
        return $this->write_table_data($table_name,$data_filter);
      }else{
        $this->error('Table '.$table_name.' doesn\'t exist');
        return false;
      }
    }else{
      $this->error('Access is denied to insert data');
      return false;
    }
  }
  public function update($table_name=null,$where=null,$data=array()){
    if($this->is_connected()){
      if(isset($table_name)&&is_dir($this->dir().$this->database.'/'.$table_name)){
        if(isset($where)){
          $prime_keys = $this->selector($table_name,$where,false);
          if(is_array($prime_keys)){
            foreach($prime_keys as $key){
              $original_data = $this->table_data($table_name,$key);
              if($original_data){
                $data_filter = $this->filter_update($table_name,$data,$original_data);
                return $this->update_table_data($table_name,$key,$data_filter);
              }
            }
          }else{
            $this->error('Error selector return non-array value');
            return false;
          }
        }else{
          $this->error('No specific data location');
          return false;
        }
      }else{
        $this->error('Table '.$table_name.' doesn\'t exist');
        return false;
      }
    }else{
      $this->error('Access is denied to update data');
      return false;
    }
  }
  public function delete($table_name=null,$where=null){
    if($this->is_connected()){
      if(isset($table_name)&&is_dir($this->dir().$this->database.'/'.$table_name)){
        if(isset($where)){
          $prime_keys = $this->selector($table_name,$where,false);
          if(is_array($prime_keys)){
            foreach($prime_keys as $key){
              if(is_file($this->dir().$this->database.'/'.$table_name.'/data/'.$key.'.ldb')){
                @unlink($this->dir().$this->database.'/'.$table_name.'/data/'.$key.'.ldb');
                return true;
              }
            }
          }else{
            $this->error('Error selector return non-array value');
            return false;
          }
        }else{
          $this->error('No specific data location');
          return false;
        }
      }else{
        $this->error('Table '.$table_name.' doesn\'t exist');
        return false;
      }
    }else{
      $this->error('Access is denied to delete data');
      return false;
    }
  }
  public function select($table_name=null,$where=null,$options=null){
    if($this->is_connected()){
      if(isset($table_name)&&is_dir($this->dir().$this->database.'/'.$table_name.'/data/')){
        $data_dir = $this->dir().$this->database.'/'.$table_name.'/data/';
        $table_explore = $this->explore($data_dir,'f',false);
        $table_rows = preg_match_all('/\w+\.ldb/i',implode(';',$table_explore),$akur_file)?count($akur_file[0]):count($table_explore)-1;
        $option = $this->parse_option(isset($options)?$options:'start=0&limit=10');
        $r = 0;
        $start = isset($option['start'])?$option['start']:0;
        $limit = (isset($option['limit'])?$option['limit']:10)+$start;
        if(isset($where)){
          parse_str($where,$output);
          $outkey = array_keys($output);
          $table_option = $this->table_option($table_name);
          if(array_key_exists($outkey[0],$table_option['column'])&&is_file($data_dir.$output[$outkey[0]].'.ldb')){
            return new Ldb4_data(array($this->table_data($table_name,$output[$outkey[0]])),$this->start,$table_rows);
          }else{
            $scan = @scandir($data_dir);
            $result = array();
            if(is_array($scan)){
              $scan = isset($option['sort'])&&$option['sort']=='desc'?array_reverse($scan):$scan;
              $store = array();
              if(array_key_exists($outkey[0],$table_option['column'])){
                foreach($scan as $file){
                  if(substr($file,-4)=='.ldb'){
                    $content = $this->table_data($table_name,substr($file,0,-4));
                    if(isset($content[$outkey[0]])&&$content[$outkey[0]]==$output[$outkey[0]]){
                      $store[substr($file,0,-4)] = $content;
                    }
                  }
                }
              }
              array_splice($output,0,1);
              if(count($output)>0){
                foreach($output as $out=>$put){
                  if(array_key_exists($out,$table_option['column'])){
                    foreach($store as $id=>$content){
                      if(isset($content[$out])&&$content[$out]==$put){
                        if($r>=$start){
                          $result[$id] = $content;
                        }
                        $r++;
                      }else{
                        unset($store[$id]);
                      }
                      if($r>=$limit){break;}
                    }
                  }
                }
              }else{
                $result = array_slice($store,$start,$limit);
              }
            }
            $ksort = isset($option['sort'])&&$option['sort']=='asc'?ksort($result):false;
            return new Ldb4_data($result,$this->start,$table_rows);
          }
        }else{
          $scan = @scandir($data_dir);
          $result = array();
          if(is_array($scan)){
            $scan = isset($option['sort'])&&$option['sort']=='desc'?array_reverse($scan):$scan;
            foreach($scan as $file){
              if(substr($file,-4)=='.ldb'){
                if($r>=$start){
                  $content = @file_get_contents($data_dir.$file);
                  $result[substr($file,0,-4)] = $this->decode($content);
                }
                $r++;
              }
              if($r>=$limit){break;}
            }
          }
          $ksort = isset($option['sort'])&&$option['sort']=='asc'?ksort($result):false;
          return new Ldb4_data($result,$this->start,$table_rows);
        }
      }else{
        $this->error('Table '.$table_name.' doesn\'t exist');
        return false;
      }
    }else{
      $this->error('Access is denied to select data');
      return false;
    }
  }
  /* user functions */
  public function create_user($username=null,$password=null){
    if($this->is_connected()){
      $option = $this->db_option($this->database);
      if(isset($username,$password)&&preg_match('/^[a-z0-9]+$/i',$username,$match_user)&&isset($match_user[0])){
        if(!isset($option[$match_user[0]])){
          $data = array_merge((array)$option,(array)array($username=>$this->hash($password)));
          $data_encode = $this->encode($data);
          if($data_encode){
            $this->write($this->dir().$this->database.'/db_option.ldb',$data_encode);
            return true;
          }else{
            $this->error('Cannot encode database option data');
            return false;
          }
        }else{
          $this->error('Username has been used');
          return false;
        }
      }else{
        $this->error('Database username format must be alphanumeric only');
        return false;
      }
    }else{
      $this->error('Access is denied to create user');
      return false;
    }
  }
  public function delete_user($username=null){
    if($this->is_connected()){
      $option = $this->db_option($this->database);
      if(isset($username,$option[$username])){
        $keys = array_keys($option);
        if($username!==$keys[0]){
          unset($option[$username]);
          $data_encode = $this->encode($option);
          if($data_encode){
            $this->write($this->dir().$this->database.'/db_option.ldb',$data_encode);
            return true;
          }else{
            $this->error('Cannot encode database option data');
            return false;
          }
        }else{
          $this->error('Cannot delete the primary user');
          return false;
        }
      }else{
        $this->error('Username doesn\'t exist');
        return false;
      }
    }else{
      $this->error('Access is denied to delete user');
      return false;
    }
  }
  /* optional functions */ /* no connection requires */
  function hash($password='',$algo=5,$raw=false){
    $algos = hash_algos();
    $algo = ($algo<count($algos))?$algo:5;
    $hash = @hash($algos[$algo],$password,$raw);
    return $hash;
  }
  /* private functions */
  private function decode($data=null){
    if(isset($data)){
      return @json_decode(@base64_decode($this->Lo9($data,true)),true);
    }else{
      return false;
    }
  }
  private function encode($data=array()){
    if(is_array($data)){
      return $this->Lo9(@base64_encode(@json_encode($data,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)));
    }else{
      return false;
    }
  }
  private function write($filename=null,$content='',$type='wb'){
    if(isset($filename)){
      $fp = @fopen($filename,$type);
      if(flock($fp,LOCK_EX)){
        $write = @fwrite($fp,$this->strip_magic($content));
        flock($fp,LOCK_UN);
        if($write){
          return true;
        }else{
          return false;
        }
      }else{
        return false;
      }
      @fclose($fp);
    }else{
      return false;
    }
  }
  private function strip_magic($str){
    if(is_array($str)){
      $hasil = array();
	  foreach($str as $k=>$v){
        $hasil[$k] = (get_magic_quotes_gpc())?stripslashes($v):$v;
      }
      return $hasil;
	}else{
	  return (get_magic_quotes_gpc())?stripslashes($str):$str;
	}
  }
  private function Lo9($str=null,$decode=false){
    if(isset($str)){
      $Lo9 = 'Lo9JqHsF7uDwBy5AmOkQ3iSgUeW1cYa0ZbXdVf2ThRjP4lNzCx6EvGtI8rKpMn#|!';
      $alphanumeric = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ+/=';
      if($decode){
        return strtr($str,$Lo9,$alphanumeric);
      }else{
        return strtr($str,$alphanumeric,$Lo9);
      }
    }else{
      return false;
    }
  }
  private function check_speed($scope=3){
    $result = 0;
    $store = 0;
    foreach(range(1,100) as $r){
      $micro = number_format(microtime(true),$scope,'.','');
      $mac = $micro-$store;
      if($store==0){
        $store = $micro;
      }elseif($mac>0){
        break;
      }else{
        $result++;
      }
    }
    if($result>0){
      $scope++;
      return $this->check_speed($scope);
    }else{
      return $scope;
    }
  }
  private function pid($default=9){
    $micro = number_format(microtime(true),$this->check_speed($default),'.','');
    $explode = explode('.',$micro);
    if(isset($explode[0],$explode[1])){
      return implode('',array(($explode[0]-$this->basetime()),$explode[1]));
    }else{
      return $micro;
    }
  }
  private function explore($dir=null,$ex='a',$depth=true){
    $dir = isset($dir)&&is_dir($dir)?$dir:'.';
    $dir = substr($dir,-1)=='/'?substr($dir,0,-1):$dir;
    $scan = @scandir($dir);
    $result = array();
    foreach($scan as $file){
      $ff = $dir.'/'.$file;
      if(is_file($ff)){
        if($ex=='a'||$ex=='f'){
          $result[] = $ff;
        }
      }elseif(is_dir($ff)&&preg_match('/^\w+$/i',$file)){
        if($ex=='a'||$ex=='d'){
          $result[] = $ff.'/';
        }
        if($depth){
          $result[] = $this->explore($ff,$ex,$depth);
        }
      }
    }
    return $this->explore_wrap($result);
  }
  private function explore_wrap($data=array()){
    $result = array();
    if(is_array($data)){foreach($data as $dat){
      if(is_array($dat)){foreach($dat as $da){
        if(is_array($da)){
          $result[] = $this->explore_wrap($da);
        }else{
          $result[] = $da;
        }
      }}else{
        $result[] = $dat;
      }
    }}
    return $result;
  }
  private function delete_dir($data=array()){
    if(isset($dir)&&is_dir($dir)){
      $files = array_diff(@scandir($dir),array('.','..')); 
      foreach($files as $file){
        if(is_dir($dir.'/'.$file)&&!is_link($dir)){
          $this->delete_dir($dir.'/'.$file);
        }else{
          @unlink($dir.'/'.$file);
        }
      }
      return @rmdir($dir);
    }else{
      return false;
    }
  }
  private function basetime(){
    return 452373300;
  }
  private function cid(){
    $micro = number_format(microtime(true),9,'.','');
    $explode = explode('.',$micro);
    if(isset($explode[0],$explode[1])){
      $hex = dechex($explode[1]);
      $expo = substr((strlen($hex)<8?$hex.$hex:$hex),0,8);
      return implode('',array(dechex($explode[0]),$expo));
    }else{
      return dechex($explode[0]);
    }
  }
  private function parse_option($string=null){
    $default = array('key','order','sort','start','limit');
    $result = array();
    parse_str($string,$option);
    if(is_array($option)){foreach($option as $key=>$value){
       if(in_array($key,$default)){
         $result[$key] = $value;
       }
    }}
    return $result;
  }
  /* protected functions */
  protected function error($string=null){
    if(isset($string)){
      $this->errors[] = $string;
      $this->error = $string;
      return true;
    }else{
      return false;
    }
  }
  protected function selector($table_name=null,$where=null,$loadContent=false){
    if(isset($table_name,$where)&&is_dir($this->dir().$this->database.'/'.$table_name.'/data/')){
      $data_dir = $this->dir().$this->database.'/'.$table_name.'/data/';
      parse_str($where,$output); $outkey = array_keys($output);
      $scan = @scandir($data_dir);
      $table_option = $this->table_option($table_name);
      foreach($outkey as $outk){if(!array_key_exists($outk,$table_option['column'])){$error=true;break;}}
      $result = array();
      if(isset($error)){
        /* return no result or empty array */
      }elseif(is_array($output)&&count($output)==1&&is_file($data_dir.$output[$outkey[0]].'.ldb')){
        $result[] = $loadContent?$this->table_data($table_name,$output[$outkey[0]]):$output[$outkey[0]];
      }elseif(is_array($scan)&&is_array($output)){
        foreach($scan as $file){
          if(preg_match('/\.ldb$/i',$file)){
            $prime_key = substr($file,0,-4);
            $data = $this->table_data($table_name,$prime_key);
            if(is_array($data)){
              $store = array();
              foreach($output as $k=>$v){
                if(isset($data[$k])&&$data[$k]==$v){
                  $store[] = $loadContent?$data:$prime_key;
                }
              }
              if(count($store)>0&&count($store)==count($output)){
                $result[] = $store[0];
              }
            }
          }
        }
      }
      return $result;
    }
  }
  protected function filter_insert($table_name=null,$data=array()){
    $result = array();
    if(isset($table_name)&&is_array($data)){
      $table_option = $this->table_option($table_name);
      $default = $this->default_column_values();
      if(isset($table_option['column'])&&is_array($table_option['column'])){
        foreach($table_option['column'] as $key=>$value){
          if(isset($data[$key])&&!in_array($value,$default)){
            $result[$key] = $data[$key];
          }elseif(in_array($value,$default)){
            if($value=='AID'){
              $aid = $table_option['AID']; $aid++; $table_option['AID'] = $aid;
              $this->write_table_option($table_name,$table_option);
              $result[$key] = $aid;
            }elseif($value=='CID'){
              $result[$key] = $this->cid();
            }elseif($value=='PID'){
              $result[$key] = $this->pid();
            }elseif($value=='DATE'){
              $result[$key] = date('d-m-Y');
            }elseif($value=='TIME'){
              $result[$key] = date('H:i:s');
            }elseif($value=='DATETIME'){
              $result[$key] = date('d-m-Y H:i:s');
            }elseif($value=='TIMESTAMP'){
              $result[$key] = time();
            }
          }else{
            $result[$key] = $value;
          }
        }
      }
    }
    return $result;
  }
  protected function filter_update($table_name=null,$data=array(),$original_data=array()){
    $result = array();
    if(isset($table_name)&&is_array($data)&&is_array($original_data)){
      $table_option = $this->table_option($table_name);
      $default = $this->default_column_values();
      if(isset($table_option['column'])&&is_array($table_option['column'])){
        foreach($table_option['column'] as $key=>$value){
          if(isset($data[$key])&&!in_array($value,$default)){
            $result[$key] = $data[$key];
          }elseif(isset($original_data[$key])){
            $result[$key] = $original_data[$key];
          }else{
            $result[$key] = $value;
          }
        }
      }
    }
    return $result;
  }
  protected function default_column($column=array()){
    $result = array();
    $default = $this->default_column_values();
    if(is_array($column)){
      foreach($column as $key=>$value){
        if(preg_match('/^\w+$/i',$key)){
           $result[$key] = $value;
        }
      }
    }
    return $result;
  }
  protected function update_table_data($table_name=null,$primary_key=null,$data=array()){
    if(isset($table_name,$primary_key)&&is_file($this->dir().$this->database.'/'.$table_name.'/data/'.$primary_key.'.ldb')&&is_array($data)){
      $table_data_file = $this->dir().$this->database.'/'.$table_name.'/data/'.$primary_key.'.ldb';
      $data_encode = $this->encode($data);
      if($data_encode){
        return $this->write($table_data_file,$data_encode);
      }else{
        $this->error('Cannot encode the data');
        return false;
      }
    }else{
      $this->error('Table data doesn\'t exist or unexpected primary key');
      return false;
    }
  }
  protected function write_table_data($table_name=null,$data=array()){
    if(isset($table_name)&&is_dir($this->dir().$this->database.'/'.$table_name.'/data/')&&is_array($data)){
      $table_data_dir = $this->dir().$this->database.'/'.$table_name.'/data/';
      $table_option = $this->table_option($table_name);
      if(isset($table_option['primary_key'])&&in_array($table_option['primary_key'],$table_option['column'])){
        $flip = array_flip($table_option['column']);
        $prim = $flip[$table_option['primary_key']];
        $primary_key = isset($data[$prim])?$data[$prim]:$this->pid();
        if(!is_file($table_data_dir.$primary_key.'.ldb')){
          $data_encode = $this->encode($data);
          if($data_encode){
            return $this->write($table_data_dir.$primary_key.'.ldb',$data_encode);
          }else{
            $this->error('Cannot encode the data');
            return false;
          }
        }else{
          $this->error('The database data key has been used');
          return false;
        }
      }else{
        $this->error('Primary key is not found, the table must be in damage');
        return false;
      }
    }else{
      $this->error('Table data doesn\'t exist, the table must be in damage');
      return false;
    }
  }
  protected function alter_table_data($table_name=null){
    if(isset($table_name)&&is_dir($this->dir().$this->database.'/'.$table_name.'/data/')){
      $data_dir = $this->dir().$this->database.'/'.$table_name.'/data/';
      $table_option = $this->table_option($table_name);
      $default = $this->default_column_values();
      if(isset($table_option['primary_key'])&&in_array($table_option['primary_key'],$table_option['column'])){
        $scan = @scandir($data_dir);
        if(is_array($scan)){foreach($scan as $file){if(substr($file,-4)=='.ldb'){
            $table_data = $this->table_data($table_name,substr($file,0,-4));
            $new_data = array();
            foreach($table_option['column'] as $key=>$value){
              if(isset($table_data[$key])){
                $new_data[$key] = $table_data[$key];
              }elseif(in_array($value,$default)){
                if($value=='AID'){
                  $aid = $table_option['AID']; $aid++; $table_option['AID'] = $aid;
                  $this->write_table_option($table_name,$table_option);
                  $new_data[$key] = $aid; unset($aid);
                }elseif($value=='CID'){
                  $new_data[$key] = $this->cid();
                }elseif($value=='PID'){
                  $new_data[$key] = $this->pid();
                }elseif($value=='DATE'){
                  $new_data[$key] = date('d-m-Y');
                }elseif($value=='TIME'){
                  $new_data[$key] = date('H:i:s');
                }elseif($value=='DATETIME'){
                  $new_data[$key] = date('d-m-Y H:i:s');
                }elseif($value=='TIMESTAMP'){
                  $new_data[$key] = time();
                }
              }else{
                $new_data[$key] = $value;
              }
              if($table_option['primary_key']==$value){$primary_key = $new_data[$key];}
            }
            $primary_key = isset($primary_key)?$primary_key:$this->pid();
            $data_write = $this->write($data_dir.$primary_key.'.ldb',$this->encode($new_data));
            if($data_write&&$file!==$primary_key.'.ldb'){
              unlink($data_dir.$file);
            }
        }}}
      }else{
        $this->error('Primary key is not found, the table must be in damage');
        return false;
      }
    }else{
      $this->error('Table data doesn\'t exist, the table must be in damage');
      return false;
    }
  }
  protected function write_table_option($table_name=null,$data=array()){
    if(isset($table_name)&&is_dir($this->dir().$this->database.'/'.$table_name)&&is_array($data)&&isset($data['AID'],$data['column'],$data['primary_key'])){
      $data_encode = $this->encode($data);
      if($data_encode){
        $this->table_options[$table_name] = $data;
        $this->write($this->dir().$this->database.'/'.$table_name.'/table_option.ldb',$data_encode);
        return true;
      }else{
        $this->error('Cannot encode the table option');
        return false;
      }
    }else{
      $this->error('Table option doesn\'t exist, the table must be in damage');
      return false;
    }
  }
  protected function is_connected(){
    if(isset($this->connection)&&$this->connection===true){
      return true;
    }else{
      return false;
    }
  }
  protected function table_data($table_name=null,$primary_key=null){
    if(isset($table_name,$primary_key)&&is_file($this->dir().$this->database.'/'.$table_name.'/data/'.$primary_key.'.ldb')){
      $file = @file_get_contents($this->dir().$this->database.'/'.$table_name.'/data/'.$primary_key.'.ldb');
      $decode = $this->decode($file);
      return $decode;
    }else{
      $this->error('Table data doesn\'t exist or unexpected primary key');
      return false;
    }
  }
  protected function table_option($table_name=null){
    if(isset($table_name)&&is_file($this->dir().$this->database.'/'.$table_name.'/table_option.ldb')){
      if(isset($this->table_options[$table_name])){
        return $this->table_options[$table_name];
      }else{
        $file = @file_get_contents($this->dir().$this->database.'/'.$table_name.'/table_option.ldb');
        $decode = $this->decode($file);
        $this->table_options[$table_name] = $decode;
        return $decode;
      }
    }else{
      $this->error('Table option doesn\'t exist, the table must be in damage');
      return false;
    }
  }
  protected function db_option($db_name=null){
    if(isset($db_name)&&is_file($this->dir().$db_name.'/db_option.ldb')){
      $file = @file_get_contents($this->dir().$db_name.'/db_option.ldb');
      $decode = $this->decode($file);
      return $decode;
    }else{
      $this->error('Database option doesn\'t exist, the database must be in damage');
      return false;
    }
  }
  protected function htaccess($dir=null){
    if(isset($dir)&&is_dir($dir)){
      $dir = substr($dir,-1)=='/'?$dir:$dir.'/';
	  @chmod($dir,0700);
      if(!is_file($dir.'.htaccess')){
        $this->write($dir.'.htaccess','Options -Indexes'. PHP_EOL .'deny from all');
      }
      return true;
    }else{
      return false;
    }
  }
  protected function dir(){
    $_defined = get_defined_constants(true);
    if(isset($_defined['user']['LDB4_DIR_'.$this->hashdir])){
      return $_defined['user']['LDB4_DIR_'.$this->hashdir];
    }else{
      $root = str_replace('\\','/',dirname(__FILE__)).'/';
      return $root.'_Ldb4/';
    }
  }
  protected function setting($dir=null,$portable=false){
    $root = str_replace('\\','/',dirname(__FILE__)).'/';
    if($portable){
      $root = $root;
    }elseif(preg_match('/^[A-Z]{1}:\//i',$root)&&preg_match('/htdocs/i',$root)){
      $root = substr($root,0,strpos($root,'htdocs'));
    }elseif(preg_match('/^\/home\/\w+/i',$root,$akur)){
      $root = is_dir($akur[0])?$akur[0].'/':$root;
    }
    $dir = isset($dir)&&preg_match('/^\w+$/i',$dir)?$root.$dir.'/':$root.'_Ldb4/';
    $this->hashdir = $this->hash(number_format(microtime(true),9,'.',''));
    define('LDB4_DIR_'.$this->hashdir,$dir);
    if(!is_dir($dir)){
      mkdir($dir,0700);
    }
    $this->htaccess($dir);
  }
}

/* Default primary key
 * AID = (standard recommended - very good for selecting data)
 * CID = (recommended for protection data or encription data)
 * PID = default primary key (recommended for high speed cpu of db server)
 * TIMESTAMP = (not recommended - this good for low speed input only)
 * 
 */

/* Default column values
 * AID = auto increasement data (start from 1)
 * CID = code increasement data (hexa decimal - according to microtime)
 * DATE = date format d-m-Y
 * TIME = time from date format H:i:s
 * DATETIME = data format d-m-Y H:i:s
 * TIMESTAMP = timestamp from function time
 * NULL = nullified of NULL value ???
 * 
 */

/* Ldb4_data class
 * This is the data fetcher, while the data has no deal to fetch
 * Please read the Instructions file: readme.txt
 */
class Ldb4_data{
  public $error = false;
  private $hashdata;
  function __construct($data=array(),$start=null,$table_rows=null){
    $this->hashdata = md5(number_format(microtime(true),9,'.',''));
    if(is_array($data)&&!defined('LDB4_DATA_'.$this->hashdata)){
      define('LDB4_DATA_'.$this->hashdata,base64_encode(json_encode($data)));
      $this->rows = count($data);
      if(isset($table_rows)){
        $this->table_rows = $table_rows;
      }
      if(isset($start)){
        $this->process_time = number_format((microtime(true)-$start),3,'.','');
      }
    }else{
      $this->error = 'Error data';
    }
  }
  public function fetch_array($refetch=false){
    $data = array_values($this->data());
    $this->debt = isset($this->debt)?$this->debt:0;
    if(isset($data[$this->debt])){
      $result = $data[$this->debt];
      $this->debt++;
      return $result;
    }else{
      if($refetch){
        $this->debt = 0;
      }
      return false;
    }
  }
  private function data(){
    $_defined = get_defined_constants(true);
    if(isset($_defined['user']['LDB4_DATA_'.$this->hashdata])){
      return json_decode(base64_decode($_defined['user']['LDB4_DATA_'.$this->hashdata]),true);
    }else{
      return false;
    }
  }
}


/* Differential of public, private and protected function in class of PHP
 * public scope to make that variable/function available from anywhere, other classes and instances of the object.
 * private scope when you want your variable/function to be visible in its own class only.
 * protected scope when you want to make your variable/function visible in all classes that extend current class including the parent class.
 * 
 */

/* Bonus function to convert array to object or object to array */
function _ac($a=null,$r=false){
  if(isset($a)){
    if(is_array($a)){
      return json_decode(json_encode($a));
    }elseif(is_object($a)){
      return json_decode(json_encode($a),true);
    }else{
      return $a;
    }
  }else{
    return false;
  }
}


