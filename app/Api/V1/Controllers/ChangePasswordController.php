<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Requests\ChangePasswordRequest;
use Config;
use App\User;
//use Dingo\Api\Contract\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Password;
use App\Api\V1\Requests\ResetPasswordRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
class ChangePasswordController extends Controller
{
    public function changePassword(Request $request, JWTAuth $JWTAuth)
    {
        $validation = Validator::make($request->all(), array(
                'current_password' => 'required',
                'new_password' => 'required',
                'confirm_password' => 'required|same:new_password'
            )
        );
        if($validation->fails()){
            return response()->json([
                'status' => 'false',
                'token' => 'false',
            ]);
        }else{
            $user = User::where('stu_id', '=', $request->input('stu_id'))->first();
            if(Hash::check($request->input('current_password'), $user->password)) {
                $this->change($request->input('new_password'),$request->input('stu_id'));
                return response()->json([
                    'status' => 'ok',
                    'token' => $JWTAuth->fromUser($user)
                ]);
            }else{
                return response()->json([
                    'status' => 'false',
                    'token' => 'false'
                ]);
            }
        }
    }

    /**
     * Get the broker to be used during password reset.
     *
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     */
    public function broker()
    {
        return Password::broker();
    }

    /**
     * Get the password reset credentials from the request.
     *
     * @param  ResetPasswordRequest  $request
     * @return array
     */
    protected function credentials(ChangePasswordRequest $request)
    {
        return $request->only(
            'current_password',
            'new_password',
            'confirm_password',
            'stu_id'
        );
    }
    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @param  string  $password
     * @return void
     */
    protected function change($new_password,$stu_id)
    {
        DB::table('rms_student')->where('stu_id','=',$stu_id)->update(['password'=>bcrypt($new_password)]);
    }
}
