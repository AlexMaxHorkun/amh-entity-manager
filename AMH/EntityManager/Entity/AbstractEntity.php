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
	@var bool
	*/
	private $loaded=FALSE;
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
	
	@return bool TRUE on success.
	*/
	protected function load(){
		if($this->repo){
			if(!$this->isLoaded()){
				if($this->id){
					$this->loaded=TRUE;
					$this->loaded=$this->repo->load($this);
				}
				else{
					throw new \RuntimeException(get_class($this).'::'.__FUNCTION__.' - Cannot load an entity without ID');
				}
			}
		}
		return $this->isLoaded();
	}
	/**
	@return bool
	*/
	public function isLoaded(){
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
	}
	/**
	Checks if entity fits criteria.
	
	@return bool
	*/
	public function fitsCriteria(array $criteria){
		$props=get_object_vars($this);
		foreach($criteria as $prop=>$value){
			if($props[$prop]!=$value){
				return FALSE;
			}
		}
		
		return TRUE;
	}
}
?>
