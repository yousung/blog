<!doctype html>
<html lang="ko">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="ie=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
	<meta name="csrf-token" content="{{ csrf_token() }}" />

	<link rel="alternate" hreflang="ko" href="{{ \Request::fullUrl() }}">
	<link rel="shortcut icon" href="/favicon.ico">

	{!! SEO::generate() !!}
	@stack('style')
	@yield('style')

	<link rel="stylesheet" media="screen" type="text/css" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css">
	<link rel="stylesheet" media="screen" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css"/>
	<link rel="stylesheet" media="screen" type="text/css" href='https://fonts.googleapis.com/css?family=Nanum+Gothic|Lora:400,700,400italic,700italic|Open+Sans:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800'>
	<link rel="stylesheet" media="screen" type="text/css" href='{{ mix('/css/clean-blog.css') }}'>
	@include('seo.google')

	<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
    @if(env('AD', 'false') === 'true')
        <script>
            (adsbygoogle = window.adsbygoogle || []).push({
                google_ad_client: "ca-pub-1322828698634218",
                enable_page_level_ads: true
            });
        </script>
    @endif
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

</html>