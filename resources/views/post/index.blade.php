@extends('template.app')

@section('content')
	<!-- Page Header -->
	<header class="masthead" style="background-image: url({{ $global->post_bg ?? '/images/home-bg.jpg' }})">
		<div class="overlay"></div>
		<div class="container">
			<div class="row">
				<div class="col-lg-12 col-md-12 mx-auto">
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
			<div class="col-lg-12 col-md-12 mx-auto">
				@forelse($posts as $idx => $post)
					@include('post.post')

					@if (!$loop->last)
						<hr>
					@endif
				@empty
					<div class="post-preview">
						<a href="{{ route('search') }}" title="다시 검색하기">
							<h2 class="post-title m-t">
								검색된 결과가 없습니다.
							</h2>
							<p class="post-meta">
								다른 검색어를 입력해주세요.
							</p>
						</a>
					</div>
				@endforelse

			<!-- Pager -->
				<div class="clearfix m-t-30">
					{!! $posts->appends(array_filter(\Request::only('tag', 'series', 'search')))->links() !!}
				</div>
			</div>
		</div>
	</div>
@endsection