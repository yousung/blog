<div id="sidebar-collapse" class="col-sm-3 col-lg-2 sidebar">
    <div class="profile-sidebar">
        <div class="profile-userpic">
            <img src="/images/yousung.jpg" class="img-responsive" alt="">
        </div>
        <div class="profile-usertitle">
            <div class="profile-usertitle-name">{{ \Auth::user()->name }}</div>
            <div class="profile-usertitle-status"><span class="indicator label-success"></span>Online</div>
        </div>
        <div class="clear"></div>
    </div>
    <div class="divider"></div>

    <ul class="nav menu">
        <li class="{{ hasUrl(route('admin.admin'), 1) }}">
            <a href="{{ route('admin.admin') }}"><em class="fa fa-dashboard">&nbsp;</em> Dashboard</a>
        </li>

        <li class="{{ hasUrl(route('admin.series.index')) }}">
            <a href="{{ route('admin.series.index') }}"><em class="fa fa-folder">&nbsp;</em> Series</a>
        </li>

        <li class="{{ hasUrl(route('admin.post.index')) }}">
            <a href="{{ route('admin.post.index') }}"><em class="fa fa-file">&nbsp;</em> Posts</a>
        </li>


        <li class="{{ hasUrl(route('admin.setting.edit', 1)) }}">
            <a href="{{ route('admin.setting.edit', 1) }}"><em class="fa fa-cogs">&nbsp;</em> Setting</a>
        </li>

        {{--<li class="parent "><a data-toggle="collapse" href="#sub-item-1">--}}
                {{--<em class="fa fa-navicon">&nbsp;</em> Multilevel <span data-toggle="collapse" href="#sub-item-1" class="icon pull-right"><em class="fa fa-plus"></em></span>--}}
            {{--</a>--}}
            {{--<ul class="children collapse" id="sub-item-1">--}}
                {{--<li><a class="" href="#">--}}
                        {{--<span class="fa fa-arrow-right">&nbsp;</span> Sub Item 1--}}
                    {{--</a></li>--}}
                {{--<li><a class="" href="#">--}}
                        {{--<span class="fa fa-arrow-right">&nbsp;</span> Sub Item 2--}}
                    {{--</a></li>--}}
                {{--<li><a class="" href="#">--}}
                        {{--<span class="fa fa-arrow-right">&nbsp;</span> Sub Item 3--}}
                    {{--</a></li>--}}
            {{--</ul>--}}
        {{--</li>--}}
        <li><a href="{{ route('logout') }}"><em class="fa fa-power-off">&nbsp;</em> Logout</a></li>
    </ul>
</div><!--/.sidebar-->
