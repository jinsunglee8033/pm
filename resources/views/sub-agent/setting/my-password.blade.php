@extends('sub-agent.layout.default')

@section('content')
    <!-- Start parallax -->
    <div class="parallax" data-background="/img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
        <div class="overlay white"></div>
        <div class="container">
            <div class="inner-head">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h4>My Password</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li><a href="#">Setting</a></li>
                            <li class="active">My Password</li>
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
                            <li class="active">
                                <a href="/sub-agent/setting/my-password">My Password</a>
                            </li>
                            <li>
                                <a href="/sub-agent/setting/my-account">My Account</a>
                            </li>
                            <li>
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
                            <form id="frm_act" method="post" action="/sub-agent/setting/my-password" class="row marginbot15">
                                {!! csrf_field() !!}
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group{{ $errors->has('current_password') ? ' has-error' : '' }}">
                                            <label class="required">Current Password</label>
                                            <input type="password" class="form-control" name="current_password"
                                                   value="{{ old('current_password') }}"
                                                   placeholder="Please enter your current password"/>
                                            @if ($errors->has('current_password'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('current_password') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                        <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                                            <label class="required">New Password</label>
                                            <input type="password" class="form-control" name="password"
                                                   value="{{ old('password') }}"
                                                   placeholder="Please enter new password"/>
                                            @if ($errors->has('password'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('password') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                        <div class="form-group{{ $errors->has('password_confirmation') ? ' has-error' : '' }}">
                                            <label class="required">Confirm New Password</label>
                                            <input type="password" class="form-control" name="password_confirmation"
                                                   value="{{ old('password_confirmation') }}"
                                                   placeholder="Please enter confirm new password"/>
                                            @if ($errors->has('password_confirmation'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('password_confirmation') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                </div>

                                <div class="divider2"></div>


                                <div class="col-md-12 col-sm-12 marginbot20">

                                    <button type="submit" class="btn btn-primary">Update My Password</button>
                                </div>

                                <div class="col-md-12 col-sm-12 marginbot20">
                                    @if (session()->has('success'))
                                        <div class="alert alert-success alert-dismissible" role="alert">
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                <span aria-hidden="true">&times;</span></button>
                                            <strong>Success!</strong> {{ session('success') }}
                                        </div>
                                    @endif
                                    @if ($errors->has('exception'))
                                        <div class="alert alert-danger alert-dismissible" role="alert">
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                <span aria-hidden="true">&times;</span></button>
                                            <strong>Error!</strong> {{ $errors->first('exception') }}
                                        </div>
                                    @endif
                                </div>

                            </form>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
    <!-- End contain wrapp -->
@stop
