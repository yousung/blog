<div class="post-preview">
	@if($post->series)
		<a href="{{ route('post.index') }}?series={{ $post->series->slug }}"
		   title="{{ $post->series->title }}">[ {{ $post->series->title }} 시리즈 ]</a>
	@endif
	<a href="{{ route('post.show', $post->secret) }}" title="{{ $post->title }}">
		<h2 class="post-title m-t">
			{{ $post->title }}
		</h2>
		<h3 class="post-subtitle">
			{{ $post->subTitle }}
		</h3>
	</a>
	<p class="post-meta">Posted by
		{{--<a href="">{{ optional($post->user)->name ?? '탈퇴한 사용자' }}</a>--}}
		{{ optional($post->user)->name ?? '탈퇴한 사용자' }}
		@if($post->created_at >= \Carbon\Carbon::now()->subDay())
			{{ $post->created_at->diffForHumans() }}
		@else
			{{ $post->created_at->toFormattedDateString() }}
		@endif
	</p>
	<div>
		@foreach($post->tags as $tag)
			<kbd class="m-r"><a class="text-white" title="{{ $tag->name }}" href="{{ route('post.index') }}?tag={{ $tag->name }}">#{{ $tag->name }}</a></kbd>
		@endforeach
	</div>
</div>