<?php
namespace AMH\EntityManager\Repository;

use AMH\EntityManager\Repository\Mapper\AbstractMapper as Mapper;
use AMH\EntityManager\Entity\AbstractEntity as Entity;
use AMH\EntityManager\Repository\Mapper\SelectStatement as SelSttm;
use AMH\EntityManager\Entity\Hydrator\AbstractHydrator as Hydrator;
use AMH\EntityManager\Repository\Repository as Repository;
use AMH\EntityManager\Repository\EntityContainer as Container;

/**
Manages loaded from mappers entities.

@author Alex Horkun mindkilleralexs@gmail.com
*/
class IdentityMap extends Mapper implements \ArrayAccess{
	/**
	@var Container
	*/
	protected $container=NULL;
	
	public function __construct(Repository $r, Hydrator $h, Container $c=NULL){
		parent::__construct($r,$h);
		if($c){
			$this->setContainer($c);
		}
		else{
			$this->setContainer(new Container());
		}
	}
	
	public function setContainer(Container $c){
		$this->container=$c;
	}
	/**
	@return Container
	*/
	public function getContainer(){
		return $this->container;
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
	
	public function add($e){
		$this->addToMap($e);
		if($e instanceof Entity){
			return $e->id();
		}
		elseif(is_array($e)){
			return $this->getHydrator()->extractId($e);
		}
	}
	/**
	Marks entity to be updated.
	
	@param int|Entity ID or Entity obj.
	*/
	public function update($e){
		if(($ind=$this->indexOf($e))>=0 && $this->entities[$ind]->getEntity()->id()){
			$this->entities[$ind]->setFlushAction(Container::FLUSH_ACTION_UPDATE);
		}
	}
	
	public function remove(Entity $e){
		if(($ind=$this->indexOf($e))>=0){
			unset($this->entities[$ind]);
		}
	}
	/**
	Marks entity's flush action as remove.
	*/
	public function delete(Entity $e){
		if(($ind=$this->indexOf($e))&&$ind!=-1){
			$this->entities[$ind]->setAction(Container::FLUSH_ACTION_REMOVE);
		}
		else{
			$this->addToMap($e,Container::FLUSH_ACTION_REMOVE);
		}
	}
	/**
	@param int|Entity Entity object or ID.
	
	@return int Idnex or -1 if not found.
	*/
	public function indexOf($e){
		if(!($e instanceof Entity)){
			$e=(int)$e;
		}
		foreach($this->entities as $key=>$data){
			if((($e instanceof Entity) && $data->getEntity()===$e) || (gettype($e)=='int' && ($e>=0) && $e==$data->getEntity()->id())){
				return $key;
			}
		}
		
		return -1;
	}
	/**
	Checks if map has entity.
	
	@param int|Entity Entity object or ID.
	
	@return bool
	*/
	public function has($e){
		return ($this->indexOf($e)!=-1);
	}
	/**
	Adds entity to object's storage.
	
	@param Entity|array $e Entity/Entity data.
	@param int Flush action.
	@param bool Is loaded?.
	
	@throws \InvalidArgumentException if wrong Flush Action given.
	@throws \InvalidArgumentException If $e is not an array or Entity.
	
	@return Entity
	*/
	public function addToMap($e, $f_action=self::FLUSH_ACTION_NONE, $loaded=TRUE){
		if(!($e instanceof Entity || is_array($e))){
			throw new \InvalidArgumentException('Given argument is not an Entity or Entity data');
		}
		$loaded=(bool)$loaded;
		switch($f_action){
		case self::FLUSH_ACTION_NONE:
		case self::FLUSH_ACTION_INSERT:
		case self::FLUSH_ACTION_UPDATE:
		case self::FLUSH_ACTION_REMOVE:
			if(($ind=$this->indexOf((is_array($e))? $this->getHydrator()->extractId($e): $e))==-1){
				if($e instanceof Entity){
					$entity=$e;
				}
				else{
					$entity=$this->getHydrator()->create();
					$entity->setId($this->getHydrator()->extractId($e));
					$entity->setRepository($this->getRepository());
				}
				$record=array(
					'entity'=>$entity,
					'action'=>(int)$f_action,
					'loaded'=>(is_array($e))? FALSE:$loaded,
					'data'=>(is_array($e))? $e:NULL,
				);
				$this->entities[]=$record;
				return $record['entity'];
			}
			elseif($loaded && !$this->entities[$ind]['loaded']){
				$this->entities[$ind]['loaded']=TRUE;
				$this->getHydrator()->hydrate($this->entities[$ind]['entity'],(is_array($e))? $e : $this->getHydrator()->extract($e));
				return $this->entities[$ind]['entity'];
			}
			break;
		default:
			throw new \InvalidArgumentException('Invalid flush action given');
			break;
		}
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
		$ind=$this->indexOf($e);
		if($ind!=-1)
			return $this->entities[$ind]['loaded'];
		return FALSE;
	}
	/**
	Marks entity as loaded.
	
	@return void
	*/
	public function setLoaded(Entity $e){
		$ind=$this->indexOf($e);
		if($ind>=0)
			$this->entities[$ind]['loaded']=TRUE;
	}
	/**
	Loads entity if already having data provided.
	
	@param Entity
	@return bool TRUE on success.
	*/
	public function load(Entity $e){
		if(($ind=$this->indexOf($e))!=-1){
			if(!$this->entities[$ind]['loaded']){
				if($this->entities[$ind]['data']){
					$this->getHydrator()->hydrate($this->entities[$ind]['entity'],$this->entities[$ind]['data']);
				}
				else{
					return FALSE;
				}
			}
			return TRUE;
		}
		return FALSE;
	}
	/**
	@return int count of entities in identity map.
	*/
	public function count(){
		return count($this->entities);
	}
	/**
	@return int flush action
	*/
	public function flushAction(Entity $e){
		if(($ind=$this->indexOf($e))>=0){
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
