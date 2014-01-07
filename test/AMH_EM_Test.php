<?php

include_once '../AMH/EntityManager/EntityManager.php';
include_once '../AMH/EntityManager/Repository/Repository.php';
include_once '../AMH/EntityManager/Entity/AbstractEntity.php';
include_once '../AMH/EntityManager/Entity/Hydrator/AbstractHydrator.php';
include_once '../AMH/EntityManager/Repository/EntityContainer.php';
include_once '../AMH/EntityManager/Repository/Mapper/SelectStatement.php';
include_once '../AMH/EntityManager/Repository/Mapper/AbstractMapper.php';
include_once '../AMH/EntityManager/Repository/IdentityMap.php';
include_once 'Test/Employee.php';
include_once 'Test/EmployeeHydrator.php';
include_once 'Test/MapperQueryStat.php';
include_once 'Test/EmployeeMapper.php';
include_once 'Test/Task.php';
include_once 'Test/TaskHydrator.php';
include_once 'Test/TaskMapper.php';
use AMH\EntityManager\Repository\Mapper\SelectStatement;

class AMH_EM_Test extends PHPUnit_Framework_TestCase{
	protected static $queries=array();
	
	protected static $pdo;
	
	protected static $em;
	
	protected static $dbname='amhemunittest';
	
	protected static $entities_count=10;
	
	public static function setUpBeforeClass(){
		echo PHP_EOL.'Preparing EntityManager'.PHP_EOL;
		self::$em=new \AMH\EntityManager\EntityManager();
		$user=include('user.config.php');
		self::$pdo=new \PDO('mysql:host=127.0.0.1',$user['name'],$user['password']);
		self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$employee_rep=new \AMH\EntityManager\Repository\Repository('Employee',new Test\EmployeeHydrator());
		$employee_mapper=new Test\EmployeeMapper(self::$pdo);
		$employee_rep->setMapper($employee_mapper);
		$employee_rep->setEntityManager(self::$em);
		self::$em->addRepository(new \AMH\EntityManager\Repository\Repository('Task',new Test\TaskHydrator(),new Test\TaskMapper(self::$pdo)));
		echo PHP_EOL.'Creating database '.self::$dbname.PHP_EOL;
		try{
			self::$pdo->query('create database '.self::$dbname);
		}
		catch(\Exception $e){
			echo PHP_EOL.'Already exists'.PHP_EOL;
		}
		self::$pdo->query('use '.self::$dbname);
		echo PHP_EOL.'Creating tables'.PHP_EOL;
		try{
			self::$pdo->query('create table employee(
				id int not null auto_increment primary key,
				name varchar(255) not null,
				salary int not null,
				mentor int,
				student int
			)engine=innodb default charset=utf8');
			self::$pdo->query('create table task(
				id int not null auto_increment primary key,
				name varchar(255) not null,
				complete tinyint not null default 0,
				complete_time datetime
			)engine=innodb default charset=utf8');
			self::$pdo->query('create table emp_task(
				employee int not null,
				task int not null
			)engine=innodb default charset=utf8');
		}
		catch(\Exception $e){
			echo PHP_EOL.'Already exists'.PHP_EOL;
		}
	}
	
	public function testInsert(){
		echo PHP_EOL.'testing Insertion'.PHP_EOL;
		$emps=array();
		$repo=self::$em->getRepository('Employee');
		for($i=0;$i<self::$entities_count;$i++){
			$emp=new Test\Employee('Employee '.($i+1), rand(1000,2000));
			$repo->persist($emp);
			$emps[]=$emp;
		}
		self::$em->flush();
		foreach($emps as $emp){
			$this->assertGreaterThan(0,$emp->id());
		}
		echo PHP_EOL.'Loading entities from db and comparing them to the ones stored in memory'.PHP_EOL;
		$repo->getIdentityMap()->clear();
		$emps_db=$repo->findAll();
		$this->assertEquals(count($emps),count($emps_db));
		for($i=0,$c=count($emps);$i<$c;$i++){
			$this->assertEquals($emps[$i]->getName(),$emps_db[$i]->getName());
			$this->assertEquals($emps[$i]->getSalary(),$emps_db[$i]->getSalary());
			$this->assertEquals(($m=$emps[$i]->getMentor())? $m->id():$m,($m=$emps_db[$i]->getMentor())? $m->id():$m);
			$this->assertEquals(($s=$emps[$i]->getStudent())? $s->id():$s,($s=$emps_db[$i]->getStudent())? $s->id():$s);
		}
	}
	/**
	@depends testInsert
	*/
	public function testSelfOneToOneRelations(){
		echo PHP_EOL.'testing Self one to one relations'.PHP_EOL;
		$select=new SelectStatement();
		$select->setLimit(self::$entities_count);
		$es=self::$em->getRepository('Employee')->findBy($select);
		for($i=1;$i<self::$entities_count;$i++){
			if((rand(0,100)%5)==0){
				$mentor=$es[rand(0,$i-1)];
				$es[$i]->setMentor($mentor);
				echo PHP_EOL.'Employee ID='.$es[$i]->id().' now is a student of Employee ID='.$mentor->id().PHP_EOL;
			}
		}
		self::$em->flush();
		echo PHP_EOL.'loading entities from db to check if changes saved correctly'.PHP_EOL;
		self::$em->getRepository('Employee')->getIdentityMap()->clear();
		$es_db=self::$em->getRepository('Employee')->findBy($select);
		$this->assertEquals(count($es),count($es_db));
		for($i=0,$c=count($es);$i<$c;$i++){
			$this->assertEquals(($m=$es[$i]->getMentor())? $m->id():$m,($m=$es_db[$i]->getMentor())? $m->id():$m);
			$this->assertEquals(($s=$es[$i]->getStudent())? $s->id():$s,($s=$es_db[$i]->getStudent())? $s->id():$s);
		}
	}
	/**
	@depends testSelfOneToOneRelations
	*/
	public function testRemove(){
		$repo=self::$em->getRepository('Employee');
		$es=$repo->findAll();
		echo PHP_EOL.'Testing Entity remove'.PHP_EOL;
		$e=$es[rand(0,count($es)-1)];
		echo PHP_EOL.'Removing Entity ID='.$e->id().PHP_EOL;
		$repo->remove($e);
		$repo->flush();
		echo PHP_EOL.'Getting Entity list to check if entity removed'.PHP_EOL;
		$es=$repo->findAll();
		$this->assertFalse(in_array($e,$es));
	}
	/**
	@depends testRemove
	*/
	public function testTaskInsert(){
		echo PHP_EOL.'Inserting entities for many to many relations test'.PHP_EOL;
		$repo=self::$em->getRepository('Task');
		$es=array();
		for($i=0,$c=self::$entities_count*3;$i<$c;$i++){
			$t=new Test\Task('Task '.($i+1),new \DateTime('+ '.($i+1).' days'));
			if(rand(0,3)==3){
				$t->complete();
			}
			$es[]=$t;
			$repo->persist($t);
		}
		$repo->flush();
		$repo->getIdentityMap()->clear();
		$es_db=$repo->findAll();
		$this->assertEquals(count($es),count($es_db));
		for($i=0,$c=count($es);$i<$c;$i++){
			$this->assertEquals($es[$i]->id(),$es_db[$i]->id());
			$this->assertEquals($es[$i]->getName(),$es_db[$i]->getName());
			$this->assertEquals($es[$i]->getCompleteTime(),$es_db[$i]->getCompleteTime());
			$this->assertEquals($es[$i]->isCompleted(),$es_db[$i]->isCompleted());
		}
	}
	/**
	@depends testTaskInsert
	*/
	public function testManyToMany(){
		echo PHP_EOL.'Testing many to many relations'.PHP_EOL;
		$emps=self::$em->getRepository('Employee')->findAll();
		$tasks=self::$em->getRepository('Task')->findAll();
		foreach($tasks as $t){
			for($i=0,$c=rand(1,self::$entities_count);$i<$c;$i++){
				$emp=$emps[rand(0,count($emps)-1)];
				echo PHP_EOL.'Assigning Employee ID='.$emp->id().' for Task ID='.$t->id().PHP_EOL;
				$t->assign($emp);
			}
		}
		self::$em->flush();
		echo PHP_EOL.'Loading entities from DB to check if they are saved correctly'.PHP_EOL;
		self::$em->getRepository('Employee')->getIdentityMap()->clear();
		self::$em->getRepository('Task')->getIdentityMap()->clear();
		$emps_db=self::$em->getRepository('Employee')->findAll();
		$this->assertEquals(count($emps),count($emps_db));
		for($i=0,$c=count($emps);$i<$c;$i++){
			$this->assertEquals($emps[$i]->id(),$emps_db[$i]->id());
			$ts=$emps[$i]->tasks();
			$ts_db=$emps_db[$i]->tasks();
			$this->assertEquals(count($ts),count($ts_db));
			for($j=0,$ct=count($ts);$j<$ct;$j++){
				$this->assertEquals($ts[$i]->id(),$ts_db[$i]->id());
				$this->assertEquals($ts[$i]->getName(),$ts_db[$i]->getName());
			}
		}
	}
	
	public function tearDown(){
		echo PHP_EOL.'Recreating Mappers'.PHP_EOL;
		$queries=self::$em->getRepository('Employee')->getMapper()->queriesStat();
		$queries=array_merge($queries,self::$em->getRepository('Task')->getMapper()->queriesStat());
		echo PHP_EOL.'Queries count executed on last test = '.count($queries).PHP_EOL;
		echo 'Queries:'.PHP_EOL;
		foreach($queries as $q){
			echo $q.PHP_EOL;
		}
		self::$queries=array_merge(self::$queries,$queries);
		self::$em->getRepository('Employee')->setMapper(new Test\EmployeeMapper(self::$pdo));
		self::$em->getRepository('Task')->setMapper(new Test\TaskMapper(self::$pdo));
	}
	
	public static function tearDownAfterClass(){
		echo PHP_EOL.'Total queries executed = '.count(self::$queries).PHP_EOL;
		echo PHP_EOL.'Droping database '.self::$dbname.PHP_EOL;
		self::$pdo->query('drop database '.self::$dbname);
	}
}
?>
