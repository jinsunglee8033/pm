@extends('sub-agent.layout.default')

@section('content')
    <!-- Start parallax -->
    <div class="parallax" data-background="/img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
        <div class="overlay white"></div>
        <div class="container">
            <div class="inner-head">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h4>Users</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li><a href="#">Setting</a></li>
                            <li class="active">Users</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End parallax -->

    <!-- Start contain wrapp -->
    <div class="contain-wrapp padding-bot70">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-sm-12">
                    <div class="clearfix"></div>


                    <div class="tabbable tab-lg">
                        <ul class="nav nav-tabs">
                            <li>
                                <a href="/sub-agent/setting/my-password">My Password</a>
                            </li>
                            <li>
                                <a href="/sub-agent/setting/my-account">My Account</a>
                            </li>
                            <li class="active">
                                <a href="/sub-agent/setting/users">Users</a>
                            </li>
                            <li>
                                <a href="/sub-agent/setting/documents">Documents</a>
                            </li>
                            <li>
                                <a href="/sub-agent/setting/att-documents">ATT Documents</a>
                            </li>
                            <li>
                                <a href="/sub-agent/setting/h2o-documents">H2O Documents</a>
                            </li>
                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content">
                            <form id="frm_act" class="form-horizontal well" method="post" action="/sub-agent/setting/users" class="row marginbot15">
                                {!! csrf_field() !!}
                                <div class="form-group">
                                    <label class="col-sm-2 control-label">User ID</label>
                                    <div class="col-sm-4">
                                        <input type="text" class="form-control" name="user_id" value="{{ $user_id }}"/>
                                    </div>
                                    <div class="col-sm-6 text-right">

                                        <button type="submit" class="btn btn-primary">Search</button>
                                        <a href="/sub-agent/setting/new-user" class="btn btn-default">New User</a>
                                    </div>
                                </div>
                            </form>
                            <table class="table table-bordered table-hover table-condensed">
                                <thead>
                                    <tr>
                                        <th>User ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Created.At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (count($users) > 0)
                                        @foreach ($users as $o)
                                        <tr>
                                            <td><a href="/sub-agent/setting/user/{{$o->user_id}}">{{ $o->user_id }}</a></td>
                                            <td>{{ $o->name }}</td>
                                            <td>{{ $o->email }}</td>
                                            <td>{{ $o->role_name }}</td>
                                            <td>{{ $o->status_name }}</td>
                                            <td>{{ $o->created_at }}</td>
                                        </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="5">No Record Found</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
    <!-- End contain wrapp -->
@stop
