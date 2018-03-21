<?php
namespace pgood\mysql;

class select{
	protected $table,$where,$fsSelect,$fsGroup,$arOrder,$arJoins,$arLimit,$dateFormat;

	function __construct(table $table){
		$this->table($table);
		$this->fields();
		$this->arOrder = [];
		$this->arJoins = [];
	}
	function table($v = null){
		if($v === null)
			return $this->table;
		if(\is_object($v) && $v instanceof table)
			$this->table = $v;
		return $this;
	}
	protected function field($v){
		if(is_string($v))
			return $this->table->field($v);
		if(is_object($v) && $v instanceof field)
			return $v;
	}
	protected function fs($name,$arValues){
		if(count($arValues) === 1){
			if(is_object($arValues[0]) && $arValues[0] instanceof fieldset){
				$this->{$name} = $arValues[0];
				$arValues = [];
			}elseif(is_array($arValues[0]))
				$arValues = $arValues[0];
		}
		if(empty($this->{$name}))
			$this->{$name} = new fieldset();
		foreach($arValues as $v){
			if($v === '*'){
				$fs = $this->table()->fieldset();
				foreach($fs as $field)
					$this->{$name}->add($field);
			}elseif($field = $this->field($v))
				$this->{$name}->add($field);
		}
		return $this;
	}
	function fields(){
		$arArgs = func_get_args();
		if(empty($arArgs)){
			if(empty($this->fsSelect))
				$this->fsSelect = new fieldset();
			return $this->fsSelect;
		}
		return $this->fs('fsSelect',$arArgs);
	}
	function groupBy(){
		return $this->fs('fsGroup',func_get_args());
	}
	function orderBy($field,$type = 'asc'){
		if(is_string($field))
			$field = $this->table->field($field);
		if($field instanceof field)
			$this->arOrder[$field->mysqlName()] = [$field,strtolower($type) == 'desc' ? 'desc' : 'asc'];
		return $this;
	}
	function where(){
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
	function join(join $join){
		$this->arJoins[] = $join;
		return $this;
	}
	function limit($rows,$offset = null){
		if($rows){
			$this->arLimit = ['rows' => $rows,'offset' => $offset];
			return $this;
		}elseif($rows === false){
			unset($this->arLimit);
			return $this;
		}
		return $this->arLimit;
	}
	function __toString(){
		return 'select '
			.($this->fsSelect && !$this->fsSelect->isEmpty() ? $this->fsSelect->mysqlFieldsCsv() : '*')
			.' from `'.$this->table->name().'`'.($this->table->alias() ? ' as `'.$this->table->alias().'`' : null)
			.($this->arJoins ? ' '.implode(' ',$this->arJoins) : null)
			.($this->where ? ' where '.$this->where : null)
			.($this->fsGroup && !$this->fsGroup->isEmpty() ? ' group by '.$this->fsGroup->mysqlFieldsCsv() : null)
			.$this->mysqlOrderBy()
			.$this->mysqlLimit();
	}
	function query($debug = null){
		if($debug)
			vdump($this->__toString());
		$rs = new rowset($this);
		if($this->dateFormat())
			$rs->dateFormat($this->dateFormat());
		if(($res = $this->table->query($this->__toString()))
			&& ($arRes = $res->fetch_all())
		){
			$fsRaw = $this->fsSelect;
			if($fsRaw->isEmpty())
				$fsRaw = clone $this->table()->fieldset();
			foreach($arRes as $arRow){
				$fs = clone $fsRaw;
				$fs->fill($arRow);
				$rs->add($fs);
			}
		}
		return $rs;
	}
	function count(){
		$count = '*';
		if($arFields = func_get_args()){
			$arCount = [];
			foreach($arFields as $v){
				if(is_object($v)){
					if($v instanceof field)
						$arCount[] = $v->mysqlName();
				}elseif($v = $this->table()->field($v))
					$arCount[] = $v->mysqlName();
			}
			if($arCount)
				$count = implode(',',$arCount);
		}
		if($res = $this->table->query('select count('.$count.') as `num_rows`'
			.' from `'.$this->table->name().'`'.($this->table->alias() ? ' as `'.$this->table->alias().'`' : null)
			.($this->arJoins ? ' '.implode(' ',$this->arJoins) : null)
			.($this->where ? ' where '.$this->where : null)
			.($this->fsGroup && !$this->fsGroup->isEmpty() ? ' group by '.$this->fsGroup->mysqlFieldsCsv() : null))
		){
			$v = 0;
			if($this->fsGroup)
				$v = $res->num_rows;
			elseif($r = $res->fetch_assoc())
				$v = $r['num_rows'];
			return $v;
		}else
			throw new \Exception('Query error: '.$this->__toString());
	}
	protected function mysqlOrderBy(){
		$ar = [];
		foreach($this->arOrder as $arVal)
			$ar[] = $arVal[0]->mysqlName().' '. $arVal[1];
		if($ar)
			return ' order by '.implode(',',$ar);
	}
	protected function mysqlLimit(){
		if(!empty($this->arLimit['rows']))
			return ' limit '.($this->arLimit['offset'] ? $this->arLimit['offset'].',' : null).$this->arLimit['rows'];
	}
	function joinedTable($alias){
		if(!empty($this->arJoins))
			foreach($this->arJoins as $j)
				if($j->table()->alias() === $alias)
					return $j->table();
	}
	function dateFormat(){
		$arArgs = func_get_args();
		if(empty($arArgs[0]))
			return $this->dateFormat;
		$this->dateFormat = $arArgs[0];
		return $this;
	}
	/**
	 * Returns expr object
	 * first argument - name|alias
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
			$expr = new expr($this->table(),$strExpr,$name);
			$expr->fields($arValues);
			$this->fields()->add($expr,$name);
		}
		return $this;
	}
}