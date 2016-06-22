<?php
include_once 'PDO_DB_LIB.class.php';

class DBPlus{
	private static $aConnectData = array(
		"S"=>array(
			"ChatRoom"=>array("host"=>"localhost", "user"=>"DB_S", "pwd"=>"DB_S")
		),
		"M"=>array(
			"ChatRoom"=>array("host"=>"localhost", "user"=>"DB_M", "pwd"=>"DB_M")
		),
	);

	private static $aInitDBLib = array();
	private function __construct(){}

	public static function getDB($_sDBName, $_sHost){
		if(isset(self::$aInitDBLib[$_sDBName][$_sHost])){
			return self::$aInitDBLib[$_sDBName][$_sHost];
		}

		$aCnt = isset(self::$aConnectData[$_sHost][$_sDBName]) ? self::$aConnectData[$_sHost][$_sDBName] : array();
		if(empty($aCnt)){
			echo "get db err.";
			return;
		}
		
		self::$aInitDBLib[$_sDBName][$_sHost] = new PDO_DB_LIB($aCnt["host"], $_sDBName, $aCnt["user"], $aCnt["pwd"]);
		return self::$aInitDBLib[$_sDBName][$_sHost];
	}
	
}
?>