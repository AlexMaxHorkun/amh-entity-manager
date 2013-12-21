<?php
namespace AMH\EntityManager\Entity;

use AMH\EntityManager\Repository\Repository;

/**
@author Alex Horkun mindkilleralexs@gmail.com

Entity carcas.
*/
abstract class AbstractEntity{
	/**
	@var int DB ID.
	*/
	protected $id=NULL;
	/**
	@var Repository
	*/
	private $repo=NULL;
	/**
	@return int Database ID.
	*/
	public function id(){
		return $this->id;
	}
	/**
	@param int
	*/
	public function setId($id){
		$this->id=(int)$id;
	}
	/**
	Loads entity from DB.
	
	@throws \RuntimeException If ID's undefined.
	*/
	protected function load(){
		if(!$this->isLoaded()){
			if($this->id){
				$this->repo->load($this);
				$this->loaded=TRUE;
			}
			else{
				throw new \RuntimeException(get_class($this).'::'.__FUNCTION__.' - Cannot load an entity without ID');
			}
		}
	}
	/**
	@return bool
	*/
	protected function isLoaded(){
		return $this->repo->isLoaded($this);
	}
	
	public function setRepository(Repository $r){
		if($this->repo){
			$this->repo->untrack($this);
		}
		$this->repo=$r;
	}
	/**
	@return Repository
	*/
	public function getRepository(){
		return $this->repo;
	}
	/**
	Marks this entity as dirty.
	*/
	protected function dirty(){
		if($this->repo){
			$this->repo->dirty($this);
		}
		else{
			throw new \RuntimeException(get_class($this).'::'.__FUNCTION__.' method requires a repository to be set');
		}
	}
}
?>
