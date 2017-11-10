<?php

class Allreport_Model_DbTable_DbMistakeCertificate extends Zend_Db_Table_Abstract
{
	public function getStudentInfo($group_id,$stu_id){
		$db = $this->getAdapter();
		$sql="SELECT 
					s.stu_id,
					s.`stu_khname`,
					s.`stu_enname`,
					(select name_kh from rms_view where type=2 and key_code=s.sex) as sex,
					s.`dob`,
					s.`pob`,
					s.`home_num`,
					s.`street_num`,
					s.`village_name`,
					s.`commune_name`,
					s.`district_name`,
					s.`province_id`,
					(select province_kh_name from rms_province as p where p.province_id = s.province_id) as province_name,
					
					s.`father_enname`,
					s.`father_job`,
					(select occu_name from rms_occupation as oc where oc.occupation_id = s.father_job) as fa_job,
					s.`father_phone`,
					
					s.`mother_enname`,
					s.`mother_job`,
					(select occu_name from rms_occupation as oc where oc.occupation_id = s.mother_job) as mo_job,
					s.`mother_phone`,
					
					(SELECT CONCAT(from_academic,'-',to_academic) FROM rms_tuitionfee AS f WHERE f.id=s.academic_year AND `status`=1 GROUP BY from_academic,to_academic,generation) AS academic_year,
					(SELECT kh_name FROM `rms_dept` WHERE (`rms_dept`.`dept_id`=`s`.`degree`) LIMIT 1) AS degree,
					(SELECT major_enname FROM `rms_major` WHERE (`rms_major`.`major_id`=`s`.`grade`) LIMIT 1 )AS grade,
					(SELECT `r`.`room_name`	FROM `rms_room` `r`	WHERE (`r`.`room_id` = `s`.`room`) LIMIT 1) AS `room_name`,
					(SELECT`rms_view`.`name_kh`	FROM `rms_view`	WHERE ((`rms_view`.`type` = 4) AND (`rms_view`.`key_code` = `s`.`session`))LIMIT 1) AS `session`
				FROM
					`rms_student` AS s
				WHERE 
					s.`stu_id` = $stu_id
			";
		return $db->fetchRow($sql);
	}
	
	public function getMistakeRecord($search,$group_id,$stu_id){
		$db = $this->getAdapter();
		$sql="SELECT
					sdd.`stu_id`,
					s.`stu_khname`,
					s.`stu_enname`,
					s.sex,
					s.`dob`,
					s.`pob`,
					s.`home_num`,
					s.`street_num`,
					s.`village_name`,
					s.`commune_name`,
					s.`district_name`,
					s.`province_id`,
						
					s.`father_enname`,
					s.`father_job`,
					s.`father_phone`,
						
					s.`mother_enname`,
					s.`mother_job`,
					s.`mother_phone`,
						
					g.`academic_year`,
					g.`degree`,
					g.`grade`,
					g.`session`,
					sd.`mistake_date`,
					sdd.`mistake_type`,
					sdd.`description`
				FROM
					`rms_student_discipline` AS sd,
					`rms_student_discipline_detail` AS sdd,
					`rms_student` AS s,
					`rms_group` AS g
				WHERE
					sd.`id` = sdd.`discipline_id`
					AND sdd.`stu_id`=s.`stu_id`
					AND g.`id` = sd.`group_id`
					AND sdd.`stu_id` = $stu_id
					AND sd.`group_id` = $group_id
			";
		
		$where = " ";
		$from_date =(empty($search['start_date']))? '1': "sd.mistake_date >= '".$search['start_date']." 00:00:00'";
    	$to_date = (empty($search['end_date']))? '1': "sd.mistake_date <= '".$search['end_date']." 23:59:59'";
    	$where .= " AND ".$from_date." AND ".$to_date;
    	
		return $db->fetchAll($sql.$where);
	}
    
}