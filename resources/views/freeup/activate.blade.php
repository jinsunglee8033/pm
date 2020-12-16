<!DOCTYPE html>
<!--[if IE 8]>
<html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]>
<html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<head lang="en">
    <!--<![endif]-->
    <!-- BEGIN HEAD -->
    <meta charset="utf-8"/>
    <title>ROC mall by SoftPayPlue</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport"/>
    <meta content="" name="author"/>
    <!-- BEGIN GLOBAL MANDATORY STYLES -->
    <link href="http://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700&subset=all" rel="stylesheet"
          type="text/css"/>
    <link href="/mall/assets/global/plugins/font-awesome/css/font-awesome.min.css" rel="stylesheet"
          type="text/css"/>
    <link href="/mall/assets/global/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css"/>
    <!-- END GLOBAL MANDATORY STYLES -->
    <!-- BEGIN THEME GLOBAL STYLES -->
    <link href="/mall/assets/global/css/components-rounded.min.css" rel="stylesheet" id="style_components"
          type="text/css"/>
    <link href="/mall/assets/global/css/plugins.min.css" rel="stylesheet" type="text/css"/>
    <!-- END THEME GLOBAL STYLES -->
    <!-- BEGIN THEME LAYOUT STYLES -->
    <link href="/mall/assets/layouts/layout3/css/layout.min.css" rel="stylesheet" type="text/css"/>
    <link href="/mall/assets/layouts/layout3/css/themes/default.min.css" rel="stylesheet" type="text/css"
          id="style_color"/>
    <link href="/mall/assets/layouts/layout3/css/custom.min.css" rel="stylesheet" type="text/css"/>
    <!-- END THEME LAYOUT STYLES -->
    <link rel="shortcut icon" href="favicon.ico"/>



    <style type="text/css">
        .required {
            font-weight: 600 !important;
            color: #4B77BE !important;
            font-size: 14px !important;
            font-family: "Open Sans", sans-serif !important;
        }

        input[type=radio]~span {
            background-color: #ffffff !important;
            border-color: #9e9e9e !important;
        }

        input[type=radio]:disabled~span {
            background-color: #dddddd !important;
            #border-color: #efefef !important;
        }

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

            $('#zip_code').keyup(function(){
                if($(this).val().length == 5){
                    document.querySelector('#error_zip_code').classList.add('hidden');
                }else{
                    document.querySelector('#error_zip_code').classList.remove('hidden');
                }
            });

            $('#call_back_phone').keyup(function(){
                if($(this).val().length > 1){
                    document.querySelector('#error_phone').classList.add('hidden');
                }else{
                    document.querySelector('#error_phone').classList.remove('hidden');
                }

            });

            $('#email').keyup(function(){
                if($(this).val().length > 1){
                    document.querySelector('#error_email').classList.add('hidden');
                }else{
                    document.querySelector('#error_email').classList.remove('hidden');
                }
            });

            paypal.Button.render({

                env: '{{ getenv('APP_ENV') == 'production' ? 'production' : 'sandbox' }}', // sandbox | production

                // PayPal Client IDs - replace with your own
                // Create a PayPal app: https://developer.paypal.com/developer/applications/create
                client: {
                    // sandbox: "ARPlPJ9KlcqJcnM3UdLSvfQP6ZjGr3XBXKUUpGnK4jMrqY4eDedjRdLUI9_JyjIlMIqOX7tZ7nQ-OOoX",
                    sandbox: "Abw1jEgr6SsKJ1xgbTO2eaQ8ZnAXgVT6opnBLysKhO_9rBcg6BUxVUxMWLMwBN0kJ0KGUVmea64U20Iw", // For Jin
                    production: "AcCYHutE4WPxUSkYIL08sJJeEFFZ9ggozC8FrupXijXCOBFPtTE9UhESlIdBAAMObIXVYGNmKlH76Rzc"
                },

                // Show the buyer a 'Pay Now' button in the checkout flow
                commit: true,

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
                                    invoice_number: 'E-' + $('#sim').val() + '-' + $('#n_invoice_no').val()
                                }
                            ]
                        },
                        experience: {
                            input_fields: {
                                no_shipping: 1
                            }
                        }
                    })
                },

                // onAuthorize() is called when the buyer approves the payment
                onAuthorize: function (data, actions) {

                    myApp.showLoading();

                    $.ajax({
                        url: '/freeup/activate/process',
                        data: {
                            _token: '{!! csrf_token() !!}',
                            afcode: $('#afcode').val(),
                            sim: $('#sim').val(),
                            esn: $('#esn').val(),
                            handset_os: $('#handset_os').val(),
                            imei: $('#imei').val(),
                            zip_code: $('#zip_code').val(),
                            sub_carrier: $('#sub_carrier').val(),
                            total: $('#total_amount').val(),
                            denom_id: $('input[name=denom_id]:checked').val(),
                            call_back_phone: $('#call_back_phone').val(),
                            email: $('#email').val(),
                            payer_id: data.payerID,
                            payment_id: data.paymentID,
                            payment_token: data.paymentToken,
                            invoice_number: 'E-' + $('#sim').val() + '-' + $('#n_invoice_no').val()
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
                                    $('#td_transaction_no').text(res.transaction_no);
                                    $('#td_sim').text(res.sim);
                                    $('#td_imei').text(res.imei);
                                    $('#td_call_back_phone').text(res.call_back_phone);
                                    $('#td_email').text(res.email);
                                    $('#td_product').text(res.product);
                                    $('#td_rtr_month').text(res.rtr_month);
                                    $('#td_sub_total').text('$' + parseFloat(res.sub_total).toFixed(2));
                                    $('#td_activation_fee').text('$' + parseFloat(res.fee).toFixed(2));
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

        };

        function change_source(type) {
            var code = $('#' + type).val();

            myApp.showLoading();
            $.ajax({
                url: '/freeup/activate/sim/' + type,
                data: {
                    code: code
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
                        $('#sim').attr('disabled','disabled');
                        // let length = $('#afcode').val().length;
                        // $('#afcode_count').text(length);

                    } else {
                        alert(res.msg);

                        $('#info_box').hide();

                        $('#afcode').val('');
                        // $('#sim').val('');
                        $('#sim').focus();

                        $('#esn_box').show();
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
                if (data.hide_plan == 'Y') {
                    $('#plans_radio').append('<div class="radio"><input type="radio" value="' + v.denom_id + '" name="denom_id" + onclick="get_commission()" checked> ' + data.plan_description + '</div>');
                } else {
                    $('#plans_radio').append('<div class="radio"><input type="radio" value="' + v.denom_id + '" name="denom_id" + onclick="get_commission()"> $' + v.denom + ' (' + v.name + ')</div>');
                }
            });

            if (data.sub_carrier == 'ATT') {
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

            if (data.button == 'paypal'){
                $('#button_type').val('paypal');
                $('#active_div').hide();
                $('#paypal_div').hide();
            }else{
                $('#button_type').val('normal');
                $('#active_div').hide();
                $('#paypal_div').hide();
            }

        }

        function get_commission() {

            if ($('#button_type').val() == 'paypal') {

                if ($('#imei').val().length < 1) {
                    alert("Please Insert IMEI First!");
                    $("input[type=radio][name=denom_id]").prop('checked', false);
                    return;
                }
                if ($('#handset_os').val().length < 1) {
                    alert("Please Select Handset OS First!");
                    $("input[type=radio][name=denom_id]").prop('checked', false);
                    return;
                }
                if ($('#zip_code').val().length < 1) {
                    alert("Please Insert Zip Code First!");
                    $("input[type=radio][name=denom_id]").prop('checked', false);
                    return;
                }
                if ($('#call_back_phone').val().length < 1) {
                    alert("Please Insert Call Back Number First!");
                    $("input[type=radio][name=denom_id]").prop('checked', false);
                    return;
                }
                if ($('#email').val().length < 1) {
                    alert("Please Insert Email First!");
                    $("input[type=radio][name=denom_id]").prop('checked', false);
                    return;
                }
            }

            $('#spiff_div').hide();

            var imei        = $('#imei').val();
            var sim         = $('#sim').val();
            var denom_id    = $('input[name=denom_id]:checked').val();

            myApp.showLoading();
            $.ajax({
                url: '/freeup/activate/commission',
                data: {
                    imei: imei,
                    sim: sim,
                    denom_id: denom_id
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

                        if (res.data.sim_charge != 0){
                            $('#sim_charge_box_body').show();
                            $('#sim_charge_box').text(res.data.sim_charge);
                        }
                        if (res.data.sim_rebate != 0){
                            $('#sim_rebate_box_body').show();
                            $('#sim_rebate_box').text(res.data.sim_rebate);
                        }
                        if (res.data.esn_charge != 0){
                            $('#esn_charge_box_body').show();
                            $('#esn_charge_box').text(res.data.esn_charge);
                        }
                        if (res.data.esn_rebate != 0){
                            $('#esn_rebate_box_body').show();
                            $('#esn_rebate_box').text(res.data.esn_rebate);
                        }

                        $('#total_box_body').show();
                        $('#total_box').text(parseFloat(res.data.total).toFixed(2));
                        $('#total_amount').val(parseFloat(res.data.total).toFixed(2));

                        if (res.data.spiff_count > 0) {
                            $('#spiff_div').show();
                            $.each(res.data.spiff_labels, function(k, v) {
                                $('#spiff_label').append("<strong>" + v + "</strong><br>");
                            });
                        }

                        if($('#button_type').val() == 'paypal'){ // paypal
                            if(res.data.total == 0){ // paypal but preload. => to normal
                                $('#active_div').show();
                                $('#paypal_div').hide();
                            }else{
                                if($('#zip_code').val().length == 5
                                    && $('#handset_os').val().length > 0
                                    && $('#call_back_phone').val().length > 0
                                    && $('#email').val().length > 0){ // if paypal, check all condition
                                    $('#paypal_div').show();
                                    $('#active_div').hide();
                                }else{
                                    alert(" Please fill out required fields (Red Text)");
                                    $("input[type=radio][name=denom_id]").prop('checked', false);
                                    $('#paypal_div').hide();
                                }
                            }
                        }else{ // normal
                            $('#active_div').show();
                            $('#paypal_div').hide();
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

        function check_imei() {
            var imei = $('#imei').val();
            myApp.showLoading();
            $.ajax({
                url: '/freeup/activate/imei',
                data: {
                    imei: imei
                },
                type: 'get',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();
                    if (res.code === '0') {

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
        function request_activation() {
            $("div[id^='error_msg_']").empty();

            if (!$("input[name=denom_id]").is(":checked")) {
                alert("Please select a plan to activate !!");
                return;
            }

            var afcode      = $('#afcode').val();
            var sim         = $('#sim').val();
            var esn         = $('#esn').val();
            var handset_os  = $('#handset_os').val();
            var imei        = $('#imei').val();
            var zip_code    = $('#zip_code').val();
            var sub_carrier = $('#sub_carrier').text();
            var denom_id    = $('input[name=denom_id]:checked').val();
            var call_back_phone = $('#call_back_phone').val();
            var email       = $('#email').val();

            var data = {
                    _token: '{{ csrf_token() }}',
                    afcode: afcode,
                    sim: sim,
                    esn: esn,
                    handset_os: handset_os,
                    imei: imei,
                    zip_code: zip_code,
                    sub_carrier: sub_carrier,
                    denom_id: denom_id,
                    call_back_phone: call_back_phone,
                    email: email
                };

            myApp.showLoading();
            $.ajax({
                url: '/freeup/activate/post',
                data: data,
                type: 'post',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();

                    if (res.code == '0') {
                        // alert(res.data.msg);
                        window.location.href = '/freeup/activate/success/' + res.data.id;
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

        function closeDiv() {
            window.location.href = '/freeup/activate';
        }

    </script>
</head>
<!-- END HEAD -->


<body class="page-container-bg-solid">

    @if (!empty($trans))
    <div id="activate_invoice" class="modal fade " tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false"
         style="display:block;">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Activate Success</h4>
                </div>
                <div class="modal-body receipt" id="activate_invoice_body">
                    <p>
                        Your request is being processed.
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
                    @if ($trans->hide_plan != 'Y')
                    <div class="row">
                        <div class="col-sm-4">Plan Price</div>
                        <div class="col-sm-8">${{ number_format($trans->denom, 2) }}</div>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="printDiv()">Print</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif

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
                                <a href="/">
                                    <img src="/mall/assets/layouts/layout3/img/logo-freeup.png" alt="logo"
                                         class="logo-default">
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
                                <div class="clearfix margin-bottom-10"></div>
                                <div class="btn-group btn-group btn-group-justified">
                                    <a href="/freeup/activate" class="btn dark"><h4>ACTIVATION</h4></a>
                                    <a href="/freeup/recharge" class="btn grey"><h4>Recharge</h4></a>
                                </div>

                            </div>
                        </div>
                        <div class="page-head">
                            <div class="container">
                                <!-- BEGIN PAGE TITLE -->
                                <div class="page-title">
                                    <h1>ACTIVATION
                                        <small></small>
                                    </h1>
                                </div>
                                <!-- END PAGE TITLE -->
                            </div>
                        </div>
                        <!-- END PAGE HEAD-->
                        <!-- BEGIN PAGE CONTENT BODY -->


                        <!-- BEGIN PAGE CONTENT BODY -->
                        <div class="page-content" style="margin-top: 32px;margin-bottom: 32px;">
                            <div class="container">
                                <!-- BEGIN PAGE CONTENT INNER -->
                                <div class="page-content-inner">
                                    <div class="row">

                                        <!-- BEGIN SAMPLE FORM PORTLET-->
                                        <form id="frm_act" method="post" class="row marginbot15">
                                            {!! csrf_field() !!}
                                            <input type="hidden" id="n_invoice_no" value="{{
                                                    \Carbon\Carbon::now()->format('ymd-His') }}">
                                            <input type="hidden" id="total_amount" value="">
                                            <input type="hidden" id="button_type" value="">

{{--                                            <div class="col-sm-12">--}}
{{--                                                <div class="col-sm-4" align="right"--}}
{{--                                                     style="">--}}
{{--                                                    <div class="form-group">--}}
{{--                                                        <label class="required">Activation Code</label>--}}
{{--                                                    </div>--}}
{{--                                                </div>--}}
{{--                                                 <div class="col-sm-5" align="right"--}}
{{--                                                     style="">--}}
{{--                                                    <div class="form-group {{ $errors->has('afcode') ? ' has-error' : '' }}">--}}
{{--                                                        <input type="text" class="form-control"--}}
{{--                                                                id="afcode"--}}
{{--                                                                name="afcode"--}}
{{--                                                                value=""--}}
{{--                                                                maxlength="20"--}}
{{--                                                                placeholder="7 or 9 digits and digits only"--}}
{{--                                                                onchange="change_source('afcode')"--}}
{{--                                                               />--}}
{{--                                                        <div id="count" align="left" style="color: red;--}}
{{--                                                        font-size: 12px;--}}
{{--                                                        margin-left: 10px;">--}}
{{--                                                            You have entered in <span id="afcode_count" style="font-weight: bold;">0</span> Digits--}}
{{--                                                        </div>--}}
{{--                                                        <div id="error_msg_afcode"></div>--}}
{{--                                                    </div>--}}
{{--                                                </div> --}}
{{--                                            </div> --}}

                                            <div class="col-sm-12">
                                                <div class="col-sm-4" align="right"
                                                     style="">
                                                    <div class="form-group">
                                                        <label class="required">SIM</label>
                                                    </div>
                                                </div>
                                                 <div class="col-sm-5" align="right"
                                                     style="">
                                                    <div class="form-group">
                                                        <input type="text" class="form-control"
                                                                id="sim"
                                                                name="sim"
                                                                value=""
                                                                maxlength="20"
                                                                placeholder="20 digits and digits only"
                                                                onblur="change_source('sim')"
                                                               />
                                                        <div id="count" align="left" style="color: red;
                                                        font-size: 12px;
                                                        margin-left: 10px;">
                                                            You have entered in <span id="sim_count" style="font-weight: bold;">0</span> Digits
                                                        </div>
                                                        <div id="error_msg_sim"></div>
                                                    </div>
                                                </div>
                                                <div class="col-sm-3" align="left">
                                                    <a class="btn btn-info btn-sm">
                                                        Enter
                                                    </a>
                                                </div>
                                            </div>  

                                            <div class="col-sm-12" id="esn_box">
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
                                                                id="esn"
                                                                name="esn"
                                                                value=""
                                                                maxlength="20"
                                                                placeholder="15 or 16 digit of IMEI"
                                                               />
                                                        <div id="count" align="left" style="color: red;
                                                        font-size: 12px;
                                                        margin-left: 10px;">
                                                            You have entered in <span id="esn_count" style="font-weight: bold;">0</span> Digits
                                                        </div>
                                                        <div id="error_msg_esn"></div>
                                                    </div>
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
                                                                    placeholder="Please Insert IMEI"
                                                                    onchange="check_imei()"
                                                                   />
                                                            <div id="count" align="left" style="color: red;
                                                        font-size: 12px;
                                                        margin-left: 10px;">
                                                                You have entered in <span id="imei_count" style="font-weight: bold;">0</span> Digits
                                                            </div>
                                                            <div id="error_msg_imei"></div>
                                                        </div>
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
                                                                    placeholder="Please Insert Valid Zip Code"
                                                                   />
                                                            <div id="error_zip_code" align="left" class="" style="color: red;
                                                                    font-size: 12px;
                                                                    margin-left: 10px;">Please Insert Valid Zip Code</div>
                                                        </div>
                                                        <div class="divider2"></div>
                                                    </div> 
                                                </div>

                                                <div class="col-sm-12" id="call_back_phone_div">
                                                    <div class="col-sm-4" align="right"
                                                         style="">
                                                        <div class="form-group">
                                                            <label class="required">Call Back Number</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-5" align="right"
                                                         style="">
                                                        <div class="form-group">
                                                            <input type="text" class="form-control"
                                                                   id="call_back_phone"
                                                                   name="call_back_phone"
                                                                   value=""
                                                                   placeholder="Please Insert Call Back Number"
                                                            />
                                                            <div id="error_phone" align="left" class="" style="color: red;
                                                                    font-size: 12px;
                                                                    margin-left: 10px;">Please Insert Call Back Number</div>
                                                        </div>
                                                        <div class="divider2"></div>
                                                    </div>
                                                </div>

                                                <div class="col-sm-12" id="email_div">
                                                    <div class="col-sm-4" align="right"
                                                         style="">
                                                        <div class="form-group">
                                                            <label class="required">Email</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-5" align="right"
                                                         style="">
                                                        <div class="form-group">
                                                            <input type="text" class="form-control"
                                                                   id="email"
                                                                   name="email"
                                                                   value=""
                                                                   placeholder="Please Insert Email"
                                                            />
                                                            <div id="error_email" align="left" class="" style="color: red;
                                                                    font-size: 12px;
                                                                    margin-left: 10px;">Please Insert Email</div>
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

                                                <div class="col-sm-12" id="sim_charge_box_body" style="display: none">
                                                    <div class="col-sm-4" align="right">
                                                        <label class="required">Sim.Charge</label>
                                                    </div>
                                                    <div class="col-sm-5">

                                                        <div class="form-group">
                                                            $<span id="sim_charge_box">0.00</span>
                                                        </div>
                                                        <div class="divider2"></div>
                                                    </div>

                                                    <div class="col-sm-2"></div>
                                                </div>
                                                <div class="col-sm-12" id="sim_rebate_box_body" style="display: none">
                                                    <div class="col-sm-4" align="right">
                                                        <label class="required">Sim.Rebate</label>
                                                    </div>
                                                    <div class="col-sm-5">

                                                        <div class="form-group">
                                                            $<span id="sim_rebate_box">0.00</span>
                                                        </div>
                                                        <div class="divider2"></div>
                                                    </div>

                                                    <div class="col-sm-2"></div>
                                                </div>
                                                <div class="col-sm-12" id="esn_charge_box_body" style="display: none">
                                                    <div class="col-sm-4" align="right">
                                                        <label class="required">ESN.Charge</label>
                                                    </div>
                                                    <div class="col-sm-5">

                                                        <div class="form-group">
                                                            $<span id="esn_charge_box">0.00</span>
                                                        </div>
                                                        <div class="divider2"></div>
                                                    </div>

                                                    <div class="col-sm-2"></div>
                                                </div>
                                                <div class="col-sm-12" id="esn_rebate_box_body" style="display: none">
                                                    <div class="col-sm-4" align="right">
                                                        <label class="required">ESN.Rebate</label>
                                                    </div>
                                                    <div class="col-sm-5">

                                                        <div class="form-group">
                                                            $<span id="esn_rebate_box">0.00</span>
                                                        </div>
                                                        <div class="divider2"></div>
                                                    </div>

                                                    <div class="col-sm-2"></div>
                                                </div>

                                                <div class="col-sm-12" id="total_box_body">
                                                    <div class="col-sm-4" align="right">
                                                        <label class="required">Total</label>
                                                    </div>
                                                    <div class="col-sm-5">

                                                        <div class="form-group">
                                                            $<span id="total_box">0.00</span>
                                                        </div>
                                                        <div class="divider2"></div>
                                                    </div>

                                                    <div class="col-sm-2"></div>
                                                </div>

                                                <!-- Normal Button -->
                                                <div class="col-sm-12 marginbot10" id="active_div" style="margin-top: 16px;">
                                                    <div class="col-md-4" align="right"></div>
                                                    <div class="col-md-5 col-sm-5" align="right">
                                                        <button type="button" class="btn btn-primary" style="margin-top: 16px;" onclick="request_activation()">
                                                            Activate
                                                        </button>
                                                    </div>
                                                    <div class="col-md-1"></div>
                                                </div>

                                                <!-- Paypal Button -->
                                                <div class="form-group" id="paypal_div" style="">
                                                    <div class="col-md-5" align="right"></div>
                                                    <div class="col-md-5 col-sm-5">
                                                        <div id="paypal-button-container" class="btn float-right"></div>
                                                    </div>
                                                </div>

                                            </div>
                                            <!-- End info box -->

                                        </form>
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
                        <a target="_blank" href="http://SoftPayPlus.com">SoftPayPlus.</a> All rights Reserved.
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
                    <h4 class="modal-title">Thank you for your requests</h4>
                </div>
                <div class="modal-body receipt">
                    <p class="text-center">
                        Your Activation request has been submitted successfully, and your payment is completed too.
                    </p>

                    <p class="text-center">
                        We are now processing your activation.
                        We will send you an email with your new phone number as soon as it’s successfully processed.
                    </p>

                    <p class="text-center">
                        This will take a few minutes normally, but sometimes, it could take up to an hour.
                        Please contact us(ops@softpayplus.com)  if you’ve not received any response up to one hour.
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
                        <div class="col-sm-8" id="td_transaction_no"></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">SIM</div>
                        <div class="col-sm-8" id="td_sim"></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">IMEI</div>
                        <div class="col-sm-8" id="td_imei"></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">Carrier</div>
                        <div class="col-sm-8">FreeUP Mobile</div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">Email</div>
                        <div class="col-sm-8" id="td_email"></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">Call Back Phone</div>
                        <div class="col-sm-8" id="td_call_back_phone"></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">RTR Month</div>
                        <div class="col-sm-8" id="td_rtr_month"></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">Sub Total</div>
                        <div class="col-sm-8" id="td_sub_total"></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">Fee</div>
                        <div class="col-sm-8" id="td_activation_fee"></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">Total</div>
                        <div class="col-sm-8" id="td_total"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="printDiv()">Print</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal" onclick="closeDiv()">Close</button>
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
            }, {
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
                '<p>{2}</p>' +
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
        $(document).ready(function () {
            $('#clickmewow').click(function () {
                $('#radio1003').attr('checked', 'checked');
            });
        })
    </script>
</body>

</html>