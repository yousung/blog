@extends('admin.template.app')

@section('content')
    <div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
        @include('admin.template.nav', ['name' => 'Series'])

        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Series List

                        <div class="pull-right w-500">
                            <a href="{{ route('admin.series.create') }}" class="btn btn-primary pull-right">Create</a>
                            <form action="{{ route('admin.series.index') }}" method="get">
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
                                <th class="text-center">Posts</th>
                                <th class="text-center">Created_at</th>
                            </thead>
                            <tbody>
                            @foreach($series as $ser)
                                <tr class="text-center">
                                    <td class="text-center">{{ $ser->id }}</td>
                                    <td class="text-center"><a class="none" href="{{ route('admin.series.show', $ser->id) }}{{ query_string('page', 'search') }}">{{ $ser->title }}</a></td>
                                    <td class="text-center"><a class="none" href="{{ route('admin.series.show', $ser->id) }}{{ query_string('page', 'search') }}">{{ $ser->subTitle }}</a></td>
                                    <td class="text-center">{{ $ser->posts_count }}</td>
                                    <td class="text-center">{{ $ser->created_at->toDateString() }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center">
                        {!! $series->links() !!}
                    </div>
                </div><!-- /.panel-->
            </div><!-- /.panel-->
        </div><!-- /.col-->
    </div>
@endsection