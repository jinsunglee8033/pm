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

        function request_eprovision() {

            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/tools/freeup/eprovision',
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
                    url: '/sub-agent/tools/freeup/eprovision/update',
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
                            <li class="active">FreeUp</li>
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
                                <a href="/sub-agent/activate/freeup">FreeUp Activation</a>
                            </li>
                            <li>
                                <a href="/sub-agent/rtr/domestic">FreeUp RTR</a>
                            </li>
                            <li>
                                <a href="/sub-agent/pin/domestic">FreeUp PIN</a>
                            </li>
                            <li>
                                <a href="/sub-agent/reports/transaction">FreeUp Transaction</a>
                            </li>
                            <li class="active">
                                <a href="/sub-agent/tools/freeup" class="black-tab">FreeUp Tools</a>
                            </li>
                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content" style="padding-top:24px;">


                            <div class="tabbable tab-lg">
                                <!-- Nav tabs -->
                                <ul class="nav nav-tabs" role="tablist">
                                    @if (\App\Lib\Helper::has_ecommerce_sim())
                                    <li role="presentation">
                                        <a href="#eprovision" aria-controls="eprovision" role="tab" data-toggle="tab">eProvision</a></li>
                                    @endif
                                </ul>

                                <!-- Tab panes -->
                                <div class="tab-content">

                                    @if (\App\Lib\Helper::has_ecommerce_sim())
                                    <div role="tabpanel" class="tab-pane active" id="eprovision" style="padding:15px;">

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
