<?php

function StackTrace()
{
	$trace = debug_backtrace(false);
	
	$html = "";
	$file = "";
	$line = "";
	
	foreach($trace as $place)
	{
		if(!isset($place['file']))
			$file = "";
		else		
			$file = $place['file'];
		
		if(!isset($place['line']))
			$line = "";
		else
			$line = $place['line'];
		
		
		$a = explode("/wse573198", $file);
		if(count($a) > 1)
			$file = $a[1];
		
		$a = explode("\\www", $file);
		if(count($a) > 1)
			$file = $a[1];
				
		
		if($place['function'] == "StackTrace")
			continue;
		
		if(strpos($file, "SQL.php") !== false && ($place['function'] == "Q" || $place['function'] == "QS"))
			continue;
		
		$html .= "$file:$line:{$place['function']}()<br>";
	}
	
	return $html;
	
}

class SQL
{

	private static $singleton;

	public static function S()
	{
		if (! isset(self::$singleton))
			self::$singleton = new SQL();
		
		return self::$singleton;
	}

	public $Querys = Array();
	public $ConnectTime = 0;

	private $mysqli = null;
	private $result = null;
	private $success = false;

	private $doing_transaction = false;

	private function __construct ()
	{
	
	}

	public function InsertId ()
	{
		return $this->mysqli->insert_id;		
	}

	public function Q ($query, $parameters)
	{		
		if ($this->mysqli == null)
		{
			$start = microtime(true);
			if($_SERVER['SERVER_NAME'] == "localhost")
				$this->mysqli = new mysqli("127.0.0.1", "root", "");
			else
				$this->mysqli = new mysqli("195.128.174.39", "wse573198", 	"songheroes");
			$this->mysqli->select_db("wse573198");
			$this->mysqli->set_charset("UTF-16");
			$stop = microtime(true);
			$this->ConnectTime = $stop - $start; 
		}
		
		$Entry = Array();
		
		//if(isset($this->result) && $this->result != false)
		//	$this->result->free();
		
		$retries = 0;
		
		$this->result = false;
		while($this->result == false && $retries++ < 3)
		{
			$before = microtime(true);
			
			if(count($parameters) > 0)
			{
				foreach($parameters as $p)
				{	
					$p = $this->mysqli->real_escape_string($p);
					$query = implode("'$p'", explode("?", $query, 2)); //str_replace on only first ocurance of "?"
				}				
			}
			
			$this->result = $this->mysqli->real_query($query);
				 
			$after = microtime(true);
		}
		
		if($this->result == false && $this->doing_transaction)
		{
			echo "Rollback!!";
			$this->mysqli->rollback();
		}
		
		$Entry['time'] = $after - $before;
		$Entry['query'] = $query . "<br>" . print_r($parameters, true);
		$Entry['trace'] = StackTrace();		
		$Entry['error'] = "";
		$Entry['rows'] = $this->mysqli->affected_rows;
		//$statement->close();
		
		$time_limit = 0.5;
		
		if ($this->result  == false || $Entry['time'] > $time_limit)
		{
			
			if(class_exists('BasicPage'))
				BasicPage::Singleton()->AddError("Error in SQL:" . $query . "<br>Error: " . $this->mysqli->error, $Entry['trace']);
			
			$user_id = 0;
			if(class_exists("Auth"))
					$user_id = Auth::Singleton()->id;
			
			$Entry['error'] = $this->mysqli->error;
			$Entry['error_no'] = $this->mysqli->errno;
			
			$escaped = $this->mysqli->real_escape_string($query);
			$this->mysqli->query("INSERT INTO log (time, duration, affected_rows, data, file, user_id) VALUES (NOW(), '{$Entry['time']}', 0, '$escaped', ' {$Entry['trace']}', '$user_id')");
			
			$last_sql = "";
			if(isset($_SESSION))
				$last_sql = $_SESSION["last_sql"];				
			
			if($this->result  === false)
				die("Error in SQL:" . $query . "<br>Params:".print_r($parameters, true)."<br>Error:" . $Entry['error'] . "<br>Error#:" .$Entry['error_no'] . "<br>Retries: $retries<br>Transaction:{$this->doing_transaction}<br>Time: ".$Entry['time'] ."<br>" . $Entry['trace'] . "<br><br>Last SQL:" . $last_sql);
		}
		
		$_SESSION["last_sql"] = $query . " <br> " . $Entry['trace'];
		
		$this->Querys[] = $Entry;
		
		$this->result = $this->mysqli->store_result();
		if($this->result != null)
			$Entry['rows'] = $this->result->num_rows;
		
		return $this->result;
	}

	public function QS ($query, $parameters)
	{
		$res = Q($query, $parameters);
		$a = $res->fetch_array();
		$res->free_result();
		
		return $a[0];
	}
	
	public function Escape($string)
	{
		return $this->mysqli->real_escape_string($string);
	}

	public function StartTransaction()
	{
		echo "autocommit=false!!";
		$this->mysqli->autocommit(FALSE);
		$this->doing_transaction = true;
	}
	
	public function EndTransaction()
	{	
		echo "Commit!!";
		$this->mysqli->commit();
		$this->mysqli->autocommit(true);
		$this->doing_transaction = false;
	}
}

function SQLInsertId()
{
	return SQL::S()->InsertId();
}

function Q($sql, $parameters)
{
	return SQL::S()->Q($sql, $parameters);
}
function QS($sql, $parameters)
{
	return SQL::S()->QS($sql, $parameters);
}
function Escape($string)
{
	return SQL::S()->Escape($string);
}

function StartTransaction()
{
	return SQL::S()->StartTransaction();
}
function EndTransaction()
{
	return SQL::S()->EndTransaction();
}


?>