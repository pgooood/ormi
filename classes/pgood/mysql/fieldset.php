<?php
namespace pgood\mysql;

class fieldset implements \Iterator{
	protected $arFields;

	function __construct(){
		$this->arFields = [];
	}
	function __clone(){
		if($arFields = $this->arFields){
			$this->arFields = [];
			foreach($arFields as $arFieldItem)
				$this->add(clone $arFieldItem['field'],$arFieldItem['alias']);
		}
	}
	function add(field $field,$alias = null){
		$this->arFields[$alias ? $alias : $field->mysqlName()] = ['alias' => $alias,'field' => $field];
		return $this;
	}
	function get($name){
		if(isset($this->arFields[$name]))
			return $this->arFields[$name]['field'];
		foreach($this as $field)
			if($field->name() === $name)
				return $field;
	}
	function alias($name){
		if(isset($this->arFields[$name]))
			return $this->arFields[$name]['alias'];
		foreach($this as $i => $field)
			if($field->name() === $name)
				return $this->arFields[$i]['alias'];
	}
	function isEmpty(){
		return !count($this->arFields);
	}
	/**
	 * Заполнет поля значениями.
	 * Принимет переменное количество аргументов, каждый агрумент
	 * считается значением для соответствующего по очередности поля.
	 */
	function fill(){
		if($arValues = func_get_args()){
			if(count($arValues) === 1 && is_array($arValues[0]))
				$arValues = $arValues[0];
			$i = 0;
			foreach($this as $field){
				if(isset($arValues[$i]))
					$field->value($arValues[$i]);
				$i++;
			}
		}
		return $this;
	}

	function __set($name,$value){
		if($field = $this->get($name))
			$field->value($value);
	}
	function __get($name){
		if($field = $this->get($name))
			return $field->value();
	}

	function mysqlFieldsCsv(){
		$ar = [];
		foreach($this as $field)
			$ar[] = $field->mysqlName();
		return implode(',',$ar);
	}

	//Iterator
	function rewind(){
		return reset($this->arFields);
	}
	function current(){
		if($arField = current($this->arFields))
			return $arField['field'];
		return $arField;
	}
	function key(){
		return key($this->arFields);
	}
	function next(){
		if($arField = next($this->arFields))
			return $arField['field'];
		return $arField;
	}
	function valid(){
		return key($this->arFields) !== null;
	}
}