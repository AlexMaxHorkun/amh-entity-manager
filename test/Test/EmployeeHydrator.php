<?php
namespace Test;

class EmployeeHydrator extends \AMH\EntityManager\Entity\Hydrator\AbstractHydrator{
	public function newEntity(){
		return new Employee();
	}
	
	public function hydrate(\AMH\EntityManager\Entity\AbstractEntity $e, array $data){
		if(isset($data['name'])) $e->setName($data['name']);
		if(isset($data['salary'])) $e->setSalary($data['salary']);
		/*if(isset($data['tasks']) && $data['tasks']){
			foreach($data['tasks'] as $t){
				$e->addTask($this->relative('Task',$t));
			}
		}*/
		if(isset($data['mentor']) && $data['mentor']){
			$e->setMentor($this->relative('Employee',$data['mentor']));
		}
		if(isset($data['student']) && $data['student']){
			$e->setStudent($this->relative('Employee',$data['student']));
		}
		if(isset($data['id'])) $e->setId($data['id']);
	}
	
	public function extract(\AMH\EntityManager\Entity\AbstractEntity $e){
		return array(
			'name'=>$e->getName(),
			'salary'=>$e->getSalary(),
			'tasks'=>$e->tasks(),
			'mentor'=>($e->getMentor())? $e->getMentor()->id():NULL,
			'student'=>($e->getStudent())? $e->getStudent()->id():NULL,
		);
	}
}
?>
