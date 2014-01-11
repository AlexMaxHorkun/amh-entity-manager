<?php
namespace Test;

class EmployeeHydrator extends \AMH\EntityManager\Entity\Hydrator\AbstractHydrator{
	public function newEntity(){
		return new Employee();
	}
	
	public function hydrate(\AMH\EntityManager\Entity\AbstractEntity $e, array $data){
		if(isset($data['name'])) $e->setName($data['name']);
		if(isset($data['salary'])) $e->setSalary($data['salary']);
		if(isset($data['tasks']) && $data['tasks']){
			$ids=array();
			foreach($data['tasks'] as $t){
				$t=$this->relative('Task',$t);
				$ids[]=$t->id();
				$e->addTask($t);
			}
			echo PHP_EOL.'Employee ID='.$e->id().' added Tasks IDs=['.implode($ids,',').']'.PHP_EOL;
		}
		if(isset($data['mentor']) && $data['mentor']){
			$e->setMentor($this->relative('Employee',$data['mentor']));
		}
		if(isset($data['student']) && $data['student']){
			$e->setStudent($this->relative('Employee',$data['student']));
		}
		if(isset($data['id'])) $e->setId($data['id']);
	}
	
	public function extract(\AMH\EntityManager\Entity\AbstractEntity $e){
		$tasks=$e->tasks();
		foreach($tasks as $key=>$t){
			$tasks[$key]=$t->id();
		}
		return array(
			'name'=>$e->getName(),
			'salary'=>$e->getSalary(),
			'tasks'=>$tasks,
			'mentor'=>($e->getMentor())? $e->getMentor()->id():NULL,
			'student'=>($e->getStudent())? $e->getStudent()->id():NULL,
		);
	}
}
?>
