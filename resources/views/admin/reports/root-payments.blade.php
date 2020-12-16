@extends('admin.layout.default')

@section('content')

    <script type="text/javascript">

        var onload_func = window.onload;

        window.onload = function () {

            if (onload_func) {
                onload_func();
            }

            $("#sdate").datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $("#edate").datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $('.note-check-box').tooltip();

            // tooltip
            $('[data-toggle="tooltip"]').tooltip();

        };

        function refresh_all() {
            window.location.href = '/admin/reports/payments/root';
        }

        function search() {

            var acct_id = $('#acct_id').val();
            if(acct_id.length > 0) {
                var regex = /^[0-9]+$/;

                if (!acct_id.match(regex)) {
                    alert("Must input numbers");
                    return false;
                }
            }

            $('#excel').val('N');
            $('#frm_search').submit();
        }

        function excel_export() {
            $('#excel').val('Y');
            $('#frm_search').submit();
        }

        function set_date() {
            var quick = $('#quick').val();

            var today = moment().toDate();
            var yesterday = moment().subtract(1, 'days');
            var startOfWeek = moment().startOf('isoweek').toDate();
            var endOfWeek = moment().endOf('isoweek').toDate();
            var startOfMonth = moment().startOf('month').toDate();
            var endOfMonth = moment().endOf('month').toDate();
            var startOfYear = moment().startOf('year').toDate();
            var endOfYear= moment().endOf('year').toDate();
            var startOfLastWeek = moment().subtract(1, 'weeks').startOf('isoweek');
            var endOfLastWeek = moment().subtract(1, 'weeks').endOf('isoweek');
            var startOfLastMonth = moment().subtract(1, 'month').startOf('month');
            var endOfLastMonth = moment().subtract(1, 'month').endOf('month');
            var startOfLastYear = moment().subtract(1, 'year').startOf('year');
            var endOfLastYear = moment().subtract(1, 'year').endOf('year');
            var startOfLastWeekend = moment(endOfLastWeek).subtract(1, 'day').toDate();

            if(quick == 'Today'){
                $('#sdate').val(moment(today).format("YYYY-MM-DD"));
                $('#edate').val(moment(today).format("YYYY-MM-DD"));
            }else if(quick == 'This Week'){
                $('#sdate').val(moment(startOfWeek).format("YYYY-MM-DD"));
                $('#edate').val(moment(endOfWeek).format("YYYY-MM-DD"));
            }else if(quick == 'This Month'){
                $('#sdate').val(moment(startOfMonth).format("YYYY-MM-DD"));
                $('#edate').val(moment(endOfMonth).format("YYYY-MM-DD"));
            }else if(quick == 'This Year'){
                $('#sdate').val(moment(startOfYear).format("YYYY-MM-DD"));
                $('#edate').val(moment(endOfYear).format("YYYY-MM-DD"));
            }else if(quick == 'Yesterday'){
                $('#sdate').val(moment(yesterday).format("YYYY-MM-DD"));
                $('#edate').val(moment(yesterday).format("YYYY-MM-DD"));
            }else if(quick == 'Yesterday to Date'){
                $('#sdate').val(moment(yesterday).format("YYYY-MM-DD"));
                $('#edate').val(moment(today).format("YYYY-MM-DD"));
            }else if(quick == 'Last Week'){
                $('#sdate').val(moment(startOfLastWeek).format("YYYY-MM-DD"));
                $('#edate').val(moment(endOfLastWeek).format("YYYY-MM-DD"));
            }else if(quick == 'Last Week to Date'){
                $('#sdate').val(moment(startOfLastWeek).format("YYYY-MM-DD"));
                $('#edate').val(moment(today).format("YYYY-MM-DD"));
            }else if(quick == 'Last Month'){
                $('#sdate').val(moment(startOfLastMonth).format("YYYY-MM-DD"));
                $('#edate').val(moment(endOfLastMonth).format("YYYY-MM-DD"));
            }else if(quick == 'Last Month to Date'){
                $('#sdate').val(moment(startOfLastMonth).format("YYYY-MM-DD"));
                $('#edate').val(moment(today).format("YYYY-MM-DD"));
            }else if(quick == 'Last Year'){
                $('#sdate').val(moment(startOfLastYear).format("YYYY-MM-DD"));
                $('#edate').val(moment(endOfLastYear).format("YYYY-MM-DD"));
            }else if(quick == 'Last Year to Date'){
                $('#sdate').val(moment(startOfLastYear).format("YYYY-MM-DD"));
                $('#edate').val(moment(today).format("YYYY-MM-DD"));
            }else if(quick == 'Last WeekEnd'){
                $('#sdate').val(moment(startOfLastWeekend).format("YYYY-MM-DD"));
                $('#edate').val(moment(endOfLastWeek).format("YYYY-MM-DD"));
            }
        }

    </script>

    <div class="parallax" data-background="/img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
        <div class="overlay white"></div>
        <div class="container">
            <div class="inner-head">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h4>Payments</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li><a href="#">Reports</a></li>
                            <li class="active">Payments</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="contain-wrapp padding-bot70">
        <div class="well filter" style="padding-bottom:5px;">
            <form id="frm_search" class="form-horizontal" method="post" action="/admin/reports/payments/root">
                {{ csrf_field() }}
                <input type="hidden" name="excel" id="excel"/>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Date</label>
                            <div class="col-md-8">
                                <input type="text" style="width:100px; float:left;" class="form-control" id="sdate"
                                       name="sdate" value="{{ old('sdate', $sdate) }}"/>
                                <span class="control-label" style="float:left;">&nbsp;~&nbsp;</span>
                                <input type="text" style="width:100px; float:left;" class="form-control" id="edate"
                                       name="edate" value="{{ old('edate', $edate) }}"/>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Quick Selection</label>
                            <div class="col-md-8">
                                <select class="form-control" name="quick" id="quick" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}' onchange="set_date()">
                                    <option value="" {{ empty($quick) == '' ? 'selected' : '' }}>Date Range</option>
                                    <option value="Today" {{$quick == 'Today' ? 'selected' : '' }}>Today</option>
                                    <option value="This Week" {{$quick == 'This Week' ? 'selected' : '' }}>This Week</option>
                                    <option value="This Month" {{$quick == 'This Month' ? 'selected' : '' }}>This Month</option>
                                    <option value="This Year" {{$quick == 'This Year' ? 'selected' : '' }}>This Year</option>
                                    <option value="Yesterday" {{$quick == 'Yesterday' ? 'selected' : '' }}>Yesterday</option>
                                    <option value="Yesterday to Date" {{$quick == 'Yesterday to Date' ? 'selected' : '' }}>Yesterday to Date</option>
                                    <option value="Last Week" {{$quick == 'Last Week' ? 'selected' : '' }}>Last Week</option>
                                    <option value="Last Week to Date" {{$quick == 'Last Week to Date' ? 'selected' : '' }}>Last Week to Date</option>
                                    <option value="Last Month" {{$quick == 'Last Month' ? 'selected' : '' }}>Last Month</option>
                                    <option value="Last Month to Date" {{$quick == 'Last Month to Date' ? 'selected' : '' }}>Last Month to Date</option>
                                    <option value="Last Year" {{$quick == 'Last Year' ? 'selected' : '' }}>Last Year</option>
                                    <option value="Last Year to Date" {{$quick == 'Last Year to Date' ? 'selected' : '' }}>Last Year to Date</option>
                                    <option value="Last WeekEnd" {{$quick == 'Last WeekEnd' ? 'selected' : '' }}>Last WeekEnd</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Method</label>
                            <div class="col-md-8">
                                <select class="form-control" name="method">
                                    <option value="">All</option>
                                    <option value="P" {{ old('method', $method) == 'P' ? 'selected' : '' }}>
                                        PayPal
                                    </option>
                                    <option value="D" {{ old('method', $method) == 'D' ? 'selected' : '' }}>
                                        Direct Deposit
                                    </option>
                                    <option value="A" {{ old('method', $method) == 'A' ? 'selected' : '' }}>
                                        Weekday ACH
                                    </option>
                                    <option value="B" {{ old('method', $method) == 'B' ? 'selected' : '' }}>
                                        Weekly Bill
                                    </option>
                                    <option value="H" {{ old('method', $method) == 'H' ? 'selected' : '' }}>
                                        Cash Pickup
                                    </option>
                                    <option value="M" {{ old('method', $method) == 'M' ? 'selected' : '' }}>
                                        Manual Credit / Debit
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
{{--                    <div class="col-md-4">--}}
{{--                        <div class="form-group">--}}
{{--                            <label class="col-md-4 control-label">Category</label>--}}
{{--                            <div class="col-md-8">--}}
{{--                                <select class="form-control" name="category">--}}
{{--                                    <option value="">All</option>--}}
{{--                                    <option {{ old('category', $category) == 'Cash' ? 'selected' : '' }}>Cash</option>--}}
{{--                                    <option {{ old('category', $category) == 'Check' ? 'selected' : '' }}>Check</option>--}}
{{--                                    <option {{ old('category', $category) == 'Credit' ? 'selected' : '' }}>Credit</option>--}}
{{--                                    <option {{ old('category', $category) == 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>--}}
{{--                                    <option {{ old('category', $category) == 'Money Order' ? 'selected' : '' }}>Money Order</option>--}}
{{--                                </select>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Account #</label>
                            <div class="col-md-8">
                                <input class="form-control" name="acct_id" id="acct_id" value="{{ $acct_id }}">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Account IDs</label>
                            <div class="col-md-8">
                                <textarea class="form-control" name="acct_ids" rows="3">{{ $acct_ids }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Paypal ID</label>
                            <div class="col-md-8">
                                <input class="form-control" name="paypal_id" id="paypal_id" value="{{ $paypal_id }}">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Invoice ID</label>
                            <div class="col-md-8">
                                <input class="form-control" name="invoice_id" id="invoice_id" value="{{ $invoice_id }}">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8 col-md-offset-4 text-right">
                        <div class="form-group">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-info btn-sm" onclick="refresh_all()">Refresh All</button>
                                <button type="button" class="btn btn-primary btn-sm" id="btn_search" onclick="search()">Search</button>
                                <button type="button" class="btn btn-info btn-sm" onclick="excel_export()">Download</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>


        <table class="table table-bordered table-hover table-condensed filter">
            <thead>
            <tr>
                <th>Created.At</th>
                <th>Account</th>
                <th>Type</th>
                <th>Method</th>
                <th>Category</th>
                <th>Deposit.Amt</th>
                <th>Fee</th>
                <th>Applied.Amt</th>
                <th>PayPal ID</th>
                <th>Invoice ID</th>
                <th>Comments</th>
                <th>Created.By</th>
            </tr>
            </thead>
            <tbody>
            @if (isset($data) && count($data) > 0)
                @foreach ($data as $o)
                    @if($o->deposit_amt != '-')
                        <tr>
                            <td>{{ $o->cdate }}</td>
                            <td>
                                <span>{!! Helper::get_account_name_html_by_id($o->account_id) !!}</span>
                            </td>
                            @if($o->type == 'P')
                                <td>Prepay</td>
                            @elseif($o->type == 'B')
                                <td>Weekly Billing</td>
                            @elseif($o->type == 'A')
                                <td>Post Pay</td>
                            @elseif($o->type == 'W')
                                <td>Weekday ACH</td>
                            @endif

                            @if($o->method == 'P')
                                <td>PayPal</td>
                            @elseif($o->method == 'D')
                                <td>Direct Deposit</td>
                            @elseif($o->method == 'C')
                                <td>Credit Card</td>
                            @elseif($o->method == 'A')
                                <td>ACH</td>
                            @elseif($o->method == 'B')
                                <td>Weekly Bill</td>
                            @elseif($o->method == 'H')
                                <td>Cash Pickup</td>
                            @endif
                            <td>{{ $o->category }}</td>
                            <td>${{ number_format($o->deposit_amt, 2) }}</td>
                            <td>${{ number_format($o->fee, 2) }}</td>
                            <td>${{ number_format($o->amt, 2) }}</td>
                            <td>{{ is_null($o->paypal_txn_id) ? '-' : $o->paypal_txn_id }}</td>
                            <td>{{ $o->invoice_number }}</td>
                            <td>{{ $o->comments }}</td>
                            <td>{{ $o->created_by }}</td>
                        </tr>
                    @else
                        <tr>
                            <td>{{ $o->cdate }}</td>
                            <td>
                                <span>{!! Helper::get_account_name_html_by_id($o->account_id) !!}</span>
                            </td>
                            <td>{{ $o->type }}</td>
                            <td>{{ $o->method }}</td>
                            <td>{{ $o->category }}</td>
                            <td>-</td>
                            <td>-</td>
                            <td>${{ number_format($o->amt, 2) }}</td>
                            <td>{{ is_null($o->paypal_txn_id) ? '-' : $o->paypal_txn_id }}</td>
                            <td>{{ $o->invoice_number }}</td>
                            <td>{{ $o->comments }}</td>
                            <td>{{ $o->created_by }}</td>
                        </tr>
                    @endif
                @endforeach
            @else
                <tr>
                    <td colspan="30" class="text-center">No Record Found</td>
                </tr>
            @endif
            </tbody>
            <tfoot>
            <tr>
                <th colspan="5" class="text-right">Total {{ $data->total() }} record(s).</th>
                <th>
                    ${{ number_format($deposit_amt, 2) }}
                </th>
                <th>
                    ${{ number_format($fee, 2) }}
                </th>
                <th>
                    ${{ number_format($amt, 2) }}
                </th>
                <th colspan="4"></th>
            </tr>
            </tfoot>
        </table>

        <div class="text-right">
            {{ $data->appends(Request::except('page'))->links() }}
        </div>
    </div>
@stop
