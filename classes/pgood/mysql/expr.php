<?php
/**
 * Select Expression for the select object
 */
namespace pgood\mysql;

class expr extends field{
	protected $expr,$arFields;

	function __construct(table $table,$expr,$name){
		parent::__construct($table,$name,self::EXPR);
		$this->expr = $expr;
	}
	/**
	 * Set fields to replace fields' alias in the expression
	 * alias format is: $n
	 * n - number of a field
	 */
	function fields(){
		$this->arFields = [];
		if($arValues = func_get_args()){
			if(count($arValues) === 1 && is_array($arValues[0]))
				$arValues = $arValues[0];
			foreach($arValues as $v){
				if(is_object($v)){
					if($v instanceof field)
						$this->arFields[] = $v;
				}elseif($v = $this->table()->field($v))
					$this->arFields[] = $v;
			}
		}
	}
	function mysqlName(){
		$expr = $this->expr;
		if($this->arFields)
			foreach($this->arFields as $i => $field)
				$expr = \str_replace('$'.($i + 1),$field->mysqlName(),$expr);
		return $expr.' as `'.$this->name().'`';
	}
}