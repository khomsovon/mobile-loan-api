<?php

return [
    'driver' => env('FCM_PROTOCOL', 'http'),
    'log_enabled' => false,

    'http' => [
        'server_key' => env('FCM_SERVER_KEY', 'AAAAFUl5wqk:APA91bFktDCO937lkDQ1JnP3fT5xT9YMfdEmBq0GH-QZs-GUGy9YbceyMvLQHNw3LBkgPbV9tZfDmzjti6oaJQyVHzhWrBmvoTdUaNhvD-q5DC3KNunJMVjDRTG3VLPrBVB8c8H9NNb_'),
        'sender_id' => env('FCM_SENDER_ID', '91427029673'),
        'server_send_url' => 'https://fcm.googleapis.com/fcm/send',
        'server_group_url' => 'https://android.googleapis.com/gcm/notification',
        'timeout' => 30.0, // in second
    ],
];
