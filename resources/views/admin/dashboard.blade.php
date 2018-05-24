@extends('admin.template.app')

@section('script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.min.js"></script>
    <script>
        axios.post('{{ route('admin.dashboard.api') }}').then(function (data) {
            new Chart(document.getElementById("line-chart"), {
                type: 'bar',
                data: {
                    labels: data.data.week.date,
                    datasets: [{
                        label: '# 주간 방문자 수',
                        data: data.data.week.cnt,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 206, 86, 0.2)',
                            'rgba(75, 192, 192, 0.2)',
                            'rgba(153, 102, 255, 0.2)',
                            'rgba(255, 159, 64, 0.2)'
                        ],
                        borderColor: [
                            'rgba(255,99,132,1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)',
                            'rgba(255, 159, 64, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero:true
                            }
                        }]
                    }
                }
            });

            $('#user-count').text(data.data.lastCnt);
            $('#post-count').text(data.data.post);
            $('#subscribe-count').text(data.data.subscribe);
            $('#week-count').text(data.data.weekCnt);
        })
    </script>

@endsection

@section('content')
    <div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
        @include('admin.template.nav', ['name' => 'Dashboard'])

        <div class="panel panel-container">
            <div class="row">
                <div class="col-xs-6 col-md-3 col-lg-3 no-padding">
                    <div class="panel panel-teal panel-widget border-right">
                        <div class="row no-padding"><em class="fa fa-xl fa-user color-blue"></em>
                            <div class="large" id="user-count">로딩중</div>
                            <div class="text-muted">Today</div>
                        </div>
                    </div>
                </div>

                <div class="col-xs-6 col-md-3 col-lg-3 no-padding">
                    <div class="panel panel-blue panel-widget border-right">
                        <div class="row no-padding"><em class="fa fa-xl fa-comments color-orange"></em>
                            <div class="large" id="post-count">로딩중</div>
                            <div class="text-muted">Posts</div>
                        </div>
                    </div>
                </div>

                <div class="col-xs-6 col-md-3 col-lg-3 no-padding">
                    <div class="panel panel-orange panel-widget border-right">
                        <div class="row no-padding"><em class="fa fa-xl fa-envelope-open color-teal"></em>
                            <div class="large" id="subscribe-count">로딩중</div>
                            <div class="text-muted">Subscribe</div>
                        </div>
                    </div>
                </div>

                <div class="col-xs-6 col-md-3 col-lg-3 no-padding">
                    <div class="panel panel-red panel-widget ">
                        <div class="row no-padding"><em class="fa fa-xl fa-eye color-red"></em>
                            <div class="large" id="week-count">로딩중</div>
                            <div class="text-muted">Week</div>
                        </div>
                    </div>
                </div>

            </div><!--/.row-->
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Site Traffic Overview
                    </div>

                    <div class="panel-body">
                        <div class="canvas-wrapper">
                            <canvas class="main-chart" id="line-chart" height="300" width="600"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection