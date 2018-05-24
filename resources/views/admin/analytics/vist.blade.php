@extends('admin.template.app')

@section('content')
    <div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
        @include('admin.template.nav', ['name' => 'Vist'])
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Week Vist List
                        @include('admin.analytics.date')
                    </div>
                    <div class="panel-body">
                        <table class="table table-striped">
                            <thead>
                            <th class="text-center">Date</th>
                            <th class="text-center">Vist</th>
                            <th class="text-center">PageViews</th>
                            </thead>
                            <tbody>
                            @forelse ($count as $cnt)
                                <tr>
                                    <td class="text-center"> {{ $cnt['date']->toDateString() }} </td>
                                    <td class="text-center"> {{ $cnt['visitors'] }} </td>
                                    <td class="text-center"> {{ $cnt['pageViews'] }} </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" style="text-align: center;">들어온 방문자가 없습니다.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div><!-- /.panel-->
            </div><!-- /.panel-->
        </div><!-- /.col-->
    </div>
@endsection