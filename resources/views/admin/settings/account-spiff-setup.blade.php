@extends('admin.layout.default')

@section('content')
    <style type="text/css">
        .tab-pane {
            border: solid 1px #ddd;
            border-top: none;
        }

        input[type=text]:disabled {
            background-color: #efefef;
        }

        table.blueTable {
            border: 1px solid #1C6EA4;
            background-color: #EEEEEE;
            width: 100%;
            text-align: left;
            border-collapse: collapse;
        }
        table.blueTable td, table.blueTable th {
            border: 1px solid #AAAAAA;
            padding: 3px 2px;
        }
        table.blueTable tbody td {
            font-size: 13px;
        }
        table.blueTable tr:nth-child(even) {
            background: #D0E4F5;
        }
        table.blueTable thead {
            background: #1C6EA4;
            background: -moz-linear-gradient(top, #5592bb 0%, #327cad 66%, #1C6EA4 100%);
            background: -webkit-linear-gradient(top, #5592bb 0%, #327cad 66%, #1C6EA4 100%);
            background: linear-gradient(to bottom, #5592bb 0%, #327cad 66%, #1C6EA4 100%);
            border-bottom: 2px solid #444444;
        }
        table.blueTable thead th {
            font-size: 15px;
            font-weight: bold;
            color: #FFFFFF;
            border-left: 2px solid #D0E4F5;
        }
        table.blueTable thead th:first-child {
            border-left: none;
        }

        table.blueTable tfoot {
            font-size: 14px;
            font-weight: bold;
            color: #FFFFFF;
            background: #D0E4F5;
            background: -moz-linear-gradient(top, #dcebf7 0%, #d4e6f6 66%, #D0E4F5 100%);
            background: -webkit-linear-gradient(top, #dcebf7 0%, #d4e6f6 66%, #D0E4F5 100%);
            background: linear-gradient(to bottom, #dcebf7 0%, #d4e6f6 66%, #D0E4F5 100%);
            border-top: 2px solid #444444;
        }
        table.blueTable tfoot td {
            font-size: 14px;
        }
        table.blueTable tfoot .links {
            text-align: right;
        }
        table.blueTable tfoot .links a{
            display: inline-block;
            background: #1C6EA4;
            color: #FFFFFF;
            padding: 2px 8px;
            border-radius: 5px;
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

        function refresh_all() {
            window.location.href = '/admin/settings/account-spiff-setup';
        }

        function excel_download() {
            $("#excel").val('Y');
            $('#frm_search').submit();
            myApp.hideLoading();
            $('#excel').val('');
        }

        function reset_form() {

            $('#rootwizard').bootstrapWizard('first');

            document.getElementById('frm_profile').reset();
        }

        function login_as() {
            var user_id = $('#ud_user_id').val()
            $('#ud_la_user_id').val(user_id);
            $('#frm_login_as').submit();
        }

        function login_as_in_list(user_id) {
            $('#ud_la_user_id').val(user_id);
            $('#frm_login_as').submit();
        }

        function remove_user(user_id) {
            myApp.showConfirm("Are you sure to remove this user?", function() {
                myApp.showLoading();
                $.ajax({
                    url: '/admin/account/remove-user',
                    data: {
                        user_id: user_id,
                        _token: '{!! csrf_token() !!}'
                    },
                    type: 'post',
                    dataType: 'json',
                    cache: false,
                    success: function(res) {
                        myApp.hideLoading();
                        if ($.trim(res.msg) === '') {
                            $('#div_user_list').modal('hide');
                            myApp.showSuccess('Your request has been processed successfully!', function() {
                                show_user_list();
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
            }, function() {
                // do nothing
            });
        }

        function save_user_detail(user_id) {
            var mode = typeof user_id === 'undefined' ? 'new' : 'edit';
            var url = mode == 'new' ? '/admin/account/add-user' : '/admin/account/update-user';

            myApp.showLoading();

            $.ajax({
                url: url,
                data: {
                    account_id: current_account_id,
                    user_id: $('#ud_user_id').val(),
                    name: $('#ud_name').val(),
                    role: $('#ud_role').val(),
                    status: $('#ud_status').val(),
                    password: $('#ud_password').val(),
                    password_confirmation: $('#ud_password_confirmation').val(),
                    email: $('#ud_email').val(),
                    copy_email: $('#ud_copy_email').val(),
                    comment: $('#ud_comment').val(),
                    _token: '{!! csrf_token() !!}'
                },
                type: 'post',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        $('#div_user_detail').modal('hide');
                        myApp.showSuccess('Your request has been processed successfully', function() {
                            show_user_list();
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

        function show_user_detail(user_id) {
            var mode = typeof user_id === 'undefined' ? 'new' : 'edit';
            var title = 'User Detail';

            if (mode === 'new') {
                title = 'New User';

                $('#user_detail_title').text(title);

                $('#ud_user_id').prop('readonly', false);
                $('#ud-profile').tab('show');
                $('#li-tab-login-history').hide();
                $('#div_user_detail').modal();
                $('#btn_save_user').off('click').on('click',function(e) {
                    save_user_detail();
                });
                $('#ud_user_id').val('');
                $('#ud_name').val('');
                $('#ud_role').val('');
                $('#ud_status').val('');
                $('#ud_email').val('');
                $('#ud_password').val('');
                $('#ud_password_confirmation').val('');

                $('#btn_login_as').hide();

                // do not allow space between letters
                $('#ud_user_id').on('keydown', function (e) { return e.which !== 32; });
                
            } else {

                $('#user_detail_title').text(title);

                $('#ud_user_id').prop('readonly', true);
                $('#btn_save_user').off('click').on('click', function(e) {
                    save_user_detail(user_id);
                });
                $('#li-tab-login-history').show();

                $('#btn_login_as').show();

                myApp.showLoading();
                $.ajax({
                    url: '/admin/account/get-user-info',
                    data: {
                        user_id: user_id,
                        _token: '{!! csrf_token() !!}'
                    },
                    type: 'post',
                    dataType: 'json',
                    cache: false,
                    success: function(res) {
                        myApp.hideLoading();

                        if ($.trim(res.msg) === '') {

                            var o = res.user;
                            
                            if (o.account_id == '{{ Auth::user()->account_id }}') {
                                $('#btn_login_as').hide();
                            } else {
                                $('#btn_login_as').show();
                            }

                            $('#ud_role').empty();
                            $('#ud_role').append('<option value="">Please Select</option>');
                            console.log(res.roles);

                            $.each(res.roles, function(i, r) {
                                var html = '<option value="' + r.type + '">' + r.name + '</option>';
                                $('#ud_role').append(html);
                            })

                            $('#ud_user_id').val(o.user_id);
                            $('#ud_name').val(o.name);
                            $('#ud_role').val(o.role);
                            $('#ud_status').val(o.status);
                            $('#ud_email').val(o.email);
                            $('#ud_password').val('');
                            $('#ud_password_confirmation').val('');

                            $('#div_user_detail').modal();

                            var tbody = $('#tbl_user_login_history tbody');
                            tbody.empty();
                            $.each(res.login_history, function(i, o) {
                                var html = '<tr>';
                                html += '<td>' + o.cdate + '</td>';
                                html += '<td>' + o.user_id + '</td>';
                                html += '<td>' + o.password + '</td>';
                                html += '<td>' + o.result + '</td>';
                                html += '<td>' + o.result_msg + '</td>';
                                html += '<td>' + o.ip + '</td>';
                                html += '</tr>';

                                tbody.append(html);
                            });

                            if (res.login_history.length < 1) {
                                tbody.append('<tr><td colspan="6">No Record Found</td></tr>');
                            }

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
                                html += '<td><a href="javascript:show_user_detail(\'' + o.user_id + '\')">' + o.user_id + '</a></td>';
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
                                if (current_account_id == '{{ Auth::user()->account_id }}' && '{{Auth::user()->user_id}}' != 'admin' ) {
                                    html += '<td>-</td>';
                                } else {
                                    html += '<td><a href="javascript:login_as_in_list(\'' + o.user_id + '\')">Login As</a></td>';
                                }

                                html += '<td><a href="javascript:remove_user(\'' + o.user_id + '\')">Remove</a></td>';

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

        function remove_account() {

            if (current_mode === 'new') {
                myApp.showError('Something is wrong!');
                return;
            }

            myApp.showConfirm(
                'Are you sure to remove this account?',
                function() {
                    myApp.showPleaseWait(current_mode);
                    $('#frm_profile').attr('action', '/admin/account/remove');
                    $('#frm_profile').submit();
                },
                function() {
                    return;
                }
            )
        }

        var current_rate_plan_id = null;
        function show_rate_plan(rate_plan_id) {
            var mode = typeof rate_plan_id === 'undefined' ? 'new' : 'edit';
            current_rate_plan_id = rate_plan_id;

            if (mode === 'new') {
                $('.drp-edit').hide();
                $('.drp-new').show();
                $('#div_rate_plan_title').text('New Rate Plan');
                $('#drp_id').val('');
                $('#drp_name').val('');
                $('#drp_status').val('');
                $('#drp_last_updated').val('');
            } else {
                $('.drp-new').hide();
                $('.drp-edit').show();
                $('#div_rate_plan_title').text('Rate Plan Detail');
            }

            $('#div_rate_plan').modal();
        }

        function load_rate_plan(rate_plan_id) {
            myApp.showLoading();
            $.ajax({
                url: '/admin/account/rate-plan/load-plan',
                data: {
                    _token: '{!! csrf_token() !!}',
                    id: rate_plan_id
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {

                        current_rate_plan_id = rate_plan_id;
                        var o = res.rate_plan;

                        $('#drp_id').val(o.id);
                        $('#drp_type').val(o.type);
                        $('#drp_name').val(o.name);
                        $('#drp_status').val(o.status);
                        $('#drp_last_updated').val(o.last_updated);

                        show_rate_plan(rate_plan_id);

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

        var current_show_type = null;

        function show_rate_detail(rate_plan_id, show_type) {

//            $('#vendor').val('');
//            $('#product_id').val('');
//            $('#product_name').val('');

            get_rate_detail(rate_plan_id, show_type);
        }

        function get_rate_detail(rate_plan_id, show_type) {
            myApp.showLoading();


            $.ajax({
                url: '/admin/account/rate-detail/load',
                data: {
                    _token: '{{ csrf_token() }}',
                    rate_plan_id: rate_plan_id,
                    show_type: show_type /* M: Mine, O: Owned */,
                    vendor: $('#vendor').val(),
                    action: $('#action').val(),
                    product_id: $('#product_id').val(),
                    product_name: $('#product_name').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {

                        $('#div_rate_detail_title').text(res.rate_plan_name);

                        show_type = res.show_type;

                        current_rate_plan_id = rate_plan_id;
                        current_show_type = show_type;

                        $('#tbody_rate_detail').empty();

                        $('#th_parent_rates').hide();
                        if (show_type === 'O') {
                            $('#th_parent_rates').show();
                        }

                        $('#th_cost').hide();
                        $('#th_vendor').hide();
                        @if(Auth::user()->account_type == 'L' && (in_array(Auth::user()->user_id, ['thomas', 'admin', 'system'])))
                             $('#th_cost').show();
                             $('#th_vendor').show();
                        @endif

                        var html = '';
                        if (res.rates.length > 0) {
                            $.each(res.rates, function(i, o) {
                                html += '<tr>';
                                html += '<td>' + o.product_name + ' ( ' + o.product_id + ' )</td>';
                                html += '<td>' + o.action + '</td>';
                                html += '<td>' + o.denom + '</td>';

                                @if(Auth::user()->account_type == 'L' && (in_array(Auth::user()->user_id, ['thomas', 'admin', 'system'])))
                                        html += '<td>' + o.vendor + '</td>';
                                        html += '<td>' + parseFloat(o.cost).toFixed(2) + '</td>';
                                @endif

                                if (show_type === 'O' || show_type === 'L') {
                                    if (show_type === 'O') {
                                        html += '<td>' + o.parent_rates + '</td>';
                                    }

                                    html += '<td><input disabled type="text" size="8" onfocus="$(this).attr(\'old_rates\', $(this).val());" id="rate_detail_' + o.denom_id + '_' + o.action +'" value="' + o.rates + '"/></td>';

                                    html += '<td>';
                                    html +=     '<button type="button" id="btn_edit_' + o.denom_id + '" class="btn btn-primary btn-xs" onclick="edit_rates(' + o.denom_id + ',\'' + o.action + '\')">Edit</button>&nbsp;';
                                    html +=     '<button type="button" style="display:none" id="btn_update_' + o.denom_id + '" class="btn btn-primary btn-xs" onclick="update_rates(' + o.denom_id + ',\'' + o.action + '\')">Update</button>&nbsp;';
                                    html +=     '<button type="button" style="display:none" id="btn_cancel_' + o.denom_id + '" class="btn btn-primary btn-xs" onclick="cancel_rates(' + o.denom_id + ',\'' + o.action + '\')">Cancel</button>&nbsp;';

                                    html += '</td>';

                                } else {
                                    html += '<td>' + o.rates + '</td>';
                                }
                                html += '</tr>';
                            });
                        } else {
                            html += '<tr><td colspan="20">No Record Found. Meaning your parent account rate plan does not have any rates yet.</td></tr>';
                        }


                        $('#tbody_rate_detail').append(html);
                        $('#div_rate_detail').modal();
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

        var old_rate = null;

        function edit_rates(id, action) {
            $('#rate_detail_' + id + '_' + action).attr('disabled', false);
            old_rate = $('#rate_detail_' + id + '_' + action).val();

            $('#btn_edit_' + id).hide();
            $('#btn_update_' + id).show();
            $('#btn_cancel_' + id).show();
        }

        function cancel_rates(id, action) {
            $('#rate_detail_' + id + '_' + action).attr('disabled', true);
            $('#rate_detail_' + id + '_' + action).val(old_rate);

            $('#btn_edit_' + id).show();
            $('#btn_update_' + id).hide();
            $('#btn_cancel_' + id).hide();
        }

        function update_rates(denom_id, action) {
            var rate_detail = $('#rate_detail_' + denom_id + '_' + action);
            var new_value = rate_detail.val();

            if ($.trim(new_value) === '') {
                myApp.showConfirm("Are you sure to remove the rates? All children rates of this denomination will be removed as well.", function() {
                    update_rates_real(denom_id, action);
                });
            } else {
                update_rates_real(denom_id, action);
            }
        }

        function update_rates_real(denom_id, action) {
            var rate_detail = $('#rate_detail_' + denom_id + '_' + action);
            var old_value = rate_detail.attr('old_rates');
            var new_value = rate_detail.val();

            myApp.showLoading();
            $.ajax({
                url: '/admin/account/rate-detail/update',
                data: {
                    _token: '{{ csrf_token() }}',
                    rate_plan_id: current_rate_plan_id,
                    denom_id: denom_id,
                    action: action,
                    rates: new_value
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        show_rate_detail(current_rate_plan_id, current_show_type);
                    } else {
                        rate_detail.val(old_value);
                        myApp.showError(res.msg);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    rate_detail.val(old_value);
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });
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

        function show_credit(account_id) {
            if (typeof account_id === 'undefined') {
                account_id = current_account_id;
            }

            myApp.showLoading();

            $.ajax({
                url: '/admin/account/credit/list',
                data: {
                    _token: '{!! csrf_token() !!}',
                    account_id: account_id,
                    type: $('#cd_type').val(),
                    sdate: $('#cd_sdate').val(),
                    edate: $('#cd_edate').val(),
                    comments: $('#cd_comments').val(),
                    paid_memo: $('#cd_paid_memo').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();

                    if ($.trim(res.msg) === '') {

                        $('#cd_account_id').html(account_id);

                        var tbody = $('#tbl_credit').find('tbody');
                        tbody.empty();

                        console.log(res.data);
                        console.log(res.data.length);

                        if (res.data.length > 0) {
                            var total_credit = 0;
                            var html = '';
                            $.each (res.data, function(i, o) {

                                var color = o.type === 'C' ? 'black' : 'red';
                                html = '<tr>';
                                html += '<td>' + o.id + '</td>';
                                html += '<td style="color:' + color + '">' + o.type_name + '</td>';
                                html += '<td style="color:' + color + '">$' + parseFloat(o.amt).toFixed(2) + '</td>';
                                html += '<td>' + o.comments + '</td>';
                                html += '<td>' + o.paid_memo + '</td>';
                                html += '<td>' + o.cdate + '</td>';
                                html += '<td>' + o.created_by + '</td>';
                                html += '</tr>';

                                total_credit += parseFloat(o.amt) * ( o.type === 'C' ? 1 : -1 );

                                tbody.append(html);
                            });

                            html = '<tr>';
                            html += '<td></td>';
                            html += '<td>Total:</td>';
                            html += '<td>$' + total_credit.toFixed(2) + '</td>';
                            html += '<td colspan="4"></td>';

                            tbody.append(html);

                        } else {
                            var html = '<tr><td colspan="20">No Record Found</td></tr>';
                            tbody.append(html);
                        }

                        $('#div_credit').modal({
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

        function show_authority(account_id) {
            if (typeof account_id === 'undefined') {
                account_id = current_account_id;
            }

            $('#auth_account_id').val(account_id);

            myApp.showLoading();
            $.ajax({
                url: '/admin/account/authority',
                data: {
                    _token: '{{ csrf_token() }}',
                    account_id: account_id
                },
                cache : false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();

                    if ($.trim(res.msg) === '') {
                        $('#auth_batch_rtr').attr('checked', res.data.auth_batch_rtr == 'Y');
                        $('#auth_batch_sim_swap').attr('checked', res.data.auth_batch_sim_swap == 'Y');
                        $('#auth_batch_plan_change').attr('checked', res.data.auth_batch_plan_change == 'Y');
                        $('#for_rtr_fee_orverride').val(res.data.for_rtr_tier);
                        $('#for_sim_swap_fee_orverride').val(res.data.for_sim_swap_tier);
                        $('#for_plan_change_fee_orverride').val(res.data.for_plan_change_tier);
                        $('#for_rtr_sdate').val(res.data.for_rtr_sdate);
                        $('#for_rtr_edate').val(res.data.for_rtr_edate);
                        $('#for_rtr_fee').val(res.data.for_rtr);
                        $('#for_sim_swap_sdate').val(res.data.for_sim_swap_sdate);
                        $('#for_sim_swap_edate').val(res.data.for_sim_swap_edate);
                        $('#for_sim_swap_fee').val(res.data.for_sim_swap);
                        $('#for_plan_change_sdate').val(res.data.for_plan_change_sdate);
                        $('#for_plan_change_edate').val(res.data.for_plan_change_edate);
                        $('#for_plan_change_fee').val(res.data.for_plan_change);

                        $('#for_rtr_daily').val(res.data.for_rtr_daily);
                        $('#for_rtr_weekly').val(res.data.for_rtr_weekly);
                        $('#for_rtr_monthly').val(res.data.for_rtr_monthly);
                        $('#for_sim_swap_daily').val(res.data.for_sim_swap_daily);
                        $('#for_sim_swap_weekly').val(res.data.for_sim_swap_weekly);
                        $('#for_sim_swap_monthly').val(res.data.for_sim_swap_monthly);
                        $('#for_plan_change_daily').val(res.data.for_plan_change_daily);
                        $('#for_plan_change_weekly').val(res.data.for_plan_change_weekly);
                        $('#for_plan_change_monthly').val(res.data.for_plan_change_monthly);
                    } else {
                        myApp.showError(res.msg);
                    }

                    $('#div_account_authority').modal();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            })
        }

        function save_authority(account_id) {
            if (typeof account_id === 'undefined') {
                account_id = current_account_id;
            }

            $('#auth_account_id').val(account_id);

            myApp.showLoading();
            $.ajax({
                url: '/admin/account/authority/post',
                data: {
                    _token: '{{ csrf_token() }}',
                    account_id: account_id,
                    auth_batch_rtr: $('#auth_batch_rtr').prop('checked') ? 'Y' : 'N',
                    auth_batch_sim_swap: $('#auth_batch_sim_swap').prop('checked') ? 'Y' : 'N',
                    auth_batch_plan_change: $('#auth_batch_plan_change').prop('checked') ? 'Y' : 'N',
                    for_rtr_tier: $('#for_rtr_fee_orverride').val(),
                    for_sim_swap_tier: $('#for_sim_swap_fee_orverride').val(),
                    for_plan_change_tier: $('#for_plan_change_fee_orverride').val(),
                    for_rtr_sdate: $('#for_rtr_sdate').val(),
                    for_rtr_edate: $('#for_rtr_edate').val(),
                    for_rtr_fee: $('#for_rtr_fee').val(),
                    for_sim_swap_sdate: $('#for_sim_swap_sdate').val(),
                    for_sim_swap_edate: $('#for_sim_swap_edate').val(),
                    for_sim_swap_fee: $('#for_sim_swap_fee').val(),
                    for_plan_change_sdate: $('#for_plan_change_sdate').val(),
                    for_plan_change_edate: $('#for_plan_change_edate').val(),
                    for_plan_change_fee: $('#for_plan_change_fee').val(),
                    for_rtr_daily: $('#for_rtr_daily').val(),
                    for_rtr_weekly: $('#for_rtr_weekly').val(),
                    for_rtr_monthly: $('#for_rtr_monthly').val(),
                    for_sim_swap_daily: $('#for_sim_swap_daily').val(),
                    for_sim_swap_weekly: $('#for_sim_swap_weekly').val(),
                    for_sim_swap_monthly: $('#for_sim_swap_monthly').val(),
                    for_plan_change_daily: $('#for_plan_change_daily').val(),
                    for_plan_change_weekly: $('#for_plan_change_weekly').val(),
                    for_plan_change_monthly: $('#for_plan_change_monthly').val(),
                },
                cache : false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();

                    alert(res.msg);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            })

        }

        function show_spiff_template(account_id, account_type) {

            $('#st_account_id').val(account_id);

            @if (!empty($d_spiff_templates) && count($d_spiff_templates) > 0)
                @foreach ($d_spiff_templates as $d)
                    $('#spiff_template_id_{{ $d->id }}').prop('checked', false);
                    $('#spiff_template_msg_{{ $d->id }}').text('');
                @endforeach
            @endif

            @if (!empty($s_spiff_templates) && count($s_spiff_templates) > 0)
                @foreach ($s_spiff_templates as $d)
                    $('#spiff_template_id_{{ $d->id }}').prop('checked', false);
                    $('#spiff_template_msg_{{ $d->id }}').text('');
                @endforeach
            @endif

            if (account_type == 'M') {
                $('#dist_spiff_templates_boby').show();
            } else {
                $('#dist_spiff_templates_boby').hide();
            }

            myApp.showLoading();
            $.ajax({
                url: '/admin/settings/account-spiff-setup/spiff-template',
                data: {
                    _token: '{{ csrf_token() }}',
                    account_id: account_id
                },
                cache : false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();

                    if (res.count > 0) {
                        $.each (res.spiff_templates, function(i, o) {
                            $('#spiff_template_id_' + o.id).prop('checked', true);
                        });
                    }

                    if (res.d_count > 0) {
                        $.each (res.dis_spiff_templates, function(i, o) {
                            $('#dis_spiff_temp_from_'+o.id).attr('disabled', 'disabled');
                            $('#dis_spiff_temp_to_'+o.id).attr('disabled', 'disabled');
                        });
                    }

                    if (res.s_count > 0) {
                        $.each (res.sub_spiff_templates, function(i, o) {
                            $('#sub_spiff_temp_from_'+o.id).attr('disabled', 'disabled');
                            $('#sub_spiff_temp_to_'+o.id).attr('disabled', 'disabled');
                        });
                    }

                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });

            $('#div_spiff_template').modal();
        }

        function onclick_spiff_template(template_id) {
            $('#st_account_id').val();

            myApp.showLoading();
            $.ajax({
                url: '/admin/account/spiff_template/check',
                data: {
                    _token: '{{ csrf_token() }}',
                    account_id: $('#st_account_id').val(),
                    template_id: template_id
                },
                cache : false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if (res.code == '0') {
                        $checked = $('#spiff_template_id_' + template_id).prop('checked');
                        if ($checked) {
                            $('#spiff_template_id_' + template_id).prop('checked', false);
                        } else {
                            $('#spiff_template_id_' + template_id).prop('checked', true);
                        }
                        myApp.showSuccess('Your request has been successfully processed!!!!!', function () {
                            $('#frm_search').submit();
                        });
                    }else{
                        $checked = $('#spiff_template_id_' + template_id).prop('checked');
                        if ($checked) {
                            $('#spiff_template_id_' + template_id).prop('checked', false);
                        } else {
                            $('#spiff_template_id_' + template_id).prop('checked', true);
                        }
                        myApp.showError(res.msg, function() {
                            $('#frm_search').submit();
                        });
                    }

                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });
        }

        function update_dis_temps(){
            var account_id = $('#st_account_id').val();
            var dis_temp_from = $('#dis_temp_from').val();
            var dis_temp_to = $('#dis_temp_to').val();

            if(dis_temp_from == dis_temp_to){
                alert("Please select different spiff templates");
                return;
            }

            myApp.showLoading();

            $.ajax({
                url: '/admin/settings/account-spiff-setup/update-dis-temps',
                data: {
                    _token: '{{ csrf_token() }}',
                    account_id: account_id,
                    dis_temp_from: dis_temp_from,
                    dis_temp_to: dis_temp_to
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    if(res.msg == '') {
                        myApp.hideLoading();
                        myApp.showSuccess('Your request has been successfully processed!', function () {
                            $('#frm_search').submit();
                        });
                    }else{
                        myApp.hideLoading();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            })
        }

        function update_sub_temps(){
            var account_id = $('#st_account_id').val();
            var sub_temp_from = $('#sub_temp_from').val();
            var sub_temp_to = $('#sub_temp_to').val();

            if(sub_temp_from == sub_temp_to){
                alert("Please select different spiff templates");
                return;
            }

            myApp.showLoading();

            $.ajax({
                url: '/admin/settings/account-spiff-setup/update-sub-temps',
                data: {
                    _token: '{{ csrf_token() }}',
                    account_id: account_id,
                    sub_temp_from: sub_temp_from,
                    sub_temp_to: sub_temp_to
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    if(res.msg == '') {
                        myApp.hideLoading();
                        myApp.showSuccess('Your request has been successfully processed!', function () {
                            $('#frm_search').submit();
                        });
                    }else{
                        myApp.hideLoading();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            })
        }

        function all_active(){

            var account_id = $('#st_account_id').val();
            var act_product = $('#act_product').val();

            if(act_product.length < 1){
                alert("Please Select Product");
                return;
            }

            myApp.showLoading();

            $.ajax({
                url: '/admin/settings/account-spiff-setup/all-active',
                data: {
                    _token: '{{ csrf_token() }}',
                    account_id: account_id,
                    act_product: act_product
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    if(res.msg == '') {
                        myApp.hideLoading();
                        myApp.showSuccess('Your request has been successfully processed!', function () {
                            $('#frm_search').submit();
                        });
                    }else{
                        myApp.hideLoading();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            })
        }

        function all_inactive(){
            var account_id = $('#st_account_id').val();
            var act_product = $('#act_product').val();

            myApp.showLoading();

            $.ajax({
                url: '/admin/settings/account-spiff-setup/all-inactive',
                data: {
                    _token: '{{ csrf_token() }}',
                    account_id: account_id,
                    act_product: act_product
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    if(res.msg == '') {
                        myApp.hideLoading();
                        myApp.showSuccess('Your request has been successfully processed!', function () {
                            $('#frm_search').submit();
                        });
                    }else{
                        myApp.hideLoading();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            })
        }

        function excel_export() {
            $('#excel').val('Y');
            $('#frm_search').submit();
            myApp.hideLoading();
            $('#excel').val('');
        }

    </script>

    <h4>Account Spiff Setup</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form class="form-horizontal" id="frm_search" name="frm_search" method="post" action="/admin/settings/account-spiff-setup">
            {{ csrf_field() }}

            <input type="hidden" name="excel" id="excel">

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
                        <div class="col-md-8">
                            <select class="form-control" name="status">
                                <option value="">All</option>
                                <option value="A" {{ $status == 'A' ? 'selected' : '' }}>Active</option>
                                <option value="H" {{ $status == 'H' ? 'selected' : '' }}>On-Hold</option>
                                <option value="P" {{ $status == 'P' ? 'selected' : '' }}>Pre-Auth</option>
                                <option value="F" {{ $status == 'F' ? 'selected' : '' }}>Failed Payment</option>
                                <option value="B" {{ $status == 'B' ? 'selected' : '' }}>Become Dealer</option>
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
                        <label class="col-md-4 control-label">Account.IDs</label>
                        <div class="col-md-4">
                            <textarea class="form-control" name="ids" rows="3">{{ $ids }}</textarea>
                        </div>
                        <div class="col-md-4">
                            <input type="checkbox" name="ids_except" value="Y" {{ $ids_except == 'Y' ? 'checked' : '' }}/> Except Them
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Spiff Template</label>
                        <div class="col-md-8">
                            <select class="form-control" name="spiff_template">
                                <option value="">All</option>
                                @if (isset($spiff_templates))
                                    @foreach ($spiff_templates as $o)
                                        <option value="{{ $o['id'] }}" {{ $spiff_template == $o['id'] ? 'selected' : '' }}>{{ $o['template'] }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Product Allow</label>
                        <div class="col-md-8">
                            <input type="checkbox" name="allow_boom" value="Y" {{ $allow_boom == 'Y' ? 'checked' : '' }}/> Boom
                            &nbsp;<input type="checkbox" name="allow_freeup" value="Y" {{ $allow_freeup == 'Y' ? 'checked' : '' }}/> FreeUP
                            &nbsp;<input type="checkbox" name="allow_gen" value="Y" {{ $allow_gen == 'Y' ? 'checked' : '' }}/> GEN
                            &nbsp;<input type="checkbox" name="allow_h2o" value="Y" {{ $allow_h2o == 'Y' ? 'checked' : '' }}/> H2O
                            &nbsp;<input type="checkbox" name="allow_liberty" value="Y" {{ $allow_liberty == 'Y' ? 'checked' : '' }}/> Liberty
                            &nbsp;<input type="checkbox" name="allow_lyca" value="Y" {{ $allow_lyca == 'Y' ? 'checked' : '' }}/> Lyca
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Min Month</label>
                        <div class="col-md-8">
                            <label class="col-md-2 control-label">Boom</label>
                            <input type="text" class="col-md-1 form-control" name="m_boom" value="{{ $m_boom }}" style="width: 30px;"/>
                            <label class="col-md-2 control-label">FreeUP</label>
                            <input type="text" class="col-md-1 form-control" name="m_freeup" value="{{ $m_freeup }}" style="width: 30px;"/>
                            <label class="col-md-2 control-label">Gen</label>
                            <input type="text" class="col-md-1 form-control" name="m_gen" value="{{ $m_gen }}" style="width: 30px;"/>
                            <br>
                            <label class="col-md-2 control-label">H2O</label>
                            <input type="text" class="col-md-1 form-control" name="m_h2o" value="{{ $m_h2o }}" style="width: 30px;"/>
                            <label class="col-md-2 control-label">Liberty</label>
                            <input type="text" class="col-md-1 form-control" name="m_liberty" value="{{ $m_liberty }}" style="width: 30px;"/>
                            <label class="col-md-2 control-label">Lyca</label>
                            <input type="text" class="col-md-1 form-control" name="m_lyca" value="{{ $m_lyca }}" style="width: 30px;"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Hold Spiff</label>
                        <div class="col-md-8">
                            <input type="checkbox" name="hs_boom" value="Y" {{ $hs_boom == 'Y' ? 'checked' : '' }}/> Boom
                            &nbsp;<input type="checkbox" name="hs_freeup" value="Y" {{ $hs_freeup == 'Y' ? 'checked' : '' }}/> FreeUP
                            &nbsp;<input type="checkbox" name="hs_gen" value="Y" {{ $hs_gen == 'Y' ? 'checked' : '' }}/> GEN
                            &nbsp;<input type="checkbox" name="hs_h2o" value="Y" {{ $hs_h2o == 'Y' ? 'checked' : '' }}/> H2O
                            &nbsp;<input type="checkbox" name="hs_liberty" value="Y" {{ $hs_liberty == 'Y' ? 'checked' : '' }}/> Liberty
                            &nbsp;<input type="checkbox" name="hs_lyca" value="Y" {{ $hs_lyca == 'Y' ? 'checked' : '' }}/> Lyca
                        </div>
                    </div>
                </div>

            </div>

            <div class="row">
                <div class="col-md-4">
                </div>
                <div class="col-md-8">
                    <div class="form-group">
                        <div class="col-md-8 col-md-offset-4 text-right">
                            <button type="button" class="btn btn-info btn-sm" onclick="refresh_all()">Refresh All</button>
                            <button class="btn btn-primary btn-sm" id="btn_search">Search</button>
                            <button type="button" class="btn btn-info btn-sm" onclick="excel_export()">Download</button>
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
    <p style="text-align: right;">M.M : Minimum Months,  H.S : Hold Spiff</p>
    <table class="tree table table-bordered table-hover table-condensed filter">
        <thead>
        <tr>
            <th style="text-align: center">Parent</th>
            <th style="text-align: center">Account</th>
            <th style="text-align: center">Spiff.Template</th>
            <th style="text-align: center">Spiff Template</th>
            <th style="text-align: center">Scheduling</th>
            <th colspan="3" style="text-align: center">BOOM</th>
            <th colspan="3" style="text-align: center">FREEUP</th>
            <th colspan="3" style="text-align: center">GEN</th>
            <th colspan="3" style="text-align: center">H2O</th>
            <th colspan="3" style="text-align: center">LIBERTY</th>
            <th colspan="3" style="text-align: center">LYCA</th>
        </tr>
        <tr>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
            <th>Allow</th>
            <th>M.M</th>
            <th>H.S</th>
            <th>Allow</th>
            <th>M.M</th>
            <th>H.S</th>
            <th>Allow</th>
            <th>M.M</th>
            <th>H.S</th>
            <th>Allow</th>
            <th>M.M</th>
            <th>H.S</th>
            <th>Allow</th>
            <th>M.M</th>
            <th>H.S</th>
            <th>Allow</th>
            <th>M.M</th>
            <th>H.S</th>
        </tr>
        </thead>
        <tbody>
        @if (isset($accounts) && count($accounts) > 0)
            @foreach ($accounts as $o)
                <tr onclick="account_selected('{{ $o->id }}', true)" class="treegrid-{{ $o->id }} treegrid-parent-{{ Auth::user()->account_id == $o->id ? '' : Helper::get_parent_id_in_collection($accounts, $o->parent_id) }}">
                    <td style="display:none">
                        <input type="checkbox" style="margin-left:5px; margin-top: 0px;" onclick="account_selected('{{ $o->id }}', false)" name="cb_select" id="{{ $o->id }}"/>
                    </td>
                    <td>
                        {!! Helper::get_parent_name_html($o->id) !!}
                    </td>
                    <td>
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
                    @if(Auth::user()->account_type == 'L' && (getenv('APP_ENV') == 'production' && in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']) || getenv('APP_ENV') == 'local'))
                    <td>
                        @if ($o->type == 'M' || $o->type == 'D')
                            <button type="button" class="btn btn-info btn-xs" onclick="show_spiff_template({{ $o->id}}, '{{ $o->type }}')">Spiff.Temp</button>
                        @endif
                    </td>
                        <td style="text-align: center">
                            @if($o->template)
                                {{ $o->template }} ({{ $o->spiff_template }})
                            @endif
                        </td>
                        <td>
                            @if ($o->type == 'S')
                                <button type="button" class="btn btn-info btn-xs" onclick="show_authority({{ $o->id }})">Scheduling</button>
                            @endif
                        </td>
                        <td style="text-align: center">{{ $o->act_boom }}</td>
                        <td style="text-align: center">{{ $o->boom_min_month }}</td>
                        <td style="text-align: center">{{ $o->boom_hold_spiff }}</td>
                        <td style="text-align: center">{{ $o->act_freeup }}</td>
                        <td style="text-align: center">{{ $o->freeup_min_month }}</td>
                        <td style="text-align: center">{{ $o->freeup_hold_spiff }}</td>
                        <td style="text-align: center">{{ $o->act_gen }}</td>
                        <td style="text-align: center">{{ $o->gen_min_month }}</td>
                        <td style="text-align: center">{{ $o->gen_hold_spiff }}</td>
                        <td style="text-align: center">{{ $o->act_h2o }}</td>
                        <td style="text-align: center">{{ $o->h2o_min_month }}</td>
                        <td style="text-align: center">{{ $o->h2o_hold_spiff }}</td>
                        <td style="text-align: center">{{ $o->act_liberty }}</td>
                        <td style="text-align: center">{{ $o->liberty_min_month }}</td>
                        <td style="text-align: center">{{ $o->liberty_hold_spiff }}</td>
                        <td style="text-align: center">{{ $o->act_lyca }}</td>
                        <td style="text-align: center">{{ $o->lyca_min_month }}</td>
                        <td style="text-align: center">{{ $o->lyca_hold_spiff }}</td>
                    @endif
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
        <div class="col-md-2">
            Total {{ $accounts->total() }} record(s).
        </div>
        <div class="col-md-10  text-right">
            {{ $accounts->appends(Request::except('page'))->links() }}
        </div>
    </div>

    @if (Auth::user()->account_type == 'L')
    <div class="modal" id="div_spiff_template" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false"
         aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Spiff Template</h4>
                </div>
                <div class="modal-body" style="min-height:400px;">
                    <input type="hidden" id="st_account_id">

                    <div id="dist_spiff_templates_boby">
                        <h5>Templates for Distributor</h5>
                        <div class="row">
                            @foreach ($d_spiff_templates as $d)
                            <div class="col-md-3 col-sm-4 col-xs-6">
                                <label class="checkbox-inline">
                                    <input type="checkbox" id="spiff_template_id_{{ $d->id }}"
                                           onclick="onclick_spiff_template({{ $d->id }})"/> {{ $d->template }}
                                </label>
                                <br>
                                <small><span id="spiff_template_msg_{{ $d->id }}"></span></small>
                            </div>
                            @endforeach
                        </div>

                        <div class="row">
                            <label class="checkbox-inline">
                                Updates all distributor with new spiff template
                            </label>
                            <div class="col-md-8">

                                <select class="form-control" style="width:150px; float:left;" name="dis_temp_from" id="dis_temp_from">
                                    @foreach ($d_spiff_templates as $d)
                                        <option value="{{$d->id}}" id="dis_spiff_temp_from_{{$d->id}}">{{ $d->template }}</option>
                                    @endforeach
                                </select>
                                <span class="glyphicon glyphicon-arrow-right"
                                      style="margin-left:5px; margin-top:5px; float:left;"></span>
                                <select class="form-control" style="width:150px; margin-left: 5px; float:left;" name="dis_temp_to" id="dis_temp_to">
                                    @foreach ($d_spiff_templates as $d)
                                        <option value="{{$d->id}}" id="dis_spiff_temp_to_{{$d->id}}">{{ $d->template }}</option>
                                    @endforeach
                                </select>
                                <span> &nbsp; </span>
                                <button class="btn btn-primary btn-sm" id="btn_search" onclick="update_dis_temps()">Update</button>
                            </div>
                        </div>
                        <hr>
                    </div>


                    <div id="suba_spiff_templates_boby">
                        <h5>Templates for Sub-Agent</h5>
                        <div class="row">
                            @foreach ($s_spiff_templates as $d)
                            <div class="col-md-3 col-sm-4 col-xs-6">
                                <label class="checkbox-inline">
                                    <input type="checkbox" id="spiff_template_id_{{ $d->id }}"
                                           onclick="onclick_spiff_template({{ $d->id }})"/> {{ $d->template }}
                                </label>
                                <br>
                                <small><span id="spiff_template_msg_{{ $d->id }}"></span></small>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="row">
                        <label class="checkbox-inline">
                            Updates all Sub-Agent with new spiff template
                        </label>
                        <div class="col-md-8">
                            <select class="form-control" style="width:150px; float:left;" name="sub_temp_from" id="sub_temp_from">
                                @foreach ($s_spiff_templates as $d)
                                    <option value="{{$d->id}}" id="sub_spiff_temp_from_{{$d->id}}">{{ $d->template }}</option>
                                @endforeach
                            </select>
                            <span class="glyphicon glyphicon-arrow-right"
                                  style="margin-left:5px; margin-top:5px; float:left;"></span>
                            <select class="form-control" style="width:150px; margin-left: 5px; float:left;" name="sub_temp_to" id="sub_temp_to">
                                @foreach ($s_spiff_templates as $d)
                                    <option value="{{$d->id}}" id="sub_spiff_temp_to_{{$d->id}}">{{ $d->template }}</option>
                                @endforeach
                            </select>
                            <span> &nbsp; </span>
                            <button class="btn btn-primary btn-sm" id="btn_search" onclick="update_sub_temps()">Update</button>
                        </div>
                    </div>

                    <hr>
                    <div id="suba_spiff_templates_boby">
                        <h5>Activation Products Under This Account</h5>
                        <select class="form-control" style="width:150px; margin-left: 5px; float:left;" name="act_product" id="act_product">
                            <option value="">Prodcut</option>
                            @foreach($carriers as $c)
                                <option value="{{$c->name}}">{{$c->name}}</option>
                            @endforeach
                        </select>
                        <span> &nbsp; &nbsp; &nbsp; &nbsp; </span>
                        <button class="btn btn-primary btn-sm" id="btn_search" onclick="all_active()">All Active</button>
                        <span> &nbsp; &nbsp; </span>
                        <button class="btn btn-primary btn-sm" id="btn_search" onclick="all_inactive()">All Inactive</button>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="div_account_authority" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Account Authority</h4>
                </div>
                <div class="modal-body" style="min-height:400px;">

                    <form id="frm_account_auth" class="form-horizontal filter" method="post">
                        {{ csrf_field() }}

                        <input type="hidden" id="auth_account_id">

                        <table class="tree table table-bordered table-hover table-condensed filter">
                            <thead>
                            <tr>
                                <th rowspan="2"></th>
                                <th rowspan="2">Auth</th>
                                <th rowspan="2">Default.Fee</th>
                                <th rowspan="2">Over.Ride</th>
                                <th colspan="3">By Account</th>
                                <th colspan="3">Max QTY</th>
                            </tr>
                            <tr>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Fee</th>
                                <th>Daily</th>
                                <th>Weekly</th>
                                <th>Monthly</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>Batch RTR</td>
                                <td style="text-align:center;"><input type="checkbox" name="auth_batch_rtr"
                                                                      id="auth_batch_rtr"
                                                                      value="Y" {{ Auth::user()->account_type == 'L' ? '' : 'disabled' }}/></td>
                                <td style="text-align: right">${{ $attbatchfeebase->for_rtr }}</td>
                                <td>
                                    <select id="for_rtr_fee_orverride">
                                        <option value="">Not Selected</option>
                                        @foreach($attbatchfeetiers as $tier)
                                            <option value="{{ $tier->id }}">{{ $tier->name }}, ${{ $tier->for_rtr
                                            }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="date" id="for_rtr_sdate" class="form-control" style="width:160px;"></td>
                                <td><input type="date" id="for_rtr_edate" class="form-control" style="width:160px;"></td>
                                <td><input type="text" id="for_rtr_fee" class="form-control" style="width:60px;
"></td>

                                <td><input type="text" id="for_rtr_daily" class="form-control"></td>
                                <td><input type="text" id="for_rtr_weekly" class="form-control"></td>
                                <td><input type="text" id="for_rtr_monthly" class="form-control"></td>
                            </tr>
                            <tr>
                                <td>Batch SIM SWAP</td>
                                <td  style="text-align:center;"><input type="checkbox" name="auth_batch_sim_swap" id="auth_batch_sim_swap"
                                                                       value="Y" {{ Auth::user()->account_type == 'L' ? '' : 'disabled' }}/></td>
                                <td style="text-align: right">${{ $attbatchfeebase->for_sim_swap }}</td>
                                <td>
                                    <select id="for_sim_swap_fee_orverride">
                                        <option value="">Not Selected</option>
                                        @foreach($attbatchfeetiers as $tier)
                                            <option value="{{ $tier->id }}">{{ $tier->name }}, ${{ $tier->for_sim_swap
                                            }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="date" id="for_sim_swap_sdate" class="form-control"
                                           style="width:160px;"></td>
                                <td><input type="date" id="for_sim_swap_edate" class="form-control"
                                           style="width:160px;"></td>
                                <td><input type="text" id="for_sim_swap_fee" class="form-control"
                                           style="width:60px;"></td>

                                <td><input type="text" id="for_sim_swap_daily" class="form-control"></td>
                                <td><input type="text" id="for_sim_swap_weekly" class="form-control"></td>
                                <td><input type="text" id="for_sim_swap_monthly" class="form-control"></td>
                            </tr>
                            <tr>
                                <td>Batch Plan Change</td>
                                <td  style="text-align:center;"><input type="checkbox" name="auth_batch_plan_change"
                                                                       id="auth_batch_plan_change" value="Y" {{ Auth::user()->account_type == 'L' ? '' : 'disabled' }}/></td>
                                <td style="text-align: right">${{ $attbatchfeebase->for_plan_change }}</td>
                                <td>
                                    <select id="for_plan_change_fee_orverride">
                                        <option value="">Not Selected</option>
                                        @foreach($attbatchfeetiers as $tier)
                                            <option value="{{ $tier->id }}">{{ $tier->name }}, ${{ $tier->for_plan_change
                                            }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="date" id="for_plan_change_sdate" class="form-control"
                                           style="width:160px;"></td>
                                <td><input type="date" id="for_plan_change_edate" class="form-control"
                                           style="width:160px;"></td>
                                <td><input type="text" id="for_plan_change_fee" class="form-control"
                                           style="width:60px;"></td>

                                <td><input type="text" id="for_plan_change_daily" class="form-control"></td>
                                <td><input type="text" id="for_plan_change_weekly" class="form-control"></td>
                                <td><input type="text" id="for_plan_change_monthly" class="form-control"></td>
                            </tr>
                            </tbody>
                        </table>
                    </form>

                    <button type="button" class="btn btn-primary btn-sm" onclick="save_authority()">Save</button>
                </div>
            </div>
        </div>
    </div>
    @endif


    <div class="modal" id="div_spiff_template_detail" tabindex="-1" role="dialog" data-backgrop="status"
         data-keyboard="false" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="div_spiff_template_detail_title">Spiff Detail</h4>
                </div>
                <div class="modal-body"id="div_spiff_template_detail_body" >

                </div>
            </div>
        </div>
    </div>

    <div style="display:none">
        <iframe name="ifm_upload"></iframe>
    </div>

@stop
