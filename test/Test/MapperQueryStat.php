<?php
namespace Test;

abstract class MapperQueryStat extends \AMH\EntityManager\Repository\Mapper\AbstractMapper{
	private $queries=array();
	
	protected function addQueryToStat($q){
		if(mb_strlen($q)){
			$this->queries[]=(string)$q;
		}
	}
	
	public function queriesStat(){
		return $this->queries;
	}
}
?>
