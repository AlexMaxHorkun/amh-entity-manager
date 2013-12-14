<?php
namespace AMH\EntityManager\Repository;

use AMH\EntityManager\Entity\AbstractEntity;
use AMH\EntityManager\Repository\Mapper\AbstractMapper as Mapper;
use AMH\EntityManager\Cache\AbstractCache as Cache;
use AMH\EntityManager\EntityManager;
use AMH\EntityManager\Entity\Hydrator\AbstractHydrator as Hydrator;

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
	public function __construct($name, Hydrator $hydr=NULL, Mapper $mapper=NULL, Cache $cache=NULL){
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
	Finds Entity by ID.
	
	@return AbstractEntity
	*/
	public function find($id){
		$id=(int)$id;
		if($id){
			$e=$this->findByIds(array((int)$id));
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
	Finds entities by IDs.
	
	@param array of IDs.
	
	@return array of Entity.
	*/
	public function findByIds(array $ids){
		$filter=array('__id'=>array());
		foreach($ids as $id){
			if((int)$id>0){
				$filter['__id'][]=(int)$id;
			}
			else{
				throw new \InvalidArgumentException('First argument must containt only ids (ints > 0)');
				return;
			}
		}
		
		return $this->findBy($filter);
	}
	/**
	Finds Entities by criteria.
	
	@return array|null of AbstractEntity.
	
	@throws \RuntimeException.
	*/
	public function findBy(array $filter=array(), $limit=0){
		/*forst look in entities prop, add resulting Entities' IDs to an array,
		then look in cache, givin $not_in_ids to it, add resulting Entities' IDs to that array,
		and finaly look for entities in DB through mapper
		*/
		if($limit<0) $limit=0;
		$found=array();//Entities found
		
		$isEnough=function() use($found,$limit){
			if(!$limit) return FALSE;
			if(count($items)>$limit)
				return array_slice($items,0,$limit);
			elseif($count($items)==$limit)
				return $items;
			else return FALSE;
		};
		
		$extractIds=function() use($found){
			$ids=array();
			foreach($found as $e){
				if($e->id()) $ids[]=$e->id();
			}
			
			return $ids;
		};
		
		$found=$this->findStored($filter, $limit);
		
		if($res=$isEnough()) return $res;
		unset($res);
		
		//Work with cache similar to mapper
		if($this->cache){
			$found=array_merge($found, $this->findWithMapper($this->cache,$filter,$limit,$extractIds()));
		}
		
		if($res=$isEnough()) return $res;
		
		//Working with mapper
		if(!$this->mapper){
			throw new \RuntimeException('Cannot find entities without DB mapper');
			return NULL;
		}
		$found=array_merge($found, $this->findWithMapper($this->mapper,$filter,$limit,$extractIds()));	
			
		if($res=$isEnough()) return $res;
		return $found;
	}
	/**
	Finds one entity by criteria.
	
	@return AbstractEntity|null
	*/
	public function findOneBy(array $filter=array()){
		$res=$this->findBy($filter,1);
		
		if($res) return $res[0];
		
		return NULL;
	}
	/**
	@return array of AbstractEntity.
	*/
	public function findAll(){
		return $this->findBy();
	}
	/**
	Looks for entities stored in prop.
	
	@param array $filter Criteria.
	@param int $limit
	
	@return array of (int)IDs.
	
	@throws \RuntimeException If no hydrator set.
	@throws \RuntimeException If filter has key which extrated entity hasn't.
	*/
	protected function findStored(array $filter=array(), $limit=0){
		if(!$this->hydrator){
			throw new \RuntimeException('Hydrator must be set to filter stored entities');
			return;
		}
		
		$found=array();
		foreach($this->entities as $e){
			$e_data=$this->hydrator->extract($e);
			foreach($filter as $field=>$val){
				$fits=TRUE;
				if(isset($e_data[$field])){
					if(is_object($val) && ($val instanceof AbstractEntity)){
						$val=$val->id();
					}
					//using STRICT comparation, might change this later
					if($e_data[$field] !== $val){
						$fits=FALSE;
					}
				}
				else{
					throw new \RuntimeException('Cannot find field "'.$field.'" in extracted by '.get_class($this->hydrator).' entity');
				}
				
				if($fits){
					$found[]=$e;
				}
			}
		}
		
		return $found;
	}
	/**
	@param AbstractEntity
	
	@return int Index or -1 if not found.
	*/
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
	Looks for entities in db or cache.
	
	@param Mapper|null If not given will use db mapper.
	@param array $filter Criteria.
	@param int $limit
	@param array of (int)IDs not to look for.
	
	@return array of AbstractEntity.
	*/
	private function findWithMapper(Mapper $mapper=NULL, array $filter=array(), $limit=0, array $not_in_ids=array()){
		if(!$mapper){
			if($this->mapper){
				$mapper=$this->mapper;
			}
			else{
				throw new \RuntimeException("Mapper need's to be set");
				return;
			}
		}
		$res=$mapper->find($filter,$limit,$not_in_ids);
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
	public function loadOne(AbstractEntity $e){
		
	}
	/**
	@return bool
	*/
	//TODO
	public function isLoaded(AbstractEntity $e){
	
	}
}
?>
