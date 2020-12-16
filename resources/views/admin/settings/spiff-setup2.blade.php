@extends('admin.layout.default')

@section('content')
    <style type="text/css">
        .modal-dialog{
            position: relative;
            #display: table; /* This is important */
            overflow-y: auto;
            overflow-x: auto;
            width: auto;
            min-width: 600px;
        }
    </style>

    <script type="text/javascript">
        var onload_events = window.onload;
        window.onload = function() {

            if (onload_events) {
                onload_events();
            }

            $( "#n_special_period_from" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $( "#n_special_period_to" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $( "#n_special_referal_period_from" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $( "#n_special_referal_period_to" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $(".tooltip").tooltip({
                html: true
            })
        }

        function search() {
            $('#frm_spiff').submit();
        }

        function add_spiff_template() {

            myApp.showLoading();
            $.ajax({
                url: '/admin/settings/spiff-setup2/add/template',
                data: {
                    _token: '{!! csrf_token() !!}',
                    template_name: $('#new_template_name').val(),
                    account_type: $('#new_template_account_type').val(),
                    copy_from: $('#new_template_copy_from').val()
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

        function add_new_spiff_call_amount() {
            let product_id = document.getElementById("add_new_spiff_product").value;
            $.ajax({
                url: '/admin/settings/spiff-setup2/call-amount',
                data: {
                    _token: '{!! csrf_token() !!}',
                    product_id: product_id
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        $('#add_new_spiff_amount').empty();
                        $.each(res.denoms, function(i, k) {
                            var html = '<option value="' + k.id + '|' + k.denom + '">' + k.denom + ' - ' + k.name + '</option>';
                            $('#add_new_spiff_amount').append(html);
                        })
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

        function add_new_spiff() {

            let template    = $('#add_new_spiff_template').val();
            let product     = $('#add_new_spiff_product').val();
            let amount      = $('#add_new_spiff_amount').val();

            let an_sp_1 = $('#an_sp_1').val();
            let an_sp_2 = $('#an_sp_2').val();
            let an_sp_3 = $('#an_sp_3').val();

            let an_rs = $('#an_rs').val();
            let an_ar = $('#an_ar').val();

            let an_rb_1 = $('#an_rb_1').val();
            let an_rb_2 = $('#an_rb_2').val();
            let an_rb_3 = $('#an_rb_3').val();

            let an_by_1 = $('#an_by_1').val();
            let an_by_2 = $('#an_by_2').val();
            let an_by_3 = $('#an_by_3').val();

            if(!amount){
                alert("Please Select Product / Amount($) [Add New Spiff] ");
                return;
            }
            if(!an_sp_1 || !an_sp_2 || !an_sp_3){
                alert("Please Insert Spiff.1st | Spiff.2nd | Spiff.3rd [Add New Spiff] ");
                return;
            }
            if(!an_rs || !an_ar){
                alert("Please Insert Residual, AR [Add New Spiff] ");
                return;
            }
            if(!an_rb_1 || !an_rb_2 || !an_rb_3){
                alert("Please Insert All Regular.Rebate.1st | Regular.Rebate.2nd | Regular.Rebate.3rd [Add New Spiff] ");
                return;
            }
            if(!an_by_1 || !an_by_2 || !an_by_3){
                alert("Please Insert All BYOD.Rebate.1st | BYOD.Rebate.2nd | BYOD.Rebate.3rd [Add New Spiff] ");
                return;
            }

            $.ajax({
                url: '/admin/settings/spiff-setup2/add-new-spiff',
                data: {
                    _token: '{!! csrf_token() !!}',
                    template: template,
                    product: product,
                    amount: amount,
                    an_sp_1 : an_sp_1,
                    an_sp_2 : an_sp_2,
                    an_sp_3 : an_sp_3,
                    an_rs : an_rs,
                    an_ar : an_ar,
                    an_rb_1 : an_rb_1,
                    an_rb_2 : an_rb_2,
                    an_rb_3 : an_rb_3,
                    an_by_1 : an_by_1,
                    an_by_2 : an_by_2,
                    an_by_3 : an_by_3
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        myApp.showSuccess('Your request has been processed successfully!', function() {
                            $('#frm_spiff').submit();
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

        function reset_exist_only_call_amount() {
            let product_id = document.getElementById("reset_exist_only_product").value;
            $.ajax({
                url: '/admin/settings/spiff-setup2/call-amount',
                data: {
                    _token: '{!! csrf_token() !!}',
                    product_id: product_id
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        $('#reset_exist_only_amount').empty();
                        $.each(res.denoms, function(i, k) {
                            var html = '<option value="' + k.id + '|' + k.denom + '">' + k.denom + ' - ' + k.name + '</option>';
                            $('#reset_exist_only_amount').append(html);
                        })
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

        function reset_exist_only() {

            let template    = $('#reset_exist_only_template').val();
            let product     = $('#reset_exist_only_product').val();
            let amount      = $('#reset_exist_only_amount').val();

            let reo_sp_1 = $('#reo_sp_1').val();
            let reo_sp_2 = $('#reo_sp_2').val();
            let reo_sp_3 = $('#reo_sp_3').val();

            let reo_rs = $('#reo_rs').val();
            let reo_ar = $('#reo_ar').val();

            let reo_rb_1 = $('#reo_rb_1').val();
            let reo_rb_2 = $('#reo_rb_2').val();
            let reo_rb_3 = $('#reo_rb_3').val();

            let reo_by_1 = $('#reo_by_1').val();
            let reo_by_2 = $('#reo_by_2').val();
            let reo_by_3 = $('#reo_by_3').val();

            if(!amount){
                alert("Please Select Product / Amount($) [Reset Exist Only] ");
                return;
            }
            if(!reo_sp_1 || !reo_sp_2 || !reo_sp_3){
                alert("Please Insert Spiff.1st | Spiff.2nd | Spiff.3rd [Reset Exist Only] ");
                return;
            }
            if(!reo_rs || !reo_ar){
                alert("Please Insert Residual, AR [Reset Exist Only] ");
                return;
            }
            if(!reo_rb_1 || !reo_rb_2 || !reo_rb_3){
                alert("Please Insert All Regular.Rebate.1st | Regular.Rebate.2nd | Regular.Rebate.3rd [Reset Exist Only] ");
                return;
            }
            if(!reo_by_1 || !reo_by_2 || !reo_by_3){
                alert("Please Insert All BYOD.Rebate.1st | BYOD.Rebate.2nd | BYOD.Rebate.3rd [Reset Exist Only] ");
                return;
            }

            $.ajax({
                url: '/admin/settings/spiff-setup2/reset-exist-only',
                data: {
                    _token: '{!! csrf_token() !!}',
                    template: template,
                    product: product,
                    amount: amount,
                    sp_1 : reo_sp_1,
                    sp_2 : reo_sp_2,
                    sp_3 : reo_sp_3,
                    rs : reo_rs,
                    ar : reo_ar,
                    rb_1 : reo_rb_1,
                    rb_2 : reo_rb_2,
                    rb_3 : reo_rb_3,
                    by_1 : reo_by_1,
                    by_2 : reo_by_2,
                    by_3 : reo_by_3
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        myApp.showSuccess('Your request has been processed successfully!', function() {
                            $('#frm_spiff').submit();
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

        function inde_exist_spiff_only_call_amount() {
            let product_id = document.getElementById("inde_exist_spiff_only_product").value;
            $.ajax({
                url: '/admin/settings/spiff-setup2/call-amount',
                data: {
                    _token: '{!! csrf_token() !!}',
                    product_id: product_id
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        $('#inde_exist_spiff_only_amount').empty();
                        $('#inde_exist_spiff_only_amount').append('<option value="all">All</option>');
                        $.each(res.denoms, function(i, k) {
                            var html = '<option value="' + k.denom + '">' + k.denom + ' - ' + k.name + '</option>';
                            $('#inde_exist_spiff_only_amount').append(html);
                        })
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

        function inde_exist_spiff_only() {

            let template    = $('#inde_exist_spiff_only_template').val();
            let product     = $('#inde_exist_spiff_only_product').val();
            let amount      = $('#inde_exist_spiff_only_amount').val();

            let inde_sp_1 = $('#inde_sp_1').val();
            let inde_sp_2 = $('#inde_sp_2').val();
            let inde_sp_3 = $('#inde_sp_3').val();

            let inde_rs = $('#inde_rs').val();
            let inde_ar = $('#inde_ar').val();

            let inde_rb_1 = $('#inde_rb_1').val();
            let inde_rb_2 = $('#inde_rb_2').val();
            let inde_rb_3 = $('#inde_rb_3').val();

            let inde_by_1 = $('#inde_by_1').val();
            let inde_by_2 = $('#inde_by_2').val();
            let inde_by_3 = $('#inde_by_3').val();

            if(!amount){
                alert("Please Select Product / Amount($) [IC/DC Exist Spiff Only] ");
                return;
            }
            if(!inde_sp_1 || !inde_sp_2 || !inde_sp_3){
                alert("Please Insert Spiff.1st | Spiff.2nd | Spiff.3rd [IC/DC Exist Spiff Only] ");
                return;
            }
            if(!inde_rs || !inde_ar){
                alert("Please Insert Residual, AR [IC/DC Exist Spiff Only] ");
                return;
            }
            if(!inde_rb_1 || !inde_rb_2 || !inde_rb_3){
                alert("Please Insert All Regular.Rebate.1st | Regular.Rebate.2nd | Regular.Rebate.3rd [IC/DC Exist Spiff Only] ");
                return;
            }
            if(!inde_by_1 || !inde_by_2 || !inde_by_3){
                alert("Please Insert All BYOD.Rebate.1st | BYOD.Rebate.2nd | BYOD.Rebate.3rd [IC/DC Exist Spiff Only] ");
                return;
            }

            $.ajax({
                url: '/admin/settings/spiff-setup2/inc-dec-exist-spiff-only',
                data: {
                    _token: '{!! csrf_token() !!}',
                    template: template,
                    product: product,
                    amount: amount,
                    sp_1 : inde_sp_1,
                    sp_2 : inde_sp_2,
                    sp_3 : inde_sp_3,
                    rs : inde_rs,
                    ar : inde_ar,
                    rb_1 : inde_rb_1,
                    rb_2 : inde_rb_2,
                    rb_3 : inde_rb_3,
                    by_1 : inde_by_1,
                    by_2 : inde_by_2,
                    by_3 : inde_by_3
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        myApp.showSuccess('Your request has been processed successfully!', function() {
                            $('#frm_spiff').submit();
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

        function excel_download() {
            $('#excel').val('Y');
            $('#frm_spiff').submit();
            myApp.hideLoading();
            $('#excel').val('');
        }

        function refresh_all() {
            window.location.href = '/admin/settings/spiff-setup2';
        }

    </script>


    <h4>Spiff & Rebate Setup</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_spiff" class="form-horizontal" action="/admin/settings/spiff-setup2" method="post" onsubmit="myApp.showLoading();">
            {{ csrf_field() }}
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Spiff.Template</label>
                        <div class="col-md-8">
                            <select id="template" name="template" class="form-control" onchange="$('#frm_spiff').submit()">
                                <option value="">All</option>
                                <option value="M" {{ old('template', $template) == "M" ? 'selected' : '' }}>Master All</option>
                                <option value="D" {{ old('template', $template) == "D" ? 'selected' : '' }}>Distributor All</option>
                                <option value="S" {{ old('template', $template) == "S" ? 'selected' : '' }}>Sub Agent All</option>
                                @foreach ($templates as $t)
                                    <option value="{{ $t->id }}" {{ old('template', $template) == $t->id ? 'selected' : '' }}>{{ $t->template }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Product</label>
                        <div class="col-md-8">
                            <select id="product" name="product" class="form-control" onchange="$('#frm_spiff').submit()">
                                <option value="">All</option>
                                @if (count($products) > 0)
                                    @foreach ($products as $o)
                                        <option value="{{ $o->id }}" {{ old('product', $product) == $o->id ? 'selected' : '' }}>{{ $o->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Denom/Denom ID</label>
                        <div class="col-md-8">
                            <select id="denom" name="denom" class="form-control" onchange="$('#frm_spiff').submit()">
                                <option value="">All</option>
                                @if (count($denoms) > 0)
                                    @foreach ($denoms as $o)
                                        <option value="{{ $o->denom }}" {{ old('denom', $denom) == $o->denom ? 'selected' : '' }}>{{ '$' . number_format($o->denom, 2) }} - {{ $o->name }} - {{ $o->id }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Search By Denom</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="search_denom" value="{{ $search_denom }}"/>
                        </div>
                    </div>
                </div>

            </div>

            <div class="row">
                <div class="col-md-12 text-right">
                    <div class="form-group">
                        <div class="col-md-12 text-right">
                            <input type="hidden" id="excel" name="excel" value="">
                            <button type="button" class="btn btn-info btn-sm" onclick="refresh_all()">Refresh All</button>
                            <button type="submit" class="btn btn-primary btn-sm" id="btn_search">Search</button>
                            <button type="button" class="btn btn-default btn-sm" onclick="excel_download()">Download</button>
                            <button type="button" class="btn btn-default btn-sm" onclick="add_template()">Add Template</button>
                            @if (!empty($template) && is_numeric($template))
                                <button type="button" class="btn btn-default btn-sm" onclick="load_template({{$template}})">Edit Template</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </form>
    </div>

    <p style="text-align: right;">RR : Regular Rebate,  BR : BYOD Rebate</p>
    <table class="table table-bordered table-hover table-condensed filter">
        <thead>
        <tr>
            <th>ID</th>
            <th>Template</th>
            <th>Product</th>
            <th>Amount($)</th>
            <th>Account.Type</th>
            <th>Spiff.1st</th>
            <th>Spiff.2nd</th>
            <th>Spiff.3rd</th>
            <th>Residual</th>
            <th>AR</th>
            <th>RR.1st</th>
            <th>RR.2nd</th>
            <th>RR.3rd</th>
            <th>BR.1st</th>
            <th>BR.2nd</th>
            <th>BR.3rd</th>
            <th>Last.Updated</th>
        </tr>
        </thead>
        <tbody>
        @if (isset($data) && count($data) > 0)
            @foreach ($data as $o)
                <tr>
                    <td>{{ $o->id }}</td>
                    <td>{{ $o->template_name }}</td>
                    <td>{{ $o->product_name }}</td>
                    <td>${{ number_format($o->denom, 2) }}</td>
                    <td>{!! Helper::get_hierarchy_img($o->account_type) !!} {{ $o->account_type_name }}</td>
                    <td>${{ number_format($o->spiff_1st, 2) }}</td>
                    <td>${{ number_format($o->spiff_2nd, 2) }}</td>
                    <td>${{ number_format($o->spiff_3rd, 2) }}</td>
                    <td>${{ number_format($o->residual, 2) }}</td>
                    <td>${{ number_format($o->ar, 2) }}</td>
                    <td>${{ number_format($o->regular_rebate_1st, 2) }}</td>
                    <td>${{ number_format($o->regular_rebate_2nd, 2) }}</td>
                    <td>${{ number_format($o->regular_rebate_3rd, 2) }}</td>
                    <td>${{ number_format($o->byod_rebate_1st, 2) }}</td>
                    <td>${{ number_format($o->byod_rebate_2nd, 2) }}</td>
                    <td>${{ number_format($o->byod_rebate_3rd, 2) }}</td>
                    <td>{{ $o->last_updated }}</td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="20" class="text-center">No Record Found</td>
            </tr>
        @endif
        </tbody>
        <tfoot>
            {{--  add new spiff --}}
            <tr>
                <td></td>
                <td><select id="add_new_spiff_template" name="add_new_spiff_template" class="form-control" >
                        <option value="M">Master All</option>
                        <option value="D">Distributor All</option>
                        <option value="S">SubAgent All</option>
                        @foreach ($templates as $t)
                            <option value="{{ $t->id }}" {{ old('template', $template) == $t->id ? 'selected' : '' }}>{{ $t->template }}</option>
                        @endforeach
                    </select>
                </td>
                <td><select id="add_new_spiff_product" name="add_new_spiff_product" class="form-control" onchange="add_new_spiff_call_amount()">
                            <option value="">Select</option>
                        @foreach ($products as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <select id="add_new_spiff_amount" name="add_new_spiff_amount">
                    </select>
                </td>
                <td></td>
                <td><input type="text" id="an_sp_1" size="5px" /></td>
                <td><input type="text" id="an_sp_2" size="5px"/></td>
                <td><input type="text" id="an_sp_3" size="5px"/></td>
                <td><input type="text" id="an_rs" size="5px"/></td>
                <td><input type="text" id="an_ar" size="5px"/></td>
                <td><input type="text" id="an_rb_1" size="5px"/></td>
                <td><input type="text" id="an_rb_2" size="5px"/></td>
                <td><input type="text" id="an_rb_3" size="5px"/></td>
                <td><input type="text" id="an_by_1" size="5px"/></td>
                <td><input type="text" id="an_by_2" size="5px"/></td>
                <td><input type="text" id="an_by_3" size="5px"/></td>
                <td><button onclick="add_new_spiff()">Reset For All Template</button></td>
            </tr>
            {{--  Reset Exist Only --}}
            <tr>
                <td></td>
                <td><select name="reset_exist_only_template" id="reset_exist_only_template" class="form-control" >
                        <option value="M">Master All</option>
                        <option value="D">Distributor All</option>
                        <option value="S">SubAgent All</option>
                        @foreach ($templates as $t)
                            <option value="{{ $t->id }}" {{ old('template', $template) == $t->id ? 'selected' : '' }}>{{ $t->template }}</option>
                        @endforeach
                    </select>
                </td>
                <td><select name="reset_exist_only_product" id="reset_exist_only_product" class="form-control" onchange="reset_exist_only_call_amount()">
                        <option value="">Select</option>
                        @foreach ($products as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <select id="reset_exist_only_amount">
                    </select>
                </td>
                <td></td>
                <td><input type="text" id="reo_sp_1" size="5px" /></td>
                <td><input type="text" id="reo_sp_2" size="5px"/></td>
                <td><input type="text" id="reo_sp_3" size="5px"/></td>
                <td><input type="text" id="reo_rs" size="5px"/></td>
                <td><input type="text" id="reo_ar" size="5px"/></td>
                <td><input type="text" id="reo_rb_1" size="5px"/></td>
                <td><input type="text" id="reo_rb_2" size="5px"/></td>
                <td><input type="text" id="reo_rb_3" size="5px"/></td>
                <td><input type="text" id="reo_by_1" size="5px"/></td>
                <td><input type="text" id="reo_by_2" size="5px"/></td>
                <td><input type="text" id="reo_by_3" size="5px"/></td>
                <td><button onclick="reset_exist_only()">Reset For Exist Template Only</button></td>
            </tr>
            <tr>
                <td></td>
                <td><select id="inde_exist_spiff_only_template" name="inde_exist_spiff_only_template" class="form-control" >
                        <option value="A">All</option>
                        <option value="M">Master All</option>
                        <option value="D">Distributor All</option>
                        <option value="S">SubAgent All</option>
                        @foreach ($templates as $t)
                            <option value="{{ $t->id }}" {{ old('template', $template) == $t->id ? 'selected' : '' }}>{{ $t->template }}</option>
                        @endforeach
                    </select>
                </td>
                <td><select id="inde_exist_spiff_only_product" name="inde_exist_spiff_only_product" class="form-control" onchange="inde_exist_spiff_only_call_amount()">
                        <option value="">Select</option>
                        @foreach ($products as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td><select id="inde_exist_spiff_only_amount">
                    </select>
                </td>
                <td></td>
                <td><input type="text" id="inde_sp_1" size="5px" /></td>
                <td><input type="text" id="inde_sp_2" size="5px"/></td>
                <td><input type="text" id="inde_sp_3" size="5px"/></td>
                <td><input type="text" id="inde_rs" size="5px"/></td>
                <td><input type="text" id="inde_ar" size="5px"/></td>
                <td><input type="text" id="inde_rb_1" size="5px"/></td>
                <td><input type="text" id="inde_rb_2" size="5px"/></td>
                <td><input type="text" id="inde_rb_3" size="5px"/></td>
                <td><input type="text" id="inde_by_1" size="5px"/></td>
                <td><input type="text" id="inde_by_2" size="5px"/></td>
                <td><input type="text" id="inde_by_3" size="5px"/></td>
                <td><button onclick="inde_exist_spiff_only()">Inc/Dec</button></td>
            </tr>
        </tfoot>
    </table>

    <div class="text-left">
        Total {{ $data->total() }} record(s).
    </div>
    <div class="text-right">
        {{ $data->appends(Request::except('page'))->links() }}
    </div>

    <script>

        function add_template() {
            $('#div_spiff_template').modal();
        }

        function load_template(template_id) {

            $('#e_template_id').val(template_id);

            $.ajax({
                url: '/admin/settings/spiff-setup2/load/template',
                data: {
                    _token: '{!! csrf_token() !!}',
                    template_id: template_id
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {

                        $('#e_template_id').val(res.temp_id);
                        $('#e_template_name').val(res.temp_name);

                    } else {
                        myApp.showError(res.msg);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });
            $('#div_spiff_template_edit').modal();
        }

        function edit_template(){

            let temp_id = $('#e_template_id').val();
            let temp_name = $('#e_template_name').val();

            if(temp_name.length < 1){
                alert('Please Insert Template name');
                return;
            }

            myApp.showLoading();
            $.ajax({
                url: '/admin/settings/spiff-setup2/edit/template',
                data: {
                    _token: '{!! csrf_token() !!}',
                    temp_id: temp_id,
                    temp_name : temp_name
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {

                        myApp.showSuccess('Your request has been processed successfully!', function() {
                            $('#frm_spiff').submit();
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

    </script>

    <div class="modal" id="div_spiff_template_edit" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">

                <form action="/admin/settings/spiff-setup2/add/template" method="post">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                    aria-hidden="true">x</span></button>
                        <h4 class="modal-title" id="n_title">Spiff Template</h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-horizontal">
                            {{ csrf_field() }}
                            <input type="hidden" id="e_template_id" value="">
                            <div class="form-group edit">
                                <label class="col-sm-4 control-label">Name</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" name="e_template_name" id="e_template_name"/>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer" style="margin-right:15px;">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="edit_template()">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal" id="div_spiff_template" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">

                <form action="/admin/settings/spiff-setup2/add/template" method="post">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                    aria-hidden="true">x</span></button>
                        <h4 class="modal-title" id="n_title">Spiff Template</h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-horizontal">
                            {{ csrf_field() }}

                            <div class="form-group edit">
                                <label class="col-sm-4 control-label">Name</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" name="new_template_name" id="new_template_name"/>
                                </div>
                            </div>

                            <div class="form-group edit">
                                <label class="col-sm-4 control-label">Account Type</label>
                                <div class="col-sm-8">
                                    <select name="new_template_account_type" id="new_template_account_type" class="form-control" >
                                        <option value="">Please Select</option>
                                        <option value="S">Sub-Agent</option>
                                        <option value="D">Distributor</option>
                                        <option value="M">Master</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group edit">
                                <label class="col-sm-4 control-label">Copy From (Template ID)</label>
                                <div class="col-sm-8">
                                    <select name="new_template_copy_from" id="new_template_copy_from" class="form-control" >
                                        <option value="">Please Select</option>
                                        @foreach ($templates as $t)
                                            <option value="{{ $t->id }}" >{{ $t->template }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="margin-right:15px;">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="add_spiff_template()">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@stop
