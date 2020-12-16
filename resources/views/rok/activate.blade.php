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
            if (onload_func) {
                onload_func();
            }

            @if (session()->has('activated') && session('activated') == 'Y')
            $('#success').modal();
            @endif

            @if (session()->has('success') && session('success') == 'Y')
            $('#success').modal();
            @endif

            @if ($errors->any())
            $('#error').modal();
            @endif
        };

        function sim_changed() {
            refresh_page();
        }

        function denom_changed() {
            @if (!empty($has_mapping_esn) && $has_mapping_esn)
                $('#act_esn').val('');
            @endif

            refresh_page();
        }

        function refresh_page() {
            $('#action_type').val('search');
            $('#frm_activate').submit();
        }

        function activate() {
            $('#action_type').val('post');
            $('#frm_activate').attr('action', '/rok/activate/post');
            $('#frm_activate').submit();
        }

        function printDiv() {
            window.print();
        }
    </script>
</head>
<!-- END HEAD -->

<body class="page-container-bg-solid">

    @if (session()->has('success') && session('success') == 'Y')
        <div id="success" class="modal fade " tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false"
             style="display:block;">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Activate Success</h4>
                    </div>
                    <div class="modal-body receipt">
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
                            <div class="col-sm-8">{{ session('invoice_no') }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Phone no.</div>
                            <div class="col-sm-8">{{ session('phone') }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">SIM</div>
                            <div class="col-sm-8">{{ session('sim') }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">SIM Type</div>
                            <div class="col-sm-8">{{ session('sim_type') }}</div>
                        </div>
                        @if(!empty(session('esn')))
                            <div class="row">
                                <div class="col-sm-4">ESN</div>
                                <div class="col-sm-8">{{ session('esn') }}</div>
                            </div>
                        @endif
                        <div class="row">
                            <div class="col-sm-4">Carrier</div>
                            <div class="col-sm-8">{{ session('carrier') }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Product</div>
                            <div class="col-sm-8">{{ session('product') }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Plan Price</div>
                            <div class="col-sm-8">${{ number_format(session('amount'), 2) }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Refill Month</div>
                            <div class="col-sm-8">{{ session('rtr_month') }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Sub Total</div>
                            <div class="col-sm-8">${{ number_format(session('sub_total'), 2) }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Vendor Fee</div>
                            <div class="col-sm-8">${{ number_format(session('fee'), 2) }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Total</div>
                            <div class="col-sm-8">${{ number_format(session('total'), 2) }}</div>
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

    @if (session()->has('activated') && session('activated') == 'Y')
        <div id="success" class="modal fade " tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false"
             style="display:block;">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Activate Success</h4>
                    </div>
                    <div class="modal-body receipt">
                        <p>
                            <span style="font-weight:bold; color:blue">Your new phone number is {{ session('phone') }}
                                .</span><br/>
                            And email or SMS will be delivered shortly after for first month RTR.<br/>
                            Or please refer to "Reports -> Activation / Port-In" for more information.
                        </p>
                        <div class="row">
                            <div class="col-sm-4">Date / Time</div>
                            <div class="col-sm-8">{{ date('Y-M-d H:i:s') }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Invoice no.</div>
                            <div class="col-sm-8">{{ session('invoice_no') }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Phone no.</div>
                            <div class="col-sm-8">{{ session('phone') }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">SIM</div>
                            <div class="col-sm-8">{{ session('sim') }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">SIM Type</div>
                            <div class="col-sm-8">{{ session('sim_type') }}</div>
                        </div>
                        @if(!empty(session('esn')))
                            <div class="row">
                                <div class="col-sm-4">ESN</div>
                                <div class="col-sm-8">{{ session('esn') }}</div>
                            </div>
                        @endif
                        <div class="row">
                            <div class="col-sm-4">Carrier</div>
                            <div class="col-sm-8">{{ session('carrier') }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Product</div>
                            <div class="col-sm-8">{{ session('product') }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Plan Price</div>
                            <div class="col-sm-8">${{ number_format(session('amount'), 2) }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Refill Month</div>
                            <div class="col-sm-8">{{ session('rtr_month') }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Phone</div>
                            <div class="col-sm-8">{{ session('phone') }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Sub Total</div>
                            <div class="col-sm-8">${{ number_format(session('sub_total'), 2) }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Vendor Fee</div>
                            <div class="col-sm-8">${{ number_format(session('fee'), 2) }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Total</div>
                            <div class="col-sm-8">${{ number_format(session('total'), 2) }}</div>
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

    @if ($errors->any())
        <div id="error" class="modal fade " tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false"
             style="display:block;">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" style="color:red">Activate Error</h4>
                    </div>
                    <div class="modal-body">
                        <p>
                            @foreach ($errors->all() as $o)
                                {{ $o }}<br/>
                            @endforeach
                        </p>
                    </div>
                    <div class="modal-footer">
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
                                    <img src="/mall/assets/layouts/layout3/img/logo-default.png" alt="logo"
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
                                    <a href="/rok/activate" class="btn dark"><h4>ACTIVATION</h4></a>
                                    <a href="/rok/recharge" class="btn grey"><h4> RECHARGE</h4></a>
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
                        <div class="page-content">
                            <div class="container">
                                <!-- BEGIN PAGE CONTENT INNER -->
                                <div class="page-content-inner">
                                    <div class="row">
                                        <div class="col-md-12 ">
                                            <!-- BEGIN SAMPLE FORM PORTLET-->
                                            <form class="form-horizontal" role="form" id="frm_activate" method="post"
                                                  action="/rok/activate" onsubmit="myApp.showLoading();">
                                                {!! csrf_field() !!}

                                                @php
                                                    $action_type_org = old('action_type');
                                                    $allowed_denoms_org = (empty($action_type_org) || $action_type_org == 'search') && empty($type) ? '' : old('allowed_denoms', $allowed_denoms);
                                                    $allowed_months_org = (empty($action_type_org) || $action_type_org == 'search') && empty($type) ? '' : old('allowed_months', $allowed_months);
                                                    $allowed_products_org = (empty($action_type_org) || $action_type_org == 'search') && empty($type) ? '' : old('allowed_products', $allowed_products);
                                                    $allowed_months_arr = explode('|', $allowed_months_org);
                                                    $allowed_denoms_arr = explode('|', $allowed_denoms_org);
                                                    $allowed_products_arr = explode('|', $allowed_products_org);

                                                    $has_denom = !empty($allowed_denoms_org) && count($allowed_denoms_arr) > 0;
                                                    $no_denom_not_regular = empty($allowed_denoms_org) && $action_type_org == 'search' &&$type != 'R';
                                                @endphp

                                                <input type="hidden" id="action_type" name="action_type" value=""/>
                                                <input type="hidden" name="selected_product_id" value="{{ $selected_product_id }}"/>
                                                <input type="hidden" name="allowed_months" value="{{ $allowed_months_org }}"/>
                                                <input type="hidden" name="allowed_denoms" value="{{ $allowed_denoms_org }}"/>
                                                <input type="hidden" name="allowed_products" value="{{ $allowed_products_org }}"/>


                                                <div class="form-group" style="{{ old('selected_product_id', $selected_product_id) == 'WROKS' && old('phone_type', $phone_type) == '3g' ? 'display:none;' : '' }}">
                                                        <label class="col-md-4 control-label {{ (old('selected_product_id', $selected_product_id) == 'WROKG' || old('phone_type', $phone_type) == '4g') ? 'required' : '' }}">SIM</label>
                                                        <div class="col-md-4">
                                                            <input type="text" class="form-control" name="sim"
                                                                   placeholder="20 digits and digits only"
                                                                   maxlength="20"
                                                                   value="{{ old('sim', $sim) }}" onchange="sim_changed()">
                                                        </div>
                                                    </div>
                                                    
                                                <div class="form-body">
                                                    <div class="form-group"">
                                                        <label class="col-md-4 control-label"></label>
                                                        <div class="col-md-4">
                                                            <span><font color="#D91E18">Note: (3G Sprint Use HEX Only)
                                                                    All Others use DEC</font></span>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-md-4 control-label required">ESN/IMEI</label>
                                                        <div class="col-md-4">
                                                            <input type="text" class="form-control" name="esn" id="act_esn"
                                                                   placeholder="18 digits and digits only"
                                                                   maxlength="18"
                                                                   value="{{ old('esn', $esn) }}" onchange="refresh_page()"
                                                                   {{ !empty($has_mapping_esn) && $has_mapping_esn ? 'readonly' : '' }}
                                                                   >
                                                        </div>
                                                    </div>

                                                    <div class="form-group">
                                                        <label class="col-md-4 control-label"><span class="sbold"><font
                                                                        color="#4B77BE"></font>
                                                            </span></label>
                                                        <div class="col-md-5">
                                                            @if (!empty($esn_status) && $esn_status == 'U')
                                                            <div class="mt-checkbox-inline" style="color:red;">
                                                                ESN {{ $esn }} is Re-usable.
                                                            </div>
                                                            @endif
                                                            <div class="mt-checkbox-inline">
                                                                Enter in IMEI for Maximize activation bonus, if you don't, enter in 123456789.
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group">
                                                        <label class="col-md-4 control-label"><span class="sbold"><font
                                                                        color="#4B77BE">ZIP</font></span></label>
                                                        <div class="col-md-4">
                                                            <input type="text" class="form-control" name="zip"
                                                                   maxlength="5" placeholder="5 digits and digits only"
                                                                   value="{{ old('zip', $zip) }}">
                                                        </div>
                                                    </div>
                                                    <div class="form-group">

                                                        <div class="col-md-offset-4 col-md-8">
                                                            <div class="col-md-3 ">
                                                                <div class="panel panel-primary">
                                                                    <div class="panel-heading">
                                                                        <h3 class="panel-title">ROK - CDMA</h3>
                                                                    </div>
                                                                    <div class="panel-body">
                                                                        <div class="mt-checkbox-list">
                                                                            @foreach ($denoms_cdma as $o)
                                                                                <label class="mt-checkbox mt-checkbox-outline" style="{{ (
                                                                                    ($has_denom && !in_array($o->denom, $allowed_denoms_arr)) || $no_denom_not_regular ) || (is_array($allowed_products_arr) && !in_array($o->product_id, $allowed_products_arr)) ? 'color:#dddddd' : '' }}">
                                                                                    ${{ number_format($o->denom, 2) }} 
                                                                                    <input type="radio"
                                                                                           onclick="denom_changed()"
                                                                                           {{ ($has_denom && !in_array($o->denom, $allowed_denoms_arr)) || ($no_denom_not_regular) ? 'disabled' : '' }}
                                                                                           {{ is_array($allowed_products_arr) && !in_array($o->product_id, $allowed_products_arr) ? 'disabled' : '' }} value="{{ $o->id }}"
                                                                                           price="{{ $o->denom }}"
                                                                                           name="denom_id" {{ old('denom_id', $denom_id) == $o->id ? 'checked' : ''}} />
                                                                                    <span></span>
                                                                                </label>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="col-md-3 ">
                                                                <div class="panel panel-success">
                                                                    <div class="panel-heading">
                                                                        <h3 class="panel-title">ROK - GSM</h3>
                                                                    </div>
                                                                    <div class="panel-body">
                                                                        <div class="mt-checkbox-list">
                                                                            @foreach ($denoms_gsm as $o)
                                                                                <label class="mt-checkbox mt-checkbox-outline" style="{{ (
                                                                                    ($has_denom && !in_array($o->denom, $allowed_denoms_arr)) || $no_denom_not_regular ) || is_array($allowed_products_arr) && !in_array($o->product_id, $allowed_products_arr) ? 'color:#dddddd' : '' }}">
                                                                                    ${{ number_format($o->denom, 2) }}
                                                                                    <input type="radio"
                                                                                           onclick="denom_changed()"
                                                                                           {{ ($has_denom && !in_array($o->denom, $allowed_denoms_arr)) || $no_denom_not_regular ? 'disabled' : '' }}
                                                                                           {{ is_array($allowed_products_arr) && !in_array($o->product_id, $allowed_products_arr) ? 'disabled' : '' }} value="{{ $o->id }}"
                                                                                           price="{{ $o->denom }}"
                                                                                           name="denom_id" {{ old('denom_id', $denom_id) == $o->id ? 'checked' : ''}} />
                                                                                    <span></span>
                                                                                </label>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="col-md-3 ">
                                                                <div class="panel panel-info">
                                                                    <div class="panel-heading">
                                                                        <h3 class="panel-title">ROK - SPR</h3>
                                                                    </div>
                                                                    <div class="panel-body">
                                                                        <div class="mt-checkbox-list">
                                                                            @foreach ($denoms_spr as $o)
                                                                                <label class="mt-checkbox mt-checkbox-outline" style="{{ (
                                                                                    ($has_denom && !in_array($o->denom, $allowed_denoms_arr)) || $no_denom_not_regular ) || is_array($allowed_products_arr) && !in_array($o->product_id, $allowed_products_arr) ? 'color:#dddddd' : '' }}">
                                                                                    ${{ number_format($o->denom, 2) }}
                                                                                    <input type="radio"
                                                                                           onclick="denom_changed()"
                                                                                           {{ ($has_denom && !in_array($o->denom, $allowed_denoms_arr)) || $no_denom_not_regular ? 'disabled' : '' }}
                                                                                           {{ is_array($allowed_products_arr) && !in_array($o->product_id, $allowed_products_arr) ? 'disabled' : '' }} value="{{ $o->id }}"
                                                                                           price="{{ $o->denom }}"
                                                                                           name="denom_id" {{ old('denom_id', $denom_id) == $o->id ? 'checked' : ''}} />
                                                                                    <span></span>
                                                                                </label>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                        </div>

                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-md-4 control-label"><span class="sbold"><font
                                                                        color="#4B77BE">Activation.Month</font></span></label>
                                                        <div class="col-md-8">
                                                            <div class="mt-radio-inline">
                                                                <label class="mt-radio" style="{{ is_array($allowed_months_arr) && !in_array(1, $allowed_months_arr) ? 'color:#dddddd' : '' }}">
                                                                    <input type="radio" name="rtr_month"
                                                                           value="1" {{ is_array($allowed_months_arr) && !in_array(1, $allowed_months_arr) ? 'disabled' : '' }} {{ old('rtr_month', $rtr_month) == 1 ? 'checked' : '' }}>
                                                                    1 Month
                                                                    <span></span>
                                                                </label>
                                                                <label class="mt-radio" style="{{ is_array($allowed_months_arr) && !in_array(2, $allowed_months_arr) ? 'color:#dddddd' : '' }}">
                                                                    <input type="radio" name="rtr_month"
                                                                           value="2" {{ is_array($allowed_months_arr) && !in_array(2, $allowed_months_arr) ? 'disabled' : '' }} {{ old('rtr_month', $rtr_month) == 2 ? 'checked' : '' }}>
                                                                    2 Month
                                                                    <span></span>
                                                                </label>
                                                                <label class="mt-radio" style="{{ is_array($allowed_months_arr) && !in_array(3, $allowed_months_arr) ? 'color:#dddddd' : '' }}">
                                                                    <input type="radio" name="rtr_month"
                                                                           value="3" {{ is_array($allowed_months_arr) && !in_array(3, $allowed_months_arr) ? 'disabled' : '' }} {{ old('rtr_month', $rtr_month) == 3 ? 'checked' : '' }}>
                                                                    3 Month
                                                                    <span></span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group" style="{{ $selected_product_id != 'WROKS' ? 'display:none;' : '' }}">
                                                        <label class="col-md-4 control-label"><span class="sbold"><font
                                                                        color="#4B77BE">Phone Type</font></span></label>
                                                        <div class="col-md-8">
                                                            <div class="mt-radio-inline">
                                                                <label class="mt-radio">
                                                                    <input type="radio" name="phone_type"
                                                                           onclick="refresh_page()"
                                                                           {{ !empty(session('denom')) && session('denom')->product_id == 'WROKC' ? 'disabled' : '' }}
                                                                           value="3g" {{ old('phone_type', $phone_type) == '3g' ? 'checked' : '' }}>
                                                                    3G Device
                                                                    <span></span>
                                                                </label>
                                                                <label class="mt-radio">
                                                                    <input type="radio" name="phone_type"
                                                                           onclick="refresh_page()"
                                                                           value="4g" {{ old('phone_type', $phone_type) == '4g' ? 'checked' : '' }}>
                                                                    4G Device
                                                                    <span></span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="col-md-offset-4 col-md-8">
                                                            <button type="button" class="btn red" onclick="activate()">Activate Now</button>
                                                        </div>
                                                    </div>
                                                </div>


                                            </form>

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