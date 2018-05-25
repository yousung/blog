@extends('template.app')

@section('content')
	<!-- Page Header -->
{{--	<header class="masthead" style="background-image: url({{ $global->post_bg ?? '/images/home-bg.jpg' }})">--}}
	<header class="masthead" style="background-image: url({{ '/images/home-bg.jpg' }})">
		<div class="overlay"></div>
		<div class="container">
			<div class="row">
				<div class="col-lg-10 col-md-10 mx-auto">
					<div class="site-heading">
						<h1>Lovizu Blog</h1>
						<span class="subheading">Emotional developers learning</span>
					</div>
				</div>
			</div>
		</div>
	</header>

	<!-- Main Content -->
	<div class="container">
		<div class="row">
			<div class="col-lg-10 col-md-10 mx-auto">
				@forelse($posts as $idx => $post)
					@include('post.post')

					@if (!$loop->last)
						<hr>
					@endif
				@empty
					<div class="post-preview">
						<a href="{{ route('search') }}" title="다시 검색하기">
							<h2 class="post-title m-t">
								아직 준비중입니다.
							</h2>
							<p class="post-meta">
								잠시만 기다려주세요 ^^
							</p>
						</a>
					</div>
				@endforelse

				<div class="clearfix m-t-30">
					{!! $posts->links() !!}
				</div>
			</div>
		</div>
	</div>
@endsection