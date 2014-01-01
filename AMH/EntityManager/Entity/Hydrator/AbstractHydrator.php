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
		if($this->repo && ($em=$this->repo->getEntityManager())){
			$repo=$em->getRepository($name);
			if(!$repo){
				throw new \RuntimeException('Repository '.$name.' doesn\'t exist');
			}
			return $repo->getEntity($id);
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
			return $repo->getEntities($ids);
		}
		else{
			throw new \RuntimeException('Cannot find relative entity without link to Entitymanager');
		}
	}
	/**
	Creates new instance of entity.
	
	@return Entity
	*/
	abstract public function newEntity();
	/**
	Creates Entity.
	
	@param int|null ID.
	
	@return Entity
	*/
	public function create($id){
		$e=$this->newEntity();
		if($id>0){
			$e->setId((int)$id);
		}
		return $e;
	}
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
	/**
	Extracts ID from array received from mapper or else to fetch an entity.
	
	@param array
	@return int
	
	@throw \InvalidArgumentException If data has no id in it.
	*/
	public function extractId(array $data){
		if(!isset($data['id'])){
			throw new \InvalidArgumentException('given array has no "id" key');
		}
		return (int)$data['id'];
	}
}
?>
