<?php
namespace AMH\EntityManager\Repository\Mapper;

use \AMH\EntityManager\Entity\AbstractEntity as Entity;
use AMH\EntityManager\Repository\Repository;
use AMH\EntityManager\Entity\Hydrator\AbstractHydrator as Hydrator;
use AMH\EntityManager\Repository\Mapper\SelectStatement as SelSttm;

/**
@author Alex Horkun

Interface to communicate with db, only CRUD methods.
*/
abstract class AbstractMapper{
	/**
	@var Repository
	*/
	private $repo=NULL;
	/**
	@var Hydrator
	*/
	private $hydrator=NULL;
	
	public function __construct(Repository $repo, Hydrator $hydr){
		$this->setRepository($repo);
		$this->setHydrator($hydr);
	}
	/**
	Select operation.
	
	If not_in_ids given should add to 'where' clause `id` not in (...).
	
	@param array $filter Criteria for records.
	@limit int|null Maxim amount of entities to return.
	@param array $not_in_ids of Entity IDs.
	
	@return array of Entity data:array.
	*/
	public function find(SelSttm $s){
		$data=$this->findEntities($s);
		return $this->fetchEntities($data);
	}
	/**
	Finds entities data.
	
	@param SelSttm
	
	@return array of Entities data.
	*/
	abstract protected function findEntities(SelSttm $s);
	/**
	Creates Entities from data received from findEntities.
	
	@param array
	
	@return array of Entity.
	*/
	protected function fetchEntities(array $data){
		$es=array();
		foreach($data as $e_data){
			$es[]=$this->repo->addToIdentityMap($e_data);
		}
		return $es;
	}
	/**
	Loads entity data.
	
	@return bool TRUE on success.
	
	@throws \InvalidArgumentException If entity has no ID.
	*/
	public function load(Entity $e){
		$select=new SelSttm();
		if(!$e->id()){
			throw new \InvalidArgumentException('Entity must have an ID');
		}
		$select->setIds(array($e->id()));
		$data=$this->findEntities($select);
		if($data){
			$data=array_values($data);
			$data=$data[0];
			$this->hydrator->hydrate($e,$data);
			return TRUE;
		}
		else{
			return FALSE;
		}
	}
	/**
	@return int ID.
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
