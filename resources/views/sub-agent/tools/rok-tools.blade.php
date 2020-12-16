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
        };

        function request_sim_swap() {

            myApp.showConfirm("Are you sure to swap the sim?", function() {
                myApp.showLoading();
                $.ajax({
                    url: '/sub-agent/tools/rok/simswap',
                    data: {
                        carrier_id: $('#carrier_id').val(),
                        mdn: $('#mdn').val(),
                        new_sim: $('#new_sim').val(),
                        is_recharge: $("#is_recharge").attr("checked") ? 'Y' : 'N'
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
                            myApp.showError(res.msg);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        myApp.hideLoading();
                        myApp.showError(errorThrown);
                    }
                });
            }, function() {
                // do nothing
            });
        }

        function get_mdninfo() {

            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/tools/rok/mdninfo',
                data: {
                    mdn: $('#g_mdn').val(),
                },
                type: 'get',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();
                    if (res.code === '0') {
                        $.each(res.data, function(k, v) {
                            //display the key and value pair
                            $('#get_mdninfo_body').append('<tr><td>' + k + '</td><td>' + v + '</td></tr>');
                        });
                    } else {
                        myApp.showError(res.msg);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });
        }

        function get_plans() {
            $('#c_plans').empty();
            $('#changeplan_body').empty();

            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/tools/rok/get_plans',
                data: {
                    mdn: $('#p_mdn').val(),
                },
                type: 'get',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();
                    if (res.code === '0') {
                        $.each(res.data, function(k, v) {
                            $('#c_plans').append('<option value="' + v.planFaceValue + '">' + v.planName + '</option>');
                        });

                        $.each(res.info, function(k, v) {
                            $('#changeplan_body').append('<tr><td>' + k + '</td><td>' + v + '</td></tr>');
                        });
                    } else {
                        myApp.showError(res.msg);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });
        }

        function portin_status() {

            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/tools/rok/portin_status',
                data: {
                    mdn: $('#s_mdn').val(),
                },
                type: 'get',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();
                    if (res.code === '0') {
                        $.each(res.data, function(k, v) {
                            //display the key and value pair
                            $('#portinstatus_body').append('<tr><td>' + k + '</td><td>' + v + '</td></tr>');
                        });
                    } else {
                        myApp.showError(res.msg);
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
                        <h4>Dealer Tools</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li class="active">Dealer Tools</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End parallax -->

    <!-- Start contain wrapp -->
    <div class="contain-wrapp padding-bot70 no-print" style="">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-sm-12">
                    <div class="clearfix"></div>


                    <div class="tabbable tab">
                        <ul class="nav nav-tabs">
                            <li>
                                <a href="/sub-agent/activate/rok">ROK Mobile</a>
                            </li>
                            <li class="active">
                                <a href="/sub-agent/tools/rok" class="black-tab">Dealer Tools</a>
                            </li>
                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content" style="padding-top:24px;">


                            <div class="tabbable tab-lg">
                                <!-- Nav tabs -->
                                <ul class="nav nav-tabs" role="tablist">
                                    <li role="presentation" class="active"><a href="#mdninfo" aria-controls="mdninfo" role="tab"
                                                               data-toggle="tab">MDN Info</a></li>
                                    <li role="presentation"><a href="#changeplan" aria-controls="changeplan" role="tab"
                                                               data-toggle="tab">Change Plan</a></li>
                                    <li role="presentation"><a href="#portinstatus" aria-controls="portinstatus" role="tab"
                                                               data-toggle="tab">Portin Status</a></li>
                                    <li role="presentation"><a href="#simswap" aria-controls="simswap"
                                                                              role="tab"
                                                                              data-toggle="tab">SwapSIM</a></li>
                                </ul>

                                <!-- Tab panes -->
                                <div class="tab-content">

                                    <div role="tabpanel" class="tab-pane active" id="mdninfo" style="padding:15px;">
                                        
                                        <form class="form-horizontal well">
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label">Phone #</label>
                                                <div class="col-sm-4">
                                                    <input type="text" class="form-control" id="g_mdn"/>
                                                </div>
                                                <div class="col-sm-6 text-right">
                                                    <a class="btn btn-primary" onclick="get_mdninfo()">Look up</a>
                                                </div>
                                            </div>
                                        </form>

                                        <table class="table table-bordered table-hover table-condensed">
                                            <tbody id="get_mdninfo_body">
                                            </tbody>
                                        </table>
                                    </div>
                                    <!-- End MDN Info -->

                                    <div role="tabpanel" class="tab-pane" id="changeplan" style="padding:15px;">
                                        
                                        <form class="form-horizontal well">
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label">Phone #</label>
                                                <div class="col-sm-4">
                                                    <input type="text" class="form-control" id="p_mdn" onchange="get_plans()"/>
                                                </div>
                                                <div class="col-sm-6 text-right">
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="col-sm-2 control-label">New Plan</label>
                                                <div class="col-sm-4">
                                                    <select class="form-control" id="c_plans">
                                                    </select>
                                                </div>
                                                <div class="col-sm-6 text-right">
                                                </div>
                                            </div>
                                        </form>

                                        <table class="table table-bordered table-hover table-condensed">
                                            <tbody id="changeplan_body">
                                            </tbody>
                                        </table>
                                    </div>
                                    <!-- End Change Plan -->

                                    <div role="tabpanel" class="tab-pane" id="portinstatus" style="padding:15px;">
                                        
                                        <form class="form-horizontal well">
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label">Phone #</label>
                                                <div class="col-sm-4">
                                                    <input type="text" class="form-control" id="s_mdn"/>
                                                </div>
                                                <div class="col-sm-6 text-right">
                                                    <a class="btn btn-primary" onclick="portin_status()">Portin Status</a>
                                                </div>
                                            </div>
                                        </form>

                                        <table class="table table-bordered table-hover table-condensed">
                                            <tbody id="portinstatus_body">
                                            </tbody>
                                        </table>
                                    </div>
                                    <!-- End Protin Status -->

                                    <div role="tabpanel" class="tab-pane" id="simswap" style="padding:15px;">

                                        <div class="row marginbot15" style="margin-top: 32px;">
                                            <div class="col-md-12">
                                                <div class="col-sm-4" align="right">
                                                    <div class="form-group{{ $errors->has('esn') ? ' has-error' : '' }}">
                                                        <label class="required">Carrier</label>
                                                    </div>
                                                </div>

                                                <div class="col-md-5">
                                                    <select class="form-control" id="carrier_id">
                                                        <option value="53">ROK GSM</option>
                                                        <option value="52">ROK CDMA</option>
                                                        <option value="57">ROK SPRINT</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="col-sm-4" align="right">
                                                    <div class="form-group{{ $errors->has('esn') ? ' has-error' : '' }}">
                                                        <label class="required">Phone #</label>
                                                    </div>
                                                </div>

                                                <div class="col-md-5">
                                                    <input type="text" class="form-control" id="mdn"/>
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="col-sm-4" align="right">
                                                    <div class="form-group{{ $errors->has('esn') ? ' has-error' : '' }}">
                                                        <label class="required">New SIM</label>
                                                    </div>
                                                </div>

                                                <div class="col-md-5">
                                                    <input type="text" class="form-control" id="new_sim"/>
                                                </div>
                                            </div>

                                            <div class="col-sm-12" style="margin-bottom: 18px;">
                                                <div class="col-md-4" align="right"></div>
                                                <div class="col-md-3 col-sm-5">
                                                    <input type="checkbox" name="is_recharge" value="Y"/> Recharge after swap ?
                                                </div>
                                            </div>

                                            <div class="col-sm-12">
                                                <div class="col-md-4" align="right"></div>
                                                <div class="col-md-3 col-sm-5">

                                                    <button type="button" class="btn btn-primary" onclick="request_sim_swap()">
                                                        Send Swap SIM Request
                                                    </button>
                                                </div>
                                                <div class="col-md-1"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- End SWAP SIM -->

                                </div>
                                <!-- End Tab Content -->

                            </div>

                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
@stop
