<?php
namespace Test;

class TaskHydrator extends \AMH\EntityManager\Entity\Hydrator\AbstractHydrator{
	public function newEntity(){
		return new Task();
	}
	
	public function hydrate(\AMH\EntityManager\Entity\AbstractEntity $e, array $data){
		if(isset($data['name'])) $e->setName($data['name']);
		if(isset($data['complete_time'])) $e->setCompleteTime(new \DateTime($data['complete_time']));
		if(isset($data['complete']) && $data['complete']) $e->complete();
		if(isset($data['emps'])) $e->assignAll($this->relatives('Employee',$data['emps']));
	}
	
	public function extract(\AMH\EntityManager\Entity\AbstractEntity $e){
		$emps=$e->assigned();
		foreach($emps as $key=>$emp){
			$emps[$key]=$emp->id();
		}
		return array(
			'name'=>$e->getName(),
			'complete_time'=>$e->getCompleteTime()->format('Y-m-d H:i:s'),
			'complete'=>(bool)$e->isCompleted(),
			'emps'=>$emps
		);
	}
}
?>
