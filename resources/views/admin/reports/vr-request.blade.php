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

        var products = [];

        function excel_export() {
            $('#excel').val('Y');
            $('#frm_search').submit();
        }

        function refresh_all() {
            window.location.href = '/admin/reports/vr-request';
        }

        function show_detail(id) {
            myApp.showLoading();
            $.ajax({
                url: '/admin/reports/vr-request/load-detail',
                data: {
                    _token: '{!! csrf_token() !!}',
                    id: id
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {

                        var o = res.data;
                        var p = res.products;


                        $('#vr_id').val(o.id);
                        $('#vr_id_title').text(o.id);
                        $('#vr_category').val(o.category);

                        // HIDE
                        $('#update').hide();
                        $('#confirm_price').hide();
                        // $('#reject').hide();
                        $('#cancel').hide();
                        $('#shipped').hide();
                        $('#cod_collected').hide();
                        $('#direct_deposit_paid').hide();
                        $('#save').hide();

                        if (p.length > 0) {

                            var prod_html = '<table class="table table-bordered table-hover table-condensed filter">' +
                                    '<tr><th>ID</th><th>Category</th><th>Model</th><th>Price</th><th>Qty</th><th>Dropship</th></tr>';

                            $.each(p, function(k, v){
                                var model = v.model;
                                var is_dropship = (v.is_dropship == null) ? '' : v.is_dropship;

                                if (v.url) {
                                    model = "<a href='" + v.url + "' target='_blank'>" + v.model + "</a>" ;
                                }
                                var key = v.prod_id; //v.prod_sku.replace(/\W+/g, '_');
                                prod_html += "<tr><td>" + v.prod_id + "</td><td>" +
                                        v.category + "</td><td>" +
                                        model +
                                        "</td><td style='width:60px;'>$<span id='denom_" + key + "'>" +
                                        (v.order_price / v.qty) + "</span></td><td>" +
                                        "<input type='text' class='update-count' id='count_"+ key +"' value='" + v.qty + "' onchange='change_price(true)' />" +
                                        "<td>" + is_dropship + "</td>"+
                                        "<input type='hidden' name='product[]' value='" + key + "'/>" +
                                        "<input type='hidden' id='id_"+ key +"' value='" + v.id + "'/>" +
                                        "</td></tr>";

                                products.push([v.id,v.prod_id,v.order_price,v.qty]);
                            });

                            prod_html += '</table>';

                            $('#vr_products').html(prod_html);
                            $('#vr_order').val(o.order);
                            $('#vr_promo_code').val(o.promo_code);

                            if (o.price) {
                                $('#vr_price').val(o.price);
                                $('#vr_shipping').val(o.shipping);
                                $('#vr_total').val(o.total);
                                $('#vr_kick_back').val(o.kick_back);
                                if(o.kick_back != 0) {
                                    $('#vr_kick_back').prop('disabled', true);
                                    $('#update_kick_back').prop('disabled', true);
                                    $('#update_btn').prop('disabled', true);
                                }else{
                                    $('#vr_kick_back').prop('disabled', true);
                                    $('#update_kick_back').prop('disabled', true);
                                    $('#update_btn').prop('disabled', false);
                                }
                            }
                            // $('#vr_pay_method_paypal').prop('checked', o.pay_method == 'PayPal');
                            // $('#vr_pay_method_cod').prop('checked', o.pay_method == 'COD');


                            $('#vr_op_comments').val(o.op_comments);
                            $('#vr_status_name').html(o.status_name);
                            $('#vr_payment_name').val(o.pay_method);
                            if (o.pay_method !== 'PayPal') {
                                $('#vr_payment_name').prop('disabled', false);
                            }
                            $('#vr_current_status').val(o.status);
                            $('#vr_status').val('');

                            $('#vr_address1').val(o.address1);
                            $('#vr_address2').val(o.address2);
                            $('#vr_city').val(o.city);
                            $('#vr_state').val(o.state);
                            $('#vr_zip').val(o.zip);

                            // limit functions & show button by status
                            switch (o.status) {
                                case 'RQ': // Requested : show only Confirmed Price & reject button and allow all actions
                                    $('#update').show();
                                    $('#confirm_price').show();
                                    // $('#reject').show();
                                    $('#cancel').show();

                                    $("#vr_price").prop('disabled', false);
                                    $("#vr_shipping").prop('disabled', false);
                                    // $("#vr_pay_method_paypal").prop('disabled', false);
                                    // $("#vr_pay_method_cod").prop('disabled', false);
                                    break;
                                case 'CP': // Confirmed Price : show shipped & reject
                                    if (o.pay_method == 'COD') {
                                        $('#shipped').show();
                                    }
                                    $('#update').show();
                                    if (o.pay_method == 'Direct Deposit') {
                                        $('#direct_deposit_paid').show();
                                    }

                                    $('#cancel').show();

                                    $("#vr_price").prop('disabled', false);
                                    $("#vr_shipping").prop('disabled', false);
                                    // $("#vr_pay_method_paypal").prop('disabled', false);
                                    // $("#vr_pay_method_cod").prop('disabled', false);
                                    $('[id^="count_"]').each(function(){
                                        $(this).prop('disabled', false);
                                    });
                                    $('#vr_payment_name').prop('disabled', false);
                                    break;
                                case 'PC': // PayPal Paid : show shipped
                                    $('#shipped').show();
                                    $('#cancel').show();
                                    $("#vr_price").prop('disabled', true);
                                    $("#vr_shipping").prop('disabled', true);
                                    // $("#vr_pay_method_paypal").prop('disabled', true);
                                    // $("#vr_pay_method_cod").prop('disabled', true);
                                    $('[id^="count_"]').each(function(){
                                        $(this).prop('disabled', true);
                                    });
                                    $('#vr_payment_name').prop('disabled', true);
                                    break;
                                case 'SH': // Shipped : show COD Collected
                                    if (o.pay_method == 'COD') {
                                        $('#cod_collected').show();
                                    }
                                    $('#cancel').show();
                                    $("#vr_price").prop('disabled', true);
                                    $("#vr_shipping").prop('disabled', true);
                                    // $("#vr_pay_method_paypal").prop('disabled', true);
                                    // $("#vr_pay_method_cod").prop('disabled', true);

                                    $('[id^="count_"]').each(function(){
                                        $(this).prop('disabled', true);
                                    });
                                    $('#vr_payment_name').prop('disabled', true);
                                    break;
                                default:
                                    $("#vr_order").prop('disabled', true);
                                    $("#vr_promo_code").prop('disabled', true);
                                    $("#vr_price").prop('disabled', true);
                                    $("#vr_shipping").prop('disabled', true);
                                    // $("#vr_pay_method_paypal").prop('disabled', true);
                                    // $("#vr_pay_method_cod").prop('disabled', true);
                                    $('#cancel').show();
                                    $('[id^="count_"]').each(function(){
                                        $(this).prop('disabled', true);
                                    });
                                    $('#vr_payment_name').prop('disabled', true);
                                    break;
                            }

                            $('#vr_tracking_no').val(o.tracking_no);
                            $('#vr_payment_note').val(o.payment_note);
                            $('#vr_vendor_note').val(o.vendor_note);

                            $('#vr_memo').val(o.memo);
                            $('#panel1').collapse('show');
                            $('#panel4').collapse('hide');
                            $('.panel-order').show();
                            $('.panel-comments').hide();
                        }

                        if (o.comments) {
                            $('#vr_comments').val(o.comments);
                            $('#vr_op_comments2').val(o.op_comments);
                            $('#vr_status2').val(o.status);

                            $('#save').show();

                            $('#panel4').collapse('show');
                            $('#panel1').collapse('hide');

                            $('.panel-order').hide();
                            $('.panel-comments').show();

                        }

                        $('#update_memo').show();
                        $('#vr_last_modified').text(o.last_modified);
                        $('#virtual_rep_modal').modal();


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


        function change_price(change_qty) {

            var price = 0;

            if (change_qty) {

                products = [];

                $("input[name='product[]']").each(function(){

                    var key = $(this).val();
                    // var key = sku.replace(/\W+/g, '_');
                    var cnt = 0;

                    if ($('#count_' + key).val()) {
                        cnt = $.trim($('#count_' + key).val());
                    }

                    var prc = $.trim($('#denom_' + key).text());
                    var id = $.trim($('#id_' + key).val());

                    products.push([id,key,prc,cnt]);
                    price += prc * cnt;
                });
            } else {
                price = $('#vr_price').val() * 1;
            }

            var shipping = $.trim($('#vr_shipping').val());

            if (shipping) {
                shipping = $('#vr_shipping').val() * 1;
            } else {
                shipping = 0;
            }

            var total = price + shipping;

            $('#vr_price').val(price);
            $('#vr_shipping').val(shipping);
            $('#vr_total').val(total);
        }


        function update() {
            if ($('#vr_payment_name').val() == 'Balance'){
                myApp.showError('Can not change to Pay by Balance!');
                return;
            }
            save_vr('RQ');
        }

        function confirm_price() {
            save_vr('CP');
        }

        function reject() {
            save_vr('R');
        }

        function cancel() {
            save_vr('CC');
        }

        function shipped() {
            if ($.trim($('#vr_tracking_no').val())) {
                save_vr('SH');
            } else {
                myApp.showError('Please enter Tracking Number!');
                $('#vr_tracking_no').focus();
                return;
            }
        }

        function cod_collected() {
            save_vr('CO');
        }

        function direct_deposit_paid() {
            save_vr('PC');
        }

        function save_vr(status) {

            myApp.showLoading();

            $.ajax({
                url: '/admin/reports/vr_request/update',
                data: {
                    _token: '{!! csrf_token() !!}',
                    id: $('#vr_id').val(),
                    status: status,
                    status2: $('#vr_status2').val(),
                    op_comments: $('#vr_op_comments').val(),
                    op_comments2: $('#vr_op_comments2').val(),
                    price: $('#vr_price').val(),
                    shipping: $('#vr_shipping').val(),
                    total: $('#vr_total').val(),
                    tracking_no: $('#vr_tracking_no').val(),
                    pay_method: $('#vr_payment_name').val(),
                    memo: $('#vr_memo').val(),
                    products: products
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        $('#virtual_rep_modal').modal('hide');
                        myApp.showSuccess('Your request has been processed successfully!', function() {
                            myApp.showLoading();
                            $('#frm_search').submit();
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

        function apply_shipping_fee() {

            myApp.showLoading();

            $.ajax({
                url: '/admin/reports/vr_request/additional_shipping_fee',
                data: {
                    _token: '{!! csrf_token() !!}',
                    id: $('#vr_id').val(),
                    additional_shipping_fee: $('#vr_additional_shipping_fee').val()
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
        }

        function apply_to_debit() {

            myApp.showLoading();

            $.ajax({
                url: '/admin/reports/vr_request/apply_to_debit',
                data: {
                    _token: '{!! csrf_token() !!}',
                    id: $('#vr_id').val(),
                    amt: $('#vr_additional_shipping_fee_direct').val()
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
        }

        function update_btn() {
            $('#vr_kick_back').prop('disabled', false);
            $("#update_kick_back").prop('disabled', false);

        }

        function update_kick_back() {
            myApp.showLoading();
            $.ajax({
                url: '/admin/reports/vr_request/update/kickback',
                data: {
                    _token: '{!! csrf_token() !!}',
                    id: $('#vr_id').val(),
                    kick_back: $('#vr_kick_back').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        $('#virtual_rep_modal').modal('hide');
                        myApp.showSuccess('Your request has been processed successfully!', function() {
                            myApp.showLoading();
                            $('#frm_search').submit();
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

        function update_memo() {

            myApp.showLoading();

            $.ajax({
                url: '/admin/reports/vr_request/update/memo',
                data: {
                    _token: '{!! csrf_token() !!}',
                    id: $('#vr_id').val(),
                    tracking_no: $('#vr_tracking_no').val(),
                    memo: $('#vr_memo').val(),
                    payment_note: $('#vr_payment_note').val(),
                    vendor_note: $('#vr_vendor_note').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        $('#virtual_rep_modal').modal('hide');
                        myApp.showSuccess('Your request has been processed successfully!', function() {
                            myApp.showLoading();
                            $('#frm_search').submit();
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

        function search() {
            $('#excel').val('N');
            $('#frm_search').submit();
        }

    </script>

    <h4>Virtual Rep. Report</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post" action="/admin/reports/vr-request">
            {{ csrf_field() }}
            <input type="hidden" name="excel" id="excel"/>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Date</label>
                        <div class="col-md-8">
                            <input type="text" style="width:100px; float:left;" class="form-control" id="sdate" name="sdate" value="{{ old('sdate', $sdate) }}"/>
                            <span class="control-label" style="margin-left:5px; float:left;"> ~ </span>
                            <input type="text" style="width:100px; margin-left: 5px; float:left;" class="form-control" id="edate" name="edate" value="{{ old('edate', $edate) }}"/>
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
                        <label class="col-md-4 control-label">Category</label>
                        <div class="col-md-8">
                            <select class="form-control" name="category" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                <option value="" {{ old('category', $category) == '' ? 'selected' : '' }}>All</option>
                                <option value="O" {{ old('category', $category) == 'O' ? 'selected' : '' }}>Order</option>
                                <option value="C" {{ old('category', $category) == 'C' ? 'selected' : '' }}>Comments</option>
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
                                <option value="RQ" {{ old('status', $status) == 'RQ' ? 'selected' : '' }}>Requested</option>
                                <option value="CP" {{ old('status', $status) == 'CP' ? 'selected' : '' }}>Confirmed Price</option>
                                <option value="PC" {{ old('status', $status) == 'PC' ? 'selected' : '' }}>Paid</option>
                                <option value="SH" {{ old('status', $status) == 'SH' ? 'selected' : '' }}>Shipped</option>
                                <option value="C" {{ old('status', $status) == 'C' ? 'selected' : '' }}>Completed</option>
{{--                                <option value="R" {{ old('status', $status) == 'R' ? 'selected' : '' }}>Rejected</option>--}}
                                <option value="CC" {{ old('status', $status) == 'CC' ? 'selected' : '' }}>Canceled</option>
                            </select>
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
                            <textarea class="form-control" name="acct_ids" rows="3">{{ old('acct_ids', $acct_ids) }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Account Name</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="account_name" value="{{ old('account_name', $account_name) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Tracking #</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="tracking_no" value="{{ old('tracking_no', $tracking_no) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Payment Note</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="payment_note" value="{{ old('payment_note', $payment_note) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Vendor Note</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="vendor_note" value="{{ old('vendor_note', $vendor_note) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Shipping Required</label>
                        <div class="col-md-8">
                            <input type="checkbox" name="shipping_required" value="Y" {{ $shipping_required == 'Y' ? 'checked' : '' }}/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Payment Method</label>
                        <div class="col-md-8">
                            <select class="form-control" name="pay_method" data-jcf='{"wrapNative": false,
                            "wrapNativeOnMobile": false}'>
                                <option value="" {{ old('pay_method', $pay_method) == '' ? 'selected' : '' }}>All</option>
                                <option value="PayPal" {{ old('pay_method', $pay_method) == 'PayPal' ? 'selected' : ''
                                }}>PayPal</option>
                                <option value="COD" {{ old('pay_method', $pay_method) == 'COD' ? 'selected' : ''
                                }}>COD</option>
                                <option value="Direct Deposit" {{ old('pay_method', $pay_method) == 'Direct Deposit' ? 'selected' : ''
                                }}>Direct Deposit</option>
                                <option value="Balance" {{ old('pay_method', $pay_method) == 'Balance' ?
                                'selected' : ''
                                }}>Account Balance</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Is Dropship</label>
                        <div class="col-md-4">
                            <input type="checkbox" name="is_dropship" value="Y" {{ $is_dropship == 'Y' ? 'checked' : '' }}/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Invoice ID</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="invoice_id" value="{{ old('invoice_id', $invoice_id) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Paypal ID</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="paypal_txn_id" value="{{ old('paypal_txn_id', $paypal_txn_id) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Model</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="model" value="{{ old('model', $model) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <div class="col-md-8 text-right">
                            <button type="button" class="btn btn-info btn-sm" onclick="refresh_all()">Refresh All</button>
                            <button type="button" class="btn btn-primary btn-sm" id="btn_search" onclick="search()">Search</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="text-left">
        Total {{ $records->total() }} record(s).
    </div>

    <table class="table table-bordered table-hover table-condensed filter">
        <thead>
        <tr>
            <th>Q.ID</th>
            <th>Account</th>
            <th>State</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Tracking #</th>
            <th>Internal Memo</th>
            <th>Category</th>
            <th>Total</th>
            <th>Kickback</th>
            <th>Status</th>
            <th>Payment</th>
            <th>Invoice ID</th>
            <th>Paypal ID</th>
            <th>Payment.Note</th>
            <th>Vendor.Note</th>
            <th>Shipping</th>
            <th>Last.Updated</th>
            <th>Original.date</th>
        </tr>
        </thead>
        <tbody>
        @if (isset($records) && count($records) > 0)
            @foreach ($records as $o)
                <tr>
                    <td>{{ $o->id }}</td>
                    @if (in_array(Auth::user()->account_type, ['L']))
                        <td>{!! Helper::get_parent_name_html($o->account_id) !!} <span>{!! Helper::get_hierarchy_img($o->acct_type) !!}</span>{{ $o->acct_name . ' ( ' . $o->account_id . ' )' }}</td>
                    @else
                        <td><span>{!! Helper::get_hierarchy_img($o->acct_type) !!}</span>{{ $o->acct_name . ' ( ' . $o->account_id . ' )' }}</td>
                    @endif
                    <td style="text-align: center;">{{ $o->state }}</td>
                    <td>{{ $o->account_phone }}</td>
                    <td>{{ $o->account_email }}</td>
                    <td>{{ $o->tracking_no }}</td>
                    <td>{{ $o->memo }}</td>
                    <td><a href="javascript:show_detail({{ $o->id }})">{{ $o->category_name }}</a></td>
                    <td>@if ($o->total && $o->category == 'O') ${!! $o->total !!} @endif</td>
                    <td>{{ $o->kick_back }}</td>
                    <td>{!! $o->status_name() !!}</td>
                    <td>{!! $o->pay_method !!}</td>
                    <td>
                        {!! $o->pay_method != 'PayPal' ? '-' : $o->invoice_number !!}
                    </td>
                    <td>{!! $o->paypal_txn_id !!}</td>
                    <td>{!! $o->payment_note !!}</td>
                    <td>{!! $o->vendor_note !!}</td>
                    <td style="text-align: center;">{{ $o->shipping_method == 'P' ? 'Pick UP' : 'Shipping' }}</td>
                    <td>{{ $o->last_modified }}</td>
                    <td>{{ $o->cdate }} ({{$o->created_by}})</td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="19" class="text-center">No Record Found</td>
            </tr>
        @endif
        </tbody>
    </table>

    <div class="text-right">
        {{ $records->appends(Request::except('page'))->links() }}
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

    <div class="modal fade" id="virtual_rep_modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Virtual Representative(ID: <span id="vr_id_title"></span>)</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="vr_id"/>
                    <input type="hidden" id="vr_category"/>

                    <!-- Start Accordion -->
                    <div class="panel-group" id="accordion1">
                        <div class="panel panel-default panel-order">
                            <div class="panel-heading" id="heading1">
                                <h6 class="panel-title">
                                    <a class="collapsed">
                                        ORDER
                                    </a>
                                </h6>
                            </div>

                            <div id="panel1" class="panel-collapse collapse">
                                <div class="panel-body">
                                    <p><span class="highlight default2">Order of Device, Posters, Brochures, etc.</span></p>
                                    <p id="vr_products"></p>

                                    <textarea class="form-control" rows="2" id="vr_promo_code" style="margin-top: 20px" disabled="disabled"></textarea>

                                    <textarea class="form-control" rows="5" id="vr_order" style="margin-top: 20px" disabled="disabled"></textarea>


                                    <p style="margin-top: 20px"><span class="highlight default2">Reply</span>
                                        <textarea class="form-control" rows="5" id="vr_op_comments"></textarea>
                                    </p>

                                    <p style="margin-top: 20px"><span class="highlight default2">Price($)</span>
                                        <input type="number" class="form-control" id="vr_price" onchange="change_price(false)">
                                    </p>

                                    <p style="margin-top: 20px"><span class="highlight default2">Shipping($)</span>
                                        <input type="number" class="form-control" id="vr_shipping" onchange="change_price(false)">
                                    </p>

                                    <p style="margin-top: 20px"><span class="highlight default2">Total($)</span>
                                        <input type="number" class="form-control" id="vr_total" disabled="disabled">
                                    </p>

                                    <p style="margin-top: 20px"><span class="highlight default2">Kickback</span>
                                        <input type="number" class="form-control" id="vr_kick_back"/>
                                        @if (Auth::check() && Auth::user()->account_type == 'L' && (in_array(Auth::user()->user_id, ['thomas', 'admin', 'system'])))
                                            <button class="btn btn-default btn-sm" id="update_kick_back" type="button" onclick="update_kick_back()">Update Kick Back</button>
                                            <button class="btn btn-default btn-sm" id="update_btn" type="button" onclick="update_btn()">Update</button>
                                        @endif
                                    </p>

                                    <p style="margin-top: 20px"><span class="highlight default2">Address1</span>
                                        <input type="text" class="form-control" id="vr_address1">
                                    </p>
                                    <p style="margin-top: 20px"><span class="highlight default2">Address2</span>
                                        <input type="text" class="form-control" id="vr_address2">
                                    </p>
                                    <p style="margin-top: 20px"><span class="highlight default2">city</span>
                                        <input type="text" class="form-control" id="vr_city">
                                    </p>
                                    <p style="margin-top: 20px"><span class="highlight default2">state</span>
                                        <input type="text" class="form-control" id="vr_state">
                                    </p>
                                    <p style="margin-top: 20px"><span class="highlight default2">zip</span>
                                        <input type="text" class="form-control" id="vr_zip">
                                    </p>

                                    <p style="margin-top: 20px"><span class="highlight default2">Status</span>
                                        <span id="vr_status_name"></span>
                                        <input type="hidden" id="vr_current_status"/>
                                        <input type="hidden" id="vr_status"/>
                                    </p>

                                    <p style="margin-top: 20px"><span class="highlight default2">Payments</span>
                                        <select id="vr_payment_name">
                                            <option value="PayPal">PayPal</option>
                                            <option value="COD">COD</option>
                                            <option value="Direct Deposit">Direct Deposit</option>
                                            <option value="Balance">Pay by Account Balance</option>
                                        </select>
                                    </p>

                                    <p style="margin-top: 20px"><span class="highlight default2">Payment Note</span>
                                        <input type="text" class="form-control" id="vr_payment_note">
                                    </p>

                                    <p style="margin-top: 20px"><span class="highlight default2">Vendor Note</span>
                                        <input type="text" class="form-control" id="vr_vendor_note">
                                    </p>

                                    <p style="margin-top: 20px"><span class="highlight default2">Tracking No.</span>
                                        <input type="text" class="form-control" id="vr_tracking_no">
                                    </p>

                                    <p style="margin-top: 20px"><span class="highlight default2">Memo</span>
                                        <textarea class="form-control" rows="5" id="vr_memo"></textarea>
                                    </p>
                                    @if (Auth::check() && Auth::user()->account_type == 'L')
                                    <button class="btn btn-default btn-sm" id="update_memo" type="button" onclick="update_memo()">Update Memo</button>
                                    @endif
                                </div>
                            </div>
                        </div>


                        <div class="panel panel-default panel-comments">
                            <div class="panel-heading" id="heading4">
                                <h6 class="panel-title">
                                    <a class="collapsed">
                                        GENERAL REQUEST
                                    </a>
                                </h6>
                            </div>
                            <div id="panel4" class="panel-collapse collapse">
                                <div class="panel-body">
                                    <p>
                                        <span class="highlight default2">Inquiries, Technical Issues, Comments</span>
                                        <textarea class="form-control" rows="7" id="vr_comments" style="margin-top: 20px" disabled="disabled"></textarea>

                                    </p>

                                    <p><span class="highlight default2">Reply</span>
                                        <textarea class="form-control" rows="7" id="vr_op_comments2" style="margin-top: 20px"></textarea>
                                    </p>

                                    <p><span class="highlight default2">Status</span>

                                        <select class="form-control" id="vr_status2" style="margin-top: 20px">
                                            <option value="RQ">Requested</option>
                                            <option value="C">Completed</option>
                                        </select>
                                    </p>

                                </div>
                            </div>
                        </div>

                    </div>
                    <!-- End Accordion -->

                    <hr>
                    <div class="row">
                        <div class="col-sm-6">
                            <p><span class="highlight default2">Additional Shipping Fee.($)</span>
                                <input type="text" class="form-control" id="vr_additional_shipping_fee">
                            </p>
                        </div>
                        <div class="col-sm-6">
                            @if (Auth::check() && Auth::user()->account_type == 'L')
                                <button class="btn btn-info btn-xs" type="button" style="margin-top: 18px;"
                                        onclick="apply_shipping_fee()">Apply Shipping Fee</button>
                            @endif
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-sm-6">
                            <p><span class="highlight default2">Additional Shipping Fee.($) - Direct </span>
                                <input type="text" class="form-control" id="vr_additional_shipping_fee_direct">
                            </p>
                        </div>
                        <div class="col-sm-6">
                            @if (Auth::check() && Auth::user()->account_type == 'L')
                                <button class="btn btn-info btn-xs" type="button" style="margin-top: 18px;"
                                        onclick="apply_to_debit()">Apply to Debit</button>
                            @endif
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary btn-sm" type="submit" data-dismiss="modal">Close</button>
                    @if (Auth::check() && Auth::user()->account_type == 'L')
                        <button class="btn btn-default btn-sm" id="save" type="button" onclick="save_vr('')">Submit</button>
                        <button class="btn btn-default btn-sm" id="update" type="button" onclick="update()">Update</button>
                        <button class="btn btn-info btn-sm" id="confirm_price" type="button" onclick="confirm_price()">Confirm Price</button>
                        <button class="btn btn-info btn-sm" id="direct_deposit_paid" type="button" onclick="direct_deposit_paid()">DD Paid</button>
                        <button class="btn btn-info btn-sm" id="shipped" type="button" onclick="shipped()">Shipped</button>
                        <button class="btn btn-info btn-sm" id="cod_collected" type="button" onclick="cod_collected()">COD Collected</button>
{{--                        <button class="btn btn-warning btn-sm" id="reject" type="button" onclick="reject()">Rejected</button>--}}
                        @if (in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']))
                            <button class="btn btn-warning btn-sm" id="cancel" type="button" onclick="cancel()">Cancel</button>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop
