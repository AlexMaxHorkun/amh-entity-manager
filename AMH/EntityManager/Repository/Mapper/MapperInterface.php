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
	
	If ID given should filter only by ID, else ignore it.
	
	@param int|null $id Database ID.
	@param array $filter Criteria for records.
	@limit int|null Maxim amount of entities to return. 
	
	@return array of EntityInterface.
	*/
	public function find($id=0, $filter=array(), $limit=0);
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
