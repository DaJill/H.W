	<?php
	/*
	 * PHP-PDO-MySQL-Class.
 	 *
     * @since 2015/4/9
     */
	class PDO_DB_LIB
	{
		private $Host;
		private $DBName;
		private $DBUser;
		private $DBPassword;
		private $pdo;
		private $sQuery;
		private $sMutiQuery;
		private $bConnected = false;
		private $parameters;
		private $debug_mode = true;
		public  $querycount = 0;
		
		
		public function __construct($Host, $DBName, $DBUser, $DBPassword)
		{
			$this->Host       = $Host;
			$this->DBName     = $DBName;
			$this->DBUser     = $DBUser;
			$this->DBPassword = $DBPassword;
			$this->Connect();
			$this->parameters = array();
		}
		
		
		private function Connect()
		{
			try {
				$this->pdo = new PDO('mysql:dbname=' . $this->DBName . ';host=' . $this->Host, $this->DBUser, $this->DBPassword, array(
					PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
					PDO::ATTR_EMULATE_PREPARES => false,
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
				));
				$this->bConnected = true;
			}
			catch (PDOException $e) {
				echo $this->ExceptionLog($e->getMessage());
				die();
			}
		}
		
		
		public function CloseConnection()
		{
			$this->pdo = null;
		}
		
		private function Init($query, $parameters = "")
		{
			if (!$this->bConnected) {
				$this->Connect();
			}
			try {
				$this->parameters = $parameters;
				$this->sQuery     = $this->pdo->prepare($this->BuildParams($query, $this->parameters));
				
				if (!empty($this->parameters)) {
					if (array_key_exists(0, $parameters)) {
						$parametersType = true;
						array_unshift($this->parameters, "");
						unset($this->parameters[0]);
					} else {
						$parametersType = false;
					}
					foreach ($this->parameters as $column => $value) {
						$this->sQuery->bindParam($parametersType ? intval($column) : ":" . $column, $this->parameters[$column]); 
					}
				}
				
				$this->succes = $this->sQuery->execute();
				$this->querycount++;
			}
			catch (PDOException $e) {
				echo "SQL Error";
				if($this->debug_mode === true){
					echo " : ".$this->parms($query, $parameters);
				}
				die();
			}
			
			$this->parameters = array();
		}
		
		private function multiInit($query)
		{
			if (!$this->bConnected) {
				$this->Connect();
			}
			
			try {
				$this->pdo->beginTransaction();
				
				for($i=0;$i<count($query);$i++) {
				$this->parameters = $query[$i]['params'];
				$this->sQuery     = $this->pdo->prepare($this->BuildParams($query[$i]['sql'], $this->parameters));
					
				if (!empty($this->parameters)) {
					if (array_key_exists(0, $this->parameters)) {
						$parametersType = true;
						array_unshift($this->parameters, "");
						unset($this->parameters[0]);
					} else {
						$parametersType = false;
					}
					foreach ($this->parameters as $column => $value) {
						$this->sQuery->bindParam($parametersType ? intval($column) : ":" . $column, $this->parameters[$column]); 
					}
				}		
				$this->succes = $this->sQuery->execute();
				}
				$this->pdo->commit();
			}
			catch (PDOException $e) {
				if (isset($this->pdo)){
					$this->pdo->rollBack();
					echo "Error:  " . $e;
				}
				echo "SQL Error: ".$this->parms($query, $parameters);
				die();
			}
			$this->parameters = array();
		}	
		
		/**
		 * 將query跟Param合併
		 * 
		 * @param string $query  sql 語法
		 * @param array $params 參數
		 */
		public function BuildParams($query, $params = null)
		{
			if (!empty($params)) {
				$rawStatement = explode(" ", $query);
				foreach ($rawStatement as $value) {
					if (strtolower($value) == 'in') {
						return str_replace("(?)", "(" . implode(",", array_fill(0, count($params), "?")) . ")", $query);
					}
				}
			}
			return $query;
		}
		
		#單筆select,insert,update,delete
		public function query($query, $params = null, $fetchmode = PDO::FETCH_ASSOC)
		{
			$query = trim($query);
			$rawStatement = explode(" ", $query);
			$this->Init($query, $params);
			$statement = strtolower($rawStatement[0]);
			if(in_array($statement,array('select','show'))){
				return $this->sQuery->fetchAll($fetchmode);
			}
			elseif (in_array($statement,array('insert','update','delete'))) {	
				return $this->sQuery->rowCount();
			} else {
				return NULL;
			}
		}
		#多筆insert,update,delete
		public function multi_query($query,$fetchmode = PDO::FETCH_ASSOC)
		{
			if(empty($query)) return false;
			for($i=0;$i<count($query);$i++) {
			$rawStatement = explode(" ", $query[$i]['sql']);
			$statement = strtolower($rawStatement[0]);
			  if(in_array($statement,array('select','show'))){
				return "Select Can't Write!!";
			  }
			}
			$this->multiInit($query);
			if($this->succes){
 	 			return true;
 	 		 }
		}
		#取最後一筆新增id
		public function lastInsertId()
		{
			return $this->pdo->lastInsertId();
		}
		
		#Select 返回指定欄位
		public function column($query, $params = null)
		{
			$this->Init($query, $params);
			return $this->sQuery->fetchAll(PDO::FETCH_COLUMN);
		}
		
		#單筆搜尋返回關連式一維陣列
		public function row($query, $params = null, $fetchmode = PDO::FETCH_ASSOC)
		{
			$this->Init($query, $params);
			$resuleRow = $this->sQuery->fetch($fetchmode);
			$this->sQuery->closeCursor();
			return $resuleRow;
		}
		
		#單筆搜尋返回單一欄位值
		public function single($query, $params = null)
		{
			$this->Init($query, $params);
			return $this->sQuery->fetchColumn();
		}
		
		
		private function ExceptionLog($message, $sql = "")
		{
			$exception = 'Unhandled Exception. <br />';
			$exception .= $message;
			$exception .= "<br /> You can find the error back in the log.";
			
			if (!empty($sql)) {
				$message .= "\r\nRaw SQL : " . $sql;
			}
			header("HTTP/1.1 500 Internal Server Error");
			header("Status: 500 Internal Server Error");
			return $exception;
		}
		
		private function parms($string,$data) {
	        $indexed=$data==array_values($data);
	        foreach($data as $k=>$v) {
	            if(is_string($v)) $v="'$v'";
	            if($indexed) $string=preg_replace('/\?/',$v,$string,1);
	            else $string=str_replace(":$k",$v,$string);
	        }
	        return $string;
	    }
	}