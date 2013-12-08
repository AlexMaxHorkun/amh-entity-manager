<?php
namespace AMH\EntityManager\Repository\Mapper;

use \AMH\EntityManager\Entity\AbstractEntity as Entity;
use AMH\EntityManager\Repository\Repository;
use AMH\EntityManager\Entity\Hydrator\AbstractHydrator as Hydrator;

/**
@author Alex Horkun

Interface to communicate with db, only CRUD methods.
*/
abstract class MapperInterface{
	/**
	@var Repository
	*/
	private $repo=NULL;
	/**
	@var Hydrator
	*/
	private $hydrator=NULL;
	
	public function __counstruct(Repository $repo,Hydrator $hydr){
		$this->setRepository($repo);
		$this->setHydrator($hydr);
	}
	/**
	Select operation.
	
	If not_in_ids given should add to 'where' clause `id` not in (...).
	
	@param array $filter Criteria for records.
	@limit int|null Maxim amount of entities to return.
	@param array $not_in_ids of Entity IDs.
	
	@return array of Entity.
	*/
	public function find($filter=array(), $limit=0, $not_in_ids=array()){
		$data=$this->findEntitiesInitData($filter, $limit, $not_in_ids);
		if($data){
			return $this->createEntities($data);
		}
		else{
			return array();
		}
	}
	/**
	Creates entities from array of array data with hydrator.
	
	@throws \RuntimeException if no hydrator provided.
	@throws \RuntimeException if hydrator returns not an Entity.
	
	@param array Data.
	
	@return array of Entity.
	*/
	protected function createEntities(array $data){
		if(!$this->hydrator){
			throw \RuntimeException('No hydrator provided');
			return;
		}
		
		$es=array();
		foreach($data as $e_data){
			$e=$this->hydrator->createFrom($e_data);
			if($e instanceof Entity){
				$es[]=$e;
			}
			else{
				throw new \RuntimeException('Hydrator did not return an Entity from createFrom method');
				return;
			}
		}
		
		return $es;
	}
	/**
	Finds entities init data (id, relative entities ids).
	
	@param array $filter Criteria for records.
	@limit int|null Maxim amount of entities to return.
	@param array $not_in_ids of Entity IDs.
	
	@return array of (int)IDS and relative entities IDs.
	*/
	abstract protected function findEntitiesInitData($filter=array(), $limit=0, $not_in_ids=array());
	/**
	@param array $ids Of IDs.
	
	@return array of data for hydrator.
	*/
	abstract public function load(array $ids);
	/**
	@return void
	*/
	abstract public function add(Entity $e);
	/**
	@return void
	*/
	abstract public function update(Entity $e);
	/**
	@return void
	*/
	abstract public function remove(Entity $e);
	/**
	@return void
	*/
	public function setRepository(Repository $r){
		if($this->repo){
			$this->repo->removeMapper();
		}
		$this->repo=$r;
	}
	/**
	@return Repository
	*/
	public function getRepository(){
		return $this->repo;
	}
	
	public function setHydrator(Hydrator $hydr){
		$this->hydrator=$hydr;
	}
	/**
	@return Hydrator
	*/
	public function getHydrator(){
		return $this->hydrator;
	}
}
?>
