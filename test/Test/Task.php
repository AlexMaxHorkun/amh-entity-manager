<?php
namespace Test;

class Task extends \AMH\EntityManager\Entity\AbstractEntity{
	/**
	@var string
	*/
	protected $name='';
	/**
	@var \DateTime
	*/
	protected $tobedone_time=NULL;
	/**
	@var array of Employee.
	*/
	protected $emps=array();
	/**
	@var bool
	*/
	protected $completed=FALSE;
	/**
	@param string $name
	@param \DateTime|null $dt Complete time.
	@param Employee|null $emp Assigned employee.
	*/
	public function __costruct($name=NULL, \DateTime $dt=NULL, array $emps=array()){
		if($name)
			$this->setName($name);
		if(!$dt){
			$dt=new \DateTime();
		}
		$this->setCompleteTime($dt);
		if($emps){
			$this->assignAll($emps);
		}
	}
	/**
	@param string
	*/
	public function setName($name){
		$name=(string)$name;
		if(mb_strlen($name)>0){
			$this->name=$name;
			$this->dirty();
		}
		else{
			throw new \InvalidArgumentException('Invalid name given, string with length greater then 0 expected');
		}
	}
	/**
	@return string
	*/
	public function getName(){
		$this->load();
		return $this->name;
	}
	
	public function setCompleteTime(\DateTime $d){
		$this->tobedone_time=$d;
		$this->dirty();
	}
	/**
	@return \DateTime
	*/
	public function getCompleteTime(){
		$this->load();
		return $this->tobedone_time;
	}
	/**
	Adds Employee to executors list.
	*/
	public function assign(Employee $e){
		$this->emps[]=$e;
		if(!in_array($this,$e->tasks())){
			$e->addTask($this);
		}
		$this->dirty();
	}
	/**
	Assigns employies given in array to this.
	
	@param array Of Employee.
	*/
	public function assignAll(array $es){
		foreach($es as $e){
			$this->assign($e);
		}
	}
	/**
	@return array Of Employee.
	*/
	public function assigned(){
		$this->load();
		return $this->emps;
	}
	/**
	@return bool If task successfuly completed.
	*/
	public function complete(){
		$this->completed=TRUE;
		foreach($this->emps as $e){
			$e->removeTask($this);
		}
		$this->dirty();
		return TRUE;
	}
	/**
	@return bool If was completed.
	*/
	public function isCompleted(){
		$this->load();
		return $this->completed;
	}
}
?>
