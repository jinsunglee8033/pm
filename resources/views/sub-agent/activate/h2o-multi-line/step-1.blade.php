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

        function change_layout(class_name) {
            if (class_name == 'multi-4') {
                $('.multi-4').show();
                $('.multi-2').hide();
                $('#sim_qty').val(4);
            } else {
                $('.multi-2').show();
                $('.multi-4').hide();
                $('#sim_qty').val(2);
                $('[name=sim3]').val('')
                $('[name=sim4').val('')
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
                            <span style="font-weight:bold; color:blue">Your new phone number is {{ session('phone') }}.</span><br/>
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
        <marquee behavior="scroll" direction="left" onmouseover="this.stop();" onmouseout="this.start();">{!! Helper::get_promotion('H2O') !!}</marquee>
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
                            <form id="frm_act" method="post" action="/sub-agent/activate/h2o-multi-line/step-1/check-sim" class="row form-horizontal" onsubmit="myApp.showLoading()">
                                {!! csrf_field() !!}
                                <input type="hidden" name="sim_qty" id="sim_qty" value="2"/>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group" style="margin-bottom:0px;">
                                            <marquee behavior="scroll" direction="left" onmouseover="this.stop();" onmouseout="this.start();">{!! Helper::get_reminder('H2O') !!}</marquee>
                                        </div>
                                    </div>
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <h4><b>Step 1</b> : <i>Link H2O Wireless SIMs and purchase plan</i></h4>
                                        </div>

                                    </div>

                                    <div class="divider2"></div>

                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="control-label col-sm-10 col-sm-offset-2" style="text-align:left !important;">SIM Verification : only $30 SIMs</label>
                                        </div>
                                        <div class="divider2"></div>
                                        <div class="form-group{{ $errors->has('sim1') ? ' has-error' : '' }}">
                                            <label class="control-label col-sm-2">SIM #1</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" name="sim1"
                                                       value="{{ old('sim1', isset($sim_info) ? $sim_info->sim1 : '') }}" maxlength="20"
                                                       placeholder="20 digits and digits only"/>
                                                @if ($errors->has('sim1'))
                                                    <span class="help-block">
                                                    <strong>{{ $errors->first('sim1') }}</strong>
                                                </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="form-group{{ $errors->has('sim2') ? ' has-error' : '' }}">
                                            <label class="control-label col-sm-2">SIM #2</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" name="sim2"
                                                       value="{{ old('sim2', isset($sim_info) ? $sim_info->sim2 : '') }}" maxlength="20"
                                                       placeholder="20 digits and digits only"/>
                                                @if ($errors->has('sim2'))
                                                    <span class="help-block">
                                                    <strong>{{ $errors->first('sim2') }}</strong>
                                                </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="form-group multi-4{{ $errors->has('sim3') ? ' has-error' : '' }}" style="display:{{ old('sim_qty', isset($sim_info) ? $sim_info->sim_qty : '') == 4 ? '' : 'none'  }}">
                                            <label class="control-label col-sm-2">SIM #3</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" name="sim3"
                                                       value="{{ old('sim3', isset($sim_info) ? $sim_info->sim3 : '') }}" maxlength="20"
                                                       placeholder="20 digits and digits only"/>
                                                @if ($errors->has('sim3'))
                                                    <span class="help-block">
                                                    <strong>{{ $errors->first('sim3') }}</strong>
                                                </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="form-group multi-4{{ $errors->has('sim4') ? ' has-error' : '' }}" style="display:{{ old('sim_qty', isset($sim_info) ? $sim_info->sim_qty : '') == 4 ? '' : 'none'  }}">
                                            <label class="control-label col-sm-2">SIM #4</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" name="sim4"
                                                       value="{{ old('sim4', isset($sim_info) ? $sim_info->sim4 : '') }}" maxlength="20"
                                                       placeholder="20 digits and digits only"/>
                                                @if ($errors->has('sim4'))
                                                    <span class="help-block">
                                                    <strong>{{ $errors->first('sim4') }}</strong>
                                                </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="divider2"></div>
                                        <div class="form-group text-right">
                                            <button type="button" class="btn btn-info multi-2" onclick="change_layout('multi-4')" style="display:{{ old('sim_qty', isset($sim_info) ? $sim_info->sim_qty : 2) == 2 ? '' : 'none'  }}">+</button>
                                            <button type="button" class="btn btn-default multi-4" onclick="change_layout('multi-2')" style="display:{{ old('sim_qty', isset($sim_info) ? $sim_info->sim_qty : '') == 4 ? '' : 'none'  }}">-</button>
                                            <button type="submit" class="btn btn-primary">SUBMIT</button>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="control-label col-sm-10 col-sm-offset-2" style="text-align:left !important;">Payment : from customer</label>
                                        </div>
                                        <div class="divider2"></div>
                                        <div class="form-group">
                                            <label class="control-label col-sm-2">LINE #1</label>
                                            <div class="col-sm-10">
                                                <span class="form-control" readonly>$25</span>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label col-sm-2">LINE #2</label>
                                            <div class="col-sm-10">
                                                <span class="form-control" readonly>$25</span>
                                            </div>
                                        </div>

                                        <div class="form-group multi-4" style="display:none">
                                            <label class="control-label col-sm-2">LINE #3</label>
                                            <div class="col-sm-10">
                                                <span class="form-control" readonly>$25</span>
                                            </div>
                                        </div>
                                        <div class="form-group multi-4" style="display:none">
                                            <label class="control-label col-sm-2">LINE #4</label>
                                            <div class="col-sm-10">
                                                <span class="form-control" readonly>$25</span>
                                            </div>
                                        </div>

                                        <div class="divider2"></div>
                                        <div class="form-group">
                                            <label class="control-label col-sm-2">
                                                Total:
                                            </label>
                                            <div class="col-sm-10">
                                                <span class="form-control multi-2" readonly>$50</span>
                                                <span class="form-control multi-4" style="display:none" readonly>$100</span>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="divider2"></div>
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
