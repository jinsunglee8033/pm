@extends('layout.default')

@section('content')
    <style>
        .jcf-select {
            height: 62px !important;
            padding: 15px !important;
        }
    </style>

    <!-- Start parallax -->
    <div class="parallax" data-background="img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
        <div class="overlay white"></div>
        <div class="container">
            <div class="inner-head">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h4>Contact Us</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li class="active">Contact us</li>
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
                <div class="col-md-8">
                    <div class="title-head">
                        <h4>Get in touch with us</h4>
                        <p>Please feel free to contact us</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 col-sm-12">
                    <!-- Start Form -->
                    <form method="post" id="mycontactform" action="/contact-us">
                        {!! csrf_field() !!}
                        <div class="clearfix"></div>
                        <div id="success"></div>
                        <div class="row wrap-form">
                            <div class="form-group col-md-6 col-sm-6 {{ $errors->has('business_name') ? ' has-error' : '' }}">
                                <h6>Business Name</h6>
                                <input type="text" name="business_name" id="business_name" class="form-control input-lg required" value="{{ old('business_name') }}" placeholder="Enter your Business Name...">
                                @if ($errors->has('business_name'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('business_name') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group col-md-6 col-sm-6 {{ $errors->has('state_in') ? ' has-error' : '' }}">
                                <h6>State In</h6>
                                <select name="state_in" id="state_in" class="form-control" style="height:62px;">
                                    <option name="">Select state ... </option>
                                    @foreach ($states as $s)
                                    <option value="{{ $s->name }}" {{ old('state_in') == $s->name ? 'selected' : '' }}>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('state_in'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('state_in') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                        
                        
                        <div class="row wrap-form">
                            <div class="form-group col-md-6 col-sm-6 {{ $errors->has('name') ? ' has-error' : '' }}">
                                <h6>Full Name</h6>
                                <input type="text" name="name" id="name" class="form-control input-lg required" value="{{ old('name') }}" placeholder="Enter your full Name...">
                                @if ($errors->has('name'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('name') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group col-md-6 col-sm-6 {{ $errors->has('email') ? ' has-error' : '' }}">
                                <h6>Email Address</h6>
                                <input type="email" name="email" id="email" class="form-control input-lg required" value="{{ old('email') }}" placeholder="Enter your email address...">
                                @if ($errors->has('email'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group col-md-12 {{ $errors->has('subject') ? ' has-error' : '' }}">
                                <h6>Subject</h6>
                                <input type="text" id="subject" name="subject" value="{{ old('subject') }}" class="form-control input-lg required" placeholder="Write your subject">
                                @if ($errors->has('subject'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('subject') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group col-md-12 {{ $errors->has('message') ? ' has-error' : '' }}">
                                <h6>Your Message</h6>
                                <textarea name="message" id="message" class="form-control input-lg required" placeholder="Write something for us..." rows="9">{{ old('message') }}</textarea>
                                @if ($errors->has('message'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('message') }}</strong>
                                    </span>
                                @endif
                            </div>

                            <div class="form-group col-md-3 col-sm-3 margintop20">
                                <canvas id="myCanvas" style="width: 100%; height: 100px;"></canvas>
                            </div>
                            <div class="form-group col-md-3 col-sm-3 margintop20">
                                <input name="verification_code" type="text" class="form-control input-lg required" placeholder="Please Enter Verification Code" required/>
                            </div>

                            <div class="form-group col-md-12 margintop5">
                                <input type="submit" value="Send Message" id="submit" class="btn btn-primary btn-lg"/>
                                <div class="status-progress"></div>
                            </div>
                        </div>

                        <div class="row">
                            @if ($errors->has('exception'))
                                <div class="alert alert-danger alert-dismissible" role="alert">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span></button>
                                    <strong>Error!</strong> {{ $errors->first('exception') }}
                                </div>
                            @endif
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
                $('#contact-us-success').modal();
            }
        </script>
        <div id="contact-us-success" class="modal fade " tabindex="-1" role="dialog"
             style="display:block;">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Thank You</h4>
                    </div>
                    <div class="modal-body">
                        <p>
                            Your request is being processed.<br/>
                            One of our agents will contact you shortly.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

@stop
