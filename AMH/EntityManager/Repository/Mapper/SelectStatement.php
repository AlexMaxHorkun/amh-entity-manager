<?php
namespace AMH\EntityManager\Repository\Mapper;

/**
Holds select params.

@author Alex Horkun mindkilleralexs@gmail.com
*/
class SelectStatement{
	/**
	@var array Of property=value.
	*/
	protected $filter=array();
	/**
	@var int If 0 - no limit, greater then 0 - limit.
	*/
	protected $limit=0;
	/**
	@var array Of ints.
	
	IDs of entities not needed.
	*/
	protected $not_in_ids=array();
	/**
	@var array Of property=>asc/desc.
	*/
	protected $order_by=array();
	/**
	@param array $filter Entity prop filter.
	@param int $limit Limit of records needed, if zero - no limit.
	@param array $order_by of prop=>asc/desc.
	@param array $not_in_ids of int IDs of entities not needed.
	*/
	public function __construct(array $filter=array(),$limit=0,array $order_by=array(),array $not_in_ids=array()){
		$this->setFilter($filter);
		$this->setLimit($limit);
		$this->setOrderBy($order_by);
		$this->setNotInIds($not_in_ids);
	}
	/**
	Just prop=>value* will assume prop value of entity must be equal to given value,
	$lt=>array(prop=>value) means prop value of entity must be less then given value,
	$lte - less or equals,
	also $gt and $gte allowed,
	$not=>array(prop=>value*) - prop not equals to given value,
	
	value* - can be an array, will be converted to in(val1,val2,...).
	
	@param array
	*/
	public function setFilter(array $filter=array()){
		$this->filter=$filter;
	}
	/**
	@return array
	*/
	public function getFilter(){
		return $this->filter;
	}
	/**
	@param int Limit, 0 if no limit.
	*/
	public function setLimit($l){
		$l=(int)$l;
		if($l<0) $l=0;
		$this->limit=$l;
	}
	/**
	@return int
	*/
	public function getLimit(){
		return $this->limit;
	}
	/**
	@param array Of property=>asc/desc.
	*/
	public function setOrderBy(array $o=array()){
		$this->order_by=$o;
	}
	/**
	@return array
	*/
	public function getOrderBy(){
		return $this->order_by;
	}
	/**
	@param array Of int IDs of not needed entities.
	*/
	public function setNotInIds(array $n=array()){
		$this->not_in_ids=$n;
	}
	/**
	@return array
	*/
	public function getNotInIds(){
		return $this->not_in_ids;
	}
}
?>
