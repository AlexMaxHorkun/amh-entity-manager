<?php
namespace Test;

use AMH\EntityManager\Entity\AbstractEntity as Entity;

class taskMapper extends MapperQueryStat{
	protected $pdo=NULL;
	
	public function __construct(\PDO $p){
		$this->pdo=$p;
		$this->pdo->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION);
	}
	
	protected function findEntities(\AMH\EntityManager\Repository\Mapper\SelectStatement $s){
		$query='select * from task';
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
			if(isset($filter['complete'])){
				$where[]='complete='.(($filter['complete'])? 1:0);
			}
			if(isset($filter['employee'])){
				$where[]='count(select * from emp_task where employee='.((int)$filter['employee']).' and task=id limit 1)>0';
			}
			if(isset($filter['employees']) && is_array($filter['employees']) && $filter['employees']){
				$where[]='count(select * from emp_task where employee in ('.implode($filter['employees'],',').') and task=id limit 1)='.count($filter['employees']);
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
			$ids[]=$row['id'];
			$res[$key]['emps']=array();
		}
		if($ids){
			$query='select * from emp_task where task in ('.implode($ids,',').')';
			$emps=$this->pdo->query($query)->fetchAll(\PDO::FETCH_ASSOC);
			$this->addQueryToStat($query);
			foreach($res as $key=>$row){
				foreach($emps as $emp){
					if($emp['task']==$row['id']){
						$res[$key]['emps'][]=$emp['employee'];
					}
				}
			}
		}
		return $res;
	}
	
	public function add(Entity $e){
		$query='insert into task values(NULL,\''.$e->getName().'\','.(($e->isCompleted())? 1:0).',\''.$e->getCompleteTime()->format('Y-m-d H:i:s').'\')';
		$this->pdo->query($query);
		$this->addQueryToStat($query);
		$id=$this->pdo->lastInsertId();
		if($emps=$e->assigned()){
			$queries=array();
			foreach($emps as $key=>$emp){
				if($emps->id()){
					$query='insert into emp_task values('.$emp->id().','.$id.')';
					$this->pdo->query($query);
					$this->addQueryToStat($query);
				}
			}
		}
		return $id;
	}
	public function update(Entity $e){
		$query='update task set name=\''.$e->getName().'\', complete='.(($e->isCompleted())? 1:0).', complete_time=\''.$e->getCompleteTime()->format('Y-m-d H:i:s').'\'';
		$this->pdo->query($query);
		$this->addQueryToStat($query);
		$query='delete from emp_task where task='.$e->id();
		$this->pdo->query($query);
		$this->addQueryToStat($query);
		if($emps=$e->assigned()){
			$queries=array();
			foreach($emps as $key=>$emp){
				if($emp->id()){
					$query='insert into emp_task values('.$emp->id().','.$e->id().')';
					$this->pdo->query($query);
					$this->addQueryToStat($query);
				}
			}
		}
	}
	public function remove(Entity $e){
		$query='delete from task where id='.$e->id();
		$this->pdo->query($query)->execute();
		$this->addQueryToStat($query);
		$query='delete from emp_task where task='.$e->id();
		$this->pdo->query($query);
		$this->addQueryToStat($query);
	}
}
?>
