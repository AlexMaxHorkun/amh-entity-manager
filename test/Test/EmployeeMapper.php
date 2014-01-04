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
		return $res;
	}
	
	protected function loadEntityData($id){
		$query='select * from employee where id='.$id;
		$res=$this->pdo->query($query)->fetchAll();
		$this->addQueryToStat($query);
		if(!$res) return NULL;
	}
	
	public function add(Entity $e){
		$query='insert into employee values(NULL,:name,:salary,:mentor,:student)';
		$stt=$this->pdo->prepare($query);
		$stt->bindValue('name',$e->getName());
		$stt->bindValue('salary',(int)$e->getSalary());
		$stt->bindValue('mentor',($mentor=$e->getMentor())? $mentor->id():NULL);
		$stt->bindValue('student',($student=$e->getStudent())? $student->id():NULL);
		$stt->execute();
		$this->addQueryToStat($query);
		return $this->pdo->lastInsertId();
	}
	public function update(Entity $e){
		$query='update employee set name=\''.$e->getName().'\', salary='.$e->getSalary().', cur_task=';
		if($e->tasks()){
			$query.=$e->tasks()[0]->id();
		}
		else{
			$query.='null';
		}
		$query.=' where id='.$e->id();
		$this->pdo->query($query);
		$this->addQueryToStat($query);
	}
	public function remove(Entity $e){
		$query='delete from employee where id='.$e->id();
		$this->pdo->query($query)->execute();
		$this->addQueryToStat($query);
	}
}
?>
