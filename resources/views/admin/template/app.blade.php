<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>Lovizu Admin</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.8.0/css/bootstrap-datepicker3.min.css" rel="stylesheet">

    <!--Custom Font-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/earlyaccess/nanumgothic.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/minireset.css/0.0.2/minireset.min.css" rel="stylesheet">
    <link href="{{ mix('/css/login.css') }}" rel="stylesheet">
    <link href="{{ mix('/css/admin.css') }}" rel="stylesheet">

    <!--[if lt IE 9]>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

    @yield('style')
    @stack('style')
</head>
    <body>

    @include('admin.template.header')

    @include('admin.template.side')

    @yield('content')
    @stack('content')

    <script src="//code.jquery.com/jquery-latest.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery.form/4.2.2/jquery.form.min.js"></script>
    <script src="{{ mix('js/app.js') }}"></script>

    @include('template.sytax')
    @yield('script')
    @stack('script')
    @include('sweet::alert')

    </body>
</html>