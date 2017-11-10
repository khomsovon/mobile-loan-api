<?php 
Class Registrar_Form_FrmSearchexpense extends Zend_Dojo_Form {
	protected $tr;
	public function init()
	{
		$this->tr = Application_Form_FrmLanguages::getCurrentlanguage();
	}
	public function AdvanceSearch($data=null){
		
		$db = new Application_Model_DbTable_DbGlobal();
		
		$request=Zend_Controller_Front::getInstance()->getRequest();
		$_status=  new Zend_Dojo_Form_Element_FilteringSelect('status');
		$_status->setAttribs(array('dojoType'=>'dijit.form.FilteringSelect','class'=>'fullside'));
		$_status_opt = array(
				-1=>$this->tr->translate("ជ្រើសរើសស្ថានការ"),
				1=>$this->tr->translate("ACTIVE"),
				0=>$this->tr->translate("DACTIVE"));
		$_status->setMultiOptions($_status_opt);
		$_status->setValue($request->getParam("status"));
		
		$_title = new Zend_Dojo_Form_Element_TextBox('adv_search');
		$_title->setAttribs(array('dojoType'=>'dijit.form.TextBox',
				'onkeyup'=>'this.submit()',
				'class'=>'fullside',
				'placeholder'=>$this->tr->translate("ADVANCE_SEARCH")
		));
		$_title->setValue($request->getParam("adv_search"));
		
		
// 		$_currency_type = new Zend_Dojo_Form_Element_FilteringSelect('payment_id');
// 		$_currency_type->setAttribs(array(
// 				'dojoType'=>'dijit.form.FilteringSelect',
// 				'class'=>'fullside'
// 		));
// 		$opt = array(-1=>"--Select Currency Type--",1=>"Dollar",2=>'Riel',3=>"Bath");
// 		$_currency_type->setMultiOptions($opt);
// 		$_currency_type->setValue($request->getParam("currency_type"));
		
		$payment_method = new Zend_Dojo_Form_Element_FilteringSelect('payment_type');
		$payment_method->setAttribs(array(
				'dojoType'=>'dijit.form.FilteringSelect',
				'class'=>'fullside',
		));
		$opt = $db->getViewById(8,1);
		$payment_method->setMultiOptions($opt);
		$payment_method->setValue($request->getParam("currency_type"));
		
		$_releasedate = new Zend_Dojo_Form_Element_DateTextBox('start_date');
		$_releasedate->setAttribs(array('dojoType'=>'dijit.form.DateTextBox',
				'onchange'=>'CalculateDate();',
				'constraints'=>"{datePattern:'dd/MM/yyyy'}",
				'class'=>'fullside'));
		$_date = $request->getParam("start_date");
		
		if(!empty($_date)){
			$_releasedate->setValue($_date);
		}
		
		
		
		$_dateline = new Zend_Dojo_Form_Element_DateTextBox('end_date');
		$_dateline->setAttribs(array('dojoType'=>'dijit.form.DateTextBox',
				'required'=>'true','class'=>'fullside',
				'constraints'=>"{datePattern:'dd/MM/yyyy'}",
		));
		$_date = $request->getParam("end_date");
		
		if(empty($_date)){
			$_date = date("Y-m-d");
		}
		$_dateline->setValue($_date);
		
		$_branch_id = new Zend_Dojo_Form_Element_FilteringSelect('branch_id');
		$_branch_id->setAttribs(array(
				'dojoType'=>'dijit.form.FilteringSelect',
				'class'=>'fullside'
		));
		
		$rows = $db->getAllBranch();
		$options=array(-1=>'---Select Branch---');
			if(!empty($rows))foreach($rows AS $row){
				$options[$row['br_id']]=$row['branch_namekh'];
			}
		$_branch_id->setMultiOptions($options);
		$_branch_id->setValue($request->getParam("branch_id"));
		
		
		$this->addElements(array($_title,$_branch_id,$payment_method,$_releasedate
				,$_dateline,$_status));
		return $this;
		
	}	
	
}