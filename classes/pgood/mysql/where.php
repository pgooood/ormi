<?php
namespace pgood\mysql;

class where{
	protected $arVals,$table;

	function __construct(){
		$this->_and(func_get_args());
	}
	function table($v = null){
		if($v === null)
			return $this->table;
		if(\is_object($v) && $v instanceof table)
			$this->table = $v;
		return $this;
	}
	function _and(){
		if(is_array($arArgs = func_get_args()) && count($arArgs) === 1 && is_array($arArgs[0]))
			$arArgs = $arArgs[0];
		return $this->condition('and',$arArgs);
	}
	function _or(){
		if(is_array($arArgs = func_get_args()) && count($arArgs) === 1 && is_array($arArgs[0]))
			$arArgs = $arArgs[0];
		return $this->condition('or',$arArgs);
	}
	function where(){
		$w = new where();
		$w->table($this->table());
		$w->_and(func_get_args());
		return $w;
	}
	protected function toField($v){
		if(!empty($v)){
			if(is_string($v) && $this->table())
				return $this->table()->field($v);
			elseif(\is_object($v) && $v instanceof field)
				return $v;
		}
	}
	function isEmpty(){
		return !count($this->arVals);
	}
	protected function condition($type,$arArgs){
		if(is_array($arArgs) && count($arArgs) === 1 && is_array($arArgs[0]))
			$arArgs = $arArgs[0];
		if(!empty($arArgs)){
			$left = array_shift($arArgs);
			$sign = '=';
			$right = null;
			switch(count($arArgs)){
				case 1:
					$right = array_shift($arArgs);
					break;
				case 2:
					$sign = trim(array_shift($arArgs));
					$right = array_shift($arArgs);
					break;
			}
			if($field = $this->toField($left))
				$left = $field;
			if(is_array($right))
				$sign = ' in ';
			elseif($field = $this->toField($right))
				$right = $field;
			$this->arVals[] = ['type' => $type,'left' =>$left,'sign' => $sign,'right' => $right];
		}
		return $this;
	}
	function __toString(){
		$res = '';
		foreach($this->arVals as $i => $ar){
			extract($ar);
			$res.= ($i ? ' '.$type.' ' : null).$this->operandToString($left,$right);
			if($right !== null)
				$res.= $sign . $this->operandToString($right,$left);
		}
		return $res;
	}
	protected function operandToString($v,$field){
		$str = null;
		if($this->operandIsValue($v)){
			if(is_array($v)){
				if(!empty($v)){
					$arTmp = [];
					foreach($v as $value)
						$arTmp[] = $field && $field instanceof field ? $field->value($value)->mysqlValue() : $value;
					$str = '('.implode(',',$arTmp).')';
				}
			}else
				$str = $field && $field instanceof field ? $field->value($v)->mysqlValue() : $v;
		}else{
			if($v instanceof where)
				$str = '('.$v.')';
			elseif($v instanceof field)
				$str = $v->mysqlName();
		}
		return $str;
	}
	protected function operandIsValue($v){
		return !(is_object($v) && ($v instanceof where || $v instanceof field));
	}
}