<?php
namespace AMH\EntityManager;

use Cache\CacheInterface;
use Repository\Repository;

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
	Sets repositories.
	@throws \InvalidArgumentException if given array contains something but repositories.
	*/
	public function setRepositories(array $repos){
		foreach($repos as $rep){
			if(!($rep instanceof Repository)){
				throw new \InvalidArgumentException(get_class($this).' constractor expects first argument to be an array of AMH\Repository\Repository');
				return;
			}
		}
		$this->repos=$repos;
		unset($rep,$repos);
	}
}
?>
