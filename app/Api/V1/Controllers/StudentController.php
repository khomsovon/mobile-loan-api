<?php
namespace App\Api\V1\Controllers;
use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function getGroup($stu_id,$group_id){
        $where = $group_id == 0 ? " " : " AND rsu.group_id=$group_id";
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
          INNER JOIN rms_student AS rsu ON score_t.group_id=rsu.group_id WHERE rsu.`stu_id`=$stu_id $where GROUP BY rsu.group_id"
        );
        return response()->json($q);
    }
    public function getMasterScore($stu_id,$group_id){
       $where = $group_id == 0 ? " " : " AND s.`group_id`=$group_id";
        $q = DB::select("SELECT s.`id`, s.`group_id`,s.exam_type, g.`group_code`,title_score,s.for_month,s.for_semester,s.note,sd.`student_id`,`g`.`degree` AS degree_id,
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
        $where
        AND g.`id`=s.`group_id` AND s.status = 1 AND s.type_score=1 GROUP BY s.id");
        return response()->json($q);
    }
    public function getScoreDetail($score_id,$degree_id,$student_id,$exam_type,$for_semester,$group_id){
        $q = DB::select("SELECT
        sd.score_id,
        sd.subject_id,
        (SELECT subject_titlekh FROM `rms_subject` AS sj WHERE sj.id=sd.subject_id LIMIT 1) AS subject_name,
        (SELECT d.max_average FROM rms_dept AS d,rms_group AS g WHERE d.dept_id=g.degree AND g.id=s.group_id) AS max_score,
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
        $semester=DB::select("SELECT
          s.`id`,
          sd.`group_id`,
          g.`group_code`,
          s.for_semester,
          (SELECT AVG(sdd.score) FROM rms_score_detail AS sdd,rms_score as sc
          WHERE
            sc.id=sdd.score_id
            AND sc.group_id=$group_id
            AND sc.for_semester =$for_semester
            AND sc.exam_type=$exam_type
            AND sdd.`is_parent`=1
            AND sdd.student_id = sd.student_id
          GROUP BY sdd.student_id LIMIT 1) AS avg_exam,
          (SELECT avg(average)
           FROM (SELECT
                   AVG(sdd.score) as average,sdd.student_id
                 FROM `rms_score` AS s,
                   `rms_score_detail` AS sdd
                 WHERE
                   s.id=sdd.score_id
                   AND sdd.status=1
                   AND sdd.is_parent=1
                   AND s.exam_type=1
                   AND s.for_semester=$for_semester
                   AND s.group_id=$group_id
                 GROUP BY sdd.student_id) AS A_MONTT WHERE A_MONTT.student_id=sd.student_id LIMIT 1) AS avg_month,
          (SELECT COUNT(ss.id) FROM `rms_score` AS ss WHERE ss.group_id=$group_id AND ss.exam_type=1 AND for_semester =$for_semester ) AS amount_month
        FROM `rms_score` AS s,
          `rms_score_detail` AS sd,
          `rms_student` AS st,
          `rms_group` AS g
        WHERE
          s.`id`=sd.`score_id`
          AND st.`stu_id`=$student_id
          AND g.`id`=s.`group_id`
          AND sd.`is_parent`=1
          AND s.status = 1
          AND s.type_score=1
          AND g.id= $group_id
          AND s.for_semester=$for_semester
          AND s.exam_type=$exam_type
        GROUP BY s.id");
        DB::statement(DB::raw('SET @rnk_=0'));
        DB::statement(DB::raw('SET @rank_=0'));
        DB::statement(DB::raw('SET @curscore_=0'));
        $sem_rank=DB::select("
            SELECT score,student_id,rank
            FROM
              (SELECT score,student_id,rank
               FROM
                 (
                   SELECT AA.*,BB.student_id,
                     (@rnk_:=@rnk_+1) rnk_,
                     (@rank:=IF(@curscore=score,@rank_,@rnk_)) rank,
                     (@curscore:=score) newscore
                   FROM
                     (
                       SELECT * FROM
                         (SELECT COUNT(1) scorecount,score
                          FROM (
                                 SELECT id AS score_id,(avg_exam + avg_month) AS score,student_id,group_id FROM (
                                    SELECT
                                      s.`id`,sd.student_id,s.group_id,
                                      (SELECT AVG(sdd.score)
                                       FROM rms_score_detail AS sdd, rms_score AS sc
                                       WHERE
                                         sc.id = sdd.score_id
                                         AND sc.group_id = $group_id
                                         AND sc.for_semester = $for_semester
                                         AND sc.exam_type = $exam_type
                                         AND sdd.`is_parent` = 1
                                         AND sdd.student_id = sd.student_id
                                       GROUP BY sdd.student_id
                                       LIMIT 1) AS avg_exam,
                                      (SELECT avg(average)
                                       FROM (SELECT
                                               AVG(sdd.score) AS average,
                                               sdd.student_id
                                             FROM `rms_score` AS s,
                                               `rms_score_detail` AS sdd
                                             WHERE
                                               s.id = sdd.score_id
                                               AND sdd.status = 1
                                               AND sdd.is_parent = 1
                                               AND s.exam_type = 1
                                               AND s.for_semester = $for_semester
                                               AND s.group_id = $group_id
                                             GROUP BY sdd.student_id) AS A_MONTT
                                       WHERE A_MONTT.student_id = sd.student_id
                                       LIMIT 1) AS avg_month
                                    FROM `rms_score` AS s,
                                      `rms_score_detail` AS sd,
                                      `rms_student` AS st,
                                      `rms_group` AS g
                                    WHERE
                                      s.`id` = sd.`score_id`
                                      AND g.`id` = s.`group_id`
                                      AND sd.`is_parent` = 1
                                      AND s.status = 1
                                      AND s.type_score = 1
                                      AND g.id = $group_id
                                      AND s.for_semester = $for_semester
                                      AND s.exam_type = $exam_type
                                    GROUP BY sd.student_id
                                  ) AS TTT GROUP BY student_id
                               ) AS ST
                          WHERE score_id=$score_id
                          GROUP BY score
                         ) AAA
                       ORDER BY score DESC
                     ) AA LEFT JOIN (
                     SELECT id,(avg_exam + avg_month) AS score,student_id,group_id FROM (
                        SELECT
                          s.`id`,sd.student_id,s.group_id,
                          (SELECT AVG(sdd.score)
                           FROM rms_score_detail AS sdd, rms_score AS sc
                           WHERE
                             sc.id = sdd.score_id
                             AND sc.group_id = $group_id
                             AND sc.for_semester = $for_semester
                             AND sc.exam_type = $exam_type
                             AND sdd.`is_parent` = 1
                             AND sdd.student_id = sd.student_id
                           GROUP BY sdd.student_id
                           LIMIT 1) AS avg_exam,
                          (SELECT avg(average)
                           FROM (SELECT
                                   AVG(sdd.score) AS average,
                                   sdd.student_id
                                 FROM `rms_score` AS s,
                                   `rms_score_detail` AS sdd
                                 WHERE
                                   s.id = sdd.score_id
                                   AND sdd.status = 1
                                   AND sdd.is_parent = 1
                                   AND s.exam_type = 1
                                   AND s.for_semester = $for_semester
                                   AND s.group_id = $group_id
                                 GROUP BY sdd.student_id) AS A_MONTT
                           WHERE A_MONTT.student_id = sd.student_id
                           LIMIT 1) AS avg_month
                        FROM `rms_score` AS s,
                          `rms_score_detail` AS sd,
                          `rms_student` AS st,
                          `rms_group` AS g
                        WHERE
                          s.`id` = sd.`score_id`
                          AND g.`id` = s.`group_id`
                          AND sd.`is_parent` = 1
                          AND s.status = 1
                          AND s.type_score = 1
                          AND g.id = $group_id
                          AND s.for_semester = $for_semester
                          AND s.exam_type = $exam_type
                        GROUP BY sd.student_id
                      ) AS TTT GROUP BY student_id ORDER BY score DESC
                     ) BB
                     USING (score)
                 ) A WHERE student_id=$student_id
              ) AS A1;
        ");
        return response()->json(['score'=>$q,'average'=>$average,'rank'=>$rank,'semester'=>$semester,'sem_rank'=>$sem_rank]);
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
        $q=DB::table('mobile_mobile_token')->where('uuid',$uuid)->get();
        if(count($q) === 0 && $stu_id !=0){
            DB::table('mobile_mobile_token')->insert(['token'=>$token,'uuid'=>$uuid,'stu_id'=>$stu_id,'date'=>date('Y-m-d')]);
        }else{
            DB::table('mobile_mobile_token')->where('uuid',$uuid)->where('stu_id',$stu_id)->update(['token'=>$token,'date'=>date('Y-m-d')]);
        }
        return response()->json(['status'=>'ok']);
    }
    public function postMessage(Request $req){
      $stu_id = $req->stu_id;
      $message = $req->message;
      $date = date('Y-m-d H:i:s');
      DB::table("mobile_message")->insert([
        'message'=>$message,
        'stu_id'=>$stu_id,
        'date'=>$date,
        'status'=>1
      ]);
      $query = DB::table("mobile_message")->orderBy('date','DESC')->limit(10)->get();
      return response()->json($query);
    }
    public function getMessage($limit,$offset,$stu_id){
      $query = DB::table("mobile_message")->where('stu_id',$stu_id)->skip($offset)->limit($limit)->orderBy('id','DESC')->get();
      return response()->json($query);
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
        $q = DB::table('mobile_appdata')->where('key','=',$key)->first();
        if(count($q)>0){
            return response()->json($q);
        }else{
            return '';
        }
    }
    public function getArticle($limit,$offset,$cate_id){
        $q = DB::table('mobile_article')->where('category_id','=',$cate_id)->skip($offset)->limit($limit)->orderBy('id','desc')->get();
        return response()->json($q);
    }
    public function getAbout($limit,$offset,$cate_id){
        $q = DB::table('mobile_about')->where('active','=',1)->skip($offset)->limit($limit)->orderBy('id','desc')->get();
        return response()->json($q);
    }
    public function getNews($limit,$offset,$cate_id){
        $q = DB::table('mobile_news_event')->where('active','=',1)->skip($offset)->limit($limit)->orderBy('id','desc')->get();
        return response()->json($q);
    }
    public function getNoti($limit,$offset,$cate_id){
        $q = DB::table('mobile_notice')->skip($offset)->limit($limit)->orderBy('id','desc')->get();
        return response()->json($q);
    }
    public function getGranding($limit,$offset,$cate_id){
        $q = DB::table('mobile_grading_system')->where('active','=',1)->skip($offset)->limit($limit)->orderBy('id','desc')->get();
        return response()->json($q);
    }
    public function getSingleArticle($id,$cate_id){
          $q = DB::table('mobile_notice')->where('id','=',$id)->first();
          return response()->json($q);
    }
    public function getSingleNews($id,$cate_id){
          $q = DB::table('mobile_news_event')->where('id','=',$id)->first();
          return response()->json($q);
    }
    public function getLocation(){
        $q = DB::table('mobile_location')->get();
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
    function getLabel($key){
       $q = DB::select("SELECT * FROM moble_label WHERE keyName='$key'");
       return response()->json($q);
    }
    function getStatusAttendence($stu_id,$group,$month){
          $sql = DB::select("
            SELECT
            sd.group_id,
            sd.type,
            sdd.attendence_status,
            sdd.description,
            sd.date_attendence,
            sd.for_session,
            MONTH(sd.date_attendence) AS for_month
            FROM
            rms_student_attendence AS sd,
            rms_student_attendence_detail AS sdd
            WHERE
            sd.type=1
            AND sd.id = sdd.attendence_id
            AND sdd.stu_id = $stu_id
            AND sd.group_id = $group
            AND MONTH(sd.date_attendence) = $month
          ");
        return response()->json($sql);
    }
    public function getGroupWithTotal($student_id,$group_id){
      $where = $group_id == 0 ? "WHERE g.id=gds.`group_id` AND gds.`stu_id`=$student_id" : "WHERE g.id=gds.`group_id` AND gds.`stu_id`=$student_id AND g.id=$group_id";
      $q = DB::select("
      SELECT T1.*,
        (SELECT COUNT(*) FROM (SELECT
               sd.group_id,sdd.attendence_status,sd.type
                FROM
                rms_student_attendence AS sd,
                rms_student_attendence_detail AS sdd
                WHERE
                (sd.type=2 OR sdd.attendence_status IN (4,5))
                AND sd.id = sdd.attendence_id
                AND sdd.stu_id = $student_id
        ) AS TS WHERE type=1 OR attendence_status = 1 AND T1.id = TS.group_id) AS small_mistake,
        (SELECT
        COUNT(*)
        FROM
        rms_student_attendence AS sd,
        rms_student_attendence_detail AS sdd
        WHERE
        (sd.type=2 OR sdd.attendence_status IN (4,5))
        AND sd.id = sdd.attendence_id
        AND sdd.stu_id = $student_id AND sdd.attendence_status = 2 AND sd.type!=1 AND T1.id = sd.group_id) AS medium_mistake,
        (SELECT
        COUNT(*)
        FROM
        rms_student_attendence AS sd,
        rms_student_attendence_detail AS sdd
        WHERE
        (sd.type=2 OR sdd.attendence_status IN (4,5))
        AND sd.id = sdd.attendence_id
        AND sdd.stu_id = $student_id AND sdd.attendence_status = 3 AND sd.type!=1 AND T1.id = sd.group_id) AS big_mistake
        FROM(
        SELECT
                  `g`.`id`,
                  (SELECT teacher_name_kh FROM `rms_teacher` WHERE `rms_teacher`.id=g.teacher_id) AS teacher_name,
                  (SELECT tel FROM `rms_teacher` WHERE `rms_teacher`.id=g.teacher_id) AS teacher_phone,
                  (SELECT teacher_name_kh FROM `rms_teacher` WHERE `rms_teacher`.id=g.teacher_assistance) AS teacher_assistance,
                  `g`.`group_code`    AS `group_code`,
                  (SELECT CONCAT(from_academic,'-',to_academic,'(',generation,')') FROM rms_tuitionfee WHERE rms_tuitionfee.id=g.academic_year LIMIT 1) AS academic,
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
                   $where
          ) AS T1
      ");
      return response()->json($q);
    }
    public function getGroupbyStudent($student_id){
        $q = DB::select('SELECT
            `g`.`id`,
            (SELECT teacher_name_kh FROM `rms_teacher` WHERE `rms_teacher`.id=g.teacher_id) AS teacher_name,
            (SELECT tel FROM `rms_teacher` WHERE `rms_teacher`.id=g.teacher_id) AS teacher_phone,
            (SELECT teacher_name_kh FROM `rms_teacher` WHERE `rms_teacher`.id=g.teacher_assistance) AS teacher_assistance,
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
    public function getGroupbyStudentAtt($student_id,$group_id){
        $where = $group_id == 0 ? ' ' : ' AND g.id='.$group_id;
        $q = DB::select('SELECT
            `g`.`id`,
            (SELECT teacher_name_kh FROM `rms_teacher` WHERE `rms_teacher`.id=g.teacher_id) AS teacher_name,
            (SELECT tel FROM `rms_teacher` WHERE `rms_teacher`.id=g.teacher_id) AS teacher_phone,
            (SELECT teacher_name_kh FROM `rms_teacher` WHERE `rms_teacher`.id=g.teacher_assistance) AS teacher_assistance,
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
            WHERE g.id=gds.`group_id` AND gds.`stu_id`='.$student_id.$where);
          $qr = DB::select("
            SELECT MONTHNAME(a.date_attendence) AS for_month,
            COUNT(attendence_status) AS amount_att,
            MONTH(a.date_attendence) AS month_number,
            attendence_status,a.group_id
            FROM rms_student_attendence AS a,
            `rms_student_attendence_detail`AS ad
            WHERE
            a.type=1
            AND a.status=1
            AND a.id=ad.attendence_id
            AND ad.stu_id = $student_id
            AND a.group_id=$group_id
            GROUP BY YEAR(a.date_attendence),
            MONTH(a.date_attendence) ORDER BY MONTH(a.date_attendence)
          ");
          $qrs = DB::select("
            SELECT MONTHNAME(a.date_attendence) AS for_month,
            COUNT(attendence_status) AS amount_att,
            MONTH(a.date_attendence) AS month_number,
            attendence_status,a.group_id
            FROM rms_student_attendence AS a,
            `rms_student_attendence_detail`AS ad
            WHERE
            a.type=1
            AND a.status=1
            AND a.id=ad.attendence_id
            AND ad.stu_id = $student_id
            AND a.group_id=$group_id
            AND attendence_status!=1
            GROUP BY YEAR(a.date_attendence),
            MONTH(a.date_attendence),attendence_status
          ");
        return response()->json(['group'=>$q,'month'=>$qr,'a_total'=>$qrs]);
    }
    public function getAttByMonth($group_id,$student_id){
      $q = DB::select("
        SELECT MONTHNAME(a.date_attendence) AS for_month,
        COUNT(attendence_status) AS amount_att,
        MONTH(a.date_attendence) AS month_number,
        attendence_status
        FROM rms_student_attendence AS a,
        `rms_student_attendence_detail`AS ad
        WHERE
        a.type=1
        AND a.status=1
        AND a.id=ad.attendence_id
        AND ad.stu_id = $student_id
        AND a.group_id=$group_id
        GROUP BY YEAR(a.date_attendence),
        MONTH(a.date_attendence),
        ad.attendence_status
      ");
      return response()->json($q);
    }
    public function getSchedule($group_id){
        $q = DB::select("
          SELECT
            gr.group_id,
          (SELECT CONCAT(rms_tuitionfee.from_academic,'-',rms_tuitionfee.to_academic,'(',rms_tuitionfee.generation,')')
           FROM rms_tuitionfee WHERE rms_tuitionfee.status=1 AND rms_tuitionfee.is_finished=0 AND rms_tuitionfee.id=gr.year_id LIMIT 1) AS years,
          (SELECT group_code FROM rms_group WHERE rms_group.id=gr.group_id LIMIT 1) AS group_code,
          (SELECT name_en FROM rms_view WHERE rms_view.key_code=gr.day_id AND rms_view.type=18 LIMIT 1)AS days,gr.day_id,
          gr.from_hour,gr.to_hour,(SELECT rms_group.session FROM rms_group WHERE rms_group.id=gr.group_id LIMIT 1)AS session_id,
          (SELECT v.name_en FROM rms_view AS v WHERE v.key_code=(SELECT rms_group.session FROM rms_group WHERE rms_group.id=gr.group_id LIMIT 1) AND v.type=4 LIMIT 1)AS session,
          (SELECT subject_titlekh FROM rms_subject WHERE is_parent=1 AND rms_subject.id = gr.subject_id AND subject_titlekh!='' LIMIT 1) AS subject_name,
          (SELECT teacher_name_kh FROM rms_teacher WHERE rms_teacher.id=gr.techer_id AND teacher_name_kh!='' LIMIT 1) AS teacher_name
          FROM
            rms_group_reschedule AS gr
          WHERE gr.status=1 and gr.group_id=$group_id GROUP BY gr.day_id ORDER BY gr.day_id ASC
        ");
        return response()->json($q);
    }
    public function getScheduleDetail($group_id,$day_id){
        $q = DB::select("
          SELECT
            gr.group_id,
          (SELECT CONCAT(rms_tuitionfee.from_academic,'-',rms_tuitionfee.to_academic,'(',rms_tuitionfee.generation,')')
           FROM rms_tuitionfee WHERE rms_tuitionfee.status=1 AND rms_tuitionfee.is_finished=0 AND rms_tuitionfee.id=gr.year_id LIMIT 1) AS years,
          (SELECT group_code FROM rms_group WHERE rms_group.id=gr.group_id LIMIT 1) AS group_code,
          (SELECT name_en FROM rms_view WHERE rms_view.key_code=gr.day_id AND rms_view.type=18 LIMIT 1)AS days,gr.day_id,
          gr.from_hour,gr.to_hour,(SELECT rms_group.session FROM rms_group WHERE rms_group.id=gr.group_id LIMIT 1)AS session_id,
          (SELECT v.name_en FROM rms_view AS v WHERE v.key_code=(SELECT rms_group.session FROM rms_group WHERE rms_group.id=gr.group_id LIMIT 1) AND v.type=4 LIMIT 1)AS session,
          (SELECT subject_titlekh FROM rms_subject WHERE is_parent=1 AND rms_subject.id = gr.subject_id AND subject_titlekh!='' LIMIT 1) AS subject_name,
          (SELECT teacher_name_kh FROM rms_teacher WHERE rms_teacher.id=gr.techer_id AND teacher_name_kh!='' LIMIT 1) AS teacher_name
          FROM
            rms_group_reschedule AS gr
          WHERE gr.status=1 and gr.group_id=$group_id AND gr.day_id=$day_id ORDER BY gr.from_hour ASC
        ");
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
          sd.group_id,
          sd.type,
          sdd.attendence_status as mistake_type,
          sdd.description,
          sd.date_attendence as mistake_date,
          sd.for_session
          FROM
          rms_student_attendence AS sd,
          rms_student_attendence_detail AS sdd
          WHERE
          (sd.type=2 OR sdd.attendence_status IN (4,5))
          AND sd.id = sdd.attendence_id
          AND sdd.stu_id = $stu_id
          AND sd.group_id = $group_id
        ");
        return response()->json($q);
    }
    public function getHoliday($type){
        $q=DB::table('mobile_calendar')->get();
        return response()->json($q);
    }
    public function getCurrentMonth($year,$month,$type){
        $q=DB::table('mobile_calendar')
            ->whereYear('date',$year)
            ->whereMonth('date',$month)
            ->get();
        return response()->json($q);
    }
}
