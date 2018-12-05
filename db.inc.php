<?
#version 1.3.2

class SQL{

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

function query($query)
{
  if($this->result = mysql_query($query, $this->sql_link))
    return $this->result;
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
    return false;

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
}
?>