<?php
namespace AMH\EntityManager\Entity\Hydrator;

use AMH\EntityManager\Repository\Repository;
use AMH\EntityManager\Entity\AbstractEntity as Entity;

/**
@author Alex Horkun mindkilleralexs@gmail.com

Class to create entities from array, set prop from array, extract to array.
*/
abstract class AbstractHydrator{
	/**
	@var Repository
	*/
	private $repo=NULL;
	
	public function __construct(Repository $repo=NULL){
		if($repo){
			$this->setRepository($repo);
		}
	}
	
	public function setRepository(Repository $repo){
		$repo->setHydrator($this);
		$this->repo=$repo;
	}
	/**
	Gets relative entity.
	
	@param string Entity classname (repo name).
	@param int ID.
	
	@return Entity.
	
	@throws \RuntimeException
	*/
	protected function relative($name, $id){
		if($em=$this->repo->getEntityManager()){
			$repo=$em->getRepository($name);
			if(!$repo){
				throw new \RuntimeException('Repository '.$name.' doesn\'t exist');
			}
			return $repo->relative($id);
		}
		else{
			throw new \RuntimeException('Cannot find relative entity without link to EntityManager');
		}
	}
	/**
	Gets collection of relative entities.
	
	@param string Entity classname (repo name).
	@param array IDs.
	
	@return array of Entity.
	
	@throws \RuntimeException
	*/
	protected function relatives($name, array $ids){
		if(($em=$this->repo->getEntityManager()) && ($repo=$em->getRepository($name))){
			return $repo->relatives($ids);
		}
		else{
			throw new \RuntimeException('Cannot find relative entity without link to Entitymanager');
		}
	}
	/**
	Creates Entity from array containing id and relative entities' ids.
	
	@param array Entity data.
	
	@return Entity
	*/
	public function createFrom(array $data){
		$e=$this->create();
		$this->hydrate($e,$data);
		return $e;
	}
	/**
	Creates Entity.
	
	@return Entity
	*/
	abstract public function create();
	/**
	@param Entity
	@param array Data.
	
	@return void
	*/
	abstract public function hydrate(Entity $e, array $data);
	/**
	Extracts entity to array, relative entities must be return as ids not objects.
	
	@param Entity
	
	@return array
	*/
	abstract public function extract(Entity $e);
}
?>
