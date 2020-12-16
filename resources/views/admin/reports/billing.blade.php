@extends('admin.layout.default')

@section('content')

    <style type="text/css">
        .blue {
            /*color:blue;*/
        }
    </style>

    <script>


    </script>

    <script type="text/javascript">
        window.onload = function () {
            $("#sdate").datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $("#edate").datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $('.tree').treegrid({
                treeColumn: 1
            });

            $('.note-check-box').tooltip();

            // tooltip
            $('[data-toggle="tooltip"]').tooltip();



        };

        function excel_export() {
            $('#excel').val('Y');
            $('#frm_search').submit();
        }

        function search() {
            $('#excel').val('N');
            $('#frm_search').submit();
        }

        function refresh_all() {
            $('#excel').val('N');
            $("form#frm_search input[type=text]").val('');
            $("form#frm_search select").val('');
            $('#frm_search').submit();
        }

        function show_bill_detail(bill) {
            //console.log(bill);

            $('#n_bill_id').text(bill.id);
            $("#n_bill_date").text(bill.bill_date);
            $('#n_period_from').text(bill.period_from);
            $('#n_period_to').text(bill.period_to);
            $('#n_starting_balance').text('$' + parseFloat(bill.starting_balance).toFixed(2));
            $('#n_starting_deposit').text('$' + parseFloat(bill.starting_deposit).toFixed(2));
            $('#n_new_deposit').text('$' + parseFloat(bill.new_deposit).toFixed(2));
            $('#n_deposit_total').text('$' + parseFloat(bill.deposit_total).toFixed(2));
            $('#n_sales').text('$' + parseFloat(bill.sales).toFixed(2));
            $('#n_sales_margin').text('$' + parseFloat(bill.sales_margin).toFixed(2));
            $('#n_void').text('$' + parseFloat(bill.void).toFixed(2));
            $('#n_void_margin').text('$' + parseFloat(bill.void_margin).toFixed(2));
            $('#n_gross').text('$' + parseFloat(bill.gross).toFixed(2));
            $('#n_net_margin').text('$' + parseFloat(bill.net_margin).toFixed(2));
            $('#n_net_revenue').text('$' + parseFloat(bill.net_revenue).toFixed(2));
            $('#n_children_paid_amt').text('$' + parseFloat(bill.children_paid_amt).toFixed(2));
            $('#n_spiff_credit').text('$' + parseFloat(bill.spiff_credit).toFixed(2));
            $('#n_spiff_debit').text('$' + parseFloat(bill.spiff_debit).toFixed(2));
            $('#n_residual').text('$' + parseFloat(bill.residual).toFixed(2));
            $('#n_adjustment').text('$' + parseFloat(bill.adjustment).toFixed(2));
            $('#n_promotion').text('$' + parseFloat(bill.promotion).toFixed(2));
            $('#n_bill_amt').text('$' + parseFloat(bill.bill_amt).toFixed(2));
            $('#n_dist_paid_amt').text('$' + parseFloat(bill.dist_paid_amt).toFixed(2));
            $('#n_ach_paid_amt').text('$' + parseFloat(bill.ach_paid_amt).toFixed(2));
            $('#n_deposit_paid_amt').text('$' + parseFloat(bill.deposit_paid_amt).toFixed(2));
            $('#n_ending_balance').text('$' + parseFloat(bill.ending_balance).toFixed(2));
            $('#n_ending_deposit').text('$' + parseFloat(bill.ending_deposit).toFixed(2));

            if (bill.type === 'S') {
                $('.paid-for-children').hide();
            } else {
                $('.paid-for-children').show();
            }

            $('#div_bill_detail').modal();
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

    <h4>Billing Report</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post" action="/admin/reports/billing">
            {{ csrf_field() }}
            <input type="hidden" name="excel" id="excel"/>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Bill.Date</label>
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
                        <label class="col-md-4 control-label">Account.ID</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="account_id"
                                   value="{{ old('account_id', $account_id) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Account.Type</label>
                        <div class="col-md-8">
                            <select class="form-control" name="account_type">
                                <option value="">All</option>
                                @if (Auth::user()->account_type == 'L')
                                    <option value="L" {{ old('account_type', $account_type) == 'L' ? 'selected' : '' }}>
                                        Root
                                    </option>
                                @endif
                                @if (in_array(Auth::user()->account_type, ['L', 'M']))
                                    <option value="M" {{ old('account_type', $account_type) == 'M' ? 'selected' : '' }}>
                                        Master
                                    </option>
                                @endif
                                <option value="D" {{ old('account_type', $account_type) == 'D' ? 'selected' : '' }}>
                                    Distributor
                                </option>
                                <option value="S" {{ old('account_type', $account_type) == 'S' ? 'selected' : '' }}>
                                    Sub-Agent
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Bounce</label>
                        <div class="col-md-8">
                            <select class="form-control" name="bounce">
                                <option value="">All</option>
                                <option value="Y" {{ old('bounce', $bounce) == 'Y' ? 'selected' : '' }}>
                                    Yes
                                </option>
                                <option value="N" {{ old('bounce', $bounce) == 'N' ? 'selected' : '' }}>
                                    No
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Suggestion Link</label>
                        <div class="col-md-8">
                            <a href="/admin/reports/ach-bounce">ACH BOUNCE REPORT</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-md-offset-8 text-right">
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


    <table class="tree table table-bordered table-hover table-condensed filter">
        <thead>
        <tr>
            <th>Bill.Date</th>
            <th>Account</th>
            <th>Starting.Balance</th>
            <th>Deposit.Total</th>
            <th>Sales</th>
            <th>Sales.Margin</th>
            <th>Adjustment</th>
            <th>Void</th>
            <th>Void.Margin</th>
            <th>Net.Revenue</th>
            <th>Bill.Amt</th>
            <th>Deposit.Paid</th>
            <th>ACH.Paid</th>
            <th>Parent.Paid</th>
            <th>Ending.Balance</th>
            <th>Ending.Deposit</th>
            <th>Bounce.Date</th>
        </tr>
        </thead>
        <tbody>
        @if (isset($bills) && count($bills) > 0)
            @foreach ($bills as $o)
                <tr class="treegrid-{{ $o->account_id }} treegrid-parent-{{ Auth::user()->account_id == $o->account_id ? '' : Helper::get_parent_id_in_collection($bills, $o->parent_id, true) }}">
                    <td>{{ $o->bill_date }}</td>
                    <td>
                        <span>{!! Helper::get_hierarchy_img($o->type) !!}</span>
                        {{ $o->name . ' ( ' . $o->account_id . ' )' }}
                    </td>
                    <td>${{ number_format($o->starting_balance, 2) }}</td>
                    <td>${{ number_format($o->deposit_total, 2) }}</td>
                    <td>${{ number_format($o->sales, 2) }}</td>
                    <td>${{ number_format($o->sales_margin, 2) }}</td>
                    <td>${{ number_format($o->adjustment, 2) }}</td>
                    <td>${{ number_format($o->void, 2) }}</td>
                    <td>${{ number_format($o->void_margin, 2) }}</td>
                    <td>${{ number_format($o->net_revenue, 2) }}</td>
                    <td>
                        <a href="/admin/reports/billing/detail/{{ $o->id }}">
                            ${{ number_format($o->bill_amt, 2) }}
                        </a>

                    </td>
                    <td>${{ number_format($o->deposit_paid_amt, 2) }}</td>
                    <td>${{ number_format($o->ach_paid_amt, 2) }}</td>
                    <td>${{ number_format($o->dist_paid_amt, 2) }}</td>
                    <td>${{ number_format($o->ending_balance, 2) }}</td>
                    <td>${{ number_format($o->ending_deposit, 2) }}</td>
                    <td>{{ $o->bounce_date }}</td>
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
            <th colspan="2" class="text-right">Total {{ $bills->total() }} record(s).</th>
            <th>${{ number_format($starting_balance, 2) }}</th>
            <th>${{ number_format($deposit_total, 2) }}</th>
            <th>${{ number_format($sales, 2) }}</th>
            <th>${{ number_format($sales_margin, 2) }}</th>
            <th>${{ number_format($adjustment, 2) }}</th>
            <th>${{ number_format($void, 2) }}</th>
            <th>${{ number_format($void_margin, 2) }}</th>
            <th>${{ number_format($net_revenue, 2) }}</th>
            <th>${{ number_format($bill_amt, 2) }}</th>
            <th>${{ number_format($deposit_paid_amt, 2) }}</th>
            <th>${{ number_format($ach_paid_amt, 2) }}</th>
            <th>${{ number_format($dist_paid_amt, 2) }}</th>
            <th>${{ number_format($ending_balance, 2) }}</th>
            <th>${{ number_format($ending_deposit, 2) }}</th>
            <th></th>
        </tr>
        </tfoot>
    </table>

    <div class="text-right">
        {{ $bills->appends(Request::except('page'))->links() }}
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

    <div class="modal" id="div_bill_detail" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Billing Detail</h4>
                </div>
                <div class="modal-body">
                    <form class="form-horizontal">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="control-label col-md-4">Invoice.#</label>
                                <div class="col-md-8">
                                    <span id="n_bill_id" class="form-control" disabled></span>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="control-label col-md-4">Invoice.Date</label>
                                <div class="col-md-8">
                                    <span id="n_bill_date" class="form-control" disabled></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <hr/>
                        </div>
                        <!--div class="form-group col-md-6">
                            <label class="control-label col-md-4">Period.From</label>
                            <div class="col-md-8">
                                <span id="n_period_from" class="form-control" disabled></span>
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="control-label col-md-4">Period.To</label>
                            <div class="col-md-8">
                                <span id="n_period_to" class="form-control" disabled></span>
                            </div>
                        </div-->
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="control-label col-md-4">Starting.Balance</label>
                                <div class="col-md-8">
                                    <span id="n_starting_balance" class="form-control" disabled></span>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="control-label col-md-4">Starting.Deposit</label>
                                <div class="col-md-8">
                                    <span id="n_starting_deposit" class="form-control" disabled></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="control-label col-md-4">&nbsp;</label>
                            </div>

                            <div class="form-group col-md-6">
                                <label class="control-label col-md-4">New.Deposit</label>
                                <div class="col-md-8">
                                    <span id="n_new_deposit" class="form-control" disabled></span>
                                </div>
                            </div>
                        </div>
                        <!--div class="form-group col-md-6">
                            <label class="control-label col-md-4">Deposit.Total</label>
                            <div class="col-md-8">
                                <span id="n_deposit_total" class="form-control" disabled></span>
                            </div>
                        </div-->
                        <div class="row">
                            <hr/>
                        </div>

                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="control-label col-md-4">Sales</label>
                                <div class="col-md-8">
                                    <span id="n_sales" class="form-control" disabled></span>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="control-label col-md-4">Sales.Margin</label>
                                <div class="col-md-8">
                                    <span id="n_sales_margin" class="form-control" disabled></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="control-label col-md-4">Void</label>
                                <div class="col-md-8">
                                    <span id="n_void" class="form-control" disabled></span>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="control-label col-md-4">Void.Margin</label>
                                <div class="col-md-8">
                                    <span id="n_void_margin" class="form-control" disabled></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="control-label col-md-4">Gross</label>
                                <div class="col-md-8">
                                    <span id="n_gross" class="form-control" disabled></span>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="control-label col-md-4">Profit</label>
                                <div class="col-md-8">
                                    <span id="n_net_margin" class="form-control" disabled></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <hr/>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="control-label col-md-4">Net.Revenue</label>
                                <div class="col-md-8">
                                    <span id="n_net_revenue" class="form-control" disabled></span>
                                </div>
                            </div>
                            <div class="form-group col-md-6 paid-for-children">
                                <label class="control-label col-md-4">Paid.For.Children</label>
                                <div class="col-md-8">
                                    <span id="n_children_paid_amt" class="form-control" disabled></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <hr/>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="control-label col-md-4">Spiff Credit</label>
                                <div class="col-md-8">
                                    <span id="n_spiff_credit" class="form-control" disabled></span>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="control-label col-md-4">Spiff Debit</label>
                                <div class="col-md-8">
                                    <span id="n_spiff_debit" class="form-control" disabled></span>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="control-label col-md-4">Residual</label>
                                <div class="col-md-8">
                                    <span id="n_residual" class="form-control" disabled></span>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="control-label col-md-4">Adjustment</label>
                                <div class="col-md-8">
                                    <span id="n_adjustment" class="form-control" disabled></span>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="control-label col-md-4">Promotion</label>
                                <div class="col-md-8">
                                    <span id="n_promotion" class="form-control" disabled></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <hr/>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="control-label col-md-4">Invoice.Amt</label>
                                <div class="col-md-8">
                                    <span id="n_bill_amt" class="form-control" disabled></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <hr/>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="control-label col-md-4">Paid.By.Deposit</label>
                                <div class="col-md-8">
                                    <span id="n_deposit_paid_amt" class="form-control" disabled></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="control-label col-md-4">Paid.By.ACH</label>
                                <div class="col-md-8">
                                    <span id="n_ach_paid_amt" class="form-control" disabled></span>
                                </div>
                            </div>
                            <div class="col-md-6">

                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6 col-md-offset-6">
                                <label class="control-label col-md-4">Paid.By.Parent</label>
                                <div class="col-md-8">
                                    <span id="n_dist_paid_amt" class="form-control" disabled></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <hr/>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="control-label col-md-4">Ending.Balance</label>
                                <div class="col-md-8">
                                    <span id="n_ending_balance" class="form-control" disabled></span>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="control-label col-md-4">Ending.Deposit</label>
                                <div class="col-md-8">
                                    <span id="n_ending_deposit" class="form-control" disabled></span>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer" style="margin-right:15px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>

    </div>
@stop
