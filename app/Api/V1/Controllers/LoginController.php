<?php

namespace App\Api\V1\Controllers;

use App\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tymon\JWTAuth\JWTAuth;
use App\Http\Controllers\Controller;
use App\Api\V1\Requests\LoginRequest;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
class LoginController extends Controller
{
    public function login(LoginRequest $request, JWTAuth $JWTAuth)
    {
        $credentials = $request->only(['stu_code', 'password']);
        $data = array();
        try{
            $token = $JWTAuth->attempt($credentials);
            if(!$token) {
                //throw new AccessDeniedHttpException();
                $data=[
                    'status' => false,
                    'token' => $token,
                    'data'=>''
                ];
            }else{
                $data=[
                    'status' => 'ok',
                    'token' => $token,
                    'stu_id' =>$JWTAuth->toUser($token)->stu_id
                ];
            }
        }catch(JWTException $e){
            throw new HttpException(500);
        }

        return response()
            ->json($data);
    }
}
