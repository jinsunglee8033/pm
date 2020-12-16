<!DOCTYPE html>
<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en">
    <!--<![endif]-->
    <!-- BEGIN HEAD -->

    <head>
        <meta charset="utf-8" />
        <title>BOOM mall by SoftPayPlue</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="width=device-width, initial-scale=1" name="viewport" />
        <meta content="" name="author" />
        <!-- BEGIN GLOBAL MANDATORY STYLES -->
        <link href="http://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700&subset=all" rel="stylesheet" type="text/css" />
        <link href="/mall/assets/global/plugins/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
        <link href="/mall/assets/global/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <!-- END GLOBAL MANDATORY STYLES -->
        <!-- BEGIN THEME GLOBAL STYLES -->
        <link href="/mall/assets/global/css/components-rounded.min.css" rel="stylesheet" id="style_components" type="text/css" />
        <link href="/mall/assets/global/css/plugins.min.css" rel="stylesheet" type="text/css" />
        <!-- END THEME GLOBAL STYLES -->
        <!-- BEGIN THEME LAYOUT STYLES -->
        <link href="/mall/assets/layouts/layout3/css/layout.min.css" rel="stylesheet" type="text/css" />
        <link href="/mall/assets/layouts/layout3/css/themes/default.min.css" rel="stylesheet" type="text/css" id="style_color" />
        <link href="/mall/assets/layouts/layout3/css/custom.min.css" rel="stylesheet" type="text/css" />
        <!-- END THEME LAYOUT STYLES -->
        <link rel="shortcut icon" href="favicon.ico" />

        <style type="text/css">
            .receipt .row {
                border: 1px solid #e5e5e5;
                margin-left: 0px;
                margin-right: 0px;

            }

            .receipt .col-sm-4 {
                border-right: 1px solid #e5e5e5;
                padding-left: 10px;
                padding-top: 5px;
                padding-bottom: 5px;
            }

            .receipt .col-sm-8 {
                padding-left: 10px;
                padding-top: 5px;
                padding-bottom: 5px;
            }

            .row + .row {
                border-top: 0;
            }

            /* FOR PRINT */
            @media print {
                .col-sm-1, .col-sm-2, .col-sm-3, .col-sm-4, .col-sm-5, .col-sm-6, .col-sm-7, .col-sm-8, .col-sm-9, .col-sm-10, .col-sm-11, .col-sm-12 {
                    float: left;
                }
                .col-sm-12 {
                    width: 100%;
                }
                .col-sm-11 {
                    width: 91.66666667%;
                }
                .col-sm-10 {
                    width: 83.33333333%;
                }
                .col-sm-9 {
                    width: 75%;
                }
                .col-sm-8 {
                    width: 66.66666667%;
                }
                .col-sm-7 {
                    width: 58.33333333%;
                }
                .col-sm-6 {
                    width: 50%;
                }
                .col-sm-5 {
                    width: 41.66666667%;
                }
                .col-sm-4 {
                    width: 33.33333333%;
                }
                .col-sm-3 {
                    width: 25%;
                }
                .col-sm-2 {
                    width: 16.66666667%;
                }
                .col-sm-1 {
                    width: 8.33333333%;
                }

                .receipt .row {
                    border: 1px solid #e5e5e5;
                }

                .receipt .col-sm-4 {
                    border-right: 1px solid #e5e5e5;
                }

                .row + .row {
                    border-top:0;
                }

                .no-print {
                    display: none;
                }

                .wrap-sticky {
                    display: none !important;
                }

            }
        </style>
        <script type="text/javascript">
            var onload_func = window.onload;
            window.onload = function() {

                $('#network_blue').change(function () {
                    $('#phone_number_box').show();
                })
                $('#network_red').change(function () {
                    $('#phone_number_box').show();
                })
                $('#network_purple').change(function () {
                    $('#phone_number_box').show();
                })

                paypal.Button.render({

                    env: '{{ getenv('APP_ENV') == 'production' ? 'production' : 'sandbox' }}', // sandbox | production

                    // PayPal Client IDs - replace with your own
                    // Create a PayPal app: https://developer.paypal.com/developer/applications/create
                    client: {
                        sandbox: "ARPlPJ9KlcqJcnM3UdLSvfQP6ZjGr3XBXKUUpGnK4jMrqY4eDedjRdLUI9_JyjIlMIqOX7tZ7nQ-OOoX",
                        production: "AcCYHutE4WPxUSkYIL08sJJeEFFZ9ggozC8FrupXijXCOBFPtTE9UhESlIdBAAMObIXVYGNmKlH76Rzc"
                    },

                    // Show the buyer a 'Pay Now' button in the checkout flow
                    commit: true,

                    validate: function (actions) {
                        toggleButton(actions);

                        // onChangeStatus(function () {
                        //     toggleButton(actions);
                        // });
                    },

                    onClick: function () {
                        if($('#total_amount').val()){

                        }else{
                            alert("Please select Plan!");
                        };
                    },

                    // payment() is called when the button is clicked
                    payment: function (data, actions) {

                        // Make a call to the REST api to create the payment
                        return actions.payment.create({
                            payment: {
                                transactions: [
                                    {
                                        amount: {total: $('#total_amount').val(), currency: 'USD'},
                                        invoice_number: 'E-' + $('#phone').val() + '-' + $('#n_invoice_no').val()
                                    }
                                ]
                            },
                            experience: {
                                input_fields: {
                                    no_shipping: 1
                                }
                            }
                        });
                    },

                    // onAuthorize() is called when the buyer approves the payment
                    onAuthorize: function (data, actions) {

                        /* First, process RTR */
                        /* Execute payment only when RTR succeed */
                        /* If not, created payment will expire in 3 hours */
                        myApp.showLoading();

                        var renew       = $('input[name=renew]:checked').val();
                        var denom_id    = $('input[name=denom_id]:checked').val();
                        var network     = $('input[name=network]:checked').val();

                        $.ajax({
                            url: '/boom/recharge/process',
                            data: {
                                _token: '{!! csrf_token() !!}',
                                denom_id: denom_id,
                                phone: $('#phone').val(),
                                plan_code: $('#plan_code').val(),
                                renew: renew,
                                network: network,
                                total: $('#total_amount').val(),
                                payer_id: data.payerID,
                                payment_id: data.paymentID,
                                payment_token: data.paymentToken,
                                invoice_number: 'E-' + $('#phone').val() + '-' + $('#n_invoice_no').val()
                            },
                            cache: false,
                            type: 'post',
                            dataType: 'json',
                            success: function (res) {

                                if ($.trim(res.msg) === '') {
                                    // Make a call to the REST api to execute the payment
                                    return actions.payment.execute().then(function () {
                                        myApp.hideLoading();

                                        $('#td_invoice_no').text(res.invoice_no);
                                        $('#td_trans_no').text(res.trans_no);
                                        $('#td_product').text(res.product);
                                        $('#td_plan').text(res.plan);
                                        $('#td_phone').text(res.phone);
                                        $('#td_rtr_month').text(res.rtr_month);
                                        $('#td_sub_total').text('$' + parseFloat(res.sub_total).toFixed(2));
                                        $('#td_fee').text('$' + parseFloat(res.fee).toFixed(2));
                                        $('#td_total').text('$' + parseFloat(res.total).toFixed(2));
                                        $('#success').modal();
                                    });

                                } else {
                                    myApp.hideLoading();
                                    myApp.showError(res.msg);
                                }

                            },
                            error: function (jqXHR, textStatus, errorThrown) {
                                myApp.hideLoading();
                                myApp.showError(errorThrown);
                            }
                        });

                        /*
                        intent:"sale"
                        payerID:"SZ5BE7V7LH97Q"
                        paymentID:"PAY-73908049SM280515FLHAZR5A"
                        paymentToken:"EC-8C721893PX7060832"
                        returnUrl:"http://demo.softpayplus.com/?paymentId=PAY-73908049SM280515FLHAZR5A&token=EC-8C721893PX7060832&PayerID=SZ5BE7V7LH97Q
                         */


                    },

                    onCancel: function (data, actions) {
                        myApp.showError('You have cancelled PayPal payment!');
                    },

                    onError: function (err) {
                        // Show an error page here, when an error occurs
                        myApp.showError(err);
                    }

                }, '#paypal-button-container');
            }

            function isValid() {

                var total = $('#total_amount').val();
                if (total <= 0) {
                    //myApp.showError('Total amount is $0.00');
                    return false;
                }

                return true;
            }

            function toggleButton(actions) {
                console.log('isValid: ' + isValid());
                return true;
                // return isValid() ? actions.enable() : actions.disable();
            }

            function calc_total() {
                var denom_id = $('#denom_id').val();

                if (denom_id === '') return;

                var denom = $('#val_' + denom_id).val();

                if (denom === '' || typeof denom === 'undefined') {
                    denom = 0;
                } else {
                    denom = parseFloat(denom);
                }

                var rtr_month = $('#rtr_month').val();
                var sub_total = denom * rtr_month;
                var fee = parseFloat($('#vendor_fee_' + denom_id).val());
                var total = sub_total + fee;

                $('#sub_total').val(sub_total.toFixed(2));
                $('#total').val(total.toFixed(2));
            }

            function check_mdn() {

                if($('#phone').val().length != 10){
                    alert("please insert 10 digit numbers");
                    return;
                }

                $('#plans_box').empty();
                $('#plan_code').val('');
                $('#processing_fee_text').text('');
                $('#current_plan_amount').text('');
                $('#current_plan_name').text('');

                var network = $('input[name=network]:checked').val();

                myApp.showLoading();
                $.ajax({
                    url: '/boom/recharge/check_mdn',
                    data: {
                        mdn: $('#phone').val(),
                        network: network
                    },
                    type: 'get',
                    dataType: 'json',
                    cache: false,
                    success: function(res) {
                        myApp.hideLoading();
                        if (res.code === '0') {
                            $('#remain_box').show();
                            $('#expired_box').show();
                            $('#expired_date').val('');
                            $('#pin_box').show();
                            $('#current_plan_box').empty();
                            $('#plans_box').empty();
                            $('#plan_code').val(res.plancode);
                            var processing_fee = parseFloat(res.processing_fee);
                            $('#processing_fee').val(processing_fee);
                            $('#processing_fee_text').text(processing_fee.toFixed(2));

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
                                $('#remain_box').hide();
                                alert("Network and Phone is not matched! Try again");
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
                            $('#remain_box').hide();
                            alert(res.msg);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        myApp.hideLoading();
                        myApp.showError(errorThrown);
                    }
                });
            }

            function retry() {

            }

            function plan_selected(denom) {
                var processing_fee = $('#processing_fee').val();
                denom = parseFloat(denom);
                var total_amt = denom + parseFloat(processing_fee);
                $('#plan_amount_text').val(denom.toFixed(2));
                $('#total_amount').val(total_amt.toFixed(2));
            }

            function plan_selected_processing_fee(denom_id) {
                myApp.showLoading();
                $.ajax({
                    url: '/boom/recharge/get_processing_fee',
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

                            $('#plan_amount_text').val(denom.toFixed(2));
                            $('#processing_fee_text').val(processing_fee.toFixed(2));
                            $('#total_amount').val(total_amt.toFixed(2));

                            $('#plan_code').val(res.data.plan_id);

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

            function printDiv() {
                window.print();
            }

            function onChangeStatus(handler) {
                document.querySelector('#denom_id').addEventListener('change', handler);
            }
        </script>
    </head>
    <!-- END HEAD -->

    <body class="page-container-bg-solid">
        <div class="page-wrapper no-print">
            <div class="page-wrapper-row">
                <div class="page-wrapper-top">
                    <!-- BEGIN HEADER -->
                    <div class="page-header">
                        <!-- BEGIN HEADER TOP -->
                        <div class="page-header-top">
                            <div class="container">
                                <!-- BEGIN LOGO -->
                                <div class="page-logo">
                                    <a href="#">
                                        <img src="/mall/assets/layouts/layout3/img/logo-boom.png" alt="logo" class="logo-default">
                                    </a>
                                </div>
                                <!-- END LOGO -->
                                <div class="top-menu">
                                    <ul class="nav navbar-nav pull-right">

                                        <!-- BEGIN INBOX DROPDOWN -->
                                        <a href="/" class="btn red-sunglo btn-sm">Back to softpayplus.com</a>

                                        <!-- END INBOX DROPDOWN -->
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <!-- END HEADER TOP -->
                    </div>
                    <!-- END HEADER -->
                </div>
            </div>
            <div class="page-wrapper-row full-height">
                <div class="page-wrapper-middle">
                    <!-- BEGIN CONTAINER -->
                    <div class="page-container">
                        <!-- BEGIN CONTENT -->
                        <div class="page-content-wrapper">
                            <!-- BEGIN CONTENT BODY -->
                            <!-- BEGIN PAGE HEAD-->
                            <div class="page-head2">
                                <div class="container">
                                    <div class="clearfix margin-bottom-10"> </div>
                                    <div class="btn-group btn-group btn-group-justified">
                                        <a href="/boom/activate" class="btn grey"> <h4>ACTIVATION BOOM BLUE</h4> </a>
                                        <a href="/boom/recharge" class="btn dark"> <h4>RECHARGE</h4> </a>
                                    </div>

                                </div>
                            </div>
                            <div class="page-head">
                                <div class="container">
                                    <!-- BEGIN PAGE TITLE -->
                                    <div class="page-title">
                                        <h1>RECHARGE<small></small>
                                        </h1>
                                    </div>
                                    <!-- END PAGE TITLE -->
                                </div>
                            </div>
                            <!-- END PAGE HEAD-->
                            <!-- BEGIN PAGE CONTENT BODY -->


                            <!-- BEGIN PAGE CONTENT BODY -->
                            <div class="page-content">
                                <div class="container">
                                    <!-- BEGIN PAGE CONTENT INNER -->
                                    <div class="page-content-inner">
                                        <div class="row">

                                            <div class="col-md-12 ">
                                                <!-- BEGIN SAMPLE FORM PORTLET-->
                                                <form class="form-horizontal" id="frm_recharge" role="form" method="post" action="/boom/recharge/process" onsubmit="myApp.showLoading();">
                                                    {!! csrf_field() !!}

                                                    <input type="hidden" id="n_invoice_no" value="{{\Carbon\Carbon::now()->format('ymd') }}">
{{--                                                    <input type="hidden" id="total_amount_text" value="">--}}

                                                    <div class="form-group">
                                                        <label for="inputPassword1" class="col-md-4 control-label">
                                                            <span class="sbold"><font color="#4B77BE">Network</font></span>
                                                        </label>
                                                        <div class="col-md-4" style="margin-top: 7px; margin-left: 10px;">
                                                            <label style="color: blue; font-weight: bold;"><input type="radio" name="network" id="network_blue" value="BLUE" />  BLUE </label> &nbsp;&nbsp;
                                                            <label style="color: red; font-weight: bold;"><input type="radio" name="network" id="network_red" value="RED" /> RED </label>&nbsp;&nbsp;
                                                            <label style="color: #6600ff; font-weight: bold;"><input type="radio" name="network" id="network_purple" value="PURPLE" /> PURPLE </label>&nbsp;&nbsp;
                                                        </div>
                                                    </div>

                                                    <div class="form-group" id="phone_number_box" style="display: none;">
                                                        <label for="inputEmail12" class="col-md-4 control-label"><span class="sbold"><font color="#4B77BE">Phone #</font></span></label>
                                                        <div class="col-md-4">
                                                            <input class="form-control" type="text" id="phone" name="phone" maxlength="10" placeholder="10 digits and digit only."/>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <a class="btn btn-info btn-sm" onclick="check_mdn()">
                                                                Enter
                                                            </a>
                                                            <a href="/boom/recharge" class="btn btn-danger btn-sm">
                                                                Retry
                                                            </a>
                                                        </div>

                                                    </div>

                                                    <div id="remain_box" style="display: none;">

                                                        <input type="hidden" id="plan_code" value="">

                                                        <div class="form-group" id="expired_box" style="display: none;">
                                                            <label for="inputPassword1" class="col-md-4 control-label">
                                                                <span class="sbold"><font color="#4B77BE">Expired Date</font></span>
                                                            </label>
                                                            <div class="col-md-4 ">
                                                                <div id="expired_date" align="left" style="color: red; font-size: 18px; margin-left: 10px;">
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="inputPassword1" class="col-md-4 control-label">
                                                                <span class="sbold"><font color="#4B77BE">Current Plan</font></span>
                                                            </label>
                                                            <div class="col-md-5">
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
                                                            </div>
                                                        </div>


                                                        <div class="form-group" id="renew_now" style="display: none;">
                                                            <label for="inputPassword1" class="col-md-4 control-label">
                                                                <span class="sbold"><font color="#4B77BE">Renew Now?</font></span>
                                                            </label>
                                                            <div class="col-md-4">
                                                                <label><input type="radio" name="renew" id="renew" value="Y" checked/>  Yes, Renew Right Now</label>&nbsp;&nbsp;
                                                                <label><input type="radio" name="renew" id="renew" value="N"/>  No, Renew at Expired Date </label>
                                                            </div>
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="inputPassword1" class="col-md-4 control-label">
                                                                <span class="sbold"><font color="#4B77BE">Another Plans</font></span>
                                                            </label>
                                                            <div class="col-md-5">
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
                                                            </div>
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="inputPassword1" class="col-md-4 control-label"><b>Sub Total</b></label>
                                                            <div class="col-md-4 ">
                                                                <input type="text" class="form-control" id="plan_amount_text" name="plan_amount_text" readonly value="0.00"/>
                                                            </div>
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="inputPassword1" class="col-md-4 control-label"><b>Regulatory Recovery Fee</b></label>
                                                            <div class="col-md-4 ">
                                                                <input type="text" class="form-control" id="processing_fee_text" name="processing_fee_text" readonly value="0.00"/>
                                                            </div>
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="inputPassword1" class="col-md-4 control-label"><b>Total</b></label>
                                                            <div class="col-md-4 ">
                                                                <input type="text" class="form-control" id="total_amount" name="total_amount" readonly value="0.00"/>
                                                            </div>
                                                        </div>

                                                        <div class="form-group">
                                                            <div class="col-md-offset-4 col-md-7">
                                                                <div id="paypal-button-container" class="btn float-right"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </form>


                                                <div class="col-md-12 margin-bottom-40">
                                                </div>

                                                <div class="col-md-12 margin-bottom-40">
                                                </div>

                                                <div class="col-md-12 margin-bottom-40">
                                                </div>

                                                <div class="col-md-12 margin-bottom-40">
                                                </div>

                                                <div class="col-md-12 margin-bottom-40">
                                                </div>

                                                <!-- BEGIN SAMPLE FORM PORTLET-->


                                                <!-- END SAMPLE FORM PORTLET-->




                                                <!-- END SAMPLE FORM PORTLET-->

                                            </div>

                                        </div>
                                    </div>
                                    <!-- END PAGE CONTENT INNER -->
                                </div>
                            </div>
                            <!-- END PAGE CONTENT BODY -->
                            <!-- END PAGE CONTENT INNER -->
                        </div>
                    </div>
                    <!-- END PAGE CONTENT BODY -->
                    <!-- END CONTENT BODY -->

                    <!-- BEGIN INNER FOOTER -->
                    <div class="page-footer">
                        <div class="container text-center"> 2017 ~ {{ date('Y') }} &copy; Copyright
                            <a target="_blank" href="http://SoftPayPlus.com">SoftPayPlus.com.</a> All rights Reserved.
                        </div>
                    </div>
                    <div class="scroll-to-top">
                        <i class="icon-arrow-up"></i>
                    </div>
                </div>

            </div>
        </div>
        <!-- END CONTENT --><!-- END THEME LAYOUT SCRIPTS -->

        <!-- START JAVASCRIPT -->

        <div style="display:none;" id="app"></div>

        <div id="success" class="modal fade " tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false"
             style="display:none;">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">RECHARGE - Success</h4>
                    </div>
                    <div class="modal-body receipt">
                        <p class="text-center">
                            Your request has been processed successfully.
                        </p>
                        <div class="row">
                            <div class="col-sm-4">Date / Time</div>
                            <div class="col-sm-8">{{ date('Y-M-d H:i:s') }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Invoice no.</div>
                            <div class="col-sm-8" id="td_invoice_no"></div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Transaction no.</div>
                            <div class="col-sm-8" id="td_trans_no"></div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Carrier</div>
                            <div class="col-sm-8" id="td_product"></div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Plan</div>
                            <div class="col-sm-8" id="td_plan"></div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Phone</div>
                            <div class="col-sm-8" id="td_phone"></div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Sub Total</div>
                            <div class="col-sm-8" id="td_sub_total"></div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Fee</div>
                            <div class="col-sm-8" id="td_fee"></div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Total</div>
                            <div class="col-sm-8" id="td_total"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" onclick="printDiv()">Print</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal" tabindex="-1" role="dialog" id="loading-modal" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Please Wait...</h4>
                    </div>
                    <div class="modal-body">
                        <div class="progress" style="margin-top:20px;">
                            <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
                                <span class="sr-only">Please wait.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal" tabindex="-1" role="dialog" id="error-modal">
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="error-modal-title">Modal title</h4>
                    </div>
                    <div class="modal-body" id="error-modal-body">
                    </div>
                    <div class="modal-footer" id="error-modal-footer">
                        <button type="button" id="error-modal-ok" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal" tabindex="-1" role="dialog" id="confirm-modal">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="confirm-modal-title">Modal title</h4>
                    </div>
                    <div class="modal-body" id="confirm-modal-body">

                    </div>
                    <div class="modal-footer" id="confirm-modal-footer">
                        <button type="button" id="confirm-modal-cancel" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="button" id="confirm-modal-ok" class="btn btn-primary" data-dismiss="modal">Ok</button>
                    </div>
                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div><!-- /.modal -->

        <script>
            window.Laravel = <?php echo json_encode([
                'csrfToken' => csrf_token(),
            ]); ?>;
            var account_id = "";
            var env = "{{ getenv('APP_ENV') }}";
        </script>

        <script src="/js/app.js"></script>

        <!-- Placed at the end of the document so the pages load faster -->
        <script type="text/javascript" src="/js/jquery.min.js"></script>
        <script src="/js/bootstrap.min.js"></script>
        <script src="/js/jquery.easing-1.3.min.js"></script>

        <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
        <script src="/js/ie10-viewport-bug-workaround.js"></script>

        <script src="/bower_components/twitter-bootstrap-wizard/jquery.bootstrap.wizard.js"></script>
        <script src="/bower_components/jquery-validation/dist/jquery.validate.js"></script>
        <script src="/js/loading.js"></script>
        <!--script src="https://use.fontawesome.com/0f855c73a0.js"></script-->

        <script src="/bower_components/moment/moment.js"></script>
        <script src="/bower_components/eonasdan-bootstrap-datetimepicker/src/js/bootstrap-datetimepicker.js"></script>
        <script src="/js/bootstrap-notify.min.js"></script>
        <script type="text/javascript">
            function realtime_notify(e) {
                window.$.notify({
                    icon: 'glyphicon glyphicon-warning-sign',
                    title: 'Transaction Status Update!',
                    message: e.message
                },{
                    type: e.transaction.status == 'C' ? 'success' : 'warning',
                    delay: 0,
                    animate: {
                        enter: 'animated fadeInRight',
                        exit: 'animated fadeOutRight'
                    },
                    template: '<div class="alert alert-{0}" role="alert" data-out="bounceOut">' +
                    '<span class="close-alert" data-dismiss="alert"><i class="fa fa-times-circle"></i></span>' +
                    '<i class="fa fa-{0}"></i>' +
                    '<h6 class="title">{1}</h6>' +
                    '<p>{2}</p>'+
                    '</div>'
                });
            }
        </script>

        <!-- Bootsnavs -->
        <script src="/js/bootsnav.js"></script>

        <!-- Custom form -->
        <script src="/js/form/jcf.js"></script>
        <!--script src="/js/form/jcf.scrollable.js"></script-->
        <!--script src="/js/form/jcf.select.js"></script-->

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

        <script src="/js/loading.js"></script>

        <!-- Theme option-->
        <script src="/js/template-option/demosetting.js"></script>

        <script type="text/javascript" src="/js/jquery.treegrid.min.js"></script>

        <script type="text/javascript" src="/js/ckeditor/ckeditor.js"></script>

        <!-- PayPal -->
        <script src="https://www.paypalobjects.com/api/checkout.js"></script>
        <script>
            $(document).ready(function()
            {
                $('#clickmewow').click(function()
                {
                    $('#radio1003').attr('checked', 'checked');
                });
            })
        </script>
    </body>

</html>