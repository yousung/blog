@servers(['web' => 'lovizu'])


@setup
    $username = 'lovizu';                     // 서버의 사용자 계정
    $remote = 'https://github.com/yousung/blog.git';  // 깃허브 저장소 주소
    $base_dir = "/home/{$username}";        // 웹서비스를 담을 기본 디렉터리
    $project_root = "{$base_dir}/web";        // 프로젝트 루트 디렉터리
    $shared_dir = "{$base_dir}/shared";         // 새 코드를 배포해도 이전 코드와 연속성을 유지하는 하는 파일/디렉터리 모음
    $release_dir = "{$base_dir}/releases";      // 깃허브에서 받은 코드(릴리스)를 담을 디렉터리
    $distname = 'release_' . date('YmdHis');    // 릴리스 이름(디렉터리 이름)

    $required_dirs = [
        $shared_dir,
        $release_dir,
    ];

    $shared_item = [
        "{$shared_dir}/.env" => "{$release_dir}/{$distname}/.env",
        "{$shared_dir}/storage" => "{$release_dir}/{$distname}/storage",
        "{$shared_dir}/cache" => "{$release_dir}/{$distname}/bootstrap/cache",
    ];
@endsetup


@task('lovizu', ['on' => ['web']])
@foreach ($required_dirs as $dir)
    [ ! -d {{ $dir }} ] && mkdir -p {{ $dir }};
@endforeach

echo 'GIT CLONE';
cd {{ $release_dir }} && git clone -b master {{ $remote }} {{ $distname }};

[ ! -f {{ $shared_dir }}/.env ] && cp {{ $release_dir }}/{{ $distname }}/.env.example {{ $shared_dir }}/.env;
[ ! -d {{ $shared_dir }}/storage ] && cp -R {{ $release_dir }}/{{ $distname }}/storage {{ $shared_dir }};
[ ! -d {{ $shared_dir }}/cache ] && cp -R {{ $release_dir }}/{{ $distname }}/bootstrap/cache {{ $shared_dir }};


@foreach($shared_item as $global => $local)
    [ -f {{ $local }} ] && rm {{ $local }};
    [ -d {{ $local }} ] && rm -rf {{ $local }};
    ln -nfs {{ $global }} {{ $local }};
@endforeach

echo 'composer install && autoload';
cd {{ $release_dir }}/{{ $distname }} && composer install --prefer-dist --no-scripts --no-dev;

echo 'nfs link';
ln -nfs {{ $release_dir }}/{{ $distname }} {{ $project_root }};

echo 'chown';
sudo chmod -R 777 {{ $shared_dir }}/storage;
sudo chmod -R 777 {{ $shared_dir }}/cache;
sudo chgrp -h -R www-data {{ $release_dir }}/{{ $distname }};

echo 'Old Date Delete';
cd {{ $release_dir }} && ls -td1 * | tail -n +5 | xargs rm -fr
cd {{ $release_dir }}/{{ $distname }} && php artisan migrate --force;

echo 'service reload';
sudo service nginx restart;
sudo service php7.1-fpm restart;

{{--echo 'queue restart';--}}
{{--sudo service supervisor reload;--}}


@endtask