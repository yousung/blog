@extends('admin.template.app')

@section('content')
    <div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
        @include('admin.template.nav', ['name' => 'Series Create'])

        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="col-md-12">
                            <form action="{{ route('admin.series.store') }}" method="post">
                                @csrf

                                @include('admin.series.form')
                            </form>
                        </div>
                    </div>
                </div>
            </div><!-- /.panel-->
        </div><!-- /.col-->
    </div>
@endsection