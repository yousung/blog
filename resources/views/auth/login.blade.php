<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lovizu - LOGIN</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.8.0/css/bootstrap-datepicker3.min.css" rel="stylesheet">
    <link href="{{ mix('/css/login.css') }}" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <!--[if lt IE 9]>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>
<div class="row">
    <div class="col-xs-10 col-xs-offset-1 col-sm-8 col-sm-offset-2 col-md-4 col-md-offset-4">
        <div class="login-panel panel panel-default">
            <div class="panel-heading">Login</div>
            <div class="panel-body">
                <form action="{{ route('login.store') }}" method="post">
                    @csrf

                    <fieldset>
                        <div class="form-group">
                            <input class="form-control" placeholder="E-mail"
                                   name="email" type="email" value="{{ old('email') }}" autofocus="">
                        </div>
                        <div class="form-group">
                            <input class="form-control" placeholder="Password"
                                   name="password" type="password" value="">
                        </div>
                        <div class="checkbox">
                            <label>
                                <input name="remember" type="checkbox" value="true">Remember Me
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary pull-right">Login</button>

                    </fieldset>
                </form>
            </div>
        </div>
    </div><!-- /.col-->
</div><!-- /.row -->


<script src="//code.jquery.com/jquery-latest.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>
</html>
