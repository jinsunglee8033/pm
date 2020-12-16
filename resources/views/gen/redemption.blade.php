<!DOCTYPE html>
<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en">
<!--<![endif]-->
<!-- BEGIN HEAD -->

<head>
    <meta charset="utf-8" />
    <title>GEN Mobile by SoftPayPlus</title>
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

            $('#mdn').keyup(function() {
                let length = $(this).val().length;
                $('#mdn_count').text(length);
            });

        }

        function printDiv() {
            window.print();
        }

        function check_mdn() {
            $('#current_plan_amount').text('');
            $('#current_plan_name').text('');

            myApp.showLoading();
            $.ajax({
                url: '/gen/redemption/check_mdn',
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

                        $('#plan_code').val(res.plancode);
                        $('#customer_id').val(res.customer_id);
                        $('#denom_id').val(res.denom_id);
                        $('#denom').val(res.denom);
                        var current_plan_selected = false;
                        $.each(res.denoms, function(k, v) {
                            if (res.plancode == v.rtr_pid) {
                                $('#current_plan_amount').text('$' + v.denom);
                                $('#current_plan_name').text(v.name);
                                current_plan_selected = true;
                            }
                        });

                        if (!current_plan_selected) {
                            $('#current_plan_name').text("Please Select New Plan");
                        }

                        if (res.plancode == '35') {
                            $('#balance_talk').text(res.balance);
                        } else {
                            $('#balance_talk').text('Unlimited');
                        }
                        $('#balance_sms').text('Unlimited');
                        $('#balance_data').text(res.databalance);
                        $('#balance_wallet').text('$' + res.balance_wallet);
                        $('#balance_expire').text(res.expirydate);

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
            var mdn = $('#mdn').val();

            var data = {
                _token: '{{ csrf_token() }}',
                mdn: mdn,
                customer_id: $('#customer_id').val(),
                denom_id: $('#denom_id').val(),
                denom: $('#denom').val(),
                pin: $('#pin').val()
            };

            myApp.showLoading();
            $.ajax({
                url: '/gen/redemption/process',
                data: data,
                type: 'post',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();

                    if (res.code == '0') {
                        // alert(res.data.msg + ' [MDN: ' + res.data.mdn + ']');
                        $('#td_invoice_no').text(res.data.id);
                        $('#td_phone').text(mdn);

                        $('#pin').val('');

                        $('#success').modal();
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
                                <img src="/mall/assets/layouts/layout3/img/logo-gen.png" alt="logo"
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
{{--                                <a href="/gen/recharge" class="btn dark"> <h4>PIN Redemption</h4> </a>--}}
                                <a href="/gen/activate" class="btn grey"><h4>ACTIVATION (SPR)</h4></a>
                                <a href="/gen/activate-tmo" class="btn grey"><h4>ACTIVATION (TMO)</h4></a>
                                <a href="/gen/recharge" class="btn grey"><h4>Recharge</h4></a>
                                <a href="/gen/redemption" class="btn dark"><h4>PIN Redemption</h4></a>
                            </div>

                        </div>
                    </div>
                    <div class="page-head">
                        <div class="container">
                            <!-- BEGIN PAGE TITLE -->
                            <div class="page-title">
                                <h1>PIN Redemption<small></small>
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

                                        <form id="frm_act" method="post" class="form-horizontal" >
                                            {!! csrf_field() !!}

                                            <input type="hidden" id="denom_id" value=""/>
                                            <input type="hidden" id="denom" value=""/>

                                            <div class="col-sm-12">
                                                <div class="form-group">
                                                    <label for="inputEmail12" class="col-md-4 control-label"><span class="sbold"><font color="#4B77BE">Phone #</font></span></label>
                                                    <div class="col-md-4" align="right"
                                                         style="padding-top: 3px;">
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
                                                    <div class="col-md-2">
                                                        <a class="btn btn-info btn-sm" style="margin-top: 4px;" onclick="check_mdn()">
                                                            Enter
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>

                                            <div id="info_box" style="display: none;">

                                                <div class="col-sm-12" style="margin-top: 16px;">
                                                    <div class="form-group">
                                                        <label for="inputEmail12" class="col-md-4 control-label"><span class="sbold"><font color="#4B77BE">Current Plan</font></span></label>
                                                        <div class="col-sm-4" align="left">
                                                            <table class="table table-bordered">
                                                                <thead>
                                                                <tr>
                                                                    <th style="text-align:center;">Amount</th>
                                                                    <th>Plan Description</th>
                                                                </tr>
                                                                </thead>
                                                                <tbody>
                                                                <tr>
                                                                    <td id="current_plan_amount"></td>
                                                                    <td id="current_plan_name"></td>
                                                                </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        <div class="divider2"></div>
                                                    </div>
                                                </div>

                                                <div class="col-sm-12" style="margin-top: 16px;">
                                                    <div class="form-group">
                                                        <label for="inputEmail12" class="col-md-4 control-label"><span class="sbold"><font color="#4B77BE">Balance</font></span></label>
                                                        <div class="col-md-4" align="right"
                                                             style="padding-top: 3px;">
                                                            <table class="table table-bordered">
                                                                <tbody id="balance_box">
                                                                <tr><td>Talk:</td><td id="balance_talk">Unlimited</td></tr>
                                                                <tr><td>SMS:</td><td id="balance_sms">Unlimited</td></tr>
                                                                <tr><td>Data:</td><td id="balance_data">1.00 GB</td></tr>
                                                                <tr><td>Wallet</td><td id="balance_wallet">$0</td></tr>
                                                                <tr><td>Expire Date:</td><td id="balance_expire"></td></tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        <div class="divider2"></div>
                                                    </div>
                                                </div>

                                                <div class="col-sm-12">
                                                    <div class="form-group">
                                                        <label for="inputEmail12" class="col-md-4
                                                        control-label"><span class="sbold"><font color="#4B77BE">PIN
                                                                    #</font></span></label>
                                                        <div class="col-md-4" align="right"
                                                             style="padding-top: 3px;">
                                                            <input type="text" class="form-control"
                                                                   id="pin"
                                                                   name="pin"
                                                                   value=""
                                                                   maxlength="25"
                                                                   placeholder="Refill PIN"
                                                            />
                                                            <div id="error_msg_esn"></div>
                                                        </div>
                                                        <div class="col-md-2">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-sm-12 marginbot10" style="margin-top: 16px;">
                                                    <div class="form-group">
                                                        <div class="col-md-4" align="right"></div>
                                                        <div class="col-md-4" align="right">
                                                            <button type="button" class="btn btn-primary" style="margin-top: 16px;"
                                                                    onclick="confirm_order()">
                                                                Submit
                                                            </button>
                                                        </div>
                                                        <div class="col-md-2"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- End info box -->

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
                    <a target="_blank" href="http://SoftPayPlus.com">SoftPayPlus.com</a> All rights Reserved.
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
                    <div class="col-sm-4">Carrier</div>
                    <div class="col-sm-8">GEN Mobile</div>
                </div>
                <div class="row">
                    <div class="col-sm-4">Phone</div>
                    <div class="col-sm-8" id="td_phone"></div>
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