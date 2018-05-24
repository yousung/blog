let mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix
    .js('resources/assets/js/app.js', 'public/js')
    .combine([
        'resources/assets/js/clean-blog.js',
        'resources/assets/js/common.js'
    ], 'public/js/clean-blog.js')

    .styles([
        'resources/assets/css/clean-blog.css',
        'resources/assets/css/custom.css',
    ], 'public/css/clean-blog.css')

    .sass('resources/assets/scss/login.scss', 'public/css')

    .styles([
        'resources/assets/css/custom.css',
    ], 'public/css/admin.css')

    .copy('resources/assets/js/ckeditor', 'public/ckeditor/', false)

    .version();
