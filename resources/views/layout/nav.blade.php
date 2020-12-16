<!-- Start top -->
<div class="top-wrapp no-print">
    <div class="container">
        <div class="row">
            <div class="col-md-12" style="">
                <ul class="top-link">
                    <li><a href="/login"><i class="fa fa-lock"></i> login</a></li>
                    <!--li><a href="/register" class="signup"><i class="fa fa-sign-in"></i> Register</a></li-->
                </ul>
            </div>
        </div>
    </div>
</div>
<!-- End top -->

<div class="clearfix"></div>

<!-- Start Navigation -->
<nav class="navbar navbar-default navbar-sticky navbar-default bootsnav no-print">

    <div class="container">
        <!-- Start Atribute Navigation -->
        <!--div class="attr-nav">
            <ul>

                <li class="search"><a href="#"><i class="fa fa-search"></i></a></li>
            </ul>
        </div-->
        <!-- End Atribute Navigation -->

        <!-- Start Header Navigation -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar-menu">
                <i class="fa fa-bars"></i>
            </button>
            <a class="navbar-brand" href="/"><img src="/img/brand/logo-black.png" class="logo" alt=""></a>
        </div>
        <!-- End Header Navigation -->

        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" id="navbar-menu">
            <ul class="nav navbar-nav navbar-right" data-in="fadeInDown" data-out="fadeOutUp">
                <li class="{{ Request::is("/") ? "active" : "" }}"><a href="/">Home</a></li>
                <li class="{{ Request::is("why-perfect-mobile") ? "active" : "" }}"><a href="/why-perfect-mobile">Why SoftpayPlus?</a></li>
                <li class="{{ Request::is("contact-us") ? "active" : "" }}"><a href="/contact-us">Contact Us</a></li>
                <li class="{{ Request::is("apply-subagent") ? "active" : "" }}"><a href="/apply-subagent">Become a Dealer</a></li>
                <li class="{{ Request::is("apply-masteragent") ? "active" : "" }}"><a
                            href="/apply-masteragent">Become an ISO</a></li>
                <li class="dropdown{{ (Request::is("att/*") || Request::is("freeup/*")) ? " active" : "" }}">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button"
                       aria-haspopup="true" aria-expanded="false">eCommerce</a>
                    <ul class="dropdown-menu">
                        <li><a href="/att/activate">AT&T USA</a></li>
                        <li><a href="/freeup/activate">FreeUP</a></li>
                        <li><a href="/gen/activate">Gen (SPR)</a></li>
                        <li><a href="/gen/activate-tmo">Gen (TMO)</a></li>
                        <li><a href="/rokit/pin">ROKiT</a></li>
                        <li><a href="/boom/activate">Boom</a></li>
                    </ul>
                </li>
            </ul>
        </div><!-- /.navbar-collapse -->
    </div>
</nav>
<!-- End Navigation -->