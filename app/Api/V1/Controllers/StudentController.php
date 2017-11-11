<?php
namespace App\Api\V1\Controllers;
use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function getGroup($stu_id){
        $q=DB::select("SELECT score_t.*,rsu.`stu_enname` FROM (SELECT s.`id`, s.`group_id`,g.`group_code`,title_score,s.for_month,s.note,
   		  (SELECT CONCAT(from_academic,'-',to_academic,'(',generation,')') 
	      FROM rms_tuitionfee AS f WHERE f.id=g.academic_year AND `status`=1 GROUP BY from_academic,to_academic,generation) AS academic_year
 	      ,(SELECT en_name FROM `rms_dept` WHERE (`rms_dept`.`dept_id`=`g`.`degree`) LIMIT 1) AS degree, 
 	      (SELECT major_enname FROM `rms_major` WHERE (`rms_major`.`major_id`=`g`.`grade`) LIMIT 1 )AS grade,
 	      `g`.`semester` AS `semester`,
 	      (SELECT `r`.`room_name`	FROM `rms_room` `r` WHERE (`r`.`room_id` = `g`.`room_id`) LIMIT 1) AS `room_name`, 
 	      (SELECT`rms_view`.`name_kh` FROM `rms_view` WHERE ((`rms_view`.`type` = 4) AND (`rms_view`.`key_code` = `g`.`session`)) LIMIT 1) AS `session`, 
 	      (SELECT month_kh FROM rms_month WHERE rms_month.id = s.for_month) AS for_month_ch, s.for_semester,
  	      s.reportdate FROM `rms_score` AS s, `rms_group` AS g WHERE  g.`id`=s.`group_id` AND s.status = 1 AND s.type_score=1 
          ORDER BY g.`id` DESC ,s.for_academic_year,s.for_semester,s.for_month
          ) AS score_t
          INNER JOIN rms_student AS rsu ON score_t.group_id=rsu.group_id WHERE rsu.`stu_id`=$stu_id GROUP BY group_id"
        );
        return response()->json($q);
    }
    public function getMasterScore($stu_id){
        $q = DB::select("SELECT s.`id`, s.`group_id`, g.`group_code`,title_score,s.for_month,s.for_semester,s.note,sd.`student_id`,`g`.`degree` AS degree_id,
        (SELECT CONCAT(from_academic,'-',to_academic,'(',generation,')') 
        FROM rms_tuitionfee AS f WHERE f.id=g.academic_year AND `status`=1 GROUP BY from_academic,to_academic,generation) AS academic_year
        ,(SELECT en_name FROM `rms_dept` WHERE (`rms_dept`.`dept_id`=`g`.`degree`) LIMIT 1) AS degree, 
        (SELECT major_enname FROM `rms_major` WHERE (`rms_major`.`major_id`=`g`.`grade`) LIMIT 1 )AS grade,
      `g`.`semester` AS `semester`, 
        (SELECT `r`.`room_name` FROM `rms_room` `r` WHERE (`r`.`room_id` = `g`.`room_id`) LIMIT 1) AS `room_name`, 
        (SELECT`rms_view`.`name_kh` FROM `rms_view` WHERE ((`rms_view`.`type` = 4) AND (`rms_view`.`key_code` = `g`.`session`)) LIMIT 1) AS `session`, (SELECT month_kh FROM rms_month WHERE rms_month.id = s.for_month) AS for_month, s.for_semester,
       s.reportdate 
       FROM `rms_score` AS s, 
       `rms_score_detail` AS sd,
       `rms_group` AS g 
        WHERE  
        s.id= sd.`score_id`
        AND sd.`student_id`=$stu_id
        AND g.`id`=s.`group_id` AND s.status = 1 AND s.type_score=1 GROUP BY s.id");
        return response()->json($q);
    }
    public function getScoreDetail($score_id,$degree_id,$student_id){
        $q = DB::select("SELECT 
        sd.score_id,
        sd.subject_id,
        (SELECT subject_titlekh FROM `rms_subject` AS sj WHERE sj.id=sd.subject_id LIMIT 1) AS subject_name,
        sd.score 
        FROM `rms_score` AS s,
        `rms_score_detail` AS sd
        WHERE 
        s.id=sd.score_id
        AND sd.status=1
        AND sd.is_parent=1
        AND sd.student_id=4
        AND score_id=$score_id");
        $average=DB::select("SELECT pass_average FROM `rms_dept` WHERE dept_id=$degree_id LIMIT 1");
        DB::statement(DB::raw('SET @rnk=0'));
        DB::statement(DB::raw('SET @rank=0'));
        DB::statement(DB::raw('SET @curscore=0'));
        $rank=DB::select("
              SELECT score,student_id,(score/average) AS average,rank 
              FROM 
              (SELECT score,student_id,(
                  SELECT COUNT(1) AS sum_score 
                  FROM rms_score_detail 
                  WHERE student_id=$student_id 
                  AND score_id=$score_id) average,rank 
               FROM
                (
                    SELECT AA.*,BB.student_id,
                    (@rnk:=@rnk+1) rnk,
                    (@rank:=IF(@curscore=score,@rank,@rnk)) rank,
                    (@curscore:=score) newscore
                    FROM
                    (
                        SELECT * FROM
                        (SELECT COUNT(1) scorecount,score
                         FROM (
                            SELECT SUM(score) AS score,
                            score_id,
                            group_id,
                            student_id 
                            FROM `rms_score_detail` 
                            WHERE score_id=$score_id 
                            GROUP BY student_id) AS ST 
                          WHERE score_id=$score_id  
                          GROUP BY score
                    ) AAA
                     ORDER BY score DESC
                 ) AA LEFT JOIN (SELECT SUM(score) AS score,
                                  score_id,
                                  group_id,
                                  student_id 
                                  FROM `rms_score_detail` 
                                  WHERE score_id=$score_id 
                                  GROUP BY student_id ORDER BY score DESC) BB 
                                USING (score) 
                                WHERE score_id=$score_id
            ) A WHERE student_id=$student_id
            ) AS A1
        ");
        return response()->json(['score'=>$q,'average'=>$average,'rank'=>$rank]);
    }
    public function getExam($stu_id,$group_id){
        $q=DB::select("SELECT score_t.*,rsu.`stu_enname` FROM (SELECT s.`id`, s.`group_id`,g.`group_code`,title_score,s.for_month,s.note,
   		  (SELECT CONCAT(from_academic,'-',to_academic,'(',generation,')') 
	      FROM rms_tuitionfee AS f WHERE f.id=g.academic_year AND `status`=1 GROUP BY from_academic,to_academic,generation) AS academic_year
 	      ,(SELECT en_name FROM `rms_dept` WHERE (`rms_dept`.`dept_id`=`g`.`degree`) LIMIT 1) AS degree, 
 	      (SELECT major_enname FROM `rms_major` WHERE (`rms_major`.`major_id`=`g`.`grade`) LIMIT 1 )AS grade,
 	      `g`.`semester` AS `semester`, 
 	      (SELECT `r`.`room_name`	FROM `rms_room` `r` WHERE (`r`.`room_id` = `g`.`room_id`) LIMIT 1) AS `room_name`, 
 	      (SELECT`rms_view`.`name_kh` FROM `rms_view` WHERE ((`rms_view`.`type` = 4) AND (`rms_view`.`key_code` = `g`.`session`)) LIMIT 1) AS `session`, 
 	      (SELECT month_kh FROM rms_month WHERE rms_month.id = s.for_month) AS for_month_ch, s.for_semester,
  	      s.reportdate FROM `rms_score` AS s, `rms_group` AS g WHERE  g.`id`=s.`group_id` AND s.status = 1 AND s.type_score=1 
          ORDER BY g.`id` DESC ,s.for_academic_year,s.for_semester,s.for_month
          ) AS score_t
          INNER JOIN rms_student AS rsu ON score_t.group_id=rsu.group_id WHERE rsu.`stu_id`=$stu_id AND score_t.group_id=$group_id"
        );
        return response()->json($q);
    }
    public function postToken(Request $req){
        $token = $req->token;
        $stu_id = $req->stu_id;
        $uuid = $req->uuid;
        $q=DB::table('rms_mobile_token')->where('uuid',$uuid)->get();
        if(count($q) === 0 && $stu_id !=0){
            DB::table('rms_mobile_token')->insert(['token'=>$token,'uuid'=>$uuid,'stu_id'=>$stu_id,'date'=>date('Y-m-d')]);
        }
        return response()->json(['status'=>'ok']);
    }
    public function getScore($stu_id,$group_id,$exam_id){
        $q = DB::select("SELECT
			 	s.`id`,
			 	sd.`group_id`,
			 	sd.`student_id`,
			 	sj.`subject_titlekh`,
			 	sj.`subject_titleen`,
			 	sj.shortcut,
			 	sd.`score`,
			 	sd.`subject_id`
			FROM `rms_score` AS s, 
			    `rms_score_detail` AS sd,
			    `rms_subject` AS sj
		   WHERE 
		   		s.`id`=sd.`score_id` 
		 		AND sj.`id`=sd.`subject_id` 
		 		AND sd.`is_parent`=1
		 		AND sd.`group_id`=$group_id
		 		AND s.`id`=$exam_id
		 		AND sd.`student_id`=$stu_id
		   GROUP BY 
		   		sd.`subject_id`");
        return response()->json($q);
    }
    public function getConfig($key){
        $q = DB::table('rms_appdata')->where('key','=',$key)->first();
        if(count($q)>0){
            return response()->json($q);
        }else{
            return '';
        }
    }
    public function getArticle($limit,$offset,$cate_id){
        $q = DB::table('rms_article')->where('category_id','=',$cate_id)->skip($offset)->limit($limit)->orderBy('id','desc')->get();
        return response()->json($q);
    }
    public function getSingleArticle($id,$cate_id){
        $q = DB::table('rms_article')->where('id','=',$id)->where('category_id','=',$cate_id)->first();
        return response()->json($q);
    }
    public function getLocation(){
        $q = DB::table('rms_location')->get();
        return response()->json($q);
    }
    public function getPaymentInvoice($student_id){
        $q=DB::select("SELECT
           sp.`id`,
          sp.receipt_number,
          s.stu_code,
          s.stu_khname,
          s.stu_enname,
          (SELECT en_name FROM rms_dept WHERE dept_id = s.degree) AS degree,
          (SELECT major_enname FROM rms_major WHERE major_id = s.grade) AS grade,
          (SELECT name_en FROM rms_view WHERE rms_view.type = 4 AND key_code=s.session) AS SESSION,
          sp.create_date,
          sp.is_void,
          (SELECT CONCAT(from_academic,'-',to_academic,'(',generation,')') FROM rms_tuitionfee WHERE `status`=1 AND id=sp.year LIMIT 1) AS YEAR,
          (SELECT CONCAT(first_name) FROM rms_users WHERE rms_users.id = sp.user_id) AS user_id,
          (SELECT name_en FROM rms_view WHERE TYPE=10 AND key_code=sp.is_void) AS void_status,
          
          sp.grand_total AS total_payment,
          sp.fine,
          sp.credit_memo,
          sp.deduct,
          sp.net_amount,
          sp.note
          FROM
          rms_student AS s,
          rms_student_payment AS sp
          WHERE s.stu_id = sp.student_id AND s.stu_id=$student_id");
        return response()->json($q);
    }
    function getAttendanceCountNotification($stu_id){
        $sql= DB::select('SELECT
          sat.`group_id`,satd.`attendence_status`,sat.`date_attendence`,satd.`description`
          FROM `rms_student_attendence` AS sat,
          `rms_student_attendence_detail` AS satd 
          WHERE sat.`id`= satd.`attendence_id`
          AND satd.`stu_id`='.$stu_id.' ORDER BY satd.id DESC');
        return response()->json($sql);
    }
    function getStatusAttendence($stu_id,$group){
        $sql= DB::select('SELECT
          sat.`group_id`,satd.`attendence_status`,sat.`date_attendence`,satd.`description`
          FROM `rms_student_attendence` AS sat,
          `rms_student_attendence_detail` AS satd 
          WHERE sat.`id`= satd.`attendence_id`
          AND satd.`stu_id`='.$stu_id.' AND sat.`group_id`='.$group.' ORDER BY satd.id DESC');
        return response()->json($sql);
    }
    public function getGroupbyStudent($student_id){
        $q = DB::select('SELECT
            `g`.`id`,
            (SELECT teacher_name_kh FROM `rms_teacher` WHERE `rms_teacher`.id=g.teacher_id) AS teacher_name,
            `g`.`group_code`    AS `group_code`,
            (SELECT CONCAT(from_academic," - ",to_academic,"(",generation,")") FROM rms_tuitionfee WHERE rms_tuitionfee.id=g.academic_year LIMIT 1) AS academic,
            `g`.`semester` AS `semester`,
            (SELECT en_name
            FROM `rms_dept`
            WHERE (`rms_dept`.`dept_id`=`g`.`degree`) LIMIT 1) AS degree,
            (SELECT major_enname
            FROM `rms_major`
            WHERE (`rms_major`.`major_id`=`g`.`grade`) LIMIT 1) AS grade,
            (SELECT `rms_view`.`name_en`
            FROM `rms_view`
            WHERE ((`rms_view`.`type` = 4)
            AND (`rms_view`.`key_code` = `g`.`session`))
            LIMIT 1) AS `session`,
            (SELECT
            `r`.`room_name`
            FROM `rms_room` `r`
            WHERE (`r`.`room_id` = `g`.`room_id`)LIMIT 1) AS `room_name`,
            g.amount_month,
            `g`.`start_date`,
            `g`.`expired_date`,
            `g`.`note`,
            (SELECT  
            `rms_view`.`name_en`
            FROM `rms_view`
            WHERE `rms_view`.`type` = 9
            AND `rms_view`.`key_code` = `g`.`is_pass`
            LIMIT 1) AS `status`,
            (SELECT COUNT(`stu_id`) FROM `rms_group_detail_student` WHERE `group_id`=`g`.`id` LIMIT 1)AS Num_Student
            FROM `rms_group` `g`,`rms_group_detail_student` AS gds
            WHERE g.id=gds.`group_id` AND gds.`stu_id`='.$student_id);
        return response()->json($q);
    }
    public function getPaymentDetail($student_id,$payment_id){
        $q = DB::select("Select 
         spd.id,
         spd.type,
         sp.scholarship_percent,
         sp.scholarship_amount,
          sp.tuition_fee,
          spd.fee,
          spd.qty,
          spd.subtotal,
          spd.late_fee,
          spd.extra_fee,
          spd.discount_percent,
          spd.discount_fix,
          spd.paidamount,
          spd.balance,
          spd.note,
          DATE_FORMAT(spd.start_date, '%d-%m-%Y') AS start_date ,
          DATE_FORMAT(spd.validate, '%d-%m-%Y') AS validate ,
          spd.is_start,
          spd.is_parent ,
          spd.is_complete,
          sp.receipt_number,
          DATE_FORMAT(sp.create_date, '%d-%m-%Y') AS create_date ,
          sp.is_void,
          s.stu_code,
          s.stu_khname,
          s.stu_enname,
          p.title AS service_name,
          (SELECT pg.name_kh FROM `rms_pro_category` AS pg WHERE pg.id = (SELECT pp.cat_id FROM `rms_product` AS pp WHERE pp.id = p.ser_cate_id LIMIT 1) LIMIT 1) AS product_category,
          (SELECT major_enname FROM `rms_major` WHERE major_id=sp.grade LIMIT 1) As major_name,
          (SELECT CONCAT(first_name) FROM rms_users WHERE rms_users.id = sp.user_id LIMIT 1) AS user,
          (SELECT name_kh FROM rms_view  WHERE rms_view.type=6 AND key_code=spd.payment_term LIMIT 1) AS payment_term,
          (select name_en from rms_view where type=10 and key_code=sp.is_void LIMIT 1) as void_status,
          (select title from rms_program_type where rms_program_type.id=p.ser_cate_id AND p.type=2 LIMIT 1) service_cate                             
           FROM 
            rms_student_payment as sp,
            rms_student_paymentdetail as spd,
            rms_student as s,
            rms_program_name as p
           where 
            s.stu_id = sp.student_id
            AND sp.id=spd.payment_id 
            AND p.service_id=spd.service_id and sp.student_id= $student_id AND sp.id=$payment_id ORDER BY create_date DESC,spd.type ASC");
        return response()->json($q);
    }
    public function getDiscipline($stu_id,$group_id){
        $q = DB::select("
            SELECT
            sd.`group_id`,
            sdd.`mistake_type`,
            sdd.description,
            sd.`mistake_date`
           FROM 
            `rms_student_discipline` AS sd,
            `rms_student_discipline_detail` AS sdd
           WHERE 
            sd.`id` = sdd.`discipline_id`
            AND sdd.`stu_id` = $stu_id
            AND sd.`group_id` = $group_id
        ");
        return response()->json($q);
    }
    public function getHoliday($type){
        $q=DB::table('rms_holiday')->where('type',$type)->get();
        return response()->json($q);
    }
    public function getCurrentMonth($year,$month,$type){
        $q=DB::table('rms_holiday')
            ->whereYear('date',$year)
            ->whereMonth('date',$month)
            ->where('type',$type)
            ->get();
        return response()->json($q);
    }
}