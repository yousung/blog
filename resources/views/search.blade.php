@extends('template.app')

@section('content')
    <!-- Page Header -->
    {{--<header class="masthead" style="background-image: url({{ $global->search_bg ?? '/images/search-bg.jpg' }})">--}}
    <header class="masthead" style="background-image: url({{ '/images/search-bg.jpg' }})">
        <div class="overlay"></div>
        <div class="container">
            <div class="row">
                <div class="col-lg-10 col-md-10 mx-auto">
                    <div class="site-heading">
                        <h1>Lovizu Blog</h1>
                        <span class="subheading">Emotional developers learning</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="row">
            <div class="col-lg-10 col-md-10 mx-auto">
                <p>제목, 태그, 시리즈명을 검색해주세요.</p>
                <form  action="{{ route('post.index') }}" novalidate method="get">

                    <div class="control-group">
                        <div class="form-group floating-label-form-group controls">
                            <label>검색어</label>
                            <input type="search" class="form-control" placeholder="Keyword"
                                   name="search" id="search">
                            <p class="help-block text-danger"></p>
                        </div>
                    </div>
                    <br>
                    <div id="success"></div>
                    <div class="form-group pull-right">
                        <button type="submit" class="btn btn-info" id="sendMessageButton">검색</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection