@extends('admin.layout.default')

@section('content')

    <style type="text/css">
        .modal-dialog{
            position: relative;
            #display: table; /* This is important */
            overflow-y: auto;
            overflow-x: auto;
            width: auto;
            min-width: 300px;
        }
    </style>

    <style type="text/css">
        input[type=text]:disabled {
            background-color: #efefef;
        }

        select:disabled {
            background-color: #efefef;
        }
    </style>

    <script type="text/javascript">
        var onload_events = window.onload;
        window.onload = function() {

            if (onload_events) {
                onload_events();
            }

            $(".tooltip").tooltip({
                html: true
            })
        };

        function search() {
            $('#excel').val('N');
            $('#frm_search').submit();
        }

        var current_id = null;
        var vendor_filter = null;
        var denom_filter = null;
        var sku_filter = null;
        function show_detail(id) {
            var mode = (typeof id === 'undefined') ? 'new' : 'edit';
            var title = '';
            if (mode === 'new') {
                $('.edit').hide();
                title = 'New Product Setup';

                $('#n_id').val('');
                $('#n_carrier').val('');
                $('#n_vendor_code').val('');
                $('#n_type').val('');
                $('#n_name').val('');
                $('#n_status').val('');
                $('#n_sim_group').val('');
                $('#n_acct_fee').val('');
                $('#n_country_code').val('');
                $('#n_activation').val('');
                $('#n_last_updated').val('');
                $('#n_id').attr('readonly', false);
            } else {
                $('.edit').show();
                title = 'Product Setup Detail - ' + id;
                $('#n_id').attr('readonly', true);
            }

            $('#n_title').text(title);
            $('#div_detail').modal();

            var modal_height = $(window).height() - 240;
            $('.modal-body').css("max-height", modal_height);

            current_id = id;
        }

        function bind_init_denoms(denoms) {

            $('#target_denom').empty();
            $('#target_denom').append('<option value="">All</option>');

            $.each(denoms, function(i, k) {
                var html = '<option value="' + k.denom + '">' + k.denom + '</option>';
                $('#target_denom').append(html);
            })
        }

        function update_init_denoms(action) {

            myApp.showLoading();
            $.ajax({
                url: '/admin/settings/product-setup/update-init-denoms',
                data: {
                    _token: '{!! csrf_token() !!}',
                    product_id: current_id,
                    action: action
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();

                    if ($.trim(res.msg) === '') {
                        bind_init_denoms(res.init_denoms)
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

        function bind_denom_ids(denoms) {
            $('#nv_denom').empty();
            var html = '';
            $.each(denoms, function(i, o) {
                html += '<option value="' + o.id + '">' + o.denom + ' - ' + o.name + '</option>';
            });
            $('#nv_denom').append(html);
        }

        function bind_denoms(denoms) {
            $('#denom_status_filter').val('');
            $('#tbody_denom').empty();
            $.each(denoms, function(i, o) {
                var html = '<tr>';
                html += '<td><input disabled type="text" size="5" id="d_denom_' + o.id + '" value="' + parseFloat(o.denom).toFixed(2) + '"/></td>';
                html += '<td><input disabled type="text" id="d_denom_name_' + o.id + '" value="' + o.name + '" size="50"/></td>';

                html += '<td><input disabled type="text" size="5" id="d_min_denom_' + o.id + '" value="' + parseFloat(o.min_denom).toFixed(2) + '"/></td>';
                html += '<td><input disabled type="text" size="5" id="d_max_denom_' + o.id + '" value="' + parseFloat(o.max_denom).toFixed(2) + '"/></td>';

                html += '<td>';
                html +=     '<select disabled id="d_denom_status_' + o.id + '">';
                html +=         '<option value="A" ' + (o.status === 'A' ? 'selected' : '') + '>Active</option>';
                html +=         '<option value="I" ' + (o.status === 'I' ? 'selected' : '') + '>Inactive</option>';
                html +=     '</select>';
                html += '</td>';

                html += '<td>';
                html +=     '<button type="button" id="btn_denom_edit_' + o.id + '" class="btn btn-primary btn-xs" onclick="edit_denom(' + o.id + ')">Edit</button>&nbsp;';
                html +=     '<button type="button" style="display:none" id="btn_denom_update_' + o.id + '" class="btn btn-primary btn-xs" onclick="update_denom(' + o.id + ')">Update</button>&nbsp;';
                html +=     '<button type="button" style="display:none" id="btn_denom_cancel_' + o.id + '" class="btn btn-primary btn-xs" onclick="cancel_denom(' + o.id + ')">Cancel</button>&nbsp;';

                html += '</td>';
                //html += '<td>' + o.last_updated + '</td>';

                html += '</tr>';

                $('#tbody_denom').append(html);
            });
        }

        var old_d_denom = '';
        var old_d_denom_name = '';
        var old_d_min_denom = '';
        var old_d_max_denom = '';
        var old_d_denom_status = '';

        function edit_denom(id) {
            $('#d_denom_' + id).attr('disabled', false);
            $('#d_denom_name_' + id).attr('disabled', false);
            $('#d_min_denom_' + id).attr('disabled', false);
            $('#d_max_denom_' + id).attr('disabled', false);
            $('#d_denom_status_' + id).attr('disabled', false);

            old_d_denom = $('#d_denom_' + id).val();
            old_d_denom_name = $('#d_denom_name_' + id).val();
            old_d_min_denom = $('#d_min_denom_' + id).val();
            old_d_max_denom = $('#d_max_denom_' + id).val();
            old_d_denom_status = $('#d_denom_status_' + id).val();

            $('#btn_denom_edit_' + id).hide();
            $('#btn_denom_update_' + id).show();
            $('#btn_denom_cancel_' + id).show();
        }

        function cancel_denom(id) {
            $('#d_denom_' + id).attr('disabled', true);
            $('#d_denom_name_' + id).attr('disabled', true);
            $('#d_min_denom_' + id).attr('disabled', true);
            $('#d_max_denom_' + id).attr('disabled', true);
            $('#d_denom_status_' + id).attr('disabled', true);

            $('#d_denom_' + id).val(old_d_denom);
            $('#d_denom_name_' + id).val(old_d_denom_name);
            $('#d_min_denom_' + id).val(old_d_min_denom);
            $('#d_max_denom_' + id).val(old_d_max_denom);
            $('#d_denom_status_' + id).val(old_d_denom_status);

            $('#btn_denom_edit_' + id).show();
            $('#btn_denom_update_' + id).hide();
            $('#btn_denom_cancel_' + id).hide();
        }

        function bind_vendor_denoms(vendors, vendor_denoms, s_user) {
            $('#vendor_status_filter').val('');
            $('#tbody_vendor_denom').empty();
            $.each(vendor_denoms, function(i, o) {
                var html = '<tr>';

                // vendor code
                html += '<td>';
                html +=     '<select disabled id="v_vendor_code_' + o.id + '">';

                $.each(vendors, function(j, k) {
                    html +=     '<option value="' + k.code + '" ' + (o.vendor_code === k.code ? 'selected' : '') + '>' + k.name + '</option>';
                });

                html +=     '</select>';
                html += '</td>';

                // denom
                html += '<td><input disabled type="text" size="5" id="v_denom_' + o.id + '" value="' + parseFloat(o.denom).toFixed(2) +  '"/>';

                // denom_id
                html += '<input disabled type="hidden" size="5" id="v_denom_id_' + o.id + '" value="' + o.denom_id +  '"/>';

                // denom_name
                html += '<td>' + o.name + '</td>';

                // act_id
                html += '<td><input disabled type="text" size="10" id="v_act_pid_' + o.id + '" value="' + o.act_pid + '"/></td>';

                // rtr_id
                html += '<td><input disabled type="text" size="10" id="v_rtr_pid_' + o.id + '" value="' + o.rtr_pid + '"/></td>';

                // pin_pid
                html += '<td><input disabled type="text" size="10" id="v_pin_pid_' + o.id + '" value="' + o.pin_pid + '"/></td>';

                // fee
                html += '<td><input disabled type="text" size="4" id="v_fee_' + o.id + '" value="' + o.fee + '"/></td>';

                // pm_fee
                html += '<td><input disabled type="text" size="4" id="v_pm_fee_' + o.id + '" value="' + o.pm_fee + '"/></td>';

                // cost
                if(s_user == 'Y') {
                    html += '<td><input disabled type="text" size="4" id="v_cost_' + o.id + '" value="' + parseFloat(o.cost).toFixed(2) + '"/></td>';
                }else{
                    html += '<td><input disabled type="hidden" id="v_cost_' + o.id + '" value="' + parseFloat(o.cost).toFixed(2) + '"/></td>'
                }
                // status
                html += '<td>';
                html +=     '<select disabled id="v_status_' + o.id + '">';
                html +=         '<option value="A" ' + (o.status === 'A' ? 'selected' : '') + '>Active</option>';
                html +=         '<option value="I" ' + (o.status === 'I' ? 'selected' : '') + '>Inactive</option>';
                html +=     '</select>';
                html += '</td>';

                // command
                html += '<td>';
                html +=     '<button type="button" id="btn_vendor_denom_update_' + o.id + '" style="display:none" class="btn btn-primary btn-xs" onclick="update_vendor_denom(' + o.id + ')">Update</button>&nbsp;';
                html +=     '<button type="button" id="btn_vendor_denom_edit_' + o.id + '" class="btn btn-primary btn-xs" onclick="edit_vendor_denom(' + o.id + ')">Edit</button>';
                html +=     '<button type="button" id="btn_vendor_denom_cancel_' + o.id + '" style="display:none" class="btn btn-primary btn-xs" onclick="cancel_vendor_denom(' + o.id + ')">Cancel</button>';
                html += '</td>';

                // last updated
                //html += '<td>' + o.last_updated  + '</td>';

                html += '</tr>';

                $('#tbody_vendor_denom').append(html);
            });
        }


        var old_v_vendor_code = '';
        var old_v_denom = '';
        var old_v_act_pid = '';
        var old_v_rtr_pid = '';
        var old_v_pin_pid = '';
        var old_v_fee = '';
        var old_v_pm_fee = '';
        var old_v_cost = '';
        var old_v_status = '';

        function edit_vendor_denom(id) {
            $('#v_vendor_code_' + id).attr('disabled', false);
            $('#v_denom_' + id).attr('disabled', false);
            $('#v_act_pid_' + id).attr('disabled', false);
            $('#v_rtr_pid_' + id).attr('disabled', false);
            $('#v_pin_pid_' + id).attr('disabled', false);
            $('#v_fee_' + id).attr('disabled', false);
            $('#v_pm_fee_' + id).attr('disabled', false);
            $('#v_cost_' + id).attr('disabled', false);
            $('#v_status_' + id).attr('disabled', false);

            old_v_vendor_code = $('#v_vendor_code_' + id).val();
            old_v_denom = $('#v_denom_' + id).val();
            old_v_act_pid = $('#v_act_pid_' + id).val();
            old_v_rtr_pid = $('#v_rtr_pid_' + id).val();
            old_v_pin_pid = $('#v_pin_pid_' + id).val();
            old_v_fee = $('#v_fee_' + id).val();
            old_v_pm_fee = $('#v_pm_fee_' + id).val();
            old_v_cost = $('#v_cost_' + id).val();
            old_v_status = $('#v_status_' + id).val();

            $('#btn_vendor_denom_edit_' + id).hide();
            $('#btn_vendor_denom_update_' + id).show();
            $('#btn_vendor_denom_cancel_' + id).show();
        }

        function cancel_vendor_denom(id) {
            $('#v_vendor_code_' + id).attr('disabled', true);
            $('#v_denom_' + id).attr('disabled', true);
            $('#v_act_pid_' + id).attr('disabled', true);
            $('#v_rtr_pid_' + id).attr('disabled', true);
            $('#v_pin_pid_' + id).attr('disabled', true);
            $('#v_fee_' + id).attr('disabled', true);
            $('#v_pm_fee_' + id).attr('disabled', true);
            $('#v_cost_' + id).attr('disabled', true);
            $('#v_status_' + id).attr('disabled', true);

            $('#v_vendor_code_' + id).val(old_v_vendor_code);
            $('#v_denom_' + id).val(old_v_denom);
            $('#v_act_pid_' + id).val(old_v_act_pid);
            $('#v_rtr_pid_' + id).val(old_v_rtr_pid);
            $('#v_pin_pid_' + id).val(old_v_pin_pid);
            $('#v_fee_' + id).val(old_v_fee);
            $('#v_pm_fee_' + id).val(old_v_pm_fee);
            $('#v_cost_' + id).val(old_v_cost);
            $('#v_status_' + id).val(old_v_status);


            $('#btn_vendor_denom_edit_' + id).show();
            $('#btn_vendor_denom_update_' + id).hide();
            $('#btn_vendor_denom_cancel_' + id).hide();
        }

        function filter_detail() {
            vendor_filter   = $('#vendor_filter').val();
            denom_filter    = $('#denom_filter').val();
            sku_filter      = $('#sku_filter').val();

            load_detail(current_id, vendor_filter, denom_filter, sku_filter);
        }

        function filter_denom_status() {

            var status = $('#denom_status_filter').val();

            $("[id^='d_denom_status_']").each(function ()
            {
                if (status) {
                    if ($(this).val() == status) {
                        $(this).closest( "tr" ).show();
                    } else {
                        $(this).closest( "tr" ).hide();
                    }
                } else {
                    $(this).closest( "tr" ).show();
                }
            });

        }

        function filter_vendor_status() {

            var status = $('#vendor_status_filter').val();

            $("[id^='v_status_']").each(function ()
            {
                if (status) {
                    if ($(this).val() == status) {
                        $(this).closest( "tr" ).show();
                    } else {
                        $(this).closest( "tr" ).hide();
                    }
                } else {
                    $(this).closest( "tr" ).show();
                }
            });

        }

        function bind_vendor_fee_setup(vendors, vendor_fee_setup) {

            $('#vendor_fee_filter').val('');
            $('#tbody_vendor_fee_setup').empty();

            $.each(vendor_fee_setup, function(i, o) {
                var html = '<tr>';

                html += '<td>';
                html +=     '<select disabled id="vfs_vendor_code_' + o.id + '">';

                $.each(vendors, function(j, k) {
                    html +=     '<option value="' + k.code + '" ' + (o.vendor_code === k.code ? 'selected' : '') + '>' + k.name + '</option>';
                });

                html +=     '</select>';
                html += '</td>';

                html += '<td><input disabled type="text" size="10" id="vfs_product_' + o.id + '" value="' + o.product_id +  '"/>';
                html += '<td><input disabled type="text" size="10" id="vfs_amt_and_fee_' + o.id + '" value="' + o.amt_and_fee + '"/></td>';

                html += '<td>';
                html +=     '<button type="button" id="btn_vendor_fee_setup_del_' + o.id + '" class="btn btn-primary btn-xs" onclick="vender_fee_setup_del(' + o.id + ')">Deletee</button>';
                html += '</td>';

                html += '</tr>';

                $('#tbody_vendor_fee_setup').append(html);
            });
        }

        function vender_fee_setup_del(id) {

            myApp.showConfirm("Are you sure to Delete?", function() {
                $.ajax({
                    url: '/admin/settings/product-setup/vendor-fee-setup-del',
                    data: {
                        _token: '{!! csrf_token() !!}',
                        id: id
                    },
                    cache: false,
                    type: 'post',
                    dataType: 'json',
                    success: function (res) {
                        myApp.hideLoading();
                        if ($.trim(res.msg) === '') {

                            myApp.showSuccess('Your request has been processed successfully!', function () {
                            });

                            load_detail(current_id);

                        } else {
                            myApp.showError(res.msg);
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        myApp.hideLoading();
                        myApp.showError(errorThrown);
                    }
                })
            }, function() {
                // do nothing
            });

        }

        function vendor_fee_setup() {
            var vendor_code = $('#vendor_fee_filter').val();
            var product_id = $('#n_id').val();

            myApp.showLoading();
            $.ajax({
                url: '/admin/settings/product-setup/vendor-fee-setup',
                data: {
                    _token: '{!! csrf_token() !!}',
                    vendor_code: vendor_code,
                    product_id: product_id
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {

                        myApp.showSuccess('Your request has been processed successfully!', function() {
                        });

                        load_detail(current_id);

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

        function load_detail(id, vendor, denom, sku) {
            $('#m_rates').val('');
            $('#d_rates').val('');
            $('#s_rates').val('');

            myApp.showLoading();
            $.ajax({
                url: '/admin/settings/product-setup/load-detail',
                data: {
                    _token: '{!! csrf_token() !!}',
                    id: id,
                    vendor: vendor,
                    denom: denom,
                    sku: sku
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {

                        $('#n_id').val(res.product.id);
                        $('#n_carrier').val(res.product.carrier);
                        $('#n_type').val(res.product.type);
                        $('#n_vendor_code').val(res.product.vendor_code);
                        $('#n_activation').val(res.product.activation);
                        $('#n_name').val(res.product.name);
                        $('#n_status').val(res.product.status);
                        $('#n_sim_group').val(res.product.sim_group);
                        $('#n_acct_fee').val(res.product.acct_fee);
                        $('#n_country_code').val(res.product.country_code);
                        $('#n_last_updated').val(res.product.last_updated);

                        bind_denom_ids(res.denoms);

                        bind_denoms(res.denoms);

                        bind_vendor_denoms(res.vendors, res.vendor_denoms, res.s_user);

                        bind_vendor_fee_setup(res.vendors, res.vendor_fee_setup);

                        bind_init_denoms(res.init_denoms_rtr);

                        show_detail(id);
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

        function add_vendor_denom() {
            myApp.showLoading();
            $.ajax({
                url: '/admin/settings/product-setup/add-vendor-denom',
                data: {
                    _token: '{!! csrf_token() !!}',
                    product_id: current_id,
                    vendor_code: $('#nv_vendor_code').val(),
                    denom_id: $('#nv_denom').val(),
                    act_pid: $('#nv_act_pid').val(),
                    rtr_pid: $('#nv_rtr_pid').val(),
                    pin_pid: $('#nv_pin_pid').val(),
                    fee: $('#nv_fee').val(),
                    pm_fee: $('#nv_pm_fee').val(),
                    cost: $('#nv_cost').val(),
                    status: $('#nv_status').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();

                    if ($.trim(res.msg) === '') {
                        myApp.showSuccess('Your request has been processed successfully!', function() {
                            $('#nv_vendor_code').val('');
                            $('#nv_denom').val('');
                            $('#nv_act_pid').val('');
                            $('#nv_rtr_pid').val('');
                            $('#nv_pin_pid').val('');
                            $('#nv_fee').val('0.00');
                            $('#nv_pm_fee').val('0.00');
                            $('#nv_cost').val('0.00');
                            $('#nv_status').val('');
                            load_detail(current_id, vendor_filter, denom_filter);
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

        function update_vendor_denom(id) {
            myApp.showLoading();
            $.ajax({
                url: '/admin/settings/product-setup/update-vendor-denom',
                data: {
                    _token: '{!! csrf_token() !!}',
                    product_id: current_id,
                    vendor_denom_id: id,
                    vendor_code: $('#v_vendor_code_' + id).val(),
                    denom: $('#v_denom_' + id).val(),
                    denom_id: $('#v_denom_id_' + id).val(),
                    act_pid: $('#v_act_pid_' + id).val(),
                    rtr_pid: $('#v_rtr_pid_' + id).val(),
                    pin_pid: $('#v_pin_pid_' + id).val(),
                    fee: $('#v_fee_' + id).val(),
                    pm_fee: $('#v_pm_fee_' + id).val(),
                    cost: $('#v_cost_' + id).val(),
                    status: $('#v_status_' + id).val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();

                    if ($.trim(res.msg) === '') {
                        myApp.showSuccess('Your request has been processed successfully!', function() {
                            load_detail(current_id, vendor_filter, denom_filter);
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

        function add_denom() {

            myApp.showLoading();
            $.ajax({
                url: '/admin/settings/product-setup/add-denom',
                data: {
                    _token: '{!! csrf_token() !!}',
                    product_id: current_id,
                    denom: $('#nd_denom').val(),
                    denom_name: $('#nd_denom_name').val(),
                    min_denom: $('#nd_min_denom').val(),
                    max_denom: $('#nd_max_denom').val(),
                    status: $('#nd_status').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();

                    if ($.trim(res.msg) === '') {

                        myApp.showSuccess('Your request has been processed successfully!', function() {
                            $('#nd_denom').val('');
                            $('#nd_denom_name').val('');
                            $('#nd_min_denom').val('');
                            $('#nd_max_denom').val('');
                            $('#nd_status').val('');

                            load_detail(current_id, '', '');
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

        function update_denom(denom_id) {

            myApp.showLoading();
            $.ajax({
                url: '/admin/settings/product-setup/update-denom',
                data: {
                    _token: '{!! csrf_token() !!}',
                    product_id: current_id,
                    denom_id: denom_id,
                    denom: $('#d_denom_' + denom_id).val(),
                    denom_name: $('#d_denom_name_' + denom_id).val(),
                    min_denom: $('#d_min_denom_' + denom_id).val(),
                    max_denom: $('#d_max_denom_' + denom_id).val(),
                    status: $('#d_denom_status_' + denom_id).val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();

                    if ($.trim(res.msg) === '') {

                        myApp.showSuccess('Your request has been processed successfully!', function() {
                            load_detail(current_id, '', '');
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

        function save_detail() {

            var url = typeof current_id === 'undefined' ? '/admin/settings/product-setup/add' : '/admin/settings/product-setup/update'

            myApp.showLoading();
            $.ajax({
                url: url,
                data: {
                    _token: '{!! csrf_token() !!}',
                    id: $('#n_id').val(),
                    type: $('#n_type').val(),
                    carrier: $('#n_carrier').val(),
                    vendor_code: $('#n_vendor_code').val(),
                    activation: $('#n_activation').val(),
                    name: $('#n_name').val(),
                    status: $('#n_status').val(),
                    sim_group: $('#n_sim_group').val(),
                    acct_fee: $('#n_acct_fee').val(),
                    country_code: $('#n_country_code').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        myApp.showSuccess('Your request has been processed successfully!', function() {
                            search();
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

        function init_rates() {
            var denom = $('#target_denom').val() != '' ? 'Denom : $' + $('#target_denom').val() : 'All';
            var text = "Are you sure to init rates with " + denom + " ?";
            myApp.showConfirm(text, function() {
                myApp.showLoading();
                $.ajax({
                    url: '/admin/settings/product-setup/init-rates',
                    data: {
                        _token: '{!! csrf_token() !!}',
                        product_id: current_id,
                        action: $('#r_action').val(),
                        m_rates: $('#m_rates').val(),
                        d_rates: $('#d_rates').val(),
                        s_rates: $('#s_rates').val(),
                        denom: $('#target_denom').val()
                    },
                    cache: false,
                    type: 'post',
                    dataType: 'json',
                    success: function(res) {
                        myApp.hideLoading();

                        if ($.trim(res.msg) === '') {
                            myApp.showSuccess('Your request has been processed successfully!', function() {
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

        function delete_rates() {
            var denom = $('#target_denom').val() != '' ? 'Denom : $' + $('#target_denom').val() : 'All';
            var text = "Are you sure to delete rates with " + denom + " ?";
            myApp.showConfirm(text, function() {
                myApp.showLoading();
                $.ajax({
                    url: '/admin/settings/product-setup/delete-rates',
                    data: {
                        _token: '{!! csrf_token() !!}',
                        product_id: current_id,
                        action: $('#r_action').val(),
                        m_rates: $('#m_rates').val(),
                        d_rates: $('#d_rates').val(),
                        s_rates: $('#s_rates').val(),
                        denom: $('#target_denom').val()
                    },
                    cache: false,
                    type: 'post',
                    dataType: 'json',
                    success: function(res) {
                        myApp.hideLoading();

                        if ($.trim(res.msg) === '') {
                            myApp.showSuccess('Your request has been processed successfully!', function() {
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

        function excel_export() {
            $('#excel').val('Y');
            $('#frm_search').submit();
            myApp.hideLoading();
            $('#excel').val('N');
        }
    </script>


    <h4>Product Setup</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" action="/admin/settings/product-setup" method="post" onsubmit="myApp.showLoading();">
            {{ csrf_field() }}
            <input type="hidden" name="excel" id="excel"/>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Carrier</label>
                        <div class="col-md-8">
                            <select name="carrier" class="form-control" onchange="$('#frm_search').submit()">
                                <option value="">All</option>
                                @if (count($carriers) > 0)
                                    @foreach ($carriers as $o)
                                        <option value="{{ $o->name }}" {{ old('carrier', $carrier) == $o->name ? 'selected' : '' }}>{{ $o->name }}</option>
                                    @endforeach
                                @endif
                                <option value="N/A">N/A</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Type</label>
                        <div class="col-md-8">
                            <select name="type" class="form-control" onchange="$('#frm_search').submit()">
                                <option value="">All</option>
                                <option value="Wireless" {{ old('type', $type) == 'Wireless' ? 'selected' : '' }}>Wireless</option>
                                <option value="ILD" {{ old('type', $type) == 'ILD' ? 'selected' : '' }}>ILD</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Status</label>
                        <div class="col-md-8">
                            <select name="status" class="form-control" onchange="$('#frm_search').submit()">
                                <option value="">All</option>
                                <option value="A" {{ old('status', $status) == 'A' ? 'selected' : '' }}>Active</option>
                                <option value="H" {{ old('status', $status) == 'H' ? 'selected' : '' }}>On-Hold</option>
                                <option value="C" {{ old('status', $status) == 'C' ? 'selected' : '' }}>Closed</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Vendor</label>
                        <div class="col-md-8">
                            <select name="vendor_code" class="form-control" onchange="$('#frm_search').submit()">
                                <option value="">All</option>
                                @if (count($vendors) > 0)
                                    @foreach ($vendors as $o)
                                        <option value="{{ $o->code }}" {{ old('vendor_code', $vendor_code) == $o->code ? 'selected' : '' }}>{{ $o->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Name</label>
                        <div class="col-md-8">
                            <input class="form-control" name="name" value="{{ old('name', $name) }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">SKU</label>
                        <div class="col-md-8">
                            <input class="form-control" name="sku" value="{{ old('sku', $sku) }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                </div>

                <div class="col-md-4">
                </div>

                <div class="col-md-4 text-right">
                    <div class="form-group">
                        <div class="col-md-12 text-right">
                            <button type="button" class="btn btn-info btn-sm" onclick="excel_export()">Download</button>
                            <button type="submit" class="btn btn-primary btn-sm" id="btn_search">Search</button>
                            <button type="button" class="btn btn-default btn-sm" onclick="show_detail()">Add New</button>
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
            <th>Type</th>
            <th>Sim.Group</th>
            <th>Carrier</th>
            <th>Vendor</th>
            <th>Name</th>
            <th>Status</th>
            <th>For.Activation?</th>
            <th>Acct.Fee?</th>
            <th>Last.Updated</th>
        </tr>
        </thead>
        <tbody>
        @if (isset($data) && count($data) > 0)
            @foreach ($data as $o)
                <tr>
                    <td><a href="javascript:load_detail('{{$o->id}}', '', '')">{{ $o->id }}</a></td>
                    <td>{{ $o->type }}</td>
                    <td>{{ $o->sim_group }}</td>
                    <td>{{ $o->carrier }}</td>
                    <td>{{ $o->vendor }}</td>
                    <td>{{ $o->name }}</td>
                    <td>{{ $o->status_name }}</td>
                    <td>{{ $o->activation_name }}</td>
                    <td>
                        @if($o->acct_fee == 'Y')
                            Yes
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $o->last_updated }}</td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="20" class="text-center">No Record Found</td>
            </tr>
        @endif
        </tbody>
    </table>

    <div class="text-left">
        Total {{ $data->total() }} record(s).
    </div>
    <div class="text-right">
        {{ $data->appends(Request::except('page'))->links() }}
    </div>

    <div class="modal" id="div_detail" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document" style="width:1300px !important;">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="n_title">Product Detail</h4>
                </div>
                <div class="modal-body">

                    <div class="form-horizontal well">
                        {{ csrf_field() }}
                        <div class="form-group">
                            <label class="col-sm-2 control-label">ID</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" id="n_id"/>
                            </div>
                            <label class="col-sm-2 control-label">Type</label>
                            <div class="col-sm-4">
                                <select id="n_type" class="form-control">
                                    <option value="">Please Select</option>
                                    <option value="Wireless">Wireless</option>
                                    <option value="ILD">ILD</option>
                                    <option value="INTL">INTL TopUp</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Carrier</label>
                            <div class="col-sm-4">
                                <select id="n_carrier" class="form-control">
                                    <option value="">Please Select</option>
                                    @if (count($carriers) > 0)
                                        @foreach ($carriers as $o)
                                            <option value="{{ $o->name }}">{{ $o->name }}</option>
                                        @endforeach
                                    @endif
                                    <option value="N/A">N/A</option>
                                </select>
                            </div>
                            <label class="col-sm-2 control-label">Vendor</label>
                            <div class="col-sm-4">
                                <select id="n_vendor_code" class="form-control">
                                    <option value="">Please Select</option>
                                    @if (count($vendors) > 0)
                                        @foreach ($vendors as $o)
                                            <option value="{{ $o->code }}">{{ $o->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Name</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" id="n_name"/>
                            </div>
                            <label class="col-sm-2 control-label">For.Activation?</label>
                            <div class="col-sm-4">
                                <select id="n_activation" class="form-control">
                                    <option value="">Please Select</option>
                                    <option value="N">No</option>
                                    <option value="Y">Yes</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group ">
                            <label class="col-sm-2 control-label">Status</label>
                            <div class="col-sm-4">
                                <select id="n_status" class="form-control">
                                    <option value="">Please Select</option>
                                    <option value="A">Active</option>
                                    <option value="H">On-Hold</option>
                                    <option value="C">Closed</option>
                                </select>
                            </div>
                            <label class="col-sm-2 control-label edit">Last.Updated</label>
                            <div class="col-sm-4 edit">
                                <input type="text" class="form-control" id="n_last_updated" disabled/>
                            </div>
                        </div>
                        <div class="form-group ">
                            <label class="col-sm-2 control-label">Sim.Group</label>
                            <div class="col-sm-4">
                                <select id="n_sim_group" class="form-control">
                                    <option value="">Please Select</option>
                                    <option value="ATT">ATT</option>
                                    <option value="H2O">H2O</option>
                                    <option value="Bolt">Bolt</option>
                                    <option value="EasyGo">EasyGo</option>
                                    <option value="FreeUp">FreeUp</option>
                                    <option value="Gen SPR">Gen Mobile (SPR)</option>
                                    <option value="Gen TMO">Gen Mobile (TMO)</option>
                                    <option value="Lyca">Lyca</option>
                                    <option value="Liberty">Liberty Mobile</option>
                                    <option value="BoomBlue">Boom Mobile Blue</option>
                                    <option value="BoomRed">Boom Mobile Red</option>
                                    <option value="BoomPurple">Boom Mobile Purple</option>
                                </select>
                            </div>
                            <label class="col-sm-2 control-label">Account.Fee?</label>
                            <div class="col-sm-4">
                                <select id="n_acct_fee" class="form-control">
                                    <option value="">Please Select</option>
                                    <option value="N">No</option>
                                    <option value="Y">Yes</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group ">
                            <label class="col-sm-2 control-label">Country Code</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" id="n_country_code"/>
                            </div>
                        </div>

                    </div>

                    <ul class="nav nav-tabs edit" role="tablist">
                        <li role="presentation" class="active">
                            <a href="#denom" aria-controls="denom" role="tab" data-toggle="tab">Denominations</a>
                        </li>
                        <li role="presentation">
                            <a href="#vendor" aria-controls="vendor" role="tab" data-toggle="tab">Vendor Configuration</a>
                        </li>
                        <li role="presentation">
                            <a href="#vendorfee" aria-controls="vendorfee" role="tab" data-toggle="tab">Dollar Phone Setup</a>
                        </li>
                        <li role="presentation">
                            <a href="#rates" aria-controls="rates" role="tab" data-toggle="tab">Init Rates</a>
                        </li>
                    </ul>

                    <div class="tab-content edit">
                        <div role="tabpanel" class="tab-pane active" id="denom">
                            <div class="form-group">
                                <div class="col-sm-12 panel panel-default">

                                    <div class="form-horizontal well margintop20">
                                        <div class="form-group">
                                            <div class="col-sm-2">
                                                <select id="denom_status_filter" onchange="filter_denom_status()">
                                                    <option value="">Select Status</option>
                                                    <option value="A">Active</option>
                                                    <option value="I">Inactive</option>
                                                </select>
                                            </div>
                                            <div class="col-sm-10"></div>
                                        </div>
                                    </div>

                                    <table class="table table-bordered table-hover table-condensed">
                                        <thead>
                                            <tr>
                                                <th>Denom($)</th>
                                                <th>Name</th>
                                                <th>Min($)</th>
                                                <th>Max($)</th>
                                                <th>Status</th>
                                                <th>Command</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbody_denom">
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td>
                                                    <input type="text" size="5" id="nd_denom"/>
                                                </td>
                                                <td>
                                                    <input type="text" size="50" id="nd_denom_name"/>
                                                </td>
                                                <td>
                                                    <input type="text" size="5" id="nd_min_denom"/>
                                                </td>
                                                <td>
                                                    <input type="text" size="5" id="nd_max_denom"/>
                                                </td>
                                                <td>
                                                    <select id="nd_status">
                                                        <option value="">Select</option>
                                                        <option value="A">Active</option>
                                                        <option value="I">Inactive</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <buttton type="button" class="btn btn-primary btn-xs" onclick="add_denom()">Add New</buttton>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div><!-- End Denom -->

                        <div role="tabpanel" class="tab-pane" id="vendor">
                            <div class="form-group">
                                <div class="col-sm-12 panel panel-default">

                                    <div class="form-horizontal well margintop20">
                                        <div class="form-group">
                                            <div class="col-sm-2">
                                                <select id="vendor_status_filter" onchange="filter_vendor_status()">
                                                    <option value="">Select Status</option>
                                                    <option value="A">Active</option>
                                                    <option value="I">Inactive</option>
                                                </select>
                                            </div>
                                            <div class="col-sm-2">
                                                <select  id="vendor_filter" onchange="filter_detail()">
                                                    <option value="">Select Vendor</option>
                                                    @if (count($vendors) > 0)
                                                        @foreach ($vendors as $o)
                                                            <option value="{{ $o->code }}">{{ $o->name }}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </div>
                                            <div class="col-sm-1">Denom</div>
                                            <div class="col-sm-1">
                                                <input type="text" value="" onblur="filter_detail()" id="denom_filter" style="height:28px; width:50px;"/>
                                            </div>
                                            <div class="col-sm-1">SKU</div>
                                            <div class="col-sm-5">
                                                <input type="text" value="" onblur="filter_detail()" id="sku_filter" style="height:28px; width:150px;"/>
                                            </div>
                                            <div class="col-sm-5"></div>
                                        </div>
                                    </div>
                                    <table class="table table-bordered table-hover table-condensed">
                                        <thead>
                                        <tr>
                                            <th>Vendor</th>
                                            <th>Denom</th>
                                            <th>Denom.Name</th>
                                            <th>Act.SKU</th>
                                            <th>RTR.SKU</th>
                                            <th>PIN.SKU</th>
                                            <th>Fee($)</th>
                                            <th>PM.Fee($)</th>
                                            <th>Cost(%)</th>
                                            <th>Status</th>
                                            <th>Command</th>
                                        </tr>
                                        </thead>
                                        <tbody id="tbody_vendor_denom">
                                        </tbody>
                                        <tfoot>
                                        <tr>
                                            <th>
                                                <select id="nv_vendor_code">
                                                    <option value="">Select</option>
                                                    @if (count($vendors) > 0)
                                                        @foreach ($vendors as $o)
                                                            <option value="{{ $o->code }}">{{ $o->name }}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </th>
                                            <th colspan="2">
                                                <select id="nv_denom">
                                                    <option value="">Select</option>
                                                    @if (count($denoms) > 0)
                                                        @foreach ($denoms as $o)
                                                            <option value="{{ $o->id }}">{{$o->denom}}-{{ $o->name }}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
{{--                                                <input type="text" size="5" value="" id="nv_denom"/>--}}
                                            </th>
{{--                                            <th>--}}
{{--                                                <input disabled type="text" value="" size="25" id="nv_denom_name"/>--}}
{{--                                            </th>--}}
                                            <th>
                                                <input type="text" size="10"value=""  id="nv_act_pid"/>
                                            </th>
                                            <th>
                                                <input type="text" size="10"value="" id="nv_rtr_pid"/>
                                            </th>
                                            <th>
                                                <input type="text" size="10" value="" size="10" id="nv_pin_pid"/>
                                            </th>
                                            <th>
                                                <input type="text" size="4" value="0.00" id="nv_fee"/>
                                            </th>
                                            <th>
                                                <input type="text" size="4" value="0.00" id="nv_pm_fee"/>
                                            </th>
                                            <th>
                                            @if($s_user == 'Y')
                                                <input type="text" size="4" value="0.00" id="nv_cost"/>
                                            @else
                                                <input type="hidden" value="0.00" id="nv_cost"/>
                                            @endif
                                            </th>
                                            <th>
                                                <select id="nv_status">
                                                    <option value="">Select</option>
                                                    <option value="A">Active</option>
                                                    <option value="I">Inactive</option>
                                                </select>
                                            </th>
                                            <th>
                                                <button type="button" class="btn btn-primary btn-xs" onclick="add_vendor_denom()">Add</button>
                                            </th>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div> <!-- End vendor setting -->

                        <div role="tabpanel" class="tab-pane" id="vendorfee">
                            <div class="form-group">
                                <div class="col-sm-12 panel panel-default">

                                    <div class="form-horizontal well margintop20">
                                        <div class="form-group">
                                            <div class="col-sm-4">Add for Send [Plan Amount + Fee]</div>
                                            <div class="col-sm-2">
                                                <select id="vendor_fee_filter">
                                                    <option value="">Select Vendor</option>
                                                    <option value="DLP">Dollar Phone</option>
                                                </select>
                                            </div>
                                            <div class="col-sm-1">
                                                <a type="button" class="btn btn-primary btn-xs" onclick="vendor_fee_setup()">ADD</a>
                                            </div>
                                            <div class="col-sm-5"></div>
                                        </div>
                                    </div>
                                    <table class="table table-bordered table-hover table-condensed">
                                        <thead>
                                        <tr>
                                            <th>Vendor</th>
                                            <th>Product</th>
                                            <th>Send Plan Amount + Fee ?</th>
                                            <th style="width:140px">Action</th>
                                        </tr>
                                        </thead>
                                        <tbody id="tbody_vendor_fee_setup">
                                        </tbody>
                                        <tfoot>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div> <!-- End vendor setting -->

                        <div role="tabpanel" class="tab-pane" id="rates">
                            <div class="form-group">
                                <div class="col-sm-12 panel panel-default">

                                    <div class="form-horizontal well margintop20">
                                        <div class="form-group">
                                            <div class="col-sm-1">M.Rates</div>
                                            <div class="col-sm-1">
                                                <input type="text" value="" id="m_rates" style="height:28px; width:50px;"/>
                                            </div>
                                            <div class="col-sm-1">D.Rates</div>
                                            <div class="col-sm-1">
                                                <input type="text" value="" id="d_rates" style="height:28px; width:50px;"/>
                                            </div>
                                            <div class="col-sm-1">S.Rates</div>
                                            <div class="col-sm-1">
                                                <input type="text" value="" id="s_rates" style="height:28px; width:50px;"/>
                                            </div>
                                            <div class="col-sm-1">Action</div>
                                            <div class="col-sm-1">
                                                <select id="r_action" onchange="update_init_denoms(this.value)">
                                                    <option value="RTR">RTR</option>
                                                    <option value="PIN">PIN</option>
                                                </select>
                                            </div>
                                            <div class="col-sm-1">Denom</div>
                                            <div class="col-sm-1">
                                                <select id="target_denom"  >
                                                </select>
                                            </div>
                                            <div class="col-sm-1">
                                                <a type="button" class="btn btn-primary btn-xs" onclick="init_rates()">Init Rates</a>
                                                <a type="button" class="btn btn-primary btn-xs" onclick="delete_rates()">Del Rates</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- End rates -->

                    </div>
                </div>
                <div class="modal-footer" style="margin-right:15px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="save_detail()">Submit</button>
                </div>
            </div>
        </div>
    </div>
@stop
