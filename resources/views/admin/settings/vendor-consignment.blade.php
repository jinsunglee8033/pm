@extends('admin.layout.default')

@section('content')

    <style type="text/css">
        .bloc1, .bloc2, .bloc3
        {
            display:inline;
        }
    </style>

    <script type="text/javascript">
        var current_mode;
        var onload_events = window.onload;

        window.onload = function() {
            if (onload_events) {
                onload_events();
            }

            $('.tree').treegrid({
                treeColumn: 2
            });

            $( "#created_sdate" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $( "#created_edate" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $( "#ulh_sdate" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $( "#ulh_edate" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $( "#pm_sdate" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });
            $( "#pm_edate" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $( "#cd_sdate" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });
            $( "#cd_edate" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $( "#vcb_sdate" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });
            $( "#vcb_edate" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });

            // tooltip
            $('[data-toggle="tooltip"]').tooltip();

        };

        var current_account_id = null;
        function close_modal(id, account_id) {
            $('#' + id).modal('hide');
            //$('#btn_search').click();
            myApp.hidePleaseWait(current_mode);

            //$('body').removeClass('modal-open');
            //$('.modal-backdrop').remove();

            if (current_mode === 'new') {
                myApp.showConfirm(
                    'You have successfully created new account. Do you want to add new user for the account?',
                    function() {
                        current_account_id = account_id
                        show_user_list(function() {
                            show_user_detail();
                        });
                    },
                    function() {
                        $('#btn_search').click();
                        return;
                    }
                )
            }
        }


        function excel_download() {
            $("#excel").val('Y');
            $('#frm_search').submit();
            myApp.hideLoading();
            $('#excel').val('');
        }



        function account_selected(id, force) {
            var cb = $('[name=cb_select]#' + id);
            if (force) {
                cb.prop('checked', true);
                $('[name=cb_select]:not(#' + id + ')').prop('checked', false);
            } else {
                if (cb.is(':checked')) {
                    $('[name=cb_select]:not(#' + id + ')').prop('checked', false);
                }
            }

            current_account_id = id;
        }

        function edit_comments(id) {
            $('#v_comments_' + id).attr('disabled', false);
            $('#v_paid_memo_' + id).attr('disabled', false);
            $('#v_status_' + id).attr('disabled', false);

            old_v_comments = $('#v_comments_' + id).val();
            old_v_paid_memo = $('#v_paid_memo_' + id).val();
            old_v_status = $('#v_status_' + id).val();

            $('#btn_comments_edit_' + id).hide();
            $('#btn_comments_update_' + id).show();
            $('#btn_comments_cancel_' + id).show();
        }

        function cancel_comments(id) {
            $('#v_comments_' + id).attr('disabled', true);
            $('#v_paid_memo_' + id).attr('disabled', true);
            $('#v_status_' + id).attr('disabled', true);

            $('#v_comments_' + id).val(old_v_comments);
            $('#v_paid_memo_' + id).val(old_v_paid_memo);
            $('#v_status_' + id).val(old_v_status);

            $('#btn_comments_edit_' + id).show();
            $('#btn_comments_update_' + id).hide();
            $('#btn_comments_cancel_' + id).hide();
        }

        function update_comments(id) {
            myApp.showLoading();
            $.ajax({
                url: '/admin/account/vcb/update',
                data: {
                    _token: '{!! csrf_token() !!}',
                    cv_id: id,
                    comments: $('#v_comments_' + id).val(),
                    paid_memo: $('#v_paid_memo_' + id).val(),
                    status: $('#v_status_' + id).val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();

                    if ($.trim(res.msg) === '') {
                        myApp.showSuccess('Comments has been updated successfully!', function() {
                            show_vcb(current_account_id);
                        });
                    } else {
                        myApp.showError(res.msg);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });
        }

        function show_vcb(account_id) {
            if (typeof account_id === 'undefined') {
                account_id = current_account_id;
            }

            myApp.showLoading();

            $.ajax({
                url: '/admin/account/vcb/list',
                data: {
                    _token: '{!! csrf_token() !!}',
                    account_id: account_id,
                    type: $('#vcb_type').val(),
                    sdate: $('#vcb_sdate').val(),
                    edate: $('#vcb_edate').val(),
                    comments: $('#vcb_comments').val(),
                    paid_memo: $('#vcb_paid_memo').val(),
                    status: $('#vcb_status').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();

                    if ($.trim(res.msg) === '') {

                        $('#vcb_account_id').html(account_id);

                        var tbody = $('#tbl_vcb').find('tbody');
                        tbody.empty();

                        console.log(res.data);
                        console.log(res.data.length);

                        if (res.data.length > 0) {
                            var total_credit = 0;
                            var html = '';
                            $.each (res.data, function(i, o) {

                                var color = o.type === 'C' ? 'black' : 'red';
                                html = '<tr>';
                                html += '<td style="text-align: center">' + o.id + '</td>';
                                html += '<td style="color:' + color + ' ; text-align: center">' + o.type_name + '</td>';
                                html += '<td style="color:' + color + ' ; text-align: right">$' + parseFloat(o.amt).toFixed(2) + '</td>';
                                html += '<td style="text-align: center">' + o.do_ach + '</td>';
                                html += '<td><textarea disabled name="v_comments_'+o.id+'" id="v_comments_'+o.id+'"> ' + (o.comments === null ? '' : o.comments) + '</textarea></td>';
                                html += '<td><textarea disabled name="v_paid_memo_'+o.id+'" id="v_paid_memo_'+o.id+'"> ' + (o.paid_memo === null ? '' : o.paid_memo) + '</textarea></td>';

                                html += '<td>';
                                if(o.type==='C') {
                                    html += '<select disabled id="v_status_' + o.id + '">';
                                    html += '<option value="N" ' + (o.status === 'N' ? 'selected' : '') + '>Not Paid</option>';
                                    html += '<option value="P" ' + (o.status === 'P' ? 'selected' : '') + '>Paid</option>';
                                    html += '<option value="F" ' + (o.status === 'F' ? 'selected' : '') + '>Follow Up</option>';
                                    html += '</select>';
                                }
                                html += '</td>';

                                html += '<td style="text-align: center">' + o.cdate + '</td>';
                                html += '<td style="text-align: center">' + o.created_by + '</td>';
                                html += '<td>';
                                html += '<button type="button" id="btn_comments_update_' + o.id + '" style="display:none" class="btn btn-primary btn-xs" onclick="update_comments(' + o.id + ')">Update</button>&nbsp;';
                                html += '<button type="button" id="btn_comments_edit_' + o.id + '" class="btn btn-primary btn-xs" onclick="edit_comments(' + o.id + ')">Edit</button>';
                                html += '<button type="button" id="btn_comments_cancel_' + o.id + '" style="display:none" class="btn btn-primary btn-xs" onclick="cancel_comments(' + o.id + ')">Cancel</button>';
                                html += '</td>';
                                html += '</tr>';

                                total_credit += parseFloat(o.amt) * ( o.type === 'C' ? 1 : -1 );

                                tbody.append(html);
                            });

                            html = '<tr>';
                            html += '<td></td>';
                            html += '<td>Total:</td>';
                            html += '<td>$' + total_credit.toFixed(2) + '</td>';
                            html += '<td colspan="5"></td>';

                            tbody.append(html);

                        } else {
                            var html = '<tr><td colspan="20">No Record Found</td></tr>';
                            tbody.append(html);
                        }

                        $('#div_vcb').modal({
                            'min-height': '400px'
                        });

                        current_account_id = account_id;
                    } else {
                        myApp.showError(res.msg);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            })
        }

        function show_vcb_detail() {
            $('#n_vcb_type').val('');
            $('#n_vcb_amt').val('');
            $('#n_vcb_comments').val('');
            $('#n_vcb_paid_memo').val('');
            $('#n_vcb_status').val('');
            $('#n_vcb_account').val(current_account_id);
            $('#n_vcb_do_ach').prop('checked', false)
            $('#n_vcb_do_ach_box').hide();
            $('#div_vcb_detail').modal();
        }

        function save_vcb_detail() {
            myApp.showLoading();

            $.ajax({
                url: '/admin/account/vcb/add',
                data: {
                    _token: '{{ csrf_token() }}',
                    account_id: current_account_id,
                    type: $('#n_vcb_type').val(),
                    amt: $('#n_vcb_amt').val(),
                    comments: $('#n_vcb_comments').val(),
                    paid_memo: $('#n_vcb_paid_memo').val(),
                    status: $('#n_vcb_status').val(),
                    do_ach: $('#n_vcb_do_ach').prop('checked') ? 'Y' : 'N'
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        $('#div_vcb_detail').modal('hide');
                        myApp.showSuccess('Your request has been processed successfully!');
                        show_vcb(current_account_id);
                    } else {
                        myApp.showError(res.msg);
                    }

                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            })
        }

        function vcb_type_change() {
            var t = $('#n_vcb_type').val();
            if (t == 'C') {
                $('#n_vcb_do_ach_box').hide();
            } else {
                $('#n_vcb_do_ach_box').show();
            }
        }

        function balance_check(){

            var balance_val = $('#balance').val();
            var total = $('#org_total').val();
            var num_zero = $('.0').length;

            if(balance_val == ''){
                $('#balance').val('Y');
                $('.0').hide();
                $('#total').text(total - num_zero);
            }else{
                $('#balance').val('');
                $('.0').show();
                $('#total').text(total);
            }
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
                $('#created_sdate').val(moment(today).format("YYYY-MM-DD"));
                $('#created_edate').val(moment(today).format("YYYY-MM-DD"));
            }else if(quick == 'This Week'){
                $('#created_sdate').val(moment(startOfWeek).format("YYYY-MM-DD"));
                $('#created_edate').val(moment(endOfWeek).format("YYYY-MM-DD"));
            }else if(quick == 'This Month'){
                $('#created_sdate').val(moment(startOfMonth).format("YYYY-MM-DD"));
                $('#created_edate').val(moment(endOfMonth).format("YYYY-MM-DD"));
            }else if(quick == 'This Year'){
                $('#created_sdate').val(moment(startOfYear).format("YYYY-MM-DD"));
                $('#created_edate').val(moment(endOfYear).format("YYYY-MM-DD"));
            }else if(quick == 'Yesterday'){
                $('#created_sdate').val(moment(yesterday).format("YYYY-MM-DD"));
                $('#created_edate').val(moment(yesterday).format("YYYY-MM-DD"));
            }else if(quick == 'Yesterday to Date'){
                $('#created_sdate').val(moment(yesterday).format("YYYY-MM-DD"));
                $('#created_edate').val(moment(today).format("YYYY-MM-DD"));
            }else if(quick == 'Last Week'){
                $('#created_sdate').val(moment(startOfLastWeek).format("YYYY-MM-DD"));
                $('#created_edate').val(moment(endOfLastWeek).format("YYYY-MM-DD"));
            }else if(quick == 'Last Week to Date'){
                $('#created_sdate').val(moment(startOfLastWeek).format("YYYY-MM-DD"));
                $('#created_edate').val(moment(today).format("YYYY-MM-DD"));
            }else if(quick == 'Last Month'){
                $('#created_sdate').val(moment(startOfLastMonth).format("YYYY-MM-DD"));
                $('#created_edate').val(moment(endOfLastMonth).format("YYYY-MM-DD"));
            }else if(quick == 'Last Month to Date'){
                $('#created_sdate').val(moment(startOfLastMonth).format("YYYY-MM-DD"));
                $('#created_edate').val(moment(today).format("YYYY-MM-DD"));
            }else if(quick == 'Last Year'){
                $('#created_sdate').val(moment(startOfLastYear).format("YYYY-MM-DD"));
                $('#created_edate').val(moment(endOfLastYear).format("YYYY-MM-DD"));
            }else if(quick == 'Last Year to Date'){
                $('#created_sdate').val(moment(startOfLastYear).format("YYYY-MM-DD"));
                $('#created_edate').val(moment(today).format("YYYY-MM-DD"));
            }else if(quick == 'Last WeekEnd'){
                $('#created_sdate').val(moment(startOfLastWeekend).format("YYYY-MM-DD"));
                $('#created_edate').val(moment(endOfLastWeek).format("YYYY-MM-DD"));
            }
        }

        function refresh_all() {
            $("form#frm_search input[type=text]").val('');
            $("form#frm_search select").val('');
            $('#frm_search').submit();
        }

    </script>

    <h4>Account List</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post" action="/admin/settings/vendor-consignment">
            {{ csrf_field() }}
            <input type="hidden" name="excel" id="excel"/>
            <div class="row">

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Account Type</label>
                        <div class="col-md-4">
                            <select id="type" name="type" class="form-control">
                                <option value="">All</option>
                                <option value="M" {{ old('type', $type) == 'M' ? 'selected' : '' }}>Master</option>
                                <option value="D" {{ old('type', $type) == 'D' ? 'selected' : '' }}>Distributor</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="checkbox" name="include_sub_account" value="Y" {{ $include_sub_account == 'Y' ? 'checked' : '' }}/> Include Sub Accounts
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Account ID</label>
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="acct_id" value="{{ $acct_id }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Account Name</label>
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="acct_name" value="{{ $acct_name }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Show me Balance</label>
                        <div class="col-md-4">
                            <input type="checkbox" id="balance" name="balance" value="" onclick="balance_check()"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">

                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <div class="col-md-12 text-right">
                            <button type="button" class="btn btn-info btn-sm" onclick="refresh_all()">Refresh All</button>
                            <button type="submit" class="btn btn-primary btn-sm" id="btn_search">Search</button>
{{--                            <button type="button" class="btn btn-info btn-sm" onclick="excel_export()">Download</button>--}}
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

    <div class="text-right">
        {{ $accounts->appends(Request::except('page'))->links() }}
    </div>
    <table class="tree table table-bordered table-hover table-condensed filter">
        <thead>
        <tr>
            <th>Parent</th>
            <th>Account</th>
            <th style="text-align: center">Status</th>
            <th style="text-align: center">Type</th>
            <th style="text-align: center">Balance</th>
            <th style="text-align: center">V.C.B</th>
            <th style="text-align: center">Created.At</th>
        </tr>
        </thead>
        <tbody>
        @if (isset($accounts) && count($accounts) > 0)
            @foreach ($accounts as $o)
                @php
                $bal = \App\Model\ConsignmentVendor::get_balance($o->id);
                @endphp
                <tr onclick="account_selected('{{ $o->id }}', true)"
{{--                    id="{{ $bal }}"--}}
                    class="{{ $bal }} treegrid-{{ $o->id }} treegrid-parent-{{ Auth::user()->account_id == $o->id ? '' : Helper::get_parent_id_in_collection($accounts, $o->parent_id) }}">
                    <td style="display:none">
                        <input type="checkbox" style="margin-left:5px; margin-top: 0px;" onclick="account_selected('{{ $o->id }}', false)" name="cb_select"
                               id="{{ $o->id }}"/>
                    </td>
                    <td style="text-align: left">
                        {!! Helper::get_parent_name_html($o->id) !!}
                    </td>
                    <td style="text-align: left">
                        <span>{!! Helper::get_hierarchy_img($o->type) !!}</span>
                        @if ($o->id == 100000)
                            <a href="/admin/account/edit/100000" style="display:inline" target="_blank">
                                {{ $o->name . ' ( ' . $o->id . ' )' }}
                            </a>
                        @else
                            <a href="/admin/account/edit/{{ $o->id == 100000 ? 100000 : $o->parent_id }}/{{ $o->id }}" style="display:inline" target="_blank">
                                {{ $o->name . ' ( ' . $o->id . ' )' }}
                            </a>
                        @endif
                    </td>
                    <td style="text-align: center">
                        {{ $o->status_name() }}
                    </td>
                    <td style="text-align: center">
                        {{ $o->type }}
                    </td>
                    <td style="text-align: right">
                        $ {{ $bal }}
                    </td>
                    <td style="text-align: center">
                        @if ($o->type == 'M' || $o->type == 'D')
                            <button type="button" class="btn btn-info btn-xs" onclick="show_vcb({{ $o->id }})">V.C.B</button>
                        @endif
                    </td>
                    <td style="text-align: center">{{ $o->cdate }}</td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="9" class="text-center">No Record Found</td>
            </tr>
        @endif
        </tbody>
    </table>
    <div class="row">
        <input type="hidden" id="org_total" value="{{ $accounts->total() }}"/>
        <div class="bloc1">
            Total
        </div>
        <div class="bloc2" id="total">
            {{ $accounts->total() }}
        </div>
        <div class="bloc3">
            record(s).
        </div>
        <div class="col-md-10  text-right">
            {{ $accounts->appends(Request::except('page'))->links() }}
        </div>
    </div>


    <div class="modal" id="div_vcb" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg" role="document" style="width:1200px !important;">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Consignment - <span id="cd_account_id"></span></h4>
                </div>
                <div class="modal-body" style="min-height:350px;">

                    <div class="well filter">
                        <form id="frm_vcb" class="form-horizontal filter" method="post">
                            {{ csrf_field() }}

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Date</label>
                                        <div class="col-md-8">
                                            <input type="text" style="width:100px; float:left;" class="form-control" id="vcb_sdate" value="2018-01-01"/>
                                            <span class="control-label" style="margin-left:10px; float:left;"> ~ </span>
                                            <input type="text" style="width:100px; margin-left: 10px; float:left;" class="form-control" id="vcb_edate" value="{{ Carbon\Carbon::today()->format('Y-m-d') }}"/>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Type</label>
                                        <div class="col-md-8">
                                            <select id="vcb_type" class="form-control">
                                                <option value="">All</option>
                                                <option value="C">Credit</option>
                                                <option value="D">Debit</option>
                                                <!--option value="A">Postpay</option-->
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Comments</label>
                                        <div class="col-md-8">
                                            <input type="text" class="form-control" id="vcb_comments"/>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Paid Memo</label>
                                        <div class="col-md-8">
                                            <input type="text" class="form-control" id="vcb_paid_memo"/>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Status</label>
                                        <div class="col-md-8">
                                            <select id="vcb_status" class="form-control">
                                                <option value="">All</option>
                                                <option value="N">Not Paid</option>
                                                <option value="P">Paid</option>
                                                <option value="F">Follow Up</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 text-right">
                                    <button type="button" class="btn btn-primary btn-sm" onclick="show_vcb()">Search</button>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="show_vcb_detail()">Add New Consignment</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <table class="table table-bordered table-hover table-condensed filter" id="tbl_vcb">
                        <thead>
                        <tr>
                            <th style="text-align: center">ID</th>
                            <th style="text-align: center">Type</th>
                            <th style="text-align: center">Amount($)</th>
                            <th style="text-align: center">Do Ach</th>
                            <th style="text-align: center">Comments</th>
                            <th style="text-align: center">Paid.Memo</th>
                            <th style="text-align: center">Status</th>
                            <th style="text-align: center">Created.At</th>
                            <th style="text-align: center">Created.By</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>


    <div class="modal" id="div_vcb_detail" tabindex="-1" role="dialog" data-background="static" data-keyboard="false" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="div_credit_title">New Consignment</h4>
                </div>
                <div class="modal-body">
                    <div class="form-horizontal">
                        <div class="form-group drp-edit">
                            <label class="control-label col-sm-4">Account</label>
                            <div class="col-sm-8">
                                <input id="n_vcb_account" class="form-control" disabled/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-4">Type</label>
                            <div class="col-sm-8">
                                <select id="n_vcb_type" class="form-control" onchange="vcb_type_change()">
                                    <option value="">Please Select</option>
                                    <option value="C">Add</option>
                                    <option value="D">Reduce</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-4">Amount($)</label>
                            <div class="col-sm-8">
                                <input id="n_vcb_amt" class="form-control" style="text-align: right"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-4">Comments</label>
                            <div class="col-sm-8">
                                <textarea id="n_vcb_comments" rows="15" style="width:100%; padding:5px;"></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-4">Paid Memo</label>
                            <div class="col-sm-8">
                                <textarea id="n_vcb_paid_memo" rows="15" style="width:100%; padding:5px;"></textarea>
                            </div>
                        </div>
                        <div class="form-group" id="n_vcb_do_ach_box" style="display: none;">
                            <label class="control-label col-sm-4"></label>
                            <div class="col-sm-8">
                                <input type="checkbox" id="n_vcb_do_ach" value="Y" checked="false"/> Send to Credit ?
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-8 col-sm-offset-4">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" onclick="save_vcb_detail()">Submit</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



@stop
