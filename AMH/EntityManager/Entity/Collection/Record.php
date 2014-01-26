<?php
namespace AMH\EntityManager\Entity\Collection;

use AMH\EntityManager\Entity\AbstractEntity as Entity;

class Record{
	const STATUS_NONE=1;
	const STATUS_NEW=2;
	const STATUS_REMOVED=3;
	/**
	@var Entity;
	*/
	private $entity;
	/**
	@var int
	*/
	private $status=self::STATUS_NONE;
	
	public function __construct(Entity $e,$s=self::STATUS_NEW){
		$this->setEntity($e);
		$this->setStatus($s);
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
	/**
	@param int Status.
	
	@throws \InvalidArgumentException If status is invalid.
	*/
	public function setStatus($s){
		switch($s){
		case self::STATUS_NONE:
		case self::STATUS_NEW:
		case self::STATUS_REMOVED:
			$this->status=(int)$s;
			break;
		default:
			throw new \InvalidArgumentException('Invalid status given');
			break;
		}
	}
	/**
	@return int One of the consts.
	*/
	public function getStatus(){
		return $this->status;
	}
}
?>
