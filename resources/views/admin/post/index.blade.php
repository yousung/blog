@extends('admin.template.app')

@section('script')
    <script>
        function naverSync(postId, obj){
            var elId = '#post-'+postId;

            $(elId).loading({
                onStart: function(loading) {
                    loading.overlay.slideDown(400);
                },
                onStop: function(loading) {
                    loading.overlay.slideUp(400);
                }
            });

            axios.post('/admin/naver/' + postId).then(function(){
                $(elId).loading('stop');
                $(obj).text('Y')
            }).catch(function(err){
                $(elId).loading('stop');
                console.log(err);
            });
        }
    </script>
@endsection

@section('content')
    <div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
        @include('admin.template.nav', ['name' => 'Posts'])

        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Posts List

                        <div class="pull-right w-500">
                            <a href="{{ route('admin.post.create') }}" class="btn btn-primary pull-right">Create</a>
                            <form action="{{ route('admin.post.index') }}" method="get">
                                <input type="text" name="search" class="form-control pull-right m-r-10 w-300 h-35" value="{{ \Request::input('search') }}">
                            </form>
                        </div>
                    </div>
                    <div class="panel-body">
                        <table class="table table-striped">
                            <thead>
                                <th class="text-center">No.</th>
                                <th class="text-center">Title</th>
                                <th class="text-center">Sub Title</th>
                                <th class="text-center">Series</th>
                                <th class="text-center">Naver</th>
                                <th class="text-center">Hit</th>
                                <th class="text-center">Created_at</th>
                            </thead>
                            <tbody>
                            @foreach($posts as $post)
                                <tr class="text-center" id="post-{{ $post->id }}">
                                    <td class="text-center">{{ $post->id }}</td>
                                    <td><a class="none" href="{{ route('admin.post.show', $post->id) }}{{ query_string('page', 'search') }}" title="{{ $post->title }}">{{ $post->title }}</a></td>
                                    <td><a class="none" href="{{ route('admin.post.show', $post->id) }}{{ query_string('page', 'search') }}" title="{{ $post->title }}">{{ $post->subTitle }}</a></td>
                                    <td class="text-center">
                                        <a class="none" href="{{ route('admin.post.index') }}?series={{ optional($post->series)->title }}" title="{{ optional($post->series)->title ?? '없음' }}">
                                            {{ optional($post->series)->title ?? '-' }}
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        @if($post->naver)
                                            <button class="btn" onclick="naverSync('{{ $post->id }}', this)">Y</button>
                                        @else
                                            <button class="btn" onclick="naverSync('{{ $post->id }}', this)">N</button>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $post->hit }}</td>
                                    <td class="text-center">{{ $post->created_at->toDateString() }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center">
                        {!! $posts->links() !!}
                    </div>
                </div><!-- /.panel-->
            </div><!-- /.panel-->
        </div><!-- /.col-->
    </div>
@endsection