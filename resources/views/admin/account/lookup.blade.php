@extends('admin.layout.default')

@section('content')
    <style type="text/css">

        input[type=text]:disabled {
            background-color: #efefef;
        }
    </style>

    <script type="text/javascript">
        window.onload = function() {
            $( "#sdate" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });
            $( "#edate" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });
        };

        function excel_download() {
            $('#excel').val('Y');
            $('#frm_search').submit();
        }

        function search() {
            $('#excel').val('');
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

    <h4>Account List</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form class="form-horizontal" id="frm_search" name="frm_search" method="post" action="/admin/account/lookup">
            {{ csrf_field() }}

            <input type="hidden" name="excel" id="excel">

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-3 control-label">Account IDs</label>
                        <div class="col-md-9">
                            <textarea class="form-control" name="acct_ids" rows="10">{{ $acct_ids }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-3 control-label">Emails</label>
                        <div class="col-md-9">
                            <textarea class="form-control" name="emails" rows="10">{{ $emails }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Matched</label>
                        <div class="col-md-8">
                            <select name="matched" class="form-control">
                                <option value="" {{ empty($matched) ? 'selected' : '' }}>All</option>
                                <option value="Y" {{ $matched == 'Y' ? 'selected' : '' }}>Matched</option>
                                <option value="N" {{ $matched == 'N' ? 'selected' : '' }}>Not Matched</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-4 control-label">Transaction Date</label>
                        <div class="col-md-8">
                            <input type="text" style="width:100px; float:left;" class="form-control" id="sdate" name="sdate" value="{{ old('sdate', $sdate) }}"/>
                            <span class="control-label" style="margin-left:5px; float:left;"> ~ </span>
                            <input type="text" style="width:100px; margin-left: 5px; float:left;" class="form-control" id="edate" name="edate" value="{{ old('edate', $edate) }}"/>
                        </div>
                    </div>
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
                    <div class="form-group">
                        <label class="col-md-4 control-label">Have Transaction</label>
                        <div class="col-md-8">
                            <select name="has_transaction" class="form-control">
                                <option value="" {{ empty($has_transaction) ? 'selected' : '' }}>All</option>
                                <option value="Y" {{ $has_transaction == 'Y' ? 'selected' : '' }}>Yes</option>
                                <option value="N" {{ $has_transaction == 'N' ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-4 control-label">Product</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" id="product"
                                   name="product" value="{{ old('product', $product) }}"/>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                </div>
                <div class="col-md-4">
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <div class="col-md-8 col-md-offset-4 text-right">
                            <button class="btn btn-primary btn-sm" onclick="search()">Search</button>
                            <button class="btn btn-primary btn-sm" onclick="excel_download()">Download</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="row">
        <div class="col-md-12">
            @if ($errors->has('exception'))
                <div class="alert alert-danger alert-dismissible" role="alert">
                    <strong>Error!</strong> {{ $errors->first('exception') }}
                </div>
            @endif
        </div>
    </div>

    <table class="tree table table-bordered table-hover table-condensed filter">
        <thead>
        <tr>
            <th colspan="1" style="text-align: left;"></th>
            <th colspan="1" style="text-align: left;">{{ $count }} Records Found</th>
            <th colspan="7" style="text-align: right;">Not Matched: {{ $notmatched_count }}</th>
            <th colspan="2" style="text-align: right;">Matched: {{ $matched_count }}</th>
            <th colspan="2" style="text-align: right;">Total Matched: {{ $dupmatched_count }}</th>
        </tr>
        <tr>
            <th>Key</th>
            <th>Account</th>
            <th>Type</th>
            <th>Address</th>
            <th>Contact Name</th>
            <th>Office Number</th>
            <th>Phone2</th>
            <th>Email</th>
            <th>Email2</th>
            <th>Tax ID</th>
            <th>Tran Qty</th>
            <th>Status</th>
            <th>Created.At</th>
        </tr>
        </thead>
        <tbody>
        @if (isset($data) && count($data) > 0)
            @foreach ($data as $o)
                <tr>
                    <td>{{ $o['key'] }}</td>
                    @if (empty($o['value']))
                        <td colspan="12">Not Found</td>
                    @else
                    @php
                        $account = $o['value'];
                    @endphp
                        <td>
                            @if (!empty($account->parent))
                            <span>{!! Helper::get_hierarchy_img($account->parent->type) !!}</span> {{ $account->parent->name . ' (
                            ' . $account->parent->id . ' )' }}
                            @endif
                            <span>{!! Helper::get_hierarchy_img
                            ($account->type) !!}</span> {{ $account->name . ' ( ' . $account->id . ' )' }}
                        </td>
                        <td>{{ $account->type_name() }}</td>
                        <td>{{ $account->address1 . ' ' . $account->address2 . ', ' . $account->city . ', ' .
                        $account->state . ' ' . $account->zip }}</td>
                        <td>{{ $account->contact }}</td>
                        <td>{{ $account->office_number }}</td>
                        <td>{{ $account->phone2 }}</td>
                        <td>{{ $account->email }}</td>
                        <td>{{ $account->email2 }}</td>
                        <td>{{ $account->tax_id }}</td>
                        <td>{{ $account->trx_count }}</td>
                        <td>{{ $account->status_name() }}</td>
                        <td>{{ $account->cdate }}</td>
                    @endif
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="13" class="text-center">No Record Found</td>
            </tr>
        @endif
        </tbody>
    </table>

@stop
