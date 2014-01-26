<?php
namespace Test;

class Employee extends \AMH\EntityManager\Entity\AbstractEntity{
	/**
	@var string
	*/
	private $name='';
	/**
	@var int
	*/
	private $salary=0;
	/**
	@var Task
	*/
	private $tasks=array();
	/**
	@var Employee
	*/
	private $mentor=NULL;
	/**
	@var Employee
	*/
	private $student=NULL;
	/**
	@param string $name Name of this employee.
	@param int|null $salary Salary, >=0.
	@param Task|null $task Current task.
	*/
	public function __construct($name=NULL,$salary=NULL,Employee $mentor=NULL){
		if($name)
			$this->setName($name);
		if($salary!==NULL){
			$this->setSalary($salary);
		}
		if($mentor){
			$this->setMentor($mentor);
		}
	}
	/**
	@param string
	*/
	public function setName($name){
		$this->load();
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
	/**
	@param int
	*/
	public function setSalary($sal){
		$this->load();
		if($sal>=0){
			$this->salary=(int)$sal;
			$this->dirty();
		}
		else{
			throw new \InvalidArgumentException('Invalid salary given, int greater or equal to zero expected');
		}
	}
	/**
	@return int
	*/
	public function getSalary(){
		$this->load();
		return $this->salary;
	}
	
	public function addTask(Task $t){
		$this->load();
		/*if(in_array($t,$this->tasks)){
			return;
		}*/
		$this->tasks[]=$t;
		if(!in_array($this,$t->assigned())){
			$t->assign($this);
		}
		$this->dirty();
	}
	/**
	@return array of Task.
	*/
	public function tasks(){
		$this->load();
		return $this->tasks;
	}
	/**
	@return bool If task was successfuly completed.
	*/
	public function removeTask(Task $t){
		$this->load();
		if(($ind=array_search($t,$this->tasks)) && $ind>=0){
			unset($this->tasks[$ind]);
			$this->tasks=array_values($this->tasks);
			$this->dirty();
			return TRUE;
		}
		return FALSE;
	}
	/**
	@throws \InvalidArgumentException If given mentor is this employee.
	*/
	public function setMentor(Employee $mentor){
		$this->load();
		if($this!==$mentor){
			$old_mentor=$this->mentor;
			if($old_mentor && $old_mentor->getStudent()===$this){
				$old_mentor->stopMentoring($this);
			}
			$this->mentor=$mentor;
			if($mentor->getStudent()!==$this){
				$mentor->setStudent($this);
			}
			$this->dirty();
		}
		else{
			throw new \InvalidArgumentException('Cannot mentor self');
		}
	}
	/**
	@return Employee
	*/
	public function getMentor(){
		$this->load();
		return $this->mentor;
	}
	/**
	Loses mentor.
	*/
	public function loseMentor(){
		$this->load();
		$mentor=$this->mentor;
		$this->mentor=NULL;
		if($mentor && $mentor->getStudent()===$this){
			$mentor->stopMentoring($this);
		}
		$this->dirty();
	}
	/**
	@throws \InvalidArgumentException If given student is this employee.
	*/
	public function setStudent(Employee $s){
		$this->load();
		if($this!==$s){
			$old_student=$this->student;
			$this->student=$s;
			if($old_student && $old_student->getMentor()===$this){
				$old_student->loseMentor();
			}
			$this->dirty();
		}
		else{
			throw new \InvalidArgumentException('Cannot mentor self');
		}
	}
	/**
	@return Employee
	*/
	public function getStudent(){
		$this->load();
		return $this->student;
	}
	/**
	Stops mentoring student.
	
	@param Employee Student.
	*/
	public function stopMentoring(Employee $s){
		$this->load();
		if($s===$this->student){
			$this->student=NULL;
			if($s->getMentor()===$this){
				$s->loseMentor();
			}
			$this->dirty();
		}
	}
}
?>
