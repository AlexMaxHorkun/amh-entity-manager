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
	private $id=NULL;
	/**
	@var bool
	*/
	private $loaded=FALSE;
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
	Loads entity from DB.
	
	@throws \RuntimeException If ID's undefined.
	*/
	protected function load(){
		if(!$this->isLoaded()){
			if($this->id){
				$this->repo->loadOne($this);
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
		return $this->loaded;
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
			throw new \RuntimeException(__FUNCTION__.' method requires a repository to be set');
		}
	}
}
?>
