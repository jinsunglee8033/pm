<!DOCTYPE HTML>
<!--[if lt IE 7]> <html class="no-js ie6 oldie" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js ie7 oldie" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js ie8 oldie" lang="en"> <![endif]-->
<html class="no-js" lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="robots" content="index,follow">
    <link rel="icon" href="/ico/favicon.png">

    <title>SoftPayPlus, Inc</title>

    <!-- Bootstrap Core CSS -->
    <link href="/css/bootstrap_shop.min.css" rel="stylesheet">

    <!-- RS5.0 Main Stylesheet -->
    <link rel="stylesheet" type="text/css" href="/css/revolution/settings.css">

    <!-- RS5.0 Layers and Navigation Styles -->
    <link rel="stylesheet" type="text/css" href="/css/revolution/layers.css">
    <link rel="stylesheet" type="text/css" href="/css/revolution/navigation.css">

    <link href="/css/style_shop.css?ver=5" rel="stylesheet">

    <!-- Color -->
    <link id="skin" href="/skins/default.css" rel="stylesheet">

    <!-- Just for debugging purposes. Don't actually copy these 2 lines! -->
    <!--[if lt IE 9]><script src="/js/ie8-responsive-file-warning.js"></script><![endif]-->
    <script src="/js/ie-emulation-modes-warning.js"></script>

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="/js/html5shiv.min.js"></script>
    <script src="/js/respond.min.js"></script>
    <![endif]-->

</head>
<body>

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

    function apply() {
        $('#apply_btn').prop("disabled", true);
        $('#registerform').submit();
    }

</script>

<!-- Start parallax -->
<div class="parallax" data-background="img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
    <div class="overlay white"></div>
    <div class="container">
        <div class="inner-head" style="width:80%; margin-left: 10%;">
            <div class="row">
                <div class="col-md-3 text-center">
{{--                    @if ($account->id == 104978)--}}
{{--                    <img src="/assets/images/ma/alpha-logo.png" style="max-height: 62px;">--}}
{{--                    @endif--}}
                    @if ($account->id == 100596)
                        <img src="/assets/images/ma/MobileWholeseller-logo.jpg" style="max-height: 62px;">
                    @endif
                </div>
                <div class="col-md-6 text-center">
                    <h4>Become a Dealer</h4>
                    <ol class="breadcrumb">
                        <li class="active">{{ $account->id }}</li>
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
                <form method="post" id="registerform" action="/apply-subagent" style="width:80%; margin-left: 10%;">
                    {!! csrf_field() !!}

                    <input type="hidden" name="agent_id" value="{{ $account->id }}">

                    <div class="clearfix"></div>
                    <div id="success"></div>
                    <div class="row wrap-form">
                        <div class="form-group col-md-12 col-sm-12 {{ $errors->has('business_name') ? ' has-error' : '' }}">
                            <h6>Business Name * <span style="color: #0c91e5">Attention! Please enter the correct information below. Missing/incorrect information will cause delay or failure on your orders.</span> </h6>
                            <input type="text" name="business_name" id="business_name" class="form-control input-lg required" value="{{ old('business_name') }}" placeholder="Enter Account Name">
                            @if ($errors->has('business_name'))
                                <span class="help-block">
                                        <strong>{{ $errors->first('business_name') }}</strong>
                                    </span>
                            @endif
                        </div>
                        <div class="form-group col-md-12 col-sm-12 {{ $errors->has('biz_license') ? ' has-error' : '' }}">
                            <h6>Business License # / Business Certificate # *</h6>
                            <input type="text" id="biz_license" name="biz_license" value="{{ old('biz_license') }}" class="form-control input-lg required" placeholder="Enter Business License/Business Certificate">
                            @if ($errors->has('biz_license'))
                                <span class="help-block">
                                        <strong>{{ $errors->first('biz_license') }}</strong>
                                    </span>
                            @endif
                        </div>

                        <div class="form-group col-md-6 col-sm-6 margintop30 {{ $errors->has('first_name') ? ' has-error' : '' }}">
                            <h6>Contact First Name *</h6>
                            <input type="text" name="first_name" id="first_name" class="form-control input-lg required" value="{{ old('first_name') }}" placeholder="Enter First Name">
                            @if ($errors->has('first_name'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('first_name') }}</strong>
                                </span>
                            @endif
                        </div>
                        <div class="form-group col-md-6 col-sm-6 margintop30 {{ $errors->has('last_name') ? ' has-error' : '' }}">
                            <h6>Contact Last Name *</h6>
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
                        <div class="form-group col-md-12 col-sm-12 {{ $errors->has('email') ? ' has-error' : '' }}">
                            <h6>Email * <span style="color:red">Important! Please make sure you put a valid email address! Otherwise, you will not be able to receive important information or retrieve log-in information when necessary.</span></h6>
                            <input type="email" name="email" id="email" class="form-control input-lg required" value="{{ old('email') }}" placeholder="Enter email address">
                            @if ($errors->has('email'))
                                <span class="help-block">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                            @endif
                        </div>


                        <div class="form-group col-md-12 col-sm-12 margintop30 {{ $errors->has('address1') ? ' has-error' : '' }}">
                            <h6>Business Address *</h6>
                            <input type="text" name="address1" id="address1" class="form-control input-lg required" value="{{ old('address1') }}" placeholder="Enter address">
                            @if ($errors->has('address1'))
                                <span class="help-block">
                                        <strong>{{ $errors->first('address1') }}</strong>
                                    </span>
                            @endif
                        </div>
                        <div class="form-group col-md-6 col-sm-6 {{ $errors->has('address2') ? ' has-error' : '' }}">
                            <h6>Suite #</h6>
                            <input type="text" id="address2" name="address2" value="{{ old('address2') }}" class="form-control input-lg required" placeholder="Enter Suite #">
                            @if ($errors->has('address2'))
                                <span class="help-block">
                                        <strong>{{ $errors->first('address2') }}</strong>
                                    </span>
                            @endif
                        </div>

                        <div class="form-group col-md-12 col-sm-12 {{ $errors->has('city') ? ' has-error' : '' }}">
                            <h6>City *</h6>
                            <input type="text" name="city" id="city" class="form-control input-lg required" value="{{ old('city') }}" placeholder="Enter city">
                            @if ($errors->has('city'))
                                <span class="help-block">
                                        <strong>{{ $errors->first('city') }}</strong>
                                    </span>
                            @endif
                        </div>
                        <div class="form-group col-md-6 col-sm-6 {{ $errors->has('state') ? ' has-error' : '' }}">
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
                        <div class="form-group col-md-6 col-sm-6 {{ $errors->has('zip') ? ' has-error' : '' }}">
                            <h6>Zip Code *</h6>
                            <input type="text" id="zip" name="zip" value="{{ old('zip') }}" class="form-control input-lg required" placeholder="Enter zip code" maxlength="5">
                            @if ($errors->has('zip'))
                                <span class="help-block">
                                        <strong>{{ $errors->first('zip') }}</strong>
                                    </span>
                            @endif
                        </div>

                        <div class="form-group col-md-12 col-sm-12 marginbot30 margintop30 {{ $errors->has('store_types') ? ' has-error' : '' }}">
                            <h6>Store Type *</h6> {{old('store_types')}}
                            @foreach ($store_types as $o)
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="store_type[]" value="{{ $o->name }}" {{ (is_array(old('store_type')) && in_array($o->name, old('store_type'))) ? ' checked' : '' }}/> {{ $o->name }}
                                </label>
                            @endforeach

                            @if ($errors->has('store_types'))
                                <span class="help-block">
                                        <strong>{{ $errors->first('store_types') }}</strong>
                                    </span>
                            @endif
                        </div>

                        <div class="form-group col-md-12 col-sm-12 margintop30">
                            <h4>Lead from / Local Sales Person <strong>(Such as independent whole-seller)</strong></h4>
                        </div>

                        <div class="form-group col-md-6 col-sm-6 {{ $errors->has('sales_name') ? ' has-error' : '' }}">
                            <h6>Sales Person Name (If you have one)</h6>
                            <input type="text" name="sales_name" id="sales_name" class="form-control input-lg
                            required" value="{{ $account->id }}" placeholder="Enter sales person's full Name" readonly>
                            @if ($errors->has('sales_name'))
                                <span class="help-block">
                                        <strong>{{ $errors->first('sales_name') }}</strong>
                                    </span>
                            @endif
                        </div>
                        <div class="form-group col-md-6 col-sm-6 {{ $errors->has('sales_phone') ? ' has-error' : '' }}">
                            <h6>Phone</h6>
                            <input type="text" id="sales_phone" name="sales_phone" value="{{ old('sales_phone') }}" class="form-control input-lg required" placeholder="Enter sales person's phone number (10 digit number)" maxlength="10">
                            @if ($errors->has('sales_phone'))
                                <span class="help-block">
                                        <strong>{{ $errors->first('sales_phone') }}</strong>
                                    </span>
                            @endif
                        </div>
                        <div class="form-group col-md-12 col-sm-12 {{ $errors->has('sales_email') ? ' has-error' : '' }}">
                            <h6>Email</h6>
                            <input type="email" name="sales_email" id="sales_email" class="form-control input-lg required" value="{{ old('sales_email') }}" placeholder="Enter sales person's email address">
                            @if ($errors->has('sales_email'))
                                <span class="help-block">
                                        <strong>{{ $errors->first('sales_email') }}</strong>
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

                        {{--<div class="form-group col-md-6 col-sm-6 marginbot40 margintop30 {{ $errors->has('promo_code') ? ' has-error' : '' }}">--}}
                        {{--<h6>Enter promo code (If Applicable)</h6>--}}
                        {{--<input type="text" id="promo_code" name="promo_code" value="{{ old('promo_code') }}" class="form-control input-lg required" placeholder="Enter promo code">--}}
                        {{--@if ($errors->has('promo_code'))--}}
                        {{--<span class="help-block">--}}
                        {{--<strong>{{ $errors->first('promo_code') }}</strong>--}}
                        {{--</span>--}}
                        {{--@endif--}}
                        {{--</div>--}}


                        @if ($account->id !== 104978)
                        <div class="form-group col-md-12 col-sm-12 margintop30">
                            <h4>Select desired account type</h4>
                        </div>


                        <div class="form-group col-md-12 col-sm-12 {{ $errors->has('account_type') ? ' has-error' : '' }}">
                            <input type="radio" name="account_type" value="P" {{ old('account_type') != 'C' ? 'checked' : ''}} />&nbsp; I would like to sign up as a Prepaid Account. <span style="color:red">(Reload fund as you use)</span>
                            <br>&nbsp;&nbsp;&nbsp;&nbsp;* Provide us W-9, Tax ID <span style="color:red">(Forms can be provided after account created)</span>
                            <br>
                            <input type="radio" name="account_type" value="C" {{ old('account_type') == 'C' ? 'checked' : ''}}/>&nbsp; I would like to sign up as a Credit Account. (ACH debit from your bank account)
                            <br>&nbsp;&nbsp;&nbsp;&nbsp;* Provide us W-9, Tax ID, Government ID, and ACH form.(May require Bank Reference)
                            <br>&nbsp;&nbsp;  <span style="color:red">(Forms can be provided after account created)</span>
                            <br>&nbsp;&nbsp;Note, We can convert account type at any time.
                            <br>&nbsp;&nbsp;&nbsp;&nbsp;*Default setup is Prepaid Account.

                            @if ($errors->has('account_type'))
                                <span class="help-block">
                                        <strong>{{ $errors->first('account_type') }}</strong>
                                    </span>
                            @endif
                        </div>
                        @endif

                        <div class="form-group col-md-3 col-sm-3">
                            <canvas id="myCanvas" style="width: 100%; height: 100px;"></canvas>
                        </div>
                        <div class="form-group col-md-3 col-sm-3">
                            <input name="verification_code" type="text" class="form-control input-lg required" placeholder="Please Enter Verification Code" required/>
                        </div>

                        <div class="form-group col-md-12 margintop40 text-center">
{{--                            <input type="submit" value="Apply" id="submit" class="btn btn-primary btn-lg"/>--}}
                            <button type="button" class="btn btn-primary btn-lg" id="apply_btn" onclick="apply()">Apply</button>
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
                        Immediately, you will get an <b>Apply for a Dealer</b> email confirmation<br/>
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

<!-- Start footer -->
<footer class="no-print">

    <div class="subfooter">
        <p>2017 ~ {{ date('Y') }} &copy; Copyright <a href="#">SoftPayPlus.</a> All rights Reserved.</p>
    </div>
</footer>
<!-- End footer -->

<!-- Start to top -->
<a href="#" class="toTop">
    <i class="fa fa-chevron-up"></i>
</a>
<!-- End to top -->

<!-- START JAVASCRIPT -->
<!-- Placed at the end of the document so the pages load faster -->
<script type="text/javascript" src="/js/jquery.min.js"></script>
<script src="/js/bootstrap.min.js"></script>
<script src="/js/jquery.easing-1.3.min.js"></script>

<!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
<script src="/js/ie10-viewport-bug-workaround.js"></script>

<!-- Bootsnavs -->
<script src="/js/bootsnav.js"></script>

<!-- Custom form -->
<script src="/js/form/jcf.js"></script>
<script src="/js/form/jcf.scrollable.js"></script>
<script src="/js/form/jcf.select.js"></script>

<!-- Custom checkbox and radio -->
<script src="/js/checkator/fm.checkator.jquery.js"></script>
<script src="/js/checkator/setting.js"></script>

<!-- REVOLUTION JS FILES -->
<script type="text/javascript" src="/js/revolution/jquery.themepunch.tools.min.js"></script>
<script type="text/javascript" src="/js/revolution/jquery.themepunch.revolution.min.js"></script>

<!-- SLIDER REVOLUTION 5.0 EXTENSIONS
(Load Extensions only on Local File Systems !
The following part can be removed on Server for On Demand Loading) -->
<script type="text/javascript" src="/js/revolution/revolution.extension.actions.min.js"></script>
<script type="text/javascript" src="/js/revolution/revolution.extension.carousel.min.js"></script>
<script type="text/javascript" src="/js/revolution/revolution.extension.kenburn.min.js"></script>
<script type="text/javascript" src="/js/revolution/revolution.extension.layeranimation.min.js"></script>
<script type="text/javascript" src="/js/revolution/revolution.extension.migration.min.js"></script>
<script type="text/javascript" src="/js/revolution/revolution.extension.navigation.min.js"></script>
<script type="text/javascript" src="/js/revolution/revolution.extension.parallax.min.js"></script>
<script type="text/javascript" src="/js/revolution/revolution.extension.slideanims.min.js"></script>
<script type="text/javascript" src="/js/revolution/revolution.extension.video.min.js"></script>

<!-- CUSTOM REVOLUTION JS FILES -->
<script type="text/javascript" src="/js/revolution/setting/clean-revolution-slider.js"></script>

<!-- masonry -->
<script src="/js/masonry/masonry.min.js"></script>
<script src="/js/masonry/masonry.filter.js"></script>
<script src="/js/masonry/setting.js"></script>

<!-- PrettyPhoto -->
<script src="/js/prettyPhoto/jquery.prettyPhoto.js"></script>
<script src="/js/prettyPhoto/setting.js"></script>

<!-- flexslider -->
<script src="/js/flexslider/jquery.flexslider-min.js"></script>
<script src="/js/flexslider/setting.js"></script>

<!-- Parallax -->
<script src="/js/parallax/jquery.parallax-1.1.3.js"></script>
<script src="/js/parallax/setting.js"></script>

<!-- owl carousel -->
<script src="/js/owlcarousel/owl.carousel.min.js"></script>
<script src="/js/owlcarousel/setting.js"></script>

<!-- Twitter -->
<script src="/js/twitter/tweetie.min.js"></script>
<script src="/js/twitter/ticker.js"></script>
<script src="/js/twitter/setting.js"></script>

<!-- Custom -->
<script src="/js/custom.js"></script>

<!-- Theme option-->
<script src="/js/template-option/demosetting.js"></script>
</body>
</html>
