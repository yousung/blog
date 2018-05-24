@extends('template.app')

@section('script')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqcloud/1.0.4/jqcloud.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqcloud/1.0.4/jqcloud-1.0.4.min.js"></script>
    <script>
        axios.post('{{ route('tag.list') }}')
            .then(function(data){
                console.log(data.data);
                $("#tag-list").jQCloud(data.data);
            });
    </script>
@endsection

@section('content')
    <!-- Page Header -->
    <header class="masthead" style="background-image: url({{ $global->tag_bg ?? '/images/tag-bg.jpg' }})">
        <div class="overlay"></div>
        <div class="container">
            <div class="row">
                <div class="col-lg-11 col-md-11 mx-auto">
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
            <div class="col-lg-11 col-md-11 mx-auto">
                <div id="tag-list" style="width: 100%; height: 500px;"></div>
            </div>
        </div>
    </div>
@endsection