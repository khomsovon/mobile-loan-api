<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notification</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">
    <!-- Styles -->
    <style>
        html, body {
            background-color: #fff;
            color: #636b6f;
            font-family: 'Raleway', sans-serif;
            font-weight: 100;
            height: 100vh;
            margin: 0;
        }

        .full-height {
            height: 100vh;
        }

        .flex-center {
            align-items: center;
            display: flex;
            justify-content: center;
        }

        .position-ref {
            position: relative;
        }

        .top-right {
            position: absolute;
            right: 10px;
            top: 18px;
        }

        .content {
            text-align: center;
        }

        .title {
            font-size: 84px;
        }

        .links > a {
            color: #636b6f;
            padding: 0 25px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .1rem;
            text-decoration: none;
            text-transform: uppercase;
        }

        .m-b-md {
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
<div class="flex-center position-ref full-height">
    <div class="content">
        <form action="{{url('notification/push')}}" method="post" name="push-notification">
            <input type="hidden" name="_token" value="{{csrf_token()}}"/>
            <div class="form-group">
                <label class="col-md-3 form-label">Title</label>
                <div class="col-md-7">
                    <input name="title" id="title" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label class="col-md-3 form-label">Message</label>
                <div class="col-md-7">
                    <input name="message" id="message" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label class="col-md-3 form-label">Submit</label>
                <div class="col-md-7">
                    <input type="submit" name="push" value="New Message">
                </div>
            </div>
        </form>
    </div>
</div>
</body>
</html>
