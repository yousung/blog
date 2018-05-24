<?php

Route::get('/', 'Admin\PageController@dashboard')->name('admin');
Route::post('dashboard', 'Admin\PageController@api')->name('dashboard.api');

Route::resource('post', 'Admin\PostController');
Route::resource('series', 'Admin\SeriesController');

Route::resource('setting', 'Admin\SettingController')->only('edit', 'update');

Route::post('file', 'Admin\FileController@store')->name('file.store');

Route::get('analytics/{type}', 'Admin\AnalyticsController@index')->name('analytics');
