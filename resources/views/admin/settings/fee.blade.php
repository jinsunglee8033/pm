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

        function search() {
            $('#frm_search').submit();
        }

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

        function show_fee(account_id) {

            $('#div_account_fee').modal();

            if (typeof account_id === 'undefined') {
                account_id = current_account_id;
            }

            $('#auth_account_id').val(account_id);

            myApp.showLoading();
            $.ajax({
                url: '/admin/settings/fee/show_modal',
                data: {
                    _token: '{{ csrf_token() }}',
                    account_id: account_id,
                    product_id: $('#product').val()
                },
                cache : false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();

                    if ($.trim(res.msg) === '') {
                        $('#r_fee').val(res.data.r_fee);
                        $('#m_fee').val(res.data.m_fee);
                        $('#d_fee').val(res.data.d_fee);
                        $('#s_fee').val(res.data.s_fee);
                        $('#prod_name').text($("#product").val());
                    } else {
                        myApp.showError(res.msg);
                    }

                    $('#div_account_fee').modal();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            })
        }

        function save_fee(account_id) {
            if (typeof account_id === 'undefined') {
                account_id = current_account_id;
            }
            myApp.showLoading();
            $.ajax({
                url: '/admin/settings/fee/post',
                data: {
                    _token: '{{ csrf_token() }}',
                    account_id: account_id,
                    product_id: $('#product').val(),

                    r_fee: $('#r_fee').val(),
                    m_fee: $('#m_fee').val(),
                    d_fee: $('#d_fee').val(),
                    s_fee: $('#s_fee').val(),
                },
                cache : false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    myApp.showSuccess('Your request has been processed successfully!', function() {
                        search();
                    });
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            })
        }

        function remove(account_id) {

            myApp.showConfirm('Are you sure to remove?', function() {
                if (typeof account_id === 'undefined') {
                    account_id = current_account_id;
                }
                myApp.showLoading();
                $.ajax({
                    url: '/admin/settings/fee/remove',
                    data: {
                        _token: '{{ csrf_token() }}',
                        account_id: account_id,
                        product_id: $('#product').val()
                    },
                    cache: false,
                    type: 'post',
                    dataType: 'json',
                    success: function (res) {
                        myApp.hideLoading();
                        myApp.showSuccess('Your request has been processed successfully!', function () {
                            search();
                        });
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        myApp.hideLoading();
                        myApp.showError(errorThrown);
                    }
                })
            })
        }

        function sel_product(){
            var cur_product = $("#product").val();
            search();
            // alert(cur_product);
            $('#prod_name').text("cur_product");
            $('#title').text("cur_product");
        }

        function sel_show(){
            search();
        }

        function refresh_all() {
            $('#excel').val('N');
            $("form#frm_search input[type=text]").val('');
            $("form#frm_search input[type=email]").val('');
            $("form#frm_search select").val('');
            $("#name").val('');
            $("#acct_ids").val('');
            $('#frm_search').submit();
        }

    </script>

    <h4>Fee Management</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post" action="/admin/settings/fee">
            {{ csrf_field() }}
            <input type="hidden" name="excel" id="excel"/>
            <div class="row">

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Account Type</label>
                        <div class="col-md-4">
                            <select class="form-control" name="type">
                                <option value="">All</option>
                                @if (isset($types))
                                    @foreach ($types as $o)
                                        <option value="{{ $o['code'] }}" {{ $type == $o['code'] ? 'selected' : '' }}>{{ $o['name'] }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="checkbox" name="include_sub_account" value="Y" {{ $include_sub_account == 'Y' ? 'checked' : '' }}/> Include Sub Accounts
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Account Name</label>
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="name" value="{{ $name }}"/>
                        </div>
                        <div class="col-md-4">
                            <input type="checkbox" name="include_sub_account_name" value="Y" {{ $include_sub_account_name == 'Y' ? 'checked' : '' }}/> Include Sub Accounts
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Status</label>
                        <div class="col-md-4">
                            <select class="form-control" name="status">
                                <option value="">All</option>
                                <option value="A" {{ $status == 'A' ? 'selected' : '' }}>Active</option>
                                <option value="H" {{ $status == 'H' ? 'selected' : '' }}>On-Hold</option>
                                <option value="P" {{ $status == 'P' ? 'selected' : '' }}>Pre-Auth</option>
                                <option value="F" {{ $status == 'F' ? 'selected' : '' }}>Failed Payment</option>
                                <option value="C" {{ $status == 'C' ? 'selected' : '' }}>Closed</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Account ID</label>
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="id" value="{{ $id }}"/>
                        </div>
                        <div class="col-md-4">
                            <input type="checkbox" name="include_sub_account_id" value="Y" {{ $include_sub_account_id == 'Y' ? 'checked' : '' }}/> Include Sub Accounts
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Account IDs</label>
                        <div class="col-md-5">
                            <textarea class="form-control" name="acct_ids" id="acct_ids" rows="3">{{ $acct_ids }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Email</label>
                        <div class="col-md-8">
                            <input type="email" class="form-control" name="email" value="{{ $email }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Product</label>
                        <div class="col-md-6">
                            <select class="form-control" name="product" id="product" onchange="sel_product()">
                                @if (count($products) > 0)
                                    @foreach ($products as $o)
                                        <option value="{{ $o->id }}" {{ old('id', $product) == $o->id ? 'selected' : '' }}>{{ $o->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Show</label>
                        <div class="col-md-4">
                            <select class="form-control" name="show" onchange="sel_show()">
                                <option value="A" {{ $show == 'A' ? 'selected' : '' }}>Show All</option>
                                <option value="O" {{ $show == 'O' ? 'selected' : '' }}>Only Assigned</option>
                            </select>
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
                            <button type="button" class="btn btn-info btn-sm" onclick="excel_export()">Download</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="text-left">
{{--        Total {{ $count }} record(s).--}}
    </div>

    <table class="table table-bordered table-hover table-condensed filter">
        <thead>
        <tr>
            <th>Parent</th>
            <th>Account</th>
            <th>Fee</th>
            <th>Status</th>
            <th>Product</th>
            <th>Root Fee</th>
            <th>Master Fee</th>
            <th>Distributor Fee</th>
            <th>Sub Agent Fee</th>
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
                        <a href="/admin/settings/fee/update/{{$o->id}}" target="_blank" style="display:inline; text-decoration:none;">
                            {{ $o->name . ' ( ' . $o->id . ' )' }}
                        </a>
                    </td>
                    <td>
                        <button type="button" class="btn btn-info btn-xs" onclick="show_fee({{ $o->id }})">Show</button>
                    </td>
                    <td>
                        {{ $o->status_name() }}
                    </td>
                    <td>{{ $o->prod_id }}</td>
                    <td>{{ $o->r_fee }}</td>
                    <td>{{ $o->m_fee }}</td>
                    <td>{{ $o->d_fee }}</td>
                    <td>{{ $o->s_fee }}</td>
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
{{--        Total {{ $count }} record(s).--}}
    </div>
    
    <div class="text-right">
        {{ $accounts->appends(Request::except('page'))->links() }}
    </div>

    <div class="modal" id="div_account_fee" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Fee Management</h4>
                </div>
                <div class="modal-body" style="min-height:400px;">

                    <form id="frm_account_auth" class="form-horizontal filter" method="post">
                        {{ csrf_field() }}

                        <input type="hidden" id="auth_account_id">

                        <table class="tree table table-bordered table-hover table-condensed filter">
                            <thead>
                            <tr>
                                <th>Product.ID</th>
                                <th>Root Fee</th>
                                <th>Master Fee</th>
                                <th>Distributor Fee</th>
                                <th>Sub Agent Fee</th>
                                <th>Remove</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td id="prod_name"> </td>
                                <td>
                                    <input type="text" id="r_fee" class="form-control" style="width:60px;">
                                </td>
                                <td>
                                    <input type="text" id="m_fee" class="form-control" style="width:60px;">
                                </td>
                                <td>
                                    <input type="text" id="d_fee" class="form-control" style="width:60px;">
                                </td>
                                <td>
                                    <input type="text" id="s_fee" class="form-control" style="width:60px;">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="remove()">remove</button>
                                </td>
                            </tr>

                            </tbody>
                        </table>
                    </form>

                    <button type="button" class="btn btn-primary btn-sm" onclick="save_fee()">Save</button>
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
