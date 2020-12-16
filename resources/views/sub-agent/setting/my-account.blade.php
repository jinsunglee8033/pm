@extends('sub-agent.layout.default')

@section('content')
    <!-- Start parallax -->
    <div class="parallax" data-background="/img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
        <div class="overlay white"></div>
        <div class="container">
            <div class="inner-head">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h4>My Account</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li><a href="#">Setting</a></li>
                            <li class="active">My Account</li>
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
                            <li class="active">
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
                            <form id="frm_act" method="post" action="/sub-agent/setting/my-account" class="row marginbot15">
                                {!! csrf_field() !!}
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group{{ $errors->has('id') ? ' has-error' : '' }}">
                                            <label class="required">Account ID</label>
                                            <input type="text" class="form-control" name="id"
                                                   value="{{ old('id', $account->id) }}" readonly/>
                                            @if ($errors->has('id'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('id') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                        <div class="form-group{{ $errors->has('tax_id') ? ' has-error' : '' }}">
                                            <label class="required">Tax ID</label>
                                            <input type="text" class="form-control" name="tax_id"
                                                   value="{{ old('tax_id', $account->tax_id) }}"/>
                                            @if ($errors->has('tax_id'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('tax_id') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                        <div class="form-group{{ $errors->has('office_number') ? ' has-error' : '' }}">
                                            <label class="required">Office Number</label>
                                            <input type="text" class="form-control" name="office_number"
                                                   value="{{ old('office_number', $account->office_number) }}"/>
                                            @if ($errors->has('office_number'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('office_number') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
                                            <label class="required">Account Name</label>
                                            <input type="text" class="form-control" name="name"
                                                   value="{{ old('name', $account->name) }}"/>
                                            @if ($errors->has('name'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('name') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                        <div class="form-group{{ $errors->has('contact') ? ' has-error' : '' }}">
                                            <label class="required">Contact</label>
                                            <input type="text" class="form-control" name="contact"
                                                   value="{{ old('contact', $account->contact) }}"/>
                                            @if ($errors->has('contact'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('contact') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                        <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                                            <label class="required">Email</label>
                                            <input type="email" class="form-control" name="email"
                                                   value="{{ old('email', $account->email) }}"/>
                                            @if ($errors->has('email'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('email') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                </div>

                                <div class="divider2"></div>

                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group{{ $errors->has('address1') ? ' has-error' : '' }}">
                                            <label class="required">Address 1</label>
                                            <input type="text" class="form-control" name="address1"
                                                   value="{{ old('address1', $account->address1) }}"/>
                                            @if ($errors->has('address1'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('address1') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                        <div class="form-group{{ $errors->has('city') ? ' has-error' : '' }}">
                                            <label class="required">City</label>
                                            <input type="text" class="form-control" name="city"
                                                   value="{{ old('city', $account->city) }}"/>
                                            @if ($errors->has('city'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('city') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                        <div class="form-group{{ $errors->has('zip') ? ' has-error' : '' }}">
                                            <label class="required">Zip</label>
                                            <input type="text" class="form-control" name="zip"
                                                   value="{{ old('zip', $account->zip) }}"/>
                                            @if ($errors->has('zip'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('zip') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="form-group{{ $errors->has('address2') ? ' has-error' : '' }}">
                                            <label class="required">Address 2</label>
                                            <input type="text" class="form-control" name="address2"
                                                   value="{{ old('address2', $account->address2) }}"/>
                                            @if ($errors->has('address2'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('address2') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                        <div class="form-group{{ $errors->has('state') ? ' has-error' : '' }}">
                                            <label class="required">State</label>
                                            <select class="form-control" name="state">
                                                <option value="">Please Select</option>
                                                @foreach ($states as $o)
                                                    <option value="{{ $o->code }}" {{ $o->code == old('state', $account->state) ? 'selected' : '' }}>{{ $o->name }}</option>
                                                @endforeach
                                            </select>
                                            @if ($errors->has('state'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('state') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                </div>

                                <div class="divider2"></div>


                                <div class="col-md-12 col-sm-12 marginbot20">

                                    <button type="submit" class="btn btn-primary">Update My Account</button>
                                </div>

                                <div class="col-md-12 col-sm-12 marginbot20">
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
