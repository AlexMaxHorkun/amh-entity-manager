<?php
namespace AMH\EntityManager\Repository\Mapper;

/**
Holds select params.

@author Alex Horkun mindkilleralexs@gmail.com
*/
class SelectStatement{
	/**
	@var array Of property=value.
	
	Just prop=>value will assume prop value of entity must be equal to given value,
	$lt=>array(prop=>value) means prop value of entity must be less then given value,
	$lte - less or equals,
	also $gt and $gte allowed.
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
	@var array Of property=>asc/desc
	*/
	protected $order_by=array();
}
?>
