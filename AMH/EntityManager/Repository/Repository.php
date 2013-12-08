<?php
namespace AMH\EntityManager\Repository;

use AMH\EntityManager\Entity\EntityInterface;
use AMH\EntityManager\Repository\Mapper\MapperInterface;
use AMH\EntityManager\Cache\CacheInterface;
use AMH\EntityManager\EntityManager;

/**
@author Alex horkun mindkilleralexs@gmail.com

Repository is used to work with Mapper and to store entitties in memory and cache.
*/
class Repository{
	const FLUSH_ACTION_NONE=0;
	const FLUSH_ACTION_INSERT=1;
	const FLUSH_ACTION_UPDATE=2;
	const FLUSH_ACTION_REMVOE=3;
	
	/**
	@var array of Entities and flush action information.
	*/
	protected $entities=array();
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
	/**
	@param string Classname of entities.
	*/
	public function __construct($name, HydratorInterface $hydr=NULL, MapperInterface $mapper=NULL, CacheInterface $cache=NULL){
		$this->setName($name);
		if($mapper) $this->setMapper($mapper);
		if($hydr) $this->setHydrator($hydr);
		if($cache) $this->setCache($cache);
	}
	/**
	@param string Classname of entities.
	
	@throws \InvalidArgumentException if name is an empty string.
	*/
	protected function setName($name){
		$name=(string)$name;
		if($name){
			$this->name=$name;
		}
		else{
			throw \InvalidArgumentException('Name cannot be an empty string');
		}
	}
	/**
	@return string
	*/
	public function getName(){
		return $this->name;
	}
	/**
	@return string
	*/
	public function __toString(){
		return $this->getName();
	}
	
	public function setMapper(MapperInterface $mapper){
		$this->mapper=$mapper;
		$this->mapper->setRepository($this);
	}
	/**
	@return MapperInterface
	*/
	public function getMapper(){
		return $this->mapper;
	}
	
	public function setHydrator(HydratorInterface $hydr){
		$this->hydrator=$hydr;
	}
	/**
	@return hydratorInterface
	*/
	public function getHydrator(){
		return $this->hydrator;
	}
	
	public function setCache(CacheInterface $cache){
		$this->cache=$cache;
	}
	/**
	@return CacheInterface
	*/
	public function getCache(){
		return $this->cache;
	}
	
	public function setEntityManager(EntityManager $em){
		$this->em=$em;
		if(!$em->hasRepository($this)){
			$em->addRepository($this);
		}
	}
	/**
	@return EntityManager
	*/
	public function getEntityManager(){
		return $this->em;
	}
	/**
	Finds Entity by ID.
	
	@return EntityInterface
	*/
	//TODO
	public function find($id){
		$id=(int)$id;
		if($id){
			
		}
		else{
			throw new \InvalidArgumentException('ID must be an integer greater then zero');
			return NULL;
		}
	}
	/**
	Finds Entities by criteria.
	
	@return array|null of EntityInterface.
	
	@throws \RuntimeException.
	*/
	//TODO
	public function findBy(array $filter=array(), $limit=0){
		if($limit<0) $limit=0;
		
		$is_enough=function(array $items) use($limit){
			if(!$limit) return FALSE;
			if(count($items)>$limit)
				return array_slice($items,0,$limit);
			elseif($count($items)==$limit)
				return $items;
			else return FALSE;
		};
	
		$found=array();//Entities found
		/*forst look in entities prop, add resulting Entities' IDs to an array,
		then look in cache, givin $not_in_ids to it, add resulting Entities' IDs to that array,
		and finaly look for entities in DB through mapper
		*/
		$found=$this->findStored($filter, $limit);
		if($res=$is_enough($found)) return $res;
		unset($res);
		
		//Work with cache similar to mapper
		//TODO
		//Working with mapper
		if(!$this->mapper){
			throw new \RuntimeException('Cannot find entities without mapper');
			return NULL;
		}
		$mapper_found=$this->mapper->find(array(),$filter,$limit,self::extractEntitiesIds($found));
		if($mapper_found){
			$this->addAllToStore($mapper_found);
			$found=array_merge($found,$mapper_found);
		}
		unset($mapper_found);
		
		if($res=$is_enough($found)) return $res;
		return $found;
	}
	/**
	Finds one entity by criteria.
	
	@return EntityInterface|null
	*/
	public function findOneBy(array $filter=array()){
		$res=$this->findBy($filter,1);
		
		if($res) return $res[0];
		
		return NULL;
	}
	/**
	@return array of EntityInterface.
	*/
	public function findAll(){
		return $this->findBy();
	}
	/**
	Looks for entities stored in prop.
	
	@param array $ids of IDs, if given filter entities only by their IDs.
	@param array $filter Criteria.
	@param int $limit
	
	@return array
	*/
	//TODO
	protected function findStored(array $ids=array(), array $filter=array(), $limit=0){
		return array();
	}
	/**
	Adds entity to object's storage.
	
	@param EntityInterface $e Entity.
	@param int Flush action.
	*/
	//TODO
	protected function addToStore(EntityInterface $e, $f_action=self::FLUSH_ACTION_NONE){
	
	}
	/**
	Adds multiple entities to object's storage.
	
	@param array $es Of EntityInterface.
	@param int $f_action Flush action for all entities.
	*/
	protected function addAllToStore(array $es, $f_action=self::FLUSH_ACTION_NONE){
		foreach($es as $e){
			$this->addToStore($e,$f_action);
		}
	}
	/**
	Clears stored entities.
	*/
	protected function clearStored(){
		$this->entities=array();
	}
	/**
	Extacts entities' IDs from array.
	
	@param array Of EntityInterface.
	
	@return array Of int.
	*/
	private static function extractEntitiesIds(array $es){
		$ids=array();
		foreach($es as $e){
			if($e->id()) $ids[]=$e->id();
		}
		
		return $ids;
	}
}
?>
