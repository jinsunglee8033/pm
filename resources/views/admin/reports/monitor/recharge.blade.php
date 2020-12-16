@extends('admin.layout.default')

@section('content')

    <script type="text/javascript">

        var onload_func = window.onload;

        window.onload = function() {

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

        function search() {
            $('#excel').val('N');
            $('#frm_search').submit();
        }

        function download() {
            $('#excel').val('Y');
            $('#frm_search').submit();
        }

        function show_batch_lookup() {
            $('#n_batch_accounts').val('');
            $('#div_batch_lookup').modal();
        }

        // function count_accounts() {
        //     var accounts = $.trim($('#n_accounts').val()).split("\n");
        //     $('#n_accounts_qty').text(accounts.length);
        // }

        function count_batch_accounts() {
            var accounts = $.trim($('#n_batch_accounts').val()).split("\n");
            $('#n_batch_accounts_qty').text(accounts.length);
        }

        function batch_lookup() {
            var batch_accounts = $('#n_batch_accounts').val();
            batch_accounts = $.trim(batch_accounts);

            if (batch_accounts === '') {
                myApp.showError('Please enter ACCOUNTs to lookup');
                return;
            }

            //myApp.showLoading();

            $('#div_batch_lookup').modal('hide');
            $('#frm_batch_lookup').submit();
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

    <h4>Activation Recharge Report</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post" action="/admin/reports/monitor/recharge">
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
                        <label class="col-md-4 control-label">Account</label>
                        <div class="col-md-8">
                            <input class="form-control" name="account" value="{{ $account }}" placeholder="Account ID or Name">
                        </div>
                    </div>
                </div>


                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Product</label>
                        <div class="col-md-8">
                            <select class="form-control" name="product"
                                    data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                <option value="" {{ old('product', $product) == '' ? 'selected' : '' }}>All
                                </option>
                                @foreach ($products as $p)
                                    <option value="{{ $p->id }}" {{ old('product', $product) == $p->id ? 'selected' : '' }}>{{ $p->carrier . ', ' . $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Vendor</label>
                        <div class="col-md-8">
                            <select class="form-control" name="vendor"
                                    data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                <option value="" {{ old('vendor', $vendor) == '' ? 'selected' : '' }}>All
                                </option>
                                @foreach ($vendors as $v)
                                    <option value="{{ $v->code }}" {{ old('vendor', $vendor) == $v->code ? 'selected' : '' }}>{{ $v->code . ', ' . $v->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Carrier</label>
                        <div class="col-md-8">
                            <select class="form-control" name="carrier"
                                    data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                <option value="" {{ old('carrier', $carrier) == '' ? 'selected' : '' }}>All
                                </option>
                                @foreach ($carriers as $c)
                                    <option value="{{ $c->name }}" {{ old('carrier', $carrier) == $c->name ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

            </div>
            <div class="row">
                <div class="col-md-4">
                </div>
                <div class="col-md-4">
                </div>
                <div class="col-md-4 text-right">
                    <div class="form-group">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary btn-sm" id="btn_search" onclick="search()">
                                Search
                            </button>
                            <button type="button" class="btn btn-blue btn-sm" onclick="show_batch_lookup()">Batch Lookup</button>
                            <button type="button" class="btn btn-primary btn-sm" id="btn_search" onclick="download()">
                                Download
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="modal" id="div_batch_lookup" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Please enter account to lookup</h4>
                </div>
                <div class="modal-body">
                    <form id="frm_batch_lookup" action="/admin/reports/monitor/batch-lookup" class="form-horizontal filter"
                          method="post" style="padding:15px;">
                        <input type="hidden" style="width:100px; float:left;" class="form-control" id="sdate"
                               name="sdate" value="{{ old('sdate', $sdate) }}"/>
                        <input type="hidden" style="width:100px; float:left;" class="form-control" id="edate"
                               name="edate" value="{{ old('edate', $edate) }}"/>
                        {{ csrf_field() }}
                        <div class="form-group">
                            <div class="col-sm-12">
                                <label>
                                    Excel file will be downloaded after submit.
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-12">
                                <textarea id="n_batch_accounts" name="batch_accounts" rows="10" style="width:100%; line-height: 150%;"
                                          onchange="count_batch_accounts()"></textarea><br/>
                                Total <span id="n_batch_accounts_qty">0</span> Account(s).
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="margin-right:15px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button class="btn btn-primary" onclick="batch_lookup()">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <table class="table table-bordered table-hover table-condensed filter">
        <thead>
        <tr>
            <th rowspan="2">Account</th>
            <th rowspan="2" style="text-align: center;">Qty.Total</th>
            <th rowspan="2" style="text-align: center;">Qty.1st</th>
            <th colspan="2" style="text-align: center;">2nd</th>
            <th colspan="2" style="text-align: center;">3rd</th>
            <th rowspan="2" style="text-align: center;">RTR (SPP)</th>
        </tr>
        <tr>
            <th style="text-align: center;">Qty</th>
            <th style="text-align: center;">Rates</th>
            <th style="text-align: center;">Qty</th>
            <th style="text-align: center;">Rates</th>
        </tr>
        </thead>
        <tbody>
        @if (isset($refills) && count($refills) > 0)
            @foreach ($refills as $r)
                <tr>
                    <td>{!! Helper::get_parent_name_html($r->account_id) !!} <span>{!! Helper::get_hierarchy_img('S')
                     !!}</span>{{ $r->account_name . ' ( ' . $r->account_id . ' )' }}</td>
                    <td style="text-align: right;">{{ $r->qty_total }}</td>
                    <td style="text-align: right;">{{ $r->qty_1st }}</td>
                    <td style="text-align: right;">{{ $r->qty_2nd }}</td>
                    <td style="text-align: right;">{{ $r->refill_rates_2nd }} %</td>
                    <td style="text-align: right;">{{ $r->qty_3rd }}</td>
                    <td style="text-align: right;">{{ $r->refill_rates_3rd }} %</td>
                    <td style="text-align: right;">{{ $r->rtr_month }}</td>
                </tr>
            @endforeach
        @else
        @endif
        </tbody>
        <tfoot>
        @foreach($summary_yms as $s)
        <tr>
            <th style="text-align: right;">{{ $s->dy . '-' . $s->dm }}:</th>
            <th style="text-align: right;">{{ $s->qty_total }}</th>
            <th style="text-align: right;">{{ $s->qty_1st }}</th>
            <th style="text-align: right;">{{ $s->qty_2nd }}</th>
            <th style="text-align: right;">{{ $s->refill_rates_2nd }} %</th>
            <th style="text-align: right;">{{ $s->qty_3rd }}</th>
            <th style="text-align: right;">{{ $s->refill_rates_3rd }} %</th>
            <th style="text-align: right;">{{ $s->rtr_month }}</th>
        </tr>
        @endforeach
        <tr>
            <th style="text-align: right;">Total:</th>
            <th style="text-align: right;">{{ $summary->qty_total }}</th>
            <th style="text-align: right;">{{ $summary->qty_1st }}</th>
            <th style="text-align: right;">{{ $summary->qty_2nd }}</th>
            <th style="text-align: right;">{{ $summary->refill_rates_2nd }} %</th>
            <th style="text-align: right;">{{ $summary->qty_3rd }}</th>
            <th style="text-align: right;">{{ $summary->refill_rates_3rd }} %</th>
            <th style="text-align: right;">{{ $summary->rtr_month }}</th>
        </tr>
        </tfoot>
    </table>
@stop
