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
		$query='select * from employee';
		$where=array();
		if($s->getIds()){
			$where[]='id in ('.implode($s->getIds(),',').')';
		}
		if($s->getNotInIds()){
			$where[]='id not in ('.implode($s->getNotInIds(),',').')';
		}
		if($filter=$s->getFilter()){
			if(isset($filter['name'])){
				$where[]='name=\''.$filter['name'].'\'';
			}
			if(isset($filter['salary'])){
				$where[]='salary='.$filter['salary'];
			}
			if(isset($filter['task'])){
				$where[]='task='.(($filter['task'] instanceof Entity)? $filter['task']->id() : (int)$filter['task']);
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
		foreach($res as $key=>$row){
			$res[$key]['task']=$row['cur_task'];
			unset($res[$key]['cur_task']);
		}
		return $res;
	}
	
	protected function loadEntityData($id){
		$query='select * from employee where id='.$id;
		$res=$this->pdo->query($query)->fetchAll();
		$this->addQueryToStat($query);
		if(!$res) return NULL;
	}
	
	public function add(Entity $e){
		$query='insert into employee values(NULL,:name,:salary,:task)';
		$stt=$this->pdo->prepare($query);
		$stt->bindValue('name',$e->getName());
		$stt->bindValue('salary',(int)$e->getSalary());
		$stt->bindValue('task',($tasks=$e->tasks())? $tasks[0]:NULL);
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
