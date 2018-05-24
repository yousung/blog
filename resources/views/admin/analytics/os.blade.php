@extends('admin.template.app')

@section('content')
    <div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
        @include('admin.template.nav', ['name' => 'Engine'])
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Engine Vist List
                        @include('admin.analytics.date')
                    </div>
                    <div class="panel-body">
                        <table class="table table-striped">
                            <thead>
                            <th class="text-center">No.</th>
                            <th class="text-center">Device</th>
                            <th class="text-center">Device Version</th>
                            <th class="text-center">Browser</th>
                            <th class="text-center">Browser Version</th>
                            <th class="text-center">Vist</th>
                            </thead>
                            <tbody>
                            @forelse ($os as $key => $cnt)
                                <tr>
                                    <td class="text-center"> {{ $key+1 }} </td>
                                    <td class="text-center"> {{ $cnt[0] }} </td>
                                    <td class="text-center"> {{ $cnt[1] }} </td>
                                    <td class="text-center"> {{ $cnt[2] }} </td>
                                    <td class="text-center"> {{ $cnt[3] }} </td>
                                    <td class="text-center"> {{ $cnt[4] }} </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align: center;">들어온 방문자가 없습니다.</td>
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