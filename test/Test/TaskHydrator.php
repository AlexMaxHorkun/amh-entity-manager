<?php
namespace Test;

class EmployeeHydrator extends \AMH\EntityManager\Entity\Hydrator\AbstractHydrator{
	public function create(){
		return new Employee();
	}
	
	public function hydrate(\AMH\EntityManager\Entity\AbstractEntity $e, array $data){
		if(isset($data['name'])) $e->setName($data['name']);
		if(isset($data['salary'])) $e->setSalary($data['salary']);
		if(isset($data['task']) && $data['task']) $e->addTask($data['task']);
		if(isset($data['id'])) $e->setId($data['id']);
	}
	
	public function extract(\AMH\EntityManager\Entity\AbstractEntity $e){
		return array(
			'name'=>$e->getName(),
			'salary'=>$e->getSalary(),
			'task'=>($ts=$e->tasks())? $ts[0]:NULL
		);
	}
}
?>
