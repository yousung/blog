<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>

<rss version="2.0">
	<channel>
		<title>감성개발자</title>
		<link>{{ \URL::to('/') }}</link>
		<description>감성 개발자 러비쥬 블로그입니다</description>

		<image>
			<url>https://www.lovizu.com/images/yousung.jpg</url>
			<title>Lovizu Logo</title>
			<link>{{ \URL::to('/') }}</link>
			<description>Lovizu Logo ( Yousung, Ahn )</description>
		</image>

		@foreach($posts as $post)
			<item>
				<title>{{ $post->title }}</title>
				<description><![CDATA[{{ noTag($post->context) }}]]></description>
				<link>{{ route('post.show', optimus($post->id)) }}</link>
				<author>{{ optional($post->user)->name ?? '탈퇴한 사용자' }}</author>
				<pubDate>{{ $post->updated_at->toW3cString() }}</pubDate>
				<guid>{{ route('post.show', optimus($post->id)) }}</guid>
			</item>
		@endforeach
	</channel>
</rss>

