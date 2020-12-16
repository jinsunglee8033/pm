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

            $(".tooltip").tooltip({
                html: true
            });

            $('#image_upload_form').submit(function(evt) {

                evt.preventDefault();
                
                $('#div_upload_product_img').modal('hide');
                myApp.showLoading();

                var formData = new FormData($('#image_upload_form')[0]);

                $.ajax({
                    url: '/admin/settings/vr-upload/upload/image',
                    data: formData,
                    enctype: 'multipart/form-data',
                    processData: false,
                    contentType: false,
                    type: 'post',
                    dataType: 'json',
                    success: function(res) {
                        myApp.hideLoading();
                        if ($.trim(res.msg) === '') {
                            myApp.showSuccess('Your request has been successfully submitted!');
                            // $('#frm_search').submit();
                            //location.href = "/admin/settings/vr-upload/update";
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
        };

        function show_new() {
            $('#modal_add').modal();
        }

        function show_detail(prod_id) {

            $.ajax({
                url: '/admin/settings/vr-upload/show-detail',
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
                        $('#carrier').val(o.carrier);
                        $('#sub_carrier').val(o.sub_carrier);
                        $('#category').val(o.category);
                        $('#sub_category_').val(o.sub_category);

                        $('#service_month').val(o.service_month);
                        $('#plan').val(o.plan);
                        $('#make').val(o.make);
                        $('#type').val(o.type);
                        $('#model').val(o.model);
                        $('#marketing').val(o.marketing);
                        $('#rebate_marketing').val(o.rebate_marketing);
                        $('#promotion').val(o.promotion);
                        $('#url').val(o.url);
                        $('#desc').val(o.desc);
                        $('#grade').val(o.grade);
                        $('#stock').val(o.stock);
                        $('#supplier').val(o.supplier);

                        $('#memo').val(o.memo);
                        $('#master_price').val(o.master_price);
                        $('#distributor_price').val(o.distributor_price);
                        $('#master_commission').val(o.master_commission);

                        $('#distributor_commission').val(o.distributor_commission);
                        $('#subagent_price').val(o.subagent_price);
                        $('#rebate').val(o.rebate);
                        // $('#status').val(o.status);
                        $('#is_external').val(o.is_external);
                        $('#is_dropship').val(o.is_dropship);
                        $('#is_free_shipping').val(o.is_free_shipping);
                        $('#for_consignment').val(o.for_consignment);
                        $('#max_quantity').val(o.max_quantity);
                        $('#forever_quantity').val(o.forever_quantity);
                        $('#sorting').val(o.sorting);

                        if(o.exclude_all_sub == 'Y'){
                            $('#exclude_all_sub').attr('checked', true);
                        }
                        if(o.exclude_all_dis == 'Y'){
                            $('#exclude_all_dis').attr('checked', true);
                        }
                        if(o.exclude_all_mas == 'Y'){
                            $('#exclude_all_mas').attr('checked', true);
                        }

                        if(!o.in_accts == ''){
                            var vi = '';
                            $.each(o.in_accts, function(i, n){
                                vi = vi + (n.account_id) + '\n';
                            });
                            $('#include_account_ids').val(vi);
                        }

                        if(!o.ex_accts == ''){
                            var vi = '';
                            $.each(o.ex_accts, function(i, n){
                                vi = vi + (n.account_id) + '\n';
                            });
                            $('#exclude_account_ids').val(vi);
                        }

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

            $('#div_upload').modal();
        }

        function update_detail() {

            var exclude_all_sub = ($('#exclude_all_sub').is(":checked")) ? 'Y' : null;
            var exclude_all_dis = ($('#exclude_all_dis').is(":checked")) ? 'Y' : null;
            var exclude_all_mas = ($('#exclude_all_mas').is(":checked")) ? 'Y' : null;

            myApp.showLoading();

            $.ajax({
                url: '/admin/settings/vr-upload/update-detail',
                data: {
                    _token: '{!! csrf_token() !!}',
                    u_id: $('#u_id').val(),
                    carrier: $('#carrier').val(),
                    sub_carrier: $('#sub_carrier').val(),
                    category: $('#category').val(),
                    sub_category: $('#sub_category_').val(),
                    service_month: $('#service_month').val(),

                    plan: $('#plan').val(),
                    make: $('#make').val(),
                    type: $('#type').val(),
                    model: $('#model').val(),
                    marketing: $('#marketing').val(),
                    rebate_marketing: $('#rebate_marketing').val(),
                    promotion: $('#promotion').val(),
                    url: $('#url').val(),
                    desc: $('#desc').val(),
                    grade: $('#grade').val(),
                    stock: $('#stock').val(),
                    supplier: $('#supplier').val(),
                    memo: $('#memo').val(),
                    master_price: $('#master_price').val(),
                    distributor_price: $('#distributor_price').val(),
                    master_commission: $('#master_commission').val(),
                    distributor_commission: $('#distributor_commission').val(),
                    subagent_price: $('#subagent_price').val(),
                    rebate: $('#rebate').val(),
                    // status: $('#status').val(),
                    is_external: $('#is_external').val(),
                    is_dropship: $('#is_dropship').val(),
                    is_free_shipping: $('#is_free_shipping').val(),

                    include_account_ids: $('#include_account_ids').val(),
                    exclude_account_ids: $('#exclude_account_ids').val(),

                    exclude_all_sub: exclude_all_sub,
                    exclude_all_dis: exclude_all_dis,
                    exclude_all_mas: exclude_all_mas,

                    for_consignment: $('#for_consignment').val(),
                    max_quantity: $('#max_quantity').val(),
                    forever_quantity: $('#forever_quantity').val(),
                    sorting: $('#sorting').val()
                },
                cache: false,
                type: 'get',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        myApp.showSuccess('Your request has been successfully submitted!');
                        $('#modal_detail').modal('hide');
                        // location.href = "/admin/settings/vr-upload";
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

        function clone_detail() {

            var exclude_all_sub = ($('#exclude_all_sub').is(":checked")) ? 'Y' : null;
            var exclude_all_dis = ($('#exclude_all_dis').is(":checked")) ? 'Y' : null;
            var exclude_all_mas = ($('#exclude_all_mas').is(":checked")) ? 'Y' : null;

            myApp.showLoading();

            $.ajax({
                url: '/admin/settings/vr-upload/clone-detail',
                data: {
                    _token: '{!! csrf_token() !!}',
                    u_id: $('#u_id').val(),
                    carrier: $('#carrier').val(),
                    sub_carrier: $('#sub_carrier').val(),
                    category: $('#category').val(),
                    sub_category: $('#sub_category_').val(),
                    service_month: $('#service_month').val(),

                    plan: $('#plan').val(),
                    make: $('#make').val(),
                    type: $('#type').val(),
                    model: $('#model').val(),
                    marketing: $('#marketing').val(),
                    rebate_marketing: $('#rebate_marketing').val(),
                    promotion: $('#promotion').val(),
                    url: $('#url').val(),
                    desc: $('#desc').val(),
                    grade: $('#grade').val(),
                    stock: $('#stock').val(),
                    supplier: $('#supplier').val(),
                    memo: $('#memo').val(),
                    master_price: $('#master_price').val(),
                    distributor_price: $('#distributor_price').val(),
                    master_commission: $('#master_commission').val(),
                    distributor_commission: $('#distributor_commission').val(),
                    subagent_price: $('#subagent_price').val(),
                    rebate: $('#rebate').val(),
                    // status: $('#status').val(),
                    is_external: $('#is_external').val(),
                    is_dropship: $('#is_dropship').val(),
                    is_free_shipping: $('#is_free_shipping').val(),

                    include_account_ids: $('#include_account_ids').val(),
                    exclude_account_ids: $('#exclude_account_ids').val(),

                    exclude_all_sub: exclude_all_sub,
                    exclude_all_dis: exclude_all_dis,
                    exclude_all_mas: exclude_all_mas,

                    for_consignment: $('#for_consignment').val(),
                    max_quantity: $('#max_quantity').val(),
                    forever_quantity: $('#forever_quantity').val(),
                    sorting: $('#sorting').val()
                },
                cache: false,
                type: 'get',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        myApp.showSuccess('Your request has been successfully submitted!');
                        location.href = "/admin/settings/vr-upload";
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

        function add_detail() {

            myApp.showLoading();

            $.ajax({
                url: '/admin/settings/vr-upload/add-detail',
                data: {
                    carrier: $('#n_carrier').val(),
                    sub_carrier: $('#n_sub_carrier').val(),
                    category: $('#n_category').val(),
                    sub_category: $('#n_sub_category').val(),
                    service_month: $('#n_service_month').val(),

                    plan: $('#n_plan').val(),
                    make: $('#n_make').val(),
                    type: $('#n_type').val(),
                    model: $('#n_model').val(),
                    marketing: $('#n_marketing').val(),
                    rebate_marketing: $('#n_rebate_marketing').val(),
                    promotion: $('#n_promotion').val(),
                    url: $('#n_url').val(),
                    desc: $('#n_desc').val(),
                    grade: $('#n_grade').val(),
                    stock: $('#n_stock').val(),
                    supplier: $('#n_supplier').val(),
                    memo: $('#n_memo').val(),
                    master_price: $('#n_master_price').val(),
                    distributor_price: $('#n_distributor_price').val(),
                    master_commission: $('#n_master_commission').val(),
                    distributor_commission: $('#n_distributor_commission').val(),
                    subagent_price: $('#n_subagent_price').val(),
                    rebate: $('#n_rebate').val(),
                    // status: $('#status').val(),
                    is_external: $('#n_is_external').val(),
                    is_dropship: $('#n_is_dropship').val(),
                    for_consignment: $('#n_for_consignment').val(),
                    is_free_shipping: $('#n_is_free_shipping').val(),
                    include_account_ids: $('#n_include_account_ids').val(),
                    exclude_account_ids: $('#n_exclude_account_ids').val(),
                    max_quantity: $('#n_max_quantity').val(),
                    forever_quantity: $('#n_forever_quantity').val(),
                    include_ids: $('#n_include_account_ids').val(),
                    exclude_ids: $('#n_exclude_account_ids').val(),
                    sorting: $('#n_sorting').val()
                },
                cache: false,
                type: 'get',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        myApp.showSuccess('Your request has been successfully submitted!');
                        location.href = "/admin/settings/vr-upload";
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

            var file = $('#vr_csv_file').val();
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

        function update(prod_id, status) {

            myApp.showLoading();

            $.ajax({
                url: '/admin/settings/vr-upload/update',
                data: {
                    _token: '{!! csrf_token() !!}',
                    prod_id: prod_id,
                    status: status
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        myApp.showSuccess('Your request has been successfully submitted!');
                        $('#frm_search').submit();
                        //location.href = "/admin/settings/vr-upload/update";
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

        function upload_image(prod_id) {
            $('#ui_prod_id').val(prod_id);
            $('#div_upload_product_img').modal();
        }

        function quick_search(sub_category, month) {
            $('#sub_category').val(sub_category);
            $('#month').val(month);
            $('#frm_search').submit();
        }

        function update_stock(prod_id) {
            $('#u_prod_id').val(prod_id);
            $('#u_stock').val($('#txt_stock_' + prod_id).text());
            $('#div_update_product_stock').modal();
        }

        function update_sorting(prod_id) {
            $('#u_prod_id').val(prod_id);
            $('#u_sorting').val($('#txt_sorting_' + prod_id).text());
            $('#div_update_product_sorting').modal();
        }

        function update_sorting_submit() {

            myApp.showLoading();

            $.ajax({
                url: '/admin/settings/vr-upload/update/sorting',
                data: {
                    _token: '{!! csrf_token() !!}',
                    prod_id: $('#u_prod_id').val(),
                    sorting: $('#u_sorting').val()
                },
                cache: false,
                type: 'get',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        myApp.showSuccess('Your request has been successfully submitted!');
                        $('#txt_sorting_' + $('#u_prod_id').val()).text($('#u_sorting').val());
                        $('#div_update_product_sorting').modal('hide');
                        //location.href = "/admin/settings/vr-upload/update";
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

        function update_stock_submit() {

            myApp.showLoading();

            $.ajax({
                url: '/admin/settings/vr-upload/update/stock',
                data: {
                    _token: '{!! csrf_token() !!}',
                    prod_id: $('#u_prod_id').val(),
                    stock: $('#u_stock').val()
                },
                cache: false,
                type: 'get',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        myApp.showSuccess('Your request has been successfully submitted!');
                        $('#txt_stock_' + $('#u_prod_id').val()).text($('#u_stock').val());
                        $('#div_update_product_stock').modal('hide');
                        //location.href = "/admin/settings/vr-upload/update";
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

        function refresh_all() {
            window.location.href = '/admin/settings/vr-upload';
        }

    </script>

    <h4>VR Upload</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post" onsubmit="myApp.showLoading();">
            {{ csrf_field() }}
            <input type="hidden" id="id" name="id"/>
            <input type="hidden" name="excel" id="excel" value=""/>
            <input type="hidden" name="month" id="month" value=""/>
            <div class="row">
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
                        <label class="col-md-4 control-label">Sub.Carrier</label>
                        <div class="col-md-8">
                            <select name="sub_carrier" class="form-control">
                                <option value="">All</option>
                                @foreach ($sub_carriers as $o)
                                    <option value="{{ $o->sub_carrier }}" {{ $sub_carrier == $o->sub_carrier ? 'selected' : '' }}>{{ $o->sub_carrier }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Category</label>
                        <div class="col-md-8">
                            <select name="category" class="form-control">
                                <option value="">All</option>
                                @foreach ($categories as $o)
                                    <option value="{{ $o->category }}" {{ $category == $o->category ? 'selected' : '' }}>{{ $o->category }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Sub.Category</label>
                        <div class="col-md-8">
                            <select name="sub_category" id="sub_category" class="form-control">
                                <option value="">All</option>
                                @foreach ($sub_categories as $o)
                                    <option value="{{ $o->sub_category }}" {{ $sub_category == $o->sub_category ? 'selected' : '' }}>{{ $o->sub_category }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Month.Service</label>
                        <div class="col-md-8">
                            <select name="service_month" class="form-control">
                                <option value="">All</option>
                                @foreach ($service_months as $o)
                                    <option value="{{ $o->service_month }}" {{ $service_month == $o->service_month ? 'selected' : '' }}>{{ $o->service_month }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Plan</label>
                        <div class="col-md-8">
                            <select name="plan" class="form-control">
                                <option value="">All</option>
                                @foreach ($plans as $o)
                                    <option value="{{ $o->plan }}" {{ $plan == $o->plan ? 'selected' : '' }}>{{ $o->plan }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Make</label>
                        <div class="col-md-8">
                            <select name="make" class="form-control">
                                <option value="">All</option>
                                @foreach ($makes as $o)
                                    <option value="{{ $o->make }}" {{ $make == $o->make ? 'selected' : '' }}>{{ $o->make }}</option>
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
                        <label class="col-md-4 control-label">Type</label>
                        <div class="col-md-8">
                            <select name="type" class="form-control">
                                <option value="">All</option>
                                @foreach ($types as $o)
                                    <option value="{{ $o->type }}" {{ $type == $o->type ? 'selected' : '' }}>{{ $o->type }}</option>
                                @endforeach
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
                        <label class="col-md-4 control-label">Grade</label>
                        <div class="col-md-8">
                            <select name="grade" class="form-control">
                                <option value="">All</option>
                                @foreach ($grades as $o)
                                    <option value="{{ $o->grade }}" {{ $grade == $o->grade ? 'selected' : '' }}>{{ $o->grade }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Promotion</label>
                        <div class="col-md-8">
                            <select name="promotion" class="form-control">
                                <option value="">All</option>
                                @foreach ($promotions as $p)
                                    <option value="{{ $p->promotion }}" {{ $promotion == $p->promotion ? 'selected' : '' }}>{{ $p->promotion }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Status</label>
                        <div class="col-md-8">
                            <select name="status" class="form-control">
                                <option value="">All</option>
                                @foreach ($statuss as $s)
                                    <option value="{{ $s->status }}" {{ $status == $s->status ? 'selected' : '' }}>{{ $s->status }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Is External</label>
                        <div class="col-md-4">
                            <input type="checkbox" name="is_external" value="Y" {{ $is_external == 'Y' ? 'checked' : '' }}/>
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
                        <label class="col-md-4 control-label">Is Free Shipping</label>
                        <div class="col-md-4">
                            <input type="checkbox" name="is_free_shipping" value="Y" {{ $is_free_shipping == 'Y' ? 'checked' : '' }}/>
                        </div>
                    </div>
                </div>
                @if(Auth::check() && in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']))
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Stock</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="stock" value="{{ $stock }}" placeholder="Empty: All, Not Empty: Stock <= Value"/>
                        </div>
                    </div>
                </div>
                @endif

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Rebate.Marketing</label>
                        <div class="col-md-8">
                            <select name="rebate_marketing" class="form-control">
                                <option value="">All</option>
                                @foreach ($rebate_marketings as $o)
                                    <option value="{{ $o->rebate_marketing }}" {{ $rebate_marketing == $o->rebate_marketing ? 'selected' : '' }}>{{ $o->rebate_marketing }}</option>
                                @endforeach
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
                        <label class="col-md-4 control-label">Marketing</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="marketing" value="{{ $marketing }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">VR.Prod.ID</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="vr_prod_id" value="{{ $vr_prod_id }}"/>
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
                                <option value="3" {{ $sorting_filter == '3' ? 'selected' : '' }}>Sorting ASC</option>
                                <option value="4" {{ $sorting_filter == '4' ? 'selected' : '' }}>Sorting DESC</option>
                            </select>
                        </div>

                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-3 control-label">Exclude All Sub</label>
                        <div class="col-md-1">
                            <input type="checkbox" name="exclude_all_sub" value="Y" {{ $exclude_all_sub == 'Y' ? 'checked' : '' }}/>
                        </div>
                        <label class="col-md-3 control-label">Exclude All Dis</label>
                        <div class="col-md-1">
                            <input type="checkbox" name="exclude_all_dis" value="Y" {{ $exclude_all_dis == 'Y' ? 'checked' : '' }}/>
                        </div>
                        <label class="col-md-3 control-label">Exclude All Mas</label>
                        <div class="col-md-1">
                            <input type="checkbox" name="exclude_all_mas" value="Y" {{ $exclude_all_mas == 'Y' ? 'checked' : '' }}/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 col-md-offset-8">
                    <div class="form-group">
                        <div class="col-md-12 text-right">
                            <button type="button" class="btn btn-info btn-sm" onclick="refresh_all()">Refresh All</button>
                            <button type="submit" class="btn btn-primary btn-sm" id="btn_search">Search</button>
                            @if(Auth::check() && in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']))
                                <button type="button" class="btn btn-success btn-sm" onclick="show_new()">Add New VR Product</button>
{{--                                <button type="button" class="btn btn-default btn-sm" onclick="show_detail()">Upload New VR Product</button>--}}
                                <button type="button" class="btn btn-info btn-sm" onclick="excel_export()">Download</button>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group quick-search">
                        <label class=" control-label">Quick Search</label>
                            @foreach ($sub_categories as $o)
                                @if($o->sub_category == $sub_category)
                                    <button type="button" class="btn btn-primary btn" onclick="quick_search('{{ $o->sub_category }}')">
                                @else
                                    <button type="button" class="btn btn-info btn" onclick="quick_search('{{ $o->sub_category }}')">
                                @endif
                                {{ $o->sub_category }}
                                @if($o->sub_category == 'BUNDLE') (Handset+SIM+Plan)</button>
                                    <table class="quick-search-month">
                                        <tr><td><input type="checkbox" id="b1" onclick="quick_search('BUNDLE','1')" {{ ($o->sub_category == $sub_category && $month == 1) ? 'checked="checked"' : '' }}> 1 month</td></tr>
                                        <tr><td><input type="checkbox" id="b2" onclick="quick_search('BUNDLE','2')" {{ ($o->sub_category == $sub_category && $month == 2) ? 'checked="checked"' : '' }}> 2 month</td></tr>
                                        <tr><td><input type="checkbox" id="b3" onclick="quick_search('BUNDLE','3')" {{ ($o->sub_category == $sub_category && $month == 3) ? 'checked="checked"' : '' }}> 3 month</td></tr>
                                    </table>
                                @elseif($o->sub_category == 'PRELOAD') (SIM+Plan)</button>
                                    <table class="quick-search-month">
                                        <tr><td><input type="checkbox" id="p1" onclick="quick_search('PRELOAD','1')" {{ ($o->sub_category == $sub_category && $month == 1) ? 'checked="checked"' : '' }}> 1 month</td></tr>
                                        <tr><td><input type="checkbox" id="p2" onclick="quick_search('PRELOAD','2')" {{ ($o->sub_category == $sub_category && $month == 2) ? 'checked="checked"' : '' }}> 2 month</td></tr>
                                        <tr><td><input type="checkbox" id="p3" onclick="quick_search('PRELOAD','3')" {{ ($o->sub_category == $sub_category && $month == 3) ? 'checked="checked"' : '' }}> 3 month</td></tr>
                                    </table>
                                @else
                                    </button> &nbsp; &nbsp;
                                @endif
                            @endforeach
                    </div>
                </div>
            </div>
        </form>
    </div>


    <table class="table table-bordered table-hover table-condensed filter">
        <thead>
        <tr>
            <th style="text-align: center;">VR.Prod.ID</th>
            <th style="text-align: center;">Carrier</th>
            <th style="text-align: center;">Sub.Carrier</th>
            <th style="text-align: center;">Category</th>
            <th style="text-align: center;">Sub.Category</th>
            <th style="text-align: center;">Mon.Ser</th>
            <th style="text-align: center;">Plan</th>
            <th style="text-align: center;">Make</th>
            <th style="text-align: center;">Type</th>
            <th style="text-align: center;">Model</th>
            <th style="text-align: center;">Marketing</th>
            <th style="text-align: center;">Rebate.Marketing</th>
            <th style="text-align: center;">Desc</th>
            <th style="text-align: center;">Grade</th>
            <th style="text-align: center;">Stock</th>
            <th style="text-align: center;">Sorting</th>
            <th style="text-align: center;">Supplier</th>
            <th style="text-align: center;">Memo</th>
            <th style="text-align: center;">Mas.Commi</th>
            <th style="text-align: center;">Dis.Commi</th>
            <th style="text-align: center;">Mas.Price</th>
            <th style="text-align: center;">Dis.Price</th>
            <th style="text-align: center;">SubA.Price</th>
            <th style="text-align: center;">Rebate</th>
            <th style="text-align: center;">Ext</th>
            <th style="text-align: center;">D.ship</th>
            <th style="text-align: center;">Free.shipping</th>
            <th style="text-align: center;">Allocation</th>
            <th style="text-align: center;">Forever.Quantity</th>
            <th style="text-align: center;">Consignment</th>
            <th style="text-align: center;">Upload.Date</th>
            <th style="text-align: center;">Update Status</th>
        </tr>
        </thead>
        <tbody>
        @if (isset($data) && count($data) > 0)
            @foreach ($data as $o)
                <tr>
                    <td><a onclick="show_detail('{{ $o->id }}')" style="cursor:pointer;" >{{ $o->id }}</a></td>
                    <td>{{ $o->carrier }}</td>
                    <td>{{ $o->sub_carrier }}</td>
                    <td>{{ $o->category }}</td>
                    <td>{{ $o->sub_category }}</td>
                    <td>{{ $o->service_month }}</td>
                    <td>{{ $o->plan }}</td>
                    <td>{{ $o->make }}</td>
                    <td>{{ $o->type }}</td>
                    <td>
                        @if (!empty($o->url))
                            <a href="{{ $o->url }}" target="_blank">{{ $o->model }}</a>
                        @else
                            {{ $o->model }}
                        @endif
                    </td>
                    <td>{{ $o->marketing }}</td>
                    <td>{{ $o->rebate_marketing }}</td>
                    <td>{{ $o->desc }}</td>
                    <td>{{ $o->grade }}</td>
                    <td><a onclick="update_stock('{{ $o->id }}')" id="txt_stock_{{
                     $o->id }}" style="cursor:pointer;">{{
                    $o->stock
                    }}</a></td>
                    <td><a onclick="update_sorting('{{ $o->id }}')" id="txt_sorting_{{
                     $o->id }}" style="cursor:pointer;">{{
                    $o->sorting
                    }}</a></td>
                    <td>{{ $o->supplier }}</td>
                    <td>{{ $o->memo }}</td>
                    <td>${{ number_format($o->master_commission, 2) }}</td>
                    <td>${{ number_format($o->distributor_commission, 2) }}</td>
                    <td>${{ number_format($o->master_price, 2) }}</td>
                    <td>${{ number_format($o->distributor_price, 2) }}</td>
                    <td>${{ number_format($o->subagent_price, 2) }}</td>
                    <td>${{ number_format($o->rebate, 2) }}</td>
                    <td>{{ $o->is_external }}</td>
                    <td>{{ $o->is_dropship }}</td>
                    <td>{{ $o->is_free_shipping }}</td>
                    <td>{{ $o->max_quantity }}</td>
                    <td>{{ $o->forever_quantity }}</td>
                    <td>{{ $o->for_consignment }}</td>
                    <td>{{ $o->upload_date }}</td>
                    <td>
                        @if ($o->status == 'A')
                        <button id="inactive" class="btn btn-primary btn-xs" onclick="update('{{ $o->id }}', 'I')">Inactive</button>
                        @else
                        <button id="active" class="btn btn-primary btn-xs" onclick="update('{{ $o->id }}', 'A')">Active</button>
                        @endif
                        <br>
                        <a class="btn btn-primary btn-xs" style="margin-top: 4px;" onclick="upload_image({{ $o->id }})">Upload Image</a>
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

    <div class="modal" id="div_detail" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">UPLOAD VR PRODUCTS</h4>
                </div>
                <div class="modal-body">

                    <form id="frm_upload" action="/admin/settings/vr-upload/upload" class="form-horizontal filter" method="post" style="padding:15px;" target="ifm_upload" enctype="multipart/form-data">
                        {{ csrf_field() }}
                        <div class="form-group">
                            <label class="col-sm-4 control-label required">Select CSV File to Upload</label>
                            <div class="col-sm-8">
                                <input type="file" class="form-control" name="vr_csv_file" id="vr_csv_file"/>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="margin-right:15px;">
                    {{--<a class="btn btn-warning" href="/upload_template/vr_upload_template.csv" target="_blank">Download Template</a>--}}
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="save_detail()">Upload</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Detail Modal --}}
    <div class="modal" id="modal_detail" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">UPDATE VR PRODUCTS</h4>
                </div>

                <div class="modal-body">
                    <form id="frm_upload" class="form-horizontal filter" method="post" style="padding:15px;" target="ifm_upload" enctype="multipart/form-data">
{{--                        {{ csrf_field() }}--}}

                        <input type="hidden" name="u_id" id="u_id" value="">
                        <div class="row" style="border-bottom:solid 1px #dedede; margin-top:-25px;">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Carrier</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="carrier" name="carrier"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Category</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="category" name="category"/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Sub Carrier</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="sub_carrier" name="sub_carrier"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Sub Category</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="sub_category_" name="sub_category_"/>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row" style="border-bottom:solid 1px #dedede; margin-top:5px;">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Service Month</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="service_month" name="service_month"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Plan</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="plan" name="plan"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Make</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="make" name="make"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Type</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="type" id="type"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Model</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="model" id="model"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Marketing</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="marketing" id="marketing"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Memo</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="memo" id="memo"/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Rebate Marketing</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="rebate_marketing" id="rebate_marketing"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Promotion</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="promotion" id="promotion"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">URL</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="url" id="url"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Desc</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="desc" id="desc"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Grade</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="grade" id="grade"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Stock</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="stock" id="stock"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Supplier</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="supplier" id="supplier"/>
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
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label"></label>
                                    <div class="col-sm-8">
                                        .
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Dist Commission</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="distributor_commission" id="distributor_commission"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Master Commission</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="master_commission" id="master_commission"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row" style="border-bottom:solid 1px #dedede; margin-top: 5px;">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Rebate</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="rebate" id="rebate"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">For Consignment</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="for_consignment" id="for_consignment"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Monthly Allocation</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="max_quantity" id="max_quantity"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-6 control-label">Entire Allocation (No Renew)</label>
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control" name="forever_quantity" id="forever_quantity"/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Is External</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="is_external" id="is_external"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Is Dropship</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="is_dropship" id="is_dropship"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Is Free Shipping</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="is_free_shipping" id="is_free_shipping"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">sorting</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="sorting" id="sorting"/>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="row" style="border-bottom:solid 1px #dedede; margin-top:-25px;">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Acct Included</label>
                                    <div class="col-sm-8">
                                        <textarea class="form-control" rows="10" name="include_account_ids" id="include_account_ids" placeholder=""></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Acct Excluded</label>
                                    <div class="col-sm-8">
                                        <textarea class="form-control" rows="10" name="exclude_account_ids" id="exclude_account_ids" placeholder=""></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row" style="border-bottom:solid 1px #dedede; margin-top:-25px;">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label class="col-sm-8 control-label">Exclude all Subagents</label>
                                    <div class="col-sm-4">
                                        <input type="checkbox" name="exclude_all_sub" id="exclude_all_sub"/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label class="col-sm-8 control-label">Exclude all Distributors</label>
                                    <div class="col-sm-4">
                                        <input type="checkbox" name="exclude_all_dis" id="exclude_all_dis">
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label class="col-sm-8 control-label">Exclude all Masters</label>
                                    <div class="col-sm-4">
                                        <input type="checkbox" name="exclude_all_mas" id="exclude_all_mas">
                                    </div>
                                </div>
                            </div>
                        </div>


                    </form>
                </div>

                <div class="modal-footer" style="margin-right:15px;">
                    {{--<a class="btn btn-warning" href="/upload_template/vr_upload_template.csv" target="_blank">Download Template</a>--}}
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="update_detail()">Update</button>
                    <button type="button" class="btn btn-primary" onclick="clone_detail()">Copy & Add</button>
                </div>
            </div>
        </div>
    </div>


    {{-- Add New Modal --}}
    <div class="modal" id="modal_add" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">ADD VR PRODUCTS</h4>
                </div>

                <div class="modal-body">
                    <form id="frm_upload" class="form-horizontal filter" method="post" style="padding:15px;" target="ifm_upload" enctype="multipart/form-data">

                        <input type="hidden" name="u_id" id="u_id" value="">
                        <div class="row" style="border-bottom:solid 1px #dedede; margin-top:-25px;">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Carrier</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="n_carrier" name="n_carrier"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Category</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="n_category" name="n_category"/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Sub Carrier</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="n_sub_carrier" name="n_sub_carrier"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Sub Category</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="n_sub_category" name="n_sub_category"/>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row" style="border-bottom:solid 1px #dedede; margin-top:5px;">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Service Month</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="n_service_month" name="n_service_month"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Plan</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="n_plan" name="n_plan"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Make</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="n_make" name="n_make"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Type</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="n_type" id="n_type"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Model</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="n_model" id="n_model"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Marketing</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="n_marketing" id="n_marketing"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Memo</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="n_memo" id="n_memo"/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Rebate Marketing</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="n_rebate_marketing" id="n_rebate_marketing"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Promotion</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="n_promotion" id="n_promotion"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">URL</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="n_url" id="n_url"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Desc</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="n_desc" id="n_desc"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Grade</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="n_grade" id="n_grade"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Stock</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="n_stock" id="n_stock"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Supplier</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="n_supplier" id="n_supplier"/>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div id="row" class="row" style="border-bottom:solid 1px #dedede; margin-top: 5px;">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Master Price</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="n_master_price" id="n_master_price"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Dist Price</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="n_distributor_price" id="n_distributor_price"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">SubAgent Price</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="n_subagent_price" id="n_subagent_price"/>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Master Commission</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="n_master_commission" id="n_master_commission"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Dist Commission</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="n_distributor_commission" id="n_distributor_commission"/>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row" style="border-bottom:solid 1px #dedede; margin-top: 5px;">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Rebate</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="n_rebate" id="n_rebate"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">For Consignment</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="n_for_consignment" id="n_for_consignment"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Allocation</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="n_max_quantity" id="n_max_quantity"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Forever Quantity</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="n_forever_quantity" id="n_forever_quantity"/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Is External</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="n_is_external" id="n_is_external"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Is Dropship</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="n_is_dropship" id="n_is_dropship"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Is Free Shipping</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="n_is_free_shipping" id="n_is_free_shipping"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">sorting</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="n_sorting" id="n_sorting"/>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="row" style="border-bottom:solid 1px #dedede; margin-top:-25px;">

                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Acct Included</label>
                                    <div class="col-sm-8">
                                        <textarea class="form-control" rows="10" name="n_include_account_ids" id="n_include_account_ids" placeholder=""></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Acct Excluded</label>
                                    <div class="col-sm-8">
                                        <textarea class="form-control" rows="10" name="n_exclude_account_ids" id="n_exclude_account_ids" placeholder=""></textarea>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </form>
                </div>

                <div class="modal-footer" style="margin-right:15px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="add_detail()">Add</button>
                </div>
            </div>
        </div>
    </div>


    <div style="display:none">
        <iframe name="ifm_upload"></iframe>
    </div>

    <div class="modal" id="div_upload_product_img" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form id="image_upload_form" action="/admin/settings/vr-upload/upload/image" class="form-horizontal filter" method="post" style="padding:15px;" target="ifm_upload" enctype="multipart/form-data">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                    aria-hidden="true">x</span></button>
                        <h4 class="modal-title" id="title">UPLOAD PRODUCT'S IMAGE</h4>
                    </div>
                    <div class="modal-body">
                        {{ csrf_field() }}
                        <input type="hidden" id="ui_prod_id" name="prod_id">
                        <div class="form-group">
                            <input type="file" class="form-control" name="image" id="image"/>
                        </div>
                    </div>
                    <div class="modal-footer" style="margin-right:15px;">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal" id="div_update_product_stock" tabindex="-1" role="dialog" data-backdrop="static"
         data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">UPDATE PRODUCT'S STOCK</h4>
                </div>
                <div class="modal-body">
                    {{ csrf_field() }}
                    <input type="hidden" id="u_prod_id">
                    <h5 id="u_product_name"></h5>
                    <div class="form-group">
                        <label class="col-sm-4 control-label required">Stock</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="stock" id="u_stock"/>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="margin-right:15px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="update_stock_submit()">Update</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="div_update_product_sorting" tabindex="-1" role="dialog" data-backdrop="static"
         data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">UPDATE PRODUCT'S Sorting</h4>
                </div>
                <div class="modal-body">
                    {{ csrf_field() }}
                    <input type="hidden" id="u_prod_id">
                    <h5 id="u_product_name"></h5>
                    <div class="form-group">
                        <label class="col-sm-4 control-label required">Sorting</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="sorting" id="u_sorting"/>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="margin-right:15px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="update_sorting_submit()">Update</button>
                </div>
            </div>
        </div>
    </div>
@stop
