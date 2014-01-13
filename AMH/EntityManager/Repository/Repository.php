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
@author Alex Horkun mindkilleralexs@gmail.com

Repository is used to work with Mapper and to store entitties in memory and cache.
*/
class Repository{
	/**
	@var string
	*/
	private $name=NULL;
	/**
	@var HydaratorInterface to fetch entity from array/extract entity to array.
	*/
	private $hydrator=NULL;
	/**
	@var EntityManager
	*/
	private $em=NULL;
	/**
	@var array of Mapper.
	*/
	protected $mappers=array('identity_map'=>NULL, 'cache'=>NULL, 'database'=>NULL);
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
		$this->mappers['database']=$mapper;
		$this->mappers['database']->setRepository($this);
		if(!$this->mappers['database']->getHydrator()&&$this->hydrator){
			$this->mappers['database']->setHydrator($this->hydrator);
		}
	}
	/**
	@return Mapper
	*/
	public function getMapper(){
		return $this->mappers['database'];
	}
	
	public function removeMapper(){
		$this->mappers['database']=NULL;
	}
	
	public function setHydrator(Hydrator $hydr){
		$this->hydrator=$hydr;
		$hydr->setRepository($this);
		if($this->mappers['database'] && !$this->mappers['database']->getHydrator()){
			$this->mappers['database']->setHydrator($this->hydrator);
		}
	}
	/**
	@return Hydrator
	*/
	public function getHydrator(){
		return $this->hydrator;
	}
	
	public function setCache(Cache $cache){
		$this->mappers['cache']=$cache;
	}
	/**
	@return Cache
	*/
	public function getCache(){
		return $this->mappers['cache'];
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
		$this->mappers['identity_map']=$m;
	}
	/**
	@return IdentityMap
	*/
	public function getIdentityMap(){
		return $this->mappers['identity_map'];
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
		$found=array();//Entities found
		foreach($this->mappers as $m){
			if(!$m) continue;
			$found=array_merge($found,$m->find($select));
			if($select->getLimit()){
				if($select->getLimit()<=count($found)){
					break;
				}
				else{
					$select->setLimit($select->getLimit()-count($found));
				}
			}
			$ids=array();
			foreach($found as $e){
				if($e->id()){
					$ids[]=$e->id();
				}
			}
			$select->setNotInIds($ids);			
		}
		if($select->getLimit() && $found){
			$found=array_slice($found,0,$select->getLimit());
		}
		return $found;
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
	Adds entity.
	
	@return bool On success.
	*/
	public function persist(AbstractEntity $e){
		if($this->mappers['identity_map']->addToMap($e,EntityContainer::FLUSH_ACTION_INSERT)){
			$e->setLoaded(TRUE);
			return TRUE;
		}
		
		return FALSE;
	}
	/**
	Adds entity to identity map.
	
	@param array|Entity
	
	@return Entity
	*/
	public function addToIdentityMap($e){
		return $this->mappers['identity_map']->addToMap($e);
	}
	/**
	Removes entity (identity map marks it's flush action as remove).
	*/
	public function remove(AbstractEntity $e){
		$this->mappers['identity_map']->remove($e);
	}
	/**
	Untracks entity object.
	*/
	public function untrack(AbstractEntity $e){
		$this->mappers['identity_map']->untrack($e);
	}
	/**
	Marks an entity as dirty.
	*/
	public function dirty(AbstractEntity $e){
		$this->mappers['identity_map']->update($e);
	}
	/**
	Gets entity from identity map, if can't - fetches new, adds to map as not loaded.
	
	@param int ID.
	
	@return AbstractEntity
	*/
	public function getEntity($id){
		$id=(int)$id;
		if(($ind=$this->mappers['identity_map']->indexOf($id))!=-1){
			return $this->mappers['identity_map'][$ind];
		}
		else{
			$e=$this->hydrator->create($id);
			$this->mappers['identity_map']->addToMap($e);
			return $e;
		}
	}
	/**
	Returns array of Entities returned by getEntity method.
	
	@param array of (int)IDs.
	
	@return array
	*/
	public function getEntities(array $ids){
		$es=array();
		foreach($ids as $id){
			$es[]=$this->getEntity($id);
		}
		return $es;
	}
	/**
	Loads entity.
	
	@return bool If loaded successfuly.
	
	@throws \RuntimeException If no mapper given.
	*/
	public function load(AbstractEntity $e){
		foreach($this->mappers as $m){
			if($m && $m->load($e)){
				return TRUE;
			}
		}
		return FALSE;
	}
	/**
	Inserts entity.
	
	@return void
	*/
	protected function flushInsert(AbstractEntity $e){
		if(!$this->mappers['database']){
			throw new \RuntimeException('No db mapper set');
		}
		if($this->getIdentityMap()->flushAction($e)==EntityContainer::FLUSH_ACTION_INSERT){
			$e->setId($this->mappers['database']->add($e));
			$this->mappers['identity_map']->clearFlushAction($e);
		}
	}
	/**
	Updates entity.
	
	@return void
	*/
	protected function flushUpdate(AbstractEntity $e){
		if(!$this->mappers['database']){
			throw new \RuntimeException('No db mapper set');
		}
		if($this->getIdentityMap()->flushAction($e)==EntityContainer::FLUSH_ACTION_UPDATE){
			$this->mappers['database']->update($e);
			$this->mappers['identity_map']->clearFlushAction($e);
		}
	}
	/**
	Removes entity.
	
	@return void
	*/
	protected function flushRemove(AbstractEntity $e){
		if(!$this->mappers['database']){
			throw new \RuntimeException('No db mapper set');
		}
		if($this->getIdentityMap()->flushAction($e)==EntityContainer::FLUSH_ACTION_REMOVE){
			$this->mappers['database']->remove($e);
			$this->untrack($e);
			$this->mappers['identity_map']->clearFlushAction($e);
		}
	}
	/**
	Saves changes done to entities.
	
	@return void
	*/
	public function flush(){
		if(!$this->mappers['database']){
			throw new \RuntimeException('Can\'t do shit without mapper');
		}
		while($e=$this->getIdentityMap()->nextEntityToRemove()){
			$this->flushRemove($e);
		}
		while($e=$this->getIdentityMap()->nextEntityToInsert()){
			$this->flushInsert($e);
		}	
		while($e=$this->getIdentityMap()->nextEntityToUpdate()){
			$this->flushUpdate($e);
		}	
	}
}
?>
