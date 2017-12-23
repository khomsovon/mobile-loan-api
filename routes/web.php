<?php
use Dingo\Api\Routing\Router;

/** @var Router $api */
$api = app(Router::class);
/* api for socialwall asia */
$api->version('v1', function (Router $api) {
    $api->group(['prefix' => 'v1/auth'], function(Router $api) {
        $api->post('signup', 'App\\Api\\V1\\Controllers\\SignUpController@signUp');
        $api->post('login', 'App\\Api\\V1\\Controllers\\LoginController@login');

        $api->post('recovery', 'App\\Api\\V1\\Controllers\\ForgotPasswordController@sendResetEmail');
        $api->post('reset', 'App\\Api\\V1\\Controllers\\ResetPasswordController@resetPassword');
        $api->post('changepassword', 'App\\Api\\V1\\Controllers\\ChangePasswordController@changePassword');
    });
    $api->group(['prefix' => 'v1/user'], function(Router $api) {
        $api->get('view','App\\Api\\V1\\Controllers\\UserController@getAuthenticatedUser');
        $api->get('getUser/{stu_id}','App\\Api\\V1\\Controllers\\UserController@getUser');
    });
    $api->group(['prefix' => 'v1/student'], function(Router $api) {
        $api->get('getGroup/{stu_id}','App\\Api\\V1\\Controllers\\StudentController@getGroup');
        $api->get('getExam/{stu_id}/{group_id}','App\\Api\\V1\\Controllers\\StudentController@getExam');
        $api->get('getScore/{stu_id}/{group_id}/{exam_id}','App\\Api\\V1\\Controllers\\StudentController@getScore');
        $api->get('config/{key}','App\\Api\\V1\\Controllers\\StudentController@getConfig');
        $api->get('article/{limit}/{offset}/{cate_id}','App\\Api\\V1\\Controllers\\StudentController@getArticle');
        $api->get('articleSingle/{id}/{cate_id}','App\\Api\\V1\\Controllers\\StudentController@getSingleArticle');
        $api->get('getLocation','App\\Api\\V1\\Controllers\\StudentController@getLocation');
        $api->get('getHoliday/{type}','App\\Api\\V1\\Controllers\\StudentController@getHoliday');
        $api->get('getPaymentInvoice/{student_id}','App\\Api\\V1\\Controllers\\StudentController@getPaymentInvoice');
        $api->get('getPaymentDetail/{student_id}/{payment_id}','App\\Api\\V1\\Controllers\\StudentController@getPaymentDetail');
        $api->get('getGroupbyStudent/{student_id}','App\\Api\\V1\\Controllers\\StudentController@getGroupbyStudent');
        $api->get('getScoreDetail/{score_id}/{degree_id}/{student_id}/{exam_type}/{for_semester}/{group_id}','App\\Api\\V1\\Controllers\\StudentController@getScoreDetail');
        $api->get('getMasterScore/{student_id}','App\\Api\\V1\\Controllers\\StudentController@getMasterScore');
        $api->get('getStatusAttendence/{student_id}/{group}','App\\Api\\V1\\Controllers\\StudentController@getStatusAttendence');
        $api->get('getAttendanceCountNotification/{student_id}','App\\Api\\V1\\Controllers\\StudentController@getAttendanceCountNotification');
        $api->get('getDiscipline/{student_id}/{group_id}','App\\Api\\V1\\Controllers\\StudentController@getDiscipline');
        $api->get('getCurrentMonth/{year}/{month}/{type}','App\\Api\\V1\\Controllers\\StudentController@getCurrentMonth');
        $api->post('postToken','App\\Api\\V1\\Controllers\\StudentController@postToken');
        $api->post('postMessage','App\\Api\\V1\\Controllers\\StudentController@postMessage');
        $api->get('getMessage/{limit}/{offset}/{stu_id}','App\\Api\\V1\\Controllers\\StudentController@getMessage');
    });
    $api->group(['middleware' => 'jwt.auth'], function(Router $api) {
        $api->get('protected', function() {
            return response()->json([
                'message' => 'Access to this item is only for authenticated user. Provide a token in your request!'
            ]);
        });

        $api->get('refresh', [
            'middleware' => 'jwt.refresh',
            function() {
                return response()->json([
                    'message' => 'By accessing this endpoint, you can refresh your access token at each request. Check out this response headers!'
                ]);
            }
        ]);
    });

    $api->get('hello', function() {
        return response()->json([
            'message' => 'This is a simple example of item returned by your APIs. Everyone can see it.'
        ]);
    });
});

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('reset_password/{token}', ['as' => 'password.reset', function($token)
{
    // implement your reset password route here!
}]);
Route::get('notification/index','NotificatonController@getNotification');
Route::post('notification/push','NotificatonController@postPushNotification');
Route::post('notification/SinglePush/{stu_id}/{title}/{message}','NotificatonController@postSinglePushNotification');
Route::get('/', function () {
    return view('welcome');
});
