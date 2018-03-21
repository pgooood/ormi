<?php
namespace pgood\mysql;

class join{
	protected $table,$type,$where;

	function __construct(table $table,$type = null){
		$this->table($table);
		$this->type = $type ? $type : 'left';
	}
	function table($v = null){
		if($v === null)
			return $this->table;
		if(\is_object($v) && $v instanceof table)
			$this->table = $v;
		return $this;
	}
	function field($name){
		return $this->table()->field($name);
	}
	function on(){
		$arArgs = func_get_args();
		if(empty($this->where)){
			$this->where = new where();
			$this->where->table($this->table());
		}
		switch(count($arArgs)){
			case 0:
				return $this->where;
			case 1:
				if($arArgs[0] instanceof where)
					$this->where = $arArgs[0];
				else
					$this->where->_and($arArgs[0]);
				break;
			default:
				$this->where->_and($arArgs);
		}
		return $this;
	}
	function type($v = null){
		if($v === null)
			return $this->type;
		switch($v = \strtolower($v)){
			case 'left':
			case 'right':
			case 'inner':
			case 'cross':
				$this->type = $v;
		}
		return $this;
	}
	function __toString(){
		return $this->type.' join '
			.'`'.$this->table->name().'`'
			.($this->table->alias() ? ' as `'.$this->table->alias().'`' : null)
			.($this->where ? ' on '.$this->where : null);
	}
}