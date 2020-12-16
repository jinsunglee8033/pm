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

        function excel_export() {
            $('#excel').val('Y');
            $('#frm_search').submit();
        }

        function show_user_list(func) {
            var cb_cnt = $('[name=cb_select]:checked').length;
            if (cb_cnt === 0) {
                myApp.showError('Please select an account first');
                return;
            }
            myApp.showLoading();
            $('#ul_account_id').text(current_account_id);
            $.ajax({
                url: '/admin/account/get-user-list',
                data: {
                    account_id: current_account_id,
                    user_id: $('#ul_user_id').val(),
                    status: $('#ul_status').val(),
                    _token: '{!! csrf_token() !!}'
                },
                type: 'post',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        var tbody = $('#tbl_user_list tbody');
                        tbody.empty();
                        if (res.users && res.users.length > 0) {
                            $.each(res.users, function(i, o) {
                                var html = '<tr>';
                                html += '<td>' + o.user_id + '</td>';
                                html += '<td>' + o.name + '</td>';
                                if(o.role == 'M'){
                                    html += '<td>Manager</td>';
                                }else if(o.role == 'S'){
                                    html += '<td>Staff</td>';
                                }else{
                                    html += '<td></td>';
                                }
                                html += '<td>' + o.email + '</td>';
                                html += '<td>' + o.status_name + '</td>';
                                html += '<td>' + o.last_login + '</td>';
                                html += '</tr>';
                                tbody.append(html);
                            });
                        } else {
                            tbody.append('<tr><td colspan="8" class="text-center">No Record Found</td></tr>');
                        }
                        $('#div_user_list').modal();
                        if (func) {
                            func();
                        }
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

    </script>

    <h4>Document Report</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post" action="/admin/reports/document-h2o">
            {{ csrf_field() }}
            <input type="hidden" name="excel" id="excel"/>
            <div class="row">

                <div class="col-md-3">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Doc.Status</label>
                        <div class="col-md-8">
                            <select name="doc_status_h2o" class="form-control">
                                <option value="">All Accounts</option>
                                <option value="4" {{ $doc_status_h2o == '4' ? 'selected' : '' }}>Non Checked</option>
                                <option value="1" {{ $doc_status_h2o == '1' ? 'selected' : '' }}>H2O Ready</option>
                                <option value="2" {{ $doc_status_h2o == '2' ? 'selected' : '' }}>H2O Sent</option>
                                <option value="3" {{ $doc_status_h2o == '3' ? 'selected' : '' }}>All Documents Ready</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Sorting By</label>
                        <div class="col-md-8">
                            <select name="sort" class="form-control">
                                <option value="">Select</option>
                                <option value="1" {{ $sort == '1' ? 'selected' : '' }}>Created Date ASC</option>
                                <option value="2" {{ $sort == '2' ? 'selected' : '' }}>Created Date DESC</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Account ID</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" name="acct_id" value="{{ $acct_id }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Account Name</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" name="acct_name" value="{{ $acct_name }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label class="col-md-4 control-label">State</label>
                        <div class="col-md-5">
                            <select class="form-control" name="state" id="state">
                                <option value="">All</option>
                                @foreach ($states as $o)
                                    <option value="{{ $o->code }}" {{ old('state', $state) == $o->code ? 'selected' : '' }}>{{ $o->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label class="col-md-4 control-label">City</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" name="city" value="{{ $city }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-8 text-right">
                    H2O.Dealer.Form <input type="checkbox" name="h2o_dealer_form" value="Y" {{ $h2o_dealer_form == 'Y' ? 'checked' : '' }}>&nbsp; &nbsp;
                    H2O.ACH <input type="checkbox" name="h2o_ach" value="Y" {{ $h2o_ach == 'Y' ? 'checked' : '' }}>&nbsp; &nbsp;
                    Reverse <input type="checkbox" name="reverse" value="Y" {{ $reverse == 'Y' ? 'checked' : '' }}>&nbsp; &nbsp;
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <div class="col-md-12 text-right">
                            <button type="submit" class="btn btn-primary btn-sm" id="btn_search">Search</button>
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
            <th>Parent</th>
            <th>Account</th>
            <th>Status</th>
            <th>Users</th>
            <th>Type</th>
            <th>State</th>
            <th>City</th>
            <th>Created.At</th>
            <th>Doc.Status</th>
            <th>H2O.Dealer.Form</th>
            <th>H2O.ACH</th>
            <th>
                Export
            </th>
            <th>H2O.Sent</th>
            <th>All.Ready</th>
        </tr>
        </thead>
        <tbody>
            @if (isset($accounts) && count($accounts) > 0)
                @foreach ($accounts as $o)
                <tr onclick="account_selected('{{ $o->id }}', true)" class="treegrid-{{ $o->id }} treegrid-parent-{{ Auth::user()->account_id == $o->id ? '' : Helper::get_parent_id_in_collection($accounts, $o->parent_id) }}">
                    <td style="display:none">
                        <input type="checkbox" style="margin-left:5px; margin-top: 0px;"
                               onclick="account_selected('{{ $o->id }}', false)"
                               name="cb_select" id="{{ $o->id }}"/>
                    </td>
                    <td>{!! Helper::get_parent_name_html($o->id) !!}</td>
                    <td>
                        <span>{!! Helper::get_hierarchy_img($o->type) !!}</span>
                        <a href="/admin/account/edit/{{$o->parent_id}}/{{$o->id}}" target="_blank" style="display:inline; text-decoration:none;">
                            {{ $o->name . ' ( ' . $o->id . ' )' }}
                        </a>
                    </td>
                    <td>
                        <a href="https://www.google.com/maps/place/{{ $o->address1 . " " . $o->address2 . " " . $o->city . " " . $o->state . " " . $o->zip }}" target="_blank"
                           data-toggle="tooltip" title="{{ $o->address1 . " " . $o->address2 . " " . $o->city . " " . $o->state . " " . $o->zip }}">
                            {{ $o->status_name() }}
                        </a>
                    </td>
                    <td>
                        <a href="javascript:show_user_list()">Users</a>
                        {{ \App\Model\Account::getUserCountByAccount($o->id) }}
                    </td>
                    <td>
                        <a href="https://maps.apple.com/?address={{ $o->address1 . " " . $o->address2 . " " . $o->city . " " . $o->state . " " . $o->zip }}" target="_blank">
                            {{ $o->type_name() }}
                        </a>
                    </td>
                    <td>{{ $o->state }}</td>
                    <td>{{ $o->city }}</td>
                    <td>{{ $o->cdate }}</td>
                    <td>{{ $o->h2o_doc_status_name() }}</td>
                    <td>
                        @if ($o->file('FILE_H2O_DEALER_FORM'))
                            <a href="/file/view/{{$o->file('FILE_H2O_DEALER_FORM')->id}}">View</a>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if ($o->file('FILE_H2O_ACH'))
                            <a href="/file/view/{{$o->file('FILE_H2O_ACH')->id}}">View</a>
                        @else
                            -
                        @endif
                    </td>
                    <th>
                        @if ($o->is_h2o_ready())
                            <form method="post" action="/admin/reports/document-h2o/pdf" target="ifm_down">
                                {!! csrf_field() !!}
                                <input type="hidden" name="id" value="{{ $o->id }}"/>
                                <button type="submit" class="btn btn-primary btn-sm" style="height:18px; padding: 0px 5px; font-size:10px;">Export</button>
                            </form>
                        @endif
                    </th>
                    <th>
                        <form id="frm_h2o_sent_{{ $o->id }}" method="post" action="/admin/reports/document-h2o/set-status/{{ $o->id }}">
                            {!! csrf_field() !!}
                            <input type="checkbox" name="doc_status_h2o" value="2" onchange="$('#frm_h2o_sent_{{ $o->id }}').submit()" {{ $o->doc_status_h2o == 2 ? 'checked' : '' }}/>
                        </form>
                    </th>
                    <th>
                        <form id="frm_all_ready_{{ $o->id }}" method="post" action="/admin/reports/document-h2o/set-status/{{ $o->id }}">
                            {!! csrf_field() !!}
                            <input type="checkbox" name="doc_status_h2o" value="3" onchange="$('#frm_all_ready_{{ $o->id }}').submit()" {{ $o->doc_status_h2o == 3 ? 'checked' : '' }}/>
                        </form>
                    </th>
                </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="20" class="text-center">No Record Found.</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="text-right">
        {{ $accounts->appends(Request::except('page'))->links() }}
    </div>

    <div class="modal" id="div_user_list" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">User List - <span id="ul_account_id"></span></h4>
                </div>
                <div class="modal-body">

                    <table class="table table-bordered table-hover table-condensed filter" id="tbl_user_list">
                        <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Last Login</th>
                        </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div style="display:none">
        <iframe name="ifm_down"></iframe>
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
