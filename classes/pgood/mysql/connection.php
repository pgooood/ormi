<?php
namespace pgood\mysql;

class connection{
	const UNDEFINED = '__undefined__';

	static protected $arConnections;

	protected $arValues;

	function __construct($host,$user,$pass,$db){
		$this->host($host)
			->user($user)
			->pass($pass)
			->db($db);
	}
	protected function value($name,$value = self::UNDEFINED){
		if($value == self::UNDEFINED){
			if(isset($this->arValues[$name]))
				return $this->arValues[$name];
		}else{
			$this->arValues[$name] = $value;
			return $this;
		}
	}
	protected function cacheIndex(){
		return implode('-',[$this->host(),$this->user(),$this->db()]);
	}
	function host($value = self::UNDEFINED){
		return $this->value('host',$value);
	}
	function user($value = self::UNDEFINED){
		return $this->value('user',$value);
	}
	function pass($value = self::UNDEFINED){
		return $this->value('pass',$value);
	}
	function db($value = self::UNDEFINED){
		return $this->value('db',$value);
	}
	function charset($v = self::UNDEFINED){
		if($v === self::UNDEFINED)
			return $this->mysql()->character_set_name();
		return $this->mysql()->set_charset($v);
	}
	function mysql(){
		$index = $this->cacheIndex();
		if(empty(self::$arConnections[$index])){
			if(!($mysqli = mysqli_init()))
				throw new \Exception('mysqli init failed');
			if(!($mysqli->real_connect($this->host(),$this->user(),$this->pass(),$this->db())))
				throw new \Exception(mysqli_connect_error().' ('.mysqli_connect_errno().')');
			self::$arConnections[$index] = $mysqli;
		}
		return self::$arConnections[$index];
	}
	function close(){
		$index = $this->cacheIndex();
		if(!empty(self::$arConnections[$index])){
			self::$arConnections[$index]->close();
			unset(self::$arConnections[$index]);
		}
	}
	function table($name){
		return new table($this,$name);
	}
}