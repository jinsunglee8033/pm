@extends('layout.default')

@section('content')
    <!-- Start parallax -->
    <div class="parallax" data-background="/img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
        <div class="overlay white"></div>
        <div class="container">
            <div class="inner-head">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h4>Action Completed!</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
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
                <div class="col-md-12 col-sm-12" style="text-align: center">
                    <h3>Your Account is activated now.</h3>
                </div>
            </div>
        </div>
        <div class="col-md-offset-4 col-sm-4" style="text-align: center">
            <div class="form-group" style="text-align: center">
                <a href="/login">
                    <button class="btn btn-primary btn-block" >Login now</button>
                </a>
            </div>
        </div>
    </div>

    <!-- End contain wrapp -->
@stop
