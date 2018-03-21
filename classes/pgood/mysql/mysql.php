<?php
class mysql{
private $mysql;
function __construct($host,$user,$pass,$db,$pref = null,$charset = null){
	global $_mysql_connection_cache;

	$this->host = $host;
	$this->user = $user;
	$this->pass = $pass;
	$this->db = $db;
	$this->prefix =$pref;
	$this->charset =  $charset ? $charset : 'utf8';
	
	$cacheIndex = $host.'-'.$user;

	if(!is_array($_mysql_connection_cache))
		$_mysql_connection_cache = array();
	if(!empty($_mysql_connection_cache[$cacheIndex])){
		$this->mysql = $_mysql_connection_cache[$cacheIndex]['mysql'];
		$this->prefix = $_mysql_connection_cache[$cacheIndex]['prefix'];
	}else{
		$this->connect();
		$_mysql_connection_cache[$cacheIndex]['mysql'] = $this->mysql;
		$_mysql_connection_cache[$cacheIndex]['prefix'] = $this->prefix;
	}
}
function connect(){
	$this->mysql = new mysqli($this->host,$this->user,$this->pass,$this->db);
	if($this->mysql->connect_errno)
		throw new Exception('MySQL connection error:<br/><code>'.$this->mysql->connect_error.'</code>');
	if(!$this->mysql->set_charset($this->charset))
		throw new Exception('MySQL charset setting error:<br/><code>'.$this->mysql->error.'</code>');
}
function query($query,$resultmode = MYSQLI_STORE_RESULT){
	$res = $this->mysql->query($query,$resultmode);
	//if(!$res) throw new Exception(mysql_error().'<br/><code>'.$query.'</code>');
	return $res;
}
function getPrefix(){
	return $this->prefix;
}
function table($name){
	if($this->getPrefix() && substr($name,0,strlen($this->getPrefix())) != $this->getPrefix())
		$name = $this->getPrefix().$name;
	return $name;
}
function tables(){
	if($res = $this->query('SHOW TABLES'))
		return $res->fetch_all();
}
function fieldType($name,$res){
	while($finfo = $res->fetch_field())
		if($finfo->name == $name)
			return $finfo->type;
}
function nextId($table){
	if($table
		&& ($rs = $this->query("SHOW TABLE STATUS FROM `".$this->db."` LIKE '".$this->getTableName($table)."'"))
		&& ($row = $rs->fetch_assoc())
	){
		return $row['Auto_increment'];
	}
}
function hasTable($name){
	$name = $this->getTableName($name);
	if($ar = $this->getTables()){
		foreach($ar as $row){
			if($row[0] == $name) return true;
		}
	}
}
function insert($table,$values){
	$query='insert into `'.$this->getTableName($table).'` (`'.implode('`,`',array_keys($values)).'`) values ('.implode(',',$values).')';
	return $this->query($query);
}
function update($table,$values,$cond){
	$ar=array();
	foreach($values as $name => $value) $ar[]='`'.$name.'`='.$value;
	$query = 'update `'.$this->getTableName($table).'` set '.implode(',',$ar).($cond ? ' where '.$cond : null);
	return $this->query($query);
}
function remove($table,$cond){
	$query = 'DELETE FROM `'.$this->getTableName($table).'`'.($cond ? ' WHERE '.$cond : null);
	return $this->query($query);
}
function insertId(){
	return $this->mysql->insert_id;
}
function affectedRows(){
	return $this->mysql->affected_rows;
}
function str($v){
	return "'".$this->mysql->real_escape_string($v)."'";
}
function num($v){
	$v = str_replace(array(' ',','),array('','.'),$v);
	return strstr($v,'.') ? floatval($v) : intval($v);
}
}