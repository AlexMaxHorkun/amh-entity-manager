<?php
namespace AMH\EntityManager;

use AMH\EntityManager\Cache\CacheInterface;
use AMH\EntityManager\Repository\Repository;

/**
@author Alex Horkun mindkilleralexs@gmail.com

Main interfacce to work with entities.
*/
class EntityManager{
	/**
	@var array of repositories.
	*/
	private $repos=array();
	/**
	@var Cache\CacheInterface Default cache that will be given to all new repos if they doesn't have one.
	*/
	private $default_cache=NULL;
	/**
	@param array of Repository.
	@throws \InvalidArgumentException if array given as first argument contains something but repositories.
	*/
	public function __construct(array $repos=array(), Cache\CacheInterface $cache=NULL){
		if($repos){
			$this->setRepositories($repos);
		}
		
		if($cache){
			$this->setDefaultCache($cache);
		}
	}
	/**
	Sets default Cache that will be given to all new repos.
	*/
	public function setDefaultCache(CacheInterface $cache){
		$this->default_cache=$cache;
	}
	/**
	@return CacheInterface
	*/
	public function getDefaultCache(){
		return $this->default_cache;
	}
	/**
	Sets repositories.
	@throws \InvalidArgumentException if given array contains something but repositories.
	*/
	public function setRepositories(array $repos){
		foreach($repos as $rep){
			if($rep instanceof Repository){
				$this->addRepository($rep);
			}
			else{
				throw new \InvalidArgumentException(get_class($this).' constractor expects first argument to be an array of AMH\Repository\Repository');
				return;
			}
		}
		unset($rep,$repos);
	}
	/**
	Adds a Repository.
	@throws \RuntimeException If repo for such entities (name) already exsists.
	*/
	public function addRepository(Repository $repo){
		if(isset($this->repos[$repo->getName()])){
			throw new \RuntimeException('Repository with such name already exists.');
			return;
		}
		$this->repos[$repo->getName()]=$repo;
	}
	/**
	@param string Name of repo needed.
	@return Repository|null
	*/
	public function getRepository($name){
		if(isset($this->repos[$name])){
			return $this->repos[$name];
		}
		else{
			return NULL;
		}
	}
	/**
	Checks if repo exists.
	
	@param string|Repository Name or object of Repository.
	*/
	public function hasRepository($repo){
		if($repo instanceof Repository){
			return isset($this->repos[$repo->getName()]) && $this->repos[$repo->getName()]==$repo;
		}
		else{
			return isset($this->repos[(string)$repo]);
		}
	}
	/**
	Removes Repository by name or object link.
	
	@param string|Repository
	@return bool Was a repo removed?.
	*/
	public function removeRepository($repo){
		if($repo instanceof Repository && $this->hasRepository($repo)){
			unset($this->repos[$repo->getName()]);
			return TRUE;
		}
		elseif(isset($this->repos[(string)$repo])){
			unset($this->repos[(string)$repo]);
			return TRUE;
		}
		
		return FALSE;
	}
	/**
	Finds entity by classname(repo name) and ID.
	
	@param string Classname (repo name).
	@param int ID.
	
	@throws \RuntimeException If no such repository.
	@throws \InvalidArgumentException If invalid ID given.
	*/
	public function find($name,$id){
		if(!(int)$id){
			throw \InvalidArgumentException('Invalid ID given, must be an integer greater then zero');
			return NULL;
		}
		
		$repo=$this->getRepository((string)$name);
		if($repo){
			return $repo->find((int)$id);
		}
		else{
			throw new \RuntimeException('No such repository: '.((string)$name));
			return NULL;
		}
	}
	/**
	Saves changes done to entities.
	
	@return void
	*/
	public function flush(){
		foreach($this->repos as $r){
			$r->flush();
		}
	}	
}
?>
