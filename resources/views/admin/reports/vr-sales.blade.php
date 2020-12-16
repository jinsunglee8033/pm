@extends('admin.layout.default')

@section('content')

    <script type="text/javascript">
        window.onload = function() {
            $( "#sdate" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });
            $( "#edate" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $('.note-check-box').tooltip();

            // tooltip
            $('[data-toggle="tooltip"]').tooltip();

        };

        function excel_download() {
            $('#excel').val('Y');
            $('#frm_search').submit();
        }

        function search() {
            $('#excel').val('');
            $('#frm_search').submit();
        }

        function refresh_all() {
            window.location.href = '/admin/reports/vr-sales';
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

    <h4>Virtual Rep. Sales</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post">
            {{ csrf_field() }}
            <input type="hidden" name="excel" id="excel"/>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Date</label>
                        <div class="col-md-8">
                            <input type="text" style="width:100px; float:left;" class="form-control" id="sdate" name="sdate" value="{{ old('sdate', $sdate) }}"/>
                            <span class="control-label" style="margin-left:5px; float:left;"> ~ </span>
                            <input type="text" style="width:100px; margin-left: 5px; float:left;" class="form-control" id="edate" name="edate" value="{{ old('edate', $edate) }}"/>
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
                        <label class="col-md-4 control-label">Status</label>
                        <div class="col-md-8">
                            <select class="form-control" name="status" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                <option value="" {{ old('status', $status) == '' ? 'selected' : '' }}>All</option>
                                <option value="RQ" {{ old('status', $status) == 'RQ' ? 'selected' : '' }}>Requested</option>
                                <option value="CP" {{ old('status', $status) == 'CP' ? 'selected' : '' }}>Confirmed Price</option>
                                <option value="PC" {{ old('status', $status) == 'PC' ? 'selected' : '' }}>Paid</option>
                                <option value="SH" {{ old('status', $status) == 'SH' ? 'selected' : '' }}>Shipped</option>
                                <option value="C" {{ old('status', $status) == 'C' ? 'selected' : '' }}>Completed</option>
                                <option value="R" {{ old('status', $status) == 'R' ? 'selected' : '' }}>Rejected</option>
                                <option value="CC" {{ old('status', $status) == 'CC' ? 'selected' : '' }}>Canceled</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Is Consignment</label>
                        <div class="col-md-8">
                            <input type="checkbox" name="is_consignment" value="Y" {{ $is_consignment == 'Y' ? 'checked' : '' }}/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Product</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="product" value="{{ old('product', $product) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Marketing</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="marketing" value="{{ old('marketing', $marketing) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Account</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="account" value="{{ old('account', $account)
                            }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Account IDs</label>
                        <div class="col-md-8">
                            <textarea class="form-control" name="acct_ids" rows="3">{{ old('acct_ids', $acct_ids) }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Supplier</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="supplier" value="{{ old('supplier', $supplier) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Is Dropship</label>
                        <div class="col-md-4">
                            <input type="checkbox" name="is_dropship" value="Y" {{ $is_dropship == 'Y' ? 'checked' : '' }}/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Quick.Notes</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="quick_note" value="{{ old('quick_note', $quick_note) }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="form-group">
                        <div class="col-md-8 text-right">
                            <button type="button" class="btn btn-info btn-sm" onclick="refresh_all()">Refresh All</button>
                            <button type="button" class="btn btn-primary btn-sm" id="btn_search" onclick="search()">Search</button>
                            <button type="button" class="btn btn-primary btn-sm" onclick="excel_download()">DOWNLOAD</button>
                        </div>
                    </div>
                </div>
                {{--<div class="col-md-4 text-right">--}}
                    {{--<div class="form-group">--}}
                        {{--<div class="col-md-12">--}}
                            {{--<button type="button" class="btn btn-primary btn-sm" id="btn_search" onclick="search()--}}
                            {{--">Search</button>--}}
                            {{--<button type="button" class="btn btn-primary btn-sm"--}}
                                    {{--onclick="excel_download()">DOWNLOAD</button>--}}
                        {{--</div>--}}
                    {{--</div>--}}
                {{--</div>--}}
            </div>
        </form>
    </div>

    <div class="text-left">
        Total {{ $records->total() }} record(s).
    </div>
    <table class="table table-bordered table-hover table-condensed filter">
        <thead>
            <tr>
                <th style="text-align: center;">ID</th>
                <th>Q.ID</th>
                <th>Account</th>
                <th style="text-align: center;">State</th>
                <th>Product</th>
                <th>Quick.Notes</th>
                <th>Category</th>
                <th>Supplier</th>
                <th>Marketing</th>
                <th style="text-align: center;">Qty</th>
                <th style="text-align: center;">Default.Price</th>
                <th style="text-align: center;">Paid.Price</th>
                <th style="text-align: center;">Status</th>
                <th style="text-align: center;">Payment</th>
                <th style="text-align: center;">Tracking #</th>
                <th style="text-align: center;">Date & Time</th>
            </tr>
        </thead>
        <tbody>
        @if (isset($records) && count($records) > 0)
            @foreach ($records as $o)
                <tr>
                    <td style="text-align: center;">{{ $o->prod_id }}</td>
                    <td>{{ $o->vr_id }}</td>
                    @if (in_array(Auth::user()->account_type, ['L']))
                        <td>{!! Helper::get_parent_name_html($o->account_id) !!} <span>{!! Helper::get_hierarchy_img($o->acct_type) !!}</span>{{ $o->acct_name . ' ( ' . $o->account_id . ' )' }}</td>
                    @else
                        <td><span>{!! Helper::get_hierarchy_img($o->acct_type) !!}</span>{{ $o->acct_name . ' ( ' . $o->account_id . ' )' }}</td>
                    @endif
                    <td style="text-align: center;">{{ $o->state }}</td>
                    <td>{{ $o->model }}</td>
                    <td>{{ $o->quick_note }}</td>
                    <td>{{ $o->category }}</td>
                    <td>{{ $o->supplier }}</td>
                    <td>{{ $o->marketing }}</td>
                    <td style="text-align: right;">{{ $o->qty }}</td>
                    <td style="text-align: right;">{{ number_format($o->subagent_price, 2) }}</td>
                    <td style="text-align: right;">{{ number_format($o->order_price, 2) }}</td>
                    <td style="text-align: center;">{!! $o->status_name() !!}</td>
                    <td style="text-align: center;">{{ $o->pay_method }}</td>
                    <td style="text-align: center;">{{ $o->tracking_no }}</td>
                    <td style="text-align: center;">{{ $o->cdate }}</td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="19" class="text-center">No Record Found</td>
            </tr>
        @endif
        </tbody>
        <tfoot>
            <tr>
                <th colspan="9" style="text-align: right;">Total:</th>
                <th style="text-align: right;">{{ $summary->qty }}</th>
                <th style="text-align: right;"></th>
                <th style="text-align: right;">{{ number_format($summary->total, 2) }}</th>
                <th colspan="4"></th>
            </tr>
        </tfoot>
    </table>

    <div class="text-right">
        {{ $records->appends(Request::except('page'))->links() }}
    </div>

@stop
