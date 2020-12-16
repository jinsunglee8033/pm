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

        .content-overlay img {
            opacity: 0.3;
        }

        .content-overlay:hover img {
            opacity: 1.0;
        }

    </style>
    <script type="text/javascript">

        window.onload = function() {
            @if (session()->has('success') && session('success') == 'Y')
            $('#success').modal();
            @endif

            @if ($errors->has('exception'))
            $('#error').modal();
            @endif

            $(".img_1").click(function(){
                $('#carrier').val('FreeUP');
                refresh_page();
            });
            $(".img_2").click(function(){
                $('#carrier').val('Page Plus');
                refresh_page();
            });
            $(".img_3").click(function(){
                $('#carrier').val('Verizon');
                refresh_page();
            });
            $(".img_4").click(function(){
                $('#carrier').val('Air Voice');
                refresh_page();
            });
            $(".img_5").click(function(){
                $('#carrier').val('H2O');
                refresh_page();
            });
            $(".img_6").click(function(){
                $('#carrier').val('AT&T');
                refresh_page();
            });
            $(".img_7").click(function(){
                $('#carrier').val('Red Pocket');
                refresh_page();
            });
            $(".img_8").click(function(){
                $('#carrier').val('Liberty Mobile ');
                refresh_page();
            });
            $(".img_9").click(function(){
                $('#carrier').val('SafeLink ');
                refresh_page();
            });
            $(".img_10").click(function(){
                $('#carrier').val('T-Mobile ');
                refresh_page();
            });
            $(".img_11").click(function(){
                $('#carrier').val('Tracfone ');
                refresh_page();
            });
            $(".img_12").click(function(){
                $('#carrier').val('Net 10 ');
                refresh_page();
            });
            $(".img_13").click(function(){
                $('#carrier').val('Simple ');
                refresh_page();
            });
            $(".img_14").click(function(){
                $('#carrier').val('ROKiT ');
                refresh_page();
            });

        };

        function change_logo($name) {
            $('#img_select').attr('src',$name);
        }

        function refresh_page(flag) {
            if(flag =='s'){
                var c = $('#carrier1').val();
                $('#carrier').val(c);
            }
            $('#frm_rtr').attr('action', '/sub-agent/pin/domestic');
            $('#frm_rtr').submit();
        }

        // function refresh_page() {
        //     $('#frm_rtr').attr('action', '/sub-agent/pin/domestic');
        //     $('#frm_rtr').submit();
        // }

        function confirm_order() {

            var msg = '<h4>WIRELESS PIN PURCHASE POLICY - Please Note</h4>';
            msg += '<p>';
            msg += '<ul>';
            msg += '    <li>All WIRELESS PIN sales are final.</li>';
            msg += '    <li>This product is NON-REFUNDABLE. Please be sure to print the customer receipt.</li>';
            msg += '    <li>Please re-confirm WIRELESS PIN Month prior to making purchase.</li>';
            msg += '</ul>';
            msg += '</p>';

            myApp.showConfirm(msg, function() {
                myApp.showLoading();
                $('#frm_rtr').submit();
            });
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
                        <h4 class="modal-title">REFILL - PIN Success</h4>
                    </div>
                    <div class="modal-body receipt">
                        <p class="text-center">
                            Your request has been processed successfully.
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
                            <div class="col-sm-4">Carrier</div>
                            <div class="col-sm-8">{{ session('carrier') }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Product</div>
                            <div class="col-sm-8">{{ session('product') }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Amount</div>
                            <div class="col-sm-8">${{ number_format(session('amount'), 2) }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">PIN.Qty</div>
                            <div class="col-sm-8">{{ session('qty') }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">PIN</div>
                            <div class="col-sm-8">{!! session('pin') !!}</div>
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
                        <h4 class="modal-title" style="color:red">REFILL - PIN Error</h4>
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
                        <h4>REFILL - PIN</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li class="active">REFILL - PIN</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End parallax -->

    <div class="contain-wrapp padding-bot70 no-print">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-sm-12">

                    <div class="form-group" style="margin-bottom:0px;">
                        {!! Helper::get_reminder_pin() !!}
                    </div>

                    <div style="clear:both"></div>

                    <div class="panel panel-default">
                        <div class="panel-body">
                            <form id="frm_rtr" method="post" class="form-horizontal" action="/sub-agent/pin/domestic/process" onsubmit="myApp.showLoading()">
                                {!! csrf_field() !!}

                                <div class="col-sm-12">
                                    <div class="col-sm-2">
                                    </div>

                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_6" src="/img/pin/AT&T.jpg" style="width: 100%; height: 100%; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_4" src="/img/pin/AirVoice.jpeg" style="width: 100%; height: 100%; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_1" src="/img/pin/FreeUP.jpg" style="width: 100%; height: 100%; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_5" src="/img/pin/H2O.jpg" style="width: 100%; height: 100%; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_8" src="/img/pin/liberty.jpg" style="width: 100%; height: 100%; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_12" src="/img/pin/Net 10.jpg" style="width: 100%; height: 100%; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_2" src="/img/pin/PagePlus.png" style="width: 100%; height: 100%; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_7" src="/img/pin/Red Pocket.jpg" style="width: 100%; height: 100%; margin-bottom: 16px;">
                                    </div>
                                </div>

                                <div class="col-sm-12">
                                    <div class="col-sm-2">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_9" src="/img/pin/SafeLink.jpg" style="width: 100%; height: 100%; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_13" src="/img/pin/Simple.png" style="width: 100%; height: 100%; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_10" src="/img/pin/T-Mobile.jpg" style="width: 100%; height: 100%; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_11" src="/img/pin/Tracfone.jpg" style="width: 100%; height: 100%; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_3" src="/img/pin/Verizon.jpg" style="width: 100%; height: 100%; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_14" src="/img/pin/rokit.jpg" style="width: 100%; height: 100%; margin-bottom: 16px;">
                                    </div>
                                </div>

                                <div class="col-sm-2">
                                    @if(!empty($carrier))
                                        @if($carrier == 'FreeUP')
                                            <img id="img_select" src="/img/pin/FreeUP.jpg" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'Page Plus')
                                            <img id="img_select" src="/img/pin/PagePlus.png" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'Verizon')
                                            <img id="img_select" src="/img/pin/Verizon.jpg" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'Air Voice')
                                            <img id="img_select" src="/img/pin/AirVoice.jpeg" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'H2O')
                                            <img id="img_select" src="/img/pin/H2O.jpg" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'AT&T')
                                            <img id="img_select" src="/img/pin/AT&T.jpg" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'Red Pocket')
                                            <img id="img_select" src="/img/pin/Red Pocket.jpg" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'Liberty Mobile')
                                            <img id="img_select" src="/img/pin/liberty.jpg" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'SafeLink')
                                            <img id="img_select" src="/img/pin/SafeLink.jpg" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'T-Mobile')
                                            <img id="img_select" src="/img/pin/T-Mobile.jpg" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'Tracfone')
                                            <img id="img_select" src="/img/pin/Tracfone.jpg" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'Net 10')
                                            <img id="img_select" src="/img/pin/Net 10.jpg" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'Simple')
                                            <img id="img_select" src="/img/pin/Simple.png" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'ROKiT')
                                            <img id="img_select" src="/img/pin/rokit.jpg" style="width: 150px; margin-bottom: 16px;">
                                        @endif
                                    @endif
                                </div>

                                <div class="col-sm-10">
                                    <div class="form-group{{ $errors->has('carrier') ? ' has-error' : '' }}">
                                        <input type="hidden" class="form-control" id="carrier" name="carrier" value="{{ isset($carrier) ? $carrier : old('carrier') }}"/>

                                        <label class="control-label col-sm-1">Carrier: </label>
                                        <div class="col-sm-8">
                                            <select class="form-control" name="carrier1" id="carrier1" onchange="refresh_page('s')">
                                                <option value="">Please Select</option>
                                                @if (count($carriers) > 0)
                                                    @foreach ($carriers as $o)
                                                        <option value="{{ $o->name }}" {{ old('carrier1', $carrier) == $o->name ? 'selected' : ''  }}>{{ $o->name }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            @if ($errors->has('carrier'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('carrier') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="form-group{{ $errors->has('product_id') ? ' has-error' : '' }}">
                                        <label class="control-label col-sm-1">Product: </label>
                                        <div class="col-sm-8">

                                            <select class="form-control" name="product_id" onchange="refresh_page()">
                                                <option value="">Please Select</option>
                                                @if (count($products) > 0)
                                                    @foreach ($products as $o)
                                                        <option value="{{ $o->product_id }}" {{ old('product_id', $product_id) == $o->product_id ? 'selected' : ''  }}>{{ $o->product_name }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            @if ($errors->has('product_id'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('product_id') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="form-group{{ $errors->has('denom_id') ? ' has-error' : '' }}">
                                        <label class="control-label col-sm-1">Amount($): </label>
                                        <div class="col-sm-8">
                                            @if ($open_denom == 'N')
                                                <select class="form-control" name="denom_id" onchange="refresh_page()">
                                                    <option value="">Please Select</option>
                                                    @if (count($denominations) > 0)
                                                        @foreach ($denominations as $o)
                                                            <option value="{{ $o->denom_id }}" {{ old('denom_id', $denom_id) == $o->denom_id ? 'selected' : '' }}>${{ number_format($o->denom, 2) }} ({{ $o->name }})</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            @else
                                                <input type="text" name="denom" class="form-control" value="{{ old('denom', $denom) }}" onchange="refresh_page()"/>
                                                @if ($errors->has('denom_id'))
                                                    <span class="help-block">
                                                        <strong>{{ $errors->first('denom_id') }}</strong>
                                                    </span>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                    <div class="form-group{{ $errors->has('qty') ? ' has-error' : '' }}">
                                        <label class="control-label col-sm-1">PIN.Qty: </label>
                                        <div class="col-sm-8">
                                            <select class="form-control" name="qty" onchange="refresh_page()">
                                                @for ($i = 1; $i<=1; $i++)
                                                    <option value="{{ $i }}" {{ old('qty', $qty) == $i ? 'selected' : '' }}>{{ $i }}</option>
                                                @endfor
                                            </select>
                                            @if ($errors->has('qty'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('qty') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="divider2"></div>
                                    <div class="form-group">
                                        <label class="control-label col-sm-1"></label>
                                        <div class="col-sm-4">
                                            Amount : ${{ number_format(old('amt', $amt), 2) }}<br/>
                                            PIN.Qty : {{ number_format(old('qty', $qty), 0) }}<br/>
                                            <hr style="margin:0px;"/>
                                            Sub Total : ${{ number_format(old('sub_total', $sub_total), 2) }}<br/>
                                            Vendor Fee : ${{ number_format(old('fee', $fee), 2) }}<br/>
                                            <hr style="margin:0px;"/>
                                            <b>Total : ${{ number_format(old('total', $total), 2) }}</b>
                                        </div>
                                        <div class="col-sm-4 text-right">
                                            <button type="button" onclick="confirm_order()" class="btn btn-primary">SUBMIT</button>
                                        </div>
                                    </div>

                                </div>

                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <table class="parameter-product table-bordered table-hover table-condensed filter">
                <thead>
                <tr class="active">
                    <td><strong>ID</strong></td>
                    <td><strong>Type</strong></td>
                    <td><strong>Status</strong></td>
                    <td><strong>Note</strong></td>
                    <td><strong>Product</strong></td>
                    <td><strong>Denom($)</strong></td>
                    <td><strong>RTR.M</strong></td>
                    <td><strong>Total($)</strong></td>
                    <td><strong>Vendor.Fee($)</strong></td>
                    <td><strong>Action</strong></td>
                    <td><strong>Phone/PIN</strong></td>
                    <td><strong>User.ID</strong></td>
                    <td><strong>Last.Updated</strong></td>
                </tr>
                </thead>
                <tbody>
                @if (isset($transactions) && count($transactions) > 0)
                    @foreach ($transactions as $o)
                        <tr>
                            <td>
                                @if ($o->status == 'C')
                                    <a target="_RECEIPT" href="/sub-agent/reports/receipt/{{ $o->id }}">{{ $o->id }}</a>
                                @else
                                    {{ $o->id }}
                                @endif
                            </td>
                            <td style="{{ $o->type_name == 'Void' ? 'color:red;' : '' }}">{{ $o->type_name }}</td>
                            @if ($o->status == 'R')
                                <td><a href="/sub-agent/reports/transaction/{{ $o->id }}">{!! $o->status_name() !!}</a></td>
                            @else
                                <td>{!! $o->status_name() !!}</td>
                            @endif
                            <td>
                                @if (!empty($o->note))
                                    {{ $o->note }}
                                @else
                                @endif
                            </td>
                            <td>{{ $o->product_name  }}</td>
                            <td>${{ $o->denom }}</td>
                            <td>{{ $o->rtr_month }}</td>
                            <td style="{{ $o->type_name == 'Void' ? 'color:red;' : '' }}">${{ $o->collection_amt }}</td>
                            <td style="{{ $o->type_name == 'Void' ? 'color:red;' : '' }}">${{ $o->fee + $o->pm_fee}}</td>
                            <td>{{ $o->action }}</td>
                            <td>{{ $o->action == 'PIN' ? Helper::mask_pin($o->pin) : $o->phone }}</td>
                            <td>{{ $o->created_by }}</td>
                            <td>{{ $o->last_updated }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="20" class="text-center">No Record Found</td>
                    </tr>
                @endif
                </tbody>
            </table>

            @if (old('carrier', $carrier) == 'Jingo TV')
            <embed src="/assets/jingotv/JingoTV_PresentationJuly2018.pdf" style="width: 100%;height: 500px;border: none;">
            @endif
        </div>
    </div>
@stop