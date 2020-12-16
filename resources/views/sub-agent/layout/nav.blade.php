<!-- Start top -->
@php
    $account = \App\Model\Account::find(Auth::user()->account_id);
    $user_role = Auth::user()->role;
@endphp
<div class="top-wrapp no-print">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <strong>AT&T Dealer Code:</strong> {{$account->att_dealer_code}} {{isset($account->att_dc_notes) ? $account->att_dc_notes : '' }}
                <ul class="top-link">
                    <li><strong>Contact Us:</strong> 703-256-3456 / ops@softpayplus.com</li>
                    @if (!in_array($account->id, [105124]))
                    <li>
                        <a href="#">{!! Helper::get_hierarchy_img(Auth::user()->account_type) !!} {{ Auth::user()->account_name . ' (' . Auth::user()->account_id . ')' }}</a>
                    </li>
                    <!--li><a href="#"><i class="fa fa-user"></i> Welcome {{ Auth::user()->user_id }}!</a></li-->
                    <li>
                        <a href="/sub-agent/reports/payments"><b>{{ Auth::user()->pay_method }}.Balance</b>: {!! Helper::get_balance() !!}

                    @if (Auth::user()->pay_method == 'C')
                        / <b>C.L: </b>${{ number_format(Auth::user()->credit_limit,2) }}
                    @endif
                    </a>
                    </li>
                    <li>
                        <a href="/sub-agent/reports/spiff#"><b>Paid Spiff</b>: ${{ number_format(\App\Model\SpiffTrans::getSpiffTotal
                        (Auth::user()->account_id), 2) }}</a>
                    </li>
                    <li>
                        <a href="/sub-agent/reports/consignment"><b>Consignment</b>: ${{ number_format(Helper::get_consignment_balance(), 2) }}</a>
                    </li>
                    @endif
                    <li><a href="/logout"><i class="fa fa-clock-o"></i>Logout</a></li>
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
            <a class="navbar-brand" href="/sub-agent"><img src="/img/brand/logo-black3.png" class="logo"
                                                                alt="" height="30"></a>
        </div>
        <!-- End Header Navigation -->

        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" id="navbar-menu">
            <ul class="nav navbar-nav navbar-right" data-in="fadeInDown" data-out="fadeOutUp">
                <li class="{{ Request::is("sub-agent") ? "active" : "" }}"><a href="/sub-agent">Home</a></li>
                @if (in_array($account->id, [105124]))
                <li class="{{ Request::is("sub-agent/reports/gen") ? "active" : "" }}"><a
                            href="/sub-agent/reports/gen">GEN Mobile Reports</a></li>
                @else
{{--                <li class="{{ Request::is("sub-agent/news") ? "active" : "" }}"><a href="/sub-agent/news">News</a></li>--}}

                <li class="dropdown{{ (Request::is("sub-agent/news")
                || Request::is("sub-agent/digital")
                || Request::is("sub-agent/advertise")
                || Request::is("sub-agent/task")
                || Request::is("sub-agent/follow") ) ? " active" : "" }}">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button"
                       aria-haspopup="true" aria-expanded="false">News</a>
                    <ul class="dropdown-menu">
                        <li><a href="/sub-agent/news">News</a></li>
                        <li><a href="/sub-agent/digital">Digital e-Marketing</a></li>
                        <li><a href="/sub-agent/advertise">Advertises</a></li>
                        <li><a href="/sub-agent/task">Tasks</a></li>
                        <li><a href="/sub-agent/follow">Follow-Ups</a></li>
                        <li><a href="/sub-agent/document">Documents</a></li>
                        <li><a href="/sub-agent/communication">Communications</a></li>
                    </ul>
                </li>

                <li class="dropdown{{ Request::is("sub-agent/activate/*") ? " active" : "" }}">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button"
                       aria-haspopup="true" aria-expanded="false">Activation</a>
                    <ul class="dropdown-menu">
                        <li><a href="/sub-agent/activate/air_voice">Air Voice</a></li>
                        <li><a href="/sub-agent/activate/att">AT&T USA</a></li>
{{--                        <li><a href="/sub-agent/activate/attprvi">AT&T PR/VI</a></li>--}}
{{--                        <li><a href="/sub-agent/activate/attdataonly">AT&T Data Only</a></li>--}}
                        <li><a href="/sub-agent/activate/boom_blue">Boom Mobile Blue</a></li>
                        <li><a href="/sub-agent/activate/boom_red">Boom Mobile Red</a></li>
                        <li><a href="/sub-agent/activate/boom_purple">Boom Mobile Purple</a></li>
                        <li><a href="/sub-agent/activate/freeup">FreeUP</a></li>
                        <li><a href="/sub-agent/activate/gen">GEN Mobile (SPR)</a></li>
                        <li><a href="/sub-agent/activate/gen_tmo">GEN Mobile (TMO)</a></li>
                        <li><a href="/sub-agent/activate/h2oe">H2O</a></li>
                        <li><a href="/sub-agent/activate/liberty">Liberty Mobile</a></li>
                        <li><a href="/sub-agent/activate/lyca">Lyca</a></li>
                        <li><a href="/sub-agent/activate/rokit">ROKiT</a></li>
{{--                        <li><a href="/sub-agent/activate/lycabnk">Lyca_BNK</a></li>--}}
                        <li><a href="/sub-agent/activate/xfinity">Xfinity</a></li>
                        <!-- <li><a href="/sub-agent/activate/h2o">H2O</a></li> -->
                        <!-- <li><a href="/sub-agent/activate/rok">ROK Mobile</a></li> -->
                        <!--li role="separator" class="divider"></li>
                        <li class="dropdown-header">Nav header</li>
                        <li><a href="#">Separated link</a></li>
                        <li><a href="#">One more separated link</a></li-->
                    </ul>
                </li>
                <li class="dropdown{{ (Request::is("sub-agent/rtr/*") || Request::is("sub-agent/pin/*")) ? " active" : "" }}">
                    <a href="/sub-agent/rtr/domestic" class="dropdown-toggle" data-toggle="dropdown" role="button"
                       aria-haspopup="true" aria-expanded="false">REFILL</a>
                    <ul class="dropdown-menu">
                        <li><a href="/sub-agent/pin/domestic">REFILL - PIN</a></li>
                        <li><a href="/sub-agent/rtr/domestic">REFILL - RTR</a></li>
                        <li><a href="#" onclick="claro_carrier()">INTL TOPUP - RTR</a></li>
                        <li><a href="/sub-agent/rtr/boom">Boom Mobile</a></li>
                        <li><a href="/sub-agent/rtr/gen">GEN Mobile</a></li>
                        @php
                            $jingo = \App\Model\Product::find('WJGTV');
                        @endphp
                        @if (!empty($jingo) && $jingo->status == 'A')
                        <li><a href="#" onclick="jingotv_pin()">Jingo TV</a></li>
                        <form id="frm_jingo_pin" method="post" action="/sub-agent/pin/domestic">
                            {!! csrf_field() !!}
                            <input type="hidden" id="jingo_carrier" name="carrier" value="Jingo TV">
                        </form>
                        <script type="text/javascript">
                            function jingotv_pin(carrier) {
                                $('#frm_jingo_pin').submit();
                            }
                        </script>
                        @endif
                        <li><a href="/sub-agent/rtr/boss">Boss Revolution</a></li>
                        <li><a href="/sub-agent/rtr/dpp">Dollarphone Pinless</a></li>
                        <li><a href="#" onclick="claro_carrier()">Claro</a></li>
                        <form id="frm_claro" method="post" action="/sub-agent/rtr/domestic">
                            {!! csrf_field() !!}
                            <input type="hidden" id="claro_carrier" name="carrier" value="Claro">
                        </form>
                        <script type="text/javascript">
                            function claro_carrier() {
                                $('#frm_claro').submit();
                            }
                        </script>
                    </ul>
                </li>
                <!--li class="dropdown{{ Request::is("sub-agent/phones/*") ? " active" : "" }}">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                       aria-expanded="false">Phones</a>
                    <ul class="dropdown-menu">
                        <li><a href="http://www.perfectmobileusa.com/shopmain/" target="_blank">Phone</a></li>
                        <li><a href="/sub-agent/phones/accessory">Accessory</a></li>
                    </ul>
                </li-->
                <li class="dropdown{{ Request::is("sub-agent/tools/*") ? " active" : "" }}">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                       aria-expanded="false">Tools</a>
                    <ul class="dropdown-menu">
                        <li><a href="/sub-agent/tools/att">ATT</a></li>
                        @if (\App\Lib\Helper::has_att_batch_authority())
{{--                        <li><a href="/sub-agent/tools/att-batch">ATT Schedule</a></li>--}}
                        @endif
                        <li><a href="/sub-agent/tools/freeup">FreeUp</a></li>
                        <li><a href="/sub-agent/tools/gen">Gen Mobile</a></li>
                        <li><a href="/sub-agent/tools/boom">Boom Mobile</a></li>
                    </ul>
                </li>
                <li class="dropdown{{ Request::is("sub-agent/reports/*") ? " active" : "" }}">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                       aria-expanded="false">Reports</a>
                    <ul class="dropdown-menu">
                        <li><a href="/sub-agent/reports/activity">Activity Report</a></li>
                        <li><a href="/sub-agent/reports/consignment">Consignment Balance Report</a></li>
                        <li><a href="/sub-agent/reports/credit">Credit History</a></li>
                        <li><a href="/sub-agent/reports/invoices">Invoice Report</a></li>
                        <li><a href="/sub-agent/reports/payments">Payment Report</a></li>
                        <li><a href="/sub-agent/reports/promotion">Promotion Report</a></li>
                        <li><a href="/sub-agent/reports/rebate">Rebate Report</a></li>
                        <li><a href="/sub-agent/reports/residual">Residual Report</a></li>
                        <li><a href="/sub-agent/reports/rtr-q">RTR Queue Report</a></li>
                        <li><a href="/sub-agent/reports/spiff">Paid Spiff Report</a></li>
                        <li><a href="/sub-agent/reports/transaction">Transaction Report</a></li>
{{--                        <li><a href="/sub-agent/reports/transaction-new">New Transaction Report</a></li>--}}
                        <li><a href="/sub-agent/reports/vr-request">Order Report</a></li>
                        <li><a href="/sub-agent/reports/ach-bounce">ACH Bounce</a></li>
                        @if ($account->show_discount_setup_report == 'Y')
                        <li><a href="/sub-agent/reports/discount-setup">Discount Setup Report</a></li>
                        @endif
                        @if ($account->show_spiff_setup_report == 'Y')
                        <li><a href="/sub-agent/reports/spiff-setup">Spiff Setup Report</a></li>
                        @endif
                    </ul>
                </li>
                <li class="dropdown{{ Request::is("sub-agent/setting/*") ? " active" : "" }}">
                    <a href="/sub-agent/setting/my-password" class="dropdown-toggle" data-toggle="dropdown"
                       role="button" aria-haspopup="true" aria-expanded="false">Setting</a>
                    <ul class="dropdown-menu">
                        <li><a href="/sub-agent/setting/documents">Documents</a></li>
                        <li><a href="/sub-agent/setting/my-account">My Account</a></li>
                        <li><a href="/sub-agent/setting/my-password">My Password</a></li>
                        <li><a href="/sub-agent/setting/users">Users</a></li>
                        @if ( $user_role == 'M')
                            <li><a href="/sub-agent/setting/store">Store Hours</a></li>
{{--                            <li><a href="/sub-agent/setting/user-hour">User Hours</a></li>--}}
                        @endif
                    </ul>
                </li>
                <li class="dropdown{{ Request::is("sub-agent/tooltips/*") ? " active" : "" }}">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                       aria-expanded="false">Tool Tips</a>
                    <ul class="dropdown-menu">
                        <li><a href="/assets/tooltips/Tooltip1.pdf" target="_blank">Tooltip 1</a></li>
                        <li><a href="/assets/tooltips/Tooltip2.pdf" target="_blank">Tooltip 2</a></li>
                        <li><a target="_blank" href="/assets/att/Registration.mp4">Registration Video</a></li>
                        <li><a target="_blank" href="/assets/att/Activate-Recharge-AddFunds-Registration.mp4">Act.RTR.Regist. Video</a></li>
                        <li><a target="_blank" href="/assets/att/Activation-Port-In.mp4">Activation-Portin Video</a></li>
                        <li><a target="_blank" href="/assets/att/Recharge.mp4">Recharge Video</a></li>
                        <li><a target="_blank" href="/assets/att/AddFunds.mp4">Add Fund Video</a></li>
                        <li><a target="_blank" href="/assets/att/ATT_Activation_E_commerce.mp4">ATT E-Comm. Video</a></li>
                        <li><a target="_blank" href="/assets/jingotv/JingoTV.mp4">Jingo TV. Video</a></li>
                        <li><a target="_blank" href="/assets/Esign.mp4">Esign Video</a></li>
                        <li><a target="_blank" href="/assets/att/ATT_Prepaid_Video_1080.mp4">ATT Prepaid Video</a></li>
                    </ul>
                </li>
                <li class="dropdown{{ Request::is("sub-agent/virtual-rep/*") ? " active" : "" }}">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                       aria-expanded="false">Market Place</a>
                    <ul class="dropdown-menu">
                        <li><a href="/sub-agent/virtual-rep/shop" style="text-align: left">Order</a></li>
                        <li><a href="/sub-agent/virtual-rep/cart" style="text-align: left">View Cart / Proceed to Checkout</a></li>
                        <li><a href="/sub-agent/reports/vr-request" style="text-align: left">Track My order</a></li>
                        <li><a href="/sub-agent/virtual-rep/general_request" style="text-align: left">General Request</a></li>
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