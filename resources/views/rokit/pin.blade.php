<!DOCTYPE html>
<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en">
<!--<![endif]-->
<!-- BEGIN HEAD -->

<head>
    <meta charset="utf-8" />
    <title>ROKiT Mobile by SoftPayPlus</title>
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
            paypal.Button.render({

                env: '{{ getenv('APP_ENV') == 'production' ? 'production' : 'sandbox' }}', // sandbox | production

                // PayPal Client IDs - replace with your own
                // Create a PayPal app: https://developer.paypal.com/developer/applications/create
                client: {
                    sandbox: "ARPlPJ9KlcqJcnM3UdLSvfQP6ZjGr3XBXKUUpGnK4jMrqY4eDedjRdLUI9_JyjIlMIqOX7tZ7nQ-OOoX",
                    // sandbox: "Abw1jEgr6SsKJ1xgbTO2eaQ8ZnAXgVT6opnBLysKhO_9rBcg6BUxVUxMWLMwBN0kJ0KGUVmea64U20Iw", // For Jin
                    production: "AcCYHutE4WPxUSkYIL08sJJeEFFZ9ggozC8FrupXijXCOBFPtTE9UhESlIdBAAMObIXVYGNmKlH76Rzc"
                },

                // Show the buyer a 'Pay Now' button in the checkout flow
                commit: true,

                validate: function (actions) {
                    toggleButton(actions);

                    onChangeStatus(function () {
                        toggleButton(actions);
                    });
                },

                onClick: function () {
                    if (!isValid()) {
                        //myApp.showError('Please enter valid amount or comments first');
                    }
                },

                // payment() is called when the button is clicked
                payment: function (data, actions) {
                    // Make a call to the REST api to create the payment
                    return actions.payment.create({
                        payment: {
                            transactions: [
                                {
                                    amount: {total: $('#total').val(), currency: 'USD'},
                                    invoice_number: 'E-PIN-' + $('#n_invoice_no').val()
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

                    $.ajax({
                        url: '/rokit/pin/process',
                        data: {
                            _token: '{!! csrf_token() !!}',
                            product_id: $('#product_id').val(),
                            denom_id: $('#denom_id').val(),
                            rtr_month: $('#rtr_month').val(),
                            total: $('#total').val(),
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
                                    $('#td_product').text(res.product);
                                    $('#td_pin').text(res.pin);
                                    $('#td_amount').text('$' + parseFloat(res.amount).toFixed(2));
                                    $('#td_rtr_month').text(res.rtr_month);
                                    $('#td_sub_total').text('$' + parseFloat(res.sub_total).toFixed(2));
                                    $('#td_vendor_fee').text('$' + parseFloat(res.fee).toFixed(2));
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
            var denom_id = $('#denom_id').val();
            if ($.trim(denom_id) === '') {
                //myApp.showError('Please lookup phone number first');
                return false;
            }

            var total = $('#total').val();
            if (total <= 0) {
                //myApp.showError('Total amount is $0.00');
                return false;
            }

            return true;
        }

        function toggleButton(actions) {
            console.log('isValid: ' + isValid());
            return isValid() ? actions.enable() : actions.disable();
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

        function refresh_page() {
            $('#frm_pin').attr('action', '/rokit/pin');
            $('#frm_pin').submit();
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
                                <img src="/mall/assets/layouts/layout3/img/logo-rokit.png" alt="logo"
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
                            <div class="clearfix margin-bottom-10"> </div>
                            <div class="btn-group btn-group btn-group-justified">
                                <a href="/rokit/pin" class="btn dark"><h4>PIN</h4></a>
                            </div>

                        </div>
                    </div>
                    <div class="page-head">
                        <div class="container">
                            <!-- BEGIN PAGE TITLE -->
                            <div class="page-title">
                                <h1>PIN<small></small>
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
                                        <form class="form-horizontal" id="frm_pin" role="form" method="post" action="/rok/pin" onsubmit="myApp.showLoading();">
                                            {!! csrf_field() !!}


                                            <input type="hidden" id="n_invoice_no" value="{{
                                                    \Carbon\Carbon::now()->format('ymd-His') }}">
                                            <input type="hidden" id="rtr_month" value="1">

                                            <div class="form-group">
                                                <label for="inputPassword1" class="col-md-4 control-label">
                                                    <span class="sbold">
                                                        <font color="#4B77BE">Product</font>
                                                    </span>
                                                </label>
                                                <div class="col-md-4">
                                                    <select class="form-control" name="product_id" onchange="refresh_page()">
                                                        <option value="">Please Select</option>
                                                        @if (count($products) > 0)
                                                            @foreach ($products as $o)
                                                                <option value="{{ $o->id }}" {{ old('product_id', $product_id) == $o->id ? 'selected' : ''  }}>{{ $o->name }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                    @if ($errors->has('product_id'))
                                                        <span class="help-block">
                                                    <strong>{{ $errors->first('product_id') }}</strong>
                                                </span>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label for="inputPassword1" class="col-md-4 control-label">
                                                    <span class="sbold">
                                                        <font color="#4B77BE">Denom</font>
                                                    </span>
                                                </label>
                                                <div class="col-md-4">
                                                    <select class="form-control" name="denom_id" id="denom_id" onchange="calc_total()">
                                                        <option value="" price="">Please select denomination</option>
                                                        @if (count($denoms) > 0)
                                                            @foreach ($denoms as $d)
                                                                <option value="{{ $d->denom_id }}" price="{{ $d->denom }}">${{ $d->denom }} | {{ $d->name }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                    @if (count($denoms) > 0)
                                                        @foreach ($denoms as $d)
                                                            <input id="val_{{ $d->denom_id }}" value="{{ $d->denom }}" type="hidden">
                                                            <input id="vendor_fee_{{ $d->denom_id }}" value="{{ $d->fee }}" type="hidden">
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>

                                            <input type="hidden" class="form-control" id="sub_total" name="sub_total" readonly value="0.00"/>
                                            <div class="form-group" style="display:none;">
                                                <label for="inputPassword1" class="col-md-4 control-label"><b>Vendor Fee</b></label>
                                                <div class="col-md-4 ">
                                                    <input type="text" class="form-control" id="vendor_fee" name="vendor_fee" readonly value="0.00"/>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label for="inputPassword1" class="col-md-4 control-label"><b>Total</b></label>
                                                <div class="col-md-4 ">
                                                    <input type="text" class="form-control" id="total" name="total" readonly value="0.00"/>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <div class="col-md-offset-4 col-md-7">
                                                    <div id="paypal-button-container" class="btn float-right"></div>
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
                    <a target="_blank" href="http://SoftPayPlus.com">SoftPayPlus</a> All rights Reserved.
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
                <h4 class="modal-title">PURCHASE - PIN Success</h4>
            </div>
            <div class="modal-body receipt">
                <p class="text-center">
                    Your request has been processed successfully.
                </p>

                <div class="row">
                    <div class="col-sm-4">Invoice no.</div>
                    <div class="col-sm-8" id="td_invoice_no"></div>
                </div>
                <div class="row">
                    <div class="col-sm-4">Carrier</div>
                    <div class="col-sm-8">ROKiT</div>
                </div>
                <div class="row">
                    <div class="col-sm-4">Product</div>
                    <div class="col-sm-8" id="td_product"></div>
                </div>
                <div class="row">
                    <div class="col-sm-4">Amount</div>
                    <div class="col-sm-8" id="td_amount"></div>
                </div>
                <div class="row">
                    <div class="col-sm-4">Pin</div>
                    <div class="col-sm-8" id="td_pin"></div>
                </div>
                <div class="row">
                    <div class="col-sm-4">Sub Total</div>
                    <div class="col-sm-8" id="td_sub_total"></div>
                </div>
                <div class="row" style="display:none;">
                    <div class="col-sm-4">Vendor Fee</div>
                    <div class="col-sm-8" id="td_vendor_fee"></div>
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