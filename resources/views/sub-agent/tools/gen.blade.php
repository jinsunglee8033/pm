@extends('sub-agent.layout.default')

@section('content')
    <style type="text/css">

        .receipt .row {
            border: 1px solid #e5e5e5;
        }

        .receipt .col-sm-4 {
            border-right: 1px solid #e5e5e5;
        }

        .row + .row {
            border-top: 0;
        }

        .divider2 {
            margin: 5px 0px !important;
        }

        hr {
            margin-top: 5px !important;
            margin-bottom: 5px !important;
        }

    </style>
    <script type="text/javascript">

        window.onload = function () {

            $('#mdn').keyup(function() {
                let length = $(this).val().length;
                $('#mdn_count').text(length);
            });

        };

        function request_puk() {

            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/tools/gen/puk',
                data: {
                    mdn: $('#mdn').val()
                },
                type: 'get',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();
                    if (res.code === '0') {
                        $('#info_box').show();
                        $('#msl').val(res.msl);
                        $('#msid').val(res.msid);
                    } else {
                        myApp.showError(res.msg);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });
        }
    </script>

    <!-- Start parallax -->
    <div class="parallax no-print" data-background="/img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
        <div class="overlay white"></div>
        <div class="container">
            <div class="inner-head">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h4>Dealer Tools</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li><a href="#">Tools</a></li>
                            <li class="active">Gen</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End parallax -->

    <!-- Start contain wrapp -->
    <div class="contain-wrapp padding-bot70 no-print" style="">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-sm-12">
                    <div class="clearfix"></div>


                    <div class="tabbable tab">
                        <ul class="nav nav-tabs">
                            <li>
                                <a href="/sub-agent/activate/gen">Activation (SPR)</a>
                            </li>
                            <li>
                                <a href="/sub-agent/activate/gen_tmo" style="cursor: pointer;">Activation (TMO)</a>
                            </li>
                            <li>
                                <a href="/sub-agent/rtr/gen" style="cursor: pointer;">Refill</a>
                            </li>
                            <li>
                                <a href="/sub-agent/rtr/gen_addon" style="cursor: pointer;">Add-Ons</a>
                            </li>
                            <li>
                                <a href="/sub-agent/rtr/gen_esn_swap" style="cursor: pointer;">ESN Swap</a>
                            </li>
                            <li>
                                <a href="/sub-agent/rtr/gen_mdn_swap" style="cursor: pointer;">MDN Swap</a>
                            </li>
                            <li>
                                <a href="/sub-agent/wallet/gen" style="cursor: pointer;">Wallet</a>
                            </li>
                            <li>
                                <a target="_blank" href="/gen/redemption" style="cursor: pointer;">PIN</a>
                            </li>
                            <li class="active">
                                <a href="/sub-agent/tools/gen" style="cursor: pointer;" class="black-tab">Gen Tool</a>
                            </li>
                            <li>
                                <a href="#" onclick="gen_sim_order()">SIM Order</a>
                            </li>
                            <li>
                                <a href="#" onclick="gen_device_order()">Device Order</a>
                            </li>
                            <li>
                                <a href="/sub-agent/reports/vr-request">Order History</a>
                            </li>
                        </ul>

                        <script>
                            function gen_sim_order() {
                                $('#frm_gen_sim_order').submit();
                            }
                            function gen_device_order() {
                                $('#frm_gen_device_order').submit();
                            }
                        </script>

                        <form id="frm_gen_sim_order" class="form-horizontal" method="post"
                              action="/sub-agent/virtual-rep/shop">
                            {{ csrf_field() }}
                            <input type="hidden" name="category" value="SIM">
                            <input type="hidden" name="sub_category" value="INSTANT SIM">
                            <input type="hidden" name="carrier" value="GEN MOBILE">
                        </form>
                        <form id="frm_gen_device_order" class="form-horizontal" method="post"
                              action="/sub-agent/virtual-rep/shop">
                            {{ csrf_field() }}
                            <input type="hidden" name="category" value="DEVICE">
                            <input type="hidden" name="carrier" value="GEN MOBILE">
                        </form>

                        <!-- Tab panes -->
                        <div class="tab-content" style="padding-top:24px;">


                            <div class="tabbable tab-lg">
                                <!-- Nav tabs -->
                                <ul class="nav nav-tabs" role="tablist">
                                    <li role="presentation">
                                        <a href="#msl" aria-controls="" role="tab" data-toggle="tab">MSL/MSID</a></li>
                                </ul>

                                <!-- Tab panes -->
                                <div class="tab-content">

                                    <div role="tabpanel" class="tab-pane active" style="padding:15px;">
                                        <form class="form-horizontal well">
                                            <div class="row marginbot15" style="margin-top: 32px;">
                                                <div class="col-md-12">
                                                    <div class="col-sm-4" align="right">
                                                        <div class="form-group">
                                                            <label class="required">Phone #</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <input type="text" class="form-control" id="mdn"
                                                               onchange="request_puk()"/>
                                                        <div id="count" align="left" style="color: red;
                                                        font-size: 12px;
                                                        margin-left: 10px;">
                                                            You have entered in <span id="mdn_count" style="font-weight: bold;">0</span> Digits
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-3" align="left">
                                                        <a class="btn btn-info btn-xs">
                                                            Enter
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>

                                            <div id="info_box" style="display: none;">
                                                <div class="row marginbot15" style="margin-top: 32px;">
                                                    <div class="col-md-12">
                                                        <div class="col-sm-4" align="right">
                                                            <div class="form-group">
                                                                <label class="required">MSL </label>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-5">
                                                            <input type="text" class="form-control" id="msl" disabled/>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <div class="col-sm-4" align="right">
                                                            <div class="form-group">
                                                                <label class="required">MSID </label>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-5">
                                                            <input type="text" class="form-control" id="msid" disabled/>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <!-- End MSL/MSID -->

                                </div>
                                <!-- End Tab Content -->

                            </div>

                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
@stop
