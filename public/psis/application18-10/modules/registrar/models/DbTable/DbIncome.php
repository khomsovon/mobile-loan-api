<?php
class registrar_Model_DbTable_DbIncome extends Zend_Db_Table_Abstract
{
	protected $_name = 'ln_income';
	
	public function getUserId(){
		$session_user=new Zend_Session_Namespace('authstu');
		return $session_user->user_id;
	}
	
	public function getBranchId(){
		$session_user=new Zend_Session_Namespace('authstu');
		return $session_user->branch_id;
	}
	
	function addIncome($data){
		
		$db = new Registrar_Model_DbTable_DbRegister();
	    $receipt_no = $db->getRecieptNo();
		
		$array = array(
					'branch_id'		=>$this->getBranchId(),
					'title'			=>$data['title'],
					'cate_income'	=>$data['cate_income'],
					'total_amount'	=>$data['total_income'],
					'invoice'		=>$receipt_no,
					'payment_method'=>$data['payment_method'],
					'cheqe_no'		=>$data['cheqe_no'],
					'description'	=>$data['note'],
					'date'			=>$data['date'],
					'status'		=>$data['status'],
					'user_id'		=>$this->getUserId(),
					'create_date'	=>date('Y-m-d'),
				);
		$this->insert($array);
 	 }
 	 
	 function updateIncome($data){
		$arr = array(
					'branch_id'		=>$this->getBranchId(),
					'title'			=>$data['title'],
					'cate_income'	=>$data['cate_income'],
					'total_amount'	=>$data['total_income'],
					'invoice'		=>$data['invoice'],
					'payment_method'=>$data['payment_method'],
					'cheqe_no'		=>$data['cheqe_no'],
					'description'	=>$data['note'],
					'date'			=>$data['date'],
					'status'		=>$data['status'],
					'user_id'		=>$this->getUserId(),
				);
		$where=" id = ".$data['id'];
		$this->update($arr, $where);
	}
	
	function getIncomeById($id){
		$db = $this->getAdapter();
		$sql=" SELECT * FROM ln_income where id=$id ";
		return $db->fetchRow($sql);
	}
	
	function getAllIncome($search=null){
		$db = $this->getAdapter();
		$session_user=new Zend_Session_Namespace('authstu');
		$from_date =(empty($search['start_date']))? '1': " create_date >= '".$search['start_date']." 00:00:00'";
		$to_date = (empty($search['end_date']))? '1': " create_date <= '".$search['end_date']." 23:59:59'";
		$where = " WHERE ".$from_date." AND ".$to_date;
		
		$sql=" SELECT id,
				(select cate.category_name from rms_cate_income_expense as cate where cate.id = cate_income) AS cate_name,
				title, invoice,
				(SELECT name_en FROM `rms_view` WHERE rms_view.type=8 and rms_view.key_code = payment_method) AS payment_method,
				total_amount,
				cheqe_no,
				description,
				date,
				status 
			FROM 
				ln_income ";
		
		if (!empty($search['adv_search'])){
				$s_where = array();
				$s_search = trim(addslashes($search['adv_search']));
				//$s_where[] = " account_id LIKE '%{$s_search}%'";
				$s_where[] = " title LIKE '%{$s_search}%'";
				$s_where[] = " total_amount LIKE '%{$s_search}%'";
				$s_where[] = " invoice LIKE '%{$s_search}%'";
				$where .=' AND ('.implode(' OR ',$s_where).')';
			}
			if($search['status']>-1){
				$where.= " AND status = ".$search['status'];
			}
	       $order=" order by id desc ";
			return $db->fetchAll($sql.$where.$order);
	}
	function getAllExpenseReport($search=null){
		$db = $this->getAdapter();
		$session_user=new Zend_Session_Namespace('authstu');
		$from_date =(empty($search['start_date']))? '1': " date >= '".$search['start_date']." 00:00:00'";
		$to_date = (empty($search['end_date']))? '1': " date <= '".$search['end_date']." 23:59:59'";
		$where = " WHERE ".$from_date." AND ".$to_date;
	
		$sql=" SELECT id,
		(SELECT branch_namekh FROM `rms_branch` WHERE rms_branch.br_id =branch_id LIMIT 1) AS branch_name,
		account_id,
		(SELECT symbol FROM `ln_currency` WHERE ln_currency.id =curr_type) AS currency_type,invoice,
		curr_type,
		total_amount,disc,date,status FROM $this->_name ";
	
		if (!empty($search['adv_search'])){
			$s_where = array();
			$s_search = trim(addslashes($search['adv_search']));
			$s_where[] = " account_id LIKE '%{$s_search}%'";
			$s_where[] = " title LIKE '%{$s_search}%'";
			$s_where[] = " total_amount LIKE '%{$s_search}%'";
			$s_where[] = " invoice LIKE '%{$s_search}%'";
			
			$where .=' AND ('.implode(' OR ',$s_where).')';
		}
		if($search['status']>-1){
			$where.= " AND status = ".$search['status'];
		}
		if($search['currency_type']>-1){
			$where.= " AND curr_type = ".$search['currency_type'];
		}
		$order=" order by id desc ";
		return $db->fetchAll($sql.$where.$order);
	}

	function getReceiptNumber($branch_id,$type){  // $type==1 => select from rms_income , $type==2 => select from rms_expense
		$db = $this->getAdapter();
		
		if($type==1){
			$table = 'ln_income';
		}else{
			$table = 'ln_expense';
		}
		$sql="select id from $table where status = 1 and branch_id = $branch_id order by id DESC";
		$id = $db->fetchOne($sql);
		$id = $id + 1;
		$length = strlen($id) + 1;
		$pre = '';
		for($i=$length;$i<=6;$i++){
			$pre.='0';
		}
		return $pre.$id;
	}
	function getInvoiceNo(){
		$db = $this->getAdapter();
		$sql = " select count(id) from ln_income ";
		$amount = $db->fetchOne($sql);
	}

	
	function getPaymentMethod($type){ // $type = rms_view type
		$db=$this->getAdapter();
		$sql="SELECT key_code as id,name_kh as name FROM rms_view WHERE `type`=$type AND `status`=1 ";
		return $db->fetchAll($sql);
	}
	
	function getCateIncome(){ // $type = rms_view type
		$db=$this->getAdapter();
		$sql="SELECT id,category_name as name FROM rms_cate_income_expense WHERE status=1 AND parent=1 and category_name!='' ";
		return $db->fetchAll($sql);
	}
	
	function addNewCateIncome($data){
		$this->_name="rms_cate_income_expense";
		$array = array(
				'category_name'	=>$data['cate_title'],
				'parent'		=>$data['type'],
				'create_date'	=>date('Y-m-d'),
				'user_id'		=>$this->getUserId(),
				);
		return $this->insert($array);
	}
	
	
	
	
	
	
	
	
	
}







