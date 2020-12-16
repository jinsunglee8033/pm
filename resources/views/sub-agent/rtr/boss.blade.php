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

        window.onload = function() {
            @if (session()->has('success') && session('success') == 'Y')
                $('#success').modal();
            @endif

            @if ($errors->has('exception'))
                $('#error').modal();
            @endif

            $('#phone').keyup(function() {
                let length = $(this).val().length;
                $('#phone_count').text(length);
            });

        };

        function refresh_page() {
            $('#frm_rtr').attr('action', '/sub-agent/rtr/boss');
            $('#frm_rtr').submit();
        }

        function confirm_order() {

            var msg = '<h4>REFILL PURCHASE POLICY - Please Note</h4>';
            msg += '<p>';
            msg += '<ul>';
            msg += '    <li>All REFILL (Real Time Replenishments) sales are final.</li>';
            msg += '    <li>This product is NON-REFUNDABLE. Please be sure to print the customer receipt.</li>';
            msg += '    <li>Please re-confirm Consumer Phone Number prior to making purchase.</li>';
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
                        <h4 class="modal-title">Boss Revolution - RTR Success</h4>
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
{{--                        <div class="row">--}}
{{--                            <div class="col-sm-4">Carrier</div>--}}
{{--                            <div class="col-sm-8">{{ session('carrier') }}</div>--}}
{{--                        </div>--}}
                        <div class="row">
                            <div class="col-sm-4">Product</div>
                            <div class="col-sm-8">{{ session('product') }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Amount</div>
                            <div class="col-sm-8">${{ number_format(session('amount'), 2) }}</div>
                        </div>
{{--                        <div class="row">--}}
{{--                            <div class="col-sm-4">Refill Month</div>--}}
{{--                            <div class="col-sm-8">{{ session('rtr_month') }}</div>--}}
{{--                        </div>--}}
                        <div class="row">
                            <div class="col-sm-4">Phone</div>
                            <div class="col-sm-8">{{ session('phone') }}</div>
                        </div>
{{--                        <div class="row">--}}
{{--                            <div class="col-sm-4">Sub Total</div>--}}
{{--                            <div class="col-sm-8">${{ number_format(session('sub_total'), 2) }}</div>--}}
{{--                        </div>--}}
{{--                        <div class="row">--}}
{{--                            <div class="col-sm-4">Vendor Fee</div>--}}
{{--                            <div class="col-sm-8">${{ number_format(session('fee'), 2) }}</div>--}}
{{--                        </div>--}}
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
                        <h4 class="modal-title" style="color:red">REFILL - RTR Error</h4>
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
                        <h4>Boss Revolution</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li class="active">Boss Revolution</li>
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
                        {!! Helper::get_reminder_refill() !!}
                    </div>

                    <div style="clear:both"></div>

                    <div class="panel panel-default">
                        <div class="panel-body">
                            <form id="frm_rtr" method="post" class="form-horizontal" action="/sub-agent/rtr/boss/process" onsubmit="myApp.showLoading()">
                                {!! csrf_field() !!}

                                <div class="col-sm-2">
                                    <img src="/img/category-img-boss.jpg" style="width: 250px; margin-bottom: 16px;">
                                </div>

                                <input type="hidden" name="carrier" value="Boss Revolution">
                                <input type="hidden" name="rtr_month" value="1">

                                <div class="col-sm-8">
                                    <div class="col-sm-2">
                                        <div class="form-group">
                                            <label class="control-label">Product: </label>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
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

                                <div class="col-sm-8">
                                    <div class="col-sm-2">
                                        <div class="form-group">
                                            <label class="control-label">Amount($): </label>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
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

                                <div class="col-sm-8">
                                    <div class="col-sm-2">
                                        <div class="form-group">
                                            <label class="control-label">Phone: </label>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <input type="text" id="phone" name="phone" maxlength="10" class="form-control" value="{{ old('phone') }}"/>
                                        @if ($errors->has('phone'))
                                            <span class="help-block">
                                                <strong>{{ $errors->first('phone') }}</strong>
                                            </span>
                                        @endif
                                        <div id="count" align="left" style="color: red;
                                                        font-size: 12px;
                                                        margin-left: 10px;">
                                            You have entered in <span id="phone_count" style="font-weight: bold;">0</span> Digits
                                        </div>
                                    </div>
                                </div>

                                <div class="divider2"></div>
                                <div class="form-group">
                                    <label class="control-label col-sm-4"></label>
                                    <div class="col-sm-2">
                                        Amount : ${{ number_format($amt, 2) }}<br/>
{{--                                        Refill.Month : {{ number_format($rtr_month, 0) }}<br/>--}}
{{--                                        <hr style="margin:0px;"/>--}}
{{--                                        Sub Total : ${{ number_format($sub_total, 2) }}<br/>--}}
{{--                                        Vendor Fee : ${{ number_format($fee, 2) }}<br/>--}}
                                        <hr style="margin:0px;"/>
                                        <b>Total : ${{ number_format($total, 2) }}</b>
                                    </div>
                                    <div class="col-sm-2 text-right">
                                        <button type="button" onclick="confirm_order()" class="btn btn-primary">SUBMIT</button>
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
{{--                    <td><strong>RTR.M</strong></td>--}}
                    <td><strong>Total($)</strong></td>
{{--                    <td><strong>Vendor.Fee($)</strong></td>--}}
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
{{--                            <td>{{ $o->rtr_month }}</td>--}}
                            <td style="{{ $o->type_name == 'Void' ? 'color:red;' : '' }}">${{ $o->collection_amt }}</td>
{{--                            <td style="{{ $o->type_name == 'Void' ? 'color:red;' : '' }}">${{ $o->fee + $o->pm_fee}}</td>--}}
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
        </div>
    </div>
@stop