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
                    url: '/sub-agent/tools/att/simswap',
                    data: {
                        denom_id: $('#denom_id').val(),
                        mdn: $('#mdn').val(),
                        new_sim: $('#new_sim').val()
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

        function request_plan_change() {

            myApp.showConfirm("Are you sure to change plan?", function() {
                myApp.showLoading();
                $.ajax({
                    url: '/sub-agent/tools/att/changeplan',
                    data: {
                        denom_id: $('#pc_denom_id').val(),
                        mdn: $('#pc_mdn').val()
                    },
                    type: 'get',
                    dataType: 'json',
                    cache: false,
                    success: function(res) {
                        myApp.hideLoading();
                        if (res.code === '0') {
                            myApp.showSuccess('Your Plan has been updated as requested. Please be sure to recharge with new plan right now', function() {
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

        function request_eprovision() {

            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/tools/att/eprovision',
                data: {
                    sim: $('#ep_sim').val()
                },
                type: 'get',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();
                    if (res.code === '0') {
                        $('#ep_denom_id').val(res.denom_id);
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

        function request_eprovision_update() {

            myApp.showConfirm("Are you sure to assign new plan?", function() {
                myApp.showLoading();
                $.ajax({
                    url: '/sub-agent/tools/att/eprovision/update',
                    data: {
                        denom_id: $('#ep_denom_id').val(),
                        sim: $('#ep_sim').val()
                    },
                    type: 'get',
                    dataType: 'json',
                    cache: false,
                    success: function(res) {
                        myApp.hideLoading();
                        if (res.code === '0') {
                            myApp.showSuccess('Your Plan has been updated as requested.', function() {
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
                            <li><a href="#">Tools</a></li>
                            <li class="active">ATT</li>
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
                                <a href="/sub-agent/activate/att">ATT Activation</a>
                            </li>
                            <li class="active">
                                <a href="/sub-agent/tools/att" class="black-tab">ATT Tools</a>
                            </li>
                            @if (\App\Lib\Helper::has_att_batch_authority())
{{--                                <li>--}}
{{--                                    <a href="/sub-agent/tools/att-batch">AT&T Schedule</a>--}}
{{--                                </li>--}}
                            @endif
                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content" style="padding-top:24px;">


                            <div class="tabbable tab-lg">
                                <!-- Nav tabs -->
                                <ul class="nav nav-tabs" role="tablist">
{{--                                    <li role="presentation" class="active"><a href="#simswap" aria-controls="simswap"--}}
{{--                                                                              role="tab"--}}
{{--                                                                              data-toggle="tab">SwapSIM</a></li>--}}
                                    <li role="presentation"><a href="#changeplan" aria-controls="changeplan" role="tab"
                                                               data-toggle="tab">Change Plan</a></li>
                                    @if (\App\Lib\Helper::has_ecommerce_sim())
                                    <li role="presentation"><a href="#eprovision" aria-controls="eprovision" role="tab"
                                                               data-toggle="tab">eProvision</a></li>
                                    @endif
                                </ul>

                                <!-- Tab panes -->
                                <div class="tab-content">

                                    <div role="tabpanel" class="tab-pane active" id="simswap" style="padding:15px;">

                                        <div class="row marginbot15" style="margin-top: 32px;">
                                            <div class="col-md-12">
                                                <div class="col-sm-4" align="right">
                                                    <div class="form-group">
                                                        <label class="required">Plan</label>
                                                    </div>
                                                </div>

                                                <div class="col-md-5">
                                                    <select class="form-control" id="denom_id">
                                                        @foreach ($denoms as $d)
                                                        <option value="{{ $d->id }}">{{ '$' . $d->denom . ' (' . $d->name . ')' }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="col-sm-4" align="right">
                                                    <div class="form-group">
                                                        <label class="required">Phone #</label>
                                                    </div>
                                                </div>

                                                <div class="col-md-5">
                                                    <input type="text" class="form-control" id="mdn"/>
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="col-sm-4" align="right">
                                                    <div class="form-group">
                                                        <label class="required">New SIM</label>
                                                    </div>
                                                </div>

                                                <div class="col-md-5">
                                                    <input type="text" class="form-control" id="new_sim"/>
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

                                    <div role="tabpanel" class="tab-pane" id="changeplan" style="padding:15px;">
                                        
                                        <form class="form-horizontal well">
                                          <div class="row marginbot15" style="margin-top: 32px;">

                                            <div class="col-md-12">
                                                <div class="col-sm-4" align="right">
                                                    <div class="form-group">
                                                        <label class="required">Phone #</label>
                                                    </div>
                                                </div>

                                                <div class="col-md-5">
                                                    <input type="text" class="form-control" id="pc_mdn"/>
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="col-sm-4" align="right">
                                                    <div class="form-group">
                                                        <label class="required">Plan</label>
                                                    </div>
                                                </div>

                                                <div class="col-md-5">
                                                    <select class="form-control" id="pc_denom_id">
                                                        @foreach ($denoms as $d)
                                                        <option value="{{ $d->id }}">{{ '$' . $d->denom . ' (' . $d->name . ')' }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-sm-12">
                                                <div class="col-md-4" align="right"></div>
                                                <div class="col-md-3 col-sm-5">

                                                    <button type="button" class="btn btn-primary" onclick="request_plan_change()">
                                                        Send Plan Change Request
                                                    </button>
                                                </div>
                                                <div class="col-md-1"></div>
                                            </div>
                                          </div>
                                        </form>
                                    </div>
                                    <!-- End Change Plan -->

                                    @if (\App\Lib\Helper::has_ecommerce_sim())
                                    <div role="tabpanel" class="tab-pane" id="eprovision" style="padding:15px;">

                                        <form class="form-horizontal well">
                                            <div class="row marginbot15" style="margin-top: 32px;">

                                                <div class="col-md-12">
                                                    <div class="col-sm-4" align="right">
                                                        <div class="form-group">
                                                            <label class="required">SIM #</label>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-5">
                                                        <input type="text" class="form-control" id="ep_sim"
                                                               onchange="request_eprovision()"/>
                                                    </div>
                                                    <div class="col-sm-3" align="left">
                                                        <a class="btn btn-info btn-xs">
                                                            Enter
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-md-12">
                                                    <div class="col-sm-4" align="right">
                                                        <div class="form-group">
                                                            <label class="required">Plan</label>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-5">
                                                        <select class="form-control" id="ep_denom_id">
                                                            <option value="">Not Assigned</option>
                                                            @foreach ($denoms as $d)
                                                                <option value="{{ $d->id }}">{{ '$' . $d->denom . ' (' . $d->name . ')' }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="col-sm-12">
                                                    <div class="col-md-4" align="right"></div>
                                                    <div class="col-md-3 col-sm-5">

                                                        <button type="button" class="btn btn-primary"
                                                                onclick="request_eprovision_update()">
                                                            Assign new plan
                                                        </button>
                                                    </div>
                                                    <div class="col-md-1"></div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <!-- End E-Provision -->
                                    @endif

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
