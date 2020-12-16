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
                            <form id="frm_act" method="post" action="/sub-agent/activate/h2o-multi-line/step-2/process" onsubmit="myApp.showLoading()">
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
                                            <h4><b>Step 2</b> : <i>Activate / Port-In</i></h4>
                                            <p>For each line, choose whether to get a new mobile number or bring current
                                                mobile to H2O.</p>
                                        </div>

                                    </div>

                                    <div class="col-sm-12">

                                        <div class="row panel panel-default">
                                            <div class="panel-heading">
                                                <h5><b>Contact Information</b></h5>
                                            </div>
                                            <div class="panel-body">
                                                <div class="col-sm-12">
                                                    <p style="color:red;">Required if a port-in request is
                                                        submitted.</p>
                                                </div>

                                                <div class="col-sm-6">
                                                    <div class="form-group{{ $errors->has('call_back_phone') ? ' has-error' : '' }}">
                                                        <label>Call Back Number</label>
                                                        <input type="text" name="call_back_phone"
                                                               value="{{ old('call_back_phone') }}"
                                                               class="form-control"/>
                                                        {!! Helper::show_error('call_back_phone') !!}
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                                                        <label>Email Address</label>
                                                        <input type="text" name="email" value="{{ old('email') }}"
                                                               class="form-control"/>
                                                        {!! Helper::show_error('email') !!}
                                                    </div>
                                                </div>
                                            </div>

                                        </div>

                                        <div class="row panel panel-default">
                                            <div class="panel-heading">
                                                <div class="row">
                                                    <div class="col-sm-6">
                                                        <h5><b>SIM #1</b> : {{ $sim_info->sim1 }}</h5>
                                                    </div>

                                                    <div class="col-sm-3">
                                                        <div class="form-group">
                                                            <label>
                                                                <input type="radio" name="sim1_type" value="A"
                                                                       {{ old('sim1_type', 'A') == 'A' ? 'checked' : '' }}
                                                                       onclick="change_layout('A', 1)"/>&nbsp;&nbsp;
                                                                Get a New Number
                                                            </label>
                                                        </div>
                                                    </div>

                                                    <div class="col-sm-3">
                                                        <div class="form-group">
                                                            <label>
                                                                <input type="radio" name="sim1_type" value="P"
                                                                       {{ old('sim1_type', 'A') == 'P' ? 'checked' : '' }}
                                                                       onclick="change_layout('P', 1)"/>&nbsp;&nbsp;
                                                                Port in a Number
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="panel-body">
                                                <div class="col-sm-6 A1 clear" style="display:{{ old('sim1_type', 'A') == 'A' ? '' : 'none' }}">
                                                    <div class="form-group{{ $errors->has('A1_npa') ? ' has-error' : '' }}">
                                                        <label>Pref.Area.Code</label>
                                                        <input type="text" name="A1_npa" value="{{ old('A1_npa') }}"
                                                               class="form-control"/>
                                                        {!! Helper::show_error('A1_npa') !!}
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 A1" style="display:{{ old('sim1_type', 'A') == 'A' ? '' : 'none' }}">
                                                    <div class="form-group{{ $errors->has('A1_zip') ? ' has-error' : '' }}">
                                                        <label>Zip</label>
                                                        <input type="text" name="A1_zip" value="{{ old('A1_zip') }}"
                                                               class="form-control"/>
                                                        {!! Helper::show_error('A1_zip') !!}
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 P1 clear" style="display:{{ old('sim1_type') == 'P' ? '' : 'none' }}">
                                                    <div class="form-group{{ $errors->has('P1_number_to_port') ? ' has-error' : '' }}">
                                                        <label>Port-In Number</label>
                                                        <input type="text" name="P1_number_to_port"
                                                               value="{{ old('P1_number_to_port') }}"
                                                               class="form-control"/>
                                                        {!! Helper::show_error('P1_number_to_port') !!}
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 P1" style="display:{{ old('sim1_type') == 'P' ? '' : 'none' }}">
                                                    <div class="form-group{{ $errors->has('P1_carrier') ? ' has-error' : '' }}">
                                                        <label>Port-In From</label>
                                                        <input type="text" name="P1_carrier"
                                                               value="{{ old('P1_carrier') }}" class="form-control"/>
                                                        {!! Helper::show_error('P1_carrier') !!}
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 P1 clear" style="display:{{ old('sim1_type') == 'P' ? '' : 'none' }}">
                                                    <div class="form-group{{ $errors->has('P1_account_no') ? ' has-error' : '' }}">
                                                        <label>Account #</label>
                                                        <input type="text" name="P1_account_no"
                                                               value="{{ old('P1_account_no') }}" class="form-control"/>
                                                        {!! Helper::show_error('P1_account_no') !!}
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 P1" style="display:{{ old('sim1_type') == 'P' ? '' : 'none' }}">
                                                    <div class="form-group{{ $errors->has('P1_account_pin') ? ' has-error' : '' }}">
                                                        <label>Account PIN</label>
                                                        <input type="text" name="P1_account_pin"
                                                               value="{{ old('P1_account_pin') }}"
                                                               class="form-control"/>
                                                        {!! Helper::show_error('P1_account_pin') !!}
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 P1 clear" style="display:{{ old('sim1_type') == 'P' ? '' : 'none' }}">
                                                    <div class="form-group{{ $errors->has('P1_fname') ? ' has-error' : '' }}">
                                                        <label>First Name</label>
                                                        <input type="text" name="P1_fname" value="{{ old('P1_fname') }}"
                                                               class="form-control"/>
                                                        {!! Helper::show_error('P1_fname') !!}
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 P1" style="display:{{ old('sim1_type') == 'P' ? '' : 'none' }}">
                                                    <div class="form-group{{ $errors->has('P1_lname') ? ' has-error' : '' }}">
                                                        <label>Last Name</label>
                                                        <input type="text" name="P1_lname" value="{{ old('P1_lname') }}"
                                                               class="form-control"/>
                                                        {!! Helper::show_error('P1_lname') !!}
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 P1 clear" style="display:{{ old('sim1_type') == 'P' ? '' : 'none' }}">
                                                    <div class="form-group{{ $errors->has('P1_address1') ? ' has-error' : '' }}">
                                                        <label>Address1</label>
                                                        <input type="text" name="P1_address1"
                                                               value="{{ old('P1_address1') }}" class="form-control"/>
                                                        {!! Helper::show_error('P1_address1') !!}
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 P1" style="display:{{ old('sim1_type') == 'P' ? '' : 'none' }}">
                                                    <div class="form-group{{ $errors->has('P1_address2') ? ' has-error' : '' }}">
                                                        <label>Address2</label>
                                                        <input type="text" name="P1_address2"
                                                               value="{{ old('P1_address2') }}" class="form-control"/>
                                                        {!! Helper::show_error('P1_address2') !!}
                                                    </div>
                                                </div>
                                                <div class="col-sm-4 P1 clear" style="display:{{ old('sim1_type') == 'P' ? '' : 'none' }}">
                                                    <div class="form-group{{ $errors->has('P1_city') ? ' has-error' : '' }}">
                                                        <label>City</label>
                                                        <input type="text" name="P1_city" value="{{ old('P1_city') }}"
                                                               class="form-control"/>
                                                        {!! Helper::show_error('P1_city') !!}
                                                    </div>
                                                </div>
                                                <div class="col-sm-4 P1" style="display:{{ old('sim1_type') == 'P' ? '' : 'none' }}">
                                                    <div class="form-group{{ $errors->has('P1_state') ? ' has-error' : '' }}">
                                                        <label>State</label>
                                                        <select name="P1_state" class="form-control">
                                                            <option value="">Please Select</option>
                                                            @foreach ($states as $o)
                                                                <option value="{{ $o->code }}" {{ old('P1_state') == $o->code ? 'selected' : '' }}>{{ $o->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        {!! Helper::show_error('P1_state') !!}
                                                    </div>
                                                </div>
                                                <div class="col-sm-4 P1" style="display:{{ old('sim1_type') == 'P' ? '' : 'none' }}">
                                                    <div class="form-group{{ $errors->has('P1_zip') ? ' has-error' : '' }}">
                                                        <label>Zip</label>
                                                        <input type="text" name="P1_zip" value="{{ old('P1_zip') }}"
                                                               class="form-control"/>
                                                        {!! Helper::show_error('P1_zip') !!}
                                                    </div>
                                                </div>
                                            </div>

                                        </div>

                                        <!-- SIM 2 -->
                                        <div class="row panel panel-default">
                                            <div class="panel-heading">
                                                <div class="row">
                                                    <div class="col-sm-6">
                                                        <h5><b>SIM #2</b> : {{ $sim_info->sim2 }}</h5>
                                                    </div>

                                                    <div class="col-sm-3">
                                                        <div class="form-group">
                                                            <label>
                                                                <input type="radio" name="sim2_type" value="A"
                                                                       {{ old('sim2_type', 'A') == 'A' ? 'checked' : '' }}
                                                                       onclick="change_layout('A', 2)"/>&nbsp;&nbsp;
                                                                Get a New Number
                                                            </label>
                                                        </div>
                                                    </div>

                                                    <div class="col-sm-3">
                                                        <div class="form-group">
                                                            <label>
                                                                <input type="radio" name="sim2_type" value="P"
                                                                       {{ old('sim2_type', 'A') == 'P' ? 'checked' : '' }}
                                                                       onclick="change_layout('P', 2)"/>&nbsp;&nbsp;
                                                                Port in a Number
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="panel-body">
                                                <div class="col-sm-6 A2 clear" style="display:{{ old('sim2_type', 'A') == 'A' ? '' : 'none' }}">
                                                    <div class="form-group{{ $errors->has('A2_npa') ? ' has-error' : '' }}">
                                                        <label>Pref.Area.Code</label>
                                                        <input type="text" name="A2_npa" value="{{ old('A2_npa') }}"
                                                               class="form-control"/>
                                                        {!! Helper::show_error('A2_npa') !!}
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 A2" style="display:{{ old('sim2_type', 'A') == 'A' ? '' : 'none' }}">
                                                    <div class="form-group{{ $errors->has('A2_zip') ? ' has-error' : '' }}">
                                                        <label>Zip</label>
                                                        <input type="text" name="A2_zip" value="{{ old('A2_zip') }}"
                                                               class="form-control"/>
                                                        {!! Helper::show_error('A2_zip') !!}
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 P2 clear" style="display:{{ old('sim2_type') == 'P' ? '' : 'none'  }}">
                                                    <div class="form-group{{ $errors->has('P2_number_to_port') ? ' has-error' : '' }}">
                                                        <label>Port-In Number</label>
                                                        <input type="text" name="P2_number_to_port"
                                                               value="{{ old('P2_number_to_port') }}"
                                                               class="form-control"/>
                                                        {!! Helper::show_error('P2_number_to_port') !!}
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 P2" style="display:{{ old('sim2_type') == 'P' ? '' : 'none'  }}">
                                                    <div class="form-group{{ $errors->has('P2_carrier') ? ' has-error' : '' }}">
                                                        <label>Port-In From</label>
                                                        <input type="text" name="P2_carrier"
                                                               value="{{ old('P2_carrier') }}" class="form-control"/>
                                                        {!! Helper::show_error('P2_carrier') !!}
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 P2 clear" style="display:{{ old('sim2_type') == 'P' ? '' : 'none'  }}">
                                                    <div class="form-group{{ $errors->has('P2_account_no') ? ' has-error' : '' }}">
                                                        <label>Account #</label>
                                                        <input type="text" name="P2_account_no"
                                                               value="{{ old('P2_account_no') }}" class="form-control"/>
                                                        {!! Helper::show_error('P2_account_no') !!}
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 P2" style="display:{{ old('sim2_type') == 'P' ? '' : 'none'  }}">
                                                    <div class="form-group{{ $errors->has('P2_account_pin') ? ' has-error' : '' }}">
                                                        <label>Account PIN</label>
                                                        <input type="text" name="P2_account_pin"
                                                               value="{{ old('P2_account_pin') }}"
                                                               class="form-control"/>
                                                        {!! Helper::show_error('P2_account_pin') !!}
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 P2 clear" style="display:{{ old('sim2_type') == 'P' ? '' : 'none'  }}">
                                                    <div class="form-group{{ $errors->has('P2_fname') ? ' has-error' : '' }}">
                                                        <label>First Name</label>
                                                        <input type="text" name="P2_fname" value="{{ old('P2_fname') }}"
                                                               class="form-control"/>
                                                        {!! Helper::show_error('P2_fname') !!}
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 P2" style="display:{{ old('sim2_type') == 'P' ? '' : 'none'  }}">
                                                    <div class="form-group{{ $errors->has('P2_lname') ? ' has-error' : '' }}">
                                                        <label>Last Name</label>
                                                        <input type="text" name="P2_lname" value="{{ old('P2_lname') }}"
                                                               class="form-control"/>
                                                        {!! Helper::show_error('P2_lname') !!}
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 P2 clear" style="display:{{ old('sim2_type') == 'P' ? '' : 'none'  }}">
                                                    <div class="form-group{{ $errors->has('P2_address1') ? ' has-error' : '' }}">
                                                        <label>Address1</label>
                                                        <input type="text" name="P2_address1"
                                                               value="{{ old('P2_address1') }}" class="form-control"/>
                                                        {!! Helper::show_error('P2_address1') !!}
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 P2" style="display:{{ old('sim2_type') == 'P' ? '' : 'none'  }}">
                                                    <div class="form-group{{ $errors->has('P2_address2') ? ' has-error' : '' }}">
                                                        <label>Address2</label>
                                                        <input type="text" name="P2_address2"
                                                               value="{{ old('P2_address2') }}" class="form-control"/>
                                                        {!! Helper::show_error('P2_address2') !!}
                                                    </div>
                                                </div>
                                                <div class="col-sm-4 P2 clear" style="display:{{ old('sim2_type') == 'P' ? '' : 'none'  }}">
                                                    <div class="form-group{{ $errors->has('P2_city') ? ' has-error' : '' }}">
                                                        <label>City</label>
                                                        <input type="text" name="P2_city" value="{{ old('P2_city') }}"
                                                               class="form-control"/>
                                                        {!! Helper::show_error('P2_city') !!}
                                                    </div>
                                                </div>
                                                <div class="col-sm-4 P2" style="display:{{ old('sim2_type') == 'P' ? '' : 'none'  }}">
                                                    <div class="form-group{{ $errors->has('P2_state') ? ' has-error' : '' }}">
                                                        <label>State</label>
                                                        <select name="P2_state" class="form-control">
                                                            <option value="">Please Select</option>
                                                            @foreach ($states as $o)
                                                                <option value="{{ $o->code }}" {{ old('P2_state') == $o->code ? 'selected' : '' }}>{{ $o->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        {!! Helper::show_error('P2_state') !!}
                                                    </div>
                                                </div>
                                                <div class="col-sm-4 P2" style="display:{{ old('sim2_type') == 'P' ? '' : 'none'  }}">
                                                    <div class="form-group{{ $errors->has('P2_zip') ? ' has-error' : '' }}">
                                                        <label>Zip</label>
                                                        <input type="text" name="P2_zip" value="{{ old('P2_zip') }}"
                                                               class="form-control"/>
                                                        {!! Helper::show_error('P2_zip') !!}
                                                    </div>
                                                </div>
                                            </div>

                                        </div>

                                    @if ($sim_info->sim_qty == 4)
                                        <!-- SIM3 -->
                                            <div class="row panel panel-default">
                                                <div class="panel-heading">
                                                    <div class="row">
                                                        <div class="col-sm-6">
                                                            <h5><b>SIM #3</b> : {{ $sim_info->sim3 }}</h5>
                                                        </div>

                                                        <div class="col-sm-3">
                                                            <div class="form-group">
                                                                <label>
                                                                    <input type="radio" name="sim3_type" value="A"
                                                                           {{ old('sim3_type', 'A') == 'A' ? 'checked' : '' }}
                                                                           onclick="change_layout('A', 3)"/>&nbsp;&nbsp;
                                                                    Get a New Number
                                                                </label>
                                                            </div>
                                                        </div>

                                                        <div class="col-sm-3">
                                                            <div class="form-group">
                                                                <label>
                                                                    <input type="radio" name="sim3_type" value="P"
                                                                           {{ old('sim3_type', 'A') == 'P' ? 'checked' : '' }}
                                                                           onclick="change_layout('P', 3)"/>&nbsp;&nbsp;
                                                                    Port in a Number
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="panel-body">
                                                    <div class="col-sm-6 A3 clear" style="display:{{ old('sim3_type', 'A') == 'A' ? '' : 'none' }}">
                                                        <div class="form-group{{ $errors->has('A3_npa') ? ' has-error' : '' }}">
                                                            <label>Pref.Area.Code</label>
                                                            <input type="text" name="A3_npa"
                                                                   value="{{ old('A3_npa') }} " class="form-control"/>
                                                            {!! Helper::show_error('A3_npa') !!}
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 A3" style="display:{{ old('sim3_type', 'A') == 'A' ? '' : 'none' }}">
                                                        <div class="form-group{{ $errors->has('A3_zip') ? ' has-error' : '' }}">
                                                            <label>Zip</label>
                                                            <input type="text" name="A3_zip" value="{{ old('A3_zip') }}"
                                                                   class="form-control"/>
                                                            {!! Helper::show_error('A3_zip') !!}
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 P3 clear" style="display:{{ old('sim3_type') == 'P' ? '' : 'none'  }}">
                                                        <div class="form-group{{ $errors->has('P3_number_to_port') ? ' has-error' : '' }}">
                                                            <label>Port-In Number</label>
                                                            <input type="text" name="P3_number_to_port"
                                                                   value="{{ old('P3_number_to_port') }}"
                                                                   class="form-control"/>
                                                            {!! Helper::show_error('P3_number_to_port') !!}
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 P3" style="display:{{ old('sim3_type') == 'P' ? '' : 'none'  }}">
                                                        <div class="form-group{{ $errors->has('P3_carrier') ? ' has-error' : '' }}">
                                                            <label>Port-In From</label>
                                                            <input type="text" name="P3_carrier"
                                                                   value="{{ old('P3_carrier') }}"
                                                                   class="form-control"/>
                                                            {!! Helper::show_error('P3_carrier') !!}
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 P3 clear" style="display:{{ old('sim3_type') == 'P' ? '' : 'none'  }}">
                                                        <div class="form-group{{ $errors->has('P3_account_no') ? ' has-error' : '' }}">
                                                            <label>Account #</label>
                                                            <input type="text" name="P3_account_no"
                                                                   value="{{ old('P3_account_no') }}"
                                                                   class="form-control"/>
                                                            {!! Helper::show_error('P3_account_no') !!}
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 P3" style="display:{{ old('sim3_type') == 'P' ? '' : 'none'  }}">
                                                        <div class="form-group{{ $errors->has('P3_account_pin') ? ' has-error' : '' }}">
                                                            <label>Account PIN</label>
                                                            <input type="text" name="P3_account_pin"
                                                                   value="{{ old('P3_account_pin') }}"
                                                                   class="form-control"/>
                                                            {!! Helper::show_error('P3_account_pin') !!}
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 P3 clear" style="display:{{ old('sim3_type') == 'P' ? '' : 'none'  }}">
                                                        <div class="form-group{{ $errors->has('P3_fname') ? ' has-error' : '' }}">
                                                            <label>First Name</label>
                                                            <input type="text" name="P3_fname"
                                                                   value="{{ old('P3_fname') }}" class="form-control"/>
                                                            {!! Helper::show_error('P3_fname') !!}
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 P3" style="display:{{ old('sim3_type') == 'P' ? '' : 'none'  }}">
                                                        <div class="form-group{{ $errors->has('P3_lname') ? ' has-error' : '' }}">
                                                            <label>Last Name</label>
                                                            <input type="text" name="P3_lname"
                                                                   value="{{ old('P3_lname') }}" class="form-control"/>
                                                            {!! Helper::show_error('P3_lname') !!}
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 P3 clear" style="display:{{ old('sim3_type') == 'P' ? '' : 'none'  }}">
                                                        <div class="form-group{{ $errors->has('P3_address1') ? ' has-error' : '' }}">
                                                            <label>Address3</label>
                                                            <input type="text" name="P3_address1"
                                                                   value="{{ old('P3_address1') }}"
                                                                   class="form-control"/>
                                                            {!! Helper::show_error('P3_address1') !!}
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 P3" style="display:{{ old('sim3_type') == 'P' ? '' : 'none'  }}">
                                                        <div class="form-group{{ $errors->has('P3_address2') ? ' has-error' : '' }}">
                                                            <label>Address2</label>
                                                            <input type="text" name="P3_address2"
                                                                   value="{{ old('P3_address2') }}"
                                                                   class="form-control"/>
                                                            {!! Helper::show_error('P3_address2') !!}
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-4 P3 clear" style="display:{{ old('sim3_type') == 'P' ? '' : 'none'  }}">
                                                        <div class="form-group{{ $errors->has('P3_city') ? ' has-error' : '' }}">
                                                            <label>City</label>
                                                            <input type="text" name="P3_city"
                                                                   value="{{ old('P3_city') }}" class="form-control"/>
                                                            {!! Helper::show_error('P3_city') !!}
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-4 P3" style="display:{{ old('sim3_type') == 'P' ? '' : 'none'  }}">
                                                        <div class="form-group{{ $errors->has('P3_state') ? ' has-error' : '' }}">
                                                            <label>State</label>
                                                            <select name="P3_state" class="form-control">
                                                                <option value="">Please Select</option>
                                                                @foreach ($states as $o)
                                                                    <option value="{{ $o->code }}" {{ old('P3_state') == $o->code ? 'selected' : '' }}>{{ $o->name }}</option>
                                                                @endforeach
                                                            </select>
                                                            {!! Helper::show_error('P3_state') !!}
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-4 P3" style="display:{{ old('sim3_type') == 'P' ? '' : 'none'  }}">
                                                        <div class="form-group{{ $errors->has('P3_zip') ? ' has-error' : '' }}">
                                                            <label>Zip</label>
                                                            <input type="text" name="P3_zip" value="{{ old('P3_zip') }}"
                                                                   class="form-control"/>
                                                            {!! Helper::show_error('P3_zip') !!}
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>

                                            <!-- SIM 4 -->
                                            <div class="row panel panel-default">
                                                <div class="panel-heading">
                                                    <div class="row">
                                                        <div class="col-sm-6">
                                                            <h5><b>SIM #4</b> : {{ $sim_info->sim4 }}</h5>
                                                        </div>

                                                        <div class="col-sm-3">
                                                            <div class="form-group">
                                                                <label>
                                                                    <input type="radio" name="sim4_type" value="A"
                                                                           {{ old('sim4_type', 'A') == 'A' ? 'checked' : '' }}
                                                                           onclick="change_layout('A', 4)"/>&nbsp;&nbsp;
                                                                    Get a New Number
                                                                </label>
                                                            </div>
                                                        </div>

                                                        <div class="col-sm-3">
                                                            <div class="form-group">
                                                                <label>
                                                                    <input type="radio" name="sim4_type" value="P"
                                                                           {{ old('sim4_type', 'A') == 'P' ? 'checked' : '' }}
                                                                           onclick="change_layout('P', 4)"/>&nbsp;&nbsp;
                                                                    Port in a Number
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="panel-body">
                                                    <div class="col-sm-6 A4 clear" style="display:{{ old('sim4_type', 'A') == 'A' ? '' : 'none' }}">
                                                        <div class="form-group{{ $errors->has('A4_npa') ? ' has-error' : '' }}">
                                                            <label>Pref.Area.Code</label>
                                                            <input type="text" name="A4_npa" value="{{ old('A4_npa') }}"
                                                                   class="form-control"/>
                                                            {!! Helper::show_error('A4_npa') !!}
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 A4" style="display:{{ old('sim4_type', 'A') == 'A' ? '' : 'none' }}">
                                                        <div class="form-group{{ $errors->has('A4_zip') ? ' has-error' : '' }}">
                                                            <label>Zip</label>
                                                            <input type="text" name="A4_zip" value="{{ old('A4_zip') }}"
                                                                   class="form-control"/>
                                                            {!! Helper::show_error('A4_zip') !!}
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 P4 clear" style="display:{{ old('sim4_type') == 'P' ? '' : 'none'  }}">
                                                        <div class="form-group{{ $errors->has('P4_number_to_port') ? ' has-error' : '' }}">
                                                            <label>Port-In Number</label>
                                                            <input type="text" name="P4_number_to_port"
                                                                   value="{{ old('P4_number_to_port') }}"
                                                                   class="form-control"/>
                                                            {!! Helper::show_error('P4_number_to_port') !!}
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 P4" style="display:{{ old('sim4_type') == 'P' ? '' : 'none'  }}">
                                                        <div class="form-group{{ $errors->has('P4_carrier') ? ' has-error' : '' }}">
                                                            <label>Port-In From</label>
                                                            <input type="text" name="P4_carrier"
                                                                   value="{{ old('P4_carrier') }}"
                                                                   class="form-control"/>
                                                            {!! Helper::show_error('P4_carrier') !!}
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 P4 clear" style="display:{{ old('sim4_type') == 'P' ? '' : 'none'  }}">
                                                        <div class="form-group{{ $errors->has('P4_account_no') ? ' has-error' : '' }}">
                                                            <label>Account #</label>
                                                            <input type="text" name="P4_account_no"
                                                                   value="{{ old('P4_account_no') }}"
                                                                   class="form-control"/>
                                                            {!! Helper::show_error('P4_account_no') !!}
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 P4" style="display:{{ old('sim4_type') == 'P' ? '' : 'none'  }}">
                                                        <div class="form-group{{ $errors->has('P4_account_pin') ? ' has-error' : '' }}">
                                                            <label>Account PIN</label>
                                                            <input type="text" name="P4_account_pin"
                                                                   value="{{ old('P4_account_pin') }}"
                                                                   class="form-control"/>
                                                            {!! Helper::show_error('P4_account_pin') !!}
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 P4 clear" style="display:{{ old('sim4_type') == 'P' ? '' : 'none'  }}">
                                                        <div class="form-group{{ $errors->has('P4_fname') ? ' has-error' : '' }}">
                                                            <label>First Name</label>
                                                            <input type="text" name="P4_fname"
                                                                   value="{{ old('P4_fname') }}" class="form-control"/>
                                                            {!! Helper::show_error('P4_fname') !!}
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 P4" style="display:{{ old('sim4_type') == 'P' ? '' : 'none'  }}">
                                                        <div class="form-group{{ $errors->has('P4_lname') ? ' has-error' : '' }}">
                                                            <label>Last Name</label>
                                                            <input type="text" name="P4_lname"
                                                                   value="{{ old('P4_lname') }}" class="form-control"/>
                                                            {!! Helper::show_error('P4_lname') !!}
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 P4 clear" style="display:{{ old('sim4_type') == 'P' ? '' : 'none'  }}">
                                                        <div class="form-group{{ $errors->has('P4_address1') ? ' has-error' : '' }}">
                                                            <label>Address4</label>
                                                            <input type="text" name="P4_address1"
                                                                   value="{{ old('P4_address1') }}"
                                                                   class="form-control"/>
                                                            {!! Helper::show_error('P4_address1') !!}
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 P4" style="display:{{ old('sim4_type') == 'P' ? '' : 'none'  }}">
                                                        <div class="form-group{{ $errors->has('P4_address2') ? ' has-error' : '' }}">
                                                            <label>Address2</label>
                                                            <input type="text" name="P4_address2"
                                                                   value="{{ old('P4_address2') }}"
                                                                   class="form-control"/>
                                                            {!! Helper::show_error('P4_address2') !!}
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-4 P4 clear" style="display:{{ old('sim4_type') == 'P' ? '' : 'none'  }}">
                                                        <div class="form-group{{ $errors->has('P4_city') ? ' has-error' : '' }}">
                                                            <label>City</label>
                                                            <input type="text" name="P4_city"
                                                                   value="{{ old('P4_city') }}" class="form-control"/>
                                                            {!! Helper::show_error('P4_city') !!}
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-4 P4" style="display:{{ old('sim4_type') == 'P' ? '' : 'none'  }}">
                                                        <div class="form-group{{ $errors->has('P4_state') ? ' has-error' : '' }}">
                                                            <label>State</label>
                                                            <select name="P4_state" class="form-control">
                                                                <option value="">Please Select</option>
                                                                @foreach ($states as $o)
                                                                    <option value="{{ $o->code }}" {{ old('P4_state') == $o->code ? 'selected' : '' }}>{{ $o->name }}</option>
                                                                @endforeach
                                                            </select>
                                                            {!! Helper::show_error('P4_state') !!}
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-4 P4" style="display:{{ old('sim4_type') == 'P' ? '' : 'none'  }}">
                                                        <div class="form-group{{ $errors->has('P4_zip') ? ' has-error' : '' }}">
                                                            <label>Zip</label>
                                                            <input type="text" name="P4_zip" value="{{ old('P4_zip') }}"
                                                                   class="form-control"/>
                                                            {!! Helper::show_error('P4_zip') !!}
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>

                                        @endif

                                        <div class="col-sm-12">
                                            <a href="/sub-agent/activate/h2o-multi-line/step-1" class="btn btn-info">BACK</a>
                                            <button class="btn btn-primary">SUBMIT</button>
                                        </div>
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
