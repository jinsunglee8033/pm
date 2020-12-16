@extends('admin.layout.default')

@section('content')

    <script type="text/javascript">
        var onload_events = window.onload;
        window.onload = function() {

            if (onload_events) {
                onload_events();
            }

            $("#sdate").datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $("#edate").datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $("#used_sdate").datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $("#used_edate").datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $(".tooltip").tooltip({
                html: true
            });

            $( "#b_buyer_date" ).datetimepicker({
                format: 'YYYY-MM-DD',
                widgetPositioning: {
                    horizontal: 'right'
                }
            });

            $('#b_buyer_id').keypress(function(event) {
                if (event.keyCode == 13 || event.which == 13) {
                    $('#find_btn').focus();
                    event.preventDefault();
                }
            });

            $('#b_buyer_name').keypress(function(event) {
                if (event.keyCode == 13 || event.which == 13) {
                    $('#find_btn').focus();
                    event.preventDefault();
                }
            });

            $('#b_buyer_email').keypress(function(event) {
                if (event.keyCode == 13 || event.which == 13) {
                    $('#find_btn').focus();
                    event.preventDefault();
                }
            });

            $('#b_buyer_price').keypress(function(event) {
                if (event.keyCode == 13 || event.which == 13) {
                    $('#find_btn').focus();
                    event.preventDefault();
                }
            });

            $('#b_buyer_memo').keypress(function(event) {
                if (event.keyCode == 13 || event.which == 13) {
                    $('#find_btn').focus();
                    event.preventDefault();
                }
            });

            $('#b_buyer_date').keypress(function(event) {
                if (event.keyCode == 13 || event.which == 13) {
                    $('#find_btn').focus();
                    event.preventDefault();
                }
            });

            $('#b_supplier_memo').keypress(function(event) {
                if (event.keyCode == 13 || event.which == 13) {
                    $('#find_btn').focus();
                    event.preventDefault();
                }
            });

            $('#b_comments').keypress(function(event) {
                if (event.keyCode == 13 || event.which == 13) {
                    $('#find_btn').focus();
                    event.preventDefault();
                }
            });

            $('#b_amount').keypress(function(event) {
                if (event.keyCode == 13 || event.which == 13) {
                    $('#find_btn').focus();
                    event.preventDefault();
                }
            });

            $('#b_esn_charge').keypress(function(event) {
                if (event.keyCode == 13 || event.which == 13) {
                    $('#find_btn').focus();
                    event.preventDefault();
                }
            });

            $('#b_rtr_month').keypress(function(event) {
                if (event.keyCode == 13 || event.which == 13) {
                    $('#find_btn').focus();
                    event.preventDefault();
                }
            });

            $('#b_rebate_month').keypress(function(event) {
                if (event.keyCode == 13 || event.which == 13) {
                    $('#find_btn').focus();
                    event.preventDefault();
                }
            });

            $('#b_esn_rebate').keypress(function(event) {
                if (event.keyCode == 13 || event.which == 13) {
                    $('#find_btn').focus();
                    event.preventDefault();
                }
            });

            $('#b_rebate_override_r').keypress(function(event) {
                if (event.keyCode == 13 || event.which == 13) {
                    $('#find_btn').focus();
                    event.preventDefault();
                }
            });

            $('#b_rebate_override_d').keypress(function(event) {
                if (event.keyCode == 13 || event.which == 13) {
                    $('#find_btn').focus();
                    event.preventDefault();
                }
            });

            $('#b_rebate_override_m').keypress(function(event) {
                if (event.keyCode == 13 || event.which == 13) {
                    $('#find_btn').focus();
                    event.preventDefault();
                }
            });

        };

        function show_batch_lookup() {
            $('#n_batch_esns').val('');
            $('#div_batch_lookup').modal();
        }

        function show_detail() {
            $('#div_detail').modal();
        }

        function save_detail() {

            var file = $('#esn_csv_file').val();
            if (file == '') {
                myApp.showError('Please select file to upload');
                return;
            }

            myApp.showLoading();
            $('#frm_upload').submit();
        }

        function close_modal() {
            $('#div_detail').modal('hide');
            myApp.showSuccess('Your request has been processed successfully!', function() {
                $('#btn_search').click();
            });
        }

        function excel_export() {
            $('#excel').val('Y');
            $('#frm_search').submit();
            myApp.hideLoading();
            $('#excel').val('');
        }

        function show_esn_assign() {
            $('#n_clear_assign').attr('checked', false);
            $('#n_c_store_id').attr('disabled', false);
            $('#n_product').val('');
            $('#n_c_store_id').val('');
            $('#n_esns').val('');

            $('#div_esn_assign').modal();
        }

        function save_esn_assign() {
            myApp.showLoading();
            $.ajax({
                url: '/admin/settings/esn/assign',
                data: {
                    _token: '{!! csrf_token() !!}',
                    product: $('#n_product').val(),
                    c_store_id: $('#n_c_store_id').val(),
                    clear: $('#n_clear_assign').is(':checked') ? 'Y' : 'N',
                    esns: $('#n_esns').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if($.trim(res.msg) === '') {
                        myApp.showSuccess('Your request has been successfully processed!', function() {
                            $('#c_store_id').val($('#n_c_store_id').val());
                            $('#frm_search').submit();
                        });
                    } else {
                        myApp.showError(res.msg);
                    }
                }
            });
        }

        function change_assign_status() {
            var clear = $('#n_clear_assign').is(':checked');
            if (clear) {
                $('#n_c_store_id').attr('disabled', true);
            } else {
                $('#n_c_store_id').attr('disabled', false);
            }
        }

        function count_esns() {
            var esns = $.trim($('#n_esns').val()).split("\n");
            $('#n_esns_qty').text(esns.length);
        }

        function count_batch_esns() {
            var esns = $.trim($('#n_batch_esns').val()).split("\n");
            $('#n_batch_esns_qty').text(esns.length);
        }

        function batch_lookup() {
            var batch_esns = $('#n_batch_esns').val();
            batch_esns = $.trim(batch_esns);

            if (batch_esns === '') {
                myApp.showError('Please enter ESNs to lookup');
                return;
            }

            $('#div_batch_lookup').modal('hide');
            $('#frm_batch_lookup').submit();
        }

        function show_bulk_update_esn() {

            $('#div_bulk_esn_update').modal();
        }

        function get_buyer_info_find(){
            var buyer_id = $('#b_buyer_id').val();
            if(buyer_id.length < 1) {
                alert("Please Insert Buyer ID");
            }
            $('#find_btn').focus();
            event.preventDefault();
            return;
        }

        function get_buyer_info() {
            var buyer_id = $('#b_buyer_id').val();
            if(buyer_id.length < 1) {
                alert("Please Insert Buyer ID");
                $('#find_btn').focus();
                event.preventDefault();
                return;
            }
            myApp.showLoading();
            $.ajax({
                url: '/admin/settings/sim/get_buyer_info',
                data: {
                    _token: '{!! csrf_token() !!}',
                    buyer_id: buyer_id
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function (res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {

                        $('#b_buyer_name').val(res.data.name);
                        $('#b_buyer_email').val(res.data.email);

                    } else {
                        myApp.showError(res.msg);
                    }
                }
            });
        }

        function b_count_esns() {
            var esns = $.trim($('#b_esns').val()).split("\n");
            $('#b_esns_qty').text(esns.length);
        }

        function save_bulk_esn_update() {

            var reset = ($("#b_reset").is(":checked")) ? 'Y' : 'N';

            if( $('#b_esn_sub_carrier').val().length < 1){
                alert('Please select Sub Carrier');
                return;
            }

            if(reset == 'N') {

                if ($('#b_buyer_id').val().length < 1) {
                    alert('Please Insert Buyer Info');
                    return;
                }

                if ($('#b_type').val().length < 1) {
                    alert("Please select TYPE");
                    return;
                }

                if (($('#b_type').val() == 'P') && (
                    $('#b_rtr_month').val().length < 1 ||
                    $('#b_amount').val().length < 1 ||
                    $('#b_rebate_month').val().length < 1)) {
                    alert('Please insert RTR month / Amount / Rebate month with Type = Preload ');
                    return;
                }
            }

            myApp.showLoading();
            $.ajax({
                url: '/admin/settings/esn/bulk_update',
                data: {
                    _token: '{!! csrf_token() !!}',
                    amount: $('#b_amount').val(),
                    type: $('#b_type').val(),
                    charge_amount_r: $('#d_charge_amount_r').val(),
                    charge_amount_d: $('#d_charge_amount_d').val(),
                    charge_amount_m: $('#d_charge_amount_m').val(),
                    rtr_month: $('#b_rtr_month').val(),
                    rebate_month: $('#b_rebate_month').val(),
                    rebate_override_r: $('#b_rebate_override_r').val(),
                    rebate_override_d: $('#b_rebate_override_d').val(),
                    rebate_override_m: $('#b_rebate_override_m').val(),
                    buyer_id: $('#b_buyer_id').val(),
                    buyer_name: $('#b_buyer_name').val(),
                    buyer_email: $('#b_buyer_email').val(),
                    buyer_price: $('#b_buyer_price').val(),
                    buyer_date: $('#b_buyer_date').val(),
                    buyer_memo: $('#b_buyer_memo').val(),
                    supplier_memo: $('#b_supplier_memo').val(),
                    comments: $('#b_comments').val(),
                    esn_charge: $('#b_esn_charge').val(),
                    esn_rebate: $('#b_esn_rebate').val(),
                    esns: $('#b_esns').val(),
                    esn_sub_carrier: $('#b_esn_sub_carrier').val(),
                    email_message: $('#b_email_message').val(),
                    reset: reset
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function (res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        myApp.showSuccess('Your request has been successfully processed!', function () {
                            $('#c_store_id').val($('#n_c_store_id').val());
                            // $('#frm_search').submit();
                        });
                    } else {
                        myApp.showError(res.msg);
                    }
                }
            });
        }

        function refresh_all() {
            window.location.href = '/admin/settings/esn';
        }

        function reset_btn() {

            if($("#b_reset").is(":checked")){
                $('#b_buyer_name').val('Back to Inventory');
            }else{
                $('#b_buyer_name').val('');
            }
        }

    </script>


    <h4>ESN Managements</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post" action="/admin/settings/esn">
            {{ csrf_field() }}
            <input type="hidden" name="excel" id="excel" value=""/>
            <input type="hidden" id="id" name="id"/>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">ESNs</label>
                        <div class="col-md-8">
{{--                            <input type="text" class="form-control" name="esn" value="{{ $esn }}"/>--}}
                            <textarea class="form-control" name="esns" rows="2">{{ $esns }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">SIMs</label>
                        <div class="col-md-8">
{{--                            <input type="text" class="form-control" name="sim" value="{{ $sim }}"/>--}}
                            <textarea class="form-control" name="sims" rows="2">{{ $sims }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Phones</label>
                        <div class="col-md-8">
{{--                            <input type="text" class="form-control" name="phone" value="{{ $phone }}"/>--}}
                            <textarea class="form-control" name="phones" rows="2">{{ $phones }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">MSID</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="msid" value="{{ $msid }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Device.Type</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="device_type" value="{{ $device_type }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Model</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="model" value="{{ $model }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Vendor</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="vendor" value="{{ $vendor }}"/>
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
                                <option value="S" {{ $status == 'S' ? 'selected' : '' }}>Suspended</option>
                                <option value="U" {{ $status == 'U' ? 'selected' : '' }}>Used</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Type</label>
                        <div class="col-md-8">
                            <select class="form-control" name="type">
                                <option value="">All</option>
                                <option value="B" {{ $type == 'B' ? 'selected' : '' }}>Bundled</option>
                                <option value="P" {{ $type == 'P' ? 'selected' : '' }}>Wallet</option>
                                <option value="R" {{ $type == 'R' ? 'selected' : '' }}>Regular</option>
                                <option value="C" {{ $type == 'C' ? 'selected' : '' }}>Consignment</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">RTR.Month</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="rtr_month" value="{{ $rtr_month }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Product</label>
                        <div class="col-md-8">
                            <select class="form-control" name="product"
                                    data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                <option value="" {{ old('product', $product) == '' ? 'selected' : '' }}>All
                                </option>
                                @foreach ($products as $p)
                                    <option value="{{ $p->id }}" {{ old('product', $product) == $p->id ? 'selected' : '' }}>{{ $p->carrier . ', ' . $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

{{--                <div class="col-md-4">--}}
{{--                    <div class="form-group">--}}
{{--                        <label class="col-md-4 control-label">Sub.Carrier</label>--}}
{{--                        <div class="col-md-8">--}}
{{--                            <select class="form-control" name="sub_carrier" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>--}}
{{--                                <option value="" {{ old('sub_carrier', $sub_carrier) == '' ? 'selected' : '' }}>All</option>--}}
{{--                                @foreach ($sub_carriers as $o)--}}
{{--                                    <option value="{{ $o->sub_carrier }}" {{ old('sub_carrier', $sub_carrier) == $o->sub_carrier ? 'selected' : '' }}>{{ $o->sub_carrier }}</option>--}}
{{--                                @endforeach--}}
{{--                            </select>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Carrier</label>
                        <div class="col-md-8">
                            <select class="form-control" name="carrier" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                <option value="" {{ old('carrier', $carrier) == '' ? 'selected' : '' }}>All</option>
                                @foreach ($carriers as $o)
                                    <option value="{{ $o->carrier }}" {{ old('carrier', $carrier) == $o->carrier ? 'selected' : '' }}>{{ $o->carrier }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>


                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Upload.Date</label>
                        <div class="col-md-8">
                            <input type="text" style="width:100px; float:left;" class="form-control" id="sdate"
                                   name="sdate" value="{{ old('sdate', $sdate) }}"/>
                            <span class="control-label" style="float:left;">&nbsp;~&nbsp;</span>
                            <input type="text" style="width:100px; float:left;" class="form-control" id="edate"
                                   name="edate" value="{{ old('edate', $edate) }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Used.Date</label>
                        <div class="col-md-8">
                            <input type="text" style="width:100px; float:left;" class="form-control" id="used_sdate"
                                   name="used_sdate" value="{{ old('used_sdate', $used_sdate) }}"/>
                            <span class="control-label" style="float:left;">&nbsp;~&nbsp;</span>
                            <input type="text" style="width:100px; float:left;" class="form-control" id="used_edate"
                                   name="used_edate" value="{{ old('used_edate', $used_edate) }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Supplier</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="supplier" value="{{ $supplier }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Supplier.Date</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="supplier_date" value="{{ $supplier_date }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Supplier.Memo</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="supplier_memo" value="{{ $supplier_memo }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Buyer.Name</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="buyer_name" value="{{ $buyer_name }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Buyer.Date</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="buyer_date" value="{{ $buyer_date }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Buyer.Memo</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="buyer_memo" value="{{ $buyer_memo }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Comments</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="comments" value="{{ $comments }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">eCommerce.ID</label>
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="c_store_id" name="c_store_id" value="{{ $c_store_id }}"/>
                        </div>
                        <div class="col-md-4">
                            <label>
                                <input type="checkbox" name="show_all_c_store" value="Y" {{ $show_all_c_store == 'Y' ? 'checked' : '' }}/> Show me All eCommerce?
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Owner.ID</label>
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="owner_id" name="owner_id" value="{{ $owner_id }}"/>
                        </div>
                        <div class="col-md-4">
                            <label>
                                <input type="checkbox" name="show_all_owner" value="Y"  {{ $show_all_owner == 'Y' ? 'checked' : '' }}/> Show me All Owner?
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Subsidy</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="subsidy" value="{{ $subsidy }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Supplier.Make</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="supplier_make" value="{{ $supplier_make }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Supplier.Model</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="supplier_model" value="{{ $supplier_model }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Shipped.Date</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="shipped_date" value="{{ $shipped_date }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Supplier.Cost</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="supplier_cost" value="{{ $supplier_cost }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Buyer.Price</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="buyer_price" value="{{ $buyer_price }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label"></label>
                        <div class="col-md-4">
                            <label>
                                <input type="checkbox" name="esn_charge" value="Y"  {{ $esn_charge == 'Y' ? 'checked' : '' }}/> ESN charge
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label>
                                <input type="checkbox" name="esn_rebate" value="Y"  {{ $esn_rebate == 'Y' ? 'checked' : '' }}/> ESN rebate
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Is.BYOD</label>
                        <div class="col-md-8">
                            <input type="checkbox" name="is_byod" value="Y" {{ $is_byod == 'Y' ? 'checked' : '' }}/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-md-offset-8">
                    <div class="form-group">
                        <div class="col-md-12 text-right">
                            <button type="button" class="btn btn-info btn-sm" onclick="refresh_all()">Refresh All</button>
                            <button type="submit" class="btn btn-primary btn-sm" id="btn_search">Search</button>
                            <button type="button" class="btn btn-blue btn-sm" onclick="show_batch_lookup()">Batch Lookup</button>
                            @if(Auth::check() && in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']))
                                <button type="button" class="btn btn-default btn-sm" onclick="show_detail()">Upload New ESN</button>
                                <button type="button" class="btn btn-info btn-sm" onclick="excel_export()">Download</button>
                                <button type="button" class="btn btn-danger btn-sm" onclick="show_esn_assign()">Assign eCommerce ESN</button>
                                <button type="button" class="btn btn-danger btn-sm" onclick="show_bulk_update_esn()">
                                    Bulk Update ESN
                                </button>

                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>


    <table class="table table-bordered table-hover table-condensed filter">
        <thead>
        <tr>
            <th rowspan="3">ESN</th>
            <th rowspan="3">SIM</th>
            <th rowspan="3">Phone</th>
            <th rowspan="3">MSL</th>
            <th rowspan="3">MSID</th>
            <th rowspan="3">MEID</th>
            <th rowspan="3">Product</th>
            <th rowspan="3">Sub.Carrier</th>
            <th rowspan="3">ESN.Charge</th>
            <th rowspan="3">ESN.Rebate</th>
            <th rowspan="3">Amount</th>
            <th colspan="5">Consignment</th>
            <th rowspan="3">Type</th>
            <th rowspan="3">RTR.Month</th>
            <th rowspan="3">Spiff.Month</th>
            <th rowspan="3">Rebate.Month</th>
            <th colspan="3" rowspan="2">Spiff.Override</th>
            <th colspan="3" rowspan="2">Rebate.Override</th>
            <th rowspan="3">Status</th>
            <th rowspan="3">Is.BYOD</th>
            <th rowspan="3">Device.Type</th>
            <th rowspan="3">Model</th>
            <th rowspan="3">Vendor</th>
            <th colspan="7" rowspan="2">Supplier</th>
            <th colspan="4" rowspan="2">Buyer</th>
            <th rowspan="3">Comments</th>
            <th rowspan="3">eCommerce.ID</th>
            <th rowspan="3">Used.Tx.ID</th>
            <th rowspan="3">Used.Date</th>
            <th rowspan="3">Upload.Date</th>
        </tr>
        <tr>
            <th colspan="3">Charge.Amount</th>
            <th rowspan="2">Owner.ID</th>
            <th rowspan="2">Shipped.Date</th>
        </tr>
        <tr>
            <th>R</th>
            <th>D</th>
            <th>M</th>

            <th>R</th>
            <th>D</th>
            <th>M</th>
            <th>R</th>
            <th>D</th>
            <th>M</th>
            <th>Name</th>
            <th>Subsidy</th>
            <th>Make</th>
            <th>Model</th>
            <th>Cost</th>
            <th>Date</th>
            <th>Memo</th>
            <th>Name</th>
            <th>Price</th>
            <th>Date</th>
            <th>Memo</th>

        </tr>
        </thead>
        <tbody>
        @if (isset($data) && count($data) > 0)
            @foreach ($data as $o)
                <tr>
                    <td>{{ $o->esn }}</td>
                    <td>{{ $o->sim }}</td>
                    <td>{{ $o->phone }}</td>
                    <td>{{ $o->msl }}</td>
                    <td>{{ $o->msid }}</td>
                    <td>{{ $o->meid }}</td>
                    <td>{{ $o->product_name }}</td>
                    <td>{{ $o->sub_carrier }}</td>
                    <td>{{ $o->esn_charge }}</td>
                    <td>{{ $o->esn_rebate }}</td>
                    <td style="{{ $o->type == 'R' ? 'background-color:#efefef' : '' }}">{{ $o->amount }}</td>
                    <td style="{{ $o->type != 'C' ? 'background-color:#efefef' : '' }}">{{ $o->charge_amount_r }}</td>
                    <td style="{{ $o->type != 'C' ? 'background-color:#efefef' : '' }}">{{ $o->charge_amount_d }}</td>
                    <td style="{{ $o->type != 'C' ? 'background-color:#efefef' : '' }}">{{ $o->charge_amount_m }}</td>
                    <td style="{{ $o->type != 'C' ? 'background-color:#efefef' : '' }}">{{ $o->owner_id }}</td>
                    <td style="{{ $o->type != 'C' ? 'background-color:#efefef' : '' }}">{{ $o->shipped_date }}</td>
                    <td>{{ $o->type_name }}</td>
                    <td>{{ $o->rtr_month }}</td>
                    <td>{{ $o->spiff_month }}</td>
                    <td>{{ $o->rebate_month }}</td>
                    <td style="{{ $o->spiff_month == 0 ? 'background-color:#efefef' : '' }}">{{ $o->spiff_override_r }}</td>
                    <td style="{{ $o->spiff_month == 0 ? 'background-color:#efefef' : '' }}">{{ $o->spiff_override_d }}</td>
                    <td style="{{ $o->spiff_month == 0 ? 'background-color:#efefef' : '' }}">{{ $o->spiff_override_m }}</td>
                    <td style="{{ $o->rebate_month == 0 ? 'background-color:#efefef' : '' }}">{{ $o->rebate_override_r }}</td>
                    <td style="{{ $o->rebate_month == 0 ? 'background-color:#efefef' : '' }}">{{ $o->rebate_override_d }}</td>
                    <td style="{{ $o->rebate_month == 0 ? 'background-color:#efefef' : '' }}">{{ $o->rebate_override_m }}</td>
                    <td>{{ $o->status_name }}</td>
                    <td>{{ $o->is_byod }}</td>
                    <td>{{ $o->device_type }}</td>
                    <td>{{ $o->model }}</td>
                    <td>{{ $o->vendor }}</td>
                    <td>{{ $o->supplier }}</td>
                    <td>{{ $o->supplier_subsidy }}</td>
                    <td>{{ $o->supplier_make }}</td>
                    <td>{{ $o->supplier_model }}</td>
                    <td>{{ $o->supplier_cost }}</td>
                    <td>{{ $o->supplier_date }}</td>
                    <td>{{ $o->supplier_memo }}</td>
                    <td>{{ $o->buyer_name }}</td>
                    <td>{{ $o->buyer_price }}</td>
                    <td>{{ $o->buyer_date }}</td>
                    <td>{{ $o->buyer_memo }}</td>
                    <td>{{ $o->comments }}</td>
                    <td>{{ $o->c_store_id }}</td>
                    <td>{{ $o->used_trans_id }}</td>
                    <td>{{ $o->used_date }}</td>
                    <td>{{ $o->upload_date }}</td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="100" class="text-center">No Record Found</td>
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
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Upload ESN</h4>
                </div>
                <div class="modal-body">

                    <form id="frm_upload" action="/admin/settings/esn/upload" class="form-horizontal filter" method="post" style="padding:15px;" target="ifm_upload" enctype="multipart/form-data">
                        {{ csrf_field() }}

                        <div class="form-group">
                            <label class="col-md-4 control-label">Product</label>
                            <div class="col-md-8">
                                <select class="form-control" name="product"
                                        data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                    <option value="" {{ old('product', $product) == '' ? 'selected' : '' }}>All
                                    </option>
                                    @foreach ($products as $p)
                                        <option value="{{ $p->id }}" {{ old('product', $product) == $p->id ? 'selected' : '' }}>{{ $p->carrier . ', ' . $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-4 control-label required">Select CSV File to Upload</label>
                            <div class="col-sm-8">
                                <input type="file" class="form-control" name="esn_csv_file" id="esn_csv_file"/>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="margin-right:15px;">
                    <a class="btn btn-warning" href="/upload_template/esn_upload_template.xlsx" target="_blank">Download Template</a>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="save_detail()">Upload</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="div_batch_lookup" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Please enter ESNs to lookup</h4>
                </div>
                <div class="modal-body">
                    <form id="frm_batch_lookup" action="/admin/settings/esn/batch-lookup" class="form-horizontal filter"
                          method="post" style="padding:15px;">
                        {{ csrf_field() }}

                        <div class="form-group">
                            <div class="col-sm-6">
                                <input type="radio" name="type" value="E"> ESN
                            </div>
                            <div class="col-sm-6">
                                <input type="radio" name="type" value="M"> MEID
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-12">
                                <label>
                                    Excel file will be downloaded after submit.
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-12">
                                <textarea id="n_batch_esns" name="batch_esns" rows="10" style="width:100%; line-height: 150%;"
                                          onchange="count_batch_esns()"></textarea><br/>
                                Total <span id="n_batch_esns_qty">0</span> ESN(s).
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

    <div class="modal" id="div_esn_assign" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Assign eCommerce ESN</h4>
                </div>
                <div class="modal-body">

                    <form id="frm_upload" action="/admin/settings/esn/assign" class="form-horizontal filter" method="post" style="padding:15px;">
                        {{ csrf_field() }}

                        <div class="form-group">
                            <label class="col-md-4 control-label">Product</label>
                            <div class="col-md-8">
                                <select class="form-control" id="n_product"
                                        data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                    <option value="">Select Product
                                    </option>
                                    @foreach ($products as $p)
                                        <option value="{{ $p->id }}">{{ $p->carrier . ', ' . $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-4 control-label required">Clear.Assign?: </label>
                            <div class="col-sm-8">
                                <label>
                                    <input type="checkbox" id="n_clear_assign" value="Y" onclick="change_assign_status()"/> Yes, I need to clear assignment.
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label required">eCommerce.ID: </label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="n_c_store_id"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label required">ESNs to Assign: </label>
                            <div class="col-sm-8">
                                <textarea id="n_esns" rows="10" style="width:100%; line-height: 150%;" onchange="count_esns()"></textarea><br/>
                                Total <span id="n_esns_qty">0</span> ESN(s).
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="margin-right:15px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="save_esn_assign()">Assign</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="div_bulk_esn_update" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Bulk Update for ESNs</h4>
                </div>
                <div class="modal-body">

                    <form id="frm_upload" action="/admin/settings/esn/bulk_update" class="form-horizontal filter"
                          method="post" style="padding:15px;">
                        {{ csrf_field() }}

                        <div class="form-group">
                            <label class="col-sm-1 control-label required">Buyer_ID: </label>
                            <div class="col-sm-2">
                                <input type="text" class="form-control" id="b_buyer_id" onchange="get_buyer_info()"/>
                            </div>
                            <div class="col-sm-1">
                                <button class="btn btn-primary btn-sm" id="find_btn" onclick="get_buyer_info_find()">Find</button>
                            </div>
                            <label class="col-sm-1 control-label required">Buyer_name: </label>
                            <div class="col-sm-3">
                                <input type="text" class="form-control" id="b_buyer_name"/>
                            </div>
                            <label class="col-sm-1 control-label required">Buyer_email: </label>
                            <div class="col-sm-3">
                                <input type="text" class="form-control" id="b_buyer_email">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-1 control-label ">Buyer_price: </label>
                            <div class="col-sm-2">
                                <input type="text" class="form-control" id="b_buyer_price"/>
                            </div>
                            <label class="col-sm-1 control-label ">Buyer_memo: </label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" id="b_buyer_memo"/>
                            </div>
                            <label class="col-sm-2 control-label ">Buyer_date: </label>
                            <div class="col-sm-2">
                                <input type="text" class="form-control" id="b_buyer_date"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label ">Supplier_memo: </label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" id="b_supplier_memo"/>
                            </div>
                            <label class="col-sm-2 control-label ">Comments: </label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" id="b_comments"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label required">ESNs to Update: </label>
                            <div class="col-sm-4">
                                <textarea id="b_esns" rows="10" style="width:100%; line-height: 150%;"
                                          onchange="b_count_esns()"></textarea><br/>
                                Total <span id="b_esns_qty">0</span> ENS(s).
                            </div>

                            <label class="col-sm-2 control-label">Email Message: </label>
                            <div class="col-sm-4">
                                <textarea id="b_email_message" rows="10" style="width:100%; line-height: 150%;"></textarea><br/>
                            </div>

                            <label class="col-sm-2 control-label required">ESN Sub Carrier: </label>
                            <div class="col-sm-4">
                                <select class="form-control" id="b_esn_sub_carrier">
                                    <option value="">Select</option>
                                    @foreach ($sub_carriers as $g)
                                        <option value="{{ $g->sub_carrier }}">{{ $g->sub_carrier }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label ">Amount: </label>
                            <div class="col-sm-2">
                                <input type="text" class="form-control" id="b_amount" placeholder="10|20|30"/>
                            </div>
                            <label class="col-sm-2 control-label ">Type: </label>
                            <div class="col-sm-2">
                                <select class="form-control" id="b_type">
                                    <option value="">Select</option>
                                    <option value="R">Regular</option>
                                    <option value="P">Preload</option>
                                </select>
                            </div>
                            <label class="col-sm-2 control-label ">ESN_charge: </label>
                            <div class="col-sm-2">
                                <input type="text" class="form-control" id="b_esn_charge"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label ">RTR_month: </label>
                            <div class="col-sm-2">
{{--                                <input type="text" class="form-control" id="b_rtr_month" placeholder="1|2|3 or 1 or 2 .."/>--}}
                                <select class="form-control" id="b_rtr_month">
                                    <option value="">Select</option>
                                    <option value="1|2|3">1|2|3</option>
                                    <option value="1|2">1|2</option>
                                    <option value="0">0</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="2|3">2|3</option>
                                </select>
                            </div>
                            <label class="col-sm-2 control-label ">Rebate_month: </label>
                            <div class="col-sm-2">
{{--                                <input type="text" class="form-control" id="b_rebate_month" placeholder="1|2|3 or 1 or 2 .."/>--}}
                                <select class="form-control" id="b_rebate_month">
                                    <option value="">Select</option>
                                    <option value="1|2|3">1|2|3</option>
                                    <option value="1|2">1|2</option>
                                    <option value="0">0</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="2|3">2|3</option>
                                </select>
                            </div>
                            <label class="col-sm-2 control-label ">ESN_rebate: </label>
                            <div class="col-sm-2">
                                <input type="text" class="form-control" id="b_esn_rebate"/>
                            </div>
                        </div>
                        {{--                        <div class="form-group">--}}
                        {{--                            <label class="col-sm-2 control-label ">Charge_amount_r: </label>--}}
                        {{--                            <div class="col-sm-2">--}}
                        {{--                                <input type="text" class="form-control" id="b_charge_amount_r"/>--}}
                        {{--                            </div>--}}
                        {{--                            <label class="col-sm-2 control-label ">Charge_amount_d: </label>--}}
                        {{--                            <div class="col-sm-2">--}}
                        {{--                                <input type="text" class="form-control" id="b_charge_amount_d"/>--}}
                        {{--                            </div>--}}
                        {{--                            <label class="col-sm-2 control-label ">Charge_amount_m: </label>--}}
                        {{--                            <div class="col-sm-2">--}}
                        {{--                                <input type="text" class="form-control" id="b_charge_amount_m"/>--}}
                        {{--                            </div>--}}
                        {{--                        </div>--}}
                        <div class="form-group">
                            <label class="col-sm-2 control-label ">Rebate_override_r: </label>
                            <div class="col-sm-2">
                                <input type="text" class="form-control" id="b_rebate_override_r"/>
                            </div>
                            <label class="col-sm-2 control-label ">Rebate_override_d: </label>
                            <div class="col-sm-2">
                                <input type="text" class="form-control" id="b_rebate_override_d"/>
                            </div>
                            <label class="col-sm-2 control-label ">Rebate_override_m: </label>
                            <div class="col-sm-2">
                                <input type="text" class="form-control" id="b_rebate_override_m"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-2">
                                <input type="checkbox" id="b_reset" value="Y" style="float: right; margin-top: 10px;" onclick="reset_btn()"/>
                            </div>
                            <label class="col-sm-3">Reset with all above values </label>
                        </div>

                    </form>
                </div>
                <div class="modal-footer" style="margin-right:15px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="save_bulk_esn_update()">Update</button>
                </div>
            </div>
        </div>
    </div>

    <div style="display:none">
        <iframe name="ifm_upload"></iframe>
    </div>
@stop
