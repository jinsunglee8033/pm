@extends('admin.layout.default')

@section('content')

    <script type="text/javascript">
        var onload_events = window.onload;
        window.onload = function() {

            if (onload_events) {
                onload_events();
            }

            $( "#sdate_c" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });
            $( "#edate_c" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $("#sdate").datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $("#edate").datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $("#u_expired_date").datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $(".tooltip").tooltip({
                html: true
            });

            $( "#expired_date" ).datetimepicker({
                format: 'YYYY-MM-DD',
                widgetPositioning: {
                    horizontal: 'right'
                }
            });
        };

        function show_new() {
            $('#modal_add').modal();
        }

        function account_check() {

            var account_id = $('#account_id').val();

            myApp.showLoading();
            $.ajax({
                url: '/admin/settings/vr-product-price/account-check',
                data: {
                    account_id: account_id
                },
                type: 'get',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {

                        $('#account_info').text(res.data.acct_str);
                        // $('#info_box').show();

                    } else {

                        alert("Account is not available!");
                        $('#account_id').val('');
                        // $('#info_box').hide();

                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });
        }

        function add_price(prod_id) {

            $.ajax({
                url: '/admin/settings/vr-product-price/show-detail',
                data: {
                    _token: '{!! csrf_token() !!}',
                    prod_id: prod_id
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {

                        var o = res.data;
                        $('#u_id').val(o.id);
                        $('#product_id').val(prod_id);

                        $('#p_id').text(o.id);
                        $('#model_name').text(o.model);

                        $('#quick_note').val(o.quick_note);

                        $('#master_price').val(o.master_price);
                        $('#distributor_price').val(o.distributor_price);
                        $('#subagent_price').val(o.subagent_price);
                        $('#master_commission').val(o.master_commission);
                        $('#distributor_commission').val(o.distributor_commission);

                        $('#marketing').val(o.marketing);
                        $('#rebate_marketing').val(o.rebate_marketing);

                        $('#modal_detail').modal();

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

        function show_detail_price(prod_price_id) {

            $.ajax({
                url: '/admin/settings/vr-product-price/show-detail-price',
                data: {
                    _token: '{!! csrf_token() !!}',
                    prod_price_id: prod_price_id
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        var o = res.data;
                        $('#u_product_price_id').val(o.id);
                        $('#u_account_id').val(o.account_id);
                        $('#u_expired_date').val(o.expired_date);
                        $('#u_quick_note').val(o.quick_note);
                        $('#u_master_price').val(o.m_price);
                        $('#u_distributor_price').val(o.d_price);
                        $('#u_subagent_price').val(o.s_price);
                        $('#u_master_commission').val(o.m_commission);
                        $('#u_distributor_commission').val(o.d_commission);
                        $('#u_marketing').val(o.marketing);
                        $('#u_rebate_marketing').val(o.rebate_marketing);
                        if(o.is_free_ship == 'Y'){
                            $('#u_is_free_ship').prop('checked', true);
                        }
                        $('#u_min_quan').val(o.min_quan);
                        $('#u_max_quan').val(o.max_quan);
                        $('#modal_detail_price').modal();
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

            myApp.showConfirm('Are you sure to proceed?', function() {

                var is_free_ship = ($('#u_is_free_ship').is(":checked")) ? 'Y' : 'N';

                myApp.showLoading();

                $.ajax({
                    url: '/admin/settings/vr-product-price/update-product-price',
                    data: {
                        _token: '{!! csrf_token() !!}',
                        account_id: $('#u_account_id').val(),
                        product_price_id: $('#u_product_price_id').val(),

                        expired_date: $('#u_expired_date').val(),
                        quick_note: $('#u_quick_note').val(),

                        master_price: $('#u_master_price').val(),
                        distributor_price: $('#u_distributor_price').val(),
                        subagent_price: $('#u_subagent_price').val(),
                        master_commission: $('#u_master_commission').val(),
                        distributor_commission: $('#u_distributor_commission').val(),

                        marketing: $('#u_marketing').val(),
                        rebate_marketing: $('#u_rebate_marketing').val(),

                        min_quan: $('#u_min_quan').val(),
                        max_quan: $('#u_max_quan').val(),

                        is_free_ship : is_free_ship

                    },
                    cache: false,
                    type: 'post',
                    dataType: 'json',
                    success: function(res) {
                        myApp.hideLoading();
                        if ($.trim(res.msg) === '') {
                            myApp.showSuccess('Your request has been successfully submitted!');
                            location.href = "/admin/settings/vr-product-price";
                        } else {
                            myApp.showError(res.msg);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        myApp.hideLoading();
                        myApp.showError(errorThrown);
                    }
                });

            });
        }

        function delete_price(vr_product_price_id) {

            myApp.showConfirm('Are you sure to proceed?', function() {

                $.ajax({
                    url: '/admin/settings/vr-product-price/delete',
                    data: {
                        _token: '{!! csrf_token() !!}',
                        vr_product_price_id: vr_product_price_id
                    },
                    cache: false,
                    type: 'get',
                    dataType: 'json',
                    success: function (res) {
                        myApp.hideLoading();
                        if ($.trim(res.msg) === '') {

                            myApp.showSuccess('Your request has been successfully submitted!');
                            location.href = "/admin/settings/vr-product-price";
                        } else {
                            myApp.showError(res.msg);
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        myApp.hideLoading();
                        myApp.showError(errorThrown);
                    }
                });
            });
        }

        function delete_all(vr_prod_id) {

            myApp.showConfirm('Are you sure to proceed?', function() {

                $.ajax({
                    url: '/admin/settings/vr-product-price/delete_all',
                    data: {
                        _token: '{!! csrf_token() !!}',
                        vr_prod_id: vr_prod_id
                    },
                    cache: false,
                    type: 'get',
                    dataType: 'json',
                    success: function (res) {
                        myApp.hideLoading();
                        if ($.trim(res.msg) === '') {

                            myApp.showSuccess('Your request has been successfully submitted!');
                            location.href = "/admin/settings/vr-product-price";
                        } else {
                            myApp.showError(res.msg);
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        myApp.hideLoading();
                        myApp.showError(errorThrown);
                    }
                });
            });
        }

        function assign() {

            if ($('#account_ids').val().length < 1) {
                myApp.showError('Please Insert Account IDs');
                return;
            }

            if ($('#master_price').val() == '') {
                myApp.showError('Please Insert Master Price');
                return;
            }
            if ($('#distributor_price').val() == '') {
                myApp.showError('Please Insert Distributor Price');
                return;
            }
            if ($('#subagent_price').val() == '') {
                myApp.showError('Please Insert Sub Agent Price');
                return;
            }
            if ($('#master_commission').val() == '') {
                myApp.showError('Please Insert Master Commission');
                return;
            }
            if ($('#distributor_commission').val() == '') {
                myApp.showError('Please Insert Distributor Commission');
                return;
            }

            var is_free_ship = ($('#is_free_ship').is(":checked")) ? 'Y' : 'N';

            myApp.showLoading();

            $.ajax({
                url: '/admin/settings/vr-product-price/assign',
                data: {
                    _token: '{!! csrf_token() !!}',
                    u_id: $('#u_id').val(),
                    product_id: $('#product_id').val(),
                    // account_id: $('#account_id').val(),

                    account_ids: $('#account_ids').val(),

                    quick_note: $('#quick_note').val(),

                    master_price: $('#master_price').val(),
                    distributor_price: $('#distributor_price').val(),
                    subagent_price: $('#subagent_price').val(),
                    master_commission: $('#master_commission').val(),
                    distributor_commission: $('#distributor_commission').val(),

                    marketing: $('#marketing').val(),
                    rebate_marketing: $('#rebate_marketing').val(),

                    min_quan: $('#min_quan').val(),
                    max_quan: $('#max_quan').val(),
                    is_free_ship : is_free_ship,
                    expired_date: $('#expired_date').val()
                },
                cache: false,
                type: 'get',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        myApp.showSuccess('Your request has been successfully submitted!');
                        location.href = "/admin/settings/vr-product-price";
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

        function upload_image(prod_id) {
            $('#ui_prod_id').val(prod_id);
            $('#div_upload_product_img').modal();
        }

        function quick_search(sub_category, month) {
            $('#sub_category').val(sub_category);
            $('#month').val(month);
            $('#frm_search').submit();
        }

        function update_stock(prod_id, prod_name) {
            $('#u_prod_id').val(prod_id);
            $('#u_product_name').text(prod_name);
            $('#u_stock').val($('#txt_stock_' + prod_id).text());
            $('#div_update_product_stock').modal();
        }

        function refresh_all() {
            window.location.href = '/admin/settings/vr-product-price';
        }

    </script>

    <h4>VR Product Price</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post" onsubmit="myApp.showLoading();">
            {{ csrf_field() }}
            <input type="hidden" id="id" name="id"/>
            <input type="hidden" name="excel" id="excel" value=""/>
            <input type="hidden" name="month" id="month" value=""/>
            <div class="row">

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">VR Prod.ID</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="vr_prod_id" value="{{ $vr_prod_id }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Carrier</label>
                        <div class="col-md-8">
                            <select name="carrier" class="form-control">
                                <option value="">All</option>
                                @foreach ($carriers as $o)
                                    <option value="{{ $o->carrier }}" {{ $carrier == $o->carrier ? 'selected' : '' }}>{{ $o->carrier }}</option>
                                @endforeach
                            </select>
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
                        <label class="col-md-4 control-label">Account ID</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="account_id" value="{{ $account_id }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Is Default</label>
                        <div class="col-md-8">
                            <select name="default" class="form-control" >
                                <option value="">All</option>
                                <option value="Y" {{ old('default', 'Y') == $default ? 'selected' : '' }}>Yes</option>
                                <option value="N" {{ old('default', 'N') == $default ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Desc</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="desc" value="{{ $desc }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Acct Type</label>
                        <div class="col-md-8">
                            <select name="acct_type" class="form-control" >
                                <option value="">All</option>
                                <option value="M" {{ old('acct_type', 'M') == $acct_type ? 'selected' : '' }}>Master</option>
                                <option value="D" {{ old('acct_type', 'D') == $acct_type ? 'selected' : '' }}>Distributor</option>
                                <option value="S" {{ old('acct_type', 'S') == $acct_type ? 'selected' : '' }}>SubAgent</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Price [ Min - Max ]</label>
                        <div class="col-md-8">
                            <span class="control-label" style="margin-left:5px; float:left;"> $ </span>
                            <input type="text" style="width:100px; float:left;" class="form-control" id="min" name="min" value="{{ old('min', $min) }}"/>
                            <span class="control-label" style="margin-left:5px; float:left;"> ~ </span>
                            <span class="control-label" style="margin-left:5px; float:left;"> $ </span>
                            <input type="text" style="width:100px; margin-left: 5px; float:left;" class="form-control" id="max" name="max" value="{{ old('max', $max) }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Created.At</label>
                        <div class="col-md-8">
                            <input type="text" style="width:100px; float:left;" class="form-control" id="sdate_c" name="sdate_c" value="{{ old('sdate_c', $sdate_c) }}"/>
                            <span class="control-label" style="margin-left:5px; float:left;"> ~ </span>
                            <input type="text" style="width:100px; margin-left: 5px; float:left;" class="form-control" id="edate_c" name="edate_c" value="{{ old('edate_c', $edate_c) }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">VR.Product Status</label>
                        <div class="col-md-8">
                            <select name="prod_status" class="form-control" >
                                <option value="">All</option>
                                <option value="A" {{ old('prod_status', 'A') == $prod_status ? 'selected' : '' }}>Active</option>
                                <option value="I" {{ old('prod_status', 'I') == $prod_status ? 'selected' : '' }}>InActive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Sorting</label>
                        <div class="col-md-8">
                            <select name="sorting_filter" class="form-control">
                                <option value="">Select</option>
                                <option value="1" {{ $sorting_filter == '1' ? 'selected' : '' }}>VR.Prod.ID ASC</option>
                                <option value="2" {{ $sorting_filter == '2' ? 'selected' : '' }}>VR.Prod.ID DESC</option>
{{--                                <option value="3" {{ $sorting_filter == '3' ? 'selected' : '' }}>Sorting ASC</option>--}}
{{--                                <option value="4" {{ $sorting_filter == '4' ? 'selected' : '' }}>Sorting DESC</option>--}}
                            </select>
                        </div>

                    </div>
                </div>

                <div class="col-md-4 col-md-offset-8">
                    <div class="form-group">
                        <div class="col-md-12 text-right">
                            <button type="button" class="btn btn-info btn-sm" onclick="refresh_all()">Refresh All</button>
                            <button type="submit" class="btn btn-primary btn-sm" id="btn_search">Search</button>
                            @if(Auth::check() && in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']))
{{--                                <button type="button" class="btn btn-success btn-sm" onclick="show_new()">Add New VR Product</button>--}}
{{--                                <button type="button" class="btn btn-default btn-sm" onclick="show_detail()">Upload New VR Product</button>--}}
                                <button type="button" class="btn btn-info btn-sm" onclick="excel_export()">Download</button>
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
            <th style="text-align: center;">VR Prod.ID</th>
            <th style="text-align: center;">Carrier</th>
            <th style="text-align: center;">Model</th>
            <th style="text-align: center;">Desc</th>
            <th style="text-align: center;">Default</th>
            <th style="text-align: center;">Account ID</th>
            <th style="text-align: center;">Sub.Price</th>
            <th style="text-align: center;">Dis.Price</th>
            <th style="text-align: center;">Mas.Price</th>
            <th style="text-align: center;">Dis.Commi</th>
            <th style="text-align: center;">Mas.Commi</th>
            <th style="text-align: center;">Stock</th>
            <th style="text-align: center;">Min.Quantity</th>
            <th style="text-align: center;">Max.Quantity</th>
            <th style="text-align: center;">Marketing</th>
            <th style="text-align: center;">Rebate.Marketing</th>
            <th style="text-align: center;">Quick.Note</th>
            <th style="text-align: center;">Is.Free.Ship</th>
            <th style="text-align: center;">Expired.Date</th>
            <th style="text-align: center;">Upload.Date</th>
            <th style="text-align: center;">C.Date(Assigned)</th>
            <th style="text-align: center;">Status</th>
            <th style="text-align: center;">Assign Price</th>
        </tr>
        </thead>
        <tbody>
        @if (isset($data) && count($data) > 0)
            @foreach ($data as $o)
                <tr>
                    <td>{{ $o->id }}</td>
                    <td>{{ $o->carrier }}</td>
                    <td>{{ $o->model }}</td>
                    <td>{{ $o->desc }}</td>
                    @if ($o->m_price == 'm_price')
                        <td>Default</td>
                        <td></td>
                        <td>${{ number_format($o->subagent_price, 2) }}</td>
                        <td>${{ number_format($o->distributor_price, 2) }}</td>
                        <td>${{ number_format($o->master_price, 2) }}</td>
                        <td>${{ number_format($o->distributor_commission, 2) }}</td>
                        <td>${{ number_format($o->master_commission, 2) }}</td>
                    @else
                        <td></td>
                        <td>
                            @if ($o->m_price == 'm_price')
                                {!! Helper::get_account_name_html_by_id($o->account_id) !!}
                            @else
                                {!! Helper::get_account_name_html_by_id($o->account_id) !!}
                            @endif
                        </td>
                        <td>${{ number_format($o->s_price, 2) }}</td>
                        <td>${{ number_format($o->d_price, 2) }}</td>
                        <td>${{ number_format($o->m_price, 2) }}</td>
                        <td>${{ number_format($o->d_commission, 2) }}</td>
                        <td>${{ number_format($o->m_commission, 2) }}</td>
                    @endif
                    <td>{{ $o->stock }}</td>
                    @if ($o->m_price == 'm_price')
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    @else
                        <td>{{ $o->min_quan }}</td>
                        <td>{{ $o->max_quan }}</td>
                        <td>{{ $o->marketing }}</td>
                        <td>{{ $o->rebate_marketing }}</td>
                        <td>{{ $o->quick_note }}</td>
                        <td>{{ $o->is_free_ship }}</td>
                        <td>{{ $o->expired_date }}</td>
                    @endif
                    <td>{{ $o->upload_date }}</td>
                    @if(!empty($o->cdate) )
                        <td>{{ $o->cdate }}</td>
                    @else
                        <td>-</td>
                    @endif
                    <td>{{ $o->status }}</td>
                    <td>
                        @if ($o->m_price == 'm_price')
                            <a class="btn btn-primary btn-xs" style="margin-top: 4px;" onclick="add_price({{ $o->id }})">Add Assign</a>

                            <a class="btn btn-primary btn-xs" style="margin-top: 4px;" onclick="delete_all({{ $o->id }})">Delete All</a>

                        @else
                            <a class="btn btn-primary btn-xs" style="margin-top: 4px;" onclick="show_detail_price({{ $o->p_id }})">Update</a>
                            <a class="btn btn-primary btn-xs" style="margin-top: 4px;" onclick="delete_price({{ $o->p_id }})">Delete</a>
                        @endif
                    </td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="23" class="text-center">No Record Found</td>
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


    {{-- Detail Modal --}}
    <div class="modal" id="modal_detail" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Assign Price to Account</h4>
                    <h5 id="model_name"></h5>
                </div>

                <div class="modal-body">
                    <form id="frm_upload" class="form-horizontal filter" method="post" style="padding:15px;" target="ifm_upload" enctype="multipart/form-data">
{{--                        {{ csrf_field() }}--}}

                        <input type="hidden" name="u_id" id="u_id" value="">
                        <input type="hidden" name="product_id" id="product_id" value="">

                        <div id="row" class="row" style="border-bottom:solid 1px #dedede;">
{{--                            <div class="col-sm-6">--}}
{{--                                <div class="form-group">--}}
{{--                                    <label class="col-sm-4 control-label required">Account</label>--}}
{{--                                    <div class="col-sm-8">--}}
{{--                                        <input type="text" class="form-control" name="account_id" id="account_id" onchange="account_check()"/>--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                            </div>--}}

                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Accounts</label>
                                    <div class="col-sm-8">
                                        <textarea id="account_ids" rows="10" style="width:100%; line-height: 150%;"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Expired Date</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="expired_date"/>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Quick Note</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="quick_note"/>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div id="row" class="row" style="border-bottom:solid 1px #dedede; margin-top: 5px;">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">SubAgent Price</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="subagent_price" id="subagent_price"/>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Dist Price</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="distributor_price" id="distributor_price"/>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Master Price</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="master_price" id="master_price"/>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Marketing</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="marketing" id="marketing"/>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Is Free Shipping</label>
                                    <div class="col-sm-8">
                                        <input type="checkbox" class="" name="is_free_ship" id="is_free_ship"/>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-6">

                                <div class="form-group">
                                    <label class="col-sm-4 control-label required"></label>
                                    <div class="col-sm-8">
                                       .
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Dist Commission</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="distributor_commission" id="distributor_commission"/>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Master Commission</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="master_commission" id="master_commission"/>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Rebate Marketing</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="rebate_marketing" id="rebate_marketing"/>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label required">Min Quantity</label>
                                    <div class="col-sm-3">
                                        <input type="text" class="form-control" name="min_quan" id="min_quan"/>
                                    </div>
                                    <label class="col-sm-3 control-label required">Allocation</label>
                                    <div class="col-sm-3">
                                        <input type="text" class="form-control" name="max_quan" id="max_quan"/>
                                    </div>
                                </div>


                            </div>


                        </div>

                    </form>
                </div>

                <div class="modal-footer" style="margin-right:15px;">
                    {{--<a class="btn btn-warning" href="/upload_template/vr_upload_template.csv" target="_blank">Download Template</a>--}}
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="assign()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="modal_detail_price" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Update Assigned Product</h4>
                    <h5 id="model_name"></h5>
                </div>

                <div class="modal-body">
                    <form id="frm_upload" class="form-horizontal filter" method="post" style="padding:15px;" target="ifm_upload" enctype="multipart/form-data">

                        <input type="hidden" name="u_product_price_id" id="u_product_price_id" value="">
                        <input type="hidden" name="u_account_id" id="u_account_id" value="">
                        {{ csrf_field() }}
                        <div id="row" class="row" style="border-bottom:solid 1px #dedede;">

                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Expired Date</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="u_expired_date"/>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Quick Note</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="u_quick_note"/>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div id="row" class="row" style="border-bottom:solid 1px #dedede; margin-top: 5px;">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">SubAgent Price</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="u_subagent_price" id="u_subagent_price"/>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Dist Price</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="u_distributor_price" id="u_distributor_price"/>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Master Price</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="u_master_price" id="u_master_price"/>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Marketing</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="u_marketing" id="u_marketing"/>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Is Free Shipping</label>
                                    <div class="col-sm-8">
                                        <input type="checkbox" class="" name="u_is_free_ship" id="u_is_free_ship"/>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-6">

                                <div class="form-group">
                                    <label class="col-sm-4 control-label required"></label>
                                    <div class="col-sm-8">
                                        .
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Dist Commission</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="u_distributor_commission" id="u_distributor_commission"/>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Master Commission</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="u_master_commission" id="u_master_commission"/>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Rebate Marketing</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="u_rebate_marketing" id="u_rebate_marketing"/>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label required">Min Quantity</label>
                                    <div class="col-sm-3">
                                        <input type="text" class="form-control" name="u_min_quan" id="u_min_quan"/>
                                    </div>
                                    <label class="col-sm-3 control-label required">Allocation</label>
                                    <div class="col-sm-3">
                                        <input type="text" class="form-control" name="u_max_quan" id="u_max_quan"/>
                                    </div>
                                </div>


                            </div>


                        </div>

                    </form>
                </div>

                <div class="modal-footer" style="margin-right:15px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="update_detail()">Update</button>
                </div>
            </div>
        </div>
    </div>

@stop
