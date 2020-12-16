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

            $('#meid').keyup(function() {
                let length = $(this).val().length;
                $('#meid_count').text(length);
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
                        return;
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
                alert("Please Enter Sim");
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
            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/activate/gen/zip',
                data: {
                    zip: $('#zip').val(),
                    sim: $("#sim").val()
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
                                tr += '<td style="text-align:center;">$' + v.spiff + '</td>';
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
                $('#port_id_checked').val(true);
                myApp.showLoading();
                $.ajax({
                    url: '/sub-agent/activate/gen/get_portin_form_tmo',
                    type: 'get',
                    dataType: 'html',
                    cache: false,
                    success: function(res) {
                        myApp.hideLoading();
                        $('#port_in_box').empty();
                        $('#port_in_box').html(res);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        myApp.hideLoading();
                        myApp.showError(errorThrown);
                    }
                });
            } else {
                $('#port_in_box').empty();
            }
        }

        function get_commission() {
            $('#spiff_div').hide();

            var meid         = $('#meid').val();
            var sim         = $('#sim').val();
            var denom_id    = $('input[name=denom_id]:checked').val();

            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/activate/gen/commission_tmo',
                data: {
                    meid: meid,
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

                        // if (res.data.spiff_count > 0) {
                            $('#spiff_div').show();
                            $.each(res.data.spiff_labels, function(k, v) {
                                $('#spiff_label').append("<strong>" + v + "</strong><br>");
                            });
                        // }

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

            var is_port_in = $("#is_port_in").is(':checked');

            var sim         = $('#sim').val();
            var esn         = $('#meid').val();
            var zip         = $('#zip').val();
            var city        = $('#city').val();
            var state       = $('#state').val();
            var denom_id    = $('input[name=denom_id]:checked').val();
            var rtr_month   = $("input[name=rtr_month]:checked").val();
            // var first_name  = $('#first_name').val();
            // var last_name   = $('#last_name').val();
            // var address     = $('#address').val();

            if(sim.length < 1){
                alert("Please Insert SIM");
                return;
            }

            if(zip.length != 5){
                alert("Please Insert ZIP");
                return;
            }

            if (city.length < 1){
                alert('Please Insert City');
                return;
            }

            if (state.length < 1){
                alert('Please Select State');
                return;
            }

            if (!$("input[name=denom_id]").is(":checked")) {
                alert("Please select a plan to activate !!");
                return;
            }

            if (!$("input[name=rtr_month]").is(":checked")) {
                alert("Please select a activation month to activate !!");
                return;
            }

            var data = is_port_in ? {
                _token: '{{ csrf_token() }}',
                esn: esn,
                sim: sim,
                zip: zip,
                city: city,
                state: state,
                denom_id: denom_id,
                rtr_month: rtr_month,
                city: city,
                state: state,
                is_port_in: 'Y',
                p_number_to_port: $('#p_number_to_port').val(),
                p_account_no: $('#p_account_no').val(),
                p_account_pin: $('#p_account_pin').val(),
                p_first_name: $('#p_first_name').val(),
                p_last_name: $('#p_last_name').val(),
                p_account_address1: $('#p_account_address1').val(),
                p_account_city: $('#p_account_city').val(),
                p_account_state: $('#p_account_state').val(),
                p_account_zip: $('#p_account_zip').val()
            } : {
                _token: '{{ csrf_token() }}',
                esn: esn,
                sim: sim,
                zip: zip,
                city: city,
                state: state,
                denom_id: denom_id,
                rtr_month: rtr_month,
                city: city,
                state: state,
                is_port_in: 'N'
            };

            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/activate/gen/post_tmo',
                data: data,
                type: 'post',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();

                    if (res.code == '0') {
                        // alert(res.data.msg + ' [MDN: ' + res.data.mdn + ']');
                        window.location.href = '/sub-agent/activate/gen/success_tmo/' + res.data.id;
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

        function change_source() {

            var sim = $('#sim').val();

            if(sim.length < 1){
                alert("Please Insert SIM");
                return;
            }

            if(sim.length != 19){
                alert("Length Must be 19 Disigts");
                return;
            }

            $('#sim_label').empty();
            $('#meid_label').empty();
            $('#spiff_label').empty();

            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/activate/gen/sim_gen_tmo',
                data: {
                    sim: $('#sim').val()
                },
                type: 'get',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();
                    if (res.code === '0') {
                        $('#info_box').show();
                        $('#port_in_box').show();
                        publish_data(res.data);
                    } else {
                        alert(res.msg);
                        $('#info_box').hide();
                        // $('#sim').focus();
                        $('#meid').prop('readonly', false);
                        $('#sub_carrier').text('');
                        $('#plans_radio').empty();
                        return;
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });
        }

        function publish_data(data) {
            $('#sub_carrier').text(data.sub_carrier);
            $('#plans_radio').empty();
            $.each(data.plans, function(k, v) {
                $('#plans_radio').append('<div class="radio"><input type="radio" value="' + v.denom_id + '" name="denom_id" onclick="get_commission()"> $' + v.denom + ' (' + v.name + ')</div>');
            });

            $('#allowed_months_box').empty();
            $.each(data.allowed_months, function(k, v) {
                $('#allowed_months_box').append('<label><input type="radio" style="margin-bottom:5px;" class="radio-inline" id="rtr_month" name="rtr_month" value="' + v + '"> ' + v + ' Month &nbsp;&nbsp;</label>');
            });

            $('#sim').val(data.sim);
            $('#meid_box').show();

            if (data.meid != '') {
                $('#meid').val(data.meid);
                $('#meid').prop('readonly', true);
            } else {
                $('#meid').prop('readonly', false);
            }

            $('#sim_charge_label').text(data.sim_charge);
            if (data.sim_charge == 0) {
                $('#sim_charge_div').hide();
            } else {
                $('#sim_charge_div').show();
            }

            $('#sim_rebate_label').text(data.sim_rebate);
            if (data.sim_rebate == 0) {
                $('#sim_rebate_div').hide();
            } else {
                $('#sim_rebate_div').show();
            }

            $('#sim_consignment_charge_label').text(data.sim_consignment_charge);
            if (data.sim_consignment_charge == 0) {
                $('#sim_consignment_charge_div').hide();
            } else {
                $('#sim_consignment_charge_div').show();
            }
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
        $promotion = Helper::get_promotion('GEN Mobile TMO');
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
                                <a href="/sub-agent/activate/gen" style="cursor: pointer; color: #ffdd05;">Activation (SPR)</a>
                            </li>
                            <li>
                                <a href="/sub-agent/activate/gen_tmo" class="black-tab" >Activation (TMO)</a>
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
                                <input type="hidden" name="port_id_checked" id="port_id_checked" value="">

                                <div class="col-sm-2">
                                    <img src="/img/category-img-gen.jpg" style="width: 100%; margin-bottom: 16px;">

                                </div>
                                <label style="color: #ea0a8e; margin-left: -65%; margin-top: 6%">TMO</label>

                                @if (Helper::over_activation('GEN Mobile TMO') != '')
                                    <div class="col-sm-8">
                                    {!! Helper::over_activation('GEN Mobile TMO') !!}
                                    </div>
                                @else
                                <div class="col-sm-8">
                                    <div class="col-sm-4" align="right">
                                        <div class="form-group">
                                            <label class="required">Network</label>
                                        </div>
                                    </div>
                                    <div class="col-sm-7">
                                        <div class="form-group">
                                            <label style="color: #ffdd05"><input type="radio" name="network" onclick="window.location.href='/sub-agent/activate/gen';"/>  SPR </label> &nbsp;&nbsp;
                                            <label style="color: #ea0a8e"><input type="radio" name="network" checked/> TMO </label>&nbsp;&nbsp;
                                        </div>
                                        <label>Activation (TMO) GSM</label>
                                    </div>
                                </div>
                                <div class="col-sm-8">
                                    <div class="col-sm-4" align="right" style="">
                                        <div class="form-group">
                                            <label class="required">SIM</label>
                                        </div>
                                    </div>
                                    <div class="col-sm-5" align="right">
                                        <div class="form-group">
                                            <input type="text" class="form-control"
                                                   id="sim"
                                                   name="sim"
                                                   value=""
                                                   maxlength="19"
                                                   placeholder="19 digits and digits only"
                                                   onblur="change_source('sim')"/>
                                            <div id="count" align="left" style="color: red;
                                                            font-size: 12px;
                                                            margin-left: 10px;">
                                                You have entered in <span id="sim_count" style="font-weight: bold;">0</span> Digits
                                            </div>
                                            <div id="error_msg_sim"></div>
                                        </div>
{{--                                        <div class="divider2"></div>--}}
                                    </div>
                                    <div class="col-sm-3" align="left">
                                        <a class="btn btn-info btn-xs">
                                            Enter
                                        </a>
                                    </div>
                                </div>
                                    <div class="divider2"></div>

                                <div class="col-sm-8" id="meid_box">
                                    <div class="col-sm-7" align="right" style="">
                                        <div class="form-group">
                                            <label class="required">IMEI</label>
                                        </div>
                                    </div>
                                    <div class="col-sm-5" align="right">
                                        <div class="form-group">
                                            <input type="text" class="form-control"
                                                   id="meid"
                                                   name="meid"
                                                   value=""
                                                   maxlength="20"
                                                   placeholder=""
                                                   />
                                            <div id="count" align="left" style="color: red;
                                                            font-size: 12px;
                                                            margin-left: 10px;">
                                                You have entered in <span id="meid_count" style="font-weight: bold;">0</span> Digits
                                            </div>
                                            <span style="font-size: 12px; margin-left: 10px">  Enter in IMEI for Maximize activation bonus.</span>
                                            <div id="error_msg_meid"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="divider2"></div>

                                <div id="info_box" style="display: none;">

                                    <div class="col-sm-8">
                                        <div class="col-sm-7" align="right"
                                             style="">
                                            <div class="form-group">
                                                <label class="required">Zip Code</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-4" align="right"
                                             style="">
                                            <div class="form-group">
                                                <input type="text" class="form-control"
                                                       id="zip"
                                                       name="zip"
                                                       value=""
                                                       maxlength="5"
                                                       placeholder=""
                                                />
                                                <div id="error_msg_zip"></div>
                                            </div>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-8">
                                        <div class="col-sm-7" align="right">
                                            <div class="form-group">
                                                <label class="required">City</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-4" align="right">
                                            <div class="form-group">
                                                <input type="text" class="form-control"
                                                       id="city"
                                                       name="city"
                                                       value=""/>
                                                <div id="error_msg_city"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-sm-8">
                                        <div class="col-sm-7" align="right">
                                            <div class="form-group">
                                                <label class="required">State</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-4" align="right">
                                            <div class="form-group">
                                                <select class="form-control" id="state"
                                                        name="state"
                                                        data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                                    <option value="">Please Select</option>
                                                    @if (isset($states))
                                                        @foreach ($states as $o)
                                                            <option value="{{ $o->code }}">{{ $o->name }}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-sm-8" id="plans_box" style="margin-top: 16px;">
                                        <div class="col-sm-7" align="right"
                                             style="">
                                            <div class="form-group">
                                                <label class="required">Product</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-5" align="left">
                                            <label style="background-color: #6600ff; border:solid 1px black; color: white; padding: 2px 10px 2px 10px;min-width: 160px;text-align: center" id="sub_carrier"></label>
                                            <div class="form-group" id="plans_radio" style="margin-left: 20px;">
                                            </div>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-8" id="spiff_div" style="margin-top: 16px;">
                                        <div class="col-sm-7" align="right"
                                             style="">
                                            <div class="form-group">
                                                <label class="required">Spiff</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-4" align="left">
                                            <label style="display: none;"><span id="sim_label"></span></label>
                                            <div id="spiff_label">&nbsp;
                                            </div>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-8" id="sim_charge_div" style="display: none;">
                                        <div class="col-sm-7" align="right"
                                             style="">
                                            <div class="form-group">
                                                <label class="required">Sim Charge</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-4" align="left">
                                            <label>$ <span id="sim_charge_label"></span></label>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-8" id="sim_rebate_div" style="display: none;">
                                        <div class="col-sm-7" align="right"
                                             style="">
                                            <div class="form-group">
                                                <label class="required">Sim Rebate</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-4" align="left">
                                            <label>$ <span id="sim_rebate_label"></span></label>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-8" id="esn_charge_div" style="display: none;">
                                        <div class="col-sm-7" align="right"
                                             style="">
                                            <div class="form-group">
                                                <label class="required">ESN Charge</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-4" align="left">
                                            <label>$ <span id="esn_charge_label"></span></label>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-8" id="esn_rebate_div" style="display: none;">
                                        <div class="col-sm-7" align="right"
                                             style="">
                                            <div class="form-group">
                                                <label class="required">ESN Rebate</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-4" align="left">
                                            <label>$ <span id="esn_rebate_label"></span></label>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-8" id="rebate_div" style="display: none;">
                                        <div class="col-sm-7" align="right"
                                             style="">
                                            <div class="form-group">
                                                <label class="required">Rebate</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-4" align="left">
                                            <label id="meid_label"></label>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-8" id="sim_consignment_charge_div" style="display: none;">
                                        <div class="col-sm-7" align="right"
                                             style="">
                                            <div class="form-group">
                                                <label class="required">Consignment Charge</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-4" align="left">
                                            <label style="color:orange;">$ (<span id="sim_consignment_charge_label"></span>)</label>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-8" id="activation_fee_box_body" style="display: none;">
                                        <div class="col-sm-7" align="right">
                                            <div class="form-group">
                                                <label class="required">Regulatory Recovery Fee</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-4" align="left">
                                            <div class="form-group">
                                                $<span id="activation_fee_box">0.00</span>
                                            </div>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-8">
                                        <div class="col-sm-7" align="right">
                                            <label class="required">Activation.Month</label>
                                        </div>
                                        <div class="col-sm-4">

                                            <div class="form-group">
                                                <div id="allowed_months_box">
                                                </div>
                                            </div>
                                            <div class="divider2"></div>
                                        </div>

                                        <div class="col-sm-2"></div>
                                    </div>

{{--                                    <div class="col-sm-8">--}}
{{--                                        <div class="col-sm-7" align="right">--}}
{{--                                            <div class="form-group">--}}
{{--                                                <label class="required">First Name</label>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                        <div class="col-sm-4" align="right">--}}
{{--                                            <div class="form-group">--}}
{{--                                                <input type="text" class="form-control"--}}
{{--                                                       id="first_name"--}}
{{--                                                       name="first_name"--}}
{{--                                                       value=""/>--}}
{{--                                                <div id="error_msg_first_name"></div>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}

{{--                                    <div class="col-sm-8">--}}
{{--                                        <div class="col-sm-7" align="right">--}}
{{--                                            <div class="form-group">--}}
{{--                                                <label class="required">Last Name</label>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                        <div class="col-sm-4" align="right">--}}
{{--                                            <div class="form-group">--}}
{{--                                                <input type="text" class="form-control"--}}
{{--                                                       id="last_name"--}}
{{--                                                       name="last_name"--}}
{{--                                                       value=""/>--}}
{{--                                                <div id="error_msg_last_name"></div>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}

{{--                                    <div class="col-sm-8">--}}
{{--                                        <div class="col-sm-7" align="right">--}}
{{--                                            <div class="form-group">--}}
{{--                                                <label class="required">Address</label>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                        <div class="col-sm-4" align="right">--}}
{{--                                            <div class="form-group">--}}
{{--                                                <input type="text" class="form-control"--}}
{{--                                                       id="address"--}}
{{--                                                       name="address"--}}
{{--                                                       value=""/>--}}
{{--                                                <div id="error_msg_address"></div>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}

                                    <div class="col-sm-8">
                                        <div class="col-sm-7" align="right" style="">
                                            <div class="form-group">
                                                <label class="required">Port-In ?</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-5" align="left" style="">
                                            <div class="form-group">
                                                <input type="checkbox" name="is_port_in" id="is_port_in" onclick="portin_checked()">
                                                I'd like to transfer from my current phone number.
                                                <div id="error_msg_zip_code"></div>
                                            </div>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-8" id="port_in_box">
                                    </div>

                                    <div class="col-sm-12 marginbot10" style="margin-top: 16px;">
                                        <div class="col-md-4" align="right"></div>
                                        <div class="col-md-4" align="right">
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

            </div>
            {!! Helper::get_reminder('GEN Mobile TMO') !!}

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
