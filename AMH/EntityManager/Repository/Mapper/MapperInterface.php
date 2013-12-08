<?php
namespace AMH\EntityManager\Repository\Mapper;

use \AMH\EntityManager\Entity\EntityInterface;
use AMH\EntityManager\Repository\Repository;

/**
@author Alex Horkun

Interface to communicate with db, only CRUD methods.
*/
interface MapperInterface{
	/**
	Select operation.
	
	If not_in_ids given should add to 'where' clause `id` not in (...).
	
	@param array $filter Criteria for records.
	@limit int|null Maxim amount of entities to return.
	@param array $not_in_ids of Entity IDs.
	
	@return array of (int)IDS and relative entities IDs.
	*/
	public function find($filter=array(), $limit=0, $not_in_ids=array());
	/**
	@param array $ids Of IDs.
	
	@return array of data for hydrator.
	*/
	public function load(array $ids);
	/**
	@return void
	*/
	public function add(EntityInterface $e);
	/**
	@return void
	*/
	public function update(EntityInterface $e);
	/**
	@return void
	*/
	public function remove(EntityInterface $e);
	/**
	@return void
	*/
	public function setRepository(Repository $r);
	/**
	@return Repository
	*/
	public function getRepository();
}
?>
