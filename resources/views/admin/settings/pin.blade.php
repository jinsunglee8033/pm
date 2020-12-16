@extends('admin.layout.default')

@section('content')

    <script type="text/javascript">
        var onload_events = window.onload;
        window.onload = function () {

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
            })
        };

        function show_batch_lookup() {
            $('#n_batch_pins').val('');
            $('#div_batch_lookup').modal();
        }

        function show_pin_assign() {
            $('#n_clear_assign').attr('checked', false);
            $('#n_c_store_id').attr('disabled', false);
            $('#n_c_store_id').val('');
            $('#n_pins').val('');

            $('#div_pin_assign').modal();
        }

        function save_pin_assign() {
            myApp.showLoading();
            $.ajax({
                url: '/admin/settings/pin/assign',
                data: {
                    _token: '{!! csrf_token() !!}',
                    c_store_id: $('#n_c_store_id').val(),
                    clear: $('#n_clear_assign').is(':checked') ? 'Y' : 'N',
                    pins: $('#n_pins').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function (res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        myApp.showSuccess('Your request has been successfully processed!', function () {
                            $('#c_store_id').val($('#n_c_store_id').val());
                            $('#frm_search').submit();
                        });
                    } else {
                        myApp.showError(res.msg);
                    }
                }
            });
        }

        function show_detail() {
            $('#div_detail').modal();
        }

        function save_detail() {

            var file = $('#pin_csv_file').val();
            if (file == '') {
                myApp.showError('Please select file to upload');
                return;
            }

            myApp.showLoading();
            $('#frm_upload').submit();
        }

        function close_modal() {
            $('#div_detail').modal('hide');
            myApp.showSuccess('Your request has been processed successfully!', function () {
                $('#btn_search').click();
            });
        }

        function excel_export() {
            $('#excel').val('Y');
            $('#frm_search').submit();
            myApp.hideLoading();
            $('#excel').val('');
        }

        function change_assign_status() {
            var clear = $('#n_clear_assign').is(':checked');
            if (clear) {
                $('#n_c_store_id').attr('disabled', true);
            } else {
                $('#n_c_store_id').attr('disabled', false);
            }
        }

        function count_pins() {
            var pins = $.trim($('#n_pins').val()).split("\n");
            $('#n_pins_qty').text(pins.length);
        }

        function count_batch_pins() {
            var pins = $.trim($('#n_batch_pins').val()).split("\n");
            $('#n_batch_pins_qty').text(pins.length);
        }

        function batch_lookup() {
            var batch_pins = $('#n_batch_pins').val();
            batch_pins = $.trim(batch_pins);

            if (batch_pins === '') {
                myApp.showError('Please enter PINs to lookup');
                return;
            }

            var before = $('#before').val();
            var after = $('#after').val();

            if (before == after){
                myApp.showError('Please enter correct status change');
                return;
            }

            if (before == '' || after == ''){
                myApp.showError('Please chose status');
                return;
            }
            //myApp.showLoading();

            $('#frm_batch_lookup').submit();
            $('#div_batch_lookup').modal('hide');
        }

        function refresh_all() {
            window.location.href = '/admin/settings/pin';
        }

    </script>

    <h4>PIN</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post" action="/admin/settings/pin">
            {{ csrf_field() }}
            <input type="hidden" name="excel" id="excel" value=""/>
            <input type="hidden" id="id" name="id"/>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">PIN</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="pin" value="{{ $pin }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Serial</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="serial" value="{{ $serial }}"/>
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
                                <option value="P" {{ $status == 'P' ? 'selected' : '' }}>Processing</option>
                                <option value="S" {{ $status == 'S' ? 'selected' : '' }}>Sold</option>
                                <option value="V" {{ $status == 'V' ? 'selected' : '' }}>Voided</option>
                                <option value="H" {{ $status == 'H' ? 'selected' : '' }}>Holding</option>
                                <option value="D" {{ $status == 'D' ? 'selected' : '' }}>Deleted</option>
                            </select>
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
                                    <option value="{{ $p->id }}" {{ old('product', $product) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

{{--                <div class="col-md-4">--}}
{{--                    <div class="form-group">--}}
{{--                        <label class="col-md-4 control-label">Sub.Carrier</label>--}}
{{--                        <div class="col-md-8">--}}
{{--                            <select class="form-control" name="sub_carrier"--}}
{{--                                    data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>--}}
{{--                                <option value="" {{ old('sub_carrier', $sub_carrier) == '' ? 'selected' : '' }}>All--}}
{{--                                </option>--}}
{{--                                @foreach ($sub_carriers as $o)--}}
{{--                                    <option value="{{ $o->sub_carrier }}" {{ old('sub_carrier', $sub_carrier) == $o->sub_carrier ? 'selected' : '' }}>{{ $o->sub_carrier }}</option>--}}
{{--                                @endforeach--}}
{{--                            </select>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}

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

                <div class="col-md-4 col-md-offset-8">
                    <div class="form-group">
                        <div class="col-md-12 text-right">
                            <button type="button" class="btn btn-info btn-sm" onclick="refresh_all()">Refresh All</button>
                            <button type="submit" class="btn btn-primary btn-sm" id="btn_search">Search</button>
                            <button type="button" class="btn btn-blue btn-sm" onclick="show_batch_lookup()">Status Update</button>
                            @if(Auth::check() && in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']))
                                <button type="button" class="btn btn-default btn-sm" onclick="show_detail()">Upload PIN</button>
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
                <th>ID #</th>
                <th>PIN</th>
                <th>Serial</th>
                <th>Product</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Supplier</th>
                <th>Comments</th>
                <th>Used.Tx.ID</th>
                <th>Used.Date</th>
                <th>Upload.Date</th>
            </tr>
        </thead>
        <tbody>
            @if ($data->total() > 0)
                @foreach ($data as $o)
                    <tr>
                        <td>{{ $o->id }}</td>
                        <td>{{ $o->pin }}</td>
                        <td>{{ $o->serial }}</td>
                        <td>{{ $o->product }}</td>
                        <td>{{ $o->amount }}</td>
                        <td>{{ $o->status }}</td>
                        <td>{{ $o->supplier }}</td>
                        <td>{{ $o->comments }}</td>
                        <td>{{ $o->used_trans_id }}</td>
                        <td>{{ $o->used_date }}</td>
                        <td>{{ $o->upload_date }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="50" class="text-center">No Record Found</td>
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
                    <h4 class="modal-title" id="title">Upload PIN</h4>
                </div>
                <div class="modal-body">

                    <form id="frm_upload" action="/admin/settings/pin/upload" class="form-horizontal filter"
                          method="post" style="padding:15px;" target="ifm_upload" enctype="multipart/form-data">
                        {{ csrf_field() }}

                        <div class="form-group">
                            <label class="col-md-4 control-label">Product</label>
                            <div class="col-md-8">
                                <select class="form-control" name="product"
                                        data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                    <option value="" {{ old('product', $product) == '' ? 'selected' : '' }}>All
                                    </option>
                                    @foreach ($products as $p)
                                        <option value="{{ $p->id }}" {{ old('product', $product) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-4 control-label required">Select CSV File to Upload</label>
                            <div class="col-sm-8">
                                <input type="file" class="form-control" name="pin_csv_file" id="pin_csv_file"/>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="margin-right:15px;">
                    <a class="btn btn-warning" href="/upload_template/pin_upload_template.xlsx" target="_blank">Download
                        Template</a>
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
                    <h4 class="modal-title" id="title">Status Update for PINs</h4>
                </div>
                <div class="modal-body">
                    <form id="frm_batch_lookup" action="/admin/settings/pin/batch-lookup"
                          class="form-horizontal filter" target="ifm_upload" method="post" style="padding:15px;">
                        {{ csrf_field() }}

                        <div class="form-group">
                            <div class="col-sm-2">
                                <label>
                                    Before
                                </label>
                            </div>
                            <div class="col-sm-4">
                                <select class="form-control" name="before" id="before">
                                    <option value="">-</option>
                                    <option value="A">Active</option>
                                    <option value="V">Void</option>
                                    <option value="H">Holding</option>
                                </select>
                            </div>
                            <div class="col-sm-2">
                                <label>
                                    After
                                </label>
                            </div>
                            <div class="col-sm-4">
                                <select class="form-control" name="after" id="after">
                                    <option value="">-</option>
                                    <option value="A">Active</option>
                                    <option value="H">Holding</option>
                                    <option value="R">Return</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-12">
                                <label>
                                    PINs
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-12">
                                <textarea id="n_batch_pins" name="batch_pins" rows="10" style="width:100%; line-height: 150%;"
                                          onchange="count_batch_pins()"></textarea><br/>
                                Total <span id="n_batch_pins_qty">0</span> PIN(s).
                            </div>
                        </div>

                        <div class="form-group">

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
        <iframe name="ifm_upload"></iframe>
    </div>
@stop
