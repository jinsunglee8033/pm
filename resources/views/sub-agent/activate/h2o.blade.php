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
            border-top:0;
        }

    </style>
    <script type="text/javascript">

        var lock_product = '{{ $lock_product }}';

        window.onload = function () {
            $('.note-check-box').tooltip();

            if (lock_product == 'Y') {
                $('[name=denom_id]:not(:checked)').attr('disabled', true);
            }

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

        function lookup_sim() {

            myApp.showLoading();
            $('#frm_act').prop('action', '/sub-agent/activate/h2o');
            $('#frm_act').submit();
        }

        function portin_checked() {

            myApp.showLoading();
            $('#frm_act').prop('action', '/sub-agent/activate/h2o');
            $('#frm_act').submit();
/*
            var checked = $('[name=port_in]').is(':checked');
            if (checked) {
                $('.port-in').show();
                $('#div_npa').hide();
                $('[name=npa]').val('');
                $('#lbl_zip').removeClass('required');
            } else {
                $('#div_npa').show();
                $('.port-in').hide();
                $('[name=number_to_port]').val('');
                $('[name=current_carrier]').val('');
                $('[name=account_no]').val('');
                $('[name=account_pin]').val('');
                $('#lbl_zip').addClass('required');
            }*/
        }

        function product_selected() {

            myApp.showLoading();
            $('#frm_act').prop('action', '/sub-agent/activate/h2o');
            $('#frm_act').submit();
        }

        function request_activation() {
            myApp.showLoading();
            $('#frm_act').prop('action', '/sub-agent/activate/h2o/post');
            $('#frm_act').submit();
        }

        function printDiv() {
            window.print();
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
                    <div class="modal-body receipt">
                        <p>
                            Your request is being processed.<br/>
                            And email or SMS will be delivered shortly after for new activation phone number.<br/>
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
                        <h4 class="modal-title">Activate / Port-In Success</h4>
                    </div>
                    <div class="modal-body receipt">
                        <p>
                            <span style="font-weight:bold; color:blue">Your new phone number is {{ session('phone') }}.</span><br/>
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
    <div class="parallax no-print" data-background="/img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
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
    <div class="container no-print">
        <table class="parameter-product table-bordered table-hover table-condensed filter" style="margin-bottom:0px;">
            <thead>
            <tr class="active">
                <td><strong>Carrier</strong></td>
                <td><strong>Product</strong></td>
                <td><strong>Amt($)</strong></td>
                <td><strong>SIM</strong></td>
                <td><strong>ESN/IMEI</strong></td>
                <td><strong>Pref.Area.Code</strong></td>
                <td><strong>Phone</strong></td>
                <td><strong>Status</strong></td>
                <td><strong>Note</strong></td>
                <td><strong>User.ID</strong></td>
                <td><strong>Created.At</strong></td>
            </tr>
            </thead>
            <tbody>
            @if (isset($transactions) && count($transactions) > 0)
                @foreach ($transactions as $o)
                    <tr>
                        <td>{{ $o->carrier() }}</td>
                        <td>{{ $o->product_name() }}</td>
                        <td>{{ $o->denom }}</td>
                        <td>{{ $o->sim }}</td>
                        <td>{{ $o->esn }}</td>
                        <td>{{ $o->npa }}</td>
                        <td>{{ $o->phone }}</td>

                        @if ($o->status == 'R')
                            <td><a href="/sub-agent/reports/transaction/{{ $o->id }}">{!! $o->status_name() !!}</a></td>
                        @else
                            <td>{!! $o->status_name() !!}</td>
                        @endif
                        <td>
                            @if (!empty($o->note))

                                <input type="checkbox" checked  onclick="return false;" class="note-check-box" data-toggle="tooltip" data-placement="top" title="{{ $o->note }}"/>
                            @else

                            @endif
                        </td>
                        <td>{{ $o->created_by }}</td>
                        <td>{{ Carbon\Carbon::parse($o->cdate)->format('m/d/Y') }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="10" class="text-center">No Record Found</td>
                </tr>
            @endif
            </tbody>
        </table>
        <marquee behavior="scroll" direction="left" onmouseover="this.stop();" onmouseout="this.start();">{!! Helper::get_promotion('H2O') !!}</marquee>
    </div>
    <!-- End Transactions -->


    <!-- Start contain wrapp -->
    <div class="contain-wrapp padding-bot70 no-print" style="padding-top:0px;">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-sm-12">
                    <div class="clearfix"></div>


                    <div class="tabbable tab-lg">
                        <ul class="nav nav-tabs">
                            <li class="active">
                                <a href="/sub-agent/activate/h2o" class="yellow-tab">H2O</a>
                            </li>

                            <!--li>
                                <a href="/sub-agent/activate/h2o-multi-line/step-1">Multi-Line</a>
                            </li-->

                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content" style="padding-top:5px;">
                            <form id="frm_act" method="post" class="row marginbot15">
                                {!! csrf_field() !!}
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group" style="margin-bottom:0px;">
                                            <marquee behavior="scroll" direction="left" onmouseover="this.stop();" onmouseout="this.start();">{!! Helper::get_reminder('H2O') !!}</marquee>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group{{ $errors->has('esn') ? ' has-error' : '' }}">
                                            <label style="display:block; width:100%;" class="">ESN / IMEI</label>
                                            <input type="text" class="form-control" name="esn"
                                                   value="{{ old('esn', $esn) }}" maxlength="15" style="width:84%; display:table-cell; clear:both;"
                                                   placeholder="14 ~ 15 digits and digits only"/>
                                            <input type="text" class="form-control" name="esn_16" placeholder="16th" style="width:15%; display:table-cell"/>

                                            @if ($errors->has('esn'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('esn') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group{{ $errors->has('sim') ? ' has-error' : '' }}">
                                            <label class="required">SIM</label>
                                            <input type="text" class="form-control" name="sim"
                                                   onchange="lookup_sim()"
                                                   value="{{ old('sim', $sim) }}" maxlength="20"
                                                   placeholder="20 digits and digits only"/>
                                            @if ($errors->has('sim'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('sim') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-sm-3" id="div_npa" style="display:{{ old('port_in', $port_in) == 'Y' ? 'none' : '' }}">
                                        <div class="form-group{{ $errors->has('npa') ? ' has-error' : '' }}">
                                            <label class="required">Pref. Area Code</label>
                                            <input type="text" class="form-control" name="npa" maxlength="3"
                                                   value="{{ old('npa') }}" placeholder="3 digits and digits only"/>
                                            @if ($errors->has('npa'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('npa') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-sm-3">
                                        <div class="form-group{{ $errors->has('zip') ? ' has-error' : '' }}">
                                            <label id="lbl_zip" class="required">Zip</label>
                                            <input type="text" class="form-control" name="zip" maxlength="5"
                                                   value="{{ old('zip') }}" placeholder="5 digits and digits only"/>
                                            @if ($errors->has('zip'))
                                                <span class="help-block">
                                                        <strong>{{ $errors->first('zip') }}</strong>
                                                    </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Port-In ?</label>
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" name="port_in"
                                                           {{ old('port_in', $port_in) == 'Y' ? 'checked' : '' }} value="Y"
                                                           onclick="portin_checked()">
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="divider2"></div>

                                    <div class="col-sm-12">


                                        <div class="form-group{{ $errors->has('denom_id') ? ' has-error' : '' }}">
                                            <label class="required">Product</label>
                                            <br>
                                            @if(isset($products))
                                                @foreach ($products as $o)
                                                    <div class="col-sm-2">
                                                        <label>{{ $o->name }}</label>

                                                        <!-- Start radio -->
                                                        @if (isset($o->denominations))
                                                            @foreach($o->denominations as $d)
                                                                <div class="radio">
                                                                    <label style="{{ $lock_product == 'Y' && old('denom_id', $denom_id) == $d->id ? 'color:red' : '' }}">
                                                                        <input type="radio" onclick="product_selected()" name="denom_id" value="{{ $d->id }}" {{ old('denom_id', $denom_id) == $d->id ? 'checked' : '' }}>
                                                                        ${{ $d->denom }}
                                                                        @if (old('denom_id', $denom_id) == $d->id && $rtr_month > 1)
                                                                            x {{ $rtr_month }}
                                                                        @endif
                                                                    </label>
                                                                </div>
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                @endforeach
                                            @endif
                                            @if ($errors->has('denom_id'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('denom_id') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>




                                </div>

                                <div class="divider2 port-in" style="display:{{ old('port_in', $port_in) == 'Y' ? '' : 'none' }};"></div>

                                <div class="row port-in" style="display:{{ old('port_in', $port_in) == 'Y' ? '' : 'none' }};">
                                    <div class="col-sm-6">
                                        <div class="form-group{{ $errors->has('number_to_port') ? ' has-error' : '' }}">
                                            <label class="required">Port-In Number</label>
                                            <input type="text" class="form-control" name="number_to_port"
                                                   value="{{ old('number_to_port') }}" maxlength="10"
                                                   placeholder="10 digits and digits only"/>
                                            @if ($errors->has('number_to_port'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('number_to_port') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="form-group{{ $errors->has('current_carrier') ? ' has-error' : '' }}">
                                            <label class="required">Port-In From</label>
                                            <input type="text" class="form-control" name="current_carrier"
                                                   value="{{ old('current_carrier') }}"/>
                                            @if ($errors->has('current_carrier'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('current_carrier') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group{{ $errors->has('account_no') ? ' has-error' : '' }}">
                                            <label class="required">Account #</label>
                                            <input type="text" class="form-control" name="account_no"
                                                   value="{{ old('account_no') }}"/>
                                            @if ($errors->has('account_no'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('account_no') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group{{ $errors->has('account_pin') ? ' has-error' : '' }}">
                                            <label class="required">Account PIN</label>
                                            <input type="text" class="form-control" name="account_pin"
                                                   value="{{ old('account_pin') }}"/>
                                            @if ($errors->has('account_pin'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('account_pin') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group{{ $errors->has('call_back_phone') ? ' has-error' : '' }}">
                                            <label class="required">Call Back #</label>
                                            <input type="text" class="form-control" name="call_back_phone" value="{{ old('call_back_phone') }}" placeholder="10 digits and digits only"/>
                                            @if ($errors->has('call_back_phone'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('call_back_phone') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                                            <label class="required">Email</label>
                                            <input type="email" class="form-control" name="email" value="{{ old('email') }}"/>
                                            @if ($errors->has('email'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('email') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="required">Under Contract?</label>
                                            <div class="">
                                                <input type="radio" class="radio-inline" name="carrier_contract" value="Y"/> Yes
                                                <input type="radio" class="radio-inline" name="carrier_contract" value="N" checked/> No
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="divider2"></div>

                                <div class="row">

                                    <div class="col-sm-6">
                                        <div class="form-group{{ $errors->has('first_name') ? ' has-error' : '' }}">
                                            <label class="{{ old('port_in', $port_in) == 'Y' ? 'required' : '' }}">First Name</label>
                                            <input type="text" class="form-control" name="first_name"/>
                                            @if ($errors->has('first_name'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('first_name') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="form-group{{ $errors->has('last_name') ? ' has-error' : '' }}">
                                            <label class="{{ old('port_in', $port_in) == 'Y' ? 'required' : '' }}">Last Name</label>
                                            <input type="text" class="form-control" name="last_name" value="{{ old('first_name') }}"/>
                                            @if ($errors->has('last_name'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('last_name') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group{{ $errors->has('address1') ? ' has-error' : '' }}">
                                            <label class="{{ old('port_in', $port_in) == 'Y' ? 'required' : '' }}">Address 1</label>
                                            <input type="text" class="form-control" name="address1"
                                                   value="{{ old('address1') }}"/>
                                            @if ($errors->has('address1'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('address1') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Address 2</label>
                                            <input type="text" class="form-control" name="address2"
                                                   value="{{ old('address2') }}"/>
                                            @if ($errors->has('city'))
                                                <span class="help-block">
                                                    <strong>&nbsp;</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group{{ $errors->has('city') ? ' has-error' : '' }}">
                                            <label class="{{ old('port_in', $port_in) == 'Y' ? 'required' : '' }}">City</label>
                                            <input type="text" class="form-control" name="city"
                                                   value="{{ old('city') }}"/>
                                            @if ($errors->has('city'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('city') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group{{ $errors->has('state') ? ' has-error' : '' }}">
                                            <label class="{{ old('port_in', $port_in) == 'Y' ? 'required' : '' }}">State</label>
                                            <select class="form-control" name="state" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                                <option value="">Please Select</option>
                                                @if (isset($states))
                                                    @foreach ($states as $o)
                                                        <option value="{{ $o->code }}" {{ old('state') == $o->code ? 'selected' : '' }}>{{ $o->name }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            @if ($errors->has('state'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('state') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="divider2"></div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Note</label>
                                            <textarea class="form-control" name="note"></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="divider2"></div>


                                <div class="col-md-6 col-sm-6 marginbot20">
                                    <div class="form-group">
                                        <label><string>Amount</string>: $<span id="amt">{{ number_format(old('denom', isset($denom->denom) ? $denom->denom : 0), 2) }}</span></label><br/>
                                        <label><string>x Month</string>: <span id="rtr_month">{{ number_format(old('denom', isset($rtr_month) ? $rtr_month : 1), 0) }}</span></label><br/>
                                        <label><string>Sub Total</string>: $<span id="rtr_month">{{ number_format(old('denom', isset($sub_total) ? $sub_total : 0), 2) }}</span></label><br/>
                                        <label><string>Vendor Fee</string>: $<span id="rtr_month">{{ number_format(old('denom', isset($fee) ? $fee : 0), 2) }}</span></label><br/>
                                        <hr/>
                                        <h5><strong>Total</strong>: $<span id="total">{{ number_format(old('total', isset($total) ? $total : 0), 2) }}</span></h5>
                                    </div>
                                </div>

                                <div class="col-md-6 col-sm-6 marginbot20">

                                    <button type="button" class="btn btn-primary" onclick="request_activation()">Activate</button>
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
