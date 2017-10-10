<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Requests\ChangePasswordRequest;
use Config;
use App\User;
use JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Password;
use App\Api\V1\Requests\ResetPasswordRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;
use DB;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Auth;
class UserController extends Controller
{
    public function getAuthenticatedUser()
    {
        $user = array();
        $status = true;
        try {
            if(!JWTAuth::parseToken()->authenticate()){
                $user =  response()->json(['user_not_found'], 404);
                $status = false;
            }else{
                $user = JWTAuth::parseToken()->authenticate();
            }
        }catch(TokenExpiredException $e){
            $user =  response()->json(['token_expired'],$e->getStatusCode());
            $status = false;
        }catch(TokenInvalidException $e) {
            $user =  response()->json(['token_invalid'],$e->getStatusCode());
            $status = false;
        }catch(JWTException $e){
            $user =  response()->json(['token_absent'],$e->getStatusCode());
            $status = false;
        }
        // the token is valid and we have found the user via the sub claim
        return response()->json(['data'=>compact('user'),'status'=>$status]);
    }
    public function getUser($stu_id){
        $q = DB::table("rms_student")
            ->leftJoin("rms_major","rms_major.major_id","=","rms_student.grade")
            ->leftJoin("rms_occupation as rof","rof.occupation_id","=","rms_student.father_job")
            ->leftJoin("rms_occupation as rom","rom.occupation_id","=","rms_student.mother_job")
            ->where('stu_id','=',$stu_id)
            ->select([
                "rms_student.*",
                "rms_major.major_enname",
                "rof.occu_name as father_occu",
                "rom.occu_name as mother_occu"
            ])->first();
       echo json_encode($q);
    }
}
