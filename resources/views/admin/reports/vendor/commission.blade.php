@extends('admin.layout.default')

@section('content')

    <script type="text/javascript">
        var onload_events = window.onload;
        window.onload = function() {
            if (onload_events) {
                onload_events();
            }

            $( "#sdate" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });
            $( "#edate" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });
        }

        function close_modal(id) {
            $('#' + id).modal('hide');
        }

        function comm_search() {
            $('#excel').val('N');
            $('#frm_search').submit();
        }

        function excel_export() {
            $('#excel').val('Y');
            $('#frm_search').submit();
        }

        function show_upload() {
            $('#div_upload').modal();
        }

        function show_upload_bonus() {
            $('#div_upload_bonus').modal();
        }

        function show_upload_bonus_by_acct() {
            $('#div_upload_bonus_by_acct').modal();
        }

        function upload_data() {
            myApp.showLoading();
            $('#frm_upload').submit();
        }

        function upload_temp_data() {
            if($('#carrier').val() == ''){
                alert('Select Carrier!');
                return;
            }
            if($('#file').val() == ''){
                alert('Select File!');
                return;
            }
            myApp.showLoading();
            $('#frm_upload_temp').submit();
        }

        function upload_data_bonus() {
            myApp.showLoading();
            $('#frm_upload_bonus').submit();
        }

        function upload_temp_data_bonus() {

            if($('#carrier_b').val() == ''){
                alert('Select Carrier!');
                return;
            }
            if($('#file_b').val() == ''){
                alert('Select File!');
                return;
            }
            myApp.showLoading();
            $('#frm_upload_bonus_temp').submit();
        }

        function upload_data_bonus_by_acct() {
            myApp.showLoading();
            $('#frm_upload_bonus_by_acct').submit();
        }

        function upload_temp_data_bonus_acct() {

            if($('#product_a').val() == ''){
                alert('Select Product!');
                return;
            }
            if($('#file_a').val() == ''){
                alert('Select File!');
                return;
            }
            myApp.showLoading();
            $('#frm_upload_bonus_by_acct_temp').submit();
        }

        function show_batch_lookup() {
            $('#n_batch_esns').val('');
            $('#div_batch_lookup').modal();
        }

        function count_batch_ress() {
            var esns = $.trim($('#n_batch_ress').val()).split("\n");
            $('#n_batch_ress_qty').text(esns.length);
        }

        function batch_lookup() {
            var batch_ress = $('#n_batch_ress').val();
            batch_ress = $.trim(batch_ress);

            if (batch_ress === '') {
                myApp.showError('Please enter RESs to lookup');
                return;
            }

            $('#div_batch_lookup').modal('hide');
            $('#frm_batch_lookup').submit();
        }


    </script>

    <h4>Compensation Report</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post" action="/admin/reports/vendor/commission">
            {{ csrf_field() }}
            <input type="hidden" name="excel" id="excel"/>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Upload.Date</label>
                        <div class="col-md-8">
                            <input type="text" style="width:100px; float:left;" class="form-control" id="sdate" name="sdate" value="{{ old('sdate', $sdate) }}"/>
                            <span class="control-label" style="margin-left:5px; float:left;"> ~ </span>
                            <input type="text" style="width:100px; margin-left: 5px; float:left;" class="form-control" id="edate" name="edate" value="{{ old('edate', $edate) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">SIM</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="sim" id="sim" value="{{ old('sim', $sim) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Phone #</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="phone" id="phone" value="{{ old('phone', $phone) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">File.Name</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="file_name" id="file_name" value="{{ old('file_name', $file_name) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Account</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="account" id="account" value="{{ old('account', $account) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Status</label>
                        <div class="col-md-8">
                            <select name="status" class="form-control">
                                <option value="">Select</option>
                                <option value="S" {{ $status == 'S' ? 'selected' : '' }}>Paid</option>
                                <option value="Q" {{ $status == 'Q' ? 'selected' : '' }}>Unpaid</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Carrier</label>
                        <div class="col-md-8">
                            <select name="carrier" class="form-control">
                                <option value="" {{ old('carrier', $carrier) == '' ? 'selected' : '' }}>All</option>
                                @foreach ($carriers as $c)
                                    <option value="{{ $c->name }}" {{ old('carrier', $carrier) == $c->name ? 'selected' : ''}} >{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Product</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="product" id="product" value="{{ old('product', $product) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Product Name</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="product_name" id="product_name" value="{{ old('product_name', $product_name) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Denom</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="denom" id="denom" value="{{ old('denom', $denom) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Month</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="month" id="month" value="{{ old('month', $month) }}"/>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8 col-md-offset-4 text-right">
                    <div class="form-group">
                        <a class="btn btn-primary btn-xs" id="btn_search" onclick="comm_search()">Search</a>
                        <!-- <button type="button" class="btn btn-blue btn-sm" onclick="show_batch_lookup()">Batch Lookup</button> -->
                        @if (in_array(Auth::user()->account_type, ['L']) && in_array(Auth::user()->user_id, ['system', 'admin', 'thomas']))
                        <a type="button" onclick="show_upload()" class="btn btn-default btn-xs">Import Spiff</a>
                        <a type="button" onclick="show_upload_bonus()" class="btn btn-default btn-xs">Import Bonus</a>
                        <a type="button" class="btn btn-info btn-xs" onclick="excel_export()">Download</a>
                        <a target="_blank" class="btn btn-info btn-xs" href="/assets/template/TMP_CommissionUpload.xls">Template</a>
                        <a type="button" onclick="show_upload_bonus_by_acct()" class="btn btn-default btn-xs">Import Bonus by Acct</a>
                        <a target="_blank" class="btn btn-info btn-xs" href="/assets/template/TMP_CommissionUploadByAcct.xls">Template 2</a>
                        @endif
                    </div>
                </div>

            </div>
        </form>
    </div>

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <div class="row">
        @if ($errors->has('exception'))
            <div class="alert alert-danger alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                <strong>Error!</strong> {{ $errors->first('exception') }}
            </div>
        @endif
    </div>

    <table class="table table-bordered table-hover table-condensed filter">
        <thead>
            <tr>
                <th>ID</th>
                <th>File.Name</th>
                <th>Product</th>
                <th>Product.Name</th>
                <th>Phone</th>
                <th>SIM</th>
                <th>Month</th>
                <th>Spiff.M</th>
                <th>Status</th>
                <th>Denom</th>
                <th>Spiff</th>
                <th>Spiff.Paid</th>
                <th>Residual</th>
                <th>Residual.Paid</th>
                <th>Bonus</th>
                <th>Bonus.Paid</th>
                <th>Value</th>
                <th>Total</th>
                <th>Description</th>
                <th>Account</th>
                <th>Act.Tx.ID</th>
                <th>Date.Added</th>
                <th>Upload.Date</th>
                <th>Upload.By</th>
            </tr>
        </thead>
        <tbody>
            @if (isset($data) && count($data) > 0)
                @foreach ($data as $o)
                    <tr>
                        <td>{{ $o->id }}</td>
                        <td>{{ $o->file_name }}</td>
                        <td>{{ $o->product_id }}</td>
                        <td>{{ $o->product_name }}</td>
                        <td>{{ $o->phone }}</td>
                        <td>{{ $o->sim }}</td>
                        <td>{{ $o->month }}</td>
                        <td>{{ $o->spiff_month }}</td>
                        <td>{{ $o->status == 'S' ? 'Paid' : 'Unpaid'}}</td>
                        <td>{{ empty($o->denom) ? '' : '$' . number_format($o->denom, 2) }}</td>
                        <td>${{ number_format($o->spiff, 2) }}</td>
                        <td>${{ number_format($o->paid_spiff, 2) }}</td>
                        <td>${{ number_format($o->residual, 2) }}</td>
                        <td>${{ number_format($o->paid_residual, 2) }}</td>
                        <td>${{ number_format($o->bonus, 2) }}</td>
                        <td>${{ number_format($o->paid_bonus, 2) }}</td>
                        <td>${{ number_format($o->value, 2) }}</td>
                        <td>${{ number_format($o->total, 2) }}</td>
                        <td>{{ $o->notes }}</td>
                        @if (isset($o->account_id))
                            <td>
                                {!! Helper::get_parent_name_html($o->account_id) !!}
                                <span>{!! Helper::get_hierarchy_img($o->account_type) !!}</span>{{ $o->account_name }} ( {{ $o->account_id }} )
                            </td>
                        @else
                            <td></td>
                        @endif
                        <td>{{ $o->trans_id }}</td>
                        <td>{{ $o->date_added }}</td>
                        <td>{{ $o->cdate }}</td>
                        <td>{{ $o->created_by }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="23" class="text-center">No Record Found</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="row">
        <div class="col-md-2">
            Total {{ $data->total() }} records.
        </div>
        <div class="col-md-10  text-right">
            {{ $data->appends(Request::except('page'))->links() }}
        </div>
    </div>

    <div class="modal" id="div_upload" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Upload Data</h4>
                </div>
                <div class="modal-body">

                    <form id="frm_upload_temp" class="form-horizontal detail" action="/admin/reports/vendor/commission/upload_temp" method="post" enctype="multipart/form-data">
                        {!! csrf_field() !!}
                        <div class="row">
                            <div class="col-sm-12">

                                <div class="form-group">
                                    <label class="col-md-4 control-label">Carrier</label>
                                    <div class="col-md-8">
                                        <select class="form-control" name="carrier" id="carrier" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                            <option value="">All</option>
                                            @foreach ($carriers as $c)
                                                <option value="{{ $c->name }}" >{{ $c->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Select Commission File</label>
                                    <div class="col-sm-8">
                                        <input type="file" class="form-control" id="file" name="file"/>
                                    </div>
                                </div>
                                <div>
                                    <div class="col-sm-4"></div>
                                    <label class="col-sm-8" style="color: red;">not csv, xls only</label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="upload_temp_data()">Upload-Preview</button>
{{--                    <button type="button" class="btn btn-primary" onclick="upload_data()">Upload</button>--}}
                </div>
            </div>
        </div>
    </div>
    <div class="modal" id="div_upload_bonus" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Upload Data for Bonus</h4>
                </div>
                <div class="modal-body">

                    <form id="frm_upload_bonus_temp" class="form-horizontal detail" action="/admin/reports/vendor/commission/upload_bonus_temp" method="post" enctype="multipart/form-data">
                        {!! csrf_field() !!}
                        <div class="row">
                            <div class="col-sm-12">

                                <div class="form-group">
                                    <label class="col-md-4 control-label">Carrier</label>
                                    <div class="col-md-8">
                                        <select class="form-control" name="carrier_b" id="carrier_b" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                            <option value="">All</option>
                                            @foreach ($carriers as $c)
                                                <option value="{{ $c->name }}" >{{ $c->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Select Commission File</label>
                                    <div class="col-sm-8">
                                        <input type="file" class="form-control" id="file_b" name="file_b"/>
                                    </div>
                                </div>
                                <div>
                                    <div class="col-sm-4"></div>
                                    <label class="col-sm-8" style="color: red;">not csv, xls only</label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="upload_temp_data_bonus()">Upload-Preview</button>
{{--                    <button type="button" class="btn btn-primary" onclick="upload_data_bonus()">Upload</button>--}}
                </div>
            </div>
        </div>
    </div>
    <div class="modal" id="div_upload_bonus_by_acct" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Upload Data for Bonus by account</h4>
                </div>
                <div class="modal-body">

                    <form id="frm_upload_bonus_by_acct_temp" class="form-horizontal detail" action="/admin/reports/vendor/commission/upload_bonus_by_acct_temp" method="post" enctype="multipart/form-data">
                        {!! csrf_field() !!}
                        <div class="row">
                            <div class="col-sm-12">

                                <div class="form-group">
                                    <label class="col-md-4 control-label">Product</label>
                                    <div class="col-md-8">
                                        <select class="form-control" name="product_a" id="product_a" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                            <option value="" {{ old('product', $product) == '' ? 'selected' : '' }}>All</option>
                                            @foreach ($products as $p)
                                                <option value="{{ $p->id }}" {{ old('product', $product) == $p->id ? 'selected' : '' }}>{{ $p->carrier . ', ' . $p->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Select Commission File</label>
                                    <div class="col-sm-8">
                                        <input type="file" class="form-control" id="file_a" name="file_a"/>
                                    </div>
                                </div>
                                <div>
                                    <div class="col-sm-4"></div>
                                    <label class="col-sm-8" style="color: red;">not csv, xls only</label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="upload_temp_data_bonus_acct()">Upload-Preview</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal" id="div_upload_residual" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Upload Data for Residual</h4>
                </div>
                <div class="modal-body">

                    <form id="frm_upload" class="form-horizontal detail" action="/admin/reports/vendor/commission/upload" method="post" target="ifm_upload" enctype="multipart/form-data">
                        {!! csrf_field() !!}
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Select Commission File</label>
                                    <div class="col-sm-8">
                                        <input type="file" class="form-control" id="file" name="file"/>
                                    </div>
                                </div>
                                <div>
                                    <div class="col-sm-4"></div>
                                    <label class="col-sm-8" style="color: red;">not csv, xls only</label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="upload_data()">Upload</button>
                </div>
            </div>
        </div>
    </div>
    <div style="display:none">
        <iframe id="ifm_upload" name="ifm_upload"></iframe>
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
                    <form id="frm_batch_lookup" action="/admin/reports/vendor/commission/batch-lookup" class="form-horizontal filter"
                          method="post" style="padding:15px;">
                        {{ csrf_field() }}

                        <div class="form-group">
                            <div class="col-sm-12">
                                <label>
                                    Excel file will be downloaded after submit.
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-12">
                                <input type="radio" name="batch_res_type" value="M" checked> MDN
                                <input type="radio" name="batch_res_type" value="D"> Device.ID
                                <input type="radio" name="batch_res_type" value="I"> ICCID
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-12">
                                <textarea id="n_batch_ress" name="batch_ress" rows="10" style="width:100%; line-height: 150%;"
                                          onchange="count_batch_ress()"></textarea><br/>
                                Total <span id="n_batch_ress_qty">0</span> ESN(s).
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
@stop
