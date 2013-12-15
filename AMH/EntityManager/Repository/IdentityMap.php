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
	const FLUSH_ACTION_REMVOE=3;
	
	/**
	@var array Of Entities, their flush action and loaded attribute.
	*/
	protected $entities=array();
	/**
	@return array of Entity.
	*/
	public function find(SelSttm $select){
		return $this->findEtnities($select);
	}
	/**
	@return array of Entity.
	*/
	protected function findEntities(SelSttm $select){
		$found=array();
		
		
		
		return $found;
	}
	
	protected function loadEntityData($id){}
	
	protected function load(Entity $e){}
	
	protected function add(Entity $e){
		$this->addToMap($e);
	}
	
	protected function update(Entity $e){}
	
	protected function remove(Entity $e){}
	/**
	Checks if entity is stored.
	
	@return int Idnex or -1 if not found.
	*/
	protected function has(Entity $e){
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
	protected function addToMap(AbstractEntity $e, $f_action=self::FLUSH_ACTION_NONE){
		if(!$this->has($e)){
			switch($f_action){
			case self::FLUSH_ACTION_NONE:
			case self::FLUSH_ACTION_INSERT:
			case self::FLUSH_ACTION_UPDATE:
			case self::FLUSH_ACTION_REMVOE:
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
	
	@param array $es Of AbstractEntity.
	@param int $f_action Flush action for all entities.
	*/
	protected function addAllToMap(array $es, $f_action=self::FLUSH_ACTION_NONE){
		foreach($es as $e){
			$this->addToStore($e,$f_action);
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
}
?>
