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
            window.location.href = '/admin/account';
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
                url: '/admin/account/spiff_template',
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

                    if (res.code != '0') {
                        $checked = $('#spiff_template_id_' + template_id).prop('checked');
                        if ($checked) {
                            $('#spiff_template_id_' + template_id).prop('checked', false);
                        } else {
                            $('#spiff_template_id_' + template_id).prop('checked', true);
                        }
                    }
                    $('#spiff_template_msg_' + template_id).text(res.msg);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });
        }

        function show_payments(account_id) {
            if (typeof account_id === 'undefined') {
                account_id = current_account_id;
            }

            myApp.showLoading();
            $.ajax({
                url: '/admin/account/payment/list',
                data: {
                    _token: '{{ csrf_token() }}',
                    account_id: account_id,
                    type: $('#pm_type').val(),
                    method: $('#pm_method').val(),
                    sdate: $('#pm_sdate').val(),
                    edate: $('#pm_edate').val(),
                    comments: $('#pm_comments').val(),
                    paypal_id: $('#pm_paypal_id').val(),
                    invoice_id: $('#pm_invoice_id').val()
                },
                cache : false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();

                    if ($.trim(res.msg) === '') {

                        $('#pm_account_id').html(account_id + ' <span style="font-size:13px; color: blue;">( current balance : $' + parseFloat(res.balance).toFixed(2) + ')</span>');

                        var tbody = $('#tbl_payments').find('tbody');
                        tbody.empty();

                        if (res.payments.length > 0) {
                            $.each (res.payments, function(i, o) {

                                var html = '<tr>';
                                html += '<td>' + o.id + '</td>';
                                html += '<td>' + o.type_name + '</td>';
                                html += '<td>' + o.method_name + '</td>';
                                html += '<td>' + o.category + '</td>';
                                html += '<td>$' + parseFloat(o.deposit_amt).toFixed(2) + '</td>';
                                html += '<td>$' + parseFloat(o.fee).toFixed(2) + '</td>';
                                html += '<td>$' + parseFloat(o.amt).toFixed(2) + '</td>';
                                html += '<td>' + o.paypal_txn_id + '</td>';
                                html += '<td>' + o.invoice_number + '</td>';
                                html += '<td>' + o.comments + '</td>';
                                html += '<td>' + o.cdate + '</td>';
                                html += '<td>' + o.created_by + '</td>';
                                html += '</tr>';

                                tbody.append(html);
                            });
                        } else {
                            var html = '<tr><td colspan="20">No Record Found</td></tr>';
                            tbody.append(html);
                        }

                        $('#div_payments').modal({
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

        function show_credit_detail() {
            $('#dcd_amt').val('');
            $('#dcd_comments').val('');
            $('#dcd_account').val(current_account_id);
            $('#div_credit_detail').modal();
        }

        function save_credit_detail() {
            myApp.showLoading();

            $.ajax({
                url: '/admin/account/credit/add',
                data: {
                    _token: '{{ csrf_token() }}',
                    account_id: current_account_id,
                    type: $('#dcd_type').val(),
                    amt: $('#dcd_amt').val(),
                    comments: $('#dcd_comments').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        $('#div_credit_detail').modal('hide');
                        myApp.showSuccess('Your request has been processed successfully!');
                        show_credit(current_account_id);
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

        function edit_comments(id) {
            $('#v_comments_' + id).attr('disabled', false);
            $('#v_paid_memo_' + id).attr('disabled', false);

            old_v_comments = $('#v_comments_' + id).val();
            old_v_paid_memo = $('#v_paid_memo_' + id).val();

            $('#btn_comments_edit_' + id).hide();
            $('#btn_comments_update_' + id).show();
            $('#btn_comments_cancel_' + id).show();
        }

        function cancel_comments(id) {
            $('#v_comments_' + id).attr('disabled', true);
            $('#v_paid_memo_' + id).attr('disabled', true);

            $('#v_comments_' + id).val(old_v_comments);
            $('#v_paid_memo_' + id).val(old_v_paid_memo);

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
                    paid_memo: $('#v_paid_memo_' + id).val()
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
                    paid_memo: $('#vcb_paid_memo').val()
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
                                html += '<td>' + o.id + '</td>';
                                html += '<td style="color:' + color + '">' + o.type_name + '</td>';
                                html += '<td style="color:' + color + '">$' + parseFloat(o.amt).toFixed(2) + '</td>';
                                html += '<td>' + o.do_ach + '</td>';
                                html += '<td><textarea disabled name="v_comments_'+o.id+'" id="v_comments_'+o.id+'"> ' + o.comments + '</textarea></td>';
                                html += '<td><textarea disabled name="v_paid_memo_'+o.id+'" id="v_paid_memo_'+o.id+'"> ' + o.paid_memo + '</textarea></td>';
                                html += '<td>' + o.cdate + '</td>';
                                html += '<td>' + o.created_by + '</td>';
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

        function show_payment_detail(str) {
            $('#dpd_deposit_amt').val('');
            $('#dpd_fee').val('');
            $('#dpd_amt').val('');
            $('#dpd_comments').val('');

            $('#dpd_account').val(current_account_id);

            // default fee $1 for cash-pickup
            if(str == 'pickup') {
                $('#dpd_fee').val(parseFloat('1'));
            }
            $('#div_payment_detail').modal();
        }

        function save_payment_detail() {
            myApp.showLoading();

            $.ajax({
                url: '/admin/account/payment/add',
                data: {
                    _token: '{{ csrf_token() }}',
                    account_id: current_account_id,
                    method: $('#dpd_method').val(),
                    category: $('#dpd_category').val(),
                    deposit_amt: $('#dpd_deposit_amt').val(),
                    fee: $('#dpd_fee').val(),
                    amt: $('#dpd_amt').val(),
                    comments: $('#dpd_comments').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        $('#div_payment_detail').modal('hide');
                        myApp.showSuccess('Your request has been processed successfully!');
                        show_payments(current_account_id);
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

        function calc_payment_fee() {
            var amt = $.trim($('#dpd_amt').val());
            if (amt === '') {
                amt = 0;
            }

            try {
                amt = parseFloat(amt);
            } catch(e) {
                amt = 0;
                $('#dpd_amt').val(0);
            }

            var fee = $.trim($('#dpd_fee').val());
            if (fee === '') {
                fee = 0;
            }
            try {
                fee = parseFloat(fee);
            } catch (e) {
                fee = 0;
                $('#dpd_fee').val(0)
            }

            if (fee > amt) {
                myApp.showError('Fee is greater than deposit amount');
                $('#dpd_amt').val(0);
                $('#dpd_fee').val(0);
                $('#dpd_deposit_amt').val(0);
                return;
            }

            var deposit = amt + fee;
            $('#dpd_deposit_amt').val(deposit.toFixed(2));

        }

        function show_vr(account_id) {
            myApp.showLoading();

            @foreach ($vr_carriers as $d)
                $('#vr_carrier_{{ $d->carrier_key }}').prop('checked', false);
                $('#vr_carrier_msg_{{ $d->carrier_key }}').text('');
            @endforeach

            $('#vr_account_id').val(account_id);

            $.ajax({
                url: '/admin/account/vr',
                data: {
                    account_id: account_id
                },
                cache: false,
                type: 'get',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();

                    if (res.vras.length > 0) {
                        $.each (res.vras, function(i, o) {

                            $('#vr_carrier_' + o.carrier_key).prop('checked', true);
                        });

                    }

                    $('#div_vr').modal();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            })
        }

        function show_vr_product(account_id) {
            myApp.showLoading();

            @foreach ($vr_products as $d)
                $('#vr_product_{{ $d->id }}').prop('checked', false);
                $('#vr_product_msg_{{ $d->id }}').text('');
            @endforeach

            $('#vr_account_id').val(account_id);

            $.ajax({
                url: '/admin/account/vr_product',
                data: {
                    account_id: account_id
                },
                cache: false,
                type: 'get',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();

                    if (res.vras.length > 0) {
                        $.each (res.vras, function(i, o) {

                            $('#vr_product_' + o.vr_product_id).prop('checked', true);
                        });

                    }

                    $('#div_vr').modal();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            })
        }

        function vr_save(carrier_key, carrier) {
            myApp.showLoading();

            $.ajax({
                url: '/admin/account/vr/save',
                data: {
                    _token: '{{ csrf_token() }}',
                    account_id: $('#vr_account_id').val(),
                    carrier: carrier
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    $('#vr_carrier_msg_' + carrier_key).text(res.msg);

                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            })
        }

        function vr_product_save(id, model) {
            myApp.showLoading();

            $.ajax({
                url: '/admin/account/vr_product/save',
                data: {
                    _token: '{{ csrf_token() }}',
                    account_id: $('#vr_account_id').val(),
                    id: id,
                    model: model
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    $('#vr_product_msg_' + id).text(res.msg);

                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            })
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

    </script>

    <h4>Account List</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form class="form-horizontal" id="frm_search" name="frm_search" method="post" action="/admin/account">
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
                @if (Auth::user()->account_type == 'L' && in_array(Auth::user()->user_id, ['admin', 'thomas', 'system']))
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
                        <label class="col-md-4 control-label">Emails</label>
                        <div class="col-md-4">
                            <textarea class="form-control" name="emails" rows="3">{{ $emails }}</textarea>
                        </div>
                        <div class="col-md-4">
                            <input type="checkbox" name="emails_except" value="Y" {{ $emails_except == 'Y' ? 'checked' : '' }}/> Except Them
                        </div>
                    </div>
                </div>
                @endif
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">User ID</label>
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="user_id" value="{{ $user_id }}"/>
                        </div>
                        <div class="col-md-4">
                            <input type="checkbox" name="user_not_created" value="Y" {{ $user_not_created == 'Y' ? 'checked' : '' }}/> User Not Created
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Office Number</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="office_number" value="{{ $office_number }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Tax ID</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="tax_id" value="{{ $tax_id }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">User Full Name</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="user_name" value="{{ $user_name }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Store Type</label>
                        <div class="col-md-8 text-left">
                            @foreach ($store_types as $o)
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="store_types[]" {{ in_array($o->id, old('store_types', $store_type_ids)) ? 'checked' : ''  }} value="{{ $o->id }}"/> {{ $o->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Address 1</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="address1" value="{{ $address1 }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Address 2</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="address2" value="{{ $address2 }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">City</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="city" value="{{ $city }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">State</label>
                        <div class="col-md-8">
                            <select name="state" class="form-control">
                                <option value="">All</option>
                                @foreach ($states as $o)
                                    <option value="{{ $o['code'] }}" {{ $o['code'] == $state ? 'selected' : ''}}>{{ $o['name'] }}</option>
                                @endforeach

                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Zip</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="zip" value="{{ $zip }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Dealer Code</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="dealer_code" value="{{ $dealer_code }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Rate Plan ID</label>
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="rate_plan_id" value="{{ $rate_plan_id }}"/>
                        </div>
                        <div class="col-md-4">
                            <input type="checkbox" name="rate_plan_not_assigned" value="Y" {{ $rate_plan_not_assigned == 'Y' ? 'checked' : '' }}/> Rate Plan Not Assigned Yet!
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">eCommerce ?</label>
                        <div class="col-md-2">
                            <input type="checkbox" name="is_c_store" value="Y" {{ $is_c_store == 'Y' ? 'checked' : '' }}/>
                        </div>
                        <label class="col-md-4 control-label">No Rebates Eligibility</label>
                        <div class="col-md-2">
                            <input type="checkbox" name="is_rebates_eligibility" value="N" {{ $is_rebates_eligibility == 'N' ? 'checked' : '' }}/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Pay.Method</label>
                        <div class="col-md-8">
                            <select class="form-control" name="pay_method">
                                <option value="">All</option>
                                <option value="P" {{ $pay_method == 'P' ? 'selected' : '' }}>Prepay</option>
                                <option value="C" {{ $pay_method == 'C' ? 'selected' : '' }}>Credit</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Show Discount Setup Report ?</label>
                        <div class="col-md-2">
                            <input type="checkbox" name="is_d_s_report" value="Y" {{ $is_d_s_report == 'Y' ? 'checked' : '' }}/>
                        </div>
                        <label class="col-md-4 control-label">Show Spiff Setup Report ?</label>
                        <div class="col-md-2">
                            <input type="checkbox" name="is_s_s_report" value="N" {{ $is_s_s_report == 'N' ? 'checked' : '' }}/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">ACH.Route.#</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="ach_routeno" value="{{ $ach_routeno }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">ACH.Account.#</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="ach_acctno" value="{{ $ach_acctno }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">No.Bank.Info?</label>
                        <div class="col-md-2">
                            <input type="checkbox" name="no_bank_info" value="Y" {{ $no_bank_info == 'Y' ? 'checked' : '' }}/>
                        </div>
                        <label class="col-md-4 control-label">Yes.Bank.Info?</label>
                        <div class="col-md-2">
                            <input type="checkbox" name="yes_bank_info" value="Y" {{ $yes_bank_info == 'Y' ? 'checked' : '' }}/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">ATT.TID</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="att_tid" value="{{ $att_tid }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">ATT.IDs</label>
                        <div class="col-md-8">
                            <textarea class="form-control" name="att_ids" rows="3">{{ $att_ids }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">No.ACH.Carry.Over</label>
                        <div class="col-md-2">
                            <input type="checkbox" name="no_ach" value="Y" {{ $no_ach == 'Y' ? 'checked' : '' }}/>
                        </div>
                    </div>
                </div>

                @if(Auth::user()->account_type == 'L')

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">No.ATT.BYOS</label>
                        <div class="col-md-8">
                            <input type="checkbox" name="no_att_byos" value="Y" {{ $no_att_byos == 'Y' ? 'checked' : '' }}/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">From Become A Dealer</label>
                        <div class="col-md-8">
                            <input type="checkbox" name="wait_for_approve" value="Y" {{ $wait_for_approve == 'Y' ?
                            'checked' : '' }}/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Notes</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="notes" value="{{ $notes }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Credit Limit</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="credit_limit" value="{{ $credit_limit }}"/>
                        </div>
                    </div>
                </div>

                @endif
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Sorting By</label>
                        <div class="col-md-8">
                            <select class="form-control" name="order_by">
                                <option value="path asc" {{ $order_by == 'path asc' ? 'selected' : ''
                                }}>Account ID</option>
                                <option value="cdate asc" {{ $order_by == 'cdate asc' ? 'selected' : ''
                                }}>Created date Ascending</option>
                                <option value="cdate desc" {{ $order_by == 'cdate desc' ? 'selected' : ''
                                }}>Created date Descending</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Created Date</label>
                        <div class="col-md-8">
                            <input type="text" style="width:100px; float:left;" class="form-control" id="created_sdate"
                                   name="created_sdate" value="{{ old('created_sdate', $created_sdate) }}"/>
                            <span class="control-label" style="margin-left:5px; float:left;"> ~ </span>
                            <input type="text" style="width:100px; margin-left: 5px; float:left;" class="form-control" id="created_edate"
                                   name="created_edate" value="{{ old('created_edate', $created_edate) }}"/>
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
                        <label class="col-md-4 control-label">ATT.Dealer.Codes</label>
                        <div class="col-md-4">
                            <textarea class="form-control" name="codes" rows="3">{{ $codes }}</textarea>
                        </div>

                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">ATT.Dealer.Notes</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="code_like" value="{{ $code_like }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">ATT.Dealer.Codes</label>
                        <div class="col-md-2">
                            <input type="checkbox" name="is_code" value="Y" {{ $is_code == 'Y' ? 'checked' : '' }}/>
                        </div>
                        <label class="col-md-4 control-label">No.ATT.Dealer.Codes</label>
                        <div class="col-md-2">
                            <input type="checkbox" name="no_att_code" value="Y" {{ $no_att_code == 'Y' ? 'checked' : '' }}/>
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
                            @if (Auth::user()->account_type == 'L' && in_array(Auth::user()->user_id, ['admin', 'thomas', 'system']))
                            <button class="btn btn-primary btn-sm" type="button" onclick="excel_download()">DOWNLOAD</button>

                            <a type="button" class="btn btn-primary btn-xs" href="/admin/account/lookup_new"
                               target="_blank">Batch Lookup</a>
{{--                         Hidden spiff lookup button (no more using vw_account_spiff stuff 9/30/2020)--}}
{{--                                <a type="button" class="btn btn-primary btn-xs" href="/admin/account/spiff"--}}
{{--                                   target="_blank">Spiff Lookup</a>--}}
                            @endif
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
            <th>Email</th>
            <th>Email2</th>
            <th>Status</th>
            <th>Users</th>
            @if (empty($credit_limit) || $credit_limit !== 'N')
                <th>Credit Limit</th>
            @endif
            <th>Type</th>
            <th>Rate.Plan</th>
            <th>Add Sub-Account</th>
            @if(Auth::user()->account_type == 'L' && (getenv('APP_ENV') == 'production' && in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']) || getenv('APP_ENV') == 'local'))
            <th>Spiff.Template</th>
{{--            <th>Scheduling</th>--}}
            <th>Payment</th>
            <th>Credit</th>
{{--            <th>V.C.B</th>--}}
                @elseif( (Auth::user()->account_type == 'L' || Auth::user()->account_type == 'M' || Auth::user()->account_type == 'D') && ($allow_cash_limit > 0))
                <th>Payment</th>
            @endif
{{--            @if(Auth::user()->account_type == 'L')--}}
{{--            <th>VR</th>--}}
{{--            @endif--}}
            <th>ATT</th>
            <th>ATT.Code</th>
            <th>ATT.Dealer Notes</th>
            <th>State</th>
            <th>Office Number</th>
            <th>Tax ID</th>
            @if(Auth::user()->account_type == 'L' && (empty($notes) || $notes == 'Y'))
            <th>Notes</th>
            @endif
            <th>Created.At</th>
        </tr>
        </thead>
        <tbody>
        @if (isset($accounts) && count($accounts) > 0)
            @foreach ($accounts as $o)
                <tr onclick="account_selected('{{ $o->id }}', true)" class="treegrid-{{ $o->id }} treegrid-parent-{{ Auth::user()->account_id == $o->id ? '' : Helper::get_parent_id_in_collection($accounts, $o->parent_id) }}">
                    <td style="display:none">

                        <input type="checkbox" style="margin-left:5px; margin-top: 0px;" onclick="account_selected('{{ $o->id }}', false)" name="cb_select"
                               id="{{ $o->id }}"/>
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
                    <td>{{ $o->email }}</td>
                    <td>{{ $o->email2 }}</td>
                    <td><a href="https://www.google.com/maps/place/{{ $o->address1 . " " . $o->address2 . " " . $o->city . " " . $o->state . " " . $o->zip }}" target="_blank"
                           data-toggle="tooltip" title="{{ $o->address1 . " " . $o->address2 . " " . $o->city . " " . $o->state . " " . $o->zip }}">
                            {{ $o->status_name() }}
                        </a>
                    </td>
                    <td>
                        <a href="javascript:show_user_list()">Users</a>
                        {{ \App\Model\Account::getUserCountByAccount($o->id) }}
                    </td>
                    @if (empty($credit_limit) || $credit_limit !== 'N')
                        <td>{{ $o->credit_limit }}</td>
                    @endif
                    <td>
                        <a href="https://maps.apple.com/?address={{ $o->address1 . " " . $o->address2 . " " . $o->city . " " . $o->state . " " . $o->zip }}" target="_blank">
                            {{ $o->type_name() }}
                        </a>
                    </td>
                    <td>
                        @if ($o->rate_plan_id != '')
                            <a href="javascript:show_rate_detail('{{ $o->rate_plan_id }}', 'M')">{{ $o->rate_plan_name . ' ( ' . $o->rate_plan_id . ' )' }}</a>
                        @else
                            Not Assigned Yet!
                        @endif
                    </td>
                    <td>
                        @if ($o->type != 'S')
                            <a href="/admin/account/add_new/{{ $o->id }}" target="_blank">Add Sub-Account</a>
                        @endif
                    </td>
                    @if(Auth::user()->account_type == 'L' && (getenv('APP_ENV') == 'production' && in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']) || getenv('APP_ENV') == 'local'))
                    <td>
                        @if ($o->type == 'M' || $o->type == 'D')
                            <button type="button" class="btn btn-info btn-xs" onclick="show_spiff_template({{ $o->id
                            }}, '{{ $o->type }}')
                                    ">Spiff.Temp</button>
                        @endif
                    </td>
{{--                    <td>--}}
{{--                        @if ($o->type == 'S')--}}
{{--                            <button type="button" class="btn btn-info btn-xs" onclick="show_authority({{ $o->id }})">Scheduling</button>--}}
{{--                        @endif--}}
{{--                    </td>--}}
                    <td>
                        @if ($o->type != 'L')
                            <button type="button" class="btn btn-info btn-xs" onclick="show_payments({{ $o->id }})">Payment</button>
                        @endif
                    </td>
                    <td>
                        @if ($o->type != 'L')
                            <button type="button" class="btn btn-info btn-xs" onclick="show_credit({{ $o->id }})">Credit</button>
                        @endif
                    </td>
{{--                    <td>--}}
{{--                        @if ($o->type == 'M' || $o->type == 'D')--}}
{{--                            <button type="button" class="btn btn-info btn-xs" onclick="show_vcb({{ $o->id }})">V.C.B</button>--}}
{{--                        @endif--}}
{{--                    </td>--}}
                        @elseif( (Auth::user()->account_type == 'L' || Auth::user()->account_type == 'M' || Auth::user()->account_type == 'D') && $allow_cash_limit > 0)
                        <td>
                            @if(Auth::user()->account_type == 'M')
                                @if ($o->type != 'M' && $o->type != 'D')
                                    <button type="button" class="btn btn-info btn-xs" onclick="show_payments({{ $o->id }})">Payment</button>
                                @endif
                            @elseif(Auth::user()->account_type == 'D')
                                @if($o->type != 'D')
                                    <button type="button" class="btn btn-info btn-xs" onclick="show_payments({{ $o->id }})">Payment</button>
                                @endif
                            @endif
                        </td>
                    @endif
{{--                    @if(Auth::user()->account_type == 'L')--}}
{{--                        <td>--}}
{{--                            @if ($o->type == 'M')--}}
{{--                                <button type="button" class="btn btn-info btn-xs" onclick="show_vr_product({{ $o->id }})">VR</button>--}}
{{--                            @endif--}}
{{--                        </td>--}}
{{--                    @endif--}}
                    @if($o->att_tid || $o->att_tid2)
                        <td>A</td>
                    @else
                        <td></td>
                    @endif
                    <td>{{ $o->att_dealer_code }}</td>
                    <td>{{ $o->att_dc_notes }}</td>
                    <td>{{ $o->state_name }}</td>
                    <td>{{ $o->office_number }}</td>
                    <td>{{ $o->tax_id }}</td>
                    @if(Auth::user()->account_type == 'L' && (empty($notes) || $notes == 'Y'))
                    <td>{{ $o->notes }}</td>
                    @endif
                    <td>{{ $o->cdate }}</td>
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

                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="modal" id="div_payments" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Payments - <span id="pm_account_id"></span></h4>
                </div>
                <div class="modal-body" style="min-height:400px;">

                    <div class="well filter">
                        <form id="frm_payments" class="form-horizontal filter" method="post">
                            {{ csrf_field() }}

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Type</label>
                                        <div class="col-md-8">
                                            <select id="pm_type" class="form-control">
                                                <option value="">All</option>
                                                <option value="P">Prepay</option>
                                                <!--option value="A">Postpay</option-->
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Method</label>
                                        <div class="col-md-8">
                                            <select id="pm_method" class="form-control">
                                                <option value="">All</option>
                                                <!--option value="P">PayPal</option-->
                                                <option value="D">Direct Deposit</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Date</label>
                                        <div class="col-md-8">
                                            <input type="text" style="width:100px; float:left;" class="form-control" id="pm_sdate" value="{{ Carbon\Carbon::today()->startOfWeek()->format('Y-m-d') }}"/>
                                            <span class="control-label" style="margin-left:10px; float:left;"> ~ </span>
                                            <input type="text" style="width:100px; margin-left: 10px; float:left;" class="form-control" id="pm_edate" value="{{ Carbon\Carbon::today()->format('Y-m-d') }}"/>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Comments</label>
                                        <div class="col-md-8">
                                            <input type="text" class="form-control" id="pm_comments" value=""/>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Paypal ID</label>
                                        <div class="col-md-8">
                                            <input type="text" class="form-control" id="pm_paypal_id" value=""/>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Invoice ID</label>
                                        <div class="col-md-8">
                                            <input type="text" class="form-control" id="pm_invoice_id" value=""/>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">

                                </div>

                                <div class="col-md-6 text-right">
                                    <button type="button" class="btn btn-primary btn-sm" onclick="show_payments()">Search</button>
                                    @if (in_array(Auth::user()->account_type, ['L']) && in_array(Auth::user()->user_id, ['system', 'admin', 'thomas']))
                                        <button type="button" class="btn btn-primary btn-sm" onclick="show_payment_detail()">Add New Payment</button>
                                    @else
                                        <button type="button" class="btn btn-primary btn-sm" onclick="show_payment_detail('pickup')">Add New Payment</button>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>

                    <table class="table table-bordered table-hover table-condensed filter" id="tbl_payments">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Method</th>
                            <th>Category</th>
                            <th>Deposit($)</th>
                            <th>Fee($)</th>
                            <th>Applied($)</th>
                            <th>PayPal ID</th>
                            <th>Invoice ID</th>
                            <th>Comments</th>
{{--                            <th>Paid.Memo</th>--}}
                            <th>Created.At</th>
                            <th>Created.By</th>
                        </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
                <!--div class="modal-footer" style="margin-right:15px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div-->
            </div>
        </div>
    </div>

    <div class="modal" id="div_credit" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Credit - <span id="cd_account_id"></span></h4>
                </div>
                <div class="modal-body" style="min-height:350px;">

                    <div class="well filter">
                        <form id="frm_credit" class="form-horizontal filter" method="post">
                            {{ csrf_field() }}

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Date</label>
                                        <div class="col-md-8">
                                            <input type="text" style="width:100px; float:left;" class="form-control" id="cd_sdate" value="{{ Carbon\Carbon::today()->startOfWeek()->format('Y-m-d') }}"/>
                                            <span class="control-label" style="margin-left:10px; float:left;"> ~ </span>
                                            <input type="text" style="width:100px; margin-left: 10px; float:left;" class="form-control" id="cd_edate" value="{{ Carbon\Carbon::today()->format('Y-m-d') }}"/>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Type</label>
                                        <div class="col-md-8">
                                            <select id="cd_type" class="form-control">
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
                                            <input type="text" class="form-control" id="cd_comments"/>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Paid Memo</label>
                                        <div class="col-md-8">
                                            <input type="text" class="form-control" id="cd_paid_memo"/>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 text-right">
                                    <button type="button" class="btn btn-primary btn-sm" onclick="show_credit()">Search</button>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="show_credit_detail()">Add New Credit</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <table class="table table-bordered table-hover table-condensed filter" id="tbl_credit">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Type</th>
                                <th>Amount($)</th>
                                <th>Comments</th>
                                <th>Paid.Memo</th>
                                <th>Created.At</th>
                                <th>Created.By</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="div_vcb" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg" role="document">
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
                                            <input type="text" style="width:100px; float:left;" class="form-control" id="vcb_sdate" value="{{ Carbon\Carbon::today()->startOfWeek()->format('Y-m-d') }}"/>
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
                                <th>ID</th>
                                <th>Type</th>
                                <th>Amount($)</th>
                                <th>Do Ach</th>
                                <th>Comments</th>
                                <th>Paid.Memo</th>
                                <th>Created.At</th>
                                <th>Created.By</th>
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
                                <input id="n_vcb_amt" class="form-control"/>
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

    <div class="modal" id="div_user_list" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">User List - <span id="ul_account_id"></span></h4>
                </div>
                <div class="modal-body">

                    <div class="well filter">
                        <form id="frm_user_list" class="form-horizontal filter" method="post" target="ifm_upload">
                            {{ csrf_field() }}

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">User ID</label>
                                        <div class="col-md-8">
                                            <input type="text" class="form-control" id="ul_user_id"/>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Status</label>
                                        <div class="col-md-8">
                                            <select id="ul_status" class="form-control">
                                                <option value="">All</option>
                                                <option value="A">Active</option>
                                                <option value="H">On-Hold</option>
                                                <option value="P">Pre-Auth</option>
                                                <option value="F">Failed Payment</option>
                                                <option value="B">Become Dealer</option>
                                                <option value="L">Lead</option>
                                                <option value="C">Closed</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 text-right">
                                    <button class="btn btn-primary btn-sm" onclick="show_user_list()">Search</button>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="show_user_detail()">Add New User</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <table class="table table-bordered table-hover table-condensed filter" id="tbl_user_list">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Full Name</th>
                                <th>Role</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Login As</th>
                                <th>Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
                <!--div class="modal-footer" style="margin-right:15px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div-->
            </div>
        </div>
    </div>



    <div class="modal" id="div_payment_detail" tabindex="-1" role="dialog" data-background="static" data-keyboard="false" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="div_rate_plan_title">New Payment</h4>
                </div>
                <div class="modal-body">
                    <div class="form-horizontal">
                        <div class="form-group drp-edit">
                            <label class="control-label col-sm-4">Account</label>
                            <div class="col-sm-8">
                                <input id="dpd_account" class="form-control" disabled/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-4">Method</label>
                            <div class="col-sm-8">
                                <select id="dpd_method" class="form-control">
                                    @if (in_array(Auth::user()->account_type, ['L']) && in_array(Auth::user()->user_id, ['system', 'admin', 'thomas']))
                                        <option value="D">Direct Deposit</option>
                                    @else
                                        <option value="H">Cash Pickup</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-4">Category</label>
                            <div class="col-sm-8">
                                <select id="dpd_category" class="form-control">
                                    @if (in_array(Auth::user()->account_type, ['L']) && in_array(Auth::user()->user_id, ['system', 'admin', 'thomas']))
                                        <option value="">Please Select</option>
                                        <option>Cash</option>
                                        <option>Check</option>
                                        <option>Credit</option>
                                        <option>Bank Transfer</option>
                                        <option>Money Order</option>
                                    @else
                                        <option value="Cash Pickup">Cash Pickup</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-4">Amount($)</label>
                            <div class="col-sm-8">
                                <input id="dpd_amt" class="form-control" onchange="calc_payment_fee()"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-4">Fee($)</label>
                            <div class="col-sm-8">
                                @if (in_array(Auth::user()->account_type, ['L']) && in_array(Auth::user()->user_id, ['system', 'admin', 'thomas']))
                                    <input id="dpd_fee" class="form-control" onchange="calc_payment_fee()"/>
                                @else
                                    <input id="dpd_fee" readonly class="form-control"/>
                                @endif
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-4">Deposit($)</label>
                            <div class="col-sm-8">
                                <input id="dpd_deposit_amt" readonly class="form-control"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-4">Comments</label>
                            <div class="col-sm-8">
                                <textarea id="dpd_comments" rows="5" style="width:100%; padding:5px;"></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-8 col-sm-offset-4">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" onclick="save_payment_detail()">Submit</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="div_credit_detail" tabindex="-1" role="dialog" data-background="static" data-keyboard="false" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="div_credit_title">New Credit</h4>
                </div>
                <div class="modal-body">
                    <div class="form-horizontal">
                        <div class="form-group drp-edit">
                            <label class="control-label col-sm-4">Account</label>
                            <div class="col-sm-8">
                                <input id="dcd_account" class="form-control" disabled/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-4">Type</label>
                            <div class="col-sm-8">
                                <select id="dcd_type" class="form-control">
                                    <option value="">Please Select</option>
                                    <option value="C">Credit</option>
                                    <option value="D">Debit</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-4">Amount($)</label>
                            <div class="col-sm-8">
                                <input id="dcd_amt" class="form-control" onchange="calc_payment_fee()"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-4">Comments</label>
                            <div class="col-sm-8">
                                <textarea id="dcd_comments" rows="5" style="width:100%; padding:5px;"></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-8 col-sm-offset-4">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" onclick="save_credit_detail()">Submit</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="div_rate_plan" tabindex="-1" role="dialog" data-background="static" data-keyboard="false" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="div_rate_plan_title">Rate Plan Detail</h4>
                </div>
                <div class="modal-body">
                    <div class="form-horizontal">
                        <div class="form-group drp-edit">
                            <label class="control-label col-sm-4">Rate.Plan.ID</label>
                            <div class="col-sm-8">
                                <input id="drp_id" class="form-control" disabled/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-4">Type</label>
                            <div class="col-sm-8">
                                <select id="drp_type" class="form-control">
                                    <option value="">Please Select</option>
                                    <option value="L">Root</option>
                                    <option value="M">Master</option>
                                    <option value="D">Distributor</option>
                                    <option value="S">Sub-Agent</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-4">Name</label>
                            <div class="col-sm-8">
                                <input id="drp_name" class="form-control"/>
                            </div>
                        </div>
                        <div class="form-group drp-new">
                            <label class="control-label col-sm-4">Copy From</label>
                            <div class="col-sm-8">
                                <input id="drp_copy_from" class="form-control" placeholder="Enter rate plan ID here to copy from..."/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-4">Status</label>
                            <div class="col-sm-8">
                                <select id="drp_status" class="form-control">
                                    <option value="">Please Select</option>
                                    <option value="A">Active</option>
                                    <option value="C">Closed</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group drp-edit">
                            <label class="control-label col-sm-4">Last.Updated</label>
                            <div class="col-sm-8">
                                <input id="drp_last_updated" class="form-control" disabled/>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-8 col-sm-offset-4">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" onclick="save_rate_plan()">Submit</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="div_rate_detail" tabindex="-1" role="dialog" data-backgrop="status" data-keyboard="false" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="div_rate_detail_title">Rates Detail</h4>
                </div>
                <div class="modal-body">

                    <div class="row" style="margin-bottom:20px;">
                        <div class="col-md-12">
                            <form action="javascript:void(0);">
                                {!! csrf_field() !!}
                                <div class="form-group row">
                                    @if (Auth::user()->account_type == 'L')
                                    <div class="col-md-3">
                                        Vendor
                                        <select id="vendor" name="vendor" class="form-control">
                                            <option value="">Show All</option>
                                            @if (count($vendors) > 0)
                                                @foreach ($vendors as $o)
                                                    <option value="{{ $o->code }}" {{ old('vendor', $vendor) == $o->code ? 'selected' : '' }}>{{ $o->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    @endif
                                    <div class="col-md-2">
                                        Action
                                        <select id="action" name="action" class="form-control">
                                            <option value="RTR">RTR</option>
                                            <option value="PIN">PIN</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        Product.ID
                                        <input type="text" class="form-control" id="product_id" name="product_id" value="{{old('product_id', $product_id)}}"/>
                                    </div>
                                    <div class="col-md-3">
                                        Product.Name
                                        <input type="text" class="form-control" id="product_name" name="product_name" value="{{old('product_name', $product_name)}}"/>
                                    </div>
                                    <div class="col-md-2">
                                        <br>
                                        <button type="button" class="btn btn-primary btn-sm" onclick="get_rate_detail(current_rate_plan_id, current_show_type)">Search</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <table class="table table-bordered table-hover table-condensed">
                        <thead>
                        <tr>
                            <th>Product</th>
                            <th>Action</th>
                            <th>Denom ($)</th>
                            <th id="th_vendor">Vendor</th>
                            <th id="th_cost">Cost (%)</th>
                            <th id="th_parent_rates">Parent.Rates (%)</th>
                            <th>Rates (%)</th>
                            <th>Command</th>
                        </tr>
                        </thead>
                        <tbody id="tbody_rate_detail">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

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

    <div class="modal" id="div_user_detail" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="user_detail_title">User Detail</h4>
                </div>
                <div class="modal-body">


                        <!-- Nav tabs -->
                    <ul class="nav nav-tabs" role="tablist">
                        <li role="presentation" class="active"><a href="#ud-profile" aria-controls="ud-profile"
                                                                  role="tab"
                                                                  data-toggle="tab">Profile</a></li>
                        <li role="presentation" id="li-tab-login-history"><a href="#ud-login-history" aria-controls="ud-login-history" role="tab"
                                                   data-toggle="tab">Login History</a></li>
                    </ul>

                    <!-- Tab panes -->
                    <div class="tab-content">

                        <div role="tabpanel" class="tab-pane active" id="ud-profile" style="padding:15px;">

                            <div class="form-horizontal">
                                <div class="form-group">
                                    <label class="col-sm-2 control-label">User ID: </label>
                                    <div class="col-sm-4">
                                        <input type="text" class="form-control" id="ud_user_id" placeholder="Case Sensitive"/>
                                    </div>
                                    <label class="col-sm-2 control-label">Full Name: </label>
                                    <div class="col-sm-4">
                                        <input type="text" class="form-control" id="ud_name"/>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-2 control-label">Role: </label>
                                    <div class="col-sm-4">
                                        <select class="form-control" id="ud_role">
                                            <option value="">Please Select</option>
                                            <option value="M">Manager</option>
                                            <option value="S">Staff</option>
                                        </select>
                                    </div>
                                    <label class="col-sm-2 control-label">Status: </label>
                                    <div class="col-sm-4">
                                        <select class="form-control" id="ud_status">
                                            <option value="">Please Select</option>
                                            <option value="A">Active</option>
                                            <option value="H">On-Hold</option>
                                            <option value="C">Closed</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-2 control-label">Password: </label>
                                    <div class="col-sm-4">
                                        <input type="password" class="form-control" id="ud_password" placeholder="Case Sensitive"/>
                                    </div>
                                    <label class="col-sm-2 control-label">Pwd. Confirm: </label>
                                    <div class="col-sm-4">
                                        <input type="password" class="form-control" id="ud_password_confirmation" placeholder="Case Sensitive"/>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-2 control-label">Email: </label>
                                    <div class="col-sm-4">
                                        <input type="email" class="form-control" id="ud_email"/>
                                    </div>
                                    <label class="col-sm-2 control-label"></label>
                                    <div class="col-sm-4">
                                    </div>
                                </div>

                                @if (in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']))
                                <div class="form-group">
                                    <label class="col-sm-2 control-label">CC Email : </label>
                                    <div class="col-sm-4">
                                        <input type="email" class="form-control" id="ud_copy_email"/>
                                    </div>
                                    <label class="col-sm-2 control-label">Resend.Comment: </label>
                                    <div class="col-sm-4">
                                        <input type="email" class="form-control" id="ud_comment"/>
                                    </div>
                                </div>
                                @endif

                                <div class="form-group">
                                    <label class="col-sm-2 control-label"></label>
                                    <div class="col-sm-4">
                                    </div>
                                    <div class="col-sm-4 col-sm-offset-2">
                                        <button type="button" class="btn btn-primary btn-sm" id="btn_save_user">Submit</button>
                                        @if (in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']))
                                        <button type="button" class="btn btn-default btn-sm" id="btn_login_as" onclick="login_as()">Login As</button>
                                        @endif
                                        <form id="frm_login_as" method="post" action="/admin/account/login-as" style="display:none">
                                            {!! csrf_field() !!}
                                            <input type="hidden" name="user_id" id="ud_la_user_id"/>
                                        </form>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div role="tabpanel" class="tab-pane" id="ud-login-history" style="padding:15px;">
                            <div class="well filter">
                                <form id="frm_user_login_history" class="form-horizontal filter" method="post" target="ifm_upload">
                                    {{ csrf_field() }}

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="col-md-4 control-label">Date</label>
                                                <div class="col-md-8">
                                                    <input type="text" style="width:100px; float:left;" class="form-control" id="ulh_sdate"/>
                                                    <span class="control-label" style="margin-left:10px; float:left;"> ~ </span>
                                                    <input type="text" style="width:100px; margin-left: 10px; float:left;" class="form-control" id="ulh_edate"/>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 text-right">
                                            <button class="btn btn-primary btn-sm" onclick="show_user_login_history()">Search</button>
                                            <button type="button" class="btn btn-primary btn-sm" onclick="reset_failurel()">Reset Failure</button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <table class="table table-bordered table-hover table-condensed filter" id="tbl_user_login_history">
                                <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>User ID</th>
                                    <th>Password</th>
                                    <th>Result</th>
                                    <th>Message</th>
                                    <th>IP</th>
                                </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>


                    </div>
                    <div class="progress" style="display:none;margin-top:20px;">
                        <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
                            <span class="sr-only">Please wait.</span>
                        </div>
                    </div>
                </div>
                <!--div class="modal-footer" style="margin-right:15px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div-->
            </div>
        </div>
    </div>


    <div class="modal" id="div_vr" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false"
         aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">VR Auth</h4>
                    <h5 class="modal-title" >Checked : Not Showing up  /  Unchecked : Showing up</h5>
                </div>
                <div class="modal-body" style="min-height:400px;">
                    <input type="hidden" id="vr_account_id">

                    <div id="dist_vr_boby">

{{--                        <div class="row">--}}
{{--                            @foreach ($vr_carriers as $d)--}}
{{--                            <div class="col-md-3 col-sm-4 col-xs-6">--}}
{{--                                <label class="checkbox-inline">--}}
{{--                                    <input type="checkbox" id="vr_carrier_{{ $d->carrier_key }}"--}}
{{--                                           onclick="vr_save('{{ $d->carrier_key }}', '{{ $d->carrier }}')" value="{{ $d->carrier }}"/> {{--}}
{{--                                            $d->carrier }}--}}
{{--                                </label>--}}
{{--                                <br>--}}
{{--                                <small><span id="vr_carrier_msg_{{ $d->carrier_key }}"></span></small>--}}
{{--                            </div>--}}
{{--                            @endforeach--}}

                            <table class="blueTable">
                                <thead>
                                <tr>
                                    <th>[*]</th>
                                    <th>Carrier</th>
                                    <th>Sub Carrier</th>
                                    <th>Category</th>
                                    <th>Sub Category</th>
                                    <th>Model</th>
                                    <th>Sub Agent Price</th>
                                </tr>
                                </thead>
                                <tboby>
                                @foreach ($vr_products as $p)
                                    <tr>
                                        <td>
                                        <input type="checkbox"
                                               id="vr_product_{{ $p->id }}"
                                               onclick="vr_product_save('{{ $p->id }}', '{{ $p->model }}')" value="{{ $p->id }}"/>
                                        </td>
                                        <td>{{ $p->carrier }}</td>
                                        <td>{{ $p->sub_carrier }} </td>
                                        <td>{{ $p->category }}</td>
                                        <td>{{ $p->sub_category }} </td>
                                        <td>{{ $p->model }} <small><span id="vr_product_msg_{{ $p->id }}" style="color: #ff4629"></span></small></td>
                                        <td align="right">${{ $p->subagent_price }} </td>
                                    </tr>
                                @endforeach
                                </tboby>
                            </table>
{{--                        </div>--}}
                        <hr>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div style="display:none">
        <iframe name="ifm_upload"></iframe>
    </div>

@stop
