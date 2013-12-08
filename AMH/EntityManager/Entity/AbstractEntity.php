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
	*/
	//TODO
	protected function load(){
		if(!$this->isLoaded()){
		
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
