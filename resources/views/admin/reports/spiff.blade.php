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

        function excel_export() {
            $('#excel').val('Y');
            $('#frm_search').submit();
            myApp.hideLoading();
            $('#excel').val('N');
        }

        function search() {
            $('#excel').val('N');
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

    <h4>Paid Spiff Report</h4>

    <div class="contain-wrapp padding-bot70">
        <div class="well filter" style="padding-bottom:5px;">
            <form id="frm_search" class="form-horizontal" method="post" action="/admin/reports/spiff" onsubmit="myApp.showLoading()">
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
                            <label class="col-md-4 control-label">Phone</label>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="phone" value="{{ old('phone', $phone) }}"/>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Tx.ID</label>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="trans_id" value="{{ old('trans_id', $trans_id) }}"/>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Account.ID</label>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="account_id" value="{{ old('account_id', $account_id) }}"/>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Spiff.Account.Type</label>
                            <div class="col-md-8">
                                <select class="form-control" name="spiff_account_type">
                                    <option value="" {{ old('spiff_account_type', $spiff_account_type) == '' ? 'selected' : '' }}>Select</option>
                                    <option value="M" {{ old('spiff_account_type', $spiff_account_type) == 'M' ? 'selected' : '' }}>Master</option>
                                    <option value="D" {{ old('spiff_account_type', $spiff_account_type) == 'D' ? 'selected' : '' }}>Distributor</option>
                                    <option value="S" {{ old('spiff_account_type', $spiff_account_type) == 'S' ? 'selected' : '' }}>Sub Agent</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Carrier</label>
                            <div class="col-md-8">
                                <select class="form-control" name="carrier" id="carrier" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                    <option value="" {{ old('carrier', $carrier) == '' ? 'selected' : '' }}>All</option>
                                    @if (count($carriers) > 0)
                                        @foreach ($carriers as $o)
                                            <option value="{{ $o->name }}" {{ old('carrier', $carrier) == $o->name ? 'selected' : '' }}>{{ $o->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Product</label>
                            <div class="col-md-8">
                                <select class="form-control" name="product" id="product" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                    <option value="" {{ old('product', $product) == '' ? 'selected' : '' }}>All</option>
                                    @if (count($products) > 0)
                                        @foreach ($products as $o)
                                            <option value="{{ $o->id }}" {{ old('product', $product) == $o->id ? 'selected' : '' }}>{{ $o->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 text-right">
                        <div class="form-group">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-primary btn-sm" id="btn_search" onclick="search()">
                                    Search
                                </button>
                                @if(Auth::check() && in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']))
                                    <button type="button" class="btn btn-info btn-sm" onclick="excel_export()">Download</button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="text-left">
            <label>Total {{ $count }} record(s)</label>
        </div>

        <table class="table table-bordered table-hover table-condensed filter">
            <thead>
            <tr>
                <th>Spiff.ID</th>
                <th>Parent</th>
                <th>Account</th>
                <th>Type</th>
                <th>Tx.ID</th>
                <th>Phone</th>
                <th>Product</th>
                <th>Denom($)</th>
                <th>Spiff.Account.Type</th>
                <th>Spiff.Month</th>
                <th>Spiff.Amt($)</th>
                <th>Is.BYOS</th>
                <th>Note</th>
                <th>Date</th>
            </tr>
            </thead>
            <tbody>
            @if (isset($data) && count($data) > 0)
                @foreach ($data as $o)
                    <tr>
                        <td>{{ $o->id }}</td>
                        <td>{!! Helper::get_parent_name_html($o->account_id) !!} </td>
                        <td>
                            <span>{!! Helper::get_hierarchy_img($o->account_type) !!}</span>
                            {{ $o->account_name . ' ( ' . $o->account_id . ' )' }}
                        </td>
                        <td style="{{ $o->type_name == 'Void' ? 'color:red;' : '' }}">{{ $o->type_name }}</td>
                        <td>{{ $o->trans_id }}</td>
                        <td>{{ $o->phone }}</td>
                        <td>{{ $o->product }}</td>
                        <td>${{ number_format($o->denom, 2) }}</td>
                        <td>{!! Helper::get_hierarchy_img($o->spiff_account_type) !!}</td>
                        <td>{{ $o->spiff_month }}</td>
                        <td>${{ $o->type_name == 'Void' ? '-'.number_format($o->spiff_amt, 2) : number_format($o->spiff_amt, 2) }}</td>
                        <td>{{ $o->is_byos == 'Y' ? 'Yes' : 'No' }}</td>
                        <td>{{ $o->note }}</td>
                        <td>{{ $o->cdate }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="30" class="text-center">No Record Found</td>
                </tr>
            @endif
            </tbody>
            <tfoot>
            <tr>
                <th colspan="6" class="text-left">Total {{ $count }} record(s)</th>
                <th class="text-right">Total :</th>
                <th>${{ number_format($denom_total,2) }}</th>
                <th></th>
                <th class="text-right">Total :</th>
                <th>${{ number_format($amt_total,2) }}</th>
                <th colspan="3"></th>
            </tr>
            </tfoot>
        </table>

        <div class="text-right">
{{--            {{ $data->appends(Request::except('page'))->links() }}--}}
        </div>
    </div>

    <div class="row">
        @if ($errors->has('exception'))
            <div class="alert alert-danger alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                <strong>Error!</strong> {{ $errors->first('exception') }}
            </div>
        @endif
    </div>
@stop
