<?php
namespace App\Api\V1\Controllers;
use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;

class LoanController extends Controller
{
  public function getDisbursement(){
    $q = DB::select("
    SELECT
      (SELECT name_kh FROM `ln_ins_client` WHERE client_id = s.customer_id LIMIT 1) AS client_name_kh,
      (SELECT b.branch_namekh FROM `ln_branch` AS b WHERE b.br_id = s.branch_id LIMIT 1) AS branch_namekh,
      p.item_name,
      s.date_sold,
      s.balance,
      s.paid,
      s.selling_price
    FROM
      `ln_ins_sales_install` AS s,
      `ln_ins_product` AS p
    WHERE
      s.product_id = p.id
      AND s.`status` =1
    ");
    return response()->json($q);
  }

  public function searchDisbursement(Request $req){
    $branch_id = $req->branch;
    $category_id = $req->category;
    $start_date = $req->start_date;
    $end_date = $req->end_date;
    $where = "";
    if(!empty($branch_id)){
        $where .=" AND s.branch_id=".$branch_id;
    }
    if(!empty($category_id)){
      $where .=" AND p.cate_id=".$category_id;
    }
    $from_date =(empty($start_date))? '1' : " s.date_sold >= '".$start_date." 00:00:00'";
    $to_date = (empty($end_date))? '1' : " s.date_sold <= '".$end_date." 23:59:59'";
    $where.= " AND ".$from_date." AND ".$to_date;
    $q = DB::select("
    SELECT
      (SELECT name_kh FROM `ln_ins_client` WHERE client_id = s.customer_id LIMIT 1) AS client_name_kh,
      (SELECT b.branch_namekh FROM `ln_branch` AS b WHERE b.br_id = s.branch_id LIMIT 1) AS branch_namekh,
      p.item_name,
      s.date_sold,
      s.balance,
      s.paid,
      s.selling_price
    FROM
      `ln_ins_sales_install` AS s,
      `ln_ins_product` AS p
    WHERE
      s.product_id = p.id
      AND s.`status` =1
    ".$where);
    return response()->json($q);
  }

  public function searchPayment(Request $req){
    $branch_id = $req->branch;
    $category_id = $req->category;
    $start_date = $req->start_date;
    $end_date = $req->end_date;
    $where = "";
    if(!empty($branch_id)){
        $where .=" AND l.branch_id=".$branch_id;
    }
    if(!empty($category_id)){
      $where .=" AND l.cate_id=".$category_id;
    }
    $from_date =(empty($start_date))? '1' : " d.`date_payment` >= '".$start_date." 00:00:00'";
    $to_date = (empty($end_date))? '1' : " d.`date_payment` <= '".$end_date." 23:59:59'";
    $where.= " AND ".$from_date." AND ".$to_date;
    $q = DB::select("
      SELECT
      (SELECT   `lb`.`branch_namekh` FROM `ln_branch` `lb`  WHERE (`lb`.`br_id` = l.`branch_id`)  LIMIT 1) AS `branch_namekh`,
      `c`.`name_kh` AS `name_kh`,

      `c`.`phone` AS phone_number,

      d.`date_payment`,
      d.`principle_after` AS principle_after,
      `d`.`total_interest_after` AS `total_interest_after`,
      `d`.`total_payment`        AS `total_payment`,
      `d`.`installment_amount`   AS `times`,
       (SELECT inp.item_name FROM `ln_ins_product` AS inp WHERE inp.id = l.`product_id` LIMIT 1) AS item_name
      FROM
      `ln_ins_sales_install` AS l,
      `ln_ins_sales_installdetail` d,
      `ln_ins_client` AS c
      WHERE l.`id` = d.`sale_id`
      AND c.`client_id` = l.`customer_id`
      AND l.`is_completed` = 0
      AND l.`status` = 1
      AND d.`status` = 1
      AND d.`is_completed` =0
    ".$where);
    return response()->json($q);
  }

  public function searchRemaining(Request $req){
    $branch_id = $req->branch;
    $category_id = $req->category;
    $start_date = $req->start_date;
    $end_date = $req->end_date;
    $where = "";
    if(!empty($branch_id)){
        $where .=" AND s.branch_id=".$branch_id;
    }
    if(!empty($category_id)){
      $where .=" AND s.cate_id=".$category_id;
    }
    $from_date =(empty($start_date))? '1' : " s.date_sold >= '".$start_date." 00:00:00'";
    $to_date = (empty($end_date))? '1' : " s.date_sold <= '".$end_date." 23:59:59'";
    $where.= " AND ".$from_date." AND ".$to_date;
    $q = DB::select("
      SELECT
          c.name_kh AS `client_kh`,
          (SELECT inp.item_name FROM `ln_ins_product` AS inp WHERE inp.id = s.`product_id` LIMIT 1) AS item_name,
          s.*,
          (SELECT  `ln_ins_receipt_money`.`paid_times` FROM `ln_ins_receipt_money` WHERE ((`ln_ins_receipt_money`.`status` = 1) AND (`s`.`id` = `ln_ins_receipt_money`.`loan_id`))
          ORDER BY `ln_ins_receipt_money`.`paid_times` DESC
          LIMIT 1) AS `installment_amount`,
          (SELECT
          SUM(`ln_ins_receipt_money`.`principal_paid`)
          FROM `ln_ins_receipt_money`
          WHERE ((`ln_ins_receipt_money`.`status` = 1)
          AND (`s`.`id` = `ln_ins_receipt_money`.`loan_id`))) AS `total_principaid`,

          (SELECT
          SUM(`ln_ins_receipt_money`.`total_paymentpaid`)
          FROM `ln_ins_receipt_money`
          WHERE ((`ln_ins_receipt_money`.`status` = 1)
          AND (`s`.`id` = `ln_ins_receipt_money`.`loan_id`))) AS `total_paymentpaid`
      FROM
      `ln_ins_sales_install` AS s,
      `ln_ins_client` AS c
      WHERE c.`client_id` = s.`customer_id` AND s.status=1
    ".$where);
    return response()->json($q);
  }

  public function searchCollection(Request $req){
    $branch_id = $req->branch;
    $start_date = $req->start_date;
    $end_date = $req->end_date;
    $where = "";
    if(!empty($branch_id)){
        $where .=" AND crm.branch_id=".$branch_id;
    }
    $from_date =(empty($start_date)) ? '1' : " `crm`.`date_input` >= '".$start_date." 00:00:00'";
    $to_date = (empty($end_date)) ? '1' : " `crm`.`date_input` <= '".$end_date." 23:59:59'";
    $where.= " AND ".$from_date." AND ".$to_date;
    $q = DB::select("
      SELECT
      (SELECT
      `ln_branch`.`branch_namekh`
      FROM `ln_branch` WHERE (`ln_branch`.`br_id` = `crm`.`branch_id`) LIMIT 1) AS `branch_name`,
      (SELECT `l`.`sale_no` FROM `ln_ins_sales_install` `l` WHERE (`l`.`id` = `crm`.`loan_id`) LIMIT 1) AS `loan_number`,
      (SELECT `c`.`name_kh` FROM `ln_ins_client` `c` WHERE (`c`.`client_id` = `crm`.`client_id`) LIMIT 1) AS `client_name`,
      (SELECT `u`.`first_name` FROM `rms_users` `u` WHERE (`u`.`id` = `crm`.`user_id`)) AS `user_name`,
      `crm`.`loan_id`		     AS loan_id,
      `crm`.`receipt_no`           AS `receipt_no`,
      `crm`.`branch_id`            AS `branch_id`,
      `crm`.`date_pay`             AS `date_pay`,
      `crm`.`date_payment`         AS `date_payment`,
      `crm`.`date_input`           AS `date_input`,
      `crm`.`user_id`              AS `user_id`,
      `crm`.`payment_option`       AS `payment_option`,
      `crm`.`total_paymentpaid`    AS `total_paymentpaid`,
      `crm`.`paid_times`           AS `paid_times`
      FROM (`ln_ins_receipt_money` `crm`
      JOIN `ln_ins_receipt_money_detail` `d`)
      WHERE ((`crm`.`status` = 1)
      AND (`crm`.`id` = `d`.`receipt_id`)
      AND (`crm`.`status` = 1))
    ".$where);
    return response()->json($q);
  }

  public function searchStock(Request $req){
    $branch_id = $req->branch;
    $category_id = $req->category;
    $where = "";
    if(!empty($branch_id)){
        $where .=" AND pl.location_id=".$branch_id;
    }
    if(!empty($category_id)){
      $where .=" AND p.cate_id=".$category_id;
    }
    $q = DB::select("
      SELECT pl.`location_id`,
      (SELECT b.branch_namekh FROM `ln_branch` AS b WHERE b.br_id = pl.`location_id` LIMIT 1) AS branch_namekh,
      (SELECT c.name FROM `ln_ins_category` AS c WHERE c.id = p.`cate_id` LIMIT 1) AS categoryName,
      p.*,
      pl.`qty` FROM
      `ln_ins_product` AS p,
      `ln_ins_prolocation` AS pl
      WHERE
        pl.`pro_id` = p.`id` AND status=1
    ".$where);
    return response()->json($q);
  }

  public function getBranch(){
    $q = DB::select("SELECT br_id AS id,branch_namekh as name FROM ln_branch WHERE branch_namekh !='' AND status=1");
    return response()->json($q);
  }
  public function getCategory(){
    $q = DB::select("SELECT b.id,b.name FROM ln_ins_category AS b WHERE b.status=1 AND b.name!='' ORDER BY b.name ASC");
    return response()->json($q);
  }

  public function getPayment(){
    $q = DB::select("
      SELECT
      (SELECT   `lb`.`branch_namekh` FROM `ln_branch` `lb`  WHERE (`lb`.`br_id` = l.`branch_id`)  LIMIT 1) AS `branch_namekh`,
      `c`.`name_kh` AS `name_kh`,

      `c`.`phone` AS phone_number,

      d.`date_payment` AS date_payment,
      d.`principle_after` AS principle_after,
      `d`.`total_interest_after` AS `total_interest_after`,
      `d`.`total_payment`        AS `total_payment`,
      `d`.`installment_amount`   AS `times`,
       (SELECT inp.item_name FROM `ln_ins_product` AS inp WHERE inp.id = l.`product_id` LIMIT 1) AS item_name
      FROM
      `ln_ins_sales_install` AS l,
      `ln_ins_sales_installdetail` d,
      `ln_ins_client` AS c
      WHERE l.`id` = d.`sale_id`
      AND c.`client_id` = l.`customer_id`
      AND l.`is_completed` = 0
      AND l.`status` = 1
      AND d.`status` = 1
      AND d.`is_completed` =0
    ");
    return response()->json($q);
  }

  public function getCollection(){
    $q = DB::select("
      SELECT
      (SELECT
      `ln_branch`.`branch_namekh`
      FROM `ln_branch` WHERE (`ln_branch`.`br_id` = `crm`.`branch_id`) LIMIT 1) AS `branch_name`,
      (SELECT `l`.`sale_no` FROM `ln_ins_sales_install` `l` WHERE (`l`.`id` = `crm`.`loan_id`) LIMIT 1) AS `loan_number`,
      (SELECT `c`.`name_kh` FROM `ln_ins_client` `c` WHERE (`c`.`client_id` = `crm`.`client_id`) LIMIT 1) AS `client_name`,
      (SELECT `u`.`first_name` FROM `rms_users` `u` WHERE (`u`.`id` = `crm`.`user_id`)) AS `user_name`,
      `crm`.`loan_id`		     AS loan_id,
      `crm`.`receipt_no`           AS `receipt_no`,
      `crm`.`branch_id`            AS `branch_id`,
      `crm`.`date_pay`             AS `date_pay`,
      `crm`.`date_payment`         AS `date_payment`,
      `crm`.`date_input`           AS `date_input`,
      `crm`.`user_id`              AS `user_id`,
      `crm`.`payment_option`       AS `payment_option`,
      `crm`.`total_paymentpaid`    AS `total_paymentpaid`,
      `crm`.`paid_times`           AS `paid_times`
      FROM (`ln_ins_receipt_money` `crm`
      JOIN `ln_ins_receipt_money_detail` `d`)
      WHERE ((`crm`.`status` = 1)
      AND (`crm`.`id` = `d`.`receipt_id`)
      AND (`crm`.`status` = 1))
    ");
    return response()->json($q);
  }

  public function getLoanRemaining(){
    $q = DB::select("
      SELECT
          c.name_kh AS `client_kh`,
          (SELECT inp.item_name FROM `ln_ins_product` AS inp WHERE inp.id = s.`product_id` LIMIT 1) AS item_name,
          s.*,
          (SELECT  `ln_ins_receipt_money`.`paid_times` FROM `ln_ins_receipt_money` WHERE ((`ln_ins_receipt_money`.`status` = 1) AND (`s`.`id` = `ln_ins_receipt_money`.`loan_id`))
          ORDER BY `ln_ins_receipt_money`.`paid_times` DESC
          LIMIT 1) AS `installment_amount`,
          (SELECT
          SUM(`ln_ins_receipt_money`.`principal_paid`)
          FROM `ln_ins_receipt_money`
          WHERE ((`ln_ins_receipt_money`.`status` = 1)
          AND (`s`.`id` = `ln_ins_receipt_money`.`loan_id`))) AS `total_principaid`,

          (SELECT
          SUM(`ln_ins_receipt_money`.`total_paymentpaid`)
          FROM `ln_ins_receipt_money`
          WHERE ((`ln_ins_receipt_money`.`status` = 1)
          AND (`s`.`id` = `ln_ins_receipt_money`.`loan_id`))) AS `total_paymentpaid`
      FROM
      `ln_ins_sales_install` AS s,
      `ln_ins_client` AS c
      WHERE c.`client_id` = s.`customer_id` AND s.status=1
    ");
    return response()->json($q);
  }

  public function getLoanStock(){
    $q = DB::select("
      SELECT pl.`location_id`,
      (SELECT b.branch_namekh FROM `ln_branch` AS b WHERE b.br_id = pl.`location_id` LIMIT 1) AS branch_namekh,
      (SELECT c.name FROM `ln_ins_category` AS c WHERE c.id = p.`cate_id` LIMIT 1) AS categoryName,
      p.*,
      pl.`qty` FROM
      `ln_ins_product` AS p,
      `ln_ins_prolocation` AS pl
      WHERE
        pl.`pro_id` = p.`id` AND status=1
    ");
    return response()->json($q);
  }
}
