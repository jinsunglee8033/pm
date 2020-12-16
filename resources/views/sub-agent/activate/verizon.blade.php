@extends('sub-agent.layout.default')

@section('content')

    <script type="text/javascript">

        window.onload = function () {
            $('.note-check-box').tooltip();

            @if (session()->has('success') && session('success') == 'Y')
                $('#success').modal();
            @endif

            @if ($errors->has('exception'))
                $('#error').modal();
            @endif
        };
        
        function portin_checked() {
            var checked = $('[name=port_in]').is(':checked');
            if (checked) {
                $('.port-in').show();
                $('#div_npa').hide();
                $('[name=npa]').val('');
            } else {
                $('.port-in').hide();
                $('#div_npa').show();
                $('[name=number_to_port]').val('');
                $('[name=current_carrier]').val('');
                $('[name=account_no]').val('');
                $('[name=account_pin]').val('');
            }
        }

        function product_selected() {
            myApp.showLoading();
            $('#frm_act').prop('action', '/sub-agent/activate/verizon');
            $('#frm_act').submit();
        }

        function request_activation() {
            myApp.showLoading();
            $('#frm_act').prop('action', '/sub-agent/activate/verizon/post');
            $('#frm_act').submit();
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
        <marquee behavior="scroll" direction="left" onmouseover="this.stop();" onmouseout="this.start();">{!! Helper::get_promotion('Verizon') !!}</marquee>
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
                                <a href="/sub-agent/activate/verizon" class="red-tab">Verizon</a>
                            </li>

                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content" style="padding-top:5px;">
                            <form id="frm_act" method="post" class="row marginbot15">
                                {!! csrf_field() !!}
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group" style="margin-bottom:0px;">
                                            <marquee behavior="scroll" direction="left" onmouseover="this.stop();" onmouseout="this.start();">{!! Helper::get_reminder('Verizon') !!}</marquee>
                                        </div>

                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group{{ $errors->has('esn') ? ' has-error' : '' }}">
                                            <label style="display:block;" style="width:100%;">ESN / IMEI</label>
                                            <input type="text" class="form-control" name="esn"
                                                   value="{{ old('esn') }}" maxlength="15" style="width:84%; display:table-cell; clear:both;"
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
                                            <label class="">SIM</label>
                                            <input type="text" class="form-control" name="sim"
                                                   value="{{ old('sim') }}" maxlength="20"
                                                   placeholder="20 digits max and digits only"/>
                                            @if ($errors->has('sim'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('sim') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-sm-6" id="div_npa">
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
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Port-In ?</label>
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" name="port_in"
                                                           {{ old('port_in') == 'Y' ? 'checked' : '' }} value="Y"
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
                                                    <div class="col-sm-6">
                                                        <label>{{ $o->name }}</label>

                                                        <!-- Start radio -->
                                                        @if (isset($o->denominations))
                                                            @foreach($o->denominations as $d)
                                                                <div class="radio">
                                                                    <label>
                                                                        <input type="radio" name="denom_id" onclick="product_selected()" value="{{ $d->id }}" {{ old('denom_id', $denom_id) == $d->id ? 'checked' : '' }}>
                                                                        ${{ $d->denom }}
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

                                <div class="divider2 port-in" style="display:{{ old('port_in') == 'Y' ? '' : 'none' }};"></div>

                                <div class="row port-in" style="display:{{ old('port_in') == 'Y' ? '' : 'none' }};">
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
                                        <div class="form-group{{ $errors->has('current_carrier') ? ' has-error' : '' }}">
                                            <label>Port-In From</label>
                                            <input type="text" class="form-control" name="current_carrier"
                                                   value="{{ old('current_carrier') }}"/>
                                            @if ($errors->has('current_carrier'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('current_carrier') }}</strong>
                                                </span>
                                            @endif
                                        </div>
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
                                </div>
                                <div class="divider2" style="display:none;"></div>
                                <div class="row" style="display:none;">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>First Name</label>
                                            <input type="text" class="form-control" name="first_name"/>
                                        </div>
                                        <div class="form-group{{ $errors->has('call_back_phone') ? ' has-error' : '' }}">
                                            <label>Call Back #</label>
                                            <input type="text" class="form-control" name="call_back_phone" value="{{ old('call_back_phone') }}" placeholder="10 digits and digits only"/>
                                            @if ($errors->has('call_back_phone'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('call_back_phone') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                        <div class="form-group{{ $errors->has('pref_pin') ? ' has-error' : '' }}">
                                            <label>Pref. PIN #</label>
                                            <input type="text" class="form-control" name="pref_pin" value="{{ old('pref_pin') }}" placeholder="4 digits"/>
                                            @if ($errors->has('pref_pin'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('pref_pin') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Last Name</label>
                                            <input type="text" class="form-control" name="last_name" value="{{ old('first_name') }}"/>
                                        </div>
                                        <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                                            <label>Email</label>
                                            <input type="email" class="form-control" name="email" value="{{ old('email') }}"/>
                                            @if ($errors->has('email'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('email') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="divider2"></div>

                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>First Name</label>
                                            <input type="text" class="form-control" name="first_name"
                                                   value="{{ old('first_name') }}"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Last Name</label>
                                            <input type="text" class="form-control" name="last_name"
                                                   value="{{ old('last_name') }}"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Address 1</label>
                                            <input type="text" class="form-control" name="address1"
                                                   value="{{ old('address1') }}"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Address 2</label>
                                            <input type="text" class="form-control" name="address2"
                                                   value="{{ old('address2') }}"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>City</label>
                                            <input type="text" class="form-control" name="city"
                                                   value="{{ old('city') }}"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>State</label>
                                            <select class="form-control" name="state" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                                <option value="">Please Select</option>
                                                @if (isset($states))
                                                    @foreach ($states as $o)
                                                        <option value="{{ $o->code }}" {{ old('state') == $o->code ? 'selected' : '' }}>{{ $o->name }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group{{ $errors->has('zip') ? ' has-error' : '' }}">
                                            <label>Zip</label>
                                            <input type="text" class="form-control" name="zip" maxlength="5"
                                                   value="{{ old('zip') }}" placeholder="5 digits and digits only"/>
                                            @if ($errors->has('zip'))
                                                <span class="help-block">
                                                        <strong>{{ $errors->first('zip') }}</strong>
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
                                        <h5><strong>Total</strong>: $<span id="total">{{ number_format(old('denom', isset($denom->denom) ? $denom->denom : 0), 2) }}</span></h5>
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
