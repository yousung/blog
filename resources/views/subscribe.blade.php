@extends('template.app')

@section('content')
	<header class="masthead" style="background-image: url({{ $global->subscribe_bg ?? '/images/subscribe-bg.jpg' }})">
		<div class="overlay"></div>
		<div class="container">
			<div class="row">
				<div class="col-lg-8 col-md-10 mx-auto">
					<div class="page-heading">
						<h1>구독하기</h1>
						<span class="subheading">새로운 소식이 있을 때마다 알려드립니다</span>
					</div>
				</div>
			</div>
		</div>
	</header>

	<div class="container">
		<div class="row">
			<div class="col-lg-8 col-md-10 mx-auto">
				<p>새로운 소식이 있을 때마다 메일로 알려드립니다.</p>
				<p>닉네임과 이메일은 암호화하여 저장되며 다른 용도로는 사용되지 않습니다.</p>
				<form  action="{{ route('subscribe.store') }}" novalidate method="post">
					@csrf

					<div class="control-group">
						<div class="form-group floating-label-form-group controls">
							<label>닉네임</label>
							<input type="text" class="form-control" placeholder="Nick Name" id="name" name="name"
								   required data-validation-required-message="Please enter your name.">
							<p class="help-block text-danger"></p>
						</div>
					</div>
					<div class="control-group">
						<div class="form-group floating-label-form-group controls">
							<label>이메일 주소</label>
							<input type="email" class="form-control" placeholder="Email Address"
								   name="email" id="email"
								   required data-validation-required-message="Please enter your email address.">
							<p class="help-block text-danger"></p>
						</div>
					</div>
					<br>
					<div id="success"></div>
					<div class="form-group pull-right">
						<button type="submit" class="btn btn-primary" id="sendMessageButton">구독신청</button>
					</div>
				</form>
			</div>
		</div>
	</div>
@endsection