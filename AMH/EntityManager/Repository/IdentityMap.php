<?php
namespace AMH\EntityManager\Repository;

use AMH\EntityManager\Repository\Mapper\AbstractMapper as Mapper;
use AMH\EntityManager\Entity\AbstractEntity as Entity;
use AMH\EntityManager\Repository\Mapper\SelectStatement as SelSttm;
use AMH\EntityManager\Entity\Hydrator\AbstractHydrator as Hydrator;

/**
Manages loaded from mappers entities.

@author Alex Horkun mindkilleralexs@gmail.com
*/
class IdentityMap extends Mapper implements \ArrayAccess{
	const FLUSH_ACTION_NONE=0;
	const FLUSH_ACTION_INSERT=1;
	const FLUSH_ACTION_UPDATE=2;
	const FLUSH_ACTION_REMOVE=3;
	
	/**
	@var array Of Entities, their flush action and loaded attribute.
	*/
	protected $entities=array();
	
	public function __construct(Repository $r, Hydrator $h){
		parent::__construct($r,$h);
	}
	/**
	@return array of Entity.
	*/
	public function find(SelSttm $select){
		return $this->findEntities($select);
	}
	/**
	@return array of Entity.
	*/
	protected function findEntities(SelSttm $select){
		$found=array();
		foreach($this->entities as $e){
			if(($ids=$select->getIds()) && !in_array($e->id(),$ids)){
				continue;
			}
			elseif(($ids=$select->getNotInIds()) && in_array($e->id(),$ids)){
				continue;
			}
			elseif(!($e['entity']->fitsCriteria($select->getFilter()))){
				continue;
			}
			else{
				$found[]=$e['entity'];
			}
			
			if($select->getLimit()>=count($found)){
				break;
			}
		}	
		return $found;
	}
	
	protected function loadEntityData($id){}
	
	public function load(Entity $e){}
	
	public function add(Entity $e){
		$this->addToMap($e);
		return $e->id();
	}
	
	public function update(Entity $e){}
	
	public function remove(Entity $e){
		if(($ind=$this->has($e))>=0){
			unset($this->entities[$ind]);
		}
	}
	/**
	Marks entity's flush action as remove.
	*/
	public function delete(Entity $e){
		if(($ind=$this->has($e))&&$ind!=-1){
			$this->entities[$ind]['action']=self::FLUSH_ACTION_REMOVE;
		}
		else{
			$this->addToMap($e,self::FLUSH_ACTION_REMOVE);
		}
	}
	/**
	Checks if entity is stored.
	
	@param int|Entity Entity object or ID.
	
	@return int Idnex or -1 if not found.
	*/
	public function has($e){
		if(!($e instanceof Entity)){
			$e=(int)$e;
		}
		foreach($this->entities as $key=>$data){
			if((($e instanceof Entity) && $data['entity']===$e) || (gettype($e)=='int' && ($e>=0) && $e==$data['entity']->id())){
				return $key;
			}
		}
		
		return -1;
	}
	/**
	Adds entity to object's storage.
	
	@param Entity $e Entity.
	@param int Flush action.
	@param bool Is loaded?.
	
	@throws \InvalidArgumentException if wrong Flush Action given.
	
	@return bool True if saved, FALSE if entity is already saved and loaded, otherwise if new given entity is loaded, extracts its data for already stored entity and makes it loaded.
	*/
	public function addToMap(Entity $e, $f_action=self::FLUSH_ACTION_NONE, $loaded=TRUE){
		$loaded=(bool)$loaded;
		switch($f_action){
		case self::FLUSH_ACTION_NONE:
		case self::FLUSH_ACTION_INSERT:
		case self::FLUSH_ACTION_UPDATE:
		case self::FLUSH_ACTION_REMOVE:
			if(($ind=$this->has($e))!=-1){
				$this->entities[]=array(
					'entity'=>$e,
					'action'=>(int)$f_action,
					'loaded'=>$loaded,
				);
				return TRUE;
			}
			elseif($loaded && !$this->entities[$ind]['loaded']){
				$this->getHydrator()->hydrate($this->entities[$ind]['entity'],$this->getHydrator()->extract($e));
				$this->entities[$ind]['loaded']=TRUE;
				return TRUE;
			}
			break;
		default:
			throw new \InvalidArgumentException('Invalid flush action given');
			break;
		}
		
		return FALSE;
	}
	/**
	Adds multiple entities to object's storage.
	
	@param array $es Of Entity.
	@param int $f_action Flush action for all entities.
	*/
	public function addAllToMap(array $es, $f_action=self::FLUSH_ACTION_NONE){
		foreach($es as $e){
			$this->addToMap($e,$f_action);
		}
	}
	/**
	Checks if entity was loaded.
	
	@param Entity
	
	@return bool
	*/
	public function isEntityLoaded(Entity $e){
		$ind=$this->has($e);
		if($ind>=0 && $this->entities[$ind]['loaded'])
			return TRUE;
					
		return FALSE;
	}
	/**
	@return int count of entities in identity map.
	*/
	public function count(){
		return count($this->entities);
	}
	/**
	Finds relative Entity, of not founds returns Entity obj only with ID.
	
	@param int ID.
	
	@return Entity
	
	@throws \RuntimeException
	*/
	public function findRelative($id){
		$id=(int)$id;
		if(($ind=$this->has($id))>=0){
			return $this->entities[$ind]['entity'];
		}
		else{
			if(!$this->getHydrator()){
				throw \RuntimeException('No hydrator provided');
			}
			$e=$this->getHydrator()->create();
			$e->setId($id);
			return $e;
		}
	}
	/**
	Marks entity to be updated.
	
	@param int|Entity ID or Entity obj.
	*/
	public function dirty($e){
		if(($ind=$this->has($e))>=0 && $this->entities[$ind]['entity']->id()){
			$this->entities[$ind]['action']=self::FLUSH_ACTION_UPDATE;
		}
	}
	/**
	@return int flush action
	*/
	public function flushAction(Entity $e){
		if(($ind=$this->has($e))>=0){
			return $this->entities[$ind]['action'];
		}
		return FALSE;
	}
	/**
	@return array List of entities to add, update or remove.
	*/
	public function unitOfWork(){
		$uow=array('add'=>array(), 'update'=>array(), 'remove'=>array());
		foreach($this->entities as $e_data){
			if($e_data['action']==self::FLUSH_ACTION_INSERT){
				$uow['add'][]=$e_data['entity'];
			}
			elseif($e_data['action']==self::FLUSH_ACTION_UPDATE){
				$uow['update'][]=$e_data['entity'];
			}
			elseif($e_data['action']==self::FLUSH_ACTION_REMOVE){
				$uow['remove'][]=$e_data['entity'];
			}
		}
		return $uow;
	}
	/**
	Clears unit of work (sets acton param to NONE).
	
	@return void
	*/
	public function clearUnitOfWork(){
		foreach($this->entities as $key=>$e_data){
			$this->entities[$key]['action']=self::FLUSH_ACTION_NONE;
		}
	}
	
	//ArrayAccess
	public function offsetExists($i){
		return isset($this->entities[$i]);
	}
	
	public function offsetGet($i){
		if(isset($this->entities[$i])){
			return $this->entities[$i]['entity'];
		}
		else{
			throw new \RuntimeException(get_class($this).'::'.__FUNCTION__.' offset "'.$i.'" does not exisits');
			return NULL;
		}
	}
	
	public function offsetSet($i,$val){}
	public function offsetUnset($i){}
}
?>
