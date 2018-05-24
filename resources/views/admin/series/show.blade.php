@extends('admin.template.app')

@push('content')
<form action="{{ route('admin.series.destroy', $series->id) }}" method="post" id="post-delete">
    @csrf
    @method('DELETE')
</form>
@endpush

@section('content')
    <div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
        @include('admin.template.nav', ['name' => 'Series Editor'])

        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Title</label>
                                <p><b>{{ $series->title }}</b></p>
                            </div>

                            <div class="form-group">
                                <label>Sub Title</label>
                                <p><b>{{ $series->subTitle }}</b></p>
                            </div>

                            <hr />

                           <div class="m-t-30">
                               <div class="pull-left">
                                   <a href="{{ route('admin.series.index') }}{{ query_string('page', 'search') }}" class="btn btn-info" title="메뉴 버튼">Menu</a>
                               </div>

                               <div class="pull-right">
                                   <button type="button" class="btn btn-danger" onclick="$.confirmDelete('#post-delete')">Delete Button</button>
                                   <a href="{{ route('admin.series.edit', $series->id) }}" class="btn btn-primary" title="수정 버튼">Editor Button</a>
                               </div>
                           </div>
                        </div>
                    </div>
                </div>
            </div><!-- /.panel-->
        </div><!-- /.col-->
    </div>
@endsection