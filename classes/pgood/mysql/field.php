<?php
namespace pgood\mysql;

class field{
	const UNDEFINED = '__undefined__'
		,INT = 'int'
		,FLOAT = 'float'
		,STRING = 'string'
		,TEXT = 'text'
		,DATE = 'date'
		,BOOL = 'bool'
		,EXPR = 'expr';

	protected $table,$name,$type,$value = null,$autoIncrement = false,$notNull = false,$length,$decimals,$defaultValue,$unsigned;

	function __construct(table $table,$name,$type = self::STRING){
		$this->table($table);
		$this->name = $name;
		$this->type = $type;
		switch($type){
			case self::STRING:
				$this->length(20); break;
			case self::INT:
				$this->length(11); break;
		}
	}
	function table($v = null){
		if($v === null)
			return $this->table;
		if(\is_object($v) && $v instanceof table)
			$this->table = $v;
		return $this;
	}
	function name($v = null){
		if($v === null)
			return $this->name;
		else
			$this->name = $v;
		return $this;
	}
	function type(){
		return $this->type;
	}
	function mysqlType(){
		switch($this->type()){
			case self::INT:
				return 'int';
			case self::FLOAT:
				return 'float';
			case self::STRING:
				return 'varchar';
			case self::TEXT:
				return 'text';
			case self::DATE:
				return 'datetime';
			case self::BOOL:
				return 'tinyint';
		}
	}
	function mysqlDefinition(){
		$name = '`'.$this->name().'` ';
		$type = $this->mysqlType();
		$nullDef = ($this->notNull() ? ' NOT NULL' : null)
				.(isset($this->defaultValue) ? ' DEFAULT \''.$this->defaultValue().'\'' : (!$this->notNull() ? ' DEFAULT NULL' : null));
		$autoDef = $this->autoIncrement() ? ' AUTO_INCREMENT' : null;
		$unsignedDef = $this->unsigned ? ' unsigned' : null;
		switch($this->type()){
			case self::FLOAT:
				return $name.$type.'('.$this->length().','.$this->decimals().')'.$unsignedDef.$nullDef.$autoDef;
			case self::BOOL:
				return $name.$type.'(1) unsigned'.($this->notNull() ? ' NOT NULL' : null);
			default:
				return $name.$type.($this->length() ? '('.$this->length().')' : null)
					.$unsignedDef
					.$nullDef
					.$autoDef;
		}
	}
	function mysqlName(){
		return '`'.($this->table()->alias() ? $this->table()->alias() : $this->table()->name()).'`.`'.$this->name().'`';
	}
	function mysqlValue(){
		if(!isset($this->value) && (!$this->notNull() || $this->autoIncrement()))
			return 'NULL';
		switch($this->type()){
			case self::BOOL:
				return intval($this->value);
			default:
				return '"'.$this->table->mysql()->real_escape_string($this->value).'"';
		}
	}
	function value($value = self::UNDEFINED){
		if($value === self::UNDEFINED)
			return $this->value;
		else{
			if(is_string($value) && strtoupper($value) === 'NULL')
				unset($this->value);
			else
				$this->value = $value;
			return $this;
		}
	}
	function autoIncrement($value = self::UNDEFINED){
		if($value === self::UNDEFINED)
			return $this->autoIncrement;
		elseif($this->type() === self::INT)
			$this->autoIncrement = !!$value;
		return $this;
	}
	function notNull($value = self::UNDEFINED){
		if($value === self::UNDEFINED)
			return $this->notNull;
		else
			$this->notNull = !!$value;
		return $this;
	}
	function length($value = self::UNDEFINED){
		if($value === self::UNDEFINED)
			return $this->length;
		else
			$this->length = $value;
		return $this;
	}
	function decimals($value = self::UNDEFINED){
		if($value === self::UNDEFINED)
			return $this->decimals;
		else
			$this->decimals = $value;
		return $this;
	}
	function defaultValue($value = self::UNDEFINED){
		if($value === self::UNDEFINED)
			return $this->defaultValue;
		else
			$this->defaultValue = $value;
		return $this;
	}
	function unsigned($value = self::UNDEFINED){
		if($value === self::UNDEFINED)
			return $this->unsigned;
		else
			$this->unsigned = !!$value;
		return $this;
	}
}