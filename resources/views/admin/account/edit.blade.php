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

            // tooltip
            $('[data-toggle="tooltip"]').tooltip();

            var $validator = $("#frm_profile").validate({
                rules: {
                    name: {
                        required: true,
                        minlength: 3
                    },
                    type: {
                        required: true
                    },
                    tax_id: {
                        required: true
                    },
                    contact: {
                        required: true
                    },
                    office_number: {
                        required: true,
                        phone: true
                    },
                    email: {
                        required: true,
                        email: true,
                        maxlength: 255
                    },
                    status: {
                        required: true
                    },

                    address1: {
                        required: true
                    },
                    city: {
                        required: true
                    },
                    state: {
                        required: true
                    },
                    zip: {
                        required: true,
                        number: true,
                        minlength: 5,
                        maxlength: 5
                    },
                    pay_method: {
                        required: true
                    },
                    rate_plan_id: {
                        required: true
                    }
                },
                onsubmit: false
            });

            $('#rootwizard').bootstrapWizard({
                'withVisible': true,
                'onTabClick': function () {
                    return current_mode === 'edit'
                },
                'onNext': function(tab, navigation, index) {
                    var $valid = $("#frm_profile").valid();
                    if(!$valid) {
                        $validator.focusInvalid();
                        //myApp.showError('something is wrong');
                        return false;
                    }

                    var type = $('#type').val();
                    console.log(type);

                    console.log('index: ' + index);
                    console.log('tab: ' + tab);
                    console.log('navigation: ' + navigation);

                    if (index === 1) {
                        /*var res = confirm('You are about to create ' + $('#type option:selected').text() + '. Are you sure?');
                        if (!res) {
                            return false;
                        }*/

                        if (type === 'S') {
                            if ($('input[name^=store_type_id]:checked').length < 1) {
                                alert('Please select store type');
                                return false;
                            }
                        }

                        myApp.showConfirm('You are about to create ' + $('#type option:selected').text() + '. Are you sure?', function() {
                            $('#rootwizard').bootstrapWizard('show', 1)
                        }, function() {
                            return false;
                        });

                        return false;
                    }

                    if (index === 2) {
                        myApp.showLoading();
                        $.ajax({
                            url: '/admin/account/create/address-check',
                            data: {
                                _token: '{{ csrf_token() }}',
                                parent_id: {{ $p_account_id }},
                                type: $('#type').val(),
                                address1: $('#address1').val(),
                                address2: $('#address2').val(),
                                city: $('#city').val(),
                                state: $('#state').val(),
                                zip: $('#zip').val()
                            },
                            cache: false,
                            type: 'post',
                            dataType: 'json',
                            success: function(res) {
                                var next  = 2;
                                myApp.hideLoading();
                                if ($.trim(res.msg) !== '') {
                                    myApp.showConfirm(res.msg, function() {
                                        $('#rootwizard').bootstrapWizard('show', next)
                                    }, function() {
                                        return false;
                                    });
                                } else {
                                    $('#rootwizard').bootstrapWizard('show', next)
                                }
                            }
                        });

                        return false;
                    }

                    if (index === 4) {
                        var pay_method = $('#pay_method').val();
                        if (pay_method === 'C') {
                            var credit_limit = $('#credit_limit').val();
                            if ($.trim(credit_limit) === '') {
                                myApp.showError('Please enter credit limit');
                                $('#credit_limit').focus();
                                return false;
                            }

                            var pattern = /^\d+([.]\d{2})?$/;
                            if (!pattern.test(credit_limit)) {
                                myApp.showError('Please enter valid decimal number');
                                $('#credit_limit').focus();
                                return false;
                            }

                            var allow_cash_limit = $('#allow_cash_limit').val();
                            if ($.trim(allow_cash_limit) === '') {
                                myApp.showError('Please enter allow cash limit');
                                $('#allow_cash_limit').focus();
                                return false;
                            }

                            var min_ach_amt = $('#min_ach_amt').val();
                            if ($.trim(min_ach_amt) === '') {
                                myApp.showError('Please enter Minimum Payment Amount');
                                $('#min_ach_amt').focus();
                                return false;
                            }

                            var pattern = /^\d+([.]\d{2})?$/;
                            if (!pattern.test(allow_cash_limit)) {
                                myApp.showError('Please enter valid decimal number');
                                $('#allow_cash_limit').focus();
                                return false;
                            }

                            var pattern = /^\d+([.]\d{2})?$/;
                            if (!pattern.test(min_ach_amt)) {
                                myApp.showError('Please enter valid decimal number');
                                $('#min_ach_amt').focus();
                                return false;
                            }

                            var ach_bank = $('#ach_bank').val();
                            if ($.trim(ach_bank) === '') {
                                myApp.showError('Please enter ACH bank');
                                $('#ach_bank').focus();
                                return false;
                            }

                            var ach_holder = $('#ach_holder').val();
                            if ($.trim(ach_holder) === '') {
                                myApp.showError('Please enter ACH holder');
                                $('#ach_holder').focus();
                                return false;
                            }

                            var ach_routeno = $('#ach_routeno').val();
                            if ($.trim(ach_routeno) === '') {
                                myApp.showError('Please enter ACH routing #');
                                $('#ach_routeno').focus();
                                return false;
                            }

                            if (!validRoutingNumber(ach_routeno)) {
                                myApp.showError('Please enter valid ACH routing #');
                                $('#ach_routeno').focus();
                                return false;
                            }

                            var ach_acctno = $('#ach_acctno').val();
                            if ($.trim(ach_acctno) === '') {
                                myApp.showError('Please enter ACH account #');
                                $('#ach_acctno').focus();
                                return false;
                            }

                            pattern = /^\d{1,20}$/;
                            if (!pattern.test(ach_acctno)) {
                                myApp.showError('Please enter valid ACH account #: ' + ach_acctno);
                                $('#ach_acctno').focus();
                                return false;
                            }
                        }

                        var allow_cash_limit = $('#allow_cash_limit').val();
                        if ($.trim(allow_cash_limit) === '') {
                            myApp.showError('Please enter allow cash limit');
                            $('#allow_cash_limit').focus();
                            return false;
                        }

                        var min_ach_amt = $('#min_ach_amt').val();
                        if ($.trim(min_ach_amt) === '') {
                            myApp.showError('Please enter allow cash limit');
                            $('#min_ach_amt').focus();
                            return false;
                        }

                        var pattern = /^\d+([.]\d{2})?$/;
                        if (!pattern.test(allow_cash_limit)) {
                            myApp.showError('Please enter valid decimal number');
                            $('#allow_cash_limit').focus();
                            return false;
                        }

                        var pattern = /^\d+([.]\d{2})?$/;
                        if (!pattern.test(min_ach_amt)) {
                            myApp.showError('Please enter valid decimal number');
                            $('#min_ach_amt').focus();
                            return false;
                        }

                        var next  = type !== 'S' ? 5 : 3;
                        console.log('next: ' + next);
                        $('#rootwizard').bootstrapWizard('show', next)
                    }
                },
                'onPrevious': function(tab, navigation, index) {

                    console.log('index: ' + index);
                    console.log('tab: ' + tab);
                    console.log('navigation: ' + navigation);

                    var type = $('#type').val();
                    if ((index === 3 && type !== 'S')) {
                        $('#rootwizard').bootstrapWizard('show', 2);
                    }

                    if ((index === 4 && type !== 'S')) {
                        $('#rootwizard').bootstrapWizard('show', 3);
                    }

                    if (index === 4 && type === 'S') {
                        $('#rootwizard').bootstrapWizard('show', 5);
                    }

                    if ((index === 2 && type !== 'S')) {
                        $('#rootwizard').bootstrapWizard('show', 2);
                    }

                },
                'onFinish': function(tab, navigation, index) {
                    save_account_detail();
                }

            });

            @if (!empty($account_id))
            show_account_detail({{ $account_id }});
            @else
            show_account_detail();
            @endif
        };

        function reload_after_create(account_id) {
            window.location.href = "/admin/account/edit/{{ $p_account_id }}/" + account_id;
        }

        function save_account_detail() {

            var type = $('#type').val();
            if (type === 'S') {
                if ($('input[name^=store_type_id]:checked').length < 1) {
                    $('#rootwizard a[href="#profile"]').tab('show')
                    myApp.showError('Please select store type');
                    return;
                }
            }

            var url = current_mode === 'new' ? '/admin/account/create' : '/admin/account/update';

            myApp.showPleaseWait(current_mode);

            /* enable input & select for form posting */
            $('input, select').prop('disabled', false);

            $('#frm_profile').attr('action', url);
            $('#frm_profile').submit();

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

        function set_tab_layout(need_rate_plan) {

            var type = $('#type').val();
            $('#tab_products').show();
            if (type === 'S') {
                $('#tab_authority').show();
                $('#div_store_type').show();
            } else {
                $('#tab_authority').hide();
                $('#div_store_type').hide();
                // if (type === 'L') {
                //     $('#tab_products').hide();
                // }
            }

            if (need_rate_plan) {

                var parent_id = {{ $p_account_id }};

                $.ajax({
                    url: '/admin/account/get-rate-plans',
                    data: {
                        _token: '{!! csrf_token() !!}',
                        parent_id: parent_id,
                        type: type
                    },
                    cache: false,
                    type: 'post',
                    dataType: 'json',
                    success: function(res) {
                        if ($.trim(res.msg) === '') {

                            console.log('calling in set_tab_layout()')
                            bind_rate_plans(res);
                            bind_spiff_templates(res);

                            var id = $('#id').val();
                            var mode = $.trim(id) === '' ? 'new' : 'edit';
                            bind_pay_method(mode, type);
                        } else {
                            myApp.showError(res.msg);
                        }
                    }
                });
            }

        }

        function show_account_detail(id) {

            $('#new_parent_id').val('');
            $('#new_parent_name').val('');
            $('#new_rate_plan_id').empty();
            $('#new_rate_plan_id').append('<option value="N/A">Please select</option>');

            var mode = typeof id === 'undefined' ? 'new' : 'edit';
            var url = mode === 'new' ? '/admin/account/get-parent-info' : '/admin/account/get-account-info';
            var title = mode === 'new' ? 'New Account' : 'Account Detail';

            $('#title').text(title);

            if (mode === 'new') {
                id = {{ $p_account_id }};
                $('.modal-footer').hide();
                $('.pager').show();
                $('.edit').hide();
            } else {
                $('.modal-footer').show();
                $('.pager').hide();
                $('.edit').show();
            }

            $('#no_postpay_check').val("{{$m_account->no_postpay}}");

            current_mode = mode;


            myApp.showLoading();

            $.ajax({
                url: url,
                data: {
                    _token: '{{ csrf_token() }}',
                    id: id
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function (res) {
                    if (id == {{ Auth::user()->account_id }}) {
                        $('#esig_w9_box').show();
                        $('#esig_pr_sales_tax_box').show();
                        $('#esig_usuc_box').show();
                        $('#esig_ach_box').show();
                        $('#esig_h2o_dealer_box').show();
                        $('#esig_h2o_ach_box').show();
                    } else {
                        $('#esig_w9_box').hide();
                        $('#esig_pr_sales_tax_box').hide();
                        $('#esig_usuc_box').hide();
                        $('#esig_ach_box').hide();
                        $('#esig_h2o_dealer_box').hide();
                        $('#esig_h2o_ach_box').hide();
                    }

                    myApp.hideLoading();

                    if ($.trim(res.msg) === '') {
                        reset_form();

                        if (res.parent) {
                            $('#parent_id').val(res.parent.id);
                            $('#parent_name').val(res.parent.name);
                        }

                        $('#type').empty();
                        $('#type').append('<option value="">Please select</option>');


                        $.each(res.types, function (i, o) {
                            $('#type').append('<option value="' + o.code + '">' + o.name + '</option>');
                        });

                        $('#drp_type').empty();
                        $('#drp_type').append('<option value="">Please select</option>');

                        $('#rate_plan_id').empty();

                        $('#pay_method').prop('disabled', false);


                        $('#credit_limit').prop('readonly', true);
                        $('#allow_cash_limit').prop('readonly', true);
                        $('#min_ach_amt').prop('readonly', true);

                        $('.ach-info input, .ach-info select').prop('disabled', true);
                        $('#btn_edit_credit_limit').show();
                        $(".owned-rate-plan").show();
                        $('#div_current_balance').show();

                        if (res.account) {

                            $.each(res.rate_plan_types, function (i, o) {
                                $('#drp_type').append('<option value="' + o.code + '">' + o.name + '</option>');
                            });

                            bind_rate_plans(res);

                            bind_spiff_templates(res);

                            bind_default_spiff(res);

                            bind_account_shipping_fees(res);

                            $('#title').text('Account Detail : ' + res.account.name + ' ( ' + res.account.id + ' )');

                            $('#id').val(res.account.id);
                            $('#name').val(res.account.name);
                            $('#type').val(res.account.type);
                            $('#tax_id').val(res.account.tax_id);
                            $('#contact').val(res.account.contact);
                            $('#office_number').val(res.account.office_number);
                            $('#email').val(res.account.email);
                            $('#phone2').val(res.account.phone2);
                            $('#email2').val(res.account.email2);
                            $('#sales_email').val(res.account.sales_email);
                            $('#status').val(res.account.status);

                            $('#created_by').val(res.account.created_by);
                            $('#cdate').val(res.account.cdate);
                            $('#modified_by').val(res.account.modified_by);
                            $('#mdate').val(res.account.mdate);

                            $('#address1').val(res.account.address1);
                            $('#address2').val(res.account.address2);
                            $('#city').val(res.account.city);
                            $('#state').val(res.account.state);
                            $('#zip').val(res.account.zip);
                            $('#dealer_code').val(res.account.dealer_code);
                            $('#dealer_password').val(res.account.dealer_password);
                            $('#att_tid').val(res.account.att_tid);
                            $('#att_tid2').val(res.account.att_tid2);
                            $('#att_dealer_code').val(res.account.att_dealer_code);
                            $('#att_dc_notes').val(res.account.att_dc_notes);
                            $('#gen_processing_fee_l').val(res.account.gen_p_fee_l);
                            $('#gen_processing_fee_m').val(res.account.gen_p_fee_m);
                            $('#gen_processing_fee_d').val(res.account.gen_p_fee_d);
                            $('#gen_activation_fee_l').val(res.account.gen_a_fee_l);
                            $('#gen_activation_fee_m').val(res.account.gen_a_fee_m);
                            $('#gen_activation_fee_d').val(res.account.gen_a_fee_d);
                            // $('#esn_swap').val(res.account.esn_swap);
                            $('#esn_swap_num').val(res.account.esn_swap_num);
                            //$('#store_type_id').val(res.account.store_type_id);
                            $('#c_store').attr('checked', res.account.c_store == 'Y');
                            $('#show_discount_setup_report').attr('checked', res.account.show_discount_setup_report == 'Y');
                            $('#show_spiff_setup_report').attr('checked', res.account.show_spiff_setup_report == 'Y');
                            @if (in_array(Auth::user()->account_type, ['L']))
                            $('#rebates_eligibility').attr('checked', res.account.rebates_eligibility == 'Y');
                            $('#c_store').attr('checked', res.account.c_store == 'Y');
                            $('#show_discount_setup_report').attr('checked', res.account.show_discount_setup_report == 'Y');
                            $('#show_spiff_setup_report').attr('checked', res.account.show_spiff_setup_report == 'Y');

                            $('#notes').val(res.account.notes);
                            @endif

                            $('input[name^=store_type_id]').prop('checked', false);
                            $.each(res.account.store_types, function(i, n) {
                                $('input[name^=store_type_id][value=' + n.store_type_id + ']').prop('checked', true);
                            });

                            switch (res.account.type) {
                                case 'L':
                                    $('.credit-limit').hide();
                                    $('#div_store_type').hide();
                                    $('.sub-agent-only').hide();
                                    $('.no-sub-agent').show();
                                    // $('#tab_others').hide();
                                    $('.ach-info').hide();
                                    @if(Auth::user()->account_type == 'L' && in_array(Auth::user()->user_id,
                                    ['admin', 'thomas', 'system']))
                                    $('#tab_transfer').hide();
                                    @endif
                                        break;
                                case 'S':
                                    $('#div_store_type').show();
                                    if (res.account.pay_method === 'C') {
                                        $('.credit-limit').show();
                                        $('.ach-info').show();
                                    } else {
                                        $('.credit-limit').hide();
                                        $('.ach-info').hide();
                                    }

                                    $('.sub-agent-only').show();
                                    $('.no-sub-agent').hide();
                                    $('#tab_others').show();
                                    @if(Auth::user()->account_type == 'L' && in_array(Auth::user()->user_id,
                                    ['admin', 'thomas', 'system']))
                                    $('#tab_transfer').show();
                                    @endif
                                        break;
                                default:
                                    $('#div_store_type').hide();
                                    $('.ach-info').show();
                                    $('.credit-limit').show();
                                    $('.sub-agent-only').hide();
                                    $('.no-sub-agent').show();
                                    $('#tab_others').show();
                                    @if(Auth::user()->account_type == 'L' && in_array(Auth::user()->user_id,
                                    ['admin', 'thomas', 'system']))
                                    $('#tab_transfer').hide();
                                    @endif
                                        break;
                            }

                            $('#balance').text(parseFloat(res.account.balance).toFixed(2));

                            /* bind pay method first */
                            //$('#pay_method').prop('disabled', true);

                            // here
                            bind_pay_method('edit', res.account.type);
                            $('#pay_method').val(res.account.pay_method);
                            $('#orig_pay_method').val(res.account.pay_method);

                            $('#ach_bank').val(res.account.ach_bank);
                            $('#ach_holder').val(res.account.ach_holder);
                            $("#ach_routeno").val(res.account.ach_routeno);
                            $("#ach_acctno").val(res.account.ach_acctno);
                            $('#no_ach').prop('checked', res.account.no_ach === 'Y');
                            $('#ach_tue').prop('checked', res.account.ach_tue === 'Y');
                            $('#ach_wed').prop('checked', res.account.ach_wed === 'Y');
                            $('#ach_thu').prop('checked', res.account.ach_thu === 'Y');
                            $('#ach_fri').prop('checked', res.account.ach_fri === 'Y');
                            $('#no_postpay').prop('checked', res.account.no_postpay === 'Y');

                            if (res.account.rate_plan_id && res.account.type !== 'S') {
                                $('.owned-rate-plan').show();
                            } else {
                                $('.owned-rate-plan').hide();
                            }

                            if ({{ empty($account_id) ? $p_account_id : $account_id }} == 100000 || res.parent.act_h2o === 'Y') {
                                if (res.is_my_account == 'Y' && res.account.act_h2o !== 'Y') {
                                    $('#act_h2o_box').hide();
                                } else {
                                    $('#act_h2o_box').show();
                                    if (res.account.act_h2o === 'Y') {
                                        $('#act_h2o').prop('checked', true);
                                    } else {
                                        $('#act_h2o').prop('checked', false);
                                    }
                                    $('#h2o_min_month').val(res.account.h2o_min_month);

                                    if(res.account.h2o_hold_spiff === 'Y'){
                                        $('#h2o_hold_spiff').prop('checked', true);
                                    }else{
                                        $('#h2o_hold_spiff').prop('checked', false);
                                    }
                                }

                            } else {
                                $('#act_h2o_box').hide();
                            }

                            if ({{ empty($account_id) ? $p_account_id : $account_id }} == 100000 || res.parent.act_lyca === 'Y') {
                                if (res.is_my_account == 'Y' && res.account.act_lyca !== 'Y') {
                                    $('#act_lyca_box').hide();
                                } else {
                                    $('#act_lyca_box').show();
                                    if (res.account.act_lyca === 'Y') {
                                        $('#act_lyca').prop('checked', true);
                                    } else {
                                        $('#act_lyca').prop('checked', false);
                                    }
                                    $('#lyca_min_month').val(res.account.lyca_min_month);

                                    if(res.account.lyca_hold_spiff === 'Y'){
                                        $('#lyca_hold_spiff').prop('checked', true);
                                    }else{
                                        $('#lyca_hold_spiff').prop('checked', false);
                                    }
                                }
                            } else {
                                $('#act_lyca_box').hide();
                            }

                            if ({{ empty($account_id) ? $p_account_id : $account_id }} == 100000 || res.parent.act_att === 'Y') {
                                if (res.is_my_account == 'Y' && res.account.act_att !== 'Y') {
                                    $('#act_att_box').hide();
                                } else {
                                    $('#act_att_box').show();
                                    if (res.account.act_att === 'Y') {
                                        $('#act_att').prop('checked', true);
                                    } else {
                                        $('#act_att').prop('checked', false);
                                    }

                                    if (res.account.att_allow_byos === 'Y') {
                                        $('#att_allow_byos').prop('checked', true);
                                    } else {
                                        $('#att_allow_byos').prop('checked', false);
                                    }
                                    $('#att_byos_act_month').val(res.account.att_byos_act_month);
                                }
                            } else {
                                $('#act_att_box').hide();
                            }


                            if ({{ empty($account_id) ? $p_account_id : $account_id }} == 100000 || res.parent.act_freeup === 'Y') {
                                if (res.is_my_account == 'Y' && res.account.act_freeup !== 'Y') {
                                    $('#act_freeup_box').hide();
                                } else {
                                    $('#act_freeup_box').show();
                                    if (res.account.act_freeup === 'Y') {
                                        $('#act_freeup').prop('checked', true);
                                    } else {
                                        $('#act_freeup_box').prop('checked', false);
                                    }
                                    $('#freeup_min_month').val(res.account.freeup_min_month);

                                    if(res.account.freeup_hold_spiff === 'Y'){
                                        $('#freeup_hold_spiff').prop('checked', true);
                                    }else{
                                        $('#freeup_hold_spiff').prop('checked', false);
                                    }
                                }
                            } else {
                                $('#act_freeup_box').hide();
                            }


                            if ({{ empty($account_id) ? $p_account_id : $account_id }} == 100000 || res.parent.act_gen === 'Y') {
                                if (res.is_my_account == 'Y' && res.account.act_gen !== 'Y') {
                                    $('#act_gen_box').hide();
                                } else {
                                    $('#act_gen_box').show();
                                    if (res.account.act_gen === 'Y') {
                                        $('#act_gen').prop('checked', true);
                                    } else {
                                        $('#act_gen_box').prop('checked', false);
                                    }

                                    if (res.account.esn_swap === 'Y') {
                                        $('#esn_swap').prop('checked', true);
                                    } else {
                                        $('#esn_swap').prop('checked', false);
                                    }
                                    $('#gen_min_month').val(res.account.gen_min_month);
                                    $('#esn_swap_num').val(res.account.esn_swap_num);

                                    if(res.account.gen_hold_spiff === 'Y'){
                                        $('#gen_hold_spiff').prop('checked', true);
                                    }else{
                                        $('#gen_hold_spiff').prop('checked', false);
                                    }
                                }
                            } else {
                                $('#act_gen_box').hide();
                            }


                            if ({{ empty($account_id) ? $p_account_id : $account_id }} == 100000 || res.parent.act_liberty === 'Y') {
                                if (res.is_my_account == 'Y' && res.account.act_liberty !== 'Y') {
                                    $('#act_liberty_box').hide();
                                } else {
                                    $('#act_liberty_box').show();
                                    if (res.account.act_liberty === 'Y') {
                                        $('#act_liberty').prop('checked', true);
                                    } else {
                                        $('#act_liberty_box').prop('checked', false);
                                    }
                                    $('#liberty_min_month').val(res.account.liberty_min_month);

                                    if(res.account.liberty_hold_spiff === 'Y'){
                                        $('#liberty_hold_spiff').prop('checked', true);
                                    }else{
                                        $('#liberty_hold_spiff').prop('checked', false);
                                    }
                                }
                            } else {
                                $('#act_liberty_box').hide();
                            }

                            if ({{ empty($account_id) ? $p_account_id : $account_id }} == 100000 || res.parent.act_boom === 'Y') {
                                if (res.is_my_account == 'Y' && res.account.act_boom !== 'Y') {
                                    $('#act_boom_box').hide();
                                } else {
                                    $('#act_boom_box').show();
                                    if (res.account.act_boom === 'Y') {
                                        $('#act_boom').prop('checked', true);
                                    } else {
                                        $('#act_boom_box').prop('checked', false);
                                    }
                                    $('#boom_min_month').val(res.account.boom_min_month);

                                    if(res.account.boom_hold_spiff === 'Y'){
                                        $('#boom_hold_spiff').prop('checked', true);
                                    }else{
                                        $('#boom_hold_spiff').prop('checked', false);
                                    }
                                }
                            } else {
                                $('#act_boom_box').hide();
                            }

                            $('#credit_limit').val(res.account.credit_limit);
                            $('#allow_cash_limit').val(res.account.allow_cash_limit);
                            $('#min_ach_amt').val(res.account.min_ach_amt);

                            $('[name=posting_limit]').val(res.account.posting_limit);

                            bind_owned_plans(res.owned_plans, res.account.default_subagent_plan);

                            cancel_credit_limit(false);
                            if (res.can_edit_credit_info !== 'Y' || res.account.pay_method === 'P') {
                                $('#btn_edit_credit_limit').hide();
                            }

                            $('#status').prop('disabled', false);
                            if (res.can_edit_status !== 'Y') {
                                $('#status').prop('disabled', true);
                            }

                        } else {
                            //$('#tab_wallet').hide();
                            var type = $('#type').val();
                            if (type === 'S') {
                                $('#tab_others').show();
                            } else {
                                $('#tab_others').hide();
                            }

                            $('.ach-info input, .ach-info select').prop('disabled', false);

                            if (res.parent.act_h2o === 'Y') {
                                $('#act_h2o_box').show();
                                $('#act_h2o').prop('checked', false);
                            } else {
                                $('#act_h2o_box').hide();
                            }

                            if (res.parent.act_lyca === 'Y') {
                                $('#act_lyca_box').show();
                                $('#act_lyca').prop('checked', false);
                            } else {
                                $('#act_lyca_box').hide();
                            }

                            if (res.parent.act_att === 'Y') {
                                $('#act_att_box').show();
                                $('#act_att').prop('checked', false);
                                $('#att_allow_byos').prop('checked', false);
                                $('#att_byos_act_month').val('');
                            } else {
                                $('#act_att_box').hide();
                            }

                            if (res.parent.act_gen === 'Y') {
                                $('#act_gen_box').show();
                                $('#act_gen').prop('checked', false);
                                $('#esn_swap').prop('checked', false);
                                $('#esn_swap_num').val('');
                            } else {
                                $('#act_gen_box').hide();
                            }

                            if (res.parent.act_freeup === 'Y') {
                                $('#act_freeup_box').show();
                                $('#act_freeup_box').prop('checked', false);
                            } else {
                                $('#act_freeup_box').hide();
                            }

                            if (res.parent.act_liberty === 'Y') {
                                $('#act_liberty_box').show();
                                $('#act_liberty_box').prop('checked', false);
                            } else {
                                $('#act_liberty_box').hide();
                            }

                            if (res.parent.act_boom === 'Y') {
                                $('#act_boom_box').show();
                                $('#act_boom_box').prop('checked', false);
                            } else {
                                $('#act_boom_box').hide();
                            }

                            // here
                            $('#no_postpay_check').val(res.parent.no_postpay);
                            bind_pay_method('new', $('#type').val());

                            $('#credit_limit').prop('readonly', false);
                            $('#allow_cash_limit').prop('readonly', false);
                            $('#min_ach_amt').prop('readonly', false);

                            $('#btn_edit_credit_limit').hide();
                            $(".owned-rate-plan").hide();
                            $('#div_current_balance').hide();
                        }

                        set_tab_layout(false);

                        $('[id^=a_FILE_]').hide();
                        $('[id^=a_FILE_]').text('');
                        $('[id^=a_FILE_]').prop('href', '#');

                        $('[name^=_LOCKED]').hide();
                        $('[name^=_LOCKED]').prop('checked', false);

                        if (res.files) {
                            $.each(res.files, function(i, o) {
                                var link = $('#a_' + o.type);
                                link.show();
                                link.text('Uploaded on ' + o.cdate);
                                link.prop('href', '/file/view/' + o.id);

                                if (o.type === 'FILE_DEALER_AGREEMENT') {
                                    if (o.signed === 'Y') {
                                        $('input[type=file][name=FILE_DEALER_AGREEMENT]').hide();
                                        link.text('Uploaded on ' + o.cdate);
                                    } else {
                                        $('input[type=file][name=FILE_DEALER_AGREEMENT]').show();
                                        link.text('Please confirm your email first');
                                    }
                                }else if (o.type === 'FILE_W_9') {
                                    if (o.signed === 'N') {
                                        $('input[type=file][name=FILE_W_9]').hide();
                                        link.text('[Waiting for eSigniture approved] Uploaded on ' + o.cdate);
                                    }
                                }else if (o.type === 'FILE_PR_SALES_TAX') {
                                    if (o.signed === 'N') {
                                        $('input[type=file][name=FILE_PR_SALES_TAX]').hide();
                                        link.text('[Waiting for eSigniture approved] Uploaded on ' + o.cdate);
                                    }
                                }else if (o.type === 'FILE_USUC') {
                                    if (o.signed === 'N') {
                                        $('input[type=file][name=FILE_USUC]').hide();
                                        link.text('[Waiting for eSigniture approved] Uploaded on ' + o.cdate);
                                    }
                                }else if (o.type === 'FILE_ACH_DOC'){
                                    if (o.signed === 'N') {
                                        $('input[type=file][name=FILE_ACH_DOC]').hide();
                                        link.text('[Waiting for eSigniture approved] Uploaded on ' + o.cdate);
                                    }
                                }else if (o.type === 'FILE_H2O_DEALER_FORM'){
                                    if (o.signed === 'N') {
                                        $('input[type=file][name=FILE_H2O_DEALER_FORM]').hide();
                                        link.text('[Waiting for eSigniture approved] Uploaded on ' + o.cdate);
                                    }
                                }

                                var locked = $('[name=' + o.type + '_LOCKED]');
                                @if (\Auth::user()->account_id == 100000)
                                locked.show();
                                @endif
                                locked.prop('checked', o.locked == 'Y');
                            });
                        }

                        if (res.att_files) {
                            $.each(res.att_files, function(i, o) {
                                var link = $('#a_' + o.type);
                                link.show();
                                link.text('Uploaded on ' + o.cdate);
                                link.prop('href', '/file/att_view/' + o.id);

                                if (o.type === 'FILE_ATT_AGREEMENT') {

                                    $('input[type=file][name=FILE_ATT_AGREEMENT]').hide();

                                    if (o.signed == 'N'){
                                        $('input[type=file][name=FILE_ATT_AGREEMENT]').show();
                                        link.text('Please confirm your email first');
                                    }
                                }

                                var locked = $('[name=' + o.type + '_LOCKED]');
                                @if (\Auth::user()->account_id == 100000)
                                locked.show();
                                @endif
                                locked.prop('checked', o.locked == 'Y');
                            });
                        }

                        set_wallet_layout();

                        $('#div_account_detail').modal();
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


        function reset_form() {

            $('#rootwizard').bootstrapWizard('first');

            document.getElementById('frm_profile').reset();
        }

        function bind_account_shipping_fees(res) {

            console.log(res.account_shipping_fees);
            console.log(res.account_shipping_fees.length);

            $('#account_ship_info').empty();

            if(res.account_shipping_fees.length > 0) {

                $.each(res.account_shipping_fees, function(i, o) {

                    $('#account_ship_info').append(
                        '<label class="col-sm-3">Account Shipping Fee #'+(i+1)+'</label>'
                        + '<div class="col-sm-9">'
                        + '<input type="text" class="form-control" name="ship_fee_min_'+o.id+'" id="ship_fee_min_'+o.id+'" value="'+o.min_amt+'" style="width:100px;float:left;" disabled/>'
                        + '<label class="col-sm-1" style="text-align: center"> ~ </label>'
                        + '<input type="text" class="form-control" name="ship_fee_max_'+o.id+'" id="ship_fee_max_'+o.id+'" value="'+o.max_amt+'" style="width:100px;float:left;" disabled/>'
                        + '<label class="col-sm-1" style="text-align: right"> $ </label>'
                        + '<input type="text" class="form-control" name="ship_fee_'+o.id+'" id="ship_fee_'+o.id+'" value="'+o.fee+'" style="width:100px;float:left;" disabled/>'
                        + '<label class="col-sm-1"></label>'
                        + '<button type="button" class="btn btn-primary btn-xs" onclick="delete_account_shipping_fee('+o.id+', '+res.account.id+')" style="width:60px;float:left;">Delete</button>'
                        + '</div>');

                });

                $('#account_ship_info').append(
                    '<label class="col-sm-3"></label>'
                    + '<div class="col-sm-9">'
                    + '<input type="text" class="form-control" name="ship_fee_min" id="ship_fee_min" value="" style="width:100px;float:left;" />'
                    + '<label class="col-sm-1" style="text-align: center"> ~ </label>'
                    + '<input type="text" class="form-control" name="ship_fee_max" id="ship_fee_max" value="" style="width:100px;float:left;" />'
                    + '<label class="col-sm-1" style="text-align: right"> $ </label>'
                    + '<input type="text" class="form-control" name="ship_fee" id="ship_fee" value="" style="width:100px;float:left;" />'
                    + '<label class="col-sm-1"></label>'
                    + '<button type="button" class="btn btn-primary btn-xs" onclick="add_account_shipping_fee('+res.account.id+')" style="width:60px;float:left;">ADD</button>'
                    + '</div>');

            }else{
                $('#account_ship_info').append(
                    '<label class="col-sm-3">Account Shipping Fee</label>'
                    + '<div class="col-sm-9">'
                    + '<input type="text" class="form-control" name="ship_fee_min" id="ship_fee_min" value="" style="width:100px;float:left;" placeholder="Min Amount"/>'
                    + '<label class="col-sm-1" style="text-align: center"> ~ </label>'
                    + '<input type="text" class="form-control" name="ship_fee_max" id="ship_fee_max" value="" style="width:100px;float:left;" placeholder="Max Amount"/>'
                    + '<label class="col-sm-1" style="text-align: right"> $ </label>'
                    + '<input type="text" class="form-control" name="ship_fee" id="ship_fee" value="" style="width:100px;float:left;" placeholder="Shipping Fee"/>'
                    + '<label class="col-sm-1"></label>'
                    + '<button type="button" class="btn btn-primary btn-xs" onclick="add_account_shipping_fee('+res.account.id+')" style="width:60px;float:left;">ADD</button>'
                    + '</div>');
            }
        }

        function bind_rate_plans(res) {
            $('#rate_plan_id').empty();

            console.log(res.rate_plans);
            console.log(res.rate_plans.length);
            if (res.rate_plans.length > 0) {
                if (res.account && res.account.id !== res.login_account_id && $.trim(res.account.rate_plan_id) === '') {
                    $('#rate_plan_id').append('<option value="">Not Assigned Yet.</option>');
                }

                $.each(res.rate_plans, function(i, o) {
                    $('#rate_plan_id').append('<option value="' + o.id + '" ' + (res.account && res.account.rate_plan_id === o.id ? 'selected' : '') + '>' + o.name + ' ( ' + o.id + ' )</option>');
                });
            } else {
                if(res.account.type == 'L'){
                    $('#rate_plan_id').append('<option value="1" selected>Root Plan (1)</option>');
                }else {
                    $('#rate_plan_id').append('<option value="">Unable to load assignable rate plans. Please ask your parent account for more detail.</option>');
                }
            }
        }

        function bind_default_spiff(res) {
            console.log(res.default_spiff);
            console.log(res.default_spiff.length);

            if(res.default_spiff.length > 0) {

                $.each(res.default_spiff, function(i, o) {

                    if(res.default_sub_spiff != null && res.default_sub_spiff.spiff_id == o.id){
                        $('#default_spiff').append(
                            '<option value="' + o.id + '" '
                            + (res.default_sub_spiff.spiff_id === o.id ? 'selected' : '') + '>'
                            + o.template
                            + '</option>');
                    }else {
                        $('#default_spiff').append('<option value="' + o.id + '" >' + o.template + '</option>');
                    }

                });
            }

        }

        function bind_spiff_templates(res) {
            console.log(res.spiff_templates);
            console.log(res.spiff_templates.length);
            if (res.spiff_templates.length > 0) {
                $('.spiff_template').show();
                $('#spiff_template').empty();

                @if (Auth::user()->account_id == 100000)

                $('#spiff_template').append('<option value="" ' + (!res.account.spiff_template ? 'selected' : '') + '>Select</option>');

                @endif

                $.each(res.spiff_templates, function(i, o) {

                    // All
                    if (res.is_my_account == 'Y') {
                        if (res.account.spiff_template == o.id) {
                            $('#spiff_template').empty();
                            $('#spiff_template').append('<option value="' + o.id + '" '
                                + (res.account && res.account.spiff_template === o.id ? 'selected' : '') + '>' + o.template + '</option>');
                        }
                    } else {
                        $('#spiff_template').append('<option value="' + o.id + '" '
                            + (res.account && res.account.spiff_template === o.id ? 'selected' : '') + '>' + o.template + '</option>');
                    }

                });

            } else {
                $('.spiff_template').hide();
            }
        }

        function remove_plan(id) {

            myApp.showConfirm('Are you sure?', function() {
                myApp.showLoading();
                $.ajax({
                    url: '/admin/account/remove_plan',
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
                            myApp.showSuccess('Your request has been processed successfully!');
                            load_owned_plans();
                        } else {
                            myApp.showError(res.msg)
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        myApp.hideLoading();
                        myApp.showError(errorThrown);
                    }
                });
            });
        }

        /* need to show wallet tab on account creation */
        function bind_pay_method(mode, type) {

            var options = '<option value="">Please Select</option>';

            if(mode === 'new'){
                if(type === 'S'){
                    options += '<option value="P">Prepay</option>';
                    if($('#no_postpay_check').val() != 'Y'){
                        options += '<option value="C">Credit</option>';
                    }
                }else{
                    options += '<option value="C">Credit</option>';
                }
            }else{
                if(type === 'S'){
                    options += '<option value="P">Prepay</option>';
                    if ($('#no_postpay_check').val() != 'Y') {
                        options += '<option value="C">Credit</option>';
                    }
                }else{
                    options += '<option value="C">Credit</option>';
                }
            }

            $('#pay_method').empty();
            $('#pay_method').append(options);
        }

        function bind_owned_plans(owned_plans, default_subagent_plan) {
            var tbody = $('#tbody_owned_plans');
            tbody.empty();
            if (owned_plans.length > 0) {
                $.each(owned_plans, function(i, o) {

                    var html = '<tr>';
                    html += '<td>' + o.id + '</td>';
                    html += '<td>' + o.type_img + '</td>';
                    html += '<td><a href="javascript:load_rate_plan(' + o.id + ');">' + o.name + ' ( ' + o.id + ' )</a></td>';
                    html += '<td>' + o.last_updated + '</td>';
                    html += '<td>';
                    html +=     '<button type="button" class="btn btn-info btn-xs" onclick="show_rate_detail(' + o.id + ', \'O\')">View Detail</button>&nbsp;';
                    //html +=     '<button type="button" class="btn btn-primary btn-xs" onclick="copy_rate_plan(' + o.id + ')">Copy</button>&nbsp;';

                    if (o.assigned_qty === 0) {
                        html +=     '<button type="button" class="btn btn-error btn-xs" onclick="remove_plan(' + o.id + ')">Remove</button>';
                    }

                    html += '</td>';
                    html += '</tr>';

                    tbody.append(html);

                    /// bind Default Sub agent Plans ///
                    if(o.type == 'S') {
                        $('#default_subagent_plan').append('<option value="' + o.id + '" ' + (default_subagent_plan === o.id ? 'selected' : '') + '>' + o.name + ' ( ' + o.id + ' )</option>');
                    }
                });
            } else {
                tbody.append('<tr><td colspan="5">No Record Found</td></tr>');
            }

            tbody.append('<tr><td colspan="5" class="text-right"><button type="button" onclick="show_rate_plan()" class="btn btn-default btn-xs">Add New</button></td></tr>')
        }

        function cancel_credit_limit(reset_values) {
            $('.ach-info input').prop('disabled', true);
            $('#credit_limit').prop('readonly', true);
            $('#allow_cash_limit').prop('readonly', true);
            $('#min_ach_amt').prop('readonly', true);

            if (reset_values) {
                $('#credit_limit').val(old_credit_limit);
                $('#allow_cash_limit').val(old_allow_cash_limit);
                $('#min_ach_amt').val(old_min_ach_amt);

                $('#ach_bank').val(old_ach_bank);
                $("#ach_holder").val(old_ach_holder);
                $("#ach_routeno").val(old_ach_routeno);
                $("#ach_acctno").val(old_ach_acctno);
                $('#no_ach').prop('checked', old_no_ach === 'Y');
                $('#ach_tue').prop('checked', old_ach_tue === 'Y');
                $('#ach_wed').prop('checked', old_ach_wed === 'Y');
                $('#ach_thu').prop('checked', old_ach_thu === 'Y');
                $('#ach_fri').prop('checked', old_ach_fri === 'Y');
                $('#no_postpay').prop('checked', old_no_postpay === 'Y');
            }

            $('#btn_edit_credit_limit').show();
            $('#btn_update_credit_info').hide();
            $('#btn_cancel_credit_limit').hide();
        }

        function set_wallet_layout() {
            var pay_method = $('#pay_method').val();
            var orig_pay_method = $('#orig_pay_method').val();
            var account_id = $('#id').val();

            var mode = $.trim(account_id) === '' ? 'new' : 'edit';
            if (mode === 'edit') {
                $('.ach-info input, .ach-info select').prop('disabled', true);
                //$('#credit_limit').prop('readonly', true);
            }

            $('.weekday-ach').show();

            switch (pay_method) {
                case 'P':
                    $('.ach-info').hide();
                    $('.credit-limit').hide();
                    $('#div_current_balance').hide();
                    $('#btn_edit_credit_limit').show();
                    $('#allow_cash_limit').attr('readonly', false);
                    $('#min_ach_amt').attr('readonly', false);

                    break;
                case 'C':
                    $('.ach-info').show();
                    $('.credit-limit').show();
                    if (mode === 'edit') {
                        $('#div_current_balance').show();
                    } else {
                        console.log('hiding current balance div');
                        console.log('mode: ' + mode);
                        console.log('acct_id: ' + account_id);
                        $('#div_current_balance').hide();
                    }

                    if (mode === 'edit' && orig_pay_method === 'P') {
                        $('.ach-info input, .ach-info select').prop('disabled', false);
                        //$('#credit_limit').prop('readonly', false);
                    }

                    break;
            }

            var type = $.trim($('#type').val());
            if (type !== 'S') {
                $('.weekday-ach').hide();
            }

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

        function send_email() {

            myApp.showConfirm(
                'Are you sure to send Email?',
                function() {
                    myApp.showLoading();
                    $.ajax({
                        url: '/admin/account/edit/send_welcome_email',
                        data: {
                            _token: '{{ csrf_token() }}',
                            account_id: '{{ $account->id }}',
                            cc_email: $('#cc_email').val(),
                            welcome_email: $('#welcome_email').val()
                        },
                        cache: false,
                        type: 'post',
                        dataType: 'json',
                        success: function(res) {
                            myApp.hideLoading();
                            if ($.trim(res.msg) === '') {
                                myApp.showSuccess('Your request has been processed successfully!');
                            } else {
                                myApp.showError(res.msg);
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            myApp.hideLoading();
                            myApp.showError(errorThrown);
                        }
                    });
                },
                function() {
                    return;
                }
            )
        }

        function show_rate_detail(rate_plan_id, show_type) {
            $('#cur_rate_plan').val(rate_plan_id);
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

        function show_rate_detail_excel() {

            $('#frm_wallet').submit();
        }

        function save_rate_plan() {

            var mode = typeof current_rate_plan_id === 'undefined' ? 'new' : 'edit';
            var url = '/admin/account/rate-plan/update';
            if (mode === 'new') {
                url = '/admin/account/rate-plan/add';
            }

            myApp.showLoading();

            $.ajax({
                url: url,
                data: {
                    _token: '{!! csrf_token() !!}',
                    id: current_rate_plan_id,
                    owner_id: {{ empty($account_id) ? $p_account_id : $account_id }},
                    type: $('#drp_type').val(),
                    name: $('#drp_name').val(),
                    copy_from: $('#drp_copy_from').val(),
                    status: $('#drp_status').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function (res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        myApp.showSuccess('Your request has been processed successfully!');
                        $('#div_rate_plan').modal('hide');
                        load_owned_plans();
                    } else {
                        myApp.showError(res.msg)
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });
        }

        function load_owned_plans() {
            myApp.showLoading();

            $.ajax({
                url: '/admin/account/rate-plan/load-owned-plans',
                data: {
                    _token: '{!! csrf_token() !!}',
                    owner_id: {{ empty($account_id) ? $p_account_id : $account_id }}
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        bind_owned_plans(res.owned_plans);
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

        function show_spiff_template_detail(template_id, carrier) {

            if ($.trim(template_id) == '') {
                return;
            }

            myApp.showLoading();

            $.ajax({
                url: '/admin/account/spiff-detail/load',
                data: {
                    _token: '{{ csrf_token() }}',
                    template_id: template_id,
                    carrier: carrier
                },
                cache: false,
                type: 'post',
                dataType: 'html',
                success: function(res) {
                    myApp.hideLoading();

                    $('#div_spiff_template_detail_body').empty();
                    $('#div_spiff_template_detail_body').append(res);

                    $('#div_spiff_template_detail').modal();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });
        }

        var old_credit_limit = null;
        var old_allow_cash_limit = null;
        var old_min_ach_amt = null;
        var old_ach_bank = null;
        var old_ach_holder = null;
        var old_ach_routeno = null;
        var old_ach_acctno = null;
        var old_no_ach = null;
        var old_ach_tue = null;
        var old_ach_wed = null;
        var old_ach_thu = null;
        var old_ach_fri = null;
        var old_no_ach = null;
        var old_no_postpay = null;

        function edit_credit_limit() {

            $('.ach-info input').prop('disabled', false);
            $('#credit_limit').attr('readonly', false);
            $('#allow_cash_limit').attr('readonly', false);
            $('#min_ach_amt').attr('readonly', false);

            old_credit_limit = $('#credit_limit').val();
            old_allow_cash_limit = $('#allow_cash_limit').val();
            old_min_ach_amt = $('#min_ach_amt').val();
            old_ach_bank = $("#ach_bank").val();
            old_ach_holder = $('#ach_holder').val();
            old_ach_routeno = $('#ach_routeno').val();
            old_ach_acctno = $('#ach_acctno').val();
            old_no_ach = $('#no_ach').is(':checked') ? 'Y' : 'N';
            old_ach_tue = $('#ach_tue').is(':checked') ? 'Y' : 'N';
            old_ach_wed = $('#ach_wed').is(':checked') ? 'Y' : 'N';
            old_ach_thu = $('#ach_thu').is(':checked') ? 'Y' : 'N';
            old_ach_fri = $('#ach_fri').is(':checked') ? 'Y' : 'N';
            old_no_postpay = $('#no_postpay').is(':checked') ? 'Y' : 'N';

            $('#btn_edit_credit_limit').hide();
            $('#btn_update_credit_info').show();
            $('#btn_cancel_credit_limit').show();
        }

        function cancel_credit_limit(reset_values) {
            $('.ach-info input').prop('disabled', true);
            $('#credit_limit').prop('readonly', true);
            $('#allow_cash_limit').prop('readonly', true);
            $('#min_ach_amt').prop('readonly', true);

            if (reset_values) {
                $('#credit_limit').val(old_credit_limit);
                $('#allow_cash_limit').val(old_allow_cash_limit);
                $('#min_ach_amt').val(old_min_ach_amt);
                $('#ach_bank').val(old_ach_bank);
                $("#ach_holder").val(old_ach_holder);
                $("#ach_routeno").val(old_ach_routeno);
                $("#ach_acctno").val(old_ach_acctno);
                $('#no_ach').prop('checked', old_no_ach === 'Y');
                $('#ach_tue').prop('checked', old_ach_tue === 'Y');
                $('#ach_wed').prop('checked', old_ach_wed === 'Y');
                $('#ach_thu').prop('checked', old_ach_thu === 'Y');
                $('#ach_fri').prop('checked', old_ach_fri === 'Y');
                $('#no_postpay').prop('checked', old_no_postpay === 'Y');
            }

            $('#btn_edit_credit_limit').show();
            $('#btn_update_credit_info').hide();
            $('#btn_cancel_credit_limit').hide();
        }

        function update_credit_info() {
            var credit_limit = $('#credit_limit');
            var allow_cash_limit = $('#allow_cash_limit');
            var min_ach_amt = $('#min_ach_amt');

            var ach_bank = $('#ach_bank');
            var ach_holder = $('#ach_holder');
            var ach_routeno = $('#ach_routeno');
            var ach_acctno = $("#ach_acctno");
            var no_ach = $('#no_ach').is(':checked') ? 'Y' : 'N';
            var ach_tue = $('#ach_tue').is(':checked') ? 'Y' : 'N';
            var ach_wed = $('#ach_wed').is(':checked') ? 'Y' : 'N';
            var ach_thu = $('#ach_thu').is(':checked') ? 'Y' : 'N';
            var ach_fri = $('#ach_fri').is(':checked') ? 'Y' : 'N';
            var no_postpay = $('#no_postpay').is(':checked') ? 'Y' : 'N';

            if ($.trim(credit_limit.val()) === '') {
                myApp.showError('Please enter credit limit');
                credit_limit.focus();
                return;
            }

            var pattern = /^\d+([.]\d{2})?$/;
            if (!pattern.test(credit_limit.val())) {
                myApp.showError('Please enter valid decimal number');
                credit_limit.focus();
                return;
            }

            if ($.trim(allow_cash_limit.val()) === '') {
                myApp.showError('Please enter allow cash limit');
                allow_cash_limit.focus();
                return;
            }

            var pattern = /^\d+([.]\d{2})?$/;
            if (!pattern.test(allow_cash_limit.val())) {
                myApp.showError('Please enter valid decimal number');
                allow_cash_limit.focus();
                return;
            }

            if ($.trim(min_ach_amt.val()) === '') {
                myApp.showError('Please enter allow cash limit');
                min_ach_amt.focus();
                return;
            }

            var pattern = /^\d+([.]\d{2})?$/;
            if (!pattern.test(min_ach_amt.val())) {
                myApp.showError('Please enter valid decimal number');
                min_ach_amt.focus();
                return;
            }

            if ($.trim(ach_bank.val()) === '') {
                myApp.showError('Please enter ACH bank');
                ach_bank.focus();
                return;
            }

            if ($.trim(ach_holder.val()) === '') {
                myApp.showError('Please enter ACH holder');
                ach_holder.focus();
                return false;
            }

            if ($.trim(ach_routeno.val()) === '') {
                myApp.showError('Please enter ACH routing #');
                ach_routeno.focus();
                return;
            }

            if (!validRoutingNumber(ach_routeno.val())) {
                myApp.showError('Please enter valid ACH routing #');
                ach_routeno.focus();
                return;
            }

            if ($.trim(ach_acctno.val()) === '') {
                myApp.showError('Please enter ACH account #');
                ach_acctno.focus();
                return;
            }

            pattern = /^\d{1,20}$/;
            if (!pattern.test(ach_acctno.val())) {
                myApp.showError('Please enter valid ACH account #');
                ach_acctno.focus();
                return;
            }

            //var new_value = credit_limit.val();
            var account_id = $('#id').val();

            myApp.showLoading();
            $.ajax({
                url: '/admin/account/credit-info/update',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: account_id,
                    credit_limit: credit_limit.val(),
                    allow_cash_limit: allow_cash_limit.val(),
                    min_ach_amt: min_ach_amt.val(),

                    ach_bank: ach_bank.val(),
                    ach_holder: ach_holder.val(),
                    ach_routeno: ach_routeno.val(),
                    ach_acctno: ach_acctno.val(),
                    no_ach: no_ach,
                    ach_tue: ach_tue,
                    ach_wed: ach_wed,
                    ach_thu: ach_thu,
                    ach_fri: ach_fri,
                    no_postpay: no_postpay
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        myApp.showSuccess('Your request has been processed successfully!',function() {
                            old_credit_limit = credit_limit.val();
                            old_allow_cash_limit = allow_cash_limit.val();
                            old_min_ach_amt = min_ach_amt.val();
                            old_ach_bank = ach_bank.val();
                            old_ach_holder= ach_holder.val();
                            old_ach_routeno = ach_routeno.val();
                            old_ach_acctno = ach_acctno.val();
                            old_no_ach = no_ach;
                            old_ach_tue = ach_tue;
                            old_ach_wed = ach_wed;
                            old_ach_thu = ach_thu;
                            old_ach_fri = ach_fri;
                            old_no_postpay = no_postpay;

                            cancel_credit_limit(true);
                            $('#balance').text(parseFloat(res.balance).toFixed(2));
                        });
                    } else {
                        myApp.showError(res.msg, function() {
                            credit_limit.val(old_credit_limit);
                            allow_cash_limit.val(old_allow_cash_limit);
                            min_ach_amt.val(old_min_ach_amt);
                            ach_bank.val(old_ach_bank);
                            ach_holder.val(old_ach_holder);
                            ach_routeno.val(old_ach_routeno);
                            ach_acctno.val(old_ach_acctno);
                            $('#no_ach').prop('checked', old_no_ach === 'Y');
                            $('#ach_tue').prop('checked', old_ach_tue === 'Y');
                            $('#ach_wed').prop('checked', old_ach_wed === 'Y');
                            $('#ach_thu').prop('checked', old_ach_thu === 'Y');
                            $('#ach_fri').prop('checked', old_ach_fri === 'Y');
                            $('#no_postpay').prop('checked', old_no_postpay === 'Y');

                            cancel_credit_limit(true);
                        });
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    credit_limit.val(old_value);
                    // allow_cash_limit.val(old_value);
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });
        }


        @if(Auth::user()->account_type == 'L' && in_array(Auth::user()->user_id, ['admin', 'thomas', 'system']))
        function load_parent_account_info() {
            myApp.showLoading();

            $.ajax({
                url: '/admin/account/load_parent_account_info',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: $('#new_parent_id').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function (res) {
                    myApp.hideLoading();

                    if ($.trim(res.msg) === '') {
                        if (res.account) {

                            $('#new_parent_name').val(res.account.name);
                            $('#new_rate_plan_id').empty();

                            $('#new_rate_plan_id').append('<option value="">Please select</option>');

                            if (res.rate_plans.length > 0) {
                                $.each(res.rate_plans, function (i, o) {
                                    $('#new_rate_plan_id').append('<option value="' + o.id + '" ' + (res.account && res.account.rate_plan_id === o.id ? 'selected' : '') + '>' + o.name + ' ( ' + o.id + ' )</option>');
                                });
                            } else {
                                if(res.account.type == 'L'){
                                    $('#rate_plan_id').append('<option value="1" selected>Root Plan (1)</option>');
                                }else {
                                    $('#new_rate_plan_id').append('<option value="">Unable to load assignable rate plans. Please ask your parent account for more detail.</option>');
                                }
                            }
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


        function parent_transfer() {
            myApp.showLoading();

            $.ajax({
                url: '/admin/account/parent_transfer',
                data: {
                    _token: '{{ csrf_token() }}',
                    acct_id: $('#id').val(),
                    parent_acct_id: $('#new_parent_id').val(),
                    rate_plan_id: $('#new_rate_plan_id').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function (res) {
                    myApp.hideLoading();

                    if ($.trim(res.msg) === '') {
                        $('#div_user_detail').modal('hide');
                        myApp.showSuccess('Parent transferred  successfully', function() {
                            window.location.href = "/admin/account/edit/" + $('#new_parent_id').val() + "/" +
                                $('#id').val();
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

        function set_activation_controler($carrier) {
            myApp.showLoading();

            $.ajax({
                url: '/admin/account/activation_controller',
                data: {
                    _token: '{{ csrf_token() }}',
                    account_id: '{{ $account->id }}',
                    carrier: $carrier
                },
                cache: false,
                type: 'get',
                dataType: 'json',
                success: function (res) {
                    myApp.hideLoading();

                    if (res.code == '0') {
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

        function add_account_shipping_fee(account_id) {

            if($('#ship_fee_min').val().length < 1){
                alert('Please Insert Minimum Amount');
                return;
            }
            if($('#ship_fee_max').val().length < 1){
                alert('Please Insert Maximum Amount');
                return;
            }
            if($('#ship_fee_min').val().length < 1){
                alert('Please Insert Minimum Amount');
                return;
            }
            if($('#ship_fee').val().length < 1){
                alert('Please Insert Shipping Fee');
                return;
            }

            myApp.showLoading();
            $.ajax({
                url: '/admin/account/add_account_shipping_fee',
                data: {
                    _token: '{!! csrf_token() !!}',
                    account_id: account_id,
                    ship_fee_min: $('#ship_fee_min').val(),
                    ship_fee_max: $('#ship_fee_max').val(),
                    ship_fee: $('#ship_fee').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();

                    if ($.trim(res.msg) === '') {
                        myApp.showSuccess('Your request has been processed successfully!', function() {

                            show_account_detail(account_id);
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

        function delete_account_shipping_fee(account_ship_fee_id, account_id) {

            myApp.showLoading();
            $.ajax({
                url: '/admin/account/delete_account_shipping_fee',
                data: {
                    _token: '{!! csrf_token() !!}',
                    account_ship_fee_id: account_ship_fee_id
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();

                    if ($.trim(res.msg) === '') {
                        myApp.showSuccess('Your request has been processed successfully!', function() {

                            show_account_detail(account_id);
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

        @endif

    </script>

    <h4>Account Edit</h4>

    <form id="frm_profile" class="form-horizontal filter" method="post" target="ifm_upload" enctype="multipart/form-data" style="padding:15px;">
        {{ csrf_field() }}
        <input type="hidden" id="orig_pay_method" name="orig_pay_method"/>
        <input type="hidden" id="no_postpay_check" name="no_postpay_check"/>
        <div id="rootwizard">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="active">
                    <a href="#profile" aria-controls="profile"role="tab" data-toggle="tab">Profile</a>
                </li>
                <li role="presentation">
                    <a href="#address" aria-controls="address" role="tab" data-toggle="tab">Address</a>
                </li>
                <li role="presentation" id="tab_products">
                    <a href="#products" aria-controls="products" role="tab" data-toggle="tab">Activation Products</a>
                </li>
                <li role="presentation" id="tab_wallet">
                    <a href="#wallet" area-controls="wallet" role="tab" data-toggle="tab">Wallet</a>
                </li>
                <li role="presentation" id="tab_others">
                    <a href="#others" area-controls="others" role="tab" data-toggle="tab">Others</a>
                </li>
                <li role="presentation">
                    <a href="#forms" aria-controls="forms" role="tab" data-toggle="tab">Forms</a>
                </li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content">

                <div role="tabpanel" class="tab-pane active" id="profile" style="padding:15px;">

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Account ID: </label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" name="id" id="id" readonly/>
                        </div>
                        <label class="col-sm-2 control-label">Account Name: </label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" name="name" id="name"/>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Type: </label>
                        <div class="col-sm-4">
                            <select class="form-control" name="type" id="type" onchange="set_tab_layout(true)">
                                @if ($p_account->type == 'L')
                                <option value="M">Master</option>
                                @endif
                                @if ($p_account->type == 'L' || $p_account->type == 'M')
                                <option value="D">Distributor</option>
                                @endif
                                <option value="S">Sub-Agent</option>
                            </select>
                        </div>
                        <label class="col-sm-2 control-label">Tax ID: </label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" name="tax_id" id="tax_id"/>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Parent ID: </label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" name="parent_id" id="parent_id"
                                   value="{{ $p_account->id }}" readonly/>
                        </div>
                        <label class="col-sm-2 control-label">Parent Name: </label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" name="parent_name"
                                   id="parent_name" value="{{ $p_account->name }}" readonly/>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Contact Name: </label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" name="contact" id="contact"/>
                        </div>
                        <label class="col-sm-2 control-label">Office Number: </label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" name="office_number" maxlength="10"
                                   id="office_number" placeholder="10 digits"/>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Email: </label>
                        <div class="col-sm-4">
                            <input type="email" class="form-control" name="email" id="email"/>
                        </div>
                        <label class="col-sm-2 control-label">Phone2: </label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" name="phone2" maxlength="10"
                                   id="phone2" placeholder="10 digits"/>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Email2: </label>
                        <div class="col-sm-4">
                            <input type="email" class="form-control" name="email2" id="email2"/>
                        </div>
                        <label class="col-sm-2 control-label">Status: </label>
                        <div class="col-sm-4">
                            <select class="form-control" name="status" id="status">
                                <option value="A">Active</option>
                                <option value="H">On-Hold</option>
                                <option value="P">Pre-Auth</option>
                                <option value="F">Failed Payment</option>
                                <option value="C">Closed</option>
                                <option value="B">Become Dealer</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Sales Email: </label>
                        <div class="col-sm-4">
                            <input type="email" class="form-control" name="sales_email" id="sales_email"/>
                        </div>
                        <label class="col-sm-2 control-label"></label>
                        <div class="col-sm-4">
                        </div>
                    </div>
                    <div class="form-group" id="div_store_type" style="display:none">
                        <label class="col-sm-2 control-label">Store Type: </label>
                        <div class="col-sm-10">
                            @foreach ($store_types as $o)
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="store_type_id[]" value="{{ $o->id }}"/> {{ $o->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="form-group edit">
                        <label class="col-sm-2 control-label">Created.By: </label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" name="created_by" id="created_by" readonly/>
                        </div>
                        <label class="col-sm-2 control-label">Created.At: </label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" name="cdate"
                                   id="cdate" readonly/>
                        </div>
                    </div>
                    <div class="form-group edit">
                        <label class="col-sm-2 control-label">Modified.By: </label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" name="modified_by" id="modified_by" readonly/>
                        </div>
                        <label class="col-sm-2 control-label">Modified.At: </label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" name="mdate" id="mdate"
                                   readonly/>
                        </div>
                    </div>

                    @if(Auth::user()->account_type == 'L')
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Notes: </label>
                            <div class="col-sm-10">
                                <textarea class="form-control" name="notes" id="notes"></textarea>
                            </div>
                        </div>
                    @endif

                    @if (!empty($account_id))
                    @if(Auth::user()->account_type == 'L' && in_array(Auth::user()->user_id, ['admin',
'thomas' , 'system']))
                        <div style="border:1px solid #ccc;padding: 16px;">
                            <h5>Parent Transfer</h5>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">Parent ID: </label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control" id="new_parent_id"
                                           onchange="load_parent_account_info()"/>
                                </div>
                                <label class="col-sm-2 control-label">Parent Name: </label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control" id="new_parent_name" readonly/>
                                </div>
                            </div>


                            <div class="form-group">
                                <label class="col-sm-2 control-label">Rate Plan</label>
                                <div class="col-sm-4">
                                    <select name="rate_plan_id" id="new_rate_plan_id" class="form-control">
                                        <option value="">Please Select</option>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <button type="button" class="btn btn-primary btn-xs"
                                            onclick="show_rate_detail($('#new_rate_plan_id').val(), 'M')">View
                                        Plan Detail</button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2 control-label"></label>
                                <div class="col-sm-4">
                                    <a type="button" class="btn btn-primary" onclick="parent_transfer()">Parent Transfer Submit</a>
                                </div>
                            </div>

                        </div>
                    @endif
                    @endif
                </div>

                <div role="tabpanel" class="tab-pane" id="address" style="padding:15px;">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Address 1: </label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" name="address1" id="address1"/>
                        </div>
                        <label class="col-sm-2 control-label">Address 2: </label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" name="address2" id="address2"/>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">City: </label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" name="city" id="city"/>
                        </div>
                        <label class="col-sm-2 control-label">State: </label>
                        <div class="col-sm-4">

                            <select class="form-control" name="state" id="state">
                                @foreach ($states as $o)
                                    <option value="{{ $o->code }}">{{ $o->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Zip: </label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" name="zip" id="zip" maxlength="5" placeholder="5 digits"/>
                        </div>
                        <label class="col-sm-2 control-label"></label>
                        <div class="col-sm-4"></div>
                    </div>
                </div>

                <div role="tabpanel" class="tab-pane" id="products" style="padding:15px;">

                    <div class="form-group" id="act_box" style="margin-top: 20px;">
                        <label class="col-sm-1 control-label">Spiff Template</label>
                        <div class="col-sm-1"></div>
                        <div class="col-sm-3 spiff_template">
                            <div class="row">
                                <div class="col-sm-9">
                                    <div class="form-group">
                                        <select id="spiff_template" name="spiff_template" class="form-control">
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

{{--                    <div class="form-group" id="act_att_box">--}}
{{--                        <hr>--}}
{{--                        <label class="col-sm-1 control-label">AT&T</label>--}}
{{--                        <div class="col-sm-1">--}}
{{--                            <input type="checkbox" name="act_att" id="act_att" value="Y"--}}
{{--                                    {{ Auth::user()->account_type == 'L' || \App\Lib\Helper::has_activation_controller_auth(Auth::user()->account_id, 'AT&T') ? '' : 'disabled' }}/><br>--}}
{{--                        </div>--}}
{{--                        <div class="col-sm-2">--}}
{{--                            <label>ATT TID</label>--}}
{{--                            <input type="text" name="att_tid" id="att_tid" class="form-control" {{ Auth::user()->account_type == 'L' ? '' : 'readonly' }}/>--}}
{{--                        </div>--}}
{{--                        <div class="col-sm-1">--}}
{{--                            <label>ATT TID 2</label>--}}
{{--                            <input type="text" name="att_tid2" id="att_tid2" class="form-control" {{ Auth::user()->account_type == 'L' ? '' : 'readonly' }}/>--}}
{{--                        </div>--}}
{{--                        <div class="col-sm-3">--}}
{{--                            <input type="checkbox" name="att_allow_byos" id="att_allow_byos" value="Y" {{ Auth::user()->account_type == 'L' ? '' : 'disabled' }}/> <strong>Allow BYOS SIM</strong>--}}
{{--                            <div>--}}
{{--                                <input type="text" name="att_byos_act_month" id="att_byos_act_month"--}}
{{--                                       style="width:40px;float:left;"--}}
{{--                                       class="form-control" {{ Auth::user()->account_type == 'L' ? '' : 'readonly' }}/>--}}
{{--                                <span style="float:left;">&nbsp;RTR. Month BYOS SIM</span>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}

                    <div class="form-group" id="act_boom_box">
                        <hr>
                        <label class="col-sm-1 control-label">AT&T</label>
                        <div class="col-sm-1"></div>
                        <div class="col-sm-3">
                            <input type="checkbox" name="act_att" id="act_att" value="Y" style="margin-top: 8px;"/>
                            <strong>&nbsp; Allow Activation or not </strong>
                            <br>
                            <div style="{{ Auth::user()->account_type == 'L' ? '' : 'display: none;' }}">
                                <input type="checkbox" name="att_allow_byos" id="att_allow_byos" value="Y" {{ Auth::user()->account_type == 'L' ? '' : 'disabled' }} style="margin-top: 8px;"/>
                                <strong>  Allow BYOS SIM </strong>
                                <br>
                                <input type="text" name="att_byos_act_month" id="att_byos_act_month" style="width:40px;float:left;" class="form-control"/>
                                <strong>  RTR. Month BYOS SIM</strong>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <label>ATT TID</label>
                            <input type="text" name="att_tid" id="att_tid" class="form-control" {{ Auth::user()->account_type == 'L' ? '' : 'readonly' }}/>
                            <label>ATT TID 2</label>
                            <input type="text" name="att_tid2" id="att_tid2" class="form-control" {{ Auth::user()->account_type == 'L' ? '' : 'readonly' }}/>
                            <label>ATT Dealer Code/Notes</label>
                            <input type="text" name="att_dealer_code" id="att_dealer_code" class="form-control" {{ Auth::user()->account_type == 'L' ? '' : 'readonly' }}/>
                            <input type="text" name="att_dc_notes" id="att_dc_notes" class="form-control" {{ Auth::user()->account_type == 'L' ? '' : 'readonly' }}/>
                        </div>
                    </div>

                    <div class="form-group" id="act_boom_box">
                        <hr>
                        <label class="col-sm-1 control-label">Boom Mobile</label>
                        <div class="col-sm-1"></div>
                        <div class="col-sm-3">
                            <input type="checkbox" name="act_boom" id="act_boom" value="Y" style="margin-top: 8px;"/>
                            <strong>&nbsp; Allow Activation or not </strong>
                            <br>
                            <div style="{{ Auth::user()->account_type == 'L' ? '' : 'display: none;' }}">
                                <input type="checkbox" name="boom_hold_spiff" id="boom_hold_spiff" value="Y" style="margin-top: 8px;"/>
                                <strong>&nbsp; Hold Spiff </strong>
                                <br>
                                <input type="text" name="boom_min_month" id="boom_min_month" style="width:40px;float:left;" class="form-control"/>
                                <strong>&nbsp;Minimum Activation Months</strong>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" id="act_freeup_box">
                        <hr>
                        <label class="col-sm-1 control-label">FreeUP</label>
                        <div class="col-sm-1"></div>
                        <div class="col-sm-3">
                            <input type="checkbox" name="act_freeup" id="act_freeup" value="Y" style="margin-top: 8px;"/>
                            <strong>&nbsp; Allow Activation or not </strong>
                            <br>
                            <div style="{{ Auth::user()->account_type == 'L' ? '' : 'display: none;' }}">
                                <input type="checkbox" name="freeup_hold_spiff" id="freeup_hold_spiff" value="Y" style="margin-top: 8px;"/>
                                <strong>&nbsp; Hold Spiff </strong>
                                <br>
                                <input type="text" name="freeup_min_month" id="freeup_min_month" style="width:40px;float:left;" class="form-control"/>
                                <strong>&nbsp;Minimum Activation Months</strong>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" id="act_gen_box">
                        <hr>
                        <label class="col-sm-1 control-label">GEN Mobile</label>
                        <div class="col-sm-1"></div>
                        <div class="col-sm-3">
                            <input type="checkbox" name="act_gen" id="act_gen" value="Y" style="margin-top: 8px;"/>
                            <strong>&nbsp; Allow Activation or not </strong>
                            <br>
                            <div style="{{ Auth::user()->account_type == 'L' ? '' : 'display: none;' }}">
                                <input type="checkbox" name="gen_hold_spiff" id="gen_hold_spiff" value="Y" style="margin-top: 8px;"/>
                                <strong>&nbsp; Hold Spiff </strong>
                                <br>
                                <input type="text" name="gen_min_month" id="gen_min_month" style="width:40px;float:left;" class="form-control"/>
                                <strong>&nbsp;Minimum Activation Months</strong>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            @if($account->type == 'S')
                                <input type="checkbox" name="esn_swap" id="esn_swap" value="Y" {{ Auth::user()->account_type == 'L' ? '' : 'disabled' }}/> <strong>ESN/MDN Swaps On?</strong>
                                <div>
                                    <input type="text" name="esn_swap_num" id="esn_swap_num"
                                           style="width:40px;float:left;"
                                           class="form-control" {{ Auth::user()->account_type == 'L' ? '' : 'readonly' }}/>
                                    <span style="float:left;">&nbsp;/ Week</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="form-group" id="act_h2o_box">
                        <hr>
                        <label class="col-sm-1 control-label">H2O</label>
                        <div class="col-sm-1"></div>
                        <div class="col-sm-3">
                            <input type="checkbox" name="act_h2o" id="act_h2o" value="Y" style="margin-top: 8px;"/>
                            <strong>&nbsp; Allow Activation or not </strong>
                            <br>
                            <div style="{{ Auth::user()->account_type == 'L' ? '' : 'display: none;' }}">
                                <input type="checkbox" name="h2o_hold_spiff" id="h2o_hold_spiff" value="Y" style="margin-top: 8px;"/>
                                <strong>&nbsp; Hold Spiff </strong>
                                <br>
                                <input type="text" name="h2o_min_month" id="h2o_min_month" style="width:40px;float:left;" class="form-control"/>
                                <strong>&nbsp;Minimum Activation Months</strong>
                            </div>
                        </div>
                        @if (in_array(Auth::user()->account_type, ['L', 'M', 'D']))
                            <div class="col-sm-3">
                                <label>Dealer.Code</label>
                                <input type="text" name="dealer_code" id="dealer_code" class="form-control" {{ Auth::user()->account_type == 'L' ? '' : 'readonly' }}/>
                                <label>Dealer.Password</label>
                                <input type="text" name="dealer_password" id="dealer_password" class="form-control" {{ Auth::user()->account_type == 'L' ? '' : 'readonly' }}/>
                            </div>
                        @endif
                    </div>

                    <div class="form-group" id="act_liberty_box">
                        <hr>
                        <label class="col-sm-1 control-label">Liberty Mobile</label>
                        <div class="col-sm-1"></div>
                        <div class="col-sm-3">
                            <input type="checkbox" name="act_liberty" id="act_liberty" value="Y" style="margin-top: 8px;"/>
                            <strong>&nbsp; Allow Activation or not </strong>
                            <br>
                            <div style="{{ Auth::user()->account_type == 'L' ? '' : 'display: none;' }}">
                                <input type="checkbox" name="liberty_hold_spiff" id="liberty_hold_spiff" value="Y" style="margin-top: 8px;"/>
                                <strong>&nbsp; Hold Spiff </strong>
                                <br>
                                <input type="text" name="liberty_min_month" id="liberty_min_month" style="width:40px;float:left;" class="form-control"/>
                                <strong>&nbsp;Minimum Activation Months</strong>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" id="act_lyca_box">
                        <hr>
                        <label class="col-sm-1 control-label">Lyca</label>
                        <div class="col-sm-1"></div>
                        <div class="col-sm-3">
                            <input type="checkbox" name="act_lyca" id="act_lyca" value="Y" style="margin-top: 8px;"/>
                            <strong>&nbsp; Allow Activation or not </strong>
                            <br>
                            <div style="{{ Auth::user()->account_type == 'L' ? '' : 'display: none;' }}">
                                <input type="checkbox" name="lyca_hold_spiff" id="lyca_hold_spiff" value="Y" style="margin-top: 8px;"/>
                                <strong>&nbsp; Hold Spiff </strong>
                                <br>
                                <input type="text" name="lyca_min_month" id="lyca_min_month" style="width:40px;float:left;" class="form-control"/>
                                <strong>&nbsp;Minimum Activation Months</strong>
                            </div>
                        </div>
                    </div>

                </div>

                <div role="tabpanel" class="tab-pane" id="wallet" style="padding:15px;">
                    <div class="form-group">
                        <label class="col-sm-3">Pay.Method</label>
                        <div class="col-sm-3">
                            <select class="form-control" id="pay_method" name="pay_method" onchange="set_wallet_layout()">
                                <option value="">Please Select</option>
                                <option value="P">Prepay</option>
                                <option value="C">Credit</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group ach-info">
                        <label class="col-sm-3">ACH.Bank</label>
                        <div class="col-sm-3">
                            <input type="text" id="ach_bank" name="ach_bank" class="form-control"></input>
                        </div>
                        <label class="col-sm-3">ACH.Holder</label>
                        <div class="col-sm-3">
                            <input type="text" id="ach_holder" name="ach_holder" class="form-control"></input>
                        </div>
                    </div>
                    <div class="form-group ach-info">
                        <label class="col-sm-3">ACH.Routing.#</label>
                        <div class="col-sm-3">
                            <input type="text" id="ach_routeno" name="ach_routeno" class="form-control"></input>
                        </div>
                        <label class="col-sm-3">ACH.Account.#</label>
                        <div class="col-sm-3">
                            <input type="text" id="ach_acctno" name="ach_acctno" class="form-control"></input>
                        </div>
                    </div>
                    <div class="form-group credit-limit">
                        <label class="col-sm-3">Credit Limit</label>
                        <div class="col-sm-3">
                            <input type="text" class="form-control" id="credit_limit" name="credit_limit" readonly/>
                        </div>
                        <label class="col-sm-2 weekday-ach">Weekday ACH</label>
                        <div class="col-sm-4 text-right weekday-ach">
                            <label class="checkbox-inline" title="ACH for Monday. Default Included.">
                                <input type="checkbox" checked disabled> Mo
                            </label>
                            <label class="checkbox-inline ach-info" title="ACH for Tuesday">
                                <input type="checkbox" id="ach_tue" name="ach_tue" value="Y"> Tu
                            </label>
                            <label class="checkbox-inline ach-info" title="ACH for Wednesday">
                                <input type="checkbox" id="ach_wed" name="ach_wed" value="Y"> We
                            </label>
                            <label class="checkbox-inline ach-info" title="ACH for Thursday">
                                <input type="checkbox" id="ach_thu" name="ach_thu" value="Y"> Th
                            </label>
                            <label class="checkbox-inline ach-info" title="ACH for Friday">
                                <input type="checkbox" id="ach_fri" name="ach_fri" value="Y"> Fr
                            </label>
                        </div>
                    </div>
                    <div class="form-group credit-limit" id="div_current_balance">
                        <label class="col-sm-3">Available Sales Amount</label>
                        <div class="col-sm-3">
                            <span id="balance" class="form-control" disabled></span>
                        </div>
                        <div class="col-sm-3">
                            <button type="button" id="btn_edit_credit_limit" class="btn btn-primary btn-xs" onclick="edit_credit_limit()">Edit</button>
                            <button type="button" style="display:none" id="btn_update_credit_info" class="btn btn-primary btn-xs" onclick="update_credit_info()">Update</button>
                            <button type="button" style="display:none" id="btn_cancel_credit_limit" class="btn btn-primary btn-xs" onclick="cancel_credit_limit(true)">Cancel</button>
                        </div>
                    </div>
                    <div class="form-group allow-cash-limit">
                        <label class="col-sm-3">Allow Cash Limit</label>
                        <div class="col-sm-3">
                            <input type="text" class="form-control" id="allow_cash_limit" name="allow_cash_limit" readonly/>
                        </div>
                        <div style="{{ Auth::user()->account_type == 'L' ? '' : 'display:none;' }}">
                            <label class="col-sm-3 ach-info">Do not allow to create postpay accounts</label>
                            <div class="col-sm-3 ach-info">
                                <input type="checkbox" id="no_postpay" name="no_postpay" value="Y"/>
                            </div>
                        </div>
                    </div>

                    <div class="form-group minimun-payment-amount">
                        <div style="{{ Auth::user()->account_type == 'L' ? '' : 'display:none;' }}">
                            <label class="col-sm-3">Minimum Payment Amount</label>
                            <div class="col-sm-3">
                                <input type="text" class="form-control" id="min_ach_amt" name="min_ach_amt" readonly/>
                            </div>
                        </div>

                        <div style="{{ Auth::user()->account_type == 'L' ? '' : 'display:none;' }}">
                            <label class="col-sm-3 ach-info">No.ACH</label>
                            <div class="col-sm-3 ach-info">
                                <input type="checkbox" id="no_ach" name="no_ach" value="Y"/>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="display:none">
                        <label class="col-sm-3 ">Posting Limit</label>
                        <div class="col-sm-3">
                            <input type="text" class="form-control" name="posting_limit"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3">Assigned Rate Plan</label>
                        <div class="col-sm-3">
                            <select name="rate_plan_id" id="rate_plan_id" class="form-control">
                                <option value="">Please Select</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <button type="button" class="btn btn-primary btn-xs" onclick="show_rate_detail($('#rate_plan_id').val(), 'M')">View Plan Detail</button>
                        </div>
                    </div>

                    @if (in_array(Auth::user()->account_type, ['L']) && $account->type != 'S')
                    <div class="form-group">
                        <label class="col-sm-3">Default Subagent plan For Become a Dealer</label>
                        <div class="col-sm-3">
                            <select name="default_subagent_plan" id="default_subagent_plan" class="form-control">
                                <option value="">Please Select</option>
                            </select>
                        </div>
                    </div>

                    {{-- Default Spiff setting--}}
                    <div class="form-group">
                        <label class="col-sm-3">Default Subagent Spiff Plan for Become a Dealer</label>
                        <div class="col-sm-3">
                            <select name="default_spiff" id="default_spiff" class="form-control">
                                <option value="">Please Select</option>
                            </select>
                        </div>
                    </div>

                    @endif

                    <div class="form-group no-sub-agent owned-rate-plan">
                        <label class="col-sm-3">Owned Rate Plan</label>
                        <div class="col-sm-9">

                        </div>
                    </div>
                    <div class="form-group no-sub-agent owned-rate-plan">
                        <div class="col-sm-12">
                            <table class="table table-bordered table-hover">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Type</th>
                                    <th>Name</th>
                                    <th>Last.Updated</th>
                                    <th>Command</th>
                                </tr>
                                </thead>
                                <tbody id="tbody_owned_plans">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div role="tabpanel" class="tab-pane" id="others" style="padding:15px;">
                    <div class="form-group">
                        <label class="col-sm-3">Enable as eCommerce</label>
                        <div class="col-sm-9">
                            <input type="checkbox" id="c_store" name="c_store" value="Y"/> Yes
                        </div>
                    </div>
                    @if (in_array(Auth::user()->account_type, ['L']))
                        <div class="form-group">
                            <label class="col-sm-3">Rebates Eligibility</label>
                            <div class="col-sm-9">
                                <input type="checkbox" id="rebates_eligibility" name="rebates_eligibility" value="Y"/> Yes
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3">Show Discount Setup Report</label>
                            <div class="col-sm-9">
                                <input type="checkbox" id="show_discount_setup_report" name="show_discount_setup_report" value="Y"/> Yes
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3">Show Spiff Setup Report</label>
                            <div class="col-sm-9">
                                <input type="checkbox" id="show_spiff_setup_report" name="show_spiff_setup_report" value="Y"/> Yes
                            </div>
                        </div>

                        <div class="form-group" id="account_ship_info">

                            @if ($account_shipping_fees->count() > 0)
                                @foreach($account_shipping_fees as $asf)
                                    <label class="col-sm-3">Account Shipping Fee #{{$loop->index + 1}}</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="ship_fee_min_{{$asf->id}}" id="ship_fee_min_{{$asf->id}}" value="{{$asf->min_amt}}" style="width:100px;float:left;" disabled/>
                                        <label class="col-sm-1" style="text-align: center"> ~ </label>
                                        <input type="text" class="form-control" name="ship_fee_max_{{$asf->id}}" id="ship_fee_max_{{$asf->id}}" value="{{$asf->max_amt}}" style="width:100px;float:left;" disabled/>
                                        <label class="col-sm-1" style="text-align: right"> $ </label>
                                        <input type="text" class="form-control" name="ship_fee_{{$asf->id}}" id="ship_fee_{{$asf->id}}" value="{{$asf->fee}}" style="width:100px;float:left;" disabled/>
                                        <label class="col-sm-1"></label>
                                        <button type="button" class="btn btn-primary btn-xs" onclick="delete_account_shipping_fee({{$asf->id}})" style="width:60px;float:left;">Delete</button>
                                    </div>
                                @endforeach
                                    <label class="col-sm-3"></label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="ship_fee_min" id="ship_fee_min" style="width:100px;float:left;" placeholder="Min Amount"/>
                                        <label class="col-sm-1" style="text-align: center"> ~ </label>
                                        <input type="text" class="form-control" name="ship_fee_max" id="ship_fee_max" style="width:100px;float:left;" placeholder="Max Amount"/>
                                        <label class="col-sm-1" style="text-align: right"> $ </label>
                                        <input type="text" class="form-control" name="ship_fee" id="ship_fee" style="width:100px;float:left;" placeholder="Shipping Fee"/>
                                        <label class="col-sm-1"></label>
                                        <button type="button" class="btn btn-primary btn-xs" onclick="add_account_shipping_fee({{$account_id}})" style="width:60px;float:left;">ADD</button>
                                    </div>
                            @else
                                <label class="col-sm-3">Account Shipping Fee</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="ship_fee_min" id="ship_fee_min" style="width:100px;float:left;" placeholder="Min Amount"/>
                                    <label class="col-sm-1" style="text-align: center"> ~ </label>
                                    <input type="text" class="form-control" name="ship_fee_max" id="ship_fee_max" style="width:100px;float:left;" placeholder="Max Amount"/>
                                    <label class="col-sm-1" style="text-align: right"> $ </label>
                                    <input type="text" class="form-control" name="ship_fee" id="ship_fee" style="width:100px;float:left;" placeholder="Shipping Fee"/>
                                    <label class="col-sm-1"></label>
                                    <button type="button" class="btn btn-primary btn-xs" onclick="add_account_shipping_fee({{$account_id}})" style="width:60px;float:left;">ADD</button>
                                </div>
                            @endif
                        </div>
                        @if ($account->type == 'S')
                        <div class="form-group">
                            <label class="col-sm-3">Comments at Resend 'Become a Dealer'</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="welcome_email" rows="3"></textarea>
                            </div>

                            <div class="col-sm-3"></div>
                            <div class="col-sm-9">
                                CC Email
                                <input type="text" class="form-control" name="cc_email" id="cc_email"/>
                            </div>

                            <div class="col-sm-3"></div>
                            <div class="col-sm-9">
                                <button type="button" class="btn btn-default btn-sm" onclick="send_email()">Send</button>
                            </div>
                        </div>
                        @endif

                    @endif

                </div>
                <div role="tabpanel" class="tab-pane" id="forms" style="padding:15px;">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Store Photo - Front : </label>
                        <div class="col-sm-4" style="margin-top:8px;">
                            <input type="file" class="form-control" name="FILE_STORE_FRONT"/>
                        </div>
                        <label class="col-sm-4 control-label" style="color:#aaa; text-align:left;">
                            @if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']))
                                <div id="a_FILE_STORE_FRONT" style="display:none; font-size:12px;"></div>
                            @else
                                <a href="#" class="form-control" id="a_FILE_STORE_FRONT" style="display:none; font-size:12px;"></a>
                            @endif
                        </label>
                        <label class="col-sm-1">
                            <input type="checkbox" name="FILE_STORE_FRONT_LOCKED" style="display:none;" value="Y"/>
                        </label>
                    </div>
                    <hr />
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Store Photo - Inside : </label>
                        <div class="col-sm-4" style="margin-top:8px;">
                            <input type="file" class="form-control" name="FILE_STORE_INSIDE"/>
                        </div>
                        <label class="col-sm-4 control-label" style="color:#aaa; text-align:left;">
                            @if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']))
                                <div id="a_FILE_STORE_INSIDE" style="display:none; font-size:12px;"></div>
                            @else
                                <a href="#" class="form-control" id="a_FILE_STORE_INSIDE" style="display:none;font-size:12px;"></a>
                            @endif
                        </label>
                        <label class="col-sm-1">
                            <input type="checkbox" name="FILE_STORE_INSIDE_LOCKED" style="display:none;" value="Y"/>
                        </label>
                    </div>
                    <hr />
                    <div class="form-group">
                        <label class="col-sm-3 control-label">W9 Form * : <span id="esig_w9_box"><br><a class="float:right" href="/admin/esig/FILE_W_9" target="_blank">eSignature</a></span><br><a class="float:right" href="/upload_template/W9_Form.pdf" target="_blank">form download</a></label>
                        <div class="col-sm-4" style="margin-top:8px;">
                            <input type="file" class="form-control" name="FILE_W_9"/>
                        </div>
                        <label class="col-sm-4 control-label" style="color:#aaa; text-align:left;">
                            @if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']))
                                <div id="a_FILE_W_9" style="display:none; font-size:12px;"></div>
                            @else
                                <a href="#" class="form-control" id="a_FILE_W_9" style="display:none;font-size:12px;"></a>
                            @endif
                        </label>
                        <label class="col-sm-1">
                            <input type="checkbox" name="FILE_W_9_LOCKED" value="Y" style="display:none;"/>
                        </label>
                    </div>
                    <hr />
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Puerto Rico Sales Tax Exemption Form: <span id="esig_pr_sales_tax_box"><br><a class="float:right" href="/admin/esig/FILE_PR_SALES_TAX" target="_blank">eSignature</a></span><br><a class="float:right" href="/upload_template/PR_SALES_TAX.pdf" target="_blank">form download</a></label>
                        <div class="col-sm-4" style="margin-top:8px;">
                            <input type="file" class="form-control" name="FILE_PR_SALES_TAX"/>
                        </div>
                        <label class="col-sm-4 control-label" style="color:#aaa; text-align:left;">
                            @if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']))
                                <div id="a_FILE_PR_SALES_TAX" style="display:none; font-size:12px;"></div>
                            @else
                                <a href="#" class="form-control" id="a_FILE_PR_SALES_TAX" style="display:none;font-size:12px;"></a>
                            @endif
                        </label>
                        <label class="col-sm-1">
                            <input type="checkbox" name="FILE_PR_SALES_TAX_LOCKED" value="Y" style="display:none;"/>
                        </label>
                    </div>
                    <hr />
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Uniform sales and Use Certificate: <span id="esig_usuc_box"><br><a class="float:right" href="/admin/esig/FILE_USUC" target="_blank">eSignature</a></span><br><a class="float:right" href="/upload_template/FILE_USUC.pdf" target="_blank">form download</a></label>
                        <div class="col-sm-4" style="margin-top:8px;">
                            <input type="file" class="form-control" name="FILE_USUC"/>
                        </div>
                        <label class="col-sm-4 control-label" style="color:#aaa; text-align:left;">
                            @if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']))
                                <div id="a_FILE_USUC" style="display:none; font-size:12px;"></div>
                            @else
                                <a href="#" class="form-control" id="a_FILE_USUC" style="display:none;font-size:12px;"></a>
                            @endif
                        </label>
                        <label class="col-sm-1">
                            <input type="checkbox" name="FILE_USUC_LOCKED" value="Y" style="display:none;"/>
                        </label>
                    </div>
                    <hr />
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Tax ID Form : </label>
                        <div class="col-sm-4" style="margin-top:8px;">
                            <input type="file" class="form-control" name="FILE_TAX_ID"/>
                        </div>
                        <label class="col-sm-4 control-label" style="color:#aaa; text-align:left;">
                            @if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']))
                                <div id="a_FILE_TAX_ID" style="display:none; font-size:12px;"></div>
                            @else
                                <a href="#" class="form-control" id="a_FILE_TAX_ID" style="display:none;font-size:12px;"></a>
                            @endif
                        </label>
                        <label class="col-sm-1">
                            <input type="checkbox" name="FILE_TAX_ID_LOCKED" value="Y" style="display:none;"/>
                        </label>
                    </div>
                    <hr />
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Business Certification : </label>
                        <div class="col-sm-4" style="margin-top:8px;">
                            <input type="file" class="form-control" name="FILE_BUSINESS_CERTIFICATION"/>
                        </div>
                        <label class="col-sm-4 control-label" style="color:#aaa; text-align:left;">
                            @if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']))
                                <div id="a_FILE_BUSINESS_CERTIFICATION" style="display:none; font-size:12px;"></div>
                            @else
                                <a href="#" class="form-control" id="a_FILE_BUSINESS_CERTIFICATION" style="display:none;font-size:12px;"></a>
                            @endif
                        </label>
                        <label class="col-sm-1">
                            <input type="checkbox" name="FILE_BUSINESS_CERTIFICATION_LOCKED" value="Y" style="display:none;"/>
                        </label>
                    </div>
                    <hr />
                    <div class="form-group" id="div_FILE_DEALER_AGREEMENT">
                        <label class="col-sm-3 control-label">Dealer Agreement * : </label>
                        <div class="col-sm-4" style="margin-top:8px;">
                            <input type="file" class="form-control" name="FILE_DEALER_AGREEMENT"/>
                        </div>
                        <label class="col-sm-4 control-label" style="color:#aaa; text-align:left;">
                            @if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']))
                                <div id="a_FILE_DEALER_AGREEMENT" style="display:none; font-size:12px;"></div>
                            @else
                                <a href="#" class="form-control" id="a_FILE_DEALER_AGREEMENT" style="display:none;font-size:12px;"></a>
                            @endif
                        </label>
                        <label class="col-sm-1">
                            <input type="checkbox" name="FILE_DEALER_AGREEMENT_LOCKED" value="Y" style="display:none;"/>
                        </label>
                    </div>
                    <hr />
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Driver License : </label>
                        <div class="col-sm-4" style="margin-top:8px;">
                            <input type="file" class="form-control" name="FILE_DRIVER_LICENSE"/>
                        </div>
                        <label class="col-sm-4 control-label" style="color:#aaa; text-align:left;">
                            @if (!in_array(Auth::user()->user_id, ['thomas', 'admin' ,'system']))
                                <div id="a_FILE_DRIVER_LICENSE" style="display:none; font-size:12px;"></div>
                            @else
                                <a href="#" class="form-control" id="a_FILE_DRIVER_LICENSE" style="display:none;font-size:12px;"></a>
                            @endif
                        </label>
                        <label class="col-sm-1">
                            <input type="checkbox" name="FILE_DRIVER_LICENSE_LOCKED" value="Y" style="display:none;"/>
                        </label>
                    </div>
                    <hr />
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Void Check : </label>
                        <div class="col-sm-4" style="margin-top:8px;">
                            <input type="file" class="form-control" name="FILE_VOID_CHECK"/>
                        </div>
                        <label class="col-sm-4 control-label" style="color:#aaa; text-align:left;">
                            @if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']))
                                <div id="a_FILE_VOID_CHECK" style="display:none; font-size:12px;"></div>
                            @else
                                <a href="#" class="form-control" id="a_FILE_VOID_CHECK" style="display:none;font-size:12px;"></a>
                            @endif
                        </label>
                        <label class="col-sm-1">
                            <input type="checkbox" name="FILE_VOID_CHECK_LOCKED" value="Y" style="display:none;"/>
                        </label>
                    </div>
                    <hr />
                    <div class="form-group">
                        <label class="col-sm-3 control-label">ACH Doc : <span id="esig_ach_box">
                                <br><a class="float:right" href="/admin/esig/FILE_ACH_DOC" target="_blank">eSignature</a></span>
                            <br><a class="float:right" href="/upload_template/ACH_FORM.pdf" target="_blank">form download</a>
                            <br><a class="float:right" href="/upload_template/Check_List.pdf" target="_blank">Check List</a></label>
                        <div class="col-sm-4" style="margin-top:8px;">
                            <input type="file" class="form-control" name="FILE_ACH_DOC"/>
                        </div>
                        <label class="col-sm-4 control-label" style="color:#aaa; text-align:left;">
                            @if (!in_array(Auth::user()->user_id, ['thomas', 'admin' ,'system']))
                                <div id="a_FILE_ACH_DOC" style="display:none; font-size:12px;"></div>
                            @else
                                <a href="#" class="form-control" id="a_FILE_ACH_DOC" style="display:none;font-size:12px;"></a>
                            @endif
                        </label>
                        <label class="col-sm-1">
                            <input type="checkbox" name="FILE_ACH_DOC_LOCKED" value="Y" style="display:none;"/>
                        </label>
                    </div>
                    <hr />
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Bank Reference : <br><a class="float:right" href="/upload_template/BANK_REFERENCE.pdf" target="_blank">form download</a></label>
                        <div class="col-sm-4" style="margin-top:8px;">
                            <input type="file" class="form-control" name="FILE_BANK_REFERENCE"/>
                        </div>
                        <label class="col-sm-4 control-label" style="color:#aaa; text-align:left;">
                            @if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']))
                                <div id="a_FILE_BANK_REFERENCE" style="display:none; font-size:12px;"></div>
                            @else
                                <a href="#" class="form-control" id="a_FILE_BANK_REFERENCE" style="display:none;font-size:12px;"></a>
                            @endif
                        </label>
                        <label class="col-sm-1">
                            <input type="checkbox" name="FILE_BANK_REFERENCE_LOCKED" value="Y" style="display:none;"/>
                        </label>
                    </div>
                    <hr />
                    <div class="form-group">
                        <label class="col-sm-3 control-label">H2O Dealer Registration : <span id="esig_h2o_dealer_box">
                                <br><a class="float:right" href="/admin/esig/FILE_H2O_DEALER_FORM" target="_blank">eSignature</a></span>
                                <br><a class="float:right" href="/upload_template/h2o_dealer_form.pdf" target="_blank">form download</a>
                        </label>
                        <div class="col-sm-4" style="margin-top:8px;">
                            <input type="file" class="form-control" name="FILE_H2O_DEALER_FORM"/>
                        </div>
                        <label class="col-sm-4 control-label" style="color:#aaa; text-align:left;">
                            @if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']))
                                <div id="a_FILE_H2O_DEALER_FORM" style="display:none; font-size:12px;"></div>
                            @else
                                <a href="#" class="form-control" id="a_FILE_H2O_DEALER_FORM" style="display:none;font-size:12px;"></a>
                            @endif
                        </label>
                        <label class="col-sm-1">
                            <input type="checkbox" name="FILE_H2O_DEALER_FORM_LOCKED" value="Y" style="display:none;"/>
                        </label>
                    </div>
                    <hr />
                    <div class="form-group">
                        <label class="col-sm-3 control-label">H2O ACH Doc : <span id="esig_h2o_ach_box">
                                <br><a class="float:right" href="/admin/esig/FILE_H2O_ACH" target="_blank">eSignature</a></span>
                                <br><a class="float:right" href="/upload_template/h2o_ach.pdf" target="_blank">form download</a>
                        </label>
                        <div class="col-sm-4" style="margin-top:8px;">
                            <input type="file" class="form-control" name="FILE_H2O_ACH"/>
                        </div>
                        <label class="col-sm-4 control-label" style="color:#aaa; text-align:left;">
                            @if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']))
                                <div id="a_FILE_H2O_ACH" style="display:none; font-size:12px;"></div>
                            @else
                                <a href="#" class="form-control" id="a_FILE_H2O_ACH" style="display:none;font-size:12px;"></a>
                            @endif
                        </label>
                        <label class="col-sm-1">
                            <input type="checkbox" name="FILE_H2O_ACH_LOCKED" value="Y" style="display:none;"/>
                        </label>
                    </div>
                    <hr />
                    <div class="form-group">
                        <label class="col-sm-3 control-label">(ATT) Application and Agreement :</label>
                        <div class="col-sm-4" style="margin-top:8px;">
                            <input type="file" class="form-control" name="FILE_ATT_AGREEMENT"/>
                        </div>
                        <label class="col-sm-4 control-label" style="color:#aaa; text-align:left;">
                            @if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']))
                                <div id="a_FILE_ATT_AGREEMENT" style="display:none; font-size:12px;"></div>
                            @else
                                <a href="#" class="form-control" id="a_FILE_ATT_AGREEMENT" style="display:none;font-size:12px;"></a>
                            @endif
                        </label>
                        <label class="col-sm-1">
                            <input type="checkbox" name="FILE_ATT_AGREEMENT_LOCKED" value="Y" style="display:none;"/>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">(ATT) Driver License :</label>
                        <div class="col-sm-4" style="margin-top:8px;">
                            <input type="file" class="form-control" name="FILE_ATT_DRIVER_LICENSE"/>
                        </div>
                        <label class="col-sm-4 control-label" style="color:#aaa; text-align:left;">
                            @if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']))
                                <div id="a_FILE_ATT_DRIVER_LICENSE" style="display:none; font-size:12px;"></div>
                            @else
                                <a href="#" class="form-control" id="a_FILE_ATT_DRIVER_LICENSE" style="display:none;font-size:12px;"></a>
                            @endif
                        </label>
                        <label class="col-sm-1">
                            <input type="checkbox" name="FILE_ATT_DRIVER_LICENSE_LOCKED" value="Y" style="display:none;"/>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">(ATT) Business Certification :</label>
                        <div class="col-sm-4" style="margin-top:8px;">
                            <input type="file" class="form-control" name="FILE_ATT_BUSINESS_CERTIFICATION"/>
                        </div>
                        <label class="col-sm-4 control-label" style="color:#aaa; text-align:left;">
                            @if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']))
                                <div id="a_FILE_ATT_BUSINESS_CERTIFICATION" style="display:none; font-size:12px;"></div>
                            @else
                                <a href="#" class="form-control" id="a_FILE_ATT_BUSINESS_CERTIFICATION" style="display:none;font-size:12px;"></a>
                            @endif
                        </label>
                        <label class="col-sm-1">
                            <input type="checkbox" name="FILE_ATT_BUSINESS_CERTIFICATION_LOCKED" value="Y" style="display:none;"/>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">(ATT) Void Check :</label>
                        <div class="col-sm-4" style="margin-top:8px;">
                            <input type="file" class="form-control" name="FILE_ATT_VOID_CHECK"/>
                        </div>
                        <label class="col-sm-4 control-label" style="color:#aaa; text-align:left;">
                            @if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']))
                                <div id="a_FILE_ATT_VOID_CHECK" style="display:none; font-size:12px;"></div>
                            @else
                                <a href="#" class="form-control" id="a_FILE_ATT_VOID_CHECK" style="display:none;font-size:12px;"></a>
                            @endif
                        </label>
                        <label class="col-sm-1">
                            <input type="checkbox" name="FILE_ATT_VOID_CHECK_LOCKED" value="Y" style="display:none;"/>
                        </label>
                    </div>
                </div>

            </div>
            <ul class="pager wizard">
                <li class="previous first" style="display:none;"><a href="#">First</a></li>
                <li class="previous"><a href="#">Previous</a></li>
                <li class="next last" style="display:none;"><a href="#">Last</a></li>
                <li class="next"><a href="#">Next</a></li>
                <li class="finish"><a href="javascript:;" style="float:right;">Finish</a></li>
            </ul>
            <div class="progress in-form" style="display:none;margin-top:20px;">
                <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
                    <span class="sr-only">Please wait.</span>
                </div>
            </div>
        </div>

        <hr>

        @if (!empty($account_id))
        <div class="form-group text-right">
            <button type="button" class="btn btn-primary" onclick="save_account_detail()">Save changes</button>
            @if (Auth::user()->account_type == 'L' && (in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']) || env('APP_ENV') == 'local'))
                <button type="button" class="btn btn-danger" onclick="remove_account()">Remove</button>
            @endif
        </div>
        @endif
    </form>

    <div style="display:none">
        <iframe name="ifm_upload"></iframe>
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
                        <div class="col-md-15">
                            <form id="frm_wallet" class="form-horizontal" action="/admin/account/rate-detail/load-excel" method="post">
                                {!! csrf_field() !!}
                                <div class="form-group row">
                                    <input type="hidden" id="cur_rate_plan" name="cur_rate_plan" value="">
                                    @if (Auth::user()->account_type == 'L')
                                        <div class="col-md-2">
                                            Vendor
                                            <select id="vendor" name="vendor" class="form-control">
                                                <option value="">Show All</option>
                                                @if (count($vendors) > 0)
                                                    @foreach ($vendors as $o)
                                                        <option value="{{ $o->code }}">{{ $o->name }}</option>
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
                                        <input type="text" class="form-control" id="product_id" name="product_id" value=""/>
                                    </div>
                                    <div class="col-md-3">
                                        Product.Name
                                        <input type="text" class="form-control" id="product_name" name="product_name" value=""/>
                                    </div>
                                    <div class="col-md-3">
                                        <br>
                                        <button type="button" class="btn btn-primary btn-sm" onclick="show_rate_detail
                                        (current_rate_plan_id, current_show_type)">Search</button>
                                        <button type="button" class="btn btn-primary btn-sm" onclick="show_rate_detail_excel()">export</button>
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


@stop
