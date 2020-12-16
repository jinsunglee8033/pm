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

            $( "#target_month" ).datetimepicker({
                format: 'YYYY-M',
                viewMode: 'months'
            });

            $("#target_month").click(function() {
                $('#target_month').data("DateTimePicker").format('YYYY-M');
                $('#target_month').data("DateTimePicker").viewMode('months');
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

        function show_pay() {
            $('#div_pay').modal();
        }

        function upload_temp_data() {
            if($('#sdate_m').val()==''){
                alert('Please Insert Start Date');
                return
            }
            if($('#edate_m').val()==''){
                alert('Please Insert End Date');
                return
            }
            myApp.showLoading();
            $('#frm_upload_temp').submit();
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

    <h4>Import Boom Raw Data</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post" action="/admin/reports/vendor/boom/commission">
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
                        <label class="col-md-4 control-label">Description</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="description" id="description" value="{{ old('description', $description) }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-8 col-md-offset-4 text-right">
                    <div class="form-group">
                        <button type="button" class="btn btn-primary btn-sm" id="btn_search" onclick="search()">Search</button>
                        @if (in_array(Auth::user()->account_type, ['L']) && in_array(Auth::user()->user_id, ['admin', 'thomas', 'system']))
                            <button type="button" onclick="show_upload()" class="btn btn-default btn-sm">Import Data</button>
                            <button type="button" onclick="show_pay()" class="btn btn-default btn-sm">Pay Boom Spiff</button>
                        @endif
                    </div>
                </div>

            </div>
        </form>
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

    <table class="table table-bordered table-hover table-condensed filter">
        <thead>
            <tr>
                <th rowspan="3">ID</th>
                <th rowspan="3">File.Name</th>
                <th rowspan="3">CustNum</th>
                <th rowspan="3">MDN</th>
                <th rowspan="3">Trasnaction.Date</th>
                <th rowspan="3">Orignal.Deealer</th>
                <th rowspan="3">Transaction.Deealer</th>
                <th rowspan="3">SKU</th>
                <th rowspan="3">Description</th>
                <th rowspan="3">ICCID</th>
                <th rowspan="3">Transaction.Cost</th>
                <th rowspan="3">Added.Amount</th>
                <th rowspan="3">Balance</th>
                <th rowspan="3">Transaction.Text</th>
                <th rowspan="3">Cdate</th>
                <th rowspan="3">Created.By</th>
            </tr>
        </thead>
        <tbody>
            @if (isset($data) && count($data) > 0)
                @foreach ($data as $o)
                    <tr>
                        <td>{{ $o->id }}</td>
                        <td>{{ $o->file_name }}</td>
                        <td>{{ $o->custnbr }}</td>
                        <td>{{ $o->mdn }}</td>
                        <td>{{ $o->transaction_date }}</td>
                        <td>{{ $o->orignal_dealer }}</td>
                        <td>{{ $o->transaction_dealer }}</td>
                        <td>{{ $o->sku }}</td>
                        <td>{{ $o->description }}</td>
                        <td>{{ $o->iccid }}</td>
                        <td>{{ $o->transaction_cost }}</td>
                        <td>{{ $o->added_amount }}</td>
                        <td>{{ $o->balance }}</td>
                        <td>{{ $o->transaction_text }}</td>
                        <td>{{ $o->cdate }}</td>
                        <td>{{ $o->created_by }}</td>
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
                    <form id="frm_upload" class="form-horizontal detail"
                          action="/admin/reports/vendor/boom/commission/upload"
                          method="post" target="ifm_upload"
                          enctype="multipart/form-data">
                        {!! csrf_field() !!}
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Select Raw File From Boom</label>
                                    <div class="col-sm-8">
                                        <input type="file" class="form-control" id="file" name="file"/>
                                    </div>
                                </div>
                                <p>.xlsx file format only, the first line should be column description, so will be ignored.</p>
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


    <div class="modal" id="div_pay" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">x</span>
                    </button>
                    <h4 class="modal-title" id="title">Pay Boom Spiff</h4>
                </div>
                <div class="modal-body" style="overflow-y: visible;">
                    <form id="frm_upload_temp" class="form-horizontal detail"
                          action="/admin/reports/vendor/boom/commission/upload_temp"
                          method="post"
                          enctype="multipart/form-data">

                        {{ csrf_field() }}

                        <div class="form-group">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label class="col-md-4 control-label">Target Month</label>
                                    <div class="col-md-8">
                                        <input type="text" style="width:100px; float:left;" class="form-control" id="target_month" name="target_month" value="" required/>
{{--                                        <span class="control-label" style="margin-left:5px; float:left;"> ~ </span>--}}
{{--                                        <input type="text" style="width:100px; margin-left: 5px; float:left;" class="form-control" id="edate_m" name="edate_m" value="" required/>--}}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div style="height: 200px;"></div>
                    </form>
                </div>
                <div class="modal-footer" style="margin-right:15px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button class="btn btn-primary" onclick="upload_temp_data()">Upload-Preview</button>
                </div>
            </div>
        </div>
    </div>


@stop
