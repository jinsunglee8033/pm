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
            $('.note-check-box').tooltip();

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
            lookup_sim();
        }

        function denom_changed() {
            @if (!empty($has_mapping_esn) && $has_mapping_esn)
                $('#act_esn').val('');
            @endif

            lookup_sim();
        }

        function lookup_sim() {

            myApp.showLoading();
            $('#frm_act').prop('action', '/sub-agent/activate/rok');
            $('#frm_act').submit();
        }

        function portin_checked() {
            @if (!empty($has_mapping_esn) && $has_mapping_esn)
                $('#act_esn').val('');
            @endif

            myApp.showLoading();
            $('#frm_act').prop('action', '/sub-agent/activate/rok');
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
            @if (!empty($has_mapping_esn) && $has_mapping_esn)
                $('#act_esn').val('');
            @endif

            myApp.showLoading();
            $('#frm_act').prop('action', '/sub-agent/activate/rok');
            $('#frm_act').submit();
        }

        function request_activation() {
            myApp.showLoading();
            $('#frm_act').prop('action', '/sub-agent/activate/rok/post');
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
                        <h4 class="modal-title">Activate / Port-In Success</h4>
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
                        <h4 class="modal-title" style="color:red">Activate / Port-In Error</h4>
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
        <!--table class="parameter-product table-bordered table-hover table-condensed filter" style="margin-bottom:0px;">
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

                                <input type="checkbox" checked onclick="return false;" class="note-check-box"
                                       data-toggle="tooltip" data-placement="top" title="{{ $o->note }}"/>
                            @else

                            @endif
                        </td>
                        <td>{{ $o->created_by }}</td>
                        <td>{{ Carbon\Carbon::parse($o->cdate)->format('m/d/Y') }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="20" class="text-center">No Record Found</td>
                </tr>
            @endif
            </tbody>
        </table-->
        <!--marquee behavior="scroll" direction="left" onmouseover="this.stop();"
                 onmouseout="this.start();">{!! Helper::get_promotion('ROK') !!}</marquee-->
    </div>
    <!-- End Transactions -->


    <!-- Start contain wrapp -->
    <div class="contain-wrapp padding-bot70 no-print" style="">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-sm-12">
                    <div class="clearfix"></div>


                    <div class="tabbable tab">
                        <ul class="nav nav-tabs">
                            <li class="active">
                                <a href="/sub-agent/activate/rok" class="black-tab">ROK Mobile</a>
                            </li>
                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content" style="padding-top:10px;">
                            <form id="frm_act" method="post" class="row marginbot15">
                                {!! csrf_field() !!}
                                <input type="hidden" name="enabled_product_id" value="{{ $enabled_product_id }}"/>
                                <!--div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group" style="margin-bottom:0px;">
                                            <marquee behavior="scroll" direction="left" onmouseover="this.stop();"
                                                     onmouseout="this.start();">{!! Helper::get_reminder('ROK') !!}</marquee>
                                        </div>
                                    </div>
                                </div>

                                <div class="divider2"></div-->

                             <div class="col-sm-12">
                                <div class="col-sm-4" align="right"
                                     style="{{ old('enabled_product_id', $enabled_product_id) == 'WROKS' && old('phone_type', $phone_type) == '3g' ? 'visibility:hidden;' : '' }}">
                                    <div class="form-group{{ $errors->has('sim') ? ' has-error' : '' }}">
                                        <label class="{{ old('phone_type', $phone_type) == '4g' || old('enabled_product_id', $enabled_product_id) == 'WROKG' ? 'required' : ''}}">SIM</label>
                                    </div>
                                </div>
                                 <div class="col-sm-5" align="right"
                                     style="{{ old('enabled_product_id', $enabled_product_id) == 'WROKS' && old('phone_type', $phone_type) == '3g' ? 'visibility:hidden;' : '' }}">
                                    <div class="form-group {{ $errors->has('sim') ? ' has-error' : '' }}">
                                        <input type="text" class="form-control" name="sim"
                                               onchange="sim_changed()"
                                               value="{{ old('enabled_product_id', $enabled_product_id) == 'WROKS' && old('phone_type', $phone_type) == '3g' ? '' : old('sim', $sim) }}"
                                               maxlength="20"
                                               placeholder="20 digits and digits only"
                                               />
                                        {!! Helper::show_error('sim') !!}
                                    </div>
                                </div> 
                                <div class="col-sm-2">
                                    <label>Credit {{ ($sim_type == 'P' && $sim_spiff_amt == 0) ? 'Already Paid' : '$' . $sim_spiff_amt }}</label>
                                </div>

                            </div>  
 
                             <div class="col-sm-12">
                                <div class="col-sm-4" align="right">
                                </div>
                                <div class="col-sm-5">
                                    <div class="form-group{{ $errors->has('esn') ? ' has-error' : '' }}">
                                        <span style="font-size: 12px; margin-left: 10px;color:red;"><strong>Note: (3G Sprint Use HEX Only) All Others use DEC</strong></span>
                                    </div>
                                </div>                      

                            </div> 
 
                             <div class="col-sm-12">
                                <div class="col-sm-4" align="right">
                                    <div class="form-group{{ $errors->has('esn') ? ' has-error' : '' }}">
                                        <label class="required">ESN / IMEI</label>
                                    </div>
                                </div>
                                <div class="col-sm-5">
                                    <div class="form-group{{ $errors->has('esn') ? ' has-error' : '' }}">
                                        <input type="text" class="form-control" name="esn" id="act_esn"
                                               value="{{ old('esn', $esn) }}" maxlength="18" onchange="lookup_sim()"
                                               placeholder="{{ old('enabled_product_id', $enabled_product_id) == 'WROKS' && old('phone_type', $phone_type) == '3g' ? '18 characters and alpha numeric only' : '18 digits and digits only' }}"
                                               {{ !empty($has_mapping_esn) && $has_mapping_esn ? 'readonly' : '' }}/>

                                        {!! Helper::show_error('esn') !!}
                                        @if (!empty($esn_status) && $esn_status == 'U')
                                        <span style="font-size: 12px; margin-left: 10px;color:red;">
                                            ESN {{ $esn }} is Re-usable.
                                        </span><br>
                                        @endif
                                        <span style="font-size: 12px; margin-left: 10px">  Enter in IMEI for Maximize activation bonus, if you don't, enter in 123456789.</span>
                                    </div>
								</div>
                                <div class="col-sm-2">
                                    <label>Credit {{ ($esn_type == 'P' && ($esn_spiff_amt + $dvc_rebate_amt) == 0) ? 'Already Paid' : '$' . ($esn_spiff_amt + $dvc_rebate_amt) }}</label>
                                </div>
                                                        

							</div> 

                            <div class="col-sm-12">

                                <div class="col-sm-4" align="right">
                                    <div class="form-group{{ $errors->has('zip') ? ' has-error' : '' }}">
                                        <label id="lbl_zip" class="required">Zip</label>

                                    </div>
                                </div>
   
                                <div class="col-sm-5">
                                    <div class="form-group {{ $errors->has('zip') ? ' has-error' : '' }}">
                                        <input type="text" class="form-control" name="zip" maxlength="5"
                                               value="{{ old('zip', $zip) }}" placeholder="5 digits and digits only"/>
                                        {!! Helper::show_error('zip') !!}
                                    </div>
                                </div>                             
                                
                             <div class="col-sm-2"></div>
								</div>
                                    
                                    

                                <!--div class="col-sm-3" id="div_npa"
                                     style="display:{{ old('port_in', $port_in) == 'Y' ? 'none' : '' }}">
                                    <div class="form-group{{ $errors->has('npa') ? ' has-error' : '' }}">
                                        <label class="required">Pref. Area Code</label>
                                        <input type="text" class="form-control" name="npa" maxlength="3"
                                               value="{{ old('npa', $npa) }}" placeholder="3 digits and digits only"/>
                                        {!! Helper::show_error('npa') !!}
                                    </div>
                                </div-->



                                <div class="divider2"></div>

                                <div class="col-sm-12">
                                 <div class="col-sm-4" align="right">

                                    <div class="form-group{{ $errors->has('denom_id') ? ' has-error' : '' }}">
                                        <label class="required">Product</label>
                                        
									 </div>
									</div>
                                       
                                 <div class="col-sm-5">
                                        @if(isset($products))
                                            @foreach ($products as $o)
                                                <div class="col-sm-4">
                                                    @if ($o->id == 'WROKC')
                                                        <label style="background-color: #D32023; border:solid 1px black; color: white; padding: 2px 10px 2px 10px;">{{ $o->name }}</label>
                                                    @elseif ($o->id == 'WROKG')
                                                        <label style="background-color: #5BCEFA; border:solid 1px black; padding: 2px 10px 2px 10px;">{{ $o->name }}</label>
                                                    @else
                                                        <label style="background-color: #FFE007; border:solid 1px black; padding: 2px 10px 2px 10px;">{{ $o->name }}</label>
                                                    @endif

                                                <!-- Start radio -->
                                                    @if (isset($o->activation_denominations))
                                                        @foreach($o->activation_denominations as $d)
                                                            <div class="radio">
                                                                <label style="{{ $lock_product == 'Y' && old('denom_id', $denom_id) == $d->id ? 'color:red' : '' }}; {{ (!empty($allowed_product_id) && $allowed_product_id != $d->product_id || ($lock_product == 'Y' && !in_array($d->denom, $allowed_denoms))) ? 'font-weight:normal; color:#efefef;' : 'font-weight:bold;' }}">
                                                                    <input type="radio" onclick="product_selected()"
                                                                           {{ (!empty($allowed_product_id) && $allowed_product_id != $d->product_id || ($lock_product == 'Y' && !in_array($d->denom, $allowed_denoms))) ? 'disabled' : '' }} name="denom_id"
                                                                           value="{{ $d->id }}" {{ old('denom_id', $denom_id) == $d->id ? 'checked' : '' }}>
                                                                    {{ $d->name }}
                                                                </label>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            @endforeach
                                        @endif
                                        {!! Helper::show_error('denom_id') !!}
									</div><div class="col-sm-2"></div>

                                </div>

                                <div class="col-sm-12">
                                   <div class="col-sm-4" align="right">
                                      <label class="required">Activation.Month</label>
									</div>
                                   <div class="col-sm-5">
                                   
                                    <div class="form-group{{ $errors->has('month') ? ' has-error' : '' }}">                                   
                                        <div>
                                            <label style="{{ in_array(1, $allowed_months) ? '' : 'font-weight:normal; color:#efefef' }}">
                                                <input type="radio" style="margin-bottom:5px;" class="radio-inline"
                                                       onclick="product_selected()"
                                                       {{ in_array(1, $allowed_months) ? '' : 'disabled' }} name="rtr_month"
                                                       value="1" {{ old('rtr_month', $rtr_month) == 1 ? 'checked' : '' }}>
                                                1 Month
                                            </label>
                                            <label style="margin-left:15px;{{ in_array(2, $allowed_months) ? '' : 'font-weight:normal; color:#efefef' }}">
                                                <input type="radio" style="margin-bottom:5px;" class="radio-inline"
                                                       onclick="product_selected()"
                                                       {{ in_array(2, $allowed_months) ? '' : 'disabled' }} name="rtr_month"
                                                       value="2" {{ old('rtr_month', $rtr_month) == 2 ? 'checked' : '' }}>
                                                2 Month
                                            </label>
                                            <label style="margin-left:15px;{{ in_array(3, $allowed_months) ? '' : 'font-weight:normal; color:#efefef' }}">
                                                <input type="radio" style="margin-bottom:5px;" class="radio-inline"
                                                       onclick="product_selected()"
                                                       {{ in_array(3, $allowed_months) ? '' : 'disabled' }} name="rtr_month"
                                                       value="3" {{ old('rtr_month', $rtr_month) == 3 ? 'checked' : '' }}>
                                                3 Month
                                            </label>
                                        </div>
                                        {!! Helper::show_error('phone_type') !!}
                                    </div>
									</div><div class="col-sm-2"></div>
                                </div>


                             <div class="col-sm-12" style="{{ old('enabled_product_id', $enabled_product_id) == 'WROKG' ? 'display:none;' : '' }}">
                                <div class="col-sm-4" align="right">
                                </div>
                                <div class="col-sm-5">
                                    <div class="form-group{{ $errors->has('esn') ? ' has-error' : '' }}">
                                        <span style="margin-left: 10px;color:red;"><strong>Please select Phone type</strong></span>
                                    </div>
                                </div>                      

                            </div> 

                                <div class="col-sm-12"
                                     style="{{ old('enabled_product_id', $enabled_product_id) == 'WROKG' ? 'display:none;' : '' }}">
                                   <div class="col-sm-4" align="right">
                                    <div class="form-group{{ $errors->has('phone_type') ? ' has-error' : '' }}">
                                        <label class="required">Phone Type</label>

									   </div></div>
									<div class="col-sm-5">
                                            <label style="{{ old('enabled_product_id', $enabled_product_id) == 'WROKC' ? 'color: #efefef;' : '' }}">
                                                <input type="radio" class="radio-inline" onclick="product_selected()"
                                                       {{ old('enabled_product_id', $enabled_product_id) == 'WROKC' ? 'disabled' : '' }} name="phone_type"
                                                       value="3g" {{ old('phone_type', $phone_type) == '3g' ? 'checked' : '' }}/>
                                                3G Device
                                            </label>
                                            <label style="margin-left:20px;">
                                                <input type="radio" class="radio-inline" onclick="product_selected()"
                                                       name="phone_type"
                                                       value="4g" {{ old('phone_type', $phone_type) == '4g' ? 'checked' : '' }}/>
                                                4G Device
                                            </label>
                                        {!! Helper::show_error('phone_type') !!}
									</div><div class="col-sm-2"></div>
                                </div>



                                <div class="divider2"></div>
                                <div class="col-sm-12">
                                 <div class="col-sm-4" align="right">
                                    <div class="form-group">
                                        <label>Port-In ?</label>
                                    </div>
									</div>
                                    <div class="col-sm-5">

                                    <div class="form-group">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="port_in"
                                                       {{ old('port_in', $port_in) == 'Y' ? 'checked' : '' }} value="Y"
                                                       onclick="portin_checked()">I'd like to port-in my old phone
                                                number.
                                            </label>
                                        </div>
                                        {!! Helper::show_error('port_in') !!}
										</div><div class="col-sm-2"></div>
									</div>
                              </div>
                                
                                <div class="col-md-12">
                                 <div class="col-sm-4" align="right">
                                    <div class="form-group">
                                        <label>Note</label>
                                    </div>
								</div>
                                <div class="col-sm-5">
                                    <div class="form-group">
                                        <textarea class="form-control" name="note"></textarea>
                                    </div>
									</div><div class="col-sm-2"></div>
                                </div>


                                <div class="divider2 port-in"
                                     style="display:{{ old('port_in', $port_in) == 'Y' ? '' : 'none' }};"></div>

                                <div class="port-in"
                                     style="display:{{ old('port_in', $port_in) == 'Y' ? '' : 'none' }};">
                                    <div class="col-sm-12">
                                       <div class="col-sm-4" align="right">
                                       
                                        <div class="form-group{{ $errors->has('number_to_port') ? ' has-error' : '' }}">
                                            <label class="required">Port-In Number</label>

                                        </div>
										</div> 
										<div class="col-sm-5">
                                       
                                        <div class="form-group{{ $errors->has('number_to_port') ? ' has-error' : '' }}">
                                            <input type="text" class="form-control" name="number_to_port"
                                                   value="{{ old('number_to_port', $number_to_port) }}" maxlength="10"
                                                   placeholder="10 digits and digits only"/>
                                            {!! Helper::show_error('number_to_port') !!}
                                        </div>
										</div>
                                   <div class="col-sm-2"></div>
                                    </div>

                                    <div class="col-sm-12">
                                       <div class="col-sm-4" align="right">
                                        <div class="form-group{{ $errors->has('current_carrier_id') ? ' has-error' : '' }}">
                                            <label class="required">Port-In From</label>
                                        </div>
										</div>
                                        <div class="col-sm-5">
                                        <div class="form-group{{ $errors->has('current_carrier_id') ? ' has-error' : '' }}">
                                            <select class="form-control" name="current_carrier_id">
                                                @foreach ($carriers as $o)
                                                    <option value="{{ $o->id }}" {{ old('current_carrier_id', $current_carrier_id) == $o->id ? 'selected' : '' }}>{{ $o->name }}</option>
                                                @endforeach
                                                <option value="200">Other</option>
                                            </select>
                                            {!! Helper::show_error('current_carrier_id') !!}
                                        </div>
										</div><div class="col-sm-2"></div>
                                    </div>
                                    
                                    <div class="col-sm-12">
                                       <div class="col-sm-4" align="right">
                                        <div class="form-group{{ $errors->has('account_no') ? ' has-error' : '' }}">
                                            <label class="required">Account #</label>
                                        </div>
										</div>
                                       <div class="col-sm-5">
                                        <div class="form-group{{ $errors->has('account_no') ? ' has-error' : '' }}">
                                            <input type="text" class="form-control" name="account_no"
                                                   value="{{ old('account_no', $account_no) }}"/>
                                            {!! Helper::show_error('account_no') !!}
                                        </div>
										</div><div class="col-sm-2"></div>
                                    </div>
                                    
                                    
                                    <div class="col-sm-12">
                                       <div class="col-sm-4" align="right">
                                        <div class="form-group{{ $errors->has('account_pin') ? ' has-error' : '' }}">
                                            <label class="required">Account PIN</label>
                                        </div>
                                        </div>
                                       <div class="col-sm-5" align="right">
                                        <div class="form-group{{ $errors->has('account_pin') ? ' has-error' : '' }}">
                                            <input type="text" class="form-control" name="account_pin"
                                                   value="{{ old('account_pin', $account_pin) }}"/>
                                            {!! Helper::show_error('account_pin') !!}
                                        </div>
                                        </div><div class="col-sm-2"></div>

                                    </div>


                                </div>

                                <div class="divider2 port-in" style="display:{{ old('port_in', $port_in) == 'Y' ? '' : 'none' }};"></div>

                                <div class="port-in" style="display:{{ old('port_in', $port_in) == 'Y' ? '' : 'none' }};">

                                    <div class="col-sm-12">
                                       <div class="col-sm-4" align="right">                                        
                                           <div class="form-group{{ $errors->has('first_name') ? ' has-error' : '' }}">
                                            <label class="">First Name</label>
                                        </div>                                        
                                        </div>
                                       <div class="col-sm-5">                                        
                                           <div class="form-group{{ $errors->has('first_name') ? ' has-error' : '' }}">
                                            <input type="text" class="form-control" name="first_name"
                                                   value="{{ old('first_name', $first_name) }}"/>
                                            {!! Helper::show_error('first_name') !!}
                                        </div>                                        
                                        </div><div class="col-sm-2"></div>

                                    </div>

                                    <div class="col-sm-12">
                                       <div class="col-sm-4" align="right">   
                                        <div class="form-group{{ $errors->has('last_name') ? ' has-error' : '' }}">
                                            <label class="">Last Name</label>
                                        </div>
                                       </div>
                                       <div class="col-sm-5">   
                                        <div class="form-group{{ $errors->has('last_name') ? ' has-error' : '' }}">
                                            <input type="text" class="form-control" name="last_name"
                                                   value="{{ old('first_name', $last_name) }}"/>
                                            {!! Helper::show_error('last_name') !!}
                                        </div>
                                       </div><div class="col-sm-2"></div>

                                    </div>
                                    <div class="col-sm-12">
                                       <div class="col-sm-4" align="right">   
                                        <div class="form-group{{ $errors->has('address1') ? ' has-error' : '' }}">
                                            <label class="">Address 1</label>
                                        </div>
                                       </div>
                                       <div class="col-sm-5">   
                                        <div class="form-group{{ $errors->has('address1') ? ' has-error' : '' }}">
                                            <input type="text" class="form-control" name="address1"
                                                   value="{{ old('address1', $address1) }}"/>
                                            {!! Helper::show_error('address1') !!}
                                        </div>
                                       </div><div class="col-sm-2"></div>
                                    </div>
                                    
                                    
                                    <div class="col-sm-12">
                                       <div class="col-sm-4" align="right">  
                                        <div class="form-group">
                                            <label>Address 2</label>
                                         </div>
                                       </div>
                                       <div class="col-sm-5">  
                                        <div class="form-group">
                                            <input type="text" class="form-control" name="address2"
                                                   value="{{ old('address2', $address2) }}"/>
                                            {!! Helper::show_error('address2') !!}
                                         </div>
                                       </div><div class="col-sm-2"></div>
                                    </div>
                                    
                                    <div class="col-sm-12">
                                       <div class="col-sm-4" align="right">  
                                        <div class="form-group{{ $errors->has('city') ? ' has-error' : '' }}">
                                            <label class="">City</label>
                                         </div>
                                       </div>
                                       <div class="col-sm-5">  
                                        <div class="form-group{{ $errors->has('city') ? ' has-error' : '' }}">
                                            <input type="text" class="form-control" name="city"
                                                   value="{{ old('city', $city) }}"/>
                                            {!! Helper::show_error('city') !!}
                                         </div>
                                       </div><div class="col-sm-2"></div>
                                    </div>
                                    
                                    
                                    
                                    
                                    <div class="col-sm-12">
                                       <div class="col-sm-4" align="right"> 
                                        <div class="form-group{{ $errors->has('state') ? ' has-error' : '' }}">
                                            <label class="">State</label>
                                        </div>
                                       </div>
                                       <div class="col-sm-5"> 
                                        <div class="form-group{{ $errors->has('state') ? ' has-error' : '' }}">
                                            <select class="form-control" name="state"
                                                    data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                                <option value="">Please Select</option>
                                                @if (isset($states))
                                                    @foreach ($states as $o)
                                                        <option value="{{ $o->code }}" {{ old('state', $state) == $o->code ? 'selected' : '' }}>{{ $o->name }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            {!! Helper::show_error('state') !!}
                                        </div>
                                       </div><div class="col-sm-2"></div>
                                    </div>

                                     <div class="col-sm-12">
                                       <div class="col-sm-4" align="right"> 
                                        <div class="form-group{{ $errors->has('call_back_phone') ? ' has-error' : '' }}">
                                            <label class="required">Call Back #</label>
                                        </div>
                                       </div>
                                       <div class="col-sm-5"> 
                                        <div class="form-group{{ $errors->has('call_back_phone') ? ' has-error' : '' }}">
                                            <input type="text" class="form-control" name="call_back_phone"
                                                   value="{{ old('call_back_phone', $call_back_phone) }}"
                                                   placeholder="10 digits and digits only"/>
                                            {!! Helper::show_error('call_back_phone') !!}
                                            <span style="color:red;">Note: Re-endter call back phone if different</span>
                                        </div>
                                       </div><div class="col-sm-2"></div>
                                    </div>
                                    
                                    
                                    
                                    <div class="col-sm-12">
                                       <div class="col-sm-4" align="right"> 
                                        <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                                            <label class="">Email</label>
                                        </div>
                                    </div>
                                       <div class="col-sm-5"> 
                                        <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                                            <input type="email" class="form-control" name="email"
                                                   value="{{ old('email', $email) }}"/>
                                            {!! Helper::show_error('email') !!}
                                        </div>
                                    </div>
                                   </div><div class="col-sm-2"></div>
                                </div>

                                <div class="divider2"></div>


                                <div class="col-sm-12">
                                   <div class="col-sm-4" align="right">
                                        <label class="">Amount :</label>
                                    </div>
                                   <div class="col-sm-5">   
                                    $<span id="amt">{{ number_format(old('denom', isset($denom->denom) ? $denom->denom : 0), 2) }}</span>
                                    @if ($is_consignment == 'Y')
                                    <div class="note-check-box" data-toggle="tooltip" data-placement="top" title="Consignment Charge" style="display:inline-block; margin-top: 0px; background-color:blue; width:18px; margin-left: 5px; height:18px; margin-right:5px; color:white; text-align:center; line-height: 20px;">C</div>
                                    $<span id="amt">{{ number_format(old('charge_amt', isset($charge_amt) ? $charge_amt : 0), 2) }}</span>
                                    @endif
                                   </div>
                                   <div class="col-sm-2"></div>
                                </div>

                                @if ($is_consignment == 'Y')
                                <div class="col-sm-12">
                                   <div class="col-sm-4" align="right">
                                        <div class="note-check-box" data-toggle="tooltip" data-placement="top" title="Consignment Charge" style="display:inline-block; margin-top: 0px; background-color:blue; width:18px; margin-left: 5px; height:18px; margin-right:5px; color:white; text-align:center; line-height: 20px;">C</div>
                                    </div>
                                   <div class="col-sm-5">   
                                    $<span id="amt">{{ number_format(old('charge_amt', isset($charge_amt) ? $charge_amt : 0), 2) }}</span>
                                   </div>
                                   <div class="col-sm-2"></div>
                                </div>
                                @endif

                                <div class="col-sm-12">
                                   <div class="col-sm-4" align="right">
                                        <label class="">x Month :</label>
                                    </div>
                                   <div class="col-sm-5">   
                                    <span id="rtr_month">{{ number_format(old('rtr_month', isset($rtr_month) ? $rtr_month : 1), 0) }}</span>
                                   </div>
                                   <div class="col-sm-2"></div>
                                </div>

                                <div class="col-sm-12">
                                   <div class="col-sm-4" align="right">
                                        <label class="">Sub Total :</label>
                                    </div>
                                   <div class="col-sm-5">   
                                    $<span id="rtr_month">{{ number_format(old('sub_total', isset($sub_total) ? $sub_total : 0), 2) }}</span>
                                   </div>
                                   <div class="col-sm-2"></div>
                                </div>

                                <div class="col-sm-12">
                                   <div class="col-sm-4" align="right">
                                        <label class="">Vendor Fee :</label>
                                    </div>
                                   <div class="col-sm-5">   
                                    $<span id="rtr_month">{{ number_format(old('fee', isset($fee) ? $fee : 0), 2) }}</span>
                                   </div>
                                   <div class="col-sm-2"></div>
                                </div>

                                <div class="col-sm-12">
                                   <div class="col-sm-4" align="right">
                                        <h5><strong>Total</strong> :</h5>
                                    </div>
                                   <div class="col-sm-5">   
                                    <h5><small>$</small><strong><span id="rtr_month">{{ number_format(old('total', isset($total) ? $total : 0), 2) }}</span></strong></h5>
                                   </div>
                                   <div class="col-sm-2"></div>
                                </div>

                                <div class="divider2"></div>

                            <div class="col-sm-12 marginbot10">
                               <div class="col-md-4" align="right"></div>
                                <div class="col-md-3 col-sm-5">

                                    <button type="button" class="btn btn-primary" onclick="request_activation()">
                                        Activate
                                    </button>
                                    </div><div class="col-md-1"></div>

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
