<footer>
	<div class="container">
		<div class="row">
			<div class="col-lg-8 col-md-10 mx-auto">
				<ul class="list-inline text-center">
					@include('common.share', [
						'href'=>'mailto:help@lovizu.com',
						'title'=>'개발자에게 메일 보내기',
						'icon'=>'fa-at',
					])

					@include('common.share', [
						'href'=>'https://www.facebook.com/nug22',
						'title'=>'개발자 Facebook 바로가기',
						'icon'=>'fa-facebook',
					])

					@include('common.share', [
						'href'=>'https://github.com/yousung',
						'title'=>'개발자 Github 바로가기',
						'icon'=>'fa-github',
					])
				</ul>
				<p class="copyright text-muted">Copyright &copy; Lovizu {{ \Carbon\Carbon::now()->year }}</p>
			</div>
		</div>
	</div>
</footer>

<!-- After Css loader -->
<link href='https://fonts.googleapis.com/css?family=Lora:400,700,400italic,700italic' rel='stylesheet' type='text/css'>
<link href='https://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800' rel='stylesheet' type='text/css'>
<link href='{{ mix('/css/clean-blog.css') }}' rel='stylesheet' type='text/css'>
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.css" rel="stylesheet" type="text/css" />
<link href="//cdn.jsdelivr.net/highlight.js/8.7/styles/monokai_sublime.min.css" rel="stylesheet" type="text/css">

<script src="//code.jquery.com/jquery-latest.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.1/js/bootstrap.bundle.min.js"></script>
<script src="{{ mix('/js/app.js') }}"></script>
<script src="{{ mix('/js/clean-blog.js') }}"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.js"></script>
@include('errors.errors')

