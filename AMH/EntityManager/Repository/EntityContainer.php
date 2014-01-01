<?php
namespace AMH\EntityManager\Repository;

use AMH\EntityManager\Entity\AbstractEntity as Entity;

/**
@author Alex Horkun mindkilleralexs@gmail.com

Stores entity info for identity map.
*/
class EntityContainer{
	const FLUSH_ACTION_NONE=0;
	const FLUSH_ACTION_INSERT=1;
	const FLUSH_ACTION_UPDATE=2;
	const FLUSH_ACTION_REMOVE=3;
	
	/**
	@var Entity
	*/
	private $entity;
	/**
	@var array Data received from mapper.
	*/
	private $data;
	/**
	@var int Flush action.
	*/
	private $action=self::FLUSH_ACTION_NONE;
	
	public function __construct(Entity $e=NULL,$action=0,array $data=NULL){
		if($e){
			$this->setEntity($e);
		}
		if($data){
			$this->setData($data);
		}
		if($action){
			$this->setFlushAction($action);
		}
	}
	
	public function setEntity(Entity $e){
		$this->entity=$e;
	}
	/**
	@return Entity
	*/
	public function getEntity(){
		return $this->entity;
	}
	
	public function setFlushAction($action){
		$action=(int)$action;
		switch($action){
		case self::FLUSH_ACTION_NONE:
		case self::FLUSH_ACTION_INSERT:
		case self::FLUSH_ACTION_UPDATE:
		case self::FLUSH_ACTION_REMOVE:
			$this->action=$action;
			break;
		default:
			throw new \InvalidArgumentException('Invalid flush action given');
		}
	}
	/**
	@return int
	*/
	public function getFlushAction(){
		return $this->action;
	}
	/**
	@var array of Data received from mapper.
	*/
	public function setData(array $data){
		$this->data=$data;
	}
	/**
	@return array
	*/
	public function getData(){
		return $this->data;
	}
}
?>
