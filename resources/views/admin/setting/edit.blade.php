@extends('admin.template.app')

@section('content')
    <div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
        @include('admin.template.nav', ['name' => 'Setting Editor'])

        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="col-md-12">
                            <form action="{{ route('admin.setting.update', $setting->id) }}" method="post">
                                @csrf
                                @method('PATCH')

                                <div class="col-md-12">
                                    <div class="form-group m-t-30 col-md-6 text-center">
                                        <label class="bg-title">최신글 백그라운드</label>
                                        <div class="thumb m-a" onclick="$.fileOnLoad('#home_src' ,'#post_bg', '1900x1200');">
                                            <img id="home_src" src="{{ $setting->post_bg }}" alt="최신글 백그라운드">
                                            <p>[ 권장 이미지 : 1900 x 1200 ]</p>
                                            <input type="hidden" id="post_bg" name="post_bg" value="{{ $setting->post_bg }}">
                                        </div>
                                    </div>

                                    <div class="form-group m-t-30 col-md-6 text-center">
                                        <label class="bg-title">자세히보기 백그라운드</label>
                                        <div class="thumb m-a" onclick="$.fileOnLoad('#show_src' ,'#show_bg', '1900x1200');">
                                            <img id="show_src" src="{{ $setting->show_bg }}" alt="주제별 백그라운드">
                                            <p>[ 권장 이미지 : 1900 x 1200 ]</p>
                                            <input type="hidden" id="show_bg" name="show_bg" value="{{ $setting->show_bg }}">
                                        </div>
                                    </div>

                                    <div class="form-group m-t-30 col-md-6 text-center">
                                        <label class="bg-title">주제별 백그라운드</label>
                                        <div class="thumb m-a" onclick="$.fileOnLoad('#tag_src' ,'#tag_bg', '1900x1200');">
                                            <img id="tag_src" src="{{ $setting->tag_bg }}" alt="주제별 백그라운드">
                                            <p>[ 권장 이미지 : 1900 x 1200 ]</p>
                                            <input type="hidden" id="tag_bg" name="tag_bg" value="{{ $setting->tag_bg }}">
                                        </div>
                                    </div>

                                    <div class="form-group m-t-30 col-md-6 text-center">
                                        <label class="bg-title">시리즈 백그라운드</label>
                                        <div class="thumb m-a" onclick="$.fileOnLoad('#series_src' ,'#series_bg', '1900x1200');">
                                            <img id="series_src" src="{{ $setting->series_bg }}" alt="시리즈 백그라운드">
                                            <p>[ 권장 이미지 : 1900 x 1200 ]</p>
                                            <input type="hidden" id="series_bg" name="series_bg" value="{{ $setting->series_bg }}">
                                        </div>
                                    </div>

                                    <div class="form-group m-t-30 col-md-6 text-center">
                                        <label class="bg-title">구독하기 백그라운드</label>
                                        <div class="thumb m-a" onclick="$.fileOnLoad('#subscribe_src' ,'#subscribe_bg', '1900x1200');">
                                            <img id="subscribe_src" src="{{ $setting->subscribe_bg }}" alt="구독하기 백그라운드">
                                            <p>[ 권장 이미지 : 1900 x 1200 ]</p>
                                            <input type="hidden" id="subscribe_bg" name="subscribe_bg" value="{{ $setting->subscribe_bg }}">
                                        </div>
                                    </div>

                                    <div class="form-group m-t-30 col-md-6 text-center">
                                        <label class="bg-title">검색 백그라운드</label>
                                        <div class="thumb m-a" onclick="$.fileOnLoad('#search_src' ,'#search_bg', '1900x1200');">
                                            <img id="search_src" src="{{ $setting->search_bg }}" alt="검색 백그라운드">
                                            <p>[ 권장 이미지 : 1900 x 1200 ]</p>
                                            <input type="hidden" id="search_bg" name="search_bg" value="{{ $setting->search_bg }}">
                                        </div>
                                    </div>


                                    <div class="form-group m-t-30 col-md-6 text-center">
                                        <label class="bg-title">에러 백그라운드</label>
                                        <div class="thumb m-a" onclick="$.fileOnLoad('#error_src' ,'#errors_bg', '1900x1200');">
                                            <img id="error_src" src="{{ $setting->errors_bg }}" alt="에러 백그라운드">
                                            <p>[ 권장 이미지 : 1900 x 1200 ]</p>
                                            <input type="hidden" id="errors_bg" name="errors_bg" value="{{ $setting->errors_bg }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="pull-right m-t-30">
                                    <button type="submit" class="btn btn-primary">Submit Button</button>
                                    <button type="reset" class="btn btn-default">Reset Button</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div><!-- /.panel-->
        </div><!-- /.col-->
    </div>
@endsection