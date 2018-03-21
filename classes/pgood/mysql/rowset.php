<?php
namespace pgood\mysql;

class rowset implements \Iterator{
	protected $arRows,$select,$skipEmptyValues,$dateFormat;
	
	function __construct($v = null){
		$this->arRows = [];
		if($v instanceof select)
			$this->select = $v;
	}
	function add(fieldset $fieldset){
		$this->arRows[] = $fieldset;
	}
	function item($i){
		if(isset($this->arRows[$i]))
			return $this->arRows[$i];
	}
	function count(){
		return count($this->arRows);
	}
	function skipEmptyValues($v){
		$this->skipEmptyValues = \boolval($v);
		return $this;
	}
	function xml($name = null,$item = null){
		$xml = new \pgood\xml\xml(\strlen($name) ? $name : 'rowset');
		if($item !== null){
			if($fs = $this->item($item)){
				$this->rowXml($xml->de(),$fs);
				return $xml;
			}
		}else{
			if($this->select)
				$xml->de()->append('select')->text($this->select->__toString());
			foreach($this as $fs){
				$eRow = $xml->de()->append('row');
				$this->rowXml($eRow,$fs);
			}
			return $xml;
		}
	}
	protected function rowXml(\pgood\xml\element $eRow,fieldset $fs){
		foreach($fs as $i => $field){
			$tagname = ($alias = $fs->alias($i)) ? $alias : $field->name();
			if($field->type() === field::BOOL && $this->dateFormat())
				$field->format($this->dateFormat());
			$len = strlen($v = $field->value());
			if(!($this->skipEmptyValues && !$len)){
				$eCell = $eRow->append($tagname);
				if($len)
					$eCell->text($v);
			}
		}
	}
	function toArray($item = null){
		$arRes = [];
		if($item !== null){
			if($fs = $this->item($item))
				$arRes = $this->rowArray($fs);
		}else{
			foreach($this as $fs)
				$arRes[] = $this->rowArray($fs);
		}
		return $arRes;
	}
	protected function rowArray(fieldset $fs){
		$arRow = [];
		foreach($fs as $i => $field){
			$name = ($alias = $fs->alias($i)) ? $alias : $field->name();
			if($field->type() === field::DATE && $this->dateFormat()){
				$field->format($this->dateFormat());
			}
			$len = strlen($v = $field->value());
			if(!($this->skipEmptyValues && !$len))
				$arRow[$name] = $v;
		}
		return $arRow;
	}
	function dateFormat($value = null){
		if($value !== null)
			$this->dateFormat = $value;
		return $this->dateFormat;
	}

	//Iterator
	function rewind(){
		return reset($this->arRows);
	}
	function current(){
		return current($this->arRows);
	}
	function key(){
		return key($this->arRows);
	}
	function next(){
		return next($this->arRows);
	}
	function valid(){
		return key($this->arRows) !== null;
	}
}