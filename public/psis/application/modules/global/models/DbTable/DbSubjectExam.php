<?php

class Global_Model_DbTable_DbSubjectExam extends Zend_Db_Table_Abstract
{

    protected $_name = 'rms_subject';
    public function getUserId(){
    	$session_user=new Zend_Session_Namespace('authstu');
    	return $session_user->user_id;
    }
	
    public function getAllSubjectParent($opt=null){
    	$db = $this->getAdapter();
    	if($opt!=null){
    		$sql = "SELECT id,subject_titleen As name FROM rms_subject WHERE `status`=1 AND subject_titleen!=''";
    		return $db->fetchAll($sql);
    	}else{
	    	$sql = "SELECT id,subject_titleen FROM rms_subject WHERE  parent=0 AND is_parent=1 AND `status`=1 AND subject_titleen!='' AND parent=0";
	    	return $db->fetchAll($sql);
    	}
    }
    
    public function getAllSubjectParentByID($id){
    	$db = $this->getAdapter();
    	$sql = "SELECT * FROM rms_subject WHERE id=".$id;
    	return $db->fetchRow($sql);
    }
    
	public function addNewSubjectExam($_data){
		$_arr=array(
				'parent' 			=> $_data['parent'],
				'subject_titlekh' 	=> $_data['subject_kh'],
				'subject_titleen' 	=> $_data['subject_en'],
				'date' 				=> date("Y-m-d"),
				'status'   			=> $_data['status'],
				'is_parent'   		=> $_data['par'],
				//'score_percent'   	=> $_data['score_percent'],
				//'access_type'   	=> $_data['access_type'],
		        'shortcut'			=> $_data['score_percent'],
				'user_id'	  		=> $this->getUserId()
		);
		return  $this->insert($_arr);
	}
	public function updateSubjectExam($_data,$id){
		$_arr=array(
				'parent' 			=> $_data['parent'],
				'subject_titlekh' 	=> $_data['subject_kh'],
				'subject_titleen' 	=> $_data['subject_en'],
				'date' 				=> date("Y-m-d"),
				'status'   			=> $_data['status'],
				//'score_percent'   	=> $_data['score_percent'],
				'is_parent'   		=> $_data['par'],
				//'access_type'   	=> $_data['access_type'],
				'shortcut'			=> $_data['score_percent'],
				'user_id'	  		=> $this->getUserId()
		);
		$where=$this->getAdapter()->quoteInto("id=?", $id);
		$this->update($_arr, $where);
   }
	public function getSubexamById($id){
		$db = $this->getAdapter();
		$sql = "SELECT * FROM rms_subject WHERE id = ".$db->quote($id);
		$sql.=" LIMIT 1";
		$row=$db->fetchRow($sql);
		return $row;
	}
	function getAllSujectName($search=null){
		$db = $this->getAdapter();
		$sql = " SELECT id,subject_titlekh,subject_titleen,shortcut,date,
		(SELECT CONCAT(last_name,' ',first_name) FROM rms_users WHERE id=user_id) as user_name
		,status
		FROM rms_subject   
		WHERE 1";
		$order=" order by id DESC";
		$where = '';
		if(empty($search)){
			return $db->fetchAll($sql.$order);
		}
		if(!empty($search['title'])){
			$s_where = array();
			$s_search = addslashes(trim($search['title']));
				$s_where[]= " subject_titlekh LIKE '%{$s_search}%'";
				$s_where[]= " subject_titleen LIKE '%{$s_search}%'";
			$where .= ' AND ( '.implode(' OR ',$s_where).')';
		}
		if($search['status']>-1){
			$where.= " AND status = ".$search['status'];
		}
		
		return $db->fetchAll($sql.$where.$order);
	}
	public function addNewSubjectajax($_data){
		$_arr=array(
				'parent' 			=> $_data['parent'],
				'subject_titlekh' 	=> $_data['subject_kh'],
				'date' 				=> date("Y-m-d"),
				'status'   			=> $_data['status'],
				'is_parent'   		=> $_data['par'],
				'score_percent'   	=> $_data['score_percent'],
				'user_id'	  		=> $this->getUserId(),
				'access_type'=>1,
		);
		$subject_id = $this->insert($_arr);
		$_model = new Global_Model_DbTable_DbGroup();
		$option = $_model->getAllSubjectStudy();
		$return = array('data'=>$subject_id,'option'=>$option);
		return   $return;//$_model->getAllSubjectStudy();//option
	}
	
	public function addSubjectajax($_data){
		$this->_name="rms_subject";
		$_arr=array(
				'parent' 			=> $_data['parent'],
				'subject_titlekh' 	=> $_data['subject_kh'],
				'subject_titleen' 	=> $_data['subject_en'],
				'date' 				=> date("Y-m-d"),
				'status'   			=> 1,
				'is_parent'   		=> $_data['par'],
				'shortcut'   		=> $_data['shortcut_sub'],
				'user_id'	  		=> $this->getUserId(),
				'access_type'=>1,
		);
		return $this->insert($_arr);
	}
	
}
