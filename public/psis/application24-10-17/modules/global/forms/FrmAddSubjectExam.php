<?php 
Class Global_Form_FrmAddSubjectExam extends Zend_Dojo_Form {
	protected $tr;
	protected $tvalidate ;//text validate
	protected $filter;
	protected $t_date;
	protected $t_num;
	protected $text;
	//protected $check;
	public function init()
	{
		$this->tr = Application_Form_FrmLanguages::getCurrentlanguage();
		$this->tvalidate = 'dijit.form.ValidationTextBox';
		$this->filter = 'dijit.form.FilteringSelect';
		$this->t_date = 'dijit.form.DateTextBox';
		$this->t_num = 'dijit.form.NumberTextBox';
		$this->text = 'dijit.form.TextBox';
		//$this->check='dijit.form.CheckBox';
	}
	public function FrmAddSubjectExam($data=null){
		
		$_subject_exam = new Zend_Dojo_Form_Element_TextBox('subject_kh');
		$_subject_exam->setAttribs(array(
				'dojoType'=>'dijit.form.ValidationTextBox',
    			'required'=>'true',
				'class'=>'fullside',
				));
		
		$_subject_kh = new Zend_Dojo_Form_Element_TextBox('subject_en');
		$_subject_kh->setAttribs(array('dojoType'=>$this->tvalidate,'required'=>'true','class'=>'fullside',));
		
		$_score_percent = new Zend_Dojo_Form_Element_TextBox('score_percent');
		$_score_percent->setAttribs(array(
				'dojoType'=>'dijit.form.ValidationTextBox',
				'class'=>'fullside',
				));
		
		$_status=  new Zend_Dojo_Form_Element_FilteringSelect('status');
		$_status->setAttribs(array('dojoType'=>$this->filter,'class'=>'fullside',));
		$_status_opt = array(
				1=>$this->tr->translate("ACTIVE"),
				0=>$this->tr->translate("DEACTIVE"));
		$_status->setMultiOptions($_status_opt);
		
		$_submit = new Zend_Dojo_Form_Element_SubmitButton('submit');
		$_submit->setLabel("save"); 
		if(!empty($data)){
			$_score_percent->setValue($data['shortcut']);
			$_subject_exam->setValue($data['subject_titlekh']);
			$_subject_kh->setValue($data['subject_titleen']);
			$_status->setValue($data['status']);
			//$_parent->setValue($data['parent']);
		}
		$this->addElements(array($_subject_exam,$_status,$_submit,$_subject_kh,$_score_percent));
		
		return $this;
		
	}
	
}