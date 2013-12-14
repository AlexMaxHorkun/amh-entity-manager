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
	/**
	@var array Of Entities, their flush action and loaded attribute.
	*/
	protected $entities=array();
	/**
	@return array of Entity.
	*/
	public find(SelSttm $select){
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
	
	protected add(Entity $e){}
	
	protected update(Entity $e){}
	
	protected remove(Entity $e){}
}
?>
