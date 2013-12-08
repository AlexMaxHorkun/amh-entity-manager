<?php
namespace AMH\EntityManager\Repository\Mapper;

use \AMH\EntityManager\Entity\EntityInterface;

/**
@author Alex Horkun

Interface to communicate with db, only CRUD methods.
*/
interface MapperInterface{
	/**
	Select operation.
	
	If ID given should filter only by ID, else ignore it,
	If not_in_ids given should add to 'where' clause `id` not in (...).
	
	@param int|null $id Database ID.
	@param array $filter Criteria for records.
	@limit int|null Maxim amount of entities to return.
	@param array $not_in_ids of Entity IDs.
	
	@return array of EntityInterface.
	*/
	public function find($id=0, $filter=array(), $limit=0, $not_in_ids=array());
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
}
?>
