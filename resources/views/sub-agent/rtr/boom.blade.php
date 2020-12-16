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


            $('#network_blue').change(function () {
                $('#phone_number_box').show();
            })
            $('#network_red').change(function () {
                $('#phone_number_box').show();
            })
            $('#network_purple').change(function () {
                $('#phone_number_box').show();
            })

        };

        function check_mdn() {
            $('#plans_box').empty();
            $('#plan_code').val('');
            $('#processing_fee_text').text('');
            $('#current_plan_amount').text('');
            $('#current_plan_name').text('');

            var network = $('input[name=network]:checked').val();

            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/rtr/boom/check_mdn',
                data: {
                    mdn: $('#mdn').val(),
                    network: network
                },
                type: 'get',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();
                    if (res.code === '0') {
                        $('#info_box').show();
                        $('#expired_box').show();
                        $('#expired_date').val('');
                        $('#pin_box').show();
                        $('#current_plan_box').empty();
                        $('#plans_box').empty();
                        $('#plan_code').val(res.plancode);
                        var processing_fee = 0;
                        if(res.processing_fee !== undefined){
                            processing_fee = parseFloat(res.processing_fee);
                        }
                        $('#processing_fee').val(processing_fee);
                        $('#processing_fee_text').text(processing_fee.toFixed(2));
                        $('#plan_amount_text').text((0).toFixed(2));
                        $('#total_amount_text').text((0).toFixed(2));

                        var current_plan_selected = false;
                        $.each(res.denoms, function(k, v) {
                            var checked = '';
                            if (res.plancode == v.rtr_pid) {
                                plan_selected_processing_fee(v.denom_id);
                                checked = 'checked';
                            }
                            var tr = '<tr>';
                            tr += '<td style="text-align:center;"><input type="radio" value="' + v.denom_id + '" ' +
                                'name="denom_id" onclick="plan_selected_processing_fee(' + v.denom_id + ')" ' + checked + '></td>';
                            tr += '<td style="text-align:center;">$' + v.denom + '</td>';
                            tr += '<td>' + v.name + '</td>';
                            tr += '</tr>';
                            if (res.plancode == v.rtr_pid) {
                                $('#current_plan_box').append(tr);
                                current_plan_selected = true;
                            } else {
                                $('#plans_box').append(tr);
                            }
                        });

                        if (!current_plan_selected) {
                            $('#current_plan_box').append("<tr><td colspan='3'>Please Select New Plan</td></tr>");
                        }

                        if (res.reload_date != ''){

                            if(res.renew_now != 'Y'){
                                $('#renew_now').show();
                            }

                            $('#expired_date').text(res.reload_date);
                        }else{

                            $('#renew_now').hide();
                            $('#expired_date').text('There is no Expired Date Info.');
                        }

                        $("#network_blue").prop("disabled", true);
                        $("#network_red").prop("disabled", true);
                        $("#network_purple").prop("disabled", true);

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

        function plan_selected(denom) {

            var processing_fee = $('#processing_fee').val();

            denom = parseFloat(denom);
            var total_amt = denom + parseFloat(processing_fee);

            $('#plan_amount_text').text(denom.toFixed(2));
            $('#total_amount_text').text(total_amt.toFixed(2));
        }

        function plan_selected_processing_fee(denom_id) {
            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/rtr/boom/get_processing_fee',
                data: {
                    denom_id: denom_id
                },
                type: 'get',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();
                    if (res.code === '0') {
                        var denom = parseFloat(res.data.denom);
                        var processing_fee = parseFloat(res.data.processing_fee);
                        var total_amt = denom + processing_fee;

                        $('#plan_amount_text').text(denom.toFixed(2));
                        $('#processing_fee_text').text(processing_fee.toFixed(2));
                        $('#total_amount_text').text(total_amt.toFixed(2));

                        $('#plan_code').val(res.data.plan_id);

                        // Hidden and fixed to False with $60
                        if(denom == 60){
                            $("#renew_yes").prop("checked", false);
                            $("#renew_no").prop("checked", true);
                            $("#renew_yes").attr("disabled", true);
                        }else{
                            $("#renew_yes").prop("checked", true);
                            $("#renew_no").prop("checked", false);
                            $("#renew_yes").attr("disabled", false);
                        }
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


        function confirm_order() {

            var msg = '<h4>REFILL PURCHASE POLICY - Please Note</h4>';
            msg += '<p>';
            msg += '<ul>';
            msg += '    <li>All REFILL (Real Time Replenishments) sales are final.</li>';
            msg += '    <li>This product is NON-REFUNDABLE. Please be sure to print the customer receipt.</li>';
            msg += '</ul>';
            msg += '</p>';

            myApp.showConfirm(msg, function() {
                myApp.showLoading();
                recharge_submit();
            });
        }

        function recharge_submit() {

            if (!$("input[name=denom_id]").is(":checked")) {
                alert("Please select a plan to activate !!");
                return;
            }
            var renew       = $('input[name=renew]:checked').val();
            var denom_id    = $('input[name=denom_id]:checked').val();
            var network     = $('input[name=network]:checked').val();

            var data = {
                _token: '{{ csrf_token() }}',
                mdn: $('#mdn').val(),
                denom_id: denom_id,
                plan_code: $('#plan_code').val(),
                renew: renew,
                network: network,
                pin: $('#pin').val()
            };

            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/rtr/boom/post',
                data: data,
                type: 'post',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();

                    if (res.code == '0') {
                        // alert(res.data.msg + ' [MDN: ' + res.data.mdn + ']');
                        window.location.href = '/sub-agent/rtr/boom/success/' + res.data.id;
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
                        <h4 class="modal-title">Refill Success</h4>
                    </div>
                    <div class="modal-body receipt" id="activate_invoice_body">
                        <p>
                            Your request is being processed.<br/>
                            Please refer to "Reports -> Transaction" for more information.
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
                            <div class="col-sm-4">Sub Total</div>
                            <div class="col-sm-8">${{ number_format($trans->denom, 2) }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Fee</div>
                            <div class="col-sm-8">${{ number_format($trans->fee + $trans->pm_fee, 2) }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Total</div>
                            <div class="col-sm-8">${{ number_format($trans->denom + $trans->fee + $trans->pm_fee, 2) }}</div>
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
                        <h4>Boom Refill</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li class="active">Boom Refill</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End parallax -->

    @php
        $promotion = Helper::get_promotion('Boom Red');
    @endphp
    @if (!empty($promotion))
        <div class="news-headline no-print">
                {!!$promotion !!}
        </div>
    @endif

    <div class="form-group" style="margin-bottom:0px;">
        {!! Helper::get_reminder_refill() !!}
    </div>

    <form id="frm_boom_rtr" method="post" action="/sub-agent/rtr/domestic">
        {!! csrf_field() !!}
        <input type="hidden" name="carrier" value="Boom Mobile">
    </form>

    <form id="frm_boom_transaction" method="post" action="/sub-agent/reports/transaction">
        {!! csrf_field() !!}
        <input type="hidden" name="carrier" value="Boom Mobile">
    </form>

    <form id="frm_boom_sim_order" class="form-horizontal" method="post"
          action="/sub-agent/virtual-rep/shop">
        {{ csrf_field() }}
        <input type="hidden" name="category" value="SIM">
        <input type="hidden" name="sub_category" value="INSTANT SIM">
        <input type="hidden" name="carrier" value="Boom Mobile">
    </form>

    <form id="frm_boom_device_order" class="form-horizontal" method="post"
          action="/sub-agent/virtual-rep/shop">
        {{ csrf_field() }}
        <input type="hidden" name="category" value="DEVICE">
        <input type="hidden" name="carrier" value="Boom Mobile">
    </form>

    <!-- Start contain wrapp -->
    <div class="contain-wrapp padding-bot70 no-print" style="">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-sm-12">
                    <div class="clearfix"></div>

                    <div class="tabbable tab">
                        <ul class="nav nav-tabs">
                            <li>
                                <a href="/sub-agent/activate/boom_blue" style="color: blue">Boom Blue Activation</a>
                            </li>
                            <li>
                                <a href="/sub-agent/activate/boom_red" style="color: red">Boom Red Activation</a>
                            </li>
                            <li>
                                <a href="/sub-agent/activate/boom_purple" style="color: #6600ff">Boom Purple Activation</a>
                            </li>
                            <li class="active">
                                <a href="/sub-agent/rtr/boom" class="black-tab">Boom Refill</a>
                            </li>
                            <li>
                                <a href="/sub-agent/tools/boom">SIM SWAP</a>
                            </li>
                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content" style="padding-top:36px;">
                            <form id="frm_act" method="post" class="row marginbot15">
                                {!! csrf_field() !!}
                                <div class="col-sm-2">
                                    <img src="/img/category-img-boom-3.jpg" style="width: 250px; margin-bottom: 16px;">
                                </div>

                                <div class="col-sm-8">
                                    <div class="col-sm-4" align="right"
                                         style="">
                                        <div class="form-group">
                                            <label class="required">Network</label>
                                        </div>
                                    </div>
                                    <div class="col-sm-5">
                                        <div class="form-group">
                                            <label style="color: blue"><input type="radio" name="network" id="network_blue" value="BLUE" />  BLUE </label> &nbsp;&nbsp;
                                            <label style="color: red"><input type="radio" name="network" id="network_red" value="RED" /> RED </label>&nbsp;&nbsp;
                                            <label style="color: #6600ff"><input type="radio" name="network" id="network_purple" value="PURPLE" /> PURPLE </label>&nbsp;&nbsp;
                                            <div id="error_msg_esn"></div>
                                        </div>
                                        <div class="divider2" style="color: red">Please Choose a Network First !!!</div>
                                    </div>
                                </div>


                                <div class="col-sm-8" id="phone_number_box" style="display: none;">
                                    <div class="col-sm-4" align="right" style="">
                                        <div class="form-group">
                                            <label class="required">Phone Number</label>
                                        </div>
                                    </div>
                                    <div class="col-sm-5" align="right" style="padding-top: 3px;">
                                        <div class="form-group">
                                            <input type="text" class="form-control"
                                                   id="mdn"
                                                   name="mdn"
                                                   value=""
                                                   maxlength="10"
                                                   placeholder="10 digits and digits only"/>
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

                                <div class="col-sm-8" id="expired_box" style="display: none;" >
                                    <div class="col-sm-4" align="right">
                                        <div class="form-group">
                                            <label>Expired Date</label>
                                        </div>
                                    </div>
                                    <div class="col-sm-5" align="right" style="padding-top: 3px;">
                                        <div class="form-group">
                                            <div id="expired_date" align="left" style="color: red; font-size: 18px; margin-left: 10px;">

                                            </div>
                                        </div>
                                        <div class="divider2"></div>
                                    </div>
                                </div>

                                <div class="col-sm-8" id="pin_box" style="display: none;" >
                                    <div class="col-sm-7" align="right">
                                        <div class="form-group">
                                            <label>PIN</label>
                                        </div>
                                    </div>
                                    <div class="col-sm-5" align="right" style="padding-top: 3px;">
                                        <div class="form-group">
                                            <input type="text" class="form-control"
                                                   id="pin"
                                                   name="pin"
                                                   value=""
                                                   maxlength="4"
                                                   placeholder="4 digits"/>
                                            <div id="count" align="left" style="color: red;
                                                        font-size: 12px;
                                                        margin-left: 10px;">
                                                Leave blank if you don't know PIN
                                            </div>
                                        </div>
                                        <div class="divider2"></div>
                                    </div>
                                </div>

                                <div class="col-sm-8" id="expired_box" style="display: none;" >
                                    <div class="col-sm-4" align="right">
                                        <div class="form-group">
                                            <label>Expired Date</label>
                                        </div>
                                    </div>
                                    <div class="col-sm-5" align="right" style="padding-top: 3px;">
                                        <div class="form-group">
                                            <div id="expired_date" align="left" style="color: red; font-size: 18px; margin-left: 10px;">

                                            </div>
                                        </div>
                                        <div class="divider2"></div>
                                    </div>
                                </div>

                                <div id="info_box" style="display: none;">

                                    <input type="hidden" id="customer_id" value="">
                                    <input type="hidden" id="plan_code" value="">

                                    <div class="col-sm-12" style="margin-top: 16px;">
                                        <div class="col-sm-4" align="right" style="">
                                            <div class="form-group">
                                                <label class="required">Current Plan</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-5" align="left">
                                            <table class="table table-bordered">
                                                <thead>
                                                <tr>
                                                    <th></th>
                                                    <th style="text-align:center;">Amount</th>
                                                    <th>Plan Description</th>
                                                </tr>
                                                </thead>
                                                <tbody id="current_plan_box">

                                                </tbody>
                                            </table>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-12" id="renew_now" style="margin-top: 16px; display: none;">
                                        <div class="col-sm-4" align="right"
                                             style="">
                                            <div class="form-group">
                                                <label class="required">Renew Now?</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-5" align="left">
                                            <label><input type="radio" name="renew" id="renew_yes" value="Y" checked/>  Renew Right Now </label>&nbsp;&nbsp;
                                            <label><input type="radio" name="renew" id="renew_no" value="N"/>  Renew at Expire Date. </label>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-12" style="margin-top: 16px;">
                                        <div class="col-sm-4" align="right"
                                             style="">
                                            <div class="form-group">
                                                <label class="required">Another Plans</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-5" align="left">
                                            <table class="table table-bordered">
                                                <thead>
                                                <tr>
                                                    <th></th>
                                                    <th style="text-align:center;">Amount</th>
                                                    <th>Plan Description</th>
                                                </tr>
                                                </thead>
                                                <tbody id="plans_box">

                                                </tbody>
                                            </table>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-12">
                                        <div class="col-sm-4" align="right">
                                            <label class="col-sm-4"></label>
                                        </div>
                                        <div class="col-sm-5">
                                            Amount : $<span id="plan_amount_text"></span><br/>
                                            <hr style="margin:0px;"/>
                                            Regulatory Recovery Fee : $<span id="processing_fee_text"></span><br/>
                                            <hr style="margin:0px;"/>
                                            <b>Total : $<span id="total_amount_text"></span></b>
                                        </div>
                                        <div class="col-sm-2">
                                        </div>
                                    </div>

                                    <div class="col-sm-12 marginbot10" style="margin-top: 16px;">
                                        <div class="col-md-4" align="right"></div>
                                        <div class="col-md-5 col-sm-5" align="right">
                                            <button type="button" class="btn btn-primary" style="margin-top: 16px;"
                                                    onclick="confirm_order()">
                                                Submit
                                            </button>
                                        </div>
                                        <div class="col-md-2"></div>

                                    </div>
                                </div>
                                <!-- End info box -->

                            </form>
                        </div>
                    </div>
                </div>

            </div>
            {!! Helper::get_reminder('Boom Red') !!}

        </div>

    </div>
    </div>
    <!-- End contain wrapp -->
@stop
