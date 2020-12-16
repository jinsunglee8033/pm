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

    <h4>ReUP Compensation Report</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post" action="/admin/reports/vendor/reup/commission">
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
                <div class="col-md-4 col-md-offset-4 text-right">
                    <div class="form-group">
                        <button type="button" class="btn btn-primary btn-sm" id="btn_search" onclick="search()">Search</button>
                        <button type="button" class="btn btn-blue btn-sm" onclick="show_batch_lookup()">Batch Lookup</button>
                        @if (in_array(Auth::user()->account_type, ['L']) && in_array(Auth::user()->user_id, ['system', 'admin', 'thomas']))
                            <button type="button" onclick="show_upload()" class="btn btn-default btn-sm">Import Data</button>
                            <button type="button" class="btn btn-info btn-sm" onclick="excel_export()">Download</button>
                        @endif
                    </div>
                </div>

            </div>
        </form>
    </div>


    <table class="table table-bordered table-hover table-condensed filter">
        <thead>
            <tr>
                <th rowspan="3">ID</th>
                <th rowspan="3">File.Name</th>
                <th rowspan="3">MDN</th>
                <th rowspan="3">Device.ID</th>
                <th rowspan="3">ICCID</th>
                <th rowspan="3">Plan.Value($)</th>
                <th colspan="8">Dealer</th>
                <th colspan="4" rowspan="2">Master</th>
                <th rowspan="3">Description</th>
                <th rowspan="3">Account</th>
                <th rowspan="3">Act.Tx.ID</th>
                <th rowspan="3">Upload.Date</th>
                <th rowspan="3">Upload.By</th>
            </tr>
            <tr>
                <th colspan="4">On File</th>
                <th colspan="4" style="color:green;font-weight:bold;">Paid by PM Rule</th>
            </tr>
            <tr>
                <th>M2.Spiff($)</th>
                <th>M3.Spiff($)</th>
                <th>Residual($)</th>
                <th>Total($)</th>
                <th style="color:green;font-weight:bold;">M2.Spiff($)</th>
                <th style="color:green;font-weight:bold;">M3.Spiff($)</th>
                <th style="color:green;font-weight:bold;">Residual($)</th>
                <th style="color:green;font-weight:bold;">Total($)</th>
                <th>M2.Spiff($)</th>
                <th>M3.Spiff($)</th>
                <th>Residual($)</th>
                <th>Total($)</th>
            </tr>
        </thead>
        <tbody>
            @if (isset($data) && count($data) > 0)
                @foreach ($data as $o)
                    <tr>
                        <td>{{ $o->id }}</td>
                        <td>{{ $o->file_name }}</td>
                        <td>{{ $o->mdn }}</td>
                        <td>{{ $o->device_id }}</td>
                        <td>{{ $o->iccid }}</td>
                        <td>${{ number_format($o->plan_value, 2) }}</td>
                        <td>${{ number_format($o->dealer_month2_payout, 2) }}</td>
                        <td>${{ number_format($o->dealer_month3_payout, 2) }}</td>
                        <td>${{ number_format($o->dealer_residual_payout, 2) }}</td>
                        <td>${{ number_format($o->total_dealer_payout, 2) }}</td>
                        <td style="color:green;font-weight:bold;">${{ number_format($o->dealer_m2_paid, 2) }}</td>
                        <td style="color:green;font-weight:bold;">${{ number_format($o->dealer_m3_paid, 2) }}</td>
                        <td style="color:green;font-weight:bold;">${{ number_format($o->dealer_residual_paid, 2) }}</td>
                        <td style="color:green;font-weight:bold;">${{ number_format($o->total_dealer_paid, 2) }}</td>
                        <td>${{ number_format($o->master_month2_payout, 2) }}</td>
                        <td>${{ number_format($o->master_month3_payout, 2) }}</td>
                        <td>${{ number_format($o->master_residual_payout, 2) }}</td>
                        <td>${{ number_format($o->total_master_payout, 2) }}</td>
                        <td>{{ $o->description }}</td>
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

                    <form id="frm_upload" class="form-horizontal detail" action="/admin/reports/vendor/reup/commission/upload" method="post" target="ifm_upload" enctype="multipart/form-data">
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
                    <form id="frm_batch_lookup" action="/admin/reports/vendor/reup/commission/batch-lookup" class="form-horizontal filter"
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
