<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use FCM;
use DB;
class NotificatonController extends Controller
{
    public function getNotification(){
        return view('notification.notification');
    }
    public function postPushNotification(Request $req){
        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(60*20);

        $notificationBuilder = new PayloadNotificationBuilder($req->title);
        $notificationBuilder->setBody($req->message)
            ->setBadge(1)
            ->setSound('default');
        $mydata=[
            'title'=>$req->title,
            'body'=>$req->message,
            'type'=>2
        ];
        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData(['data' => $mydata]);

        $option = $optionBuilder->build();
        $notification = $notificationBuilder->build();
        $data = $dataBuilder->build();

        // You must change it to get your tokens
        $tokens = DB::table('mobile_mobile_token')->pluck('token')->toArray();

        $downstreamResponse = FCM::sendTo($tokens, $option, $notification, $data);

        $downstreamResponse->numberSuccess();
        $downstreamResponse->numberFailure();
        $downstreamResponse->numberModification();

//return Array - you must remove all this tokens in your database
        $downstreamResponse->tokensToDelete();

//return Array (key : oldToken, value : new token - you must change the token in your database )
        $downstreamResponse->tokensToModify();

//return Array - you should try to resend the message to the tokens in the array
        $downstreamResponse->tokensToRetry();

// return Array (key:token, value:errror) - in production you should remove from your database the tokens present in this array
        $downstreamResponse->tokensWithError();

    }
    public function postSinglePushNotification($stu_id,$title,$message){
        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(60*20);

        $notificationBuilder = new PayloadNotificationBuilder($title);
        $notificationBuilder->setBody($message)
            ->setSound('default');
        $mydata=[
          'title'=>$title,
          'body'=>$message,
          'student_id'=>$stu_id
        ];
        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData(['data' => $mydata]);

        $option = $optionBuilder->build();
        $notification = $notificationBuilder->build();
        $data = $dataBuilder->build();

        $token = DB::table('mobile_mobile_token')->where('stu_id',$stu_id)->first();
        $token = count($token) > 0 ? $token->token : '';
        $downstreamResponse = FCM::sendTo($token, $option, $notification, $data);

        $downstreamResponse->numberSuccess();
        $downstreamResponse->numberFailure();
        $downstreamResponse->numberModification();

        //return Array - you must remove all this tokens in your database
        $downstreamResponse->tokensToDelete();

        //return Array (key : oldToken, value : new token - you must change the token in your database )
        $downstreamResponse->tokensToModify();

        //return Array - you should try to resend the message to the tokens in the array
        $downstreamResponse->tokensToRetry();

        // return Array (key:token, value:errror) - in production you should remove from your database the tokens

    }
}
