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

            $('#afcode').keyup(function() {
                let length = $(this).val().length;
                $('#afcode_count').text(length);
            });

            $('#sim').keyup(function() {
                let length = $(this).val().length;
                $('#sim_count').text(length);
            });

            $('#esn').keyup(function() {
                let length = $(this).val().length;
                $('#esn_count').text(length);
            });

            $('#imei').keyup(function() {
                let length = $(this).val().length;
                $('#imei_count').text(length);
            });

            $('[data-toggle="popover-hover"]').popover({
                html: true,
                trigger: 'hover',
                content: function () { return '<img src="' + $(this).data('img') + '" />'; }
            });
        };

        function change_source_sim() {

            var type = 'sim';
            var afcode = $('#afcode').val();
            var code = $('#sim').val();

            if(code.length != 20){
                alert("Length Must be 20 Disigts");
                return;
            }

            $('#sim_label').empty();
            $('#imei_label').empty();
            $('#esn_label').empty();
            $('#spiff_label').empty();

            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/activate/freeup/sim/' + type,
                data: {
                    code: code,
                    afcode : afcode,
                    sim: $('#sim').val()
                },
                type: 'get',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();
                    if (res.code === '0') {
                        $('#info_box').show();
                        publish_data(res.data);

                        $('#afcode').attr('disabled','disabled');
                        // if($('#afcode').val().length < 1){
                        //     $('#afcode').attr("placeholder", "You can SKIP here.");
                        // }
                        // let length = $('#afcode').val().length;
                        // $('#afcode_count').text(length);
                        $('#imei').focus();
                    } else {
                        alert(res.msg);
                        $('#info_box').hide();
                        $('#' + type).focus();
                        $('#esn_box').show();
                        $('#sub_carrier').text('');
                        $('#plans_radio').empty();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });
        }

        // function change_source_afcode() {
        //
        //     var type = 'afcode';
        //     var code = $('#afcode').val();
        //
        //     $('#sim_label').empty();
        //     $('#imei_label').empty();
        //     $('#esn_label').empty();
        //     $('#spiff_label').empty();
        //
        //     myApp.showLoading();
        //     $.ajax({
        //         url: '/sub-agent/activate/freeup/sim/' + type,
        //         data: {
        //             code: code,
        //             sim: $('#sim').val()
        //         },
        //         type: 'get',
        //         dataType: 'json',
        //         cache: false,
        //         success: function(res) {
        //             myApp.hideLoading();
        //             if (res.code === '0') {
        //                 $('#info_box').show();
        //                 $('#sim').focus();
        //                 publish_data(res.data);
        //                 $('#sim').attr('disabled','disabled');
        //                 let length = $('#sim').val().length;
        //                 $('#sim_count').text(length);
        //             } else {
        //                 alert(res.msg);
        //                 $('#info_box').hide();
        //                 $('#esn_box').show();
        //                 $('#sub_carrier').text('');
        //                 $('#plans_radio').empty();
        //                 $('#sim').focus();
        //             }
        //         },
        //         error: function(jqXHR, textStatus, errorThrown) {
        //             myApp.hideLoading();
        //             myApp.showError(errorThrown);
        //         }
        //     });
        // }

        function publish_data(data) {
            $('#sub_carrier').text(data.sub_carrier);
            $('#plans_radio').empty();
            $.each(data.plans, function(k, v) {
                if(v.denom == 40){
                    $('#plans_radio').append('<div class="radio" style="color: blue;"><input type="radio" value="' + v.denom_id + '" name="denom_id" onclick="get_commission()"> $' + v.denom + ' (' + v.name + ')</div>');
                }else {
                    $('#plans_radio').append('<div class="radio"><input type="radio" value="' + v.denom_id + '" name="denom_id" onclick="get_commission()"> $' + v.denom + ' (' + v.name + ')</div>');
                }
            });

            $('#allowed_months_box').empty();
            $.each(data.allowed_months, function(k, v) {
                $('#allowed_months_box').append('<label><input type="radio" style="margin-bottom:5px;" class="radio-inline" id="rtr_month" name="rtr_month" value="' + v + '"> ' + v + ' Month &nbsp;&nbsp;</label>');
            });

            if (data.sub_carrier == 'FreeUP') {
                $('#afcode').val(data.afcode);
                $('#sim').val(data.sim);
                $('#imei_box').show();

                if (data.imei != '') {
                    $('#imei').val(data.imei);
                    $('#imei').prop('readonly', true);
                } else {
                    $('#imei').prop('readonly', false);
                }

                $('#esn_box').hide();
            } else {
                $('#imei_box').hide();
                $('#esn_box').show();
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

        function portin_checked() {
            var checked = $("#is_port_in").is(':checked');

            if (checked) {
                myApp.showLoading();
                $.ajax({
                    url: '/sub-agent/activate/freeup/get_portin_form/' + $('#sub_carrier').text(),
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

            // Get Commission
            get_commission();
        }

        function request_activation() {
            $("div[id^='error_msg_']").empty();

            if (!$("input[name=denom_id]").is(":checked")) {
                alert("Please select a plan to activate !!");
                return;
            }
            
            var is_port_in = $("#is_port_in").is(':checked');

            var afcode      = $('#afcode').val();
            var sim         = $('#sim').val();
            var esn         = $('#esn').val();
            var handset_os  = $('#handset_os').val();
            var imei        = $('#imei').val();
            var zip_code    = is_port_in ? $('#account_zip').val() : $('#zip_code').val();
            var sub_carrier = $('#sub_carrier').text();
            var denom_id    = $('input[name=denom_id]:checked').val();

            if (zip_code.toString().length != 5) {
                alert("Please input valid zip code !!");
                if (is_port_in) {
                    $('#account_zip').focus();
                } else {
                    $('#zip_code').focus();
                }
                return;
            }

            var data = is_port_in ? {
                    _token: '{{ csrf_token() }}',
                    afcode: afcode,
                    sim: sim,
                    esn: esn,
                    handset_os: handset_os,
                    imei: imei,
                    zip_code: zip_code,
                    sub_carrier: sub_carrier,
                    denom_id: denom_id,
                    is_port_in: 'Y',
                    note: $('#note').val(),
                    equipment_type: $('#equipment_type').val(),
                    number_to_port: $('#number_to_port').val(),
                    account_no: $('#account_no').val(),
                    account_pin: $('#account_pin').val(),
                    first_name: $('#first_name').val(),
                    last_name: $('#last_name').val(),
                    address1: $('#address1').val(),
                    address2: $('#address2').val(),
                    city: $('#city').val(),
                    state: $('#state').val(),
                    call_back_phone: $('#call_back_phone').val(),
                    email: $('#email').val()
                } : {
                    _token: '{{ csrf_token() }}',
                    afcode: afcode,
                    sim: sim,
                    esn: esn,
                    handset_os: handset_os,
                    imei: imei,
                    zip_code: zip_code,
                    sub_carrier: sub_carrier,
                    denom_id: denom_id,
                    is_port_in: 'N'
                };

            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/activate/freeup/post',
                data: data,
                type: 'post',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();

                    if (res.code == '0') {
                        // alert(res.data.msg);
                        window.location.href = '/sub-agent/activate/freeup/success/' + res.data.id;
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

                        myApp.showError(error_msg);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });
            
        }

        function get_commission() {

            $('#sim_label').empty();
            $('#imei_label').empty();
            $('#esn_label').empty();

            var sub_carrier = $('#sub_carrier').text();
            var sim         = $('#sim').val();
            var esn         = sub_carrier == 'ATT' ? $('#imei').val() : $('#esn').val();
            var denom_id    = $('input[name=denom_id]:checked').val();

            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/activate/freeup/commission',
                data: {
                    sim: sim,
                    esn: esn,
                    denom_id: denom_id,
                    is_port_in: $("#is_port_in").is(':checked') ? 'Y' : 'N'
                },
                type: 'get',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();
                    if (res.code === '0') {
                        // $('#sim_label').text(res.data.sim_label);
                        if (res.data.esn_label == '') {
                            $('#rebate_div').hide();
                        } else {
                            $('#rebate_div').show();
                            $('#imei_label').text(res.data.esn_label);
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

                        $('#spiff_label').empty();

                        $.each(res.data.spiff_labels, function(k, v) {
                            $('#spiff_label').append("<strong>" + v + "</strong><br>");
                        });

                    } else {
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });

        }

        // function print_invoice(id) {

        //     myApp.showLoading();
        //     $.ajax({
        //         url: '/sub-agent/activate/freeup/invoice/' + id,
        //         type: 'get',
        //         dataType: 'html',
        //         cache: false,
        //         success: function(res) {
        //             myApp.hideLoading();
        //             $('#activate_invoice_body').empty();
        //             $('#activate_invoice_body').html(res);
        //             $('#activate_invoice').modal();
        //         },
        //         error: function(jqXHR, textStatus, errorThrown) {
        //             myApp.hideLoading();
        //             myApp.showError(errorThrown);
        //         }
        //     });
        // }

        function printDiv() {
            window.print();
        }

        function freeup_recharge_rtr() {
            $('#frm_freeup_rtr').submit();
        }

        function freeup_recharge_pin() {
            $('#frm_freeup_pin').submit();
        }

        function freeup_transactions() {
            $('#frm_freeup_transaction').submit();
        }

    </script>


    @if (!empty($trans))
    <div id="activate_invoice" class="modal fade " tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false"
         style="display:block;">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Activate / Port-In Success</h4>
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
                    @if(!empty(session('esn')))
                        <div class="row">
                            <div class="col-sm-4">ESN</div>
                            <div class="col-sm-8">{{ $trans->esn }}</div>
                        </div>
                    @endif
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
                        <h4>Activation/Port-In</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li class="active">Activation/Port-In</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End parallax -->

    @php
        $promotion = Helper::get_promotion('FreeUP');
    @endphp
    @if (!empty($promotion))
        <div class="news-headline no-print">
{{--            <marquee behavior="scroll" direction="left" onmouseover="this.stop();" onmouseout="this.start();">--}}
                {!!$promotion !!}
{{--            </marquee>--}}
        </div>
    @endif

    <!-- Start Transactions -->
    <div class="container no-print">
        <!--table class="parameter-product table-bordered table-hover table-condensed filter" style="margin-bottom:0px;">
            <thead>
            <tr class="active">
                <td><strong>Carrier</strong></td>
                <td><strong>Product</strong></td>
                <td><strong>Amt($)</strong></td>
                <td><strong>SIM</strong></td>
                <td><strong>ESN/IMEI</strong></td>
                <td><strong>Pref.Area.Code</strong></td>
                <td><strong>Phone</strong></td>
                <td><strong>Status</strong></td>
                <td><strong>Note</strong></td>
                <td><strong>User.ID</strong></td>
                <td><strong>Created.At</strong></td>
            </tr>
            </thead>
            <tbody>
            @if (isset($transactions) && count($transactions) > 0)
                @foreach ($transactions as $o)
                    <tr>
                        <td>{{ $o->carrier() }}</td>
                        <td>{{ $o->product_name() }}</td>
                        <td>{{ $o->denom }}</td>
                        <td>{{ $o->sim }}</td>
                        <td>{{ $o->esn }}</td>
                        <td>{{ $o->npa }}</td>
                        <td>{{ $o->phone }}</td>

                        @if ($o->status == 'R')
                            <td><a href="/sub-agent/reports/transaction/{{ $o->id }}">{!! $o->status_name() !!}</a></td>
                        @else
                            <td>{!! $o->status_name() !!}</td>
                        @endif
                        <td>
                            @if (!empty($o->note))

                                <input type="checkbox" checked onclick="return false;" class="note-check-box"
                                       data-toggle="tooltip" data-placement="top" title="{{ $o->note }}"/>
                            @else

                            @endif
                        </td>
                        <td>{{ $o->created_by }}</td>
                        <td>{{ Carbon\Carbon::parse($o->cdate)->format('m/d/Y') }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="20" class="text-center">No Record Found</td>
                </tr>
            @endif
            </tbody>
        </table-->
        <!--marquee behavior="scroll" direction="left" onmouseover="this.stop();"
                 onmouseout="this.start();">{!! Helper::get_promotion('ROK') !!}</marquee-->
    </div>
    <!-- End Transactions -->

    <form id="frm_freeup_rtr" method="post" action="/sub-agent/rtr/domestic">
        {!! csrf_field() !!}
        <input type="hidden" name="carrier" value="FreeUP">
    </form>

    <form id="frm_freeup_pin" method="post" action="/sub-agent/pin/domestic">
        {!! csrf_field() !!}
        <input type="hidden" name="carrier" value="FreeUP">
    </form>

    <form id="frm_freeup_transaction" method="post" action="/sub-agent/reports/transaction">
        {!! csrf_field() !!}
        <input type="hidden" name="carrier" value="FreeUP">
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
                                <a href="/sub-agent/activate/freeup" class="black-tab">FreeUP</a>
                            </li>
                            <li>
                                <a onclick="freeup_recharge_rtr()" style="cursor: pointer;">FreeUP RTR</a>
                            </li>
                            <li>
                                <a onclick="freeup_recharge_pin()" style="cursor: pointer;">FreeUP PIN</a>
                            </li>
                            <li>
                                <a onclick="freeup_transactions()" style="cursor: pointer;">FreeUP Transactions</a>
                            </li>
                            <li>
                                <a href="/sub-agent/tools/freeup" style="cursor: pointer;">FreeUP Tools</a>
                            </li>
                            <li>
                                <a href="#" onclick="freeup_sim_order()">SIM Order</a>
                            </li>
                            <li>
                                <a href="#" onclick="freeup_device_order()">Device Order</a>
                            </li>
                            <li>
                                <a href="/sub-agent/reports/vr-request">Order History</a>
                            </li>
                        </ul>

                        <script>
                            function freeup_sim_order() {
                                $('#frm_freeup_sim_order').submit();
                            }
                            function freeup_device_order() {
                                $('#frm_freeup_device_order').submit();
                            }
                        </script>

                        <form id="frm_freeup_sim_order" class="form-horizontal" method="post"
                              action="/sub-agent/virtual-rep/shop">
                            {{ csrf_field() }}
                            <input type="hidden" name="category" value="SIM">
                            <input type="hidden" name="sub_category" value="INSTANT SIM">
                            <input type="hidden" name="carrier" value="FREEUP">
                        </form>

                        <form id="frm_freeup_device_order" class="form-horizontal" method="post"
                              action="/sub-agent/virtual-rep/shop">
                            {{ csrf_field() }}
                            <input type="hidden" name="category" value="DEVICE">
                            <input type="hidden" name="carrier" value="FREEUP">
                        </form>

                        <!-- Tab panes -->
                        <div class="tab-content" style="padding-top:36px;">
                            <form id="frm_act" method="post" class="row marginbot15">
                                {!! csrf_field() !!}
                                <div class="col-sm-2">
                                    <img src="/img/freeup_main.jpg" style="width: 100%; margin-bottom: 16px;">
                                </div>

                                @if (Helper::over_activation('FreeUP') != '')
                                    <div class="col-sm-8">
                                    {!! Helper::over_activation('FreeUP') !!}
                                    </div>
                                @else

                                <div class="col-sm-8">
                                    <div class="col-sm-4" align="right"
                                         style="">
                                        <div class="form-group">
                                            <label class="required">SIM</label>
                                        </div>
                                    </div>
                                    <div class="col-sm-4" align="right"
                                         style="padding-top:3px;">
                                        <div class="form-group">
                                            <input type="text" class="form-control"
                                                    id="sim"
                                                    name="sim"
                                                    value=""
                                                    maxlength="20"
                                                    placeholder="20 digits and digits only"
                                                    onchange="change_source_sim()"
                                                   />
                                            <div id="count" align="left" style="color: red;
                                                        font-size: 12px;
                                                        margin-left: 10px;">
                                                You have entered in <span id="sim_count" style="font-weight: bold;">0</span> Digits
                                            </div>
                                            <div id="error_msg_sim"></div>
                                        </div>
                                    </div>
                                    <div class="col-sm-3" align="left"
                                         style="">
                                         <a class="btn btn-info btn-xs">
                                             Enter
                                         </a>
                                    </div>
                                </div>

                                <div id="info_box" style="display: none;">

                                    <div class="col-sm-12" id="imei_box">
                                        <div class="col-sm-4" align="right"
                                             style="">
                                            <div class="form-group">
                                                <label class="required">IMEI</label>
                                            </div>
                                        </div>
                                         <div class="col-sm-5" align="right"
                                             style="">
                                            <div class="form-group">
                                                <input type="text" class="form-control"
                                                        id="imei"
                                                        name="imei"
                                                        value=""
                                                        maxlength="20"
                                                        placeholder="15 or 16 digit IMEI of the handset."
                                                        onchange="get_commission()"
                                                       />
                                                <div id="count" align="left" style="color: red;
                                                        font-size: 12px;
                                                        margin-left: 10px;">
                                                    You have entered in <span id="imei_count" style="font-weight: bold;">0</span> Digits
                                                </div>
                                                <span style="font-size: 12px; margin-left: 10px">  Enter in IMEI for Maximize activation bonus.</span>
                                                <div id="error_msg_imei"></div>
                                            </div>
                                        </div>
                                        <div class="col-sm-3" align="right"
                                             style="">
                                        </div>
                                    </div>

                                    <div class="col-sm-12" style="margin-top: 16px;">
                                        <div class="col-sm-4" align="right">
                                            <div class="form-group">
                                                <label class="required">Handset OS</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-5">
                                            <div class="form-group">
                                                <select class="form-control" id="handset_os" name="handset_os">
                                                    <option value="">Select Handset</option>
                                                    <option value="IOS">iOS</option>
                                                    <option value="ANDROID">Android</option>
                                                    <option value="OTHER">Other</option>
                                                </select>
                                                <div id="error_msg_handset_os"></div>
                                            </div>
                                        </div><div class="col-sm-2"></div>
                                    </div>

                                    <div class="col-sm-12">
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
                                                        id="zip_code"
                                                        name="zip_code"
                                                        value=""
                                                        maxlength="20"
                                                        placeholder=""
                                                       />
                                                <div id="error_msg_zip_code"></div>
                                            </div>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-12" id="plans_box" style="margin-top: 16px;">
                                        <div class="col-sm-4" align="right"
                                             style="">
                                            <div class="form-group">
                                                <label class="required">Product</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-5" align="left">
                                             <label style="background-color: #D32023; border:solid 1px black; color: white; padding: 2px 10px 2px 10px;min-width: 160px;text-align: center" id="sub_carrier"></label>
                                            <div class="form-group" id="plans_radio" style="margin-left: 20px;">
                                            </div>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-12" id="spiff_div" style="margin-top: 16px;">
                                        <div class="col-sm-4" align="right"
                                             style="">
                                            <div class="form-group">
                                                <label class="required">Spiff</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-5" align="left">
                                            <label style="display: none;"><span id="sim_label"></span></label>
                                            <div id="spiff_label">
                                            </div>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-12" id="sim_charge_div" style="margin-top: 16px;display: none;">
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

                                    <div class="col-sm-12" id="sim_rebate_div" style="margin-top: 16px;display: none;">
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

                                    <div class="col-sm-12" id="esn_charge_div" style="margin-top: 16px;display: none;">
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

                                    <div class="col-sm-12" id="esn_rebate_div" style="margin-top: 16px;display: none;">
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

                                    <div class="col-sm-12" id="rebate_div" style="margin-top: 16px;display: none;">
                                        <div class="col-sm-4" align="right"
                                             style="">
                                            <div class="form-group">
                                                <label class="required">Rebate</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-5" align="left">
                                            <label id="imei_label"></label>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-12" id="sim_consignment_charge_div" style="display: none;">
                                        <div class="col-sm-4" align="right"
                                             style="">
                                            <div class="form-group">
                                                <label class="required">Consignment Charge</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-5" align="left">
                                            <label style="color:orange;">$ (<span id="sim_consignment_charge_label"></span>)</label>
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
                                                        <input type="checkbox" id="is_port_in" onclick="portin_checked()">I'd like to port-in my old phone number.
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

                {!! Helper::get_reminder('FreeUP') !!}

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
                                    @if (!empty($o->note))
                                        @if ($o->status == 'C')
                                            Success
                                        @else
                                            {{ $o->note }}
                                        @endif
                                    @else
                                        -
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
                                    {{ $o->sim }} 
                                    @php
                                        $sim_obj = \App\Model\StockSim::where('sim_serial', $o->sim)->where('product',
                                        $o->product_id)->first();
                                    @endphp
                                    <br> Type: {{ empty($sim_obj) ? 'BYOS' : $sim_obj->type_name }}
                                    @endif
                                </td>
                                <td>{{ $o->esn }}</td>
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
