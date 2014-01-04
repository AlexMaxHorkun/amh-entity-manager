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
	protected $entities=array();
	
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
	
	public function add(Entity $e){
		$this->addToMap($e);
		return $e->id();
	}
	/**
	Marks entity to be updated.
	*/
	public function update(Entity $e){
		if(($ind=$this->indexOf($e))>=0 && $this->entities[$ind]->getEntity()->id()){
			$this->entities[$ind]->setFlushAction(Container::FLUSH_ACTION_UPDATE);
		}
	}
	/**
	Removes entity from identity map.
	*/
	public function untrack(Entity $e){
		if(($ind=$this->indexOf($e))>=0){
			unset($this->entities[$ind]);
		}
	}
	/**
	Marks entity's flush action as remove.
	*/
	public function remove(Entity $e){
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
			if($e instanceof Entity){
				if($e===$data->getEntity()){
					return $key;
				}
			}
			elseif($e==$data->getEntity()->id()){
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
	@throws \InvalidArgumentException If $e is array and it has no ID in it.
	@throws \RuntimeException If entity is already in identity map.
	
	@return Entity
	*/
	public function addToMap($e, $f_action=Container::FLUSH_ACTION_NONE){
		if(!($e instanceof Entity || is_array($e))){
			throw new \InvalidArgumentException('Given argument is not an Entity or Entity data');
		}
		if(is_array($e)){
			try{
				$this->getHydrator()->extractId($e);
			}
			catch(\Exception $e){
				throw new \InvalidArgumentException('array given to '.get_class($this).'::'.__FUNCTION__.' contains no ID');
			}
		}
		if((($e instanceof Entity)&&!$this->has($e)) || (is_array($e)&&!$this->has($this->getHydrator()->extractId($e)))){
			$e_cont=new Container();
			$this->entities[]=$e_cont;
			$e_cont->setFlushAction($f_action);
			if($e instanceof Entity){
				$e_cont->setEntity($e);
			}
			elseif(is_array($e)){
				$e_cont->setData($e);
				$e_cont->setEntity($this->getHydrator()->create($this->getHydrator()->extractId($e)));				
			}
			$e_cont->getEntity()->setRepository($this->getRepository());
			return $e_cont->getEntity();
		}
		else{
			throw new \RuntimeException('Cannot add existing entity');
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
	Loads entity if already having data provided.
	
	@param Entity
	@return bool TRUE on success.
	*/
	public function load(Entity $e){
		if(($ind=$this->indexOf($e))!=-1){
			if($this->entities[$ind]->getData()){
				$this->getHydrator()->hydrate($this->entities[$ind]->getEntity(),$this->entities[$ind]->getData());
				return TRUE;
			}
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
			return $this->entities[$ind]->getFlushAction();
		}
		return FALSE;
	}
	/**
	@return array List of entities to add, update or remove.
	*/
	public function unitOfWork(){
		$uow=array('add'=>array(), 'update'=>array(), 'remove'=>array());
		foreach($this->entities as $e_data){
			if($e_data->getFlushAction()==Container::FLUSH_ACTION_INSERT){
				$uow['add'][]=$e_data->getEntity();
			}
			elseif($e_data->getFlushAction()==Container::FLUSH_ACTION_UPDATE){
				$uow['update'][]=$e_data->getEntity();
			}
			elseif($e_data->getFlushAction()==Container::FLUSH_ACTION_REMOVE){
				$uow['remove'][]=$e_data->getEntity();
			}
		}
		return $uow;
	}
	/**
	Clears unit of work (sets action param to NONE).
	
	@return void
	*/
	public function clearUnitOfWork(){
		foreach($this->entities as $key=>$e_data){
			$this->entities[$key]->setFlushAction(Container::FLUSH_ACTION_NONE);
		}
	}
	/**
	Clears entity array.
	
	@return void
	*/
	public function clear(){
		$this->entities=array();
	}
	
	//ArrayAccess
	public function offsetExists($i){
		return isset($this->entities[$i]);
	}
	
	public function offsetGet($i){
		if(isset($this->entities[$i])){
			return $this->entities[$i]->getEntity();
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
