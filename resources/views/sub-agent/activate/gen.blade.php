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

            $('#esn').keyup(function() {
                let length = $(this).val().length;
                $('#esn_count').text(length);
            });

            $('#sim').keyup(function() {
                let length = $(this).val().length;
                $('#sim_count').text(length);
            });

            $('[data-toggle="popover-hover"]').popover({
                html: true,
                trigger: 'hover',
                content: function () { return '<img src="' + $(this).data('img') + '" />'; }
            });
        };

        function check_device() {

            if($('#esn').val().length == 0){
                alert("Please Insert ESN");
                return;
            }

            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/activate/gen/esn',
                data: {
                    esn: $('#esn').val()
                },
                type: 'get',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();
                    if (res.code === '0') {
                        if(res.kits !=='') {
                            let text = '<b>Models Number: </b>'+ res.kits.model_number +'<br>';
                            text += '<p style="color: red"><b>Types: </b>SIM';
                            if (res.kits.kit_a == 'Yes') {
                                text += ' Kit-A ' ;
                            }
                            if (res.kits.kit_b == 'Yes') {
                                text += ' Kit-B ' ;
                            }
                            if (res.kits.kit_c == 'Yes') {
                                text += ' Kit-C ' ;
                            }
                            text += '</p>';
                            $('#kits').html(text);
                        }
                        $('#sim_box').show();
                        $('#sim').val(res.sim);
                        $('#sim_count').text(res.sim.length);
                        $('#zip_box').show();
                    } else {
                        $('#sim_box').hide();
                        alert(res.msg);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });

        }

        function onchange_sim() {
            $('#zip').val('');
            $('#city').val('');
            $('#state').val('');
            $('#zip_box').hide();
            $('#info_box').hide();
        }

        function check_sim() {

            if($('#sim').val().length == 0){
                alert("Please Insert Sim");
                return;
            }
            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/activate/gen/sim',
                data: {
                    sim: $('#sim').val()
                },
                type: 'get',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();
                    if (res.code === '0') {
                        $('#zip_box').show();
                    } else {
                        $('#zip_box').hide();
                        alert(res.msg);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });

        }

        function check_zip() {

            if($('#zip').val().length != 5){
                alert("5 Digit zip code Only");
                return;
            }

            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/activate/gen/zip',
                data: {
                    zip: $('#zip').val(),
                    sim: $("#sim").val(),
                    esn: $('#esn').val()
                },
                type: 'get',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();
                    if (res.code === '0') {
                        $('#info_box').show();


                        $('#plans_box').empty();
                        $('#activation_fee_box_body').hide();

                        // $.each(res.plans, function(k, v) {
                        $.each(res.denoms, function(k, v) {
                            var tr = '<tr>';
                            tr += '<td><input type="radio" value="' + v.denom_id + '" name="denom_id" ' +
                                'onclick="get_commission()"></td>';
                            tr += '<td style="text-align:center;">$' + v.denom + '</td>';
                            tr += '<td>' + v.denom_name + '</td>';
                            if (res.sim_type == 'P' && v.spiff == 0) {
                                tr += '<td style="text-align:center;">Paid</td>';
                            } else {
                                if(v.spiff == null){
                                    tr += '<td style="text-align:center;">$ 0.00</td>';
                                }else {
                                    tr += '<td style="text-align:center;">$' + v.spiff + '</td>';
                                }
                            }
                            tr += '</tr>';
                            $('#plans_box').append(tr);
                        });

                        if (res.city == '') {
                            $('#city').val('');
                            $('#state').val('');
                        } else {
                            $('#city').val(res.city.city);
                            $('#state').val(res.city.state);
                        }

                        $('#allowed_months_box').empty();
                        $.each(res.allowed_months, function(k, v) {
                            $('#allowed_months_box').append('<label><input type="radio" style="margin-bottom:5px;" class="radio-inline" id="rtr_month" name="rtr_month" value="' + v + '"> ' + v + ' Month &nbsp;&nbsp;</label>');
                        });
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

        function portin_checked() {
            var checked = $("#is_port_in").is(':checked');

            if (checked) {
                myApp.showLoading();
                $.ajax({
                    url: '/sub-agent/activate/gen/get_portin_form',
                    type: 'get',
                    dataType: 'html',
                    cache: false,
                    success: function(res) {
                        myApp.hideLoading();
                        $('#portin_box').empty();
                        $('#portin_box').html(res);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        myApp.hideLoading();
                        myApp.showError(errorThrown);
                    }
                });
            } else {
                $('#portin_box').empty();
            }
        }

        function get_commission() {
            $('#spiff_div').hide();

            var esn         = $('#esn').val();
            var sim         = $('#sim').val();
            var denom_id    = $('input[name=denom_id]:checked').val();

            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/activate/gen/commission',
                data: {
                    esn: esn,
                    sim: sim,
                    denom_id: denom_id,
                    is_port_in: $("#is_port_in").is(':checked') ? 'Y' : 'N'
                },
                type: 'get',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();
                    if (res.code === '0') {
                        $('#spiff_label').empty();
                        $('#activation_fee_box_body').show();
                        $('#activation_fee_box').text(res.data.activation_fee);

                        if (res.data.spiff_count > 0) {
                            $('#spiff_div').show();
                            $.each(res.data.spiff_labels, function(k, v) {
                                $('#spiff_label').append("<strong>" + v + "</strong><br>");
                            });
                        }

                        if (res.data.sim_charge == 0) {
                            $('#sim_charge_div').hide();
                        }else{
                            $('#sim_charge_label').text(res.data.sim_charge);
                            $('#sim_charge_div').show();
                        }

                        if (res.data.sim_rebate == 0) {
                            $('#sim_rebate_div').hide();
                        }else{
                            $('#sim_rebate_label').text(res.data.sim_rebate);
                            $('#sim_rebate_div').show();
                        }

                        if (res.data.esn_charge == 0) {
                            $('#esn_charge_div').hide();
                        }else{
                            $('#esn_charge_label').text(res.data.esn_charge);
                            $('#esn_charge_div').show();
                        }

                        if (res.data.esn_rebate == 0) {
                            $('#esn_rebate_div').hide();
                        }else{
                            $('#esn_rebate_label').text(res.data.esn_rebate);
                            $('#esn_rebate_div').show();
                        }

                    } else {
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });

        }

        function request_activation() {
            $("div[id^='error_msg_']").empty();

            if (!$("input[name=denom_id]").is(":checked")) {
                alert("Please select a plan to activate !!");
                return;
            }

            if (!$("input[name=rtr_month]").is(":checked")) {
                alert("Please select a activation month to activate !!");
                return;
            }

            var is_port_in = $("#is_port_in").is(':checked');

            var esn         = $('#esn').val();
            var sim         = $('#sim').val();
            var zip         = $('#zip').val();
            var city        = $('#city').val();
            var state       = $('#state').val();
            var denom_id    = $('input[name=denom_id]:checked').val();
            var rtr_month   = $("input[name=rtr_month]:checked").val();

            var data = is_port_in ? {
                _token: '{{ csrf_token() }}',
                esn: esn,
                sim: sim,
                zip: zip,
                city: city,
                state: state,
                denom_id: denom_id,
                rtr_month: rtr_month,
                is_port_in: 'Y',
                note: $('#note').val(),
                number_to_port: $('#number_to_port').val(),
                account_no: $('#account_no').val(),
                account_pin: $('#account_pin').val(),
                first_name: $('#first_name').val(),
                last_name: $('#last_name').val(),
                address1: $('#address1').val(),
                address2: $('#address2').val(),
                account_city: $('#account_city').val(),
                account_state: $('#account_state').val(),
                account_zip: $('#account_zip').val(),
                call_back_phone: $('#call_back_phone').val(),
                email: $('#email').val()
            } : {
                _token: '{{ csrf_token() }}',
                esn: esn,
                sim: sim,
                zip: zip,
                city: city,
                state: state,
                denom_id: denom_id,
                rtr_month: rtr_month,
                is_port_in: 'N'
            };

            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/activate/gen/post',
                data: data,
                type: 'post',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();

                    if (res.code == '0') {
                        // alert(res.data.msg + ' [MDN: ' + res.data.mdn + ']');
                        window.location.href = '/sub-agent/activate/gen/success/' + res.data.id;
                        // print_invoice(res.data.id);
                    } else {
                        var error_msg = '';
                        if (res.code == '-1') {
                            $.each(res.data, function(k, v) {
                                // alert(v.fld + ' : ' + v.msg);
                                error_msg += v.fld + ' : ' + v.msg + '<br>';
                                $('#' + 'error_msg_' + v.fld).append('<strong><span class="help-block" style="color:red;text-align:left;">' + v.msg + '</span></strong>');
                            });
                        } else {
                            // alert(res.data.fld + ' : ' + res.data.msg);
                            error_msg += res.data.fld + ' : ' + res.data.msg;
                        }

                        myApp.showError(error_msg + '<br> Please call 1-833-528-1380 if you have questions.');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });

        }

        function printDiv() {
            window.print();
        }

    </script>


    @if (!empty($trans))
        <div id="activate_invoice" class="modal fade " tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false"
             style="display:block;">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Activation Success</h4>
                    </div>
                    <div class="modal-body receipt" id="activate_invoice_body">
                        <p>
                            Your request is being processed.<br/>
                            Please refer to "Reports -> Activation / Port-In" for more information.
                        </p>
                        <div class="row">
                            <div class="col-sm-4">Date / Time</div>
                            <div class="col-sm-8">{{ date('Y-M-d H:i:s') }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Invoice no.</div>
                            <div class="col-sm-8">{{ $trans->id }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Phone no.</div>
                            <div class="col-sm-8">{{ $trans->phone }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">SIM</div>
                            <div class="col-sm-8">{{ $trans->sim }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">IMEI</div>
                            <div class="col-sm-8">{{ $trans->esn }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">MSL</div>
                            <div class="col-sm-8">{{ empty($trans->deviceinfo) ? '' : $trans->deviceinfo->msl }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">MSID</div>
                            <div class="col-sm-8">{{ empty($trans->deviceinfo) ? '' : $trans->deviceinfo->msid }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Carrier</div>
                            <div class="col-sm-8">{{ $trans->product->carrier }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Product</div>
                            <div class="col-sm-8">{{ $trans->product->name }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Plan Price</div>
                            <div class="col-sm-8">${{ number_format($trans->denom, 2) }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Refill Month</div>
                            <div class="col-sm-8">{{ $trans->rtr_month }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Sub Total</div>
                            <div class="col-sm-8">${{ number_format($trans->denom * $trans->rtr_month, 2) }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Vendor Fee</div>
                            <div class="col-sm-8">${{ number_format($trans->fee, 2) }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Total</div>
                            <div class="col-sm-8">${{ number_format($trans->denom * $trans->rtr_month + $trans->fee, 2) }}</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" onclick="printDiv()">Print</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

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


    <form id="frm_att_rtr" method="post" action="/sub-agent/rtr/domestic">
        {!! csrf_field() !!}
        <input type="hidden" name="carrier" value="GEN Mobile">
        <input type="hidden" name="product_id" value="WGENR">
    </form>

    <form id="frm_att_transaction" method="post" action="/sub-agent/reports/transaction">
        {!! csrf_field() !!}
        <input type="hidden" name="carrier" value="AT&T">
    </form>

    <!-- Start contain wrapp -->
    <div class="contain-wrapp padding-bot70 no-print" style="">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-sm-12">
                    <div class="clearfix"></div>


                    <div class="tabbable tab">
                        <ul class="nav nav-tabs">
                            <li class="active">
                                <a href="/sub-agent/activate/gen" class="black-tab">Activation (SPR)</a>
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
                                <label style="color: #ffdd05; margin-left: -65%; margin-top: 6%">SPR</label>

                                @if (Helper::over_activation('GEN Mobile') != '')
                                    <div class="col-sm-8">
                                    {!! Helper::over_activation('GEN Mobile') !!}
                                    </div>
                                @else
                                <div class="col-sm-8">
                                    <div class="col-sm-4" align="right">
                                        <div class="form-group">
                                            <label class="required">Network</label>
                                        </div>
                                    </div>
                                    <div class="col-sm-5">
                                        <div class="form-group">
                                            <label style="color: #ffdd05"><input type="radio" name="network" checked/>  SPR </label> &nbsp;&nbsp;
                                            <label style="color: #ea0a8e"><input type="radio" name="network" onclick="window.location.href='/sub-agent/activate/gen_tmo';" /> TMO </label>&nbsp;&nbsp;
                                        </div>
                                        <label>Activation (SPR) CDMA</label>
                                    </div>
                                </div>
                                <div class="col-sm-8">
                                    <div class="col-sm-4" align="right" style="">
                                        <div class="form-group">
                                            <label class="required">Device ID (ESN#)</label>
                                        </div>
                                    </div>
                                    <div class="col-sm-5" align="right"
                                         style="padding-top: 3px;">
                                        <div class="form-group">
                                            <input type="text" class="form-control"
                                                   id="esn"
                                                   name="esn"
                                                   value=""
                                                   maxlength="20"
                                                   placeholder="20 digits and digits only"
                                                   onblur="check_device()"
                                            />
                                            <div id="count" align="left" style="color: red;
                                                        font-size: 12px;
                                                        margin-left: 10px;">
                                                You have entered in <span id="esn_count" style="font-weight: bold;">0</span> Digits
                                            </div>
                                            <div id="error_msg_esn"></div>
                                            <div id="kits" align="left"></div>
                                        </div>
                                        <p align="left"><strong>Sprint/Boost/Virgin Devices:</strong> The existing SIM
                                            can be used
                                            for Gen
                                            Mobile service.  If you need a replacement SIM, check for Gen Mobile SIM A, B, or C for compatibility.</p>
                                        <p align="left"><strong>Other Devices:</strong> For new devices or a device that
                                            has no
                                            current
                                                       service, you can put in the new SIM and start activation. If
                                            you are porting a number from a device that you are currently using,
                                            insert the new SIM after your current service stops working and then
                                            start activation. <a target="_blank" href="https://www.genmobile.com/pages/dealer-support">More Reference</a>
                                        </p>
                                        <div class="divider2"></div>
                                    </div>
                                    <div class="col-sm-3" align="left">
                                        <a class="btn btn-info btn-xs" href="#" onclick="check_device()">
                                            Enter
                                        </a>
                                    </div>
                                </div>

                                <div class="col-sm-12" id="sim_box" style="display:none">
                                    <div class="col-sm-4" align="right"
                                         style="">
                                        <div class="form-group">
                                            <label class="required">UICCID (SIM#)</label>
                                        </div>
                                    </div>
                                    <div class="col-sm-5" align="right"
                                         style="padding-top:3px;">
                                        <div class="form-group">
                                            <input type="text" class="form-control"
                                                   id="sim"
                                                   name="sim"
                                                   value=""
                                                   maxlength="20"
                                                   placeholder="Enter if 4G LTE Capable device"
                                                   onblur="check_sim()"
                                            />
                                            <div id="count" align="left" style="color: red;
                                                        font-size: 12px;
                                                        margin-left: 10px;">
                                                You have entered in <span id="sim_count" style="font-weight: bold;">0</span> Digits
                                            </div>
                                            <div id="error_msg_sim"></div>
                                        </div>
                                    </div>
                                    <div class="col-sm-3" align="left" style="">
                                        <a class="btn btn-info btn-xs" href="#" onclick="check_sim()">
                                            Enter
                                        </a>
                                    </div>
                                </div>

                                <div class="col-sm-12" id="zip_box" style="display:none">
                                    <div class="col-sm-4" align="right"
                                         style="">
                                        <div class="form-group">
                                            <label class="required">Zip Code</label>
                                        </div>
                                    </div>
                                    <div class="col-sm-5" align="right"
                                         style="">
                                        <div class="form-group">
                                            <input type="text" class="form-control"
                                                   id="zip"
                                                   name="zip"
                                                   value=""
                                                   maxlength="5"
                                                   placeholder=""
                                                   onblur="check_zip()"
                                            />
                                            <div id="error_msg_zip_code"></div>
                                        </div>
                                        <div class="divider2"></div>
                                    </div>
                                    <div class="col-sm-3" align="left">
                                        <a class="btn btn-info btn-xs" href="#" onclick="check_zip()">
                                            Enter
                                        </a>
                                    </div>
                                </div>

                                <div id="info_box" style="display: none;">

                                    <div class="col-sm-12">
                                        <div class="col-sm-4" align="right"
                                             style="">
                                            <div class="form-group">
                                                <label class="required">City</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-5" align="right"
                                             style="">
                                            <div class="form-group">
                                                <input type="text" class="form-control"
                                                       id="city"
                                                       name="city"
                                                       value=""
                                                       placeholder=""
                                                />
                                                <div id="error_msg_city"></div>
                                            </div>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-12">
                                        <div class="col-sm-4" align="right"
                                             style="">
                                            <div class="form-group">
                                                <label class="required">State</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-5" align="right"
                                             style="">
                                            <div class="form-group">
                                                <select id="state" name="state" class="form-control">
                                                    <option value="">Select State</option>
                                                    @foreach($states as $st)
                                                        <option value="{{ $st->code }}">{{ $st->name }}</option>
                                                    @endforeach
                                                </select>
                                                <div id="error_msg_state"></div>
                                            </div>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-12" style="margin-top: 16px;">
                                        <div class="col-sm-4" align="right"
                                             style="">
                                            <div class="form-group">
                                                <label class="required">Plans</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-5" align="left">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th></th>
                                                        <th style="text-align:center;">Amount</th>
                                                        <th>Plan Description</th>
                                                        <th style="text-align:center;">Spiff</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="plans_box">

                                                </tbody>
                                            </table>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-12" id="spiff_div" style="margin-top: 16px;display: none;">
                                        <div class="col-sm-4" align="right"
                                             style="">
                                            <div class="form-group">
                                                <label class="required">Extra Spiff</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-5" align="left">
                                            <label style="display: none;"><span id="sim_label"></span></label>
                                            <div id="spiff_label">&nbsp;
                                            </div>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-12" id="activation_fee_box_body">
                                        <div class="col-sm-4" align="right">
                                            <label class="required">Activation.Fee</label>
                                        </div>
                                        <div class="col-sm-5">

                                            <div class="form-group">
                                                $<span id="activation_fee_box">0.00</span>
                                            </div>
                                            <div class="divider2"></div>
                                        </div>

                                        <div class="col-sm-2"></div>
                                    </div>

                                    <div class="col-sm-12" id="sim_charge_div" style="display: none;">
                                        <div class="col-sm-4" align="right"
                                             style="">
                                            <div class="form-group">
                                                <label class="required">Sim Charge</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-5" align="left">
                                            <label>$ <span id="sim_charge_label"></span></label>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-12" id="sim_rebate_div" style="display: none;">
                                        <div class="col-sm-4" align="right"
                                             style="">
                                            <div class="form-group">
                                                <label class="required">Sim Rebate</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-5" align="left">
                                            <label>$ <span id="sim_rebate_label"></span></label>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-12" id="esn_charge_div" style="display: none;">
                                        <div class="col-sm-4" align="right"
                                             style="">
                                            <div class="form-group">
                                                <label class="required">ESN Charge</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-5" align="left">
                                            <label>$ <span id="esn_charge_label"></span></label>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-12" id="esn_rebate_div" style="display: none;">
                                        <div class="col-sm-4" align="right"
                                             style="">
                                            <div class="form-group">
                                                <label class="required">ESN Rebate</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-5" align="left">
                                            <label>$ <span id="esn_rebate_label"></span></label>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-12">
                                        <div class="col-sm-4" align="right">
                                            <label class="required">Activation.Month</label>
                                        </div>
                                        <div class="col-sm-5">

                                            <div class="form-group">
                                                <div id="allowed_months_box">
                                                </div>
                                            </div>
                                            <div class="divider2"></div>
                                        </div>

                                        <div class="col-sm-2"></div>
                                    </div>


                                    <div class="col-sm-12">
                                        <div class="col-sm-4" align="right">
                                            <div class="form-group">
                                                <label>Port-In ?</label>
                                            </div>
                                        </div>

                                        <div class="col-sm-5">
                                            <div class="form-group">
                                                <div class="checkbox">
                                                    <label>
                                                        <input type="checkbox" id="is_port_in"
                                                               onclick="portin_checked()">I'd like to transfer from my
                                                        current phone number.
                                                    </label>
                                                </div>
                                            </div>
                                            <label>
                                                <a class="btn btn-primary" data-toggle="popover-hover"
                                                   data-img="/img/Port-in_data_example.PNG"
                                                   data-placement="bottom"
                                                   style="font-size: 10px;">
                                                    Show me Port-In Example
                                                </a>
                                            </label>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div id="portin_box">
                                        <div class="col-sm-4" align="right"
                                             style="">
                                            <div class="form-group">
                                                <label class="required"></label>
                                            </div>
                                        </div>
                                        <div class="col-sm-5" align="left"
                                             style="padding-top:3px;color: red;">
                                            <p>If you’d like to port in a number, activate the service and then call Gen Mobile Customer Service at 1-833-528-1380.
                                            </p>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-12">
                                        <div class="col-sm-4" align="right"
                                             style="">
                                            <div class="form-group">
                                                <label class="required"></label>
                                            </div>
                                        </div>
                                        <div class="col-sm-5" align="left"
                                             style="padding-top:3px;color: red;">
                                                <p><strong>Activation</strong>: Dial ##25327# [talk] for iPhones or
                                                        ##72786# [talk] for Androids; and then dial ##873283# [talk]
                                                        to update your coverage.
                                                </p>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-12 marginbot10" style="margin-top: 16px;">
                                        <div class="col-md-4" align="right"></div>
                                        <div class="col-md-5 col-sm-5" align="right">
                                            <button type="button" class="btn btn-primary" style="margin-top: 16px;" onclick="request_activation()">
                                                Activate
                                            </button>
                                        </div>
                                        <div class="col-md-1"></div>

                                    </div>
                                </div>
                                <!-- End info box -->

                                @endif

                            </form>
                        </div>
                    </div>
                </div>

                {!! Helper::get_reminder('GEN Mobile') !!}

            <table class="parameter-product table-bordered table-hover table-condensed filter">
                <thead>
                <tr class="active">
                    <td><strong>ID</strong></td>
                    <td><strong>Type</strong></td>
                    <td><strong>Status</strong></td>
                    <td><strong>Note</strong></td>
                    <td><strong>Product</strong></td>
                    <td><strong>Denom($)</strong></td>
                    <td><strong>RTR.M</strong></td>
                    <td><strong>Total($)</strong></td>
                    <td><strong>Vendor.Fee($)</strong></td>
                    <td><strong>Action</strong></td>
                    <td><strong>SIM</strong></td>
                    <td><strong>ESN/IMEI</strong></td>
                    <td><strong>Pref.Area.Code</strong></td>
                    <td><strong>Phone/PIN</strong></td>
                    <td><strong>User.ID</strong></td>
                    <td><strong>Last.Updated</strong></td>
                </tr>
                </thead>
                <tbody>
                @if (isset($transactions) && count($transactions) > 0)
                    @foreach ($transactions as $o)
                        <tr>
                            <td>
                                @if ($o->status == 'C')
                                    <a target="_RECEIPT" href="/sub-agent/reports/receipt/{{ $o->id }}">{{ $o->id }}</a>
                                @else
                                    {{ $o->id }}
                                @endif
                            </td>
                            <td style="{{ $o->type_name == 'Void' ? 'color:red;' : '' }}">{{ $o->type_name }}</td>
                            @if ($o->status == 'R')
                                <td><a href="/sub-agent/reports/transaction/{{ $o->id }}">{!! $o->status_name() !!}</a></td>
                            @else
                                <td>{!! $o->status_name() !!}</td>
                            @endif
                            <td>
                                @if (in_array($o->product_id, ['WGENA', 'WGENOA','WGENTA','WGENTOA']))
                                    @php
                                        $gena = \App\Model\GenActivation::where('trans_id', $o->id)->first();
                                    @endphp

                                    @if (!empty($gena))
                                        MSL: {{ $gena->msl }} <br>
                                        MSID: {{ $gena->msid }} <br>
                                    @endif

                                @endif
                                @if (!empty($o->note))
                                    {{ $o->note }}
                                @else

                                @endif
                            </td>
                            <td>{{ $o->product_name  }}</td>
                            <td>${{ $o->denom }}</td>
                            <td>{{ $o->rtr_month }}</td>
                            <td style="{{ $o->type_name == 'Void' ? 'color:red;' : '' }}">${{ $o->collection_amt }}</td>
                            <td style="{{ $o->type_name == 'Void' ? 'color:red;' : '' }}">${{ $o->fee + $o->pm_fee}}</td>
                            <td>{{ $o->action }}</td>
                            <td>
                                @if (!empty($o->sim))
                                    {{ substr($o->sim, 0, 18) . 'XX' }}
                                    @php
                                        $sim_obj = \App\Model\StockSim::where('sim_serial', $o->sim)->where('product',
                                        $o->product_id)->first();
                                    @endphp
                                    <br> Type: {{ empty($sim_obj) ? 'BYOS' : $sim_obj->type_name }}
                                @endif
                            </td>
                            <td>{{ empty($o->esn) ? '' : substr($o->esn, 0, strlen($o->esn) - 2) . 'XX' }}</td>
                            <td>{{ $o->npa }}</td>
                            <td>{{ $o->action == 'PIN' ? Helper::mask_pin($o->pin) : $o->phone }}</td>

                            <td>{{ $o->created_by }}</td>
                            <td>{{ $o->last_updated }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="20" class="text-center">No Record Found</td>
                    </tr>
                @endif
                </tbody>

            </table>

            </div>
        </div>
    </div>
    <!-- End contain wrapp -->
@stop
