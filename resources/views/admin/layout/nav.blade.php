<!-- Start top -->
@php
    $account = \App\Model\Account::find(Auth::user()->account_id);
@endphp
<div class="top-wrapp">
    <div class="container" style="width:100% !important;">
        <div class="row">
            <div class="col-md-12">
                <ul class="top-link">
                    @if (Auth::user()->account_type == 'L')
                    <li>
                    <script type="text/javascript">
                        (function(d, src, c) { var t=d.scripts[d.scripts.length - 1],s=d.createElement('script');s.id='la_x2s6df8d';s.async=true;s.src=src;s.onload=s.onreadystatechange=function(){var rs=this.readyState;if(rs&&(rs!='complete')&&(rs!='loaded')){return;}c(this);};t.parentElement.insertBefore(s,t.nextSibling);})(document,
                            'https://support.boom.us/scripts/track.js',
                            function(e){ LiveAgent.createButton('uiu6wndo', e); });
                    </script>
                    </li>
                    @endif
                    <li><a href="#">{!! Helper::get_hierarchy_img(Auth::user()->account_type) !!} {{ Auth::user()->account_name . ' (' . Auth::user()->account_id . ')' }}</a></li>
                    @if (Auth::user()->account_type != 'L')
                    <li>
                        <a href="/admin/reports/billing"><b>Last Week Billing</b>: ${{ number_format(Helper::get_last_week_billing(), 2) }}</a>
                    </li>
                    @php
                        $cb = Helper::get_consignment_balance();
                        $rb = Helper::get_consignment_vendor_balance();
                    @endphp
                    @if ($cb != 0)
                    <li>
                        <a href="/admin/reports/consignment/balance"><b>Consignment Balance</b>: ${{ number_format($cb, 2) }}</a>
                    </li>
                    @endif
                    @if ($rb != 0)
                    <li>
                        <a href="/admin/reports/consignment-vendor/balance"><b>R.Consignment Balance</b>: ${{ number_format($rb, 2) }}</a>
                    </li>
                    @endif
                    @endif
                    <li><a href="#"><i class="fa fa-user"></i> Welcome {{ Auth::user()->user_id }}!</a></li>
                    <li><a href="/logout"><i class="fa fa-clock-o"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>
<!-- End top -->

<div class="clearfix"></div>

<!-- Start Navigation -->
<nav class="navbar navbar-default navbar-sticky navbar-default bootsnav">
    <!-- Start Top Search -->
    <div class="top-search">
        <div class="container">
            <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-search"></i></span>
                <input type="text" class="form-control" placeholder="Search">
                <span class="input-group-addon close-search"><i class="fa fa-times"></i></span>
            </div>
        </div>
    </div>
    <!-- End Top Search -->

    <div class="container" style="width:100% !important;">
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
            <a class="navbar-brand" href="#brand"><img src="/img/brand/logo-black3.png" class="logo" alt=""></a>
        </div>
        <!-- End Header Navigation -->

        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" id="navbar-menu">
            <ul class="nav navbar-nav navbar-right" data-in="fadeInDown" data-out="fadeOutUp">
                <li class="{{ Request::is("admin") ? "active" : "" }}"><a href="/admin">Dashboard</a></li>

{{--                <li class="{{ Request::is("admin/news") ? "active" : "" }}"><a href="/admin/news">News</a></li>--}}

                <li class="dropdown{{ (Request::is("admin/news")
                || Request::is("admin/digital")
                || Request::is("admin/advertise")
                || Request::is("admin/task")
                || Request::is("admin/follow") ) ? " active" : "" }}">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button"
                       aria-haspopup="true" aria-expanded="false">News</a>
                    <ul class="dropdown-menu">
                        <li><a href="/admin/news">News</a></li>
                        <li><a href="/admin/digital">Digital e-Marketing</a></li>
                        <li><a href="/admin/advertise">Advertises</a></li>
                        <li><a href="/admin/task">Tasks</a></li>
                        <li><a href="/admin/follow">Follow-Ups</a></li>
                        <li><a href="/admin/document">Documents</a></li>
                        <li><a href="/admin/communication">Communications</a></li>
                    </ul>
                </li>

{{--                <li class="{{ Request::is("admin/account") ? "active" : "" }}"><a href="/admin/account">Account - </a></li>--}}

                <li class="dropdown{{ Request::is("admin/account") ? " active" : "" }}">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                       aria-expanded="false">Account</a>
                    <ul class="dropdown-menu">
                        <li><a href="/admin/account">Account List</a></li>
                        @if ( Auth::user()->role == 'M')
                            <li><a href="/admin/account/store">Store Hours</a></li>
                        @endif
                        @if (in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']) )
                            <li><a href="/admin/account/map">Store Map</a></li>
                        @endif
                    </ul>
                </li>


                <li class="{{ Request::is("admin/reports/transaction") ? "active" : "" }}"><a href="/admin/reports/transaction">Transaction Report</a></li>
                @if (Auth::check() && Auth::user()->account_type == 'L')
                <li class="{{ Request::is("admin/reports/rtr-q") ? "active" : "" }}"><a href="/admin/reports/rtr-q">RTR Queue Report</a></li>
                @endif
                @if (Auth::check() && Auth::user()->account_type != 'L')
                <li class="{{ Request::is("admin/reports/payments") ? "active" : "" }}"><a href="/admin/reports/payments">Payments Report</a></li>
                @endif

                @if (Auth::check() && Auth::user()->account_type != 'L')
                    <li class="dropdown{{ Request::is("admin/virtual-rep/*") ? " active" : "" }}">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                           aria-expanded="false">Market Place</a>
                        <ul class="dropdown-menu">
                            <li><a href="/admin/virtual-rep/shop">Order </a></li>
                            <li><a href="/admin/virtual-rep/cart">View Cart / Proceed to Checkout</a></li>
                            <li><a href="/admin/reports/vr-request-for-master">Track My order</a></li>
                            <li><a href="/admin/virtual-rep/general_request">General Request</a></li>
                        </ul>
                    </li>
                @endif

                @if (Auth::check() && Auth::user()->account_type == 'L')
                <li class="dropdown {{ Request::is("admin/reports/vr-request") || Request::is("admin/reports/vr-sales") ? "active" : "" }}">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Virtual Rep.</a>
                    <ul class="dropdown-menu">
                        <li class="{{ Request::is("admin/reports/vr-request") ? "active" : "" }}"><a href="/admin/reports/vr-request">Virtual Rep. Orders</a>
                        </li>
                        <li class="{{ Request::is("admin/reports/vr-sales") ? "active" : "" }}"><a href="/admin/reports/vr-sales">Virtual Rep. Sales</a>
                        </li>
                        <li class="{{ Request::is("admin/reports/vr-cart") ? "active" : "" }}"><a href="/admin/reports/vr-cart">Virtual Rep. Cart</a>
                        </li>
                    </ul>
                </li>
                @endif

                <li class="dropdown {{ Request::is("admin/reports/*") && !Request::is("admin/reports/transaction") && !Request::is("admin/reports/rtr-q") && !Request::is("admin/reports/vr-request") && !Request::is("admin/reports/vr-sales") ? "active" : "" }}">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Reports</a>
                    <ul class="dropdown-menu">
                        @if (Auth::check() && Auth::user()->account_type == 'L')
                        <li class="dropdown {{ Request::is("admin/reports/verizon/*") ? "active" : "" }}">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Verizon</a>
                            <ul class="dropdown-menu">
                                <li><a href="/admin/reports/verizon/activation">Activation</a></li>
                                <li><a href="/admin/reports/verizon/chargeback">Chargeback</a></li>
                            </ul>
                        </li>
{{--                        <li><a href="/admin/reports/document">Document Report</a></li>--}}
                        <li class="dropdown {{ Request::is("admin/reports/document/*") ? "active" : "" }}">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Document Report</a>
                            <ul class="dropdown-menu">
                                <li><a href="/admin/reports/document">Document Report</a></li>
                                <li><a href="/admin/reports/document-att">ATT Document</a></li>
                                <li><a href="/admin/reports/document-h2o">H2O Document</a></li>
                            </ul>
                        </li>
                        @if (in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']) || getenv('APP_ENV') == 'local')
                        <li class="dropdown {{ Request::is("admin/reports/vendor/*") ? "active" : "" }}">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Vendor</a>
                            <ul class="dropdown-menu">
                                <li><a href="/admin/reports/vendor/commission">Compensation</a></li>
                                <li><a href="/admin/reports/vendor/bonus">Bonus</a></li>
                                <li class="dropdown {{ Request::is("admin/reports/vendor/reup") ? "active" : "" }}">
                                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">ReUP</a>
                                    <ul class="dropdown-menu">
                                        <li><a href="/admin/reports/vendor/reup/commission">Commission</a></li>
                                        <li><a href="/admin/reports/vendor/reup/charge-back">Charge Back</a></li>
                                        <li><a href="/admin/reports/vendor/reup/rebate">Rebate</a></li>
                                    </ul>
                                </li>
                                <li class="dropdown {{ Request::is("admin/reports/vendor/reup") ? "active" : "" }}">
                                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Boom</a>
                                    <ul class="dropdown-menu">
                                        <li><a href="/admin/reports/vendor/boom/commission">Import Raw Data</a></li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                        <li><a href="/admin/reports/payments/root">Payment Report</a></li>
                        @endif
                        @endif
                        <li><a href="/admin/reports/billing">Billing Report</a></li>
                        <li><a href="/admin/reports/spiff">Paid Spiff Report</a></li>
                        <li><a href="/admin/reports/rebate">Rebate Report</a></li>
                        <li><a href="/admin/reports/credit">Credit Report</a></li>
                        <li><a href="/admin/reports/activity">Activity Report</a></li>
                        <li class="dropdown {{ Request::is("admin/reports/consignment/*") ? "active" : "" }}">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Consignment</a>
                            <ul class="dropdown-menu">
                                @if (!in_array(Auth::user()->account_type, ['M', 'D']))
                                <li><a href="/admin/reports/consignment/charge">Charge</a></li>
                                @endif
                                @if (Auth::user()->account_type != 'L')
                                <li><a href="/admin/reports/consignment/balance">Balance</a></li>
                                @endif
                            </ul>
                        </li>
                        <li><a href="/admin/reports/promotion">Promotion Report</a></li>

                        <li><a href="/admin/reports/ach-bounce">ACH Bounce Report</a></li>
                        @if ((Auth::user()->account_type == 'L' && in_array(Auth::user()->user_id, ['thomas', 'admin', 'system'])) )
                        <li class="dropdown {{ Request::is("admin/reports/monitor/*") ? "active" : "" }}">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button"
                               aria-haspopup="true" aria-expanded="false">Monitor</a>
                            <ul class="dropdown-menu">
                                <li><a href="/admin/reports/monitor/plansim">Plan Change & SIM Swap</a></li>
                                <li><a href="/admin/reports/monitor/recharge">Activation Recharge</a></li>
                                <li><a href="/admin/reports/monitor/esn-swap-history">ESN/MDN Swap History (Gen)</a></li>
                                <li><a href="/admin/reports/monitor/boom-sim-swap">SIM Swap History (Boom)</a></li>
                            </ul>
                        </li>
                        @endif

                        @if ($account->show_discount_setup_report == 'Y')
                            <li><a href="/admin/reports/discount-setup-report">Discount setup Report</a></li>
                        @endif
                        @if ($account->show_spiff_setup_report == 'Y')
                            <li><a href="/admin/reports/spiff-setup-report">Spiff setup Report</a></li>
                        @endif
                        @if (Auth::check() && Auth::user()->account_type == 'L')
                            <li><a href="/admin/reports/login-history">LogIn History</a></li>
                        @endif
                    </ul>
                </li>
                @if (Auth::check() && Auth::user()->account_type == 'L')
                <li class="dropdown {{ Request::is("admin/settings/*") ? "active" : "" }}">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Settings</a>
                    <ul class="dropdown-menu">
                        @if (in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']) )
                        <li><a href="/admin/settings/news">News</a></li>
                        <li><a href="/admin/settings/fee">Fee Management</a></li>
                        @endif
                        <li class="{{ Request::is("admin/settings/activation-limit") ? "active" : "" }}">
                            <a href="/admin/settings/activation-limit">Activation Limit</a>
                        </li>
                        <li class="{{ Request::is("admin/settings/*-sims") ? "active" : "" }}">
                            <a href="/admin/settings/sim">SIM Upload</a>
                        </li>

                        <li class="dropdown {{ Request::is("admin/settings/esn/*") ? "active" : "" }}">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button"
                               aria-haspopup="true" aria-expanded="false">ESN Upload</a>
                            <ul class="dropdown-menu">
                                <li><a href="/admin/settings/esn">ESN Upload</a></li>
                                <li><a href="/admin/settings/esn/pm">Inventory ESN</a></li>
                                <!-- <li><a href="/admin/settings/esn/h2o">H2O ESN</a></li> -->
                            </ul>
                        </li>

                        <li class="{{ Request::is("admin/settings/pin/*") ? "active" : "" }}">
                            <a href="/admin/settings/pin">PIN Upload</a>
                        </li>

                        <li class="dropdown {{ Request::is("admin/settings/mapping/*") ? "active" : "" }}">
                            <a href="/admin/settings/mapping">SIM ESN Mapping</a>
                        </li>

                        @if(Auth::user()->account_type == 'L' && (in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']) || getenv('APP_ENV') == 'local'))
{{--                            <li><a href="/admin/settings/spiff-setup">Spiff & Rebate Setup</a></li>--}}
                            <li><a href="/admin/settings/spiff-setup2">Spiff & Rebate Setup</a></li>
                            <li><a href="/admin/settings/account-spiff-setup">Account Spiff Setup</a></li>
                            <li><a href="/admin/settings/spiff-setup/special">Special Spiff Setup</a></li>
                            <li><a href="/admin/settings/product-setup">Product Setup</a></li>
                            <li><a href="/admin/settings/permission">Permission Setup</a></li>
                            <li><a href="/admin/settings/vr-upload">VR Product Upload</a></li>
                            <li><a href="/admin/settings/vr-upload2">Admin Upload</a></li>
                            <li><a href="/admin/settings/vr-product-price">VR Product Price</a></li>
                            <li><a href="/admin/settings/vendor-consignment">Vendor Consignment</a></li>
                        @endif
                    </ul>
                </li>
                @endif
            </ul>
        </div><!-- /.navbar-collapse -->
    </div>
    @php
        $static_headline = Helper::get_static_headline();
    @endphp
    @if ($static_headline == '')

    @else
        <div class="">
            {!!$static_headline !!}
        </div>
    @endif
</nav>
<!-- End Navigation -->