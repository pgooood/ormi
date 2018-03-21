<?php
namespace pgood\mysql;

class fieldDate extends field{
	protected $format;

	function __construct(table $table,$name,$type = null){
		parent::__construct($table,$name,self::DATE);
	}

	function format($value){
		$this->format = $value;
		return $this;
	}

	function mysqlValue(){
		if(empty($this->value))
			return 'NULL';
		return '"'.date('Y-m-d H:i:s',$this->value).'"';
	}

	function value($value = self::UNDEFINED){
		if($value === self::UNDEFINED){
			return $this->format && $this->value ? date($this->format,$this->value) : $this->value;
		}elseif(is_int($value) || is_string($value)){
			if(is_string($value)){
				switch(strtoupper($value)){
					case 'NULL':
						return parent::value($value);
					case 'NOW()':
						$value = time();
						break;
					default:
						$value = strtotime($value);
				}
			}
			if(is_int($value) && $value > 0)
				return parent::value($value);
		}
	}
}