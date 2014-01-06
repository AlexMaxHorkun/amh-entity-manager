<?php
namespace Test;

use AMH\EntityManager\Entity\AbstractEntity as Entity;

class EmployeeMapper extends MapperQueryStat{
	protected $pdo=NULL;
	
	public function __construct(\PDO $p){
		$this->pdo=$p;
		$this->pdo->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION);
	}
	
	protected function findEntities(\AMH\EntityManager\Repository\Mapper\SelectStatement $s){
		$query='select * from employee as e';
		$where=array();
		if($s->getIds()){
			$where[]='e.id in ('.implode($s->getIds(),',').')';
		}
		if($s->getNotInIds()){
			$where[]='e.id not in ('.implode($s->getNotInIds(),',').')';
		}
		if($filter=$s->getFilter()){
			if(isset($filter['name'])){
				$where[]='e.name=\''.$filter['name'].'\'';
			}
			if(isset($filter['salary'])){
				$where[]='e.salary='.$filter['salary'];
			}
			if(isset($filter['task'])){
				$where[]='e.id in (select employee as id from employee_task where task='.(($filter['task'] instanceof Entity)? $filter['task']->id():$filter['task']).')';
			}
			if(isset($filter['mentor'])){
				$where[]='e.mentor='.(($filter['mentor'] instanceof Entity)? $filter['mentor']->id():$filter['mentor']);
			}
			if(isset($filter['student'])){
				$where[]='e.student='.(($filter['student'] instanceof Entity)? $filter['student']->id():$filter['student']);
			}
		}
		if($where){
			$query.=' where '.implode($where,' ');
		}
		if($s->getLimit()){
			$query.=' limit '.$s->getLimit();
		}
		$this->addQueryToStat($query);
		$res=$this->pdo->query($query)->fetchAll(\PDO::FETCH_ASSOC);
		$ids=array();
		foreach($res as $key=>$row){
			$res[$key]['tasks']=array();
			$ids[]=$row['id'];
		}
		if($ids){
			$query='select * from emp_task where employee in ('.implode($ids,',').')';
			$tasks=$this->pdo->query($query)->fetchAll(\PDO::FETCH_ASSOC);
			foreach($res as $key=>$row){
				foreach($tasks as $t){
					if($t['employee']==$row['id']){
						$res[$key]['tasks'][]=$t['task'];
					}
				}
			}
		}
		return $res;
	}
	
	public function add(Entity $e){
		$query='insert into employee values(NULL,\''.$e->getName().'\','.((int)$e->getSalary()).','.(($mentor=$e->getMentor())? $mentor->id():'NULL').','
			.(($student=$e->getStudent())? $student->id():'NULL').')';
		$stt=$this->pdo->prepare($query);
		$stt->execute();
		$this->addQueryToStat($query);
		$id=$this->pdo->lastInsertId();
		if($tasks=$e->tasks()){
			foreach($tasks as $t){
				if($t->id()){
					$query='insert into emp_task values('.$id.','.$t->id().')';
					$this->pdo->query($query);
					$this->addQueryToStat($query);
				}
			}
		}
		return $id;
	}
	public function update(Entity $e){
		$query='update employee set name=\''.$e->getName().'\', salary='.$e->getSalary().', mentor='.(($e->getMentor())? $e->getMentor()->id():'NULL')
			.', student='.(($e->getStudent())? $e->getStudent()->id():'NULL').' where id='.$e->id();
		$stt=$this->pdo->prepare($query);
		$stt->execute();
		$this->addQueryToStat($query);
		$query='delete from emp_task where employee='.$e->id();
		$this->pdo->query($query);
		$this->addQueryToStat($query);
		if($tasks=$e->tasks()){
			foreach($tasks as $t){
				if($t->id()){
					$query='insert into emp_task values('.$e->id().','.$t->id().')';
					$this->pdo->query($query);
					$this->addQueryToStat($query);
				}
			}
		}
	}
	public function remove(Entity $e){
		$query='delete from employee where id='.$e->id();
		$this->pdo->query($query);
		$this->addQueryToStat($query);
		$query='update employee set mentor=NULL where mentor='.$e->id();
		$this->pdo->query($query);
		$this->addQueryToStat($query);
		$query='update employee set student=NULL where student='.$e->id();
		$this->pdo->query($query);
		$this->addQueryToStat($query);
		$query='delete from emp_task where employee='.$e->id();
		$this->pdo->query($query);
		$this->addQueryToStat($query);
	}
}
?>
