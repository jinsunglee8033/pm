@extends('admin.layout.default')

@section('content')

    <script type="text/javascript">
        window.onload = function() {
            $( "#sdate" ).datetimepicker({
                format: 'YYYY-MM-DD HH:00:00',
                sideBySide: true
            });
            $( "#edate" ).datetimepicker({
                format: 'YYYY-MM-DD HH:59:59',
                sideBySide: true
            });

            $( "#sdate_batch" ).datetimepicker({
                format: 'YYYY-MM-DD HH:00:00',
                sideBySide: true
            });
            $( "#edate_batch" ).datetimepicker({
                format: 'YYYY-MM-DD HH:59:59',
                sideBySide: true
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
            window.location.href = '/admin/reports/transaction';
        }

        function show_detail(id) {

            myApp.showLoading();

            $.ajax({
                url: '/admin/reports/transaction/get-detail',
                data: {
                    id: id,
                    _token: '{!! csrf_token() !!}'
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {

                        var o = res.data;

                        $('#id').val(o.id);
                        $('#type').val(o.type_name);
                        if (o.type_name === 'Void') {
                            $('#type').css('color', 'red');
                        } else {
                            $('#type').css('color', 'black');
                        }
                        $('#api').val(o.api == 'Y' ? 'YES' : 'NO');
                        $('#carrier').val(o.product.carrier);
                        $('#action').val(o.action);

                        if (o.action == 'Port-In') {
                            $('#port-in-row').show();
                            $('#phone').val(o.phone);
                            //$('#phone').prop('readonly', true);
                        } else {
                            $('#port-in-row').hide();
                            //$('#phone').prop('readonly', false);
                        }

                        $('#product').val(o.product.name);
                        $('#denom').val('$' + parseFloat(o.denom).toFixed(2));
                        $('#sim').val(o.sim);
                        $('#esn').val(o.esn);
                        $('#npa').val(o.npa);
                        $('#afcode').val(o.afcode);
                        $('#first_name').val(o.first_name);
                        $('#last_name').val(o.last_name);
                        $('#address1').val(o.address1);
                        $('#address2').val(o.address2);
                        $('#city').val(o.city);
                        $('#state').val(o.state);
                        $('#zip').val(o.zip);
                        $('#number_to_port').val(o.phone);
                        $('#current_carrier').val(o.current_carrier);
                        $('#account_no').val(o.account_no);
                        $('#account_pin').val(o.account_pin);
                        $('#first_name').val(o.first_name);
                        $('#last_name').val(o.last_name);
                        $('#call_back_phone').val(o.call_back_phone);
                        $('#email').val(o.email);
                        $('#pref_pin').val(o.pref_pin);
                        $('#phone').val(o.phone);
                        $('#pin').val(o.pin);
                        $('#status').val(o.status);
                        $('#note').val(o.note);
                        $('#note2').val(o.note2);
                        $('#loc_id').val(o.loc_id);
                        $('#loc_state').val(o.loc_state);
                        $('#outlet_id').val(o.outlet_id);

                        if (o.action === 'PIN') {
                            $('#div_detail_pin').show();
                            $('#div_detail_phone').hide();
                        } else {
                            $('#div_detail_pin').hide();
                            $('#div_detail_phone').show();
                        }

                        @if(Auth::user()->account_type == 'L' && (getenv('APP_ENV') == 'production' && in_array
                        (Auth::user()->user_id, ['admin', 'thomas', 'system']) || getenv('APP_ENV') == 'local'))
                        $('#btn_void_transaction').hide();
                        if (o.action === 'Activation' || o.action === 'Port-In' || o.action === 'RTR' || o.action === 'PIN') {
                            // Hide void button at void transaction detail popup 3/2/2020 //
                            if ( (o.status === 'C' && o.void_date == null) && (o.type != 'V') ) {
                                $('#btn_void_transaction').show();
                            }
                        }
                        @endif

                        //$('#sim').prop('readonly', !o.can_edit);
                        //$('#esn').prop('readonly', !o.can_edit);
                        $('#phone').prop('readonly', !o.can_edit);
                        $('#pin').prop('readonly', !o.can_edit);
                        $('#status').prop('disabled', !o.can_edit);
                        $('#note').prop('readonly', !o.can_edit);
                        if (o.can_edit) {
                            $('#btn_update').show();
                        } else {
                            $('#btn_update').hide()
                        }

                        if (o.action === 'Port-In' && o.status ==='C' && o.product.carrier === 'AT&T' && o.type ==='S') {
                            $('#btn_action_required').show();
                        }else if(o.action === 'Port-In' && o.status ==='R' && o.product.carrier === 'Boom Mobile' && o.type ==='S') {
                            $('#btn_action_required').show();
                        }

                        if (o.product.id == 'WBMBA' || o.product.id == 'WBMRA' || o.product.id == 'WBMPA' || o.product.id == 'WBMPOA' ||
                            o.product.id == 'WBMBAR' || o.product.id == 'WBMRAR' || o.product.id == 'WBMPAR'){
                            $('#btn_update_boom').show();
                            $('#phone').attr('readonly', false);
                            $('#note').attr('readonly', false);
                        }else {
                            $('#btn_update_boom').hide();
                            //$('#phone').attr('readonly', true);
                            // $('#note').attr('readonly', true);
                        }

                        @if(Auth::user()->account_type == 'L' && (in_array(Auth::user()->user_id, ['admin', 'thomas', 'system'])))
                        status_changed();
                        @endif

                        $('#div_transaction_detail').modal();

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

        function status_changed() {
            var status = $('#status').val();
            switch (status) {
                case 'R':
                case 'C':
                case 'F':
                    $('#note').show();
                    break;
                default:
                    $('#note').hide();
                    $('#note').val('');
                    break;
            }
        }

        function update_detail_boom() {
            myApp.showLoading();

            $.ajax({
                url: '/admin/reports/transaction/update-boom',
                data: {
                    _token: '{!! csrf_token() !!}',
                    id: $('#id').val(),
                    phone: $('#phone').val(),
                    note: $('#note').val(),
                    note2: $('#note2').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if($.trim(res.msg) === ''){
                        $('#div_transaction_detail').hide();
                        myApp.showSuccess('Your request has been updated successfully!', function() {
                            $('#btn_search').click();
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

        function update_detail() {
            myApp.showLoading();

            $.ajax({
                url: '/admin/reports/transaction/update',
                data: {
                    _token: '{!! csrf_token() !!}',
                    id: $('#id').val(),
                    phone: $('#phone').val(),
                    sim: $('#sim').val(),
                    esn: $('#esn').val(),
                    status: $('#status').val(),
                    note: $('#note').val(),
                    note2: $('#note2').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        $('#div_transaction_detail').hide();
                        myApp.showSuccess('Your request has been updated successfully!', function() {
                            $('#btn_search').click();
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

        function update_note2() {
            myApp.showLoading();

            $.ajax({
                url: '/admin/reports/transaction/update-note2',
                data: {
                    _token: '{!! csrf_token() !!}',
                    id: $('#id').val(),
                    note2: $('#note2').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        $('#div_transaction_detail').hide();
                        myApp.showSuccess('Your request has been updated successfully!', function() {
                            $('#btn_search').click();
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

        @if(Auth::user()->account_type == 'L' && (getenv('APP_ENV') == 'production' && in_array(Auth::user()
        ->user_id, ['admin', 'thomas', 'system']) || getenv('APP_ENV') == 'local'))
        function void_transaction() {

            if (!confirm("Are you sure to void the transaction ?")) {
                return;
            }

            myApp.showLoading();

            $.ajax({
                url: '/admin/reports/transaction/void-transaction',
                data: {
                    _token: '{!! csrf_token() !!}',
                    id: $('#id').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        // $('#div_transaction_detail').hide();
                        myApp.showSuccess('Your request has been voided successfully!', function() {
                            // $('#btn_search').click();
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

        function action_required() {

            myApp.showLoading();

            $.ajax({
                url: '/admin/reports/transaction/action_required',
                data: {
                    _token: '{!! csrf_token() !!}',
                    id: $('#id').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {

                        myApp.showSuccess('Your request has been successfully!', function() {
                            $('#btn_search').click();
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

        function show_batch_lookup() {
            $('#n_batch_lines').val('');
            $('#div_batch_lookup').modal();
        }

        function count_batch_lines() {
            var lines = $.trim($('#n_batch_lines').val()).split("\n");
            $('#n_batch_lines_qty').text(lines.length);
        }

        function batch_lookup() {
            var lines = $('#n_batch_lines').val();
            lines = $.trim(lines);

            var category = $('input[name=category]:checked').val();
            if (category === '' || typeof category === 'undefined') {
                myApp.showError('Please select batch item category to lookup');
                return;
            }

            if (lines === '') {
                myApp.showError('Please enter batch item to lookup');
                return;
            }

            $('#div_batch_lookup').modal('hide');
            $('#frm_batch_lookup').submit();
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
                $('#sdate').val(moment(today).format("YYYY-MM-DD 00:00:00"));
                $('#edate').val(moment(today).format("YYYY-MM-DD 23:59:59"));
            }else if(quick == 'This Week'){
                $('#sdate').val(moment(startOfWeek).format("YYYY-MM-DD 00:00:00"));
                $('#edate').val(moment(endOfWeek).format("YYYY-MM-DD 23:59:59"));
            }else if(quick == 'This Month'){
                $('#sdate').val(moment(startOfMonth).format("YYYY-MM-DD 00:00:00"));
                $('#edate').val(moment(endOfMonth).format("YYYY-MM-DD 23:59:59"));
            }else if(quick == 'This Year'){
                $('#sdate').val(moment(startOfYear).format("YYYY-MM-DD 00:00:00"));
                $('#edate').val(moment(endOfYear).format("YYYY-MM-DD 23:59:59"));
            }else if(quick == 'Yesterday'){
                $('#sdate').val(moment(yesterday).format("YYYY-MM-DD 00:00:00"));
                $('#edate').val(moment(yesterday).format("YYYY-MM-DD 23:59:59"));
            }else if(quick == 'Yesterday to Date'){
                $('#sdate').val(moment(yesterday).format("YYYY-MM-DD 00:00:00"));
                $('#edate').val(moment(today).format("YYYY-MM-DD 23:59:59"));
            }else if(quick == 'Last Week'){
                $('#sdate').val(moment(startOfLastWeek).format("YYYY-MM-DD 00:00:00"));
                $('#edate').val(moment(endOfLastWeek).format("YYYY-MM-DD 23:59:59"));
            }else if(quick == 'Last Week to Date'){
                $('#sdate').val(moment(startOfLastWeek).format("YYYY-MM-DD 00:00:00"));
                $('#edate').val(moment(today).format("YYYY-MM-DD 23:59:59"));
            }else if(quick == 'Last Month'){
                $('#sdate').val(moment(startOfLastMonth).format("YYYY-MM-DD 00:00:00"));
                $('#edate').val(moment(endOfLastMonth).format("YYYY-MM-DD 23:59:59"));
            }else if(quick == 'Last Month to Date'){
                $('#sdate').val(moment(startOfLastMonth).format("YYYY-MM-DD 00:00:00"));
                $('#edate').val(moment(today).format("YYYY-MM-DD 23:59:59"));
            }else if(quick == 'Last Year'){
                $('#sdate').val(moment(startOfLastYear).format("YYYY-MM-DD 00:00:00"));
                $('#edate').val(moment(endOfLastYear).format("YYYY-MM-DD 23:59:59"));
            }else if(quick == 'Last Year to Date'){
                $('#sdate').val(moment(startOfLastYear).format("YYYY-MM-DD 00:00:00"));
                $('#edate').val(moment(today).format("YYYY-MM-DD 23:59:59"));
            }else if(quick == 'Last WeekEnd'){
                $('#sdate').val(moment(startOfLastWeekend).format("YYYY-MM-DD 00:00:00"));
                $('#edate').val(moment(endOfLastWeek).format("YYYY-MM-DD 23:59:59"));
            }
        }

        function set_date_batch() {
            var quick = $('#quick_batch').val();

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
                $('#sdate_batch').val(moment(today).format("YYYY-MM-DD 00:00:00"));
                $('#edate_batch').val(moment(today).format("YYYY-MM-DD 23:59:59"));
            }else if(quick == 'This Week'){
                $('#sdate_batch').val(moment(startOfWeek).format("YYYY-MM-DD 00:00:00"));
                $('#edate_batch').val(moment(endOfWeek).format("YYYY-MM-DD 23:59:59"));
            }else if(quick == 'This Month'){
                $('#sdate_batch').val(moment(startOfMonth).format("YYYY-MM-DD 00:00:00"));
                $('#edate_batch').val(moment(endOfMonth).format("YYYY-MM-DD 23:59:59"));
            }else if(quick == 'This Year'){
                $('#sdate_batch').val(moment(startOfYear).format("YYYY-MM-DD 00:00:00"));
                $('#edate_batch').val(moment(endOfYear).format("YYYY-MM-DD 23:59:59"));
            }else if(quick == 'Yesterday'){
                $('#sdate_batch').val(moment(yesterday).format("YYYY-MM-DD 00:00:00"));
                $('#edate_batch').val(moment(yesterday).format("YYYY-MM-DD 23:59:59"));
            }else if(quick == 'Yesterday to Date'){
                $('#sdate_batch').val(moment(yesterday).format("YYYY-MM-DD 00:00:00"));
                $('#edate_batch').val(moment(today).format("YYYY-MM-DD 23:59:59"));
            }else if(quick == 'Last Week'){
                $('#sdate_batch').val(moment(startOfLastWeek).format("YYYY-MM-DD 00:00:00"));
                $('#edate_batch').val(moment(endOfLastWeek).format("YYYY-MM-DD 23:59:59"));
            }else if(quick == 'Last Week to Date'){
                $('#sdate_batch').val(moment(startOfLastWeek).format("YYYY-MM-DD 00:00:00"));
                $('#edate_batch').val(moment(today).format("YYYY-MM-DD 23:59:59"));
            }else if(quick == 'Last Month'){
                $('#sdate_batch').val(moment(startOfLastMonth).format("YYYY-MM-DD 00:00:00"));
                $('#edate_batch').val(moment(endOfLastMonth).format("YYYY-MM-DD 23:59:59"));
            }else if(quick == 'Last Month to Date'){
                $('#sdate_batch').val(moment(startOfLastMonth).format("YYYY-MM-DD 00:00:00"));
                $('#edate_batch').val(moment(today).format("YYYY-MM-DD 23:59:59"));
            }else if(quick == 'Last Year'){
                $('#sdate_batch').val(moment(startOfLastYear).format("YYYY-MM-DD 00:00:00"));
                $('#edate_batch').val(moment(endOfLastYear).format("YYYY-MM-DD 23:59:59"));
            }else if(quick == 'Last Year to Date'){
                $('#sdate_batch').val(moment(startOfLastYear).format("YYYY-MM-DD 00:00:00"));
                $('#edate_batch').val(moment(today).format("YYYY-MM-DD 23:59:59"));
            }else if(quick == 'Last WeekEnd'){
                $('#sdate_batch').val(moment(startOfLastWeekend).format("YYYY-MM-DD 00:00:00"));
                $('#edate_batch').val(moment(endOfLastWeek).format("YYYY-MM-DD 23:59:59"));
            }
        }

    </script>

    <h4>Transaction Report</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post" action="/admin/reports/transaction">
            {{ csrf_field() }}
            <input type="hidden" name="excel" id="excel"/>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Date</label>
                        <div class="col-md-8">
                            <input type="text" style="width:125px; float:left;" class="form-control" id="sdate" name="sdate" value="{{ old('sdate', $sdate) }}"/>
                            <span class="control-label" style="margin-left:5px; float:left;"> ~ </span>
                            <input type="text" style="width:125px; margin-left: 5px; float:left;" class="form-control" id="edate" name="edate" value="{{ old('edate', $edate) }}"/>
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
                        <label class="col-md-4 control-label">Carrier</label>
                        <div class="col-md-8">
                            <select class="form-control" name="carrier" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                <option value="" {{ old('carrier', $carrier) == '' ? 'selected' : '' }}>All</option>
                                @foreach ($carriers as $o)
                                    <option value="{{ $o->name }}" {{ old('carrier', $carrier) == $o->name ? 'selected' : '' }}>{{ $o->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Sales Type</label>
                        <div class="col-md-8">
                            <select class="form-control" name="sales_type" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                <option value="" {{ old('sales_type', $sales_type) == '' ? 'selected' : '' }}>All</option>
                                <option value="S" {{ old('sales_type', $sales_type) == 'S' ? 'selected' : '' }}>Sales</option>
                                <option value="V" {{ old('sales_type', $sales_type) == 'V' ? 'selected' : '' }}>Void</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Action</label>
                        <div class="col-md-8">
                            <select class="form-control" name="action" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                <option value="" {{ old('action', $action) == '' ? 'selected' : '' }}>All</option>
                                <option value="Activation" {{ old('action', $action) == 'Activation' ? 'selected' : '' }}>Activation</option>
                                <option value="Port-In" {{ old('action', $action) == 'Port-In' ? 'selected' : '' }}>Port-In</option>
                                <option value="Activation,Port-In" {{ old('action', $action) == 'Activation,Port-In' ? 'selected' :
                                 '' }}>Activation + Port-In</option>
                                <option value="RTR" {{ old('action', $action) == 'RTR' ? 'selected' : '' }}>RTR</option>
                                <option value="PIN" {{ old('action', $action) == 'PIN' ? 'selected' : '' }}>PIN</option>
                                <option value="RTR,PIN" {{ old('action', $action) == 'RTR,PIN' ? 'selected' : '' }}>RTR +
                                    PIN</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Status</label>
                        <div class="col-md-8">
                            <select class="form-control" name="status" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                <option value="" {{ old('status', $status) == '' ? 'selected' : '' }}>All</option>
                                <option value="N" {{ old('status', $status) == 'N' ? 'selected' : '' }}>New</option>
                                <option value="P" {{ old('status', $status) == 'P' ? 'selected' : '' }}>Processing</option>
                                <option value="C" {{ old('status', $status) == 'C' ? 'selected' : '' }}>Completed</option>
                                <option value="Q" {{ old('status', $status) == 'Q' ? 'selected' : '' }}>Port-In Requested</option>
                                <option value="R" {{ old('status', $status) == 'R' ? 'selected' : '' }}>Action.Required</option>
                                <option value="F" {{ old('status', $status) == 'F' ? 'selected' : '' }}>Failed</option>
                                <option value="I" {{ old('status', $status) == 'I' ? 'selected' : '' }}>Initiating</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Note</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="note" value="{{ $note }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Denomination</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="denomination" value="{{ $denomination }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">SIM Type</label>
                        <div class="col-md-8">
                            <select class="form-control" name="sim_type" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                <option value="" {{ old('sim_type', $sim_type) == '' ? 'selected' : '' }}>All</option>
                                <option value="B" {{ old('sim_type', $sim_type) == 'B' ? 'selected' : '' }}>Bundled</option>
                                <option value="P" {{ old('sim_type', $sim_type) == 'P' ? 'selected' : '' }}>Wallet</option>
                                <option value="R" {{ old('sim_type', $sim_type) == 'R' ? 'selected' : '' }}>Regular</option>
                                <option value="C" {{ old('sim_type', $sim_type) == 'C' ? 'selected' : '' }}>Consignment</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">User ID</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="user_id" value="{{ old('user_id', $user_id) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Tx.ID</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="id" value="{{ $id }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Account Name</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="account_name" value="{{ $account_name }}"/>
                        </div>
                    </div>
                </div>

                                @if (in_array(Auth::user()->account_type, ['L']) && in_array(Auth::user()->user_id, ['admin', 'thomas', 'system']))
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Vendor</label>
                                        <div class="col-md-8">
                                            <select class="form-control" name="api_vendor" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                                <option value="" {{ old('api_vendor', $api_vendor) == '' ? 'selected' : '' }}>All</option>
                                                @foreach ($api_vendors as $o)
                                                    <option value="{{ $o->code }}" {{ old('api_vendor', $api_vendor) == $o->code ? 'selected' : '' }}>{{ $o->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">R.M</label>
                                        <div class="col-md-8">
                                            <input type="text" class="form-control" name="seq" value="{{ $seq }}"/>
                                        </div>
                                    </div>
                                </div>
                                @endif

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Product Name</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="product" value="{{ $product }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Account ID</label>
                        <div class="col-md-8">
                                <input type="text" class="form-control" name="account_id" value="{{ old('account_id', $account_id) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Account IDs</label>
                        <div class="col-md-8">
                            <textarea class="form-control" name="account_ids" rows="2">{{ $account_ids }}</textarea>
                        </div>
                    </div>
                </div>


                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Phones</label>
                        <div class="col-md-8">
                            <textarea class="form-control" name="phones" rows="2">{{ $phones }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">SIMs</label>
                        <div class="col-md-8">
                            <textarea class="form-control" name="sims" rows="2">{{ $sims }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">ESNs</label>
                        <div class="col-md-8">
                            <textarea class="form-control" name="esns" rows="2">{{ $esns }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        @if (in_array(Auth::user()->account_type, ['L']) && in_array(Auth::user()->user_id, ['admin', 'thomas', 'system']))
                            <label class="col-md-4 control-label">Suggestion Link</label>
                            <div class="col-md-8">
                                <a href="/admin/reports/rtr-q">QUEUE REPORT</a>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <div class="form-group">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-info btn-sm" onclick="refresh_all()">Refresh All</button>
                            <button type="button" class="btn btn-primary btn-sm" id="btn_search" onclick="search()">Search</button>
                            <button type="button" class="btn btn-info btn-sm" onclick="show_batch_lookup()">Batch Lookup</button>
                            <button type="button" class="btn btn-info btn-sm" onclick="excel_export()">Export</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>


    <table class="table table-bordered table-hover table-condensed filter">
        <thead>
        <tr>
            <th colspan="23">Total {{ $total_count }} record(s)</th>
        </tr>
        <tr>
            <th>Tx.ID</th>
            <th>Type</th>
            <th>Account</th>
            <th>LOC.State</th>
            <th>Action</th>
            <th>Status</th>
            <th>Note</th>
            <th>Note2</th>
            <th>Product</th>
            <th>Denom($)</th>
            <th>Denom.Name</th>
            <th>RTR.M</th>
            <th>Total($)</th>
            <th>Vendor.Fee($)</th>
            <th>API.Activated?</th>
            @if (in_array(Auth::user()->account_type, ['L']) && in_array(Auth::user()->user_id, ['admin', 'thomas', 'system']))
            <th>Vendor</th>
            @endif
            <th>SIM</th>
            <th>R.M</th>
            @if (in_array(Auth::user()->account_type, ['L']))
            <th>ESN</th>
            @endif
            <th>NPA</th>
            <th>Phone/PIN</th>
            @if (in_array(Auth::user()->account_type, ['L']))
            @endif
            <th>User.ID</th>
            <th>Created.Date</th>
            @if (in_array(Auth::user()->account_type, ['L']) && Auth::user()->user_id == 'admin')
            <th></th>
            @endif
            <th></th>
        </tr>
        </thead>
        <tbody>
        @if (isset($transactions) && count($transactions) > 0)
            @foreach ($transactions as $o)
                <tr style="{{ $o->type_name == 'Void' ? 'color:red' : '' }}">
                    <td>{{ $o->id }}</td>
                    <td style="{{ $o->type == 'Void' ? 'color:red;' : '' }}">{{ $o->type}}</td>
                    @if (in_array(Auth::user()->account_type, ['L']))
                        <td>{!! Helper::get_parent_name_html($o->account_id) !!} <span>{!! Helper::get_hierarchy_img($o->account_type) !!}</span>{{ $o->account_name . ' ( ' . $o->account_id . ' )' }}</td>
                    @else
                        <td><span>{!! Helper::get_hierarchy_img($o->account_type) !!}</span>{{ $o->account_name . ' ( ' . $o->account_id . ' )' }}</td>
                    @endif
                    <td>{{ $o->loc_state }}</td>
                    <td>{{ $o->action }}</td>

                    <td>
                        @if ($o->status == 'R')
                            @if($o->product_id == 'WBMBA' || $o->product_id == 'WBMRA' || $o->product_id == 'WBMPA')
                                <a href="/admin/reports/transaction/{{ $o->id }}">{!! $o->status_name() !!} <span  class="btn btn-primary btn-sm" style="padding: 6px 8px;">Edit</span> </a>
                            @else
                                {!! $o->status_name() !!}
                            @endif
                        @else
                            <strong>{!! $o->status_name() !!}</strong>
                        @endif

                        @if (($o->status == 'F' && $o->product_id == 'WMLL') || ($o->status == 'R' && $o->action == 'Port-In'))
                            <form method="post" action="/admin/reports/transaction/retry" onsubmit="myApp.showLoading()">
                                {!! csrf_field() !!}
                                <input type="hidden" name="id" value="{{ $o->id }}"/>
                                <button class="btn btn-primary btn-sm">Retry</button>
                            </form>
                        @endif
                        @if ($o->action == 'Port-In')
                            <br><small>Note: Port Message({{ empty($o->portstatus) ? '' : $o->portstatus }})</small>
                        @endif
                    </td>

                    <td>
                    @if (!empty($o->note))
                        {{ $o->note }}
                    @endif

                    @if (!empty($o->pref_pin) && in_array(Auth::user()->user_id, ['admin', 'thomas', 'system']))
                        PIN - {{ $o->pref_pin }}
                    @endif
                    </td>
                    <td>{{ $o->note2 }}</td>
                    <td><a href="javascript:show_detail('{{ $o->id }}')">{{ $o->product_name }}</a></td>
                    <td>${{ $o->denom }}</td>
                    <td>{{ $o->denom_name }}</td>
                    <td>{{ $o->rtr_month }}</td>
                    <td style="{{ $o->type == 'Void' ? 'color:red;' : '' }}">${{ $o->collection_amt }}</td>
                    <td style="{{ $o->type == 'Void' ? 'color:red;' : '' }}">${{ $o->fee + $o->pm_fee }}</td>
                    <td>{{ $o->api == 'Y' ? 'YES' : '-' }}</td>
                    @if (in_array(Auth::user()->account_type, ['L']) && in_array(Auth::user()->user_id, ['admin', 'thomas', 'system']))
                    <td>{{ $o->vendor_code }}</td>
                    @endif
                    <td>
                        @if (!empty($o->sim))
                        {{ $o->sim }} 
                        @php
                            $sim_obj = \App\Model\StockSim::where('sim_serial', $o->sim)->where('product',
                            $o->product_id)->first();
                        @endphp
                        <br> Type: {{ empty($sim_obj) ? 'BYOS' : $sim_obj->type_name }}
                        <br> Supplier: {{ empty($sim_obj) ? 'BYOS' : ($sim_obj->is_byos == 'Y' ? 'BYOS SIM' : $sim_obj->supplier) }}
                        @endif
                    </td>
                    <td>{{ $o->seq }}</td>
                    @if (in_array(Auth::user()->account_type, ['L']))
                    <td>
                        @if (!empty($o->esn))
                            {{ $o->esn }}
                            @php
                                $esn_obj = \App\Model\StockESN::where('esn', $o->esn)->where('product',
                                $o->product_id)->first();
                            @endphp
                            <br> Model: {{ empty($esn_obj) ? 'BYOD' : $esn_obj->supplier_model }}
                            <br> Supplier: {{ empty($esn_obj) ? 'BYOD' : ($esn_obj->is_byod == 'Y' ? 'BYOD ESN' : $esn_obj->supplier) }}
                        @endif
                    </td>
                    @endif
                    <td>{{ $o->npa }}</td>
                    <td>{{ $o->action == 'PIN' ? (Auth::user()->account_type == 'L' ? $o->pin : Helper::mask_pin($o->pin)) : $o->phone }}</td>
                    @if (in_array(Auth::user()->account_type, ['L']))
                    @endif
                    <td>{{ $o->created_by }}</td>
                    <td>{{ $o->cdate }}</td>
                    <td>
                    @if (in_array(Auth::user()->account_type, ['L']) && Auth::user()->user_id == 'admin')
                        @if (empty($o->seq) && $o->status == 'C' && $o->type != 'Void')
                            @if ($o->action != 'PIN')
                                <a href="/admin/reports/transaction/rtrque/{{ $o->id }}">Create RTR QUE</a>
                            @endif
                        @endif
                    @endif
                    </td>
                    <td>{{ $o->curtime }}</td>
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
                <th colspan="12">Total {{ $total_count }} record(s)</th>
                <th>${{ number_format($collection_amt, 2) }}</th>
                <th>${{ number_format($fee, 2) }}</th>
                <th colspan="19"></th>
            </tr>
        </tfoot>
    </table>

    <div class="text-right">
        {{ $transactions->appends(Request::except('page'))->links() }}
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

    <div class="modal" id="div_transaction_detail" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Transaction Detail</h4>
                </div>
                <div class="modal-body">

                    <form id="frm_transaction" class="form-horizontal filter" method="post" style="padding:15px;">

                        <div class="row" style="border-bottom:solid 1px #dedede; margin-top:-25px;">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Tx.ID</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="id" name="id" readonly/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Carrier</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="carrier" name="carrier" readonly/>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-4 control-label">API</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="api" readonly="readonly"/>
                                    </div>
                                </div>


                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Type</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="type" name="type" readonly/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Action</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="action" name="action" readonly/>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row" style="border-bottom:solid 1px #dedede; margin-top:5px;">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Product</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="product" name="product" readonly/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Amount($)</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="denom" name="denom" readonly/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">SIM</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="sim" name="sim" readonly/>
                                    </div>
                                </div>
                                @if (in_array(Auth::user()->account_type, ['L']))
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">ESN</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="esn" id="esn" readonly/>
                                    </div>
                                </div>
                                @endif
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Pref. Area Code</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="npa" id="npa" readonly/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Act. Code</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="afcode" id="afcode" readonly/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">First Name</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="first_name" id="first_name" readonly/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Last Name</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="last_name" id="last_name" readonly/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Address 1</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="address1" id="address1" readonly/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Address 2</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="address2" id="address2" readonly/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">City</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="city" id="city" readonly/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">State</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="state" id="state" readonly/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Zip</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="zip" id="zip" maxlength="5" readonly/>
                                    </div>
                                </div>


                            </div>

                        </div>

                        <div id="port-in-row" class="row" style="border-bottom:solid 1px #dedede; margin-top: 5px;">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Port-In Number</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="number_to_port" id="number_to_port" maxlength="10" readonly/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Account #</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="account_no" id="account_no" readonly/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Port-In From</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="current_carrier" id="current_carrier" readonly/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Account PIN</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="account_pin" id="account_pin" readonly/>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row" style="border-bottom:solid 1px #dedede; margin-top: 5px;">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">First Name</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="first_name" id="first_name" readonly/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Call Back #</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="call_back_phone" id="call_back_phone" readonly/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Pref. PIN #</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="pref_pin" id="pref_pin" readonly/>
                                    </div>
                                </div>
                                @if (in_array(Auth::user()->account_type, ['L']))
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">LOC.ID</label>
                                    <div class="col-sm-8">
                                        <input type="email" class="form-control" name="loc_id" id="loc_id" maxlength="7" readonly/>
                                    </div>
                                </div>
                                @endif
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Last Name</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="last_name" id="last_name" readonly/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Email</label>
                                    <div class="col-sm-8">
                                        <input type="email" class="form-control" name="email" id="email" readonly/>
                                    </div>
                                </div>
                                @if (in_array(Auth::user()->account_type, ['L']))
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">LOC.State</label>
                                    <div class="col-sm-8">
                                        <input type="email" class="form-control" name="loc_state" id="loc_state" readonly/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Outlet.ID</label>
                                    <div class="col-sm-8">
                                        <input type="email" class="form-control" name="outlet_id" maxlength="9" id="outlet_id" readonly/>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        <div class="row" style="border-bottom:solid 1px #dedede; margin-top: 5px; margin-bottom:0px;">
                            <div class="col-sm-6" id="div_detail_phone">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label" style="color:red;">Activated Phone #</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="phone" id="phone" maxlength="10"/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6" id="div_detail_pin" style="display:none;">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label" style="color:red;">PIN #</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="pin" id="pin"/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label" style="color:red;">Status</label>
                                    <div class="col-sm-8">
                                        <select class="form-control" id="status" name="status" onchange="status_changed()" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                            <option value="">Please Select</option>
                                            <option value="F">Failed</option>
                                            <option value="Q">Port-In Requested</option>
                                            <option value="C">Completed</option>
                                            <option value="R">Action.Required</option>
                                            <option value="V">Mark as Voided ( When Failed )</option>
                                        </select>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row" style="margin-top: 5px; margin-bottom:0px;">
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label class="col-sm-2 control-label">Note</label>
                                    <div class="col-sm-10">
                                        <textarea id="note" name="note" class="form-control" rows="3" style="margin-top:5px; height:80px; margin-bottom:5px;" placeholder="Please enter note"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row" style="margin-top: 5px; margin-bottom:0px;">
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label class="col-sm-2 control-label">Note2</label>
                                    <div class="col-sm-10">
                                        <textarea id="note2" name="note2" class="form-control" rows="3" style="margin-top:5px; height:80px; margin-bottom:5px;" placeholder="Please enter note"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                            @if(Auth::user()->account_type == 'L' && in_array(Auth::user()->user_id, ['admin', 'thomas', 'system']) )
                            <button type="button" class="btn btn-primary  pull-right" onclick="update_note2()">Update Note2</button>
                            @endif
                        </form>
                    </div>
                    <div class="modal-footer" style="margin-right:15px;">
                        @if(Auth::user()->account_type == 'L' && (getenv('APP_ENV') == 'production' && in_array
                        (Auth::user()->user_id, ['admin', 'thomas', 'system']) || getenv('APP_ENV') == 'local'))
                        <button type="button" class="btn btn-primary pull-left" id="btn_void_transaction"
                                onclick="void_transaction()">Void Transaction</button>
                        @endif

                        <button type="button" class="btn btn-primary  pull-left" id="btn_update_boom" onclick="update_detail_boom()">Update (Boom)</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary  pull-left" id="btn_update" onclick="update_detail()">Update</button>
                        <button type="button" class="btn btn-primary  pull-left" id="btn_action_required" onclick="action_required()" style="display: none;">Action Required</button>

                    </div>
                </div>
            </div>
        </div>


        <div class="modal" id="div_batch_lookup" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                    aria-hidden="true">x</span></button>
                        <h4 class="modal-title" id="title">Batch Lookup</h4>
                    </div>
                    <div class="modal-body">
                        <form id="frm_batch_lookup" action="/admin/reports/transaction/batch-lookup" target="ifm_download" class="form-horizontal filter"
                              method="post" style="padding:15px;">
                            {{ csrf_field() }}
                            <div class="form-group">
                                <div class="col-sm-12">
                                    <label>
                                        Excel file will be downloaded after submit.
                                    </label>
                                </div>
                            </div>

                            <div class="col-group">
                                <label class="col-sm-4 control-label required">Date: </label>
                                <div class="col-sm-8">
                                    <input type="text" style="width:165px; float:left;" class="form-control" id="sdate_batch" name="sdate_batch" value="{{ old('sdate', $sdate) }}"/>
                                    <span class="control-label" style="margin-left:5px; float:left;"> ~ </span>
                                    <input type="text" style="width:165px; margin-left: 5px; float:left;" class="form-control" id="edate_batch" name="edate_batch" value="{{ old('edate', $edate) }}"/>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-md-4 control-label">Quick Selection</label>
                                <div class="col-md-8">
                                    <select class="form-control" name="quick_batch" id="quick_batch" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}' onchange="set_date_batch()">
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
                                <label class="col-md-4 control-label">Action</label>
                                <div class="col-md-8">
                                    <select class="form-control" name="action_batch" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                        <option value="" {{ old('action_batch', $action) == '' ? 'selected' : '' }}>All</option>
                                        <option value="Activation" {{ old('action_batch', $action) == 'Activation' ? 'selected' : '' }}>Activation</option>
                                        <option value="Port-In" {{ old('action_batch', $action) == 'Port-In' ? 'selected' : '' }}>Port-In</option>
                                        <option value="Activation,Port-In" {{ old('action_batch', $action) == 'Activation,Port-In' ? 'selected' : '' }}>Activation + Port-In</option>
                                        <option value="RTR" {{ old('action_batch', $action) == 'RTR' ? 'selected' : '' }}>RTR</option>
                                        <option value="PIN" {{ old('action_batch', $action) == 'PIN' ? 'selected' : '' }}>PIN</option>
                                        <option value="RTR,PIN" {{ old('action_batch', $action) == 'RTR,PIN' ? 'selected' : '' }}>RTR +
                                            PIN</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-md-4 control-label required">Carrier: </label>
                                <div class="col-md-8">
                                    <select class="form-control" name="carrier_batch" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                        <option value="" {{ old('carriercarrier_batch', $carrier) == '' ? 'selected' : '' }}>All</option>
                                        @foreach ($carriers as $o)
                                            <option value="{{ $o->name }}" {{ old('carrier_batch', $carrier) == $o->name ? 'selected' : '' }}>{{ $o->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-md-4 control-label">Vendor</label>
                                <div class="col-md-8">
                                    <select class="form-control" name="api_vendor_batch" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                        <option value="" {{ old('api_vendor_batch', $api_vendor) == '' ? 'selected' : '' }}>All</option>
                                        @foreach ($api_vendors as $o)
                                            <option value="{{ $o->code }}" {{ old('api_vendor_batch', $api_vendor) == $o->code ? 'selected' : '' }}>{{ $o->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-4 control-label required">Category: </label>
                                <div class="col-sm-8">
                                    <input type="radio" name="category" value="MDN"> MDN
                                    <input type="radio" name="category" value="SIM"> SIM
                                    <input type="radio" name="category" value="ESN"> ESN
                                    <input type="radio" name="category" value="ACT"> ACCOUNT
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label required">Include 'SPP Only'</label>
                                <div class="col-sm-8">
                                    <input type="radio" name="spp_only" value="N" checked> NO
                                    <input type="radio" name="spp_only" value="Y"> YES
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label required">Lookup Items: </label>
                                <div class="col-sm-8">
                                    <textarea id="n_batch_lines" name="batch_lines" rows="10" style="width:100%; line-height: 150%;"
                                              onchange="count_batch_lines()"></textarea><br/>
                                    Total <span id="n_batch_lines_qty">0</span> lines.
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer" style="margin-right:15px;">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button class="btn btn-primary" onclick="batch_lookup()">Submit</button>
                    </div>
                </div>
            </div>
        </div>

        <div style="display:none">
            <iframe name="ifm_download"></iframe>
        </div>
    @stop
