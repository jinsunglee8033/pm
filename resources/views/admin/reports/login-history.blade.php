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

        function excel_export() {
            $('#excel').val('Y');
            $('#frm_search').submit();
        }

        function refresh_all() {
            $('#excel').val('N');
            $("form#frm_search input[type=text]").val('');
            $("form#frm_search select").val('');
            $("form#frm_search input[type=checkbox]").val('');
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

    <h4>Login History Report</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post" action="/admin/reports/login-history">
            {{ csrf_field() }}
            <input type="hidden" name="excel" id="excel"/>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Login Date</label>
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
                        <label class="col-md-4 control-label">IP.Address</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" id="ip" name="ip" value="{{ old('ip', $ip) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">User.ID</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" id="user_id" name="user_id" value="{{ old('user_id', $user_id) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Account.ID</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" id="account_id" name="account_id" value="{{ old('account_id', $account_id) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Result</label>
                        <div class="col-md-8">
                            <select class="form-control" name="result" id="result">
                                <option value="" {{ empty($result) == '' ? 'selected' : '' }}>All</option>
                                <option value="S" {{$result == 'S' ? 'selected' : '' }}>Success</option>
                                <option value="F" {{$result == 'F' ? 'selected' : '' }}>Fail</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">No.Login</label>
                        <div class="col-md-8">
                            <input type="checkbox" name="no_login" value="Y" {{ $no_login == 'Y' ? 'checked' : '' }}/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">

                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <div class="form-group">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-info btn-sm" onclick="refresh_all()">Refresh All
                            </button>
                            <button type="button" class="btn btn-primary btn-sm" id="btn_search" onclick="search()">
                                Search
                            </button>
                            <button type="button" class="btn btn-info btn-sm" onclick="excel_export()">Export</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>


    <table class="table table-bordered table-hover table-condensed filter">
        <thead>
            <tr>
                <th>ID</th>
                <th>Account</th>
                <th>User.ID</th>
                <th>Result</th>
                <th>Error.MSG</th>
                <th>IP</th>
                <th>Login.Date</th>
            </tr>
        </thead>
        <tbody>
            @if (isset($data) && count($data) > 0)
                @foreach ($data as $o)
                    <tr>
                        <td>{{ $o->id }}</td>
                        <td>
                            {!! Helper::get_parent_name_html($o->account_id) !!}
                            {!! Helper::get_hierarchy_img($o->account_type) !!}
                            {{ $o->account_name . ' ( ' . $o->account_id . ' )' }}
                        </td>
                        <td>{{ $o->user_id }}</td>
                        <td>{{ $o->result }}</td>
                        <td>{{ $o->result_msg }}</td>
                        <td>{{ $o->ip }}</td>
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

        </tfoot>
    </table>

    <div class="text-right">
        {{ $data->appends(Request::except('page'))->links() }}
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
