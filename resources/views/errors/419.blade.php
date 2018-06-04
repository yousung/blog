@php
    \SEO::setTitle('419 | 페이지가 만료되었습니다');
    \SEO::setDescription(noTag('페이지가 만료되었습니다. 다시 시도하여주세요'));
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
                    <h1>페이지가 만료되었습니다</h1>

                    <span class="m-t-30">다시 시도하여주세요</span>

                    <div class="m-t-30 m-b-30">
                        <a href="#" onclick="window.history.back()" class="btn btn-danger">뒤로가기</a>
                        <a href="{{ route('home') }}" class="btn btn-primary">홈으로가기</a>
                    </div>

                </div>
            </div>
        </div>
    </div>

@endsection