@php
    \SEO::setTitle('500 서버 내부 오류');
    \SEO::setDescription(noTag('서버내부 오류, 잠시 후에 다시 시도해주세요 지속적인 문제가 있을경우 관리자에게 알려주세요'));
@endphp

@extends('template.app')

@section('content')
    <!-- Page Header -->
    <header class="masthead" style="background-image: url({{ $global->errors_bg ?? '/images/errors-bg.jpg' }})">
        <div class="overlay"></div>
        <div class="container">
            <div class="row">
                <div class="col-lg-8 col-md-10 mx-auto">
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
            <div class="col-lg-8 col-md-10 mx-auto">
                <div class="errorbox text-center">
                    <h1>서버내부 오류</h1>

                    <span class="m-t-30">계속 같은 상황이 발생되면 서버 관리자에게 문의 바랍니다.</span>

                    <div class="m-t-30 m-b-30">
                        <a href="#" onclick="window.history.back()" class="btn btn-danger">뒤로가기</a>
                        <a href="{{ route('home') }}" class="btn btn-primary">홈으로가기</a>
                    </div>

                </div>
            </div>
        </div>
    </div>

@endsection