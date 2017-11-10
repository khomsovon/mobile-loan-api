<?php
class Registrar_RegisterController extends Zend_Controller_Action {
	protected $tr;
	const REDIRECT_URL ='/registrar';
    public function init()
    {    	
     /* Initialize action controller here */
    	header('content-type: text/html; charset=utf8');
    	$this->tr=Application_Form_FrmLanguages::getCurrentlanguage();
    	defined('BASE_URL')	|| define('BASE_URL', Zend_Controller_Front::getInstance()->getBaseUrl());
	}
    public function indexAction(){
    	try{
    		$db = new Registrar_Model_DbTable_DbRegister();
    		    		if($this->getRequest()->isPost()){
    		    			$search=$this->getRequest()->getPost();
    		    		}
    		    		else{
    		    			$search = array(
    		    					'adv_search' => '',
    		    					'study_year' => '',
    		    					'degree' => '',
    		    					'time'   =>'', 
    		    					'session'=>'',
    		    					'grade_all'=>'',
    		    					'branch_id'=>0,
    		    					'user'=>'',
    		    					'start_date'=> date('Y-m-d'),
    		    					'end_date'=>date('Y-m-d'));
    		    		}
    		$this->view->adv_search=$search;
    		$rs_rows= $db->getAllStudentRegister($search);
    		$glClass = new Application_Model_GlobalClass();
    		$rs_rows = $glClass->getGernder($rs_rows, BASE_URL );
    		//$rs_rows = $glClass->getGetPayTerm($rs_rows, BASE_URL );
    		$list = new Application_Form_Frmtable();
    		$collumns = array("RECEIPT_NO","STUDENT_ID","STUDENT_NAME","SEX","ACADEMIC_YEAR","DEGREE","CLASS","TOTAL_PAYMENT","FINE","CREDIT_MEMO","DEDUCT","NET_AMOUNT","DATE_PAY","USER","STATUS","PRINT_SCHOLARSHIP");
    		$link=array('module'=>'registrar','controller'=>'register','action'=>'edit',);
    		$letter=array('module'=>'registrar','controller'=>'register','action'=>'congratulationletter',);
    		
    		$this->view->list=$list->getCheckList(0, $collumns, $rs_rows,array('បោះ.អាហារូ'=>$letter,'branch_name'=>$link,'stu_code'=>$link,'receipt_number'=>$link,'name'=>$link));
    	}catch (Exception $e){
    		Application_Model_DbTable_DbUserLog::writeMessageError($e->getMessage());
    		echo $e->getMessage();
    	}
    	$data = new Registrar_Model_DbTable_DbRegister();
    	$db=$this->view->rows_degree=$data->getDegree();
    	
    	$form=new Registrar_Form_FrmSearchInfor();
    	$form->FrmSearchRegister();
    	Application_Model_Decorator::removeAllDecorator($form);
    	$this->view->form_search=$form;
    }
  
    public function addAction(){
      if($this->getRequest()->isPost()){
      	$_data = $this->getRequest()->getPost();
      	try {
      		$db = new Registrar_Model_DbTable_DbRegister();
      		$db->addRegister($_data);
      		if(isset($_data['save_new'])){
      			Application_Form_FrmMessage::message($this->tr->translate('INSERT_SUCCESS'));
      		}else{
      			Application_Form_FrmMessage::Sucessfull($this->tr->translate('INSERT_SUCCESS'), self::REDIRECT_URL . '/register/index');
      		}
      	} catch (Exception $e) {
      		Application_Form_FrmMessage::message($this->tr->translate('INSERT_FAIL'));
      		Application_Model_DbTable_DbUserLog::writeMessageError($e->getMessage());
      	}
      }
       $_db = new Application_Model_DbTable_DbGlobal();
       $this->view->all_dept = $_db->getAllDegreeName();
       $this->view->exchange_rate = $_db->getExchangeRate();
       $this->view->deduct = $_db->getDeduct();
       
       $db = new Registrar_Model_DbTable_DbRegister();
       $this->view->all_student_code = $db->getAllGerneralOldStudent();
       $this->view->all_student_name = $db->getAllGerneralOldStudentName();
       
       $this->view->all_student_test = $db->getAllStudentTested();
       
       $this->view->all_year = $db->getAllYears();
       $this->view->all_session = $db->getAllSession();
       $this->view->all_paymentterm = $db->getAllpaymentTerm();
       $this->view->all_service = $db->getAllService();
       $this->view->all_product = $db->getAllProductName();
       $this->view->all_room = $db->getAllRoom();
	   $test = $this->view->branch_info = $db->getBranchInfo();
	   $db = new Foundation_Model_DbTable_DbStudent();
	   $this->view->group = $db->getAllgroup();
    }
    
    public function editAction(){
    	$id=$this->getRequest()->getParam('id');
    	if($this->getRequest()->isPost()){
    		$_data = $this->getRequest()->getPost();
    		//$_data['pay_id']=$id;
//     		if(!empty($_data['void'])){
//     			echo $_data['void'];exit();
// 			}else{
// 				echo 'no void';exit();
// 			}
    		try {
    			$db = new Registrar_Model_DbTable_DbRegister();
    			$db->updateRegister($_data,$id);
    			if(isset($_data['save_new'])){
    				Application_Form_FrmMessage::Sucessfull($this->tr->translate('INSERT_SUCCESS'), self::REDIRECT_URL . '/register/index');
    			}else{
    				Application_Form_FrmMessage::Sucessfull($this->tr->translate('INSERT_SUCCESS'), self::REDIRECT_URL . '/register/index');
    			}
    		} catch (Exception $e) {
    			Application_Form_FrmMessage::message($this->tr->translate('INSERT_FAIL'));
    			echo $e->getMessage();exit();
    			
    		}
    	}
    	$db = new Registrar_Model_DbTable_DbRegister();
        $form_row=$db->getRegisterById($id);
        $is_start=$form_row['is_start'];
        if($is_start==0 || $form_row['is_void']>0){
        	Application_Form_FrmMessage::Sucessfull($this->tr->translate('Can not Edit'), self::REDIRECT_URL . '/register/index');
        }
        
    	$_db = new Application_Model_DbTable_DbGlobal();
    	$this->view->all_dept = $_db->getAllDegreeName();
    	
    	$db = new Registrar_Model_DbTable_DbRegister();
    	
    	$this->view->teacher = $db->getTeacherEdit($id);
    	
    	$this->view->payment = $db->getStudentPaymentByID($id);
    	
    	// for loop in initialize
    	$this->view->payment_detail_service = $db->getStudentPaymentDetailServiceByID($id);
    	// for information in  register
    	$this->view->payment_detail_register = $db->getStudentPaymentDetailRegisterByID($id);
    	
    	$this->view->service_only = $db->getServiceOnlyByID($id);
    	
    	$this->view->product_only = $db->getProductOnlyByID($id);
    	
    	$this->view->all_student_code = $db->getAllGerneralOldStudent();
    	$this->view->all_student_name = $db->getAllGerneralOldStudentName();
    	$this->view->all_year = $db->getAllYears();
    	$this->view->all_session = $db->getAllSession();
    	$this->view->all_paymentterm = $db->getAllpaymentTerm();
    	$this->view->all_service = $db->getAllService();
    	$this->view->all_room = $db->getAllRoom();

    	$test = $this->view->branch_info = $db->getBranchInfo();
    	
    	$db = new Foundation_Model_DbTable_DbStudent();
    	$this->view->group = $db->getAllgroup();
    	
    }
    function getGradeAction(){
    	if($this->getRequest()->isPost()){
    		$data=$this->getRequest()->getPost();
    		$db = new Registrar_Model_DbTable_DbRegister();
    		$grade = $db->getAllGrade($data['dept_id']);
    		print_r(Zend_Json::encode($grade));
    		exit();
    	}
    }
   function getStuNoAction(){
		if($this->getRequest()->isPost()){
			$data=$this->getRequest()->getPost();
			$db = new Registrar_Model_DbTable_DbRegister();
			$dept_id=$data['dept_id'];
			$stu_no = $db->getNewAccountNumber($dept_id);
			print_r(Zend_Json::encode($stu_no));
			exit();
		}
	}
    function getPaymentTermAction(){
    	if($this->getRequest()->isPost()){
    		$data=$this->getRequest()->getPost();
    		$db = new Registrar_Model_DbTable_DbRegister();
    		$payment = $db->getPaymentTerm($data['generat_id'],$data['pay_id'],$data['grade_id']);
    		print_r(Zend_Json::encode($payment));
    		exit();
    	}
    }
    function getBanlancePriceAction(){
    	if($this->getRequest()->isPost()){
    		$data=$this->getRequest()->getPost();
    		$db = new Registrar_Model_DbTable_DbRegister();
    		$payment = $db->getBalance($data['service_id'],$data['student_id'],$data['type']);
    		print_r(Zend_Json::encode($payment));
    		exit();
    	}
    }
    function getGeneralOldStudentAction(){
    	if($this->getRequest()->isPost()){
    		$data=$this->getRequest()->getPost();
    		$db = new Registrar_Model_DbTable_DbRegister();
    		$general = $db->getGeneralOldStudentById($data['student_id']);
    		print_r(Zend_Json::encode($general));
    		exit();
    	}
    }
    function getServiceTypeAction(){
    	if($this->getRequest()->isPost()){
    		$data = $this->getRequest()->getPost(); 
    		$db = new Registrar_Model_DbTable_DbRegister();
    		$service_type = $db->getServiceType($data['service_id']);
    		print_r(Zend_Json::encode($service_type));
    		exit();
    	}
    }
    function getServiceFeeAction(){
    	if($this->getRequest()->isPost()){
    		$data=$this->getRequest()->getPost();
    		$db = new Registrar_Model_DbTable_DbRegister();
    		$service_fee = $db->getServiceFee($data['studentid'],$data['service'],$data['term']);
    		print_r(Zend_Json::encode($service_fee));
    		exit();
    	}
    }
    
    function getProductFeeAction(){
    	if($this->getRequest()->isPost()){
    		$data=$this->getRequest()->getPost();
    		$db = new Registrar_Model_DbTable_DbRegister();
    		$product_fee = $db->getProductFee($data['service_id']);
    		print_r(Zend_Json::encode($product_fee));
    		exit();
    	}
    }
	
	function getTeacherAction(){
		if($this->getRequest()->isPost()){
    		$data=$this->getRequest()->getPost();
    		$db = new Registrar_Model_DbTable_DbRegister();
    		$teacher = $db->getAllTeacherByGrade($data['grade'],$data['session']);
    		array_unshift($teacher, array ( 'id' => -1, 'name' => 'select teacher') );
    		print_r(Zend_Json::encode($teacher));
    		exit();
    	}
	}
	
	function getReceiptNoAction(){
		if($this->getRequest()->isPost()){
			$data=$this->getRequest()->getPost();
			$db = new Registrar_Model_DbTable_DbRegister();
			$receipt = $db->getRecieptNo();
			print_r(Zend_Json::encode($receipt));
			exit();
		}
	}
	
	function getStudentTestInfoAction(){
		if($this->getRequest()->isPost()){
			$data=$this->getRequest()->getPost();
			$db = new Registrar_Model_DbTable_DbRegister();
			$stu_info = $db->getStudentTestInfo($data['stu_test_id']);
			print_r(Zend_Json::encode($stu_info));
			exit();
		}
	}
    
	function getCreditMemoAction(){
		if($this->getRequest()->isPost()){
			$data=$this->getRequest()->getPost();
			$db = new Registrar_Model_DbTable_DbRegister();
			$credit_memo = $db->getCreditMemoByStuId($data['stu_id']);
			print_r(Zend_Json::encode($credit_memo));
			exit();
		}
	}
	
	function getStartDateAction(){
		if($this->getRequest()->isPost()){
			$data = $this->getRequest()->getPost();
			$db = new Registrar_Model_DbTable_DbRegister();
			$validate = $db->getStartDate($data['service_id'],$data['stu_id']);
			print_r(Zend_Json::encode($validate));
			exit();
		}
	}
	function getStudentpaymenthistoryAction(){
		if($this->getRequest()->isPost()){
			$data=$this->getRequest()->getPost();
			$db = new Registrar_Model_DbTable_DbRegister();
			$payment = $db->getStudentPaymentHistory($data['student_id']);
			print_r(Zend_Json::encode($payment));
			exit();
		}
	}
	function congratulationletterAction(){
		$id=$this->getRequest()->getParam('id');
		if(empty($id)){$this->_redirect("registrar/register");}
		$db = new Registrar_Model_DbTable_DbRegister();
		$this->view->rs = $db->getStudentPaymentByID($id);
	}
}