@extends('template.app')

@section('script')
	<script src="//cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.1/clipboard.min.js"></script>
	<script src="//developers.kakao.com/sdk/js/kakao.min.js"></script>
	<script id="dsq-count-scr" src="//lovizu-blog.disqus.com/count.js" async></script>

	@include('template.sytax')

	<script>
		var sendBtn;
		var goUrl;
        var openWindow;
        $(function(){
            Kakao.init('{{ env('KKAO_SCRIPT_API') }}');
            goUrl = function(url){
                openWindow = window.open('about:blank');
                openWindow.location.href = url;
            };

            sendBtn = function() {
                Kakao.Link.sendDefault({
                    objectType: 'feed',
                    content: {
                        title: '{{ $post->title }}',
                        description: '{{ $post->subTitle }}',
                        imageUrl: '{{ get_images($post)[0] }}',
                        link: {
                            mobileWebUrl: '{{ Request::fullUrl() }}',
                            webUrl: '{{ Request::fullUrl() }}'
                        }
                    },
                    buttons: [
                        {
                            title: 'Lovizu',
                            link: {
                                mobileWebUrl: '{{ Request::fullUrl() }}',
                                webUrl: '{{ Request::fullUrl() }}'
                            }
                        },
                        {
                            title: 'N Blog',
                            link: {
                                mobileWebUrl: 'https://blog.naver.com/nug22/{{ $post->naver }}',
                                webUrl: 'https://developers.kakao.com//{{ $post->naver }}'
                            }
                        }
                    ]
                });
            }
        });

		console.log('This post is {{ $post->hit }} hit');
	</script>
@endsection

@section('content')
	<header class="masthead" style="background-image: url({{ $global->post_bg ?? '/images/post-bg.jpg' }})">
		<div class="overlay"></div>
		<div class="container">
			<div class="row">
				<div class="col-lg-10 col-md-10 mx-auto text-word">
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
				<div class="col-lg-10 col-md-10 mx-auto text-word">
					{!! $post->context !!}

					<p class="pull-left col-lg-5 col-md-5">
						@foreach($post->tags as $tag)
							<kbd class="m-r"><a class="text-white" title="{{ $tag->name }}" href="{{ route('post.index') }}?tag={{ $tag->name }}">#{{ $tag->name }}</a></kbd>
						@endforeach
					</p>

					<p class="pull-right col-lg-5 col-md-5">
						<button class="pull-right copy-btn none-btn" title="URL 복사하기" data-clipboard-text="{{ \Request::fullUrl() }}">
							<i style="font-size: 2rem;" class="xi xi-file-add-o"></i>
						</button>
						<button class="pull-right none-btn" onclick="goUrl('https://twitter.com/share?text={{ $post->title }}&url={{ \Request::fullUrl() }}')" title="트위터로 전달">
							<i style="font-size: 2rem;" class="xi xi-twitter"></i>
						</button>
						<button class="pull-right  none-btn" onclick="sendBtn();" title="카카오톡으로 보내기">
							<i style="font-size: 2rem;" class="xi xi-kakaotalk"></i>
						</button>
						<button class="pull-right none-btn" onclick="goUrl('https://www.facebook.com/sharer.php?u={{ \Request::fullUrl() }}&t={{ $post->title }}');" title="페이스북으로 전달">
							<i style="font-size: 2rem;" class="xi xi-facebook"></i>
						</button>
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
                        {{--var disqus_config = function () {--}}
                            {{--this.page.url = '{{ \Request::fullUrl() }}';--}}
                            {{--this.page.identifier = '{{ \Request::input('page', 1) }}';--}}
                        {{--};--}}
                        (function() {
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