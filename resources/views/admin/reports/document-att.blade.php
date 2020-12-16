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
            myApp.hideLoading();
            $('#excel').val('N');
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

        function refresh_all() {
            window.location.href = '/admin/reports/document-att';
        }

    </script>

    <h4>ATT Document Report</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post" action="/admin/reports/document-att">
            {{ csrf_field() }}
            <input type="hidden" name="excel" id="excel"/>
            <div class="row">

                <div class="col-md-3">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Account IDs</label>
                        <div class="col-md-5">
                            <textarea class="form-control" name="acct_ids" rows="10">{{ $acct_ids }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label class="col-md-4 control-label">ATT IDs</label>
                        <div class="col-md-5">
                            <textarea class="form-control" name="att_ids" rows="10">{{ $att_ids }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label class="col-md-4 control-label">ATT.Dealer.Codes</label>
                        <div class="col-md-5">
                            <textarea class="form-control" name="codes" rows="10">{{ $codes }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Doc.Status</label>
                        <div class="col-md-8">
                            <select name="doc_status" class="form-control">
                                <option value="">Select</option>
                                <option value="6" {{ $doc_status == '6' ? 'selected' : '' }}>Non Checked</option>
                                <option value="3" {{ $doc_status == '3' ? 'selected' : '' }}>All Ready</option>
                                <option value="2" {{ $doc_status == '2' ? 'selected' : '' }}>AT&T Sent</option>
                                <option value="4" {{ $doc_status == '4' ? 'selected' : '' }}>AT&T Partial sent</option>
                                <option value="1" {{ $doc_status == '1' ? 'selected' : '' }}>AT&T Ready (Export)</option>
                                <option value="5" {{ $doc_status == '5' ? 'selected' : '' }}>Pending</option>
                                <option value="7" {{ $doc_status == '7' ? 'selected' : '' }}>Denied</option>
                                <option value="8" {{ $doc_status == '8' ? 'selected' : '' }}>Call</option>
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
                                <option value="11" {{ $sort == '11' ? 'selected' : '' }}>All Date ASC</option>
                                <option value="12" {{ $sort == '12' ? 'selected' : '' }}>All Date DESC</option>
                                <option value="5" {{ $sort == '5' ? 'selected' : '' }}>Agreement Date ASC</option>
                                <option value="6" {{ $sort == '6' ? 'selected' : '' }}>Agreement Date DESC</option>
                                <option value="9" {{ $sort == '9' ? 'selected' : '' }}>Biz Cert Date ASC</option>
                                <option value="10" {{ $sort == '10' ? 'selected' : '' }}>Biz Cert Date DESC</option>
                                <option value="7" {{ $sort == '7' ? 'selected' : '' }}>Driver Date ASC</option>
                                <option value="8" {{ $sort == '8' ? 'selected' : '' }}>Driver Date DESC</option>
                                <option value="3" {{ $sort == '3' ? 'selected' : '' }}>Void Check Date ASC</option>
                                <option value="4" {{ $sort == '4' ? 'selected' : '' }}>Void Check Date DESC</option>
                            </select>
                        </div>
                    </div>
                </div>

{{--                <div class="col-md-3">--}}
{{--                    <div class="form-group">--}}
{{--                        <label class="col-md-4 control-label">Account ID</label>--}}
{{--                        <div class="col-md-5">--}}
{{--                            <input type="text" class="form-control" name="acct_id" value="{{ $acct_id }}"/>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}

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

                <div class="col-md-3">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Notes</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" name="notes" value="{{ $notes }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label class="col-md-4 control-label">ATT.Dealer Notes</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" name="code_like" value="{{ $code_like }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-5 text-right">
                    Agreement <input type="checkbox" name="agreement" value="Y" {{ $agreement == 'Y' ? 'checked' : '' }}>&nbsp; &nbsp;
                    Driver.License <input type="checkbox" name="license" value="Y" {{ $license == 'Y' ? 'checked' : '' }}>&nbsp; &nbsp;
                    Business.Certification <input type="checkbox" name="certification" value="Y" {{ $certification == 'Y' ? 'checked' : '' }}>&nbsp; &nbsp;
                    Void.Check <input type="checkbox" name="void" value="Y" {{ $void == 'Y' ? 'checked' : '' }}>&nbsp; &nbsp;
                    Reverse <input type="checkbox" name="reverse" value="Y" {{ $reverse == 'Y' ? 'checked' : '' }}>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <div class="col-md-12 text-right">
                            <button type="button" class="btn btn-info btn-sm" onclick="refresh_all()">Refresh All</button>
                            <button type="submit" class="btn btn-primary btn-sm" id="btn_search">Search</button>
                            <button type="button" class="btn btn-info btn-sm" onclick="excel_export()">Download</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="text-left">
        Total {{ $count }} record(s).
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
            <th>ATT</th>
            <th>ATT ID</th>
            <th>ATT ID2</th>
            <th>ATT.Dealer.Codes</th>
            <th>ATT.Dealer.Notes</th>
            <th>Note</th>
            <th>Created.At</th>
            <th>Doc.Status</th>
            <th>Agreement</th>
            <th>Driver.License</th>
            <th>Business.Certification</th>
            <th>Void.Check</th>
            <th>Export</th>
            <th>Call</th>
            <th>Denied</th>
            <th>Pending</th>
            <th>Partial.Sent</th>
            <th>AT&T.Sent</th>
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
                    <td>
                        @if ($o->att_tid || $o->att_tid2)
                            A
                        @else

                        @endif
                    </td>
                    <td>{{ $o->att_tid }}</td>
                    <td>{{ $o->att_tid2 }}</td>
                    <td>{{ $o->att_dealer_code }}</td>
                    <td>{{ $o->att_dc_notes }}</td>
                    <td>{{ $o->notes }}</td>
                    <td>{{ $o->cdate }}</td>
                    <td>{{ $o->att_doc_status_name() }}</td>
                    <td>
                        @if($o->a_cdate)
                            <a href="/file/att_view/{{$o->file_att('FILE_ATT_AGREEMENT')->id}}">View</a>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($o->d_cdate)
                            <a href="/file/att_view/{{$o->file_att('FILE_ATT_DRIVER_LICENSE')->id}}">View</a>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($o->b_cdate)
                            <a href="/file/att_view/{{$o->file_att('FILE_ATT_BUSINESS_CERTIFICATION')->id}}">View</a>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($o->v_cdate)
                            <a href="/file/att_view/{{$o->file_att('FILE_ATT_VOID_CHECK')->id}}">View</a>
                        @else
                            -
                        @endif
                    </td>
                    <th>
                        @if ($o->all_cdate)
                            <form method="post" action="/admin/reports/document-att/pdf" target="ifm_down">
                                {!! csrf_field() !!}
                                <input type="hidden" name="id" value="{{ $o->id }}"/>
                                <button type="submit" class="btn btn-primary btn-sm" style="height:18px; padding: 0px 5px; font-size:10px;">Export</button>
                            </form>
                        @endif
                    </th>
                    <th>
                        <form id="frm_call_{{ $o->id }}" method="post" action="/admin/reports/document-att/set-status/{{ $o->id }}">
                            {!! csrf_field() !!}
                            <input type="checkbox" name="doc_status_att" value="8" onchange="$('#frm_call_{{ $o->id }}').submit()" {{ $o->doc_status_att == 8 ? 'checked' : '' }}/>
                        </form>
                    </th>
                    <th>
                        <form id="frm_denied_{{ $o->id }}" method="post" action="/admin/reports/document-att/set-status/{{ $o->id }}">
                            {!! csrf_field() !!}
                            <input type="checkbox" name="doc_status_att" value="7" onchange="$('#frm_denied_{{ $o->id }}').submit()" {{ $o->doc_status_att == 7 ? 'checked' : '' }}/>
                        </form>
                    </th>
                    <th>
                        <form id="frm_pending_{{ $o->id }}" method="post" action="/admin/reports/document-att/set-status/{{ $o->id }}">
                            {!! csrf_field() !!}
                            <input type="checkbox" name="doc_status_att" value="5" onchange="$('#frm_pending_{{ $o->id }}').submit()" {{ $o->doc_status_att == 5 ? 'checked' : '' }}/>
                        </form>
                    </th>
                    <th>
                        <form id="frm_partial_{{ $o->id }}" method="post" action="/admin/reports/document-att/set-status/{{ $o->id }}">
                            {!! csrf_field() !!}
                            <input type="checkbox" name="doc_status_att" value="4" onchange="$('#frm_partial_{{ $o->id }}').submit()" {{ $o->doc_status_att == 4 ? 'checked' : '' }}/>
                        </form>
                    </th>
                    <th>
                        <form id="frm_att_sent_{{ $o->id }}" method="post" action="/admin/reports/document-att/set-status/{{ $o->id }}">
                            {!! csrf_field() !!}
                            <input type="checkbox" name="doc_status_att" value="2" onchange="$('#frm_att_sent_{{ $o->id }}').submit()" {{ $o->doc_status_att == 2 ? 'checked' : '' }}/>
                        </form>
                    </th>
                    <th>
                        <form id="frm_all_ready_{{ $o->id }}" method="post" action="/admin/reports/document-att/set-status/{{ $o->id }}">
                            {!! csrf_field() !!}
                            <input type="checkbox" name="doc_status_att" value="3" onchange="$('#frm_all_ready_{{ $o->id }}').submit()" {{ $o->doc_status_att == 3 ? 'checked' : '' }}/>
                        </form>
                    </th>

                </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="23" class="text-center">No Record Found.</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="text-left">
        Total {{ $count }} record(s).
    </div>
    
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
