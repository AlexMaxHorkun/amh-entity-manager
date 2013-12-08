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
	
	@return array of (int)IDS and relative entities IDs.
	*/
	abstract public function find($filter=array(), $limit=0, $not_in_ids=array());
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
}
?>
