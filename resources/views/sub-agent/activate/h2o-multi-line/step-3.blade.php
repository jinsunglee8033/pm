@extends('sub-agent.layout.default')

@section('content')

    <script type="text/javascript">

        window.onload = function () {
            $('.note-check-box').tooltip();

            @if (session()->has('activated') && session('activated') == 'Y')
                $('#success').modal();
            @endif

            @if (session()->has('success') && session('success') == 'Y')
                $('#success').modal();
            @endif

            @if ($errors->has('exception'))
                $('#error').modal();
            @endif
        };

        function change_layout(type, seq) {
            if (type == 'A') {
                $('.P' + seq).hide();
                $('.A' + seq).show();
            } else {
                $('.A' + seq).hide();
                $('.P' + seq).show();
            }
        }
    </script>

    @if (session()->has('success') && session('success') == 'Y')
        <div id="success" class="modal fade " tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false"
             style="display:block;">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Activate / Port-In Success</h4>
                    </div>
                    <div class="modal-body">
                        <p>
                            Your request is being processed.<br/>
                            And email or SMS will be delivered shortly after for new activation phone number.<br/>
                            Or please refer to "Reports -> Activation / Port-In" for more information.
                        </p>
                    </div>
                    <div class="modal-footer">
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
                        <h4 class="modal-title">Activate / Port-In Success</h4>
                    </div>
                    <div class="modal-body">
                        <p>
                            <span style="font-weight:bold; color:blue">Your new phone number is {{ session('phone') }}
                                .</span><br/>
                            And email or SMS will be delivered shortly after for first month RTR.<br/>
                            Or please refer to "Reports -> Activation / Port-In" for more information.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($errors->has('exception'))
        <div id="error" class="modal fade " tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false"
             style="display:block;">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" style="color:red">Activate / Port-In Error</h4>
                    </div>
                    <div class="modal-body">
                        <p>
                            {{ $errors->first('exception') }}
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <!-- Start parallax -->
    <div class="parallax" data-background="/img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
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


    <!-- Start Transactions -->
    <div class="container">
        <marquee behavior="scroll" direction="left" onmouseover="this.stop();"
                 onmouseout="this.start();">{!! Helper::get_promotion('H2O') !!}</marquee>
    </div>
    <!-- End Transactions -->


    <!-- Start contain wrapp -->
    <div class="contain-wrapp padding-bot70" style="padding-top:0px;">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-sm-12">
                    <div class="clearfix"></div>


                    <div class="tabbable tab-lg">
                        <ul class="nav nav-tabs">
                            <li>
                                <a href="/sub-agent/activate/h2o">H2O</a>
                            </li>
                            <li class="active">
                                <a href="/sub-agent/activate/h2o-multi-line/step-1">Multi-Line</a>
                            </li>
                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content" style="padding-top:5px;">
                            <form id="frm_act" method="post" action="/sub-agent/activate/h2o-multi-line/step-2/process">
                                {!! csrf_field() !!}
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group" style="margin-bottom:0px;">
                                            <marquee behavior="scroll" direction="left" onmouseover="this.stop();"
                                                     onmouseout="this.start();">{!! Helper::get_reminder('H2O') !!}</marquee>
                                        </div>
                                    </div>
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <h4><b>Step 3</b> : <i>Confirmation</i></h4>
                                            <p>Current status shown for all lines.</p>
                                        </div>

                                    </div>

                                    <div class="col-sm-12">

                                        <div class="row panel panel-default">
                                            <div class="panel-heading">
                                                <h5><b>SIM #1 : {{ $multi_trans[1]->action }}</b></h5>
                                            </div>

                                            <div class="panel-body">
                                                <div class="col-sm-4">
                                                    <div class="form-group">
                                                        <label>Phone Number</label>
                                                        <input type="text" class="form-control" value="{{ $multi_trans[1]->phone }}" readonly/>
                                                    </div>
                                                </div>
                                                <div class="col-sm-4">
                                                    <div class="form-group">
                                                        <label>SIM</label>
                                                        <input type="text" class="form-control" value="{{ $multi_trans[1]->sim }}" readonly/>
                                                    </div>
                                                </div>
                                                <div class="col-sm-4">
                                                    <div class="form-group">
                                                        <label>Status</label>
                                                        <span class="form-control" readonly>{!! $multi_trans[1]->status_name() !!}</span>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>

                                        <div class="row panel panel-default">
                                            <div class="panel-heading">
                                                <h5><b>SIM #2 : {{ $multi_trans[2]->action }}</b></h5>
                                            </div>

                                            <div class="panel-body">
                                                <div class="col-sm-4">
                                                    <div class="form-group">
                                                        <label>Phone Number</label>
                                                        <input type="text" class="form-control" readonly value="{{ $multi_trans[2]->phone }}"/>
                                                    </div>
                                                </div>
                                                <div class="col-sm-4">
                                                    <div class="form-group">
                                                        <label>SIM</label>
                                                        <input type="text" class="form-control" readonly value="{{ $multi_trans[2]->sim }}"/>
                                                    </div>
                                                </div>
                                                <div class="col-sm-4">
                                                    <div class="form-group">
                                                        <label>Status</label>
                                                        <span class="form-control" readonly>{!! $multi_trans[2]->status_name() !!}</span>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>

                                        @if ($sim_info->sim_qty == 4)
                                        <div class="row panel panel-default">
                                            <div class="panel-heading">
                                                <h5><b>SIM #2 : {{ $multi_trans[3]->action }}</b></h5>
                                            </div>

                                            <div class="panel-body">
                                                <div class="col-sm-4">
                                                    <div class="form-group">
                                                        <label>Phone Number</label>
                                                        <input type="text" class="form-control" readonly value="{{ $multi_trans[3]->phone }}"/>
                                                    </div>
                                                </div>
                                                <div class="col-sm-4">
                                                    <div class="form-group">
                                                        <label>SIM</label>
                                                        <input type="text" class="form-control" readonly value="{{ $multi_trans[3]->sim }}"/>
                                                    </div>
                                                </div>
                                                <div class="col-sm-4">
                                                    <div class="form-group">
                                                        <label>Status</label>
                                                        <span class="form-control" readonly>{!! $multi_trans[3]->status_name() !!}</span>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>

                                        <div class="row panel panel-default">
                                            <div class="panel-heading">
                                                <h5><b>SIM #2 : {{ $multi_trans[4]->action }}</b></h5>
                                            </div>

                                            <div class="panel-body">
                                                <div class="col-sm-4">
                                                    <div class="form-group">
                                                        <label>Phone Number</label>
                                                        <input type="text" class="form-control" readonly value="{{ $multi_trans[4]->phone }}"/>
                                                    </div>
                                                </div>
                                                <div class="col-sm-4">
                                                    <div class="form-group">
                                                        <label>SIM</label>
                                                        <input type="text" class="form-control" readonly value="{{ $multi_trans[4]->sim }}"/>
                                                    </div>
                                                </div>
                                                <div class="col-sm-4">
                                                    <div class="form-group">
                                                        <label>Status</label>
                                                        <span class="form-control" readonly>{!! $multi_trans[4]->status_name() !!}</span>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                        @endif
                                    </div>

                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
    <!-- End contain wrapp -->
@stop
