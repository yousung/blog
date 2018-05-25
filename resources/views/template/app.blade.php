<!doctype html>
<html lang="ko">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="ie=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
	<meta name="csrf-token" content="{{ csrf_token() }}" />

	<link rel="alternate" hreflang="ko" href="{{ \Request::fullUrl() }}">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.1/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/earlyaccess/nanumgothic.css">
	<link href='https://fonts.googleapis.com/css?family=Lora:400,700,400italic,700italic' rel='stylesheet' type='text/css'>
	<link href='https://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800' rel='stylesheet' type='text/css'>
	<link href='{{ mix('/css/clean-blog.css') }}' rel='stylesheet' type='text/css'>
	<link rel="shortcut icon" href="/favicon.ico">

	{!! SEO::generate() !!}
	@stack('style')
	@yield('style')

	@include('seo.google')
</head>
<body>
<!-- Navigation -->
@include('template.nav')

@yield('content')
@stack('content')

<hr>

<!-- Footer -->
@include('template.footer')
@stack('script')
@yield('script')
@include('sweet::alert')

</body>

@yield('last')
@include('seo.ad')

</html>