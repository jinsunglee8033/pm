@extends('sub-agent.layout.default')

@section('content')
    <!-- Start parallax -->
    <div class="parallax" data-background="/img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
        <div class="overlay white"></div>
        <div class="container">
            <div class="inner-head">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h4>New User</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li><a href="#">Setting</a></li>
                            <li><a href="/sub-agent/setting/users">Users</a></li>
                            <li class="active">New User</li>
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
                            <form id="frm_act" class="form-horizontal" method="post" class="row marginbot15">
                                {!! csrf_field() !!}
                                <div class="form-group">
                                    <label class="col-sm-2 control-label">User ID</label>
                                    <div class="col-sm-4 {{ $errors->has('user_id') ? ' has-error' : '' }}">
                                        <input type="text" class="form-control" name="user_id" value="{{ old('user_id', isset($user) ? $user->user_id : '') }}"/>
                                        @if ($errors->has('user_id'))
                                            <span class="help-block">
                                                    <strong>{{ $errors->first('user_id') }}</strong>
                                                </span>
                                        @endif
                                    </div>
                                    <label class="col-sm-2 control-label">User Name</label>
                                    <div class="col-sm-4 {{ $errors->has('name') ? ' has-error' : '' }}">
                                        <input type="text" class="form-control" name="name" value="{{ old('name', isset($user) ? $user->name : '') }}"/>
                                        @if ($errors->has('name'))
                                            <span class="help-block">
                                                    <strong>{{ $errors->first('name') }}</strong>
                                                </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label">Password</label>
                                    <div class="col-sm-4 {{ $errors->has('password') ? ' has-error' : '' }}">
                                        <input type="password" class="form-control" name="password" value="{{ old('password') }}"/>
                                        @if ($errors->has('password'))
                                            <span class="help-block">
                                                    <strong>{{ $errors->first('password') }}</strong>
                                                </span>
                                        @endif
                                    </div>
                                    <label class="col-sm-2 control-label">Confirm</label>
                                    <div class="col-sm-4">
                                        <input type="password" class="form-control" name="password_confirmation" value="{{ old('password_confirmation') }}"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label">Email</label>
                                    <div class="col-sm-4 {{ $errors->has('email') ? ' has-error' : '' }}">
                                        <input type="email" class="form-control" name="email" value="{{ old('email', isset($user) ? $user->email : '') }}"/>
                                        @if ($errors->has('email'))
                                            <span class="help-block">
                                                    <strong>{{ $errors->first('email') }}</strong>
                                                </span>
                                        @endif
                                    </div>
                                    <label class="col-sm-2 control-label">Role</label>
                                    <div class="col-sm-4 {{ $errors->has('role') ? ' has-error' : '' }}">
                                        <select class="form-control" name="role">
                                            <option value="M" {{ old('role', isset($user) ? $user->role : '') == 'M' ? 'selected' : '' }}>Manager</option>
                                            <option value="S" {{ old('role', isset($user) ? $user->role : '') == 'S' ? 'selected' : ''}}>Staff</option>
                                        </select>
                                        @if ($errors->has('role'))
                                            <span class="help-block">
                                                    <strong>{{ $errors->first('role') }}</strong>
                                                </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label">Status</label>
                                    <div class="col-sm-4 {{ $errors->has('status') ? ' has-error' : '' }}">
                                        <select class="form-control" name="status">
                                            <option value="A" {{ old('status', isset($user) ? $user->status : '') == 'A' ? 'selected' : '' }}>Active</option>
                                            <option value="H" {{ old('status', isset($user) ? $user->status : '') == 'H' ? 'selected' : '' }}>On Hold</option>
                                            <option value="C" {{ old('status', isset($user) ? $user->status : '') == 'C' ? 'selected' : '' }}>Closed</option>
                                        </select>
                                        @if ($errors->has('status'))
                                            <span class="help-block">
                                                    <strong>{{ $errors->first('status') }}</strong>
                                                </span>
                                        @endif
                                    </div>
                                    <div class="col-sm-4 col-sm-offset-2">
                                        <a href="/sub-agent/setting/users" class="btn btn-default">Back</a>
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </div>
                                </div>
                                <div class="form-group">
                                    @if (session()->has('success'))
                                        <div class="alert alert-success alert-dismissible" role="alert">
                                            <strong>Success!</strong> {{ session('success') }}
                                        </div>
                                    @endif
                                    @if ($errors->has('exception'))
                                        <div class="alert alert-danger" role="alert">
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
