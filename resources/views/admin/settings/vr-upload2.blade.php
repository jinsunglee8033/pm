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
                    url: '/admin/settings/vr-upload2/upload/image',
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
                            location.href = "/admin/settings/vr-upload2";
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
                url: '/admin/settings/vr-upload2/show-detail',
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
                        $('#category').val(o.category)
                        $('#product_name').val(o.product_name);
                        $('#url').val(o.url);
                        $('#memo1').val(o.memo1);
                        $('#memo2').val(o.memo2);
                        $('#comment').val(o.comment);

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

            myApp.showLoading();

            $.ajax({
                url: '/admin/settings/vr-upload2/update-detail',
                data: {
                    _token: '{!! csrf_token() !!}',
                    u_id: $('#u_id').val(),
                    carrier: $('#carrier').val(),
                    category: $('#category').val(),
                    product_name: $('#product_name').val(),
                    memo1: $('#memo1').val(),
                    memo2: $('#memo2').val(),
                    comment: $('#comment').val()
                },
                cache: false,
                type: 'get',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        myApp.showSuccess('Your request has been successfully submitted!');
                        location.href = "/admin/settings/vr-upload2";
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

        function copy_update() {

            myApp.showConfirm('Are you sure to proceed?', function() {

                myApp.showLoading();

                $.ajax({
                    url: '/admin/settings/vr-upload2/copy_update',
                    data: {
                        _token: '{!! csrf_token() !!}',
                        carrier: $('#carrier').val(),
                        category: $('#category').val(),
                        product_name: $('#product_name').val(),
                        memo1: $('#memo1').val(),
                        memo2: $('#memo2').val(),
                        comment: $('#comment').val()
                    },
                    cache: false,
                    type: 'get',
                    dataType: 'json',
                    success: function (res) {
                        myApp.hideLoading();
                        if ($.trim(res.msg) === '') {
                            myApp.showSuccess('Your request has been successfully submitted!');
                            location.href = "/admin/settings/vr-upload2";
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

        function add_detail() {

            if ($('#n_carrier').val().length < 1){
                alert("Please Insert Carrier");
                return;
            }

            if ($('#n_category').val().length < 1){
                alert("Please Insert Category");
                return;
            }

            if ($('#n_product_name').val().length < 1){
                alert("Please Insert Product Name");
                return;
            }

            myApp.showLoading();

            $.ajax({
                url: '/admin/settings/vr-upload2/add-detail',
                data: {
                    carrier: $('#n_carrier').val(),
                    category: $('#n_category').val(),
                    product_name: $('#n_product_name').val(),
                    memo1: $('#n_memo_1').val(),
                    memo2: $('#n_memo_2').val(),
                    comment: $('#n_comment').val()
                },
                cache: false,
                type: 'get',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        myApp.showSuccess('Your request has been successfully submitted!');
                        location.href = "/admin/settings/vr-upload2";
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

        function update(prod_id, status) {

            myApp.showLoading();

            $.ajax({
                url: '/admin/settings/vr-upload2/update',
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
                        // location.href = "/admin/settings/vr-uploa";
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

        function refresh_all() {
            window.location.href = '/admin/settings/vr-upload2';
        }

    </script>

    <h4>Admin Upload</h4>

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
                        <label class="col-md-4 control-label">Product.Name</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="product_name" value="{{ $product_name }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Memo1</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="memo1" value="{{ $memo1 }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Memo2</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="memo2" value="{{ $memo2 }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Comment</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="comment" value="{{ $comment }}"/>
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

                <div class="col-md-4 col-md-offset-8">
                    <div class="form-group">
                        <div class="col-md-12 text-right">
                            <button type="button" class="btn btn-info btn-sm" onclick="refresh_all()">Refresh All</button>
                            <button type="submit" class="btn btn-primary btn-sm" id="btn_search">Search</button>
                            @if(Auth::check() && in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']))
                                <button type="button" class="btn btn-success btn-sm" onclick="show_new()">Add New Admin Upload</button>
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
            <th style="text-align: center;">ID</th>
            <th style="text-align: center;">Carrier</th>
            <th style="text-align: center;">Category</th>
            <th style="text-align: center;">Product .Name</th>
            <th style="text-align: center;">URL</th>
            <th style="text-align: center;">Memo1</th>
            <th style="text-align: center;">Memo2</th>
            <th style="text-align: center;">Comment</th>
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
                    <td>{{ $o->category }}</td>
                    <td>{{ $o->product_name }}</td>

                    @if (!empty($o->url))
                        <td>
                            <div style="margin: 1px 1px -15px 1px;">
                            <a href="{{ $o->url }}" target="_blank">{{ $o->product_name }}</a>
                            </div>
                            <hr>
                            <div style="margin: -15px 1px 1px 1px;">{{ url('/') }}{{ $o->url }}</div>
                        </td>
                    @else
                        <td>{{ $o->product_name }}</td>
                    @endif

                    <td>{{ $o->memo1 }}</td>
                    <td>{{ $o->memo2 }}</td>
                    <td>{{ $o->comment }}</td>
                    <td>{{ $o->cdate }}</td>
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
        <div class="modal-dialog modal-md" role="document">
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
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Carrier</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="carrier" name="carrier"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Category</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="category" name="category"/>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row" style="border-bottom:solid 1px #dedede; margin-top:5px;">
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Product Name</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="product_name" name="product_name"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">URL</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="url" name="url"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Memo1</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="memo1" name="memo1"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Memo2</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="memo2" name="memo2"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Comment</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="comment" id="comment"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer" style="margin-right:15px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="update_detail()">Update</button>
                    <button type="button" class="btn btn-primary" onclick="copy_update()">Copy & Update</button>
                </div>
            </div>
        </div>
    </div>


    {{-- Add New Modal --}}
    <div class="modal" id="modal_add" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">ADD ADMIN UPLOAD</h4>
                </div>

                <div class="modal-body">
                    <form id="frm_upload" class="form-horizontal filter" method="post" style="padding:15px;" target="ifm_upload" enctype="multipart/form-data">

                        <input type="hidden" name="u_id" id="u_id" value="">
                        <div class="row" style="border-bottom:solid 1px #dedede; margin-top:-25px;">
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Carrier</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="n_carrier" name="n_carrier"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Category</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="n_category" name="n_category"/>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row" style="border-bottom:solid 1px #dedede; margin-top:5px;">
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Product Name</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="n_product_name" id="n_product_name"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Memo 1</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="n_memo_1" id="n_memo_1"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Memo 2</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="n_memo_2" id="n_memo_2"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Comment</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="n_comment" id="n_comment"/>
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
                <form id="image_upload_form" action="/admin/settings/vr-upload2/upload/image" class="form-horizontal filter" method="post" style="padding:15px;" target="ifm_upload" enctype="multipart/form-data">
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
@stop
