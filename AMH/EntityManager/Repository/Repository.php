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
	private $hydrator=NULL;
	/**
	@var EntityManager
	*/
	private $em=NULL;
	/**
	@param string Classname of entities.
	*/
	public function __construct($name, Hydrator $hydr, Mapper $mapper=NULL, Cache $cache=NULL, IdentityMap $map=NULL){
		$this->setName($name);
		if($mapper) $this->setMapper($mapper);
		$this->setHydrator($hydr);
		$this->setIdentityMap($map);
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
		if($this->mapper && !$this->mapper->getHydrator()){
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
		
		$found=$this->findWithMapper($this->identity_map,$select);
		if($limit && count($found) >= $limit){
			return array_slice($found, 0 ,$limit);
		}
		
		$select->setNotInIds($extractIds());
		if($select->getLimit()){
			$select->setLimit($select->getLimit()-count($found));
		}
		//Work with cache similar to mapper
		if($this->cache){
			$found=array_merge($found, $this->findWithMapper($this->cache, $select));
			$select->setNotInIds($extractIds());
			if($limit && count($found) >= $limit){
				return array_slice($found, 0 ,$limit);
			}
			if($select->getLimit()){
				$select->setLimit($select->getLimit()-count($found));
			}
		}		
				
		//Working with mapper
		if(!$this->mapper){
			throw new \RuntimeException('Cannot find entities without DB mapper');
			return NULL;
		}
		$found=array_merge($found, $this->findWithMapper($this->mapper,$select));
			
		if($limit && count($found) >= $limit){
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
	Looks for entities in db or cache.
	
	@param Mapper|null If not given will uses identity map.
	@param array $filter Criteria.
	@param int $limit
	@param array of (int)IDs not to look for.
	
	@return array of AbstractEntity.
	*/
	private function findWithMapper(Mapper $mapper=NULL, SelectStatement $select){
		if(!$mapper){
			$mapper=$this->identity_map;
		}
		$res=$mapper->find($select);
		if($res && $mapper!=$this->identity_map){
			foreach($res as $key=>$e){
				if($this->identity_map->addToMap($e)){
					$e->setRepository($this);
				}
				elseif(($ind=$this->identity_map->has($e))!=-1){
					$res[$key]=$this->identity_map[$ind];
				}
				else{
					unset($res[$key]);
				}
			}
		}
		
		return $res;
	}
	/**
	Adds entity.
	*/
	public function persist(AbstractEntity $e){
		return $this->identity_map->addToMap($e,IdentityMap::FLUSH_ACTION_INSERT);
	}
	/**
	Untracks entity object.
	*/
	public function untrack(AbstractEntity $e){
		$this->identity_map->remove($e);
	}
	/**
	Marks an entity as dirty.
	*/
	public function dirty(AbstractEntity $e){
		$this->identity_map->dirty($e);
	}
	/**
	Loads entity.
	
	@return bool If loaded successfuly.
	
	@throws \RuntimeException If no mapper given.
	*/
	public function load(AbstractEntity $e){
		if(!$this->isLoaded($e)){
			$loaded=FALSE;
			if($this->cache){
				$loaded=$this->cache->load($e);
			}
			if(!$loaded){
				if(!$this->mapper){
					throw \RuntimeException('Cannot load entity without db mapper.');
				}
				$loaded=$this->mapper->load($e);
			}
			return $loaded;
		}
	}
	/**
	@return bool
	*/
	public function isLoaded(AbstractEntity $e){
		return $this->identity_map->isEntityLoaded($e);
	}
	/**
	Searches for relative Entity in indentity map.
	
	@param int ID.
	
	@return Entity
	*/
	public function relative($id){
		return $this->identity_map->findRelative($id);
	}
	/**
	Searches for relatives Entity in indentity map.
	
	@param array IDs.
	
	@return array of Entity.
	*/
	public function relatives(array $ids){
		$es=array();
		foreach($ids as $id){
			$es[]=$this->identity_map->findRelative($id);
		}
		return $es;
	}
	/**
	Saves changes done to entities.
	
	@return void
	*/
	public function flush(){
		if(!$this->mapper){
			throw new \RuntimeException('Can\'t do shit without mapper');
		}
		
		$uow=$this->identity_map->unitOfWork();
		foreach($uow['add'] as $e){
			$id=(int)$this->mapper->add($e);
			if($id>=0){
				$e->setId($id);
			}
			else{
				throw new \RuntimeException('Mapper '.get_class($this->mapper).'::add did not return new Entity\'s ID');
			}
		}
		foreach($uow['update'] as $e){
			$this->mapper->update($e);
		}
		foreach($uow['remove'] as $e){
			$this->mapper->remove($e);
			$this->identity_map->remove($e);
		}
		$this->identity_map->clearUnitOfWork();
	}
}
?>
