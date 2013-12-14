<?php
namespace AMH\EntityManager\Repository;

use AMH\EntityManager\Entity\AbstractEntity;
use AMH\EntityManager\Repository\Mapper\AbstractMapper as Mapper;
use AMH\EntityManager\Cache\AbstractCache as Cache;
use AMH\EntityManager\EntityManager;
use AMH\EntityManager\Entity\Hydrator\AbstractHydrator as Hydrator;
use AMH\EntityManager\Repository\Mapper\SelectStatement as SelectStatement;
use AMH\EntityManager\Repository\IdentityMap as IdentityMap;

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
	@var IdentityMap
	*/
	protected $identity_map;
	/**
	@var string
	*/
	private $name=NULL;
	/**
	@var Mapper
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
	public function __construct($name, Hydrator $hydr=NULL, Mapper $mapper=NULL, Cache $cache=NULL, IdentityMap $map=NULL){
		$this->setName($name);
		if($mapper) $this->setMapper($mapper);
		if($hydr) $this->setHydrator($hydr);
		if($cache) $this->setCache($cache);
		$this->setIdentityMap($map);
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
	
	public function setMapper(Mapper $mapper){
		$this->mapper=$mapper;
		$this->mapper->setRepository($this);
		if(!$this->mapper->getHydrator()&&$this->hydrator){
			$this->mapper->setHydrator($this->hydrator);
		}
	}
	/**
	@return Mapper
	*/
	public function getMapper(){
		return $this->mapper;
	}
	
	public function removeMapper(){
		$this->mapper=NULL;
	}
	
	public function setHydrator(Hydrator $hydr){
		$this->hydrator=$hydr;
		if(!$this->mapper->getHydrator()){
			$this->mapper->setHydrator($this->hydrator);
		}
	}
	/**
	@return Hydrator
	*/
	public function getHydrator(){
		return $this->hydrator;
	}
	
	public function setCache(Cache $cache){
		$this->cache=$cache;
	}
	/**
	@return Cache
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
	Sets identity map, If NULL given creates one by default.
	
	@param IdentityMap|null
	
	@throws \RuntimeException If no hydrator presented.
	*/
	public function setIdentityMap(IdentityMap $m=NULL){
		if(!$m){
			if($this->hydrator){
				$m=new IdentityMap($this,$this->hydrator);
			}
			else{
				throw new \RuntimeException('Cannot create default IdentityMap without hydrator');
			}
		}
		else{
			$m->setRepository($this);
		}
		$this->identity_map=$m;
	}
	/**
	@return IdentityMap
	*/
	public function getIdentityMap(){
		return $this->identity_map;
	}
	/**
	Finds Entity by ID.
	
	@return AbstractEntity
	*/
	public function find($id){
		$id=(int)$id;
		if($id){
			$ss=new SelectStatement();
			$ss->setIds($id);
			$e=$this->findBy($ss);
			if($e){
				return $e[0];
			}
			else{
				return NULL;
			}
		}
		else{
			throw new \InvalidArgumentException('ID must be an integer greater then zero');
			return NULL;
		}
	}
	/**
	Finds Entities by criteria.
	
	@return array|null of AbstractEntity.
	
	@throws \RuntimeException.
	*/
	public function findBy(SelectStatement $select){
		/*forst look in identity map, add resulting Entities' IDs to an array,
		then look in cache, givin $not_in_ids to it, add resulting Entities' IDs to that array,
		and finaly look for entities in DB through mapper
		*/
		$limit=$select->getLimit();
		if($limit<0) $limit=0;
		$found=array();//Entities found
		
		$extractIds=function() use($found){
			$ids=array();
			foreach($found as $e){
				if($e->id()) $ids[]=$e->id();
			}
			
			return $ids;
		};
		
		if(!$this->identity_map){
			throw \RuntimeException('IdentityMap is not set');
		}
		$found=$this->findWithMapper($this->identity_map,$select);
		if($limit && count($found >= $limit)){
			return array_slice($found, 0 ,$limit);
		}
		
		$select->setNotInIds($extractIds());
		//Work with cache similar to mapper
		if($this->cache){
			$found=array_merge($found, $this->findWithMapper($this->cache, $select));
			$select->setNotInIds($extractIds());
		}		
		if($limit && count($found >= $limit)){
			return array_slice($found, 0 ,$limit);
		}
		
		//Working with mapper
		if(!$this->mapper){
			throw new \RuntimeException('Cannot find entities without DB mapper');
			return NULL;
		}
		$found=array_merge($found, $this->findWithMapper($this->mapper,$select);	
			
		if($limit && count($found >= $limit)){
			return array_slice($found, 0 ,$limit);
		}
		else{
			return $found;
		}
	}
	/**
	Finds one entity by criteria.
	
	@return AbstractEntity|null
	*/
	public function findOneBy(SelectStatement $s){
		$s->setLimit(1);
		$res=$this->findBy($s);
		
		if($res) return $res[0];
		
		return NULL;
	}
	/**
	@return array of AbstractEntity.
	*/
	public function findAll(){
		return $this->findBy(new SelectStatement());
	}
	/**
	@param AbstractEntity
	
	@return int Index or -1 if not found.
	*/
	//TODO
	protected isEntityStored(AbstractEntity $e){
		foreach($this->entities as $key=>$data){
			if($data['entity']===$e){
				return $key;
			}
		}
		
		return -1;
	}
	/**
	Adds entity to object's storage.
	
	@param AbstractEntity $e Entity.
	@param int Flush action.
	
	@throws \InvalidArgumentException if wrong Flush Action given.
	
	@return bool True if saved, FALSE if entity is already saved.
	*/
	//TODO Move to IdentityMap
	protected function addToStore(AbstractEntity $e, $f_action=self::FLUSH_ACTION_NONE){
		if(!$this->isEntityStore($e)){
			switch($f_action){
			case self::FLUSH_ACTION_NONE:
			case self::FLUSH_ACTION_INSERT:
			case self::FLUSH_ACTION_UPDATE:
			case self::FLUSH_ACTION_REMVOE:
				$this->entities[]=array(
					'entity'=>$e,
					'action'=>(int)$f_action
				);
				break;
			default:
				throw new \InvalidArgumentException('Invalid flush action given');
				break;
			}
			
			return TRUE;
		}
		
		return FALSE;
	}
	/**
	Adds multiple entities to object's storage.
	
	@param array $es Of AbstractEntity.
	@param int $f_action Flush action for all entities.
	*/
	//TODO Move to IdentityMap
	protected function addAllToStore(array $es, $f_action=self::FLUSH_ACTION_NONE){
		foreach($es as $e){
			$this->addToStore($e,$f_action);
		}
	}
	/**
	Clears stored entities.
	*/
	//TODO Move to IdentityMap
	protected function clearStored(){
		$this->entities=array();
	}
	/**
	Looks for entities in db or cache.
	
	@param Mapper|null If not given will use db mapper.
	@param array $filter Criteria.
	@param int $limit
	@param array of (int)IDs not to look for.
	
	@return array of AbstractEntity.
	*/
	//TODO Change according to IdentityMap
	private function findWithMapper(Mapper $mapper=NULL, SelectStatement $select){
		if(!$mapper){
			if($this->mapper){
				$mapper=$this->mapper;
			}
			else{
				throw new \RuntimeException("Mapper need's to be set");
				return;
			}
		}
		$res=$mapper->find($select);
		if($res){
			$this->addAllToStore($res);
		}
		
		return $res;
	}
	/**
	Untracks entity object.
	*/
	//TODO
	public function untrack(AbstractEntity $e){
	
	}
	/**
	Marks an entity as dirty.
	*/
	//TODO
	public function dirty(AbstractEntity $e){
	
	}
	/**
	Loads entity.
	*/
	//TODO
	public function load(AbstractEntity $e){
		
	}
	/**
	@return bool
	*/
	//TODO
	public function isLoaded(AbstractEntity $e){
	
	}
}
?>
