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
            $('.note-check-box').tooltip();

            @if (!empty($trans))
            $('#activate_invoice').modal();
            @endif

            $('#mdn').keyup(function() {
                let length = $(this).val().length;
                $('#mdn_count').text(length);
            });

            $('#new_esn').keyup(function() {
                let length = $(this).val().length;
                $('#new_esn_count').text(length);
            });

        };

        function check_mdn() {

            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/rtr/gen/check_mdn_for_esn_swap',
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

                        $('#pin_box').show();

                        $('#customer_id_').val(res.customer_id);
                        $('#esn_number_').val(res.esn_number);
                        $('#telephone_number_').val(res.telephone_number);
                        $('#uiccid_').val(res.uiccid);
                        $('#account_password_').val(res.account_password);

                        $('#customer_id').text(res.customer_id);
                        $('#esn_number').text(res.esn_number);
                        $('#telephone_number').text(res.telephone_number);
                        $('#uiccid').text(res.uiccid);
                        $('#account_password').text(res.account_password);

                    } else {
                        $('#info_box').hide();
                        alert(res.msg);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });
        }

        function send_text_pin() {
            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/rtr/gen/send_text_pin',
                data: {
                    mdn: $('#mdn').val(),
                    pin: $('#account_password_').val()
                },
                type: 'get',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();
                    if (res.code === '0') {
                        myApp.showSuccess('Your request has been processed successfully!', function() {

                        });
                    } else {
                        alert(res.msg);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });
        }

        function check_pin() {

            if($('#pin').val().length < 1){
                alert('Please Enter Pin');
                return;
            }

            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/rtr/gen/check_pin',
                data: {
                    mdn: $('#mdn').val(),
                    pin: $('#pin').val()
                },
                type: 'get',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();
                    if (res.code === '0') {

                        $('#target_esn_box').show();

                    } else {
                        alert(res.msg);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });
        }

        function esn_swap_post() {

            if($('#pin').val().length < 1){
                alert('Please Enter Pin');
                return;
            }

            if($('#new_esn').val().length < 1){
                alert('Please Enter Target ESN');
                return;
            }

            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/rtr/gen/esn_swap_post',
                data: {
                    pin: $('#pin').val(),
                    customer_id : $('#customer_id_').val(),
                    mdn: $('#telephone_number_').val(),
                    old_esn: $('#esn_number_').val(),
                    olduiccid: $('#uiccid_').val(),
                    new_esn: $('#new_esn').val()
                },
                type: 'get',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();
                    if (res.code === '0') {
                        myApp.showSuccess('Your request has been processed successfully!', function() {
                            location.href = "/sub-agent/rtr/gen_esn_swap";
                        });
                    } else {
                        myApp.showError(res.msg, function() {
                            location.href = "/sub-agent/rtr/gen_esn_swap";
                        });
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
                        <h4>GEN Mobile</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li class="active">GEN Mobile</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End parallax -->

    @php
        $promotion = Helper::get_promotion('GEN Mobile');
    @endphp
    @if (!empty($promotion))
        <div class="news-headline no-print">
{{--            <marquee behavior="scroll" direction="left" onmouseover="this.stop();" onmouseout="this.start();">--}}
                {!!$promotion !!}
{{--            </marquee>--}}
        </div>
    @endif

    <!-- Start contain wrapp -->
    <div class="contain-wrapp padding-bot70 no-print" style="">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-sm-12">
                    <div class="clearfix"></div>


                    <div class="tabbable tab">
                        <ul class="nav nav-tabs">
                            <li>
                                <a href="/sub-agent/activate/gen" style="color: #ffdd05;">Activation (SPR)</a>
                            </li>
                            <li>
                                <a href="/sub-agent/activate/gen_tmo" style="cursor: pointer; color: #ea0a8e;">Activation (TMO)</a>
                            </li>
                            <li>
                                <a href="/sub-agent/rtr/gen" style="cursor: pointer;">Refill</a>
                            </li>
                            <li>
                                <a href="/sub-agent/rtr/gen_addon" style="cursor: pointer;">Add-Ons</a>
                            </li>
                            <li class="active">
                                <a href="#" style="cursor: pointer;" class="black-tab">ESN Swap</a>
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
                            <li>
                                <a href="/sub-agent/tools/gen" style="cursor: pointer;">Gen Tool</a>
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
                        <div class="tab-content" style="padding-top:36px;">
                            <form id="frm_act" method="post" class="row marginbot15">
                                {!! csrf_field() !!}
                                <div class="col-sm-2">
                                    <img src="/img/category-img-gen.jpg" style="width: 100%; margin-bottom: 16px;">
                                </div>

                                @if($esn_swap == 'Y')

                                    @if($esn_swap_num >= $num_try)
                                        <div class="col-sm-8">
                                            <div class="col-sm-4" align="right"
                                                 style="">
                                                <div class="form-group">
                                                    <label class="required">Phone Number</label>
                                                </div>
                                            </div>
                                            <div class="col-sm-5" align="right"
                                                 style="padding-top: 3px;">
                                                <div class="form-group">
                                                    <input type="text" class="form-control"
                                                           id="mdn"
                                                           name="mdn"
                                                           value=""
                                                           maxlength="10"
                                                           placeholder="10 digits and digits only"
                                                    />
                                                    <div id="count" align="left" style="color: red;
                                                                font-size: 12px;
                                                                margin-left: 10px;">
                                                        You have entered in <span id="mdn_count" style="font-weight: bold;">0</span> Digits
                                                    </div>
                                                    <div id="error_msg_esn"></div>
                                                </div>
                                                <div class="divider2"></div>
                                            </div>
                                            <div class="col-sm-3" align="left">
                                                <a class="btn btn-info btn-xs" onclick="check_mdn()">
                                                    Enter
                                                </a>
                                            </div>
                                        </div>


                                        <div class="col-sm-8" id="pin_box" style="display: none;">

                                            <input type="hidden" id="customer_id_" value="">
                                            <input type="hidden" id="esn_number_" value="">
                                            <input type="hidden" id="telephone_number_" value="">
                                            <input type="hidden" id="uiccid_" value="">
                                            <input type="hidden" id="account_password_" value="">

{{--                                            <div class="col-sm-12" style="margin-top: 16px;">--}}
{{--                                                <div class="col-sm-4" align="right"--}}
{{--                                                     style="">--}}
{{--                                                    <div class="form-group">--}}
{{--                                                        <label class="required">Information</label>--}}
{{--                                                    </div>--}}
{{--                                                </div>--}}
{{--                                                <div class="col-sm-5" align="left">--}}
{{--                                                    <table class="table table-bordered">--}}
{{--                                                        <tbody id="balance_box">--}}
{{--                                                        <tr><td>customer_id:</td><td id="customer_id"></td></tr>--}}
{{--                                                        <tr><td>esn_number:</td><td id="esn_number"></td></tr>--}}
{{--                                                        <tr><td>telephone_number:</td><td id="telephone_number"></td></tr>--}}
{{--                                                        <tr><td>uiccid:</td><td id="uiccid"></td></tr>--}}
{{--                                                        <tr><td>account_password:</td><td id="account_password"></td></tr>--}}
{{--                                                        </tbody>--}}
{{--                                                    </table>--}}
{{--                                                    <div class="divider2"></div>--}}
{{--                                                </div>--}}
{{--                                            </div>--}}

                                            <div class="col-sm-4" align="right"
                                                 style="">
                                                <div class="form-group">
                                                    <label class="required">PIN Number</label>
                                                </div>
                                            </div>
                                            <div class="col-sm-5" align="right"
                                                 style="padding-top: 3px;">
                                                <div class="form-group">
                                                    <input type="text" class="form-control"
                                                           id="pin"
                                                           name="pin"
                                                           value=""
                                                           maxlength="4"
                                                           placeholder="4 digits"
                                                    />
                                                </div>
                                                <div class="divider2"></div>
                                            </div>
                                            <div class="col-sm-3" align="left">
                                                <a class="btn btn-info btn-xs" onclick="send_text_pin()">
                                                    Send Text with PIN
                                                </a>
                                            </div>

                                            <div class="col-sm-4" align="right"
                                                 style="">
                                                <div class="form-group">
                                                    <label class="required">Target ESN</label>
                                                </div>
                                            </div>
                                            <div class="col-sm-5" align="right"
                                                 style="padding-top: 3px;">
                                                <div class="form-group">
                                                    <input type="text" class="form-control"
                                                           id="new_esn"
                                                           name="new_esn"
                                                           value=""
                                                    />
                                                    <div id="count" align="left" style="color: red;
                                                                font-size: 12px;
                                                                margin-left: 10px;">
                                                        You have entered in <span id="new_esn_count" style="font-weight: bold;">0</span> Digits
                                                    </div>
                                                    <div id="error_msg_new_esn"></div>
                                                </div>
                                                <div class="divider2"></div>
                                            </div>
                                            <div class="col-sm-3" align="left">
                                                <a class="btn btn-info btn-xs" onclick="esn_swap_post()">
                                                    Submit
                                                </a>
                                            </div>

                                        </div>

                                    @else
                                        <div class="col-sm-8">
                                            <div class="col-sm-6" align="right"
                                                 style="">
                                                <div class="form-group">
                                                    <label class="required">You've reached the maximum ESN Swap try</label>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                @else
                                    <div class="col-sm-8">
                                        <div class="col-sm-6" align="right"
                                             style="">
                                            <div class="form-group">
                                                <label class="required">ESN Swap function OFF. Please contact our customer care</label>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <!-- End info box -->

                            </form>
                        </div>


                    </div>
                </div>

            </div>
            {!! Helper::get_reminder('GEN Mobile') !!}

        </div>

    </div>
    </div>
    <!-- End contain wrapp -->
@stop
