@extends('sub-agent.layout.default')

@section('content')
    <!-- Start parallax -->
    <div class="parallax" data-background="/img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
        <div class="overlay white"></div>
        <div class="container">
            <div class="inner-head">
                <div class="row">
                    <div class="col-md-12 text-center">
                        @if (empty($title))
                            <h4>Pending Approval from Carrier</h4>
                            <h6>Please check back later...</h6>
                            <ol class="breadcrumb">
                                <li><a href="#">Home</a></li>
                                <li class="active">Pending Approval from Carrier</li>
                            </ol>
                        @else
                            <h4>{{ $title }}</h4>
                        @endif
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
                    <p>
                        Your account is not authorized to do ATT Mobile activation. Please contact your distributor , <b style="color: red">or Email: ops@softpayplus.com</b>
                    </b>
                </div>
            </div>
        </div>
    </div>
    <!-- End contain wrapp -->
@stop
