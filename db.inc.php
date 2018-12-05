<?php
#version 2.0.0
// новые функции

class SQL
{

  var $db_host;
  var $db_login;
  var $db_password;
  var $db_name;

  var $debug;  

  var $sql_link;
  var $result;
  var $last_id;
  var $record;
  var $quantity;
  
  var $query_quantity = 0;
  var $query_list = array();
  var $low_query_log = array();

  var $error;

  var $db_charset = false;

  function __construct($db_host="", $db_login="", $db_password="", $db_name="", $db_charset="", $debug="")
  {
    if (empty($db_host)) $this->db_host = DB_HOST; else $this->db_host = $db_host;
    if (empty($db_login)) $this->db_login = DB_LOGIN; else $this->db_login = $db_login;
    if (empty($db_password)) $this->db_password = DB_PASSWORD; else $this->db_password = $db_password;  
    if (empty($db_name)) $this->db_name = DB_NAME; else $this->db_name = $db_name;
    
    if (empty($debug)) $this->debug = DB_DEBUG; else $this->debug = $debug;
    if (empty($db_charset)) $this->db_charset = DB_CHARSET; else $this->db_charset = $db_charset; 
    
    return;
  }

  function SQL()
  {
    $this->__construct();
    return;
  }

  function setCharset()
  {
    $query = "SET NAMES '".$this->db_charset."'";
    $this->query($query);
  }

  function open()
  {
    if ($this->sql_link = @mysql_connect($this->db_host, $this->db_login, $this->db_password, true))
    {
      if ($this->db_charset) $this->setCharset();
      return $this->sql_link;
    }
    else
    {
      if ($this->debug)
      {
        echo "<br><span style='color:red'>open()</span><br> errno =".mysql_errno() . ":" . mysql_error() ."<br>";
        exit;
      }
      $this -> error = array (mysql_errno(), mysql_error());
      return false;
    }
  }

  function close()
  {
    mysql_close($this->sql_link);
  }

  function useDB($db_name='')
  {
    if (empty($db_name)) $db_name = $this->db_name;

    if(!mysql_select_db($db_name, $this->sql_link))
    {
      if ($this->debug)
      {
        echo "<br><span style='color:red'>useDB</span><br> errno =".mysql_errno() . ":" . mysql_error() ."<br>";
        exit;
      }
      return false;
    }
    return true;
  }
  
  function save_low_query ($diff_time, $query)
  {
//  if ( $diff_time > 2 ) 
    if ( false ) 
    {
      $cache_file = $_SERVER[DOCUMENT_ROOT].'/static_html/low-query-'.date("md").'.txt'; 
      $fp = @fopen($cache_file, "a" );
      @fwrite($fp, "time: ".$diff_time." [".date("m.d H:i:s")."]\r\n".$query."\r\n\r\n");
      @fclose($fp);
      @chmod($cache_file, 0777);

      $cache_file = $_SERVER[DOCUMENT_ROOT].'/static_html/low-query-time-'.date("md").'.txt'; 
      $fp = @fopen($cache_file, "a" );
      @fwrite($fp, date("m.d H:i:s")."\r\n");
      @fclose($fp);
      @chmod($cache_file, 0777);
    }
    return;
  }  
  
  function query($query)
  {
    $this->query_quantity++;
    $this->query_list[] = $query; 

    $mtime = microtime();
    $mtime = explode(" ",$mtime);
    $mtime = $mtime[1] + $mtime[0];
    $starttime = $mtime;

    if($this->result = mysql_query($query, $this->sql_link))
    {
      
      $mtime = microtime();
      $mtime = explode(" ",$mtime);
      $mtime = $mtime[1] + $mtime[0];
      $endtime = $mtime;
  //    $this->sql_time += $endtime - $starttime;
      $diff_time = $endtime - $starttime;
  //    $this->arr_queries[] = array ($query, $diff_time);
      $this->save_low_query ($diff_time, $query);          
      unset ($diff_time, $starttime, $endtime);    
      
      return $this->result;
    }
    else
    {
      if ($this->debug)
      {
        echo "<br><span style='color:red'>query()</span><br> errno =".mysql_errno() . ":" . mysql_error() ."<br><span style='color:red'>".$query."</span><br>";
        exit;
      }
      return false;
    }
  }  


  function format_query($query)  
  {  
    return("<p><b>Query was:</b><br/><textarea cols='50' rows='10'>$query</textarea></p>");  
  } 

  function query2($query)
  {
    $backtrace = debug_backtrace();  
   
    $backtrace = "</b> in : <b>" . $backtrace[0]["file"] . "</b>, on line: <b>" . $backtrace[0]["line"] . "</b>";  
   
    $this->result = mysql_query($query)  
      or trigger_error(mysql_errno() . ": <b>" . mysql_error() . $backtrace . $this->format_query($query) , E_USER_ERROR);  
   
    return($this->result); 
  }


  function getLastID()
  {
    if ($this->last_id = mysql_insert_id($this->sql_link))
      return $this->last_id;
    else
      return "getLastId err";

  }

  function getQuantityRecords($result = "")
  {
    if (!empty($result)) $this->result = $result;
    if ($this->quantity = mysql_num_rows($this->result))
      return $this->quantity;
    else
      return false;

  }

  function getRecord($result = "")
  {
    if (!empty($result)) $this->result = $result;
    if ($this->record = mysql_fetch_array($this->result, MYSQL_ASSOC))
      return $this->record;
    else
      return false;

  }

  public function quot( $value ) 
  { 
    /*if (is_numeric($value) && !is_float($value)) { 
      $value = (int)$value; 
    } else {*/ 
    if (is_array($value)) 
      foreach (array_keys($value) as $k)
//        if (!is_numeric($value[$k]))
      $value[$k] = "'" . mysql_real_escape_string($value[$k], $this->sql_link) . "'"; 
    else 
      $value = "'" . mysql_real_escape_string($value, $this->sql_link) . "'"; 
    /*}*/ 
    return $value; 
  }

  public function sqlParse( $query ) 
  { 
    $res = $this->query($query); 
    if ($res) 
    { 
      while($row = mysql_fetch_assoc($res)) 
        $qresult[] = $row; 
      mysql_free_result($res); 
    } 

    return isset($qresult) ? $qresult : null; 
  } 
  
  public function sqlParseOne( $query ) 
  { 
    $res = $this->sqlParse($query); 
    return isset($res[0]) ? $res[0] : null; 
  }  

  public function insert( $table, $items ) 
  { 
    $f_sql = $v_sql = array (); 

    foreach ($items as $f => $v) 
    { 
      $f_sql[] = '`' . $f . '`'; 
      $v_sql[] = $v; 
    } 
    $query = 'INSERT INTO `' . $table . '` (' . implode(',', $f_sql) . ') VALUES (' . implode(',', $v_sql) . ')'; 
    $this->query($query); 
    return $this->getLastId(); 
  } 

  public function replace( $table, $items ) 
  { 
    $f_sql = $v_sql = array (); 
    foreach ($items as $f => $v) 
    { 
      $f_sql[] = '`' . $f . '`'; 
      $v_sql[] = $v; 
    } 
    $query = 'REPLACE INTO `' . $table . '` (' . implode(',', $f_sql) . ') VALUES (' . implode(',', $v_sql) . ')'; 
    $this->query($query); 
    return  mysql_affected_rows($this->sql_link); 
  } 

  public function update( $table, $items, $where ) 
  { 
    $sql = array (); 
    foreach ($items as $f => $v) 
      $sql[] = '`' . $f . '`=' . $v; 
    $query = 'UPDATE `' . $table . '` SET ' . implode(',', $sql); 

    if (! empty($where)) 
      $query .= ' WHERE ' . $where;

//echo $query;
    
    return $this->query($query); 
  } 

  public function update2( $table, $items, $where ) 
  { 
    $sql = array (); 
    foreach ($items as $f => $v) 
      $sql[] = '`' . $f . '`=' . $v; 
    $query = 'UPDATE `' . $table . '` SET ' . implode(',', $sql); 

    if (! empty($where)) 
      $query .= ' WHERE ' . $where;
      
      $cache_file = $_SERVER[DOCUMENT_ROOT].'/static_html/sql-'.date("md").'.txt'; 
      $fp = @fopen($cache_file, "a" );
      @fwrite($fp, "time: ".$diff_time." [".date("m.d H:i:s")."]\r\n".$query."\r\n\r\n");
      @fclose($fp);
      @chmod($cache_file, 0777);

   
    return $this->query($query); 
  } 

  public function delete( $table, $where ) 
  { 
    $query = 'DELETE FROM `' . $table . '` WHERE ' . $where; 
    return $this->query($query); 
  }
}
?>