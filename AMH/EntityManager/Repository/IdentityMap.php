<?php
namespace AMH\EntityManager\Repository;

use AMH\EntityManager\Repository\Mapper\AbstractMapper as Mapper;
use AMH\EntityManager\Entity\AbstractEntity as Entity;
use AMH\EntityManager\Repository\Mapper\SelectStatement as SelSttm;

/**
Manages loaded from mappers entities.

@author Alex Horkun mindkilleralexs@gmail.com
*/
class IdentityMap extends Mapper{
	const FLUSH_ACTION_NONE=0;
	const FLUSH_ACTION_INSERT=1;
	const FLUSH_ACTION_UPDATE=2;
	const FLUSH_ACTION_REMOVE=3;
	
	/**
	@var array Of Entities, their flush action and loaded attribute.
	*/
	protected $entities=array();
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
		
		
		
		return $found;
	}
	
	protected function loadEntityData($id){}
	
	public function load(Entity $e){}
	
	public function add(Entity $e){
		$this->addToMap($e);
	}
	
	public function update(Entity $e){}
	
	public function remove(Entity $e){
		if(($ind=$this->has($e))>=0){
			unset($this->entities[$ind]);
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
			if((($e instanceof Entity) && $data['entity']->id()==$e->id()) || $e==$data['entity']->id()){
				return $key;
			}
		}
		
		return -1;
	}
	/**
	Adds entity to object's storage.
	
	@param Entity $e Entity.
	@param int Flush action.
	
	@throws \InvalidArgumentException if wrong Flush Action given.
	
	@return bool True if saved, FALSE if entity is already saved.
	*/
	public function addToMap(Entity $e, $f_action=self::FLUSH_ACTION_NONE){
		if($this->has($e) == -1){
			switch($f_action){
			case self::FLUSH_ACTION_NONE:
			case self::FLUSH_ACTION_INSERT:
			case self::FLUSH_ACTION_UPDATE:
			case self::FLUSH_ACTION_REMOVE:
				$this->entities[]=array(
					'entity'=>$e,
					'action'=>(int)$f_action,
					'loaded'=>TRUE,
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
		if(($int=$this->has($e))>=0){
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
}
?>
