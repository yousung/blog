@extends('template.app')

@section('script')
	<script id="dsq-count-scr" src="//lovizu-blog.disqus.com/count.js" async></script>
	@include('template.sytax')
	<script>
		console.log('This post is {{ $post->hit }} hit');
	</script>
@endsection

@section('content')
	<header class="masthead" style="background-image: url({{ $global->post_bg ?? '/images/post-bg.jpg' }})">
		<div class="overlay"></div>
		<div class="container">
			<div class="row">
				<div class="col-lg-11 col-md-11 mx-auto text-word">
					<div class="post-heading">
						<h1>{{ $post->title }}</h1>
						<h2 class="subheading">{{ $post->subTitle }}</h2>
						<span class="meta">
                        <a href="#">{{ optional($post->user)->name ?? '탈퇴한 사용자' }}</a>
							- {{ $post->created_at->toFormattedDateString() }}</span>
					</div>
				</div>
			</div>
		</div>
	</header>

	<article>
		<div class="container">
			<div class="row">
				<div class="col-lg-11 col-md-11 mx-auto text-word">
					{!! $post->context !!}
					<p>
						@foreach($post->tags as $tag)
							<kbd class="m-r"><a class="text-white" title="{{ $tag->name }}" href="{{ route('post.index') }}?tag={{ $tag->name }}">#{{ $tag->name }}</a></kbd>
						@endforeach
					</p>

					@if($series && count($series) > 1)
					<h3>관련 포스팅</h3>
					@foreach($series as $idx => $s)
						<ul class="list-group">
							<li class="list-group-item {{ hasUrl(route('post.show', optimus($s->id))) ? 'list-group-item-primary' : '' }}">
								<a href="{{ route('post.show', optimus($s->id)) }}" title="{{ $s->subTitle }}">
									<b>{{ $idx+1 }}.</b>
									{{ $s->title }}
									<span class="pull-right">[ {{ $s->created_at->toDateString() }} ]</span>
								</a>
							</li>
						</ul>
					@endforeach
					@endif

					<hr/>

					<div id="disqus_thread"></div>

					<script>

                        /**
                         *  RECOMMENDED CONFIGURATION VARIABLES: EDIT AND UNCOMMENT THE SECTION BELOW TO INSERT DYNAMIC VALUES FROM YOUR PLATFORM OR CMS.
                         *  LEARN WHY DEFINING THESE VARIABLES IS IMPORTANT: https://disqus.com/admin/universalcode/#configuration-variables*/
                        /*
                        var disqus_config = function () {
                        this.page.url = PAGE_URL;  // Replace PAGE_URL with your page's canonical URL variable
                        this.page.identifier = PAGE_IDENTIFIER; // Replace PAGE_IDENTIFIER with your page's unique identifier variable
                        };
                        */
                        (function() { // DON'T EDIT BELOW THIS LINE
                            var d = document, s = d.createElement('script');
                            s.src = 'https://lovizu-blog.disqus.com/embed.js';
                            s.setAttribute('data-timestamp', +new Date());
                            (d.head || d.body).appendChild(s);
                        })();
					</script>
					<noscript>Please enable JavaScript to view the <a href="https://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>
				</div>
			</div>
		</div>
	</article>
@endsection