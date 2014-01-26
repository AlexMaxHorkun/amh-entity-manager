<?php
namespace AMH\EntityManager\Entity\Collection;

/**
@author Alex Horkun mindkilleralexs@gmail.com

Helps to track one-to-many and many-to-many relations.
*/
class Collection implements \ArrayAccess, \IteratorAggregate, \Countable{
	/**
	@var array of Entity.
	*/
	private $es=array();
	
	public function __construct(array $arr=NULL){
		if($arr!==NULL){
			$this->addAll($arr,Record::STATUS_NONE);
		}
	}
	
	public function add(Entity $e,$s=Record::STATUS_NEW){
		$this->es[]=new Record($e,$s);
	}
	/**
	@param array of Entity.
	*/
	public function addAll(array $arr,$s=Record::STATUS_NEW){
		foreach($arr as $e){
			$this->es[]=new Record($e,$s);
		}
	}
	/**
	@param Entity|int Entity obj or index.
	
	@return bool If marked as removed.
	*/
	public function remove($e){
		foreach($this->es as $key=>$rec){
			if((($e instanceof Entity) && $rec->getEntity()===$e) || ($key==$e)){
				$rec->setStatus(Record::STATUS_REMOVED);
				return TRUE;
			}
		}
		return FALSE;
	}
	/**
	Sets all statuses as NONE.
	
	Must be invoked after mapper flushed all changes.
	*/
	public function flushed(){
		foreach($this->es as $rec){
			$rec->setStatus(Record::STATUS_NONE);
		}
	}
	/**
	Gets Entity with such index, if it hasn't status REMOVED.
	
	@param int Key.
	
	@return Entity|null
	*/
	public function get($i){
		if(isset($this->es[$i])){
			if($this->es[$i]->getStatus()!=Record::STATUS_REMOVED){
				return $this->es[$i]->getEntity();
			}
		}
		return NULL;
	}
	/**
	Gets Entity's with such status.
	
	@param int $status Status.
	@param bool $ifis If TRUE returs Entities with such status, else returns Entities with not such status.
	
	@return array of Entity.
	*/
	protected function entitiesWithStatus($status=Record::STATUS_NONE,$ifis=TRUE){
		$found=array();
		foreach($this->es as $rec){
			if(($ifis && $rec->getStatus()==$status) || (!$ifis && $rec->getStatus()!=$status)){
				$found[]=$rec->getEntity();
			}
		}
		return $found;
	}
	/**
	@return array of Entity.
	*/
	public function added(){
		return $this->entitiesWithStatus(Record::STATUS_NEW);
	}
	/**
	@return array of Entity.
	*/
	public function removed(){
		return $this->entitiesWithStatus(Record::STATUS_REMOVED);
	}
	//IteratorAggregate interface
	public function getIterator(){
		return new \ArrayIterator($this->entitiesWithStatus(Record::STATUS_REMOVED,FALSE));
	}
	//Countable interface
	public function count(){
		return count($this->es);
	}
	//ArrayAccess interface
	public function offsetExists($i){
		return isset($this->es[$i]);
	}
	
	public function offsetGet($i){
		return $this->get($i);
	}
	/**
	@throws \RuntimeException
	*/
	public function offsetSet($i,$e){
		if(!isset($this->es[$i])){
			$this->add($e);
		}
		else{
			throw new \RuntimeException('Setting value on predefined index is forbidden (index given - '.$i.')');
		}
	}
	
	public function offsetUnset($i){
		$this->remove($i);
	}
}
?>
