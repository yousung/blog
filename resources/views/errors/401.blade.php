@php
    \SEO::setTitle('401 접근권한이 없습니다.');
    \SEO::setDescription(noTag('잘못된 접근입니다 주소를 다시 확인하여주세요.'));
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
                    <h1>접근권한이 없습니다</h1>

                    <span class="m-t-30">정상적인 방법으로 이용하여주시기 바랍니다.</span>

                    <div class="m-t-30 m-b-30">
                        <a href="#" onclick="window.history.back()" class="btn btn-danger">뒤로가기</a>
                        <a href="{{ route('home') }}" class="btn btn-primary">홈으로가기</a>
                    </div>

                </div>
            </div>
        </div>
    </div>

@endsection