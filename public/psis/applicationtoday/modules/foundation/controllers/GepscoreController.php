<?php
class Foundation_GepscoreController extends Zend_Controller_Action {
	
	
    public function init()
    {    	
     /* Initialize action controller here */
    	header('content-type: text/html; charset=utf8');
    	defined('BASE_URL')	|| define('BASE_URL', Zend_Controller_Front::getInstance()->getBaseUrl());
	}
	public function indexAction(){
		try{
			$db = new Foundation_Model_DbTable_DbGepScore();
			$this->view->g_all_name=$db->getGroupSearch();
			if($this->getRequest()->isPost()){
				$search=$this->getRequest()->getPost();
				$this->view->g_name=$search;
			}
			else{
				$search = array(
						'group_name' => '',
						'study_year'=> '',
						'degree_english'=> '',
						'grade_english'=> '',
						'session'=> '',
						'time'=> '',
						'start_date'=> date('Y-m-d'),
						'end_date'=>date('Y-m-d'));
			}
			$rs_rows = $db->getAllScore($search);
			$glClass = new Application_Model_GlobalClass();
			$rs = $glClass->getImgActive($rs_rows, BASE_URL, true);
			$list = new Application_Form_Frmtable();
			$collumns = array( "STUDENT_GROUP","TITLE","STUDY_YEAR","DEGREE","GRADE","SESSION","ROOM","TIME","SCORE_TYPE","STATUS");
			$link=array(
					'module'=>'foundation','controller'=>'gepscore','action'=>'edit',
			);
			$this->view->list=$list->getCheckList(0, $collumns, $rs,array('title_score'=>$link,'student_no'=>$link,'student_id'=>$link,'academic_id'=>$link,'degree'=>$link,'group_id'=>$link));
		
		}catch (Exception $e){
			Application_Form_FrmMessage::message("Application Error");
			Application_Model_DbTable_DbUserLog::writeMessageError($e->getMessage());
			echo $e->getMessage();
		}
		$form=new Registrar_Form_FrmSearchInfor();
		$form->FrmSearchRegister();
		Application_Model_Decorator::removeAllDecorator($form);
		$this->view->form_search=$form;
	}
	public function fullResultAction(){
		
	}
	public	function addAction(){
		$db = new Foundation_Model_DbTable_DbGepScore();
		if($this->getRequest()->isPost()){
			$_data = $this->getRequest()->getPost();
			try {
				if(isset($_data['save_new'])){
					$rs =  $db->addStudentScore($_data);
					Application_Form_FrmMessage::Sucessfull("INSERT_SUCCESS","/foundation/gepscore/add");
				}else {
					$rs =  $db->addStudentScore($_data);
					Application_Form_FrmMessage::Sucessfull("INSERT_SUCCESS","/foundation/gepscore");
				}
	
			}catch(Exception $e){
				Application_Form_FrmMessage::message("INSERT_FAIL");
				Application_Model_DbTable_DbUserLog::writeMessageError($e->getMessage());
			}
		}
		$result= $db->getAllgroupStudyNotPass();
		array_unshift($result, array ( 'id' => '', 'name' => 'ជ្រើសរើសក្រុម') );
		$this->view->group = $result;
		
		$db_global=new Application_Model_DbTable_DbGlobal();
		$this->view->row_year=$db_global->getAllYear();
		$this->view->session=$db_global->getSession();
		$this->view->degree=$db_global->getDegree();
		$this->view->room = $row =$db_global->getAllRoom();
			
	}
	public	function editAction(){
		$id=$this->getRequest()->getParam('id');
		$_model = new Foundation_Model_DbTable_DbGepScore();
		if($this->getRequest()->isPost()){
			$_data = $this->getRequest()->getPost();
			$_data['score_id']=$id;
			try {
				if(isset($_data['save_close'])){
					$rs =  $_model->updateStudentScore($_data);
					Application_Form_FrmMessage::Sucessfull("INSERT_SUCCESS","/foundation/gepscore");
				}
	
			}catch(Exception $e){
				Application_Form_FrmMessage::message("INSERT_FAIL");
				Application_Model_DbTable_DbUserLog::writeMessageError($e->getMessage());
			}
		}
		
		
		$schorebyid = $_model->getScoreById($id);
		$this->view->score_id = $id;
		$this->view->score = $schorebyid;
		$this->view->student= $_model->getStudentSccoreforEdit($id);
		$this->view->rows_scor=$_model->getScoreStudents($id);
		$data=$this->view->rows_detail=$_model->getSubjectById($id);
		$this->view->row_g=$_model->getGroupStudent($id);
		//for control group
		$result= $_model->getAllgroupStudyNotPass($schorebyid['group_id']);
		array_unshift($result, array ( 'id' => '', 'name' => 'ជ្រើសរើសក្រុម') );
		$this->view->group = $result;
		
		
		$db_homwork=new Global_Model_DbTable_DbHomeWorkScore();
		$this->view->row_year=$db_homwork->getAllYears();
		$db_global=new Application_Model_DbTable_DbGlobal();
		$this->view->session=$db_global->getSession();
		$this->view->degree=$db_global->getDegree();
		$this->view->rows_sub=$db_homwork->getSubjectId();
	
	
		$db_homwork=new Global_Model_DbTable_DbHomeWorkScore();
		$this->view->rows_sub=$db_homwork->getSubjectId();
		$this->view->rows_parent=$db_homwork->getParent();
		
		$db_global=new Application_Model_DbTable_DbGlobal();
		$this->view->room = $row =$db_global->getAllRoom();		
		
	}
	function getGradeAction(){
		if($this->getRequest()->isPost()){
			$data=$this->getRequest()->getPost();
			$db = new Foundation_Model_DbTable_DbGepScore();
			$grade = $db->getAllGrade($data['degree']);
			//print_r($grade);exit();
			//array_unshift($makes, array ( 'id' => -1, 'name' => 'បន្ថែមថ្មី') );
			print_r(Zend_Json::encode($grade));
			exit();
		}
	}
	function getStudentAction(){
		if($this->getRequest()->isPost()){
			$data = $this->getRequest()->getPost();
			$db = new Foundation_Model_DbTable_DbGepScore();
			$data=$db->getStudentByGroup($data['group']);
			print_r(Zend_Json::encode($data));
			exit();
		}
	}
	function getSubjectbygroupAction(){
		if($this->getRequest()->isPost()){
			$data = $this->getRequest()->getPost();
			$db = new Foundation_Model_DbTable_DbGepScore();
			$data=$db->getSubjectByGroup($data['group'],$data['score_type']);
			print_r(Zend_Json::encode($data));
			exit();
		}
	}
	function getChildsubjectAction(){
		if($this->getRequest()->isPost()){
			$data = $this->getRequest()->getPost();
			$db = new Foundation_Model_DbTable_DbGepScore();
			$data=$db->getChildSubject($data['subject_id']);
			print_r(Zend_Json::encode($data));
			exit();
		}
	}
}