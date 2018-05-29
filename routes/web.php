<?php

// 홈
Route::get('/', 'PageController@index')->name('home');

//검색
Route::get('search', 'PageController@search')->name('search');

// 구독
Route::resource('subscribe', 'SubscribeController')->only('create', 'store');
Route::get('destory/subscribe/{email}/{code}', 'SubscribeController@destory');

// 포스트
Route::resource('post', 'PostController')->only('index', 'show');

// 시리즈
Route::resource('series', 'SeriesController')->only('index');

// 태그
Route::get('tag', 'TagController@index')->name('tag.index');
Route::post('tag/list', 'TagController@getTag')->name('tag.list');

// SEO
Route::get('sitemap', 'SEOController@sitemap');
Route::get('feed', 'SEOController@feed');

// 로그인 로그아웃 관련
Route::get('auth/login', 'AuthController@login')->name('login');
Route::post('auth/login', 'AuthController@store')->name('login.store');
Route::get('auth/logout', 'AuthController@logout')->name('logout');

Route::get('clear', function () {
    \Cache::flush();

    return 'clear';
});
