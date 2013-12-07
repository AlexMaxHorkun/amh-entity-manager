<?php
namespace AMH\EntityManager\Repository;

use \AMH\EntityManager\Entity\EntityInterface;
use Mapper\MapperInterface;
use \AMH\EntityManager\Cache\CacheInterface;

/**
@author Alex horkun mindkilleralexs@gmail.com

Repository is used to work with Mapper and to store entitties in memory and cache.
*/
class Repository{
	/**
	@var array of Entities and flush action information.
	*/
	private $entities=array();
	/**
	@var string
	*/
	private $name=NULL;
	/**
	@var MapperInterface
	*/
	private $mapper=NULL;
	/**
	@var CacheInterface
	*/
	private $cache=NULL;
	/**
	@var HydaratorInterface to fetch entity from array/extract entity to array.
	*/
	private $hydarator=NULL;
	/**
	@var EntityManager
	*/
	private $em=NULL;
}
?>
