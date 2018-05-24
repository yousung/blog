<nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNav">
	<div class="container">
		<a class="navbar-brand" href="{{ route('home') }}" title="홈화면 가기">감성 개발자</a>
		<button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
			Menu
			<i class="fa fa-bars"></i>
		</button>
		<div class="collapse navbar-collapse" id="navbarResponsive">
			<ul class="navbar-nav ml-auto">
				<li class="nav-item">
					<a class="nav-link" title="Search" href="{{ route('search') }}">
						<i class="fa fa-search mobile-hidden"></i>
						<span class="mobile-visible">검색</span>
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" title="New Post" href="{{ route('post.index') }}">전체보기</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" title="Tags" href="{{ route('tag.index') }}">주제별 보기</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" title="Series" href="{{ route('series.index') }}">시리즈</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" title="Subscribe" href="{{ route('subscribe.create') }}">구독하기</a>
				</li>
			</ul>
		</div>
	</div>
</nav>