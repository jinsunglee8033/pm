@extends('layout.default')

@section('content')
    <style>
        .jcf-select {
            height: 62px !important;
            padding: 15px !important;
        }
    </style>

    <script type="text/javascript">
        window.onload = function() {
            var field = document.querySelector('[name="user_name"]');
            field.addEventListener('keypress', function ( event ) {
                var key = event.keyCode;
                if (key === 32) {
                    event.preventDefault();
                }
            });

            field.addEventListener('mouseout', function () {
                myText = $("#user_name").val();
                var remove_space = myText.replace(/[^a-zA-Z]/g, "");
                $("#user_name").val(remove_space);
            });

            var field = document.querySelector('[name="password"]');
            field.addEventListener('keypress', function ( event ) {
                var key = event.keyCode;
                if (key === 32) {
                    event.preventDefault();
                }
            });
        };
    </script>

    <!-- Start parallax -->
    <div class="parallax" data-background="img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
        <div class="overlay white"></div>
        <div class="container">
            <div class="inner-head">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h4>Become an ISO</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li class="active">Register</li>
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
            <div class="col-md-12 col-sm-12">
            </div>
            <div class="row">
                <div class="col-md-12 col-sm-12">

                    <div class="row">
                        @if ($errors->has('exception'))
                            <div class="alert alert-danger alert-dismissible" role="alert">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"></button>
                                <strong>Error!</strong> {{ $errors->first('exception') }}
                            </div>
                        @endif
                    </div>
                    <!-- Start Form -->
                    <form method="post" id="registerform" action="/apply-masteragent" style="width:80%; margin-left:
                    10%;">

                        <div style="margin-bottom: 32px;">
                            If you are interested in becoming an ISO, please contact us by filling out the form below and we will get in touch with you shortly.
                        </div>
                        <h6><div style="color: #0c91e5">Attention! Please enter the correct information below. Missing/incorrect information will cause delay or failure on your orders.</div></h6>
                        {!! csrf_field() !!}
                        <div class="clearfix"></div>
                        <div id="success"></div>
                        <br class="row wrap-form">

                            <div class="form-group col-md-6 col-sm-6 {{ $errors->has('business_name') ? ' has-error'
                             : '' }}">

                                <h6>Business Name * </h6>
                                <input type="text" name="business_name" id="business_name" class="form-control input-lg required" value="{{ old('business_name') }}" placeholder="Enter Account Name">
                                @if ($errors->has('business_name'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('business_name') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group col-md-6 col-sm-6 {{ $errors->has('biz_license') ? ' has-error' :
                             '' }}">
                                <h6>Business License # / Business Certificate #</h6>
                                <input type="text" id="biz_license" name="biz_license" value="{{ old('biz_license') }}" class="form-control input-lg required" placeholder="Enter Business License/Business Certificate">
                                @if ($errors->has('biz_license'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('biz_license') }}</strong>
                                    </span>
                                @endif
                            </div>

                            <div class="form-group col-md-6 col-sm-6 margintop30 {{ $errors->has('first_name') ? ' has-error' : '' }}">
                                <h6>Contact Name * (First Name)</h6>
                                <input type="text" name="first_name" id="first_name" class="form-control input-lg required" value="{{ old('first_name') }}" placeholder="Enter First Name">
                                @if ($errors->has('first_name'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('first_name') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group col-md-6 col-sm-6 margintop30 {{ $errors->has('last_name') ? ' has-error' : '' }}">
                                <h6>Contact Name * (Last Name)</h6>
                                <input type="text" name="last_name" id="last_name" class="form-control input-lg required" value="{{ old('last_name') }}" placeholder="Enter Last Name">
                                @if ($errors->has('last_name'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('last_name') }}</strong>
                                    </span>
                                @endif
                            </div>

                            <div class="form-group col-md-6 col-sm-6 margintop30 {{ $errors->has('phone') ? ' has-error' : '' }}">
                                <h6>Phone *</h6>
                                <input type="text" id="phone" name="phone" value="{{ old('phone') }}" class="form-control input-lg required" placeholder="Enter phone number (10 digit number)" maxlength="10">
                                @if ($errors->has('phone'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('phone') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group col-md-12 col-sm-12 margintop30 {{ $errors->has('email') ? '
                            has-error' : '' }}">
                                <h6>Email * <span style="color:red">Important! Please make sure you put a valid email address! Otherwise, you will not be able to receive important information or retrieve log-in information when necessary.</span></h6>
                                <input type="email" name="email" id="email" class="form-control input-lg required" value="{{ old('email') }}" placeholder="Enter email address">
                                @if ($errors->has('email'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif
                            </div>

                            <div class="form-group col-md-12 col-sm-12 margintop30 {{ $errors->has('address') ? '
                            has-error' : '' }}">
                                <h6>Address *</h6>
                                <input type="text" name="address" id="address" class="form-control input-lg required"
                                       value="{{ old('address') }}" placeholder="Enter Address">
                                @if ($errors->has('address'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('address') }}</strong>
                                    </span>
                                @endif
                            </div>

                            <div class="form-group col-md-6 col-sm-6 margintop30 {{ $errors->has('city') ? '
                            has-error' : '' }}">
                                <h6>City *</h6>
                                <input type="text" name="city" id="city" class="form-control input-lg required" value="{{ old('city') }}" placeholder="Enter city">
                                @if ($errors->has('city'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('city') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group col-md-6 col-sm-6 margintop30 {{ $errors->has('state') ? ' has-error' : '' }}">
                                <h6>State *</h6>
                                <select name="state" class="form-control" style="height:62px;">
                                    <option value="">All</option>
                                    @foreach ($states as $o)
                                        <option value="{{ $o['code'] }}" {{ $o['code'] == old('state') ? 'selected' : ''}}>{{ $o['name'] }}</option>
                                    @endforeach

                                </select>
                                @if ($errors->has('state'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('state') }}</strong>
                                    </span>
                                @endif
                            </div>

                            <div class="form-group col-md-6 col-sm-6 margintop30 {{ $errors->has('zip') ? '
                            has-error' : '' }}">
                                <h6>Zip *</h6>
                                <input type="text" name="zip" id="zip" class="form-control input-lg required" value="{{ old('zip') }}" placeholder="Enter zip">
                                @if ($errors->has('zip'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('zip') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group col-md-6 col-sm-6 margintop30 {{ $errors->has('retail_location_no') ? '
                            has-error' : '' }}">
                                <h6>Number of Your Distribution Retail Locations *</h6>
                                <input type="text" id="retail_location_no" name="retail_location_no" value="{{ old
                                ('retail_location_no') }}" class="form-control input-lg required" placeholder="Enter
                                number of retail locations" maxlength="5">
                                @if ($errors->has('retail_location_no'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('retail_location_no') }}</strong>
                                    </span>
                                @endif
                            </div>

                            <div class="form-group col-md-12 col-sm-12 margintop30">
                                <h4>Account Information</h4>
                            </div>

                            <div class="form-group col-md-6 col-sm-6 {{ $errors->has('user_name') ? ' has-error' : '' }}">
                                <h6>Desire User ID</h6>
                                <input type="text" id="user_name" name="user_name" value="{{ old('user_name') }}" class="form-control input-lg required" placeholder="Desire User Name *">
                                @if ($errors->has('user_name'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('user_name') }}</strong>
                                    </span>
                                @endif
                            </div>

                            <div class="form-group col-md-6 col-sm-6 {{ $errors->has('password') ? ' has-error' : '' }}">
                                <h6>Desire Password</h6>
                                <input type="password" id="password" name="password" value="{{ old('password') }}" class="form-control input-lg required" placeholder="Desire Password">
                                @if ($errors->has('password'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                            </div>

                            <div class="form-group col-md-3 col-sm-3 margintop40">
                                <canvas id="myCanvas" style="width: 100%; height: 100px;"></canvas>
                            </div>
                            <div class="form-group col-md-3 col-sm-3 margintop40">
                                <input name="verification_code" type="text" class="form-control input-lg required" placeholder="Please Enter Verification Code" required/>
                            </div>

                            <div class="form-group col-md-12 margintop40 text-center">
                                <input type="submit" value="Apply" id="submit" class="btn btn-primary btn-lg"/>
                                <div class="status-progress"></div>
                            </div>
                        </div>


                    </form>
                    <!-- End Form -->
                </div>

            </div>

        </div>
    </div>
    <!-- End contain wrapp -->
    <script>
        var canvas = document.getElementById('myCanvas');
        var context = canvas.getContext('2d');

        context.fillStyle = "#F5DEB3";

        var w = context.width;
        var h = context.height;

        context.fillRect(0, 0, 320, 93);

        context.font = "15px Georgia";
        context.fillStyle = "blue";
        context.fillText('Verification Code', 20, 25);
        context.font = "40px Georgia";
        context.fillRect(0, 40, 320, 3);
        context.fillRect(0, 60, 320, 3);
        context.fillRect(0, 80, 320, 3);
        context.fill();
        context.fillText('{{ $verification_code }}', 20, 75);

    </script>

    @if (session()->has('success') && session('success') == 'Y')
        <script>
            var onload_events = window.onload;
            window.onload = function () {
                if (onload_events) {
                    onload_events();
                }
                $('#register-success').modal();
            }
        </script>
        <div id="register-success" class="modal fade " tabindex="-1" role="dialog"
             style="display:block;">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Thank You</h4>
                    </div>
                    <div class="modal-body">
                        <h4 style="font-size: large;">
                            Your request is being processed.<br/>
                            Immediately, you will get an <b>Apply for an ISO</b> email confirmation<br/>
                            Please check your email and follow the instructions to activate your account now.<br/>
                        </h4>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

@stop
