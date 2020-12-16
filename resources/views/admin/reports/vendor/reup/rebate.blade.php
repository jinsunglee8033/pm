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

        function search() {
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

        function upload_data() {
            myApp.showLoading();
            $('#frm_upload').submit();
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

    <h4>ReUP Rebate Report</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post" action="/admin/reports/vendor/reup/rebate">
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
                        <label class="col-md-4 control-label">Device.ID</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="device_id" id="device_id" value="{{ old('device_id', $device_id) }}"/>
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
                <div class="col-md-4 col-md-offset-8 text-right">
                    <div class="form-group">
                        <button type="button" onclick="search()" class="btn btn-primary btn-sm" id="btn_search">Search</button>
                        <button type="button" class="btn btn-blue btn-sm" onclick="show_batch_lookup()">Batch Lookup</button>
                        @if (in_array(Auth::user()->account_type, ['L']) && in_array(Auth::user()->user_id, ['system', 'admin', 'thomas']))
                            <button type="button" onclick="show_upload()" class="btn btn-default btn-sm">Import Data</button>
                            <button type="button" onclick="excel_export()" class="btn btn-info btn-sm">Download</button>
                        @endif
                    </div>
                </div>

            </div>
        </form>
    </div>


    <table class="table table-bordered table-hover table-condensed filter">
        <thead>
            <tr>
                <th rowspan="2">ID</th>
                <th rowspan="2">File.Name</th>
                <th rowspan="2">TSP</th>
                <th rowspan="2">MA.ID</th>
                <th rowspan="2">MA.Name</th>
                <th rowspan="2">Sale.Date</th>
                <th rowspan="2">Retailer.ID</th>
                <th rowspan="2">Store.Name</th>
                <th rowspan="2">Device.ID</th>
                <th colspan="2">Payout</th>
                <th rowspan="2">Account</th>
                <th rowspan="2">Act.Trans.ID</th>
                <th rowspan="2">Upload.Date</th>
                <th rowspan="2">Upload.By</th>
            </tr>
            <tr>
                <th>On File</th>
                <th style="color:green;font-weight:bold;">Paid by PM Rule</th>
            </tr>
        </thead>
        <tbody>
            @if (isset($data) && count($data) > 0)
                @foreach ($data as $o)
                    <tr>
                        <td>{{ $o->id }}</td>
                        <td>{{ $o->file_name }}</td>
                        <td>{{ $o->tsp }}</td>
                        <td>{{ $o->ma_id }}</td>
                        <td>{{ $o->ma_name }}</td>
                        <td>{{ $o->sale_date }}</td>
                        <td>{{ $o->retailer_id }}</td>
                        <td>{{ $o->store_name }}</td>
                        <td>{{ $o->device_id }}</td>
                        <td>${{ number_format($o->payout, 2) }}</td>
                        <td style="color:green;font-weight:bold;">${{ number_format($o->paid, 2) }}</td>
                        @if (isset($o->account_id))
                            <td>
                                {!! Helper::get_parent_name_html($o->account_id) !!}
                                <span>{!! Helper::get_hierarchy_img($o->account_type) !!}</span>{{ $o->account_name }} ( {{ $o->account_id }} )
                            </td>
                        @else
                            <td></td>
                        @endif
                        <td>{{ $o->act_trans_id }}</td>
                        <td>{{ $o->upload_date }}</td>
                        <td>{{ $o->upload_by }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="50" class="text-center">No Record Found</td>
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

    <div class="row">
        @if ($errors->has('exception'))
            <div class="alert alert-danger alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                <strong>Error!</strong> {{ $errors->first('exception') }}
            </div>
        @endif
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

                    <form id="frm_upload" class="form-horizontal detail" action="/admin/reports/vendor/reup/rebate/upload" method="post" target="ifm_upload" enctype="multipart/form-data">
                        {!! csrf_field() !!}
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Select Commission File From ReUP</label>
                                    <div class="col-sm-8">
                                        <input type="file" class="form-control" id="file" name="file"/>
                                    </div>
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
                    <form id="frm_batch_lookup" action="/admin/reports/vendor/reup/rebate/batch-lookup" class="form-horizontal filter"
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
