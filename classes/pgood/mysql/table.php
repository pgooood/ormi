<?php
namespace pgood\mysql;

class table{
	protected $connection,$name,$fieldset,$alias;

	function __construct(connection $connection,$name){
		$this->connection = $connection;
		$this->name = $name;
		$this->fieldset = new fieldset();
	}
	function __clone(){
		if($fieldset = $this->fieldset){
			$this->fieldset = new fieldset();
			foreach($fieldset as $field){
				$newField = clone $field;
				$newField->table($this);
				$this->fieldset->add($newField);
			}
		}
	}
	function name(){
		return $this->name;
	}
	function mysql(){
		return $this->connection->mysql();
	}
	function query($query){
		return $this->mysql()->query($query);
	}
	function insertId(){
		return $this->mysql()->insert_id;
	}
	function alias($value = false){
		if($value === false)
			return $this->alias;
		else
			$this->alias = $value;
		return $this;
	}
	function fieldset($value = null){
		if(is_object($value) && $value instanceof fieldset)
			$this->fieldset = $value;
		return $this->fieldset;
	}
	function field($name,$type = false,$alias = null){
		if(false === $type){
			return $this->fieldset->get($name);
		}elseif(strlen($name)){
			switch($type){
				case field::DATE:
					$field = new fieldDate($this,$name,$type);
					break;
				default:
					$field = new field($this,$name,$type);
			}
			$this->fieldset->add($field,$alias);
			return $field;
		}
	}
	function create($primaryKey = null){
		if(!$this->fieldset->isEmpty()){
			$arFields = [];
			foreach($this->fieldset as $f)
				$arFields[] = $f->mysqlDefinition();
			$query = 'CREATE TABLE `'.$this->name().'` ('
				.implode(',',$arFields)
				.($primaryKey ? ',PRIMARY KEY (`'.$primaryKey.'`)' : null)
				.')';
			return $this->query($query);
		}
	}
	function index($name,$fields,$type = null){
		if(!is_array($fields))
			$fields = [$fields];
		$fields = array_filter($fields);
		if(!empty($fields))
			return $this->query('create '.$type.' index `'.$name.'` on `'.$this->name().'` (`'.implode('`,`',$fields).'`)');
	}
	function exists(){
		return false !== $this->mysql()->query('select 1 from `'.$this->name().'` limit 1');
	}
	function drop(){
		return false !== $this->mysql()->query('drop table if exists `'.$this->name().'`');
	}
	/**
	 * Возвращает объект fieldset
	 * по умолчанию со всеми полями таблицы
	 * принимет переменное количество аргументов, где
	 * каждый агрумент считается именем поля, которе попадет в fildset
	 */
	function row(){
		if($arFieldNames = func_get_args()){
			$fieldset = new fieldset();
			foreach($arFieldNames as $name)
				if($field = $this->fieldset->get($name))
					$fieldset->add($field);
				else
					throw new Exception('Field "'.$name.'" not found');
			return $fieldset;
		}
		return clone $this->fieldset;
	}
	function insert(fieldset $row){
		$arValues = array();
		foreach($row as $field)
			$arValues[$field->name()] = $field->mysqlValue();
		return $this->query('insert into `'.$this->name().'` (`'.implode('`,`',array_keys($arValues)).'`) values ('.implode(',',$arValues).')');
	}
	function update(fieldset $row,$where = null){
		$arValues = array();
		foreach($row as $field)
			$arValues[] = '`'.$field->name().'`='.$field->mysqlValue();
		return $this->query('update `'.$this->name().'` set '.implode(',',$arValues).($where ? ' where '.$where : null));
	}
	function delete(){
		$arArgs = array_filter(func_get_args());
		$where = null;
		if(!empty($arArgs)){
			if(count($arArgs[0]) == 1 && $arArgs[0] instanceof where){
				$where = $arArgs[0];
			}else{
				$where = new where($arArgs);
				$where->table($this);
			}
		}
		return $this->query('delete from `'.$this->name().'`'.($where ? ' where '.$where : null));
	}
	function clear(){
		return $this->query('truncate table `'.$this->name().'`');
	}
	function where(){
		$where = new where();
		$where->table($this);
		$where->_and(func_get_args());
		return $where; 
	}
	function select(){
		return (new select($this))->fields(func_get_args());
	}
	function join($alias = null){
		$t = clone $this;
		if($alias)
			$t->alias($alias);
		return new join($t);
	}
	/**
	 * Returns expr object
	 * first argument - name
	 * second argument - expression
	 * third, etc. fields to replace fields' alias in the expression
	 * alias format is: $n
	 * n - number of a field
	 */
	function expr(){
		if(($arValues = func_get_args())
			&& ($name = array_shift($arValues))	
			&& ($strExpr = array_shift($arValues))
		){
			$expr = new expr($this,$strExpr,$name);
			$expr->fields($arValues);
			return $expr;
		}
	}
}