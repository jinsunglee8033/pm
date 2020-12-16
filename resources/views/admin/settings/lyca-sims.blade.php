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
            })
        };

        function show_detail() {
            $('#div_detail').modal();
        }

        function save_detail() {

            var file = $('#sim_csv_file').val();
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

    </script>


    <h4>Lyca SIM</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post" onsubmit="myApp.showLoading();">
            {{ csrf_field() }}
            <input type="hidden" id="id" name="id"/>
            <input type="hidden" name="excel" id="excel" value=""/>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">SIM #</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="sim" value="{{ $sim }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">AF.Code</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="afcode" value="{{ $afcode }}"/>
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
                        <label class="col-md-4 control-label">Comments</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="comments" value="{{ $comments }}"/>
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
                <div class="col-md-4 col-md-offset-4">
                    <div class="form-group">
                        <div class="col-md-12 text-right">
                            <button type="submit" class="btn btn-primary btn-sm" id="btn_search">Search</button>
                            @if(Auth::check() && in_array(Auth::user()->user_id, ['thomas', 'system', 'admin']))
                                <button type="button" class="btn btn-default btn-sm" onclick="show_detail()">Upload New SIM</button>
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
            <th>SIM #</th>
            <th>AF.Code</th>
            <th>Type</th>
            <th>Product</th>
            <th>Amount</th>
            <th>RTR.Month</th>
            <th>Status</th>
            <th>Comments</th>
            <th>Used.Tx.ID</th>
            <th>Used.Date</th>
            <th>Upload.Date</th>
        </tr>
        </thead>
        <tbody>
        @if (isset($data) && count($data) > 0)
            @foreach ($data as $o)
                <tr>
                    <td>{{ $o->sim_serial }}</td>
                    <td>{{ $o->afcode }}</td>
                    <td>{{ $o->type_name }}</td>
                    <td>{{ $o->product }}</td>
                    <td>${{ number_format($o->amount, 2) }}</td>
                    <td>{{ $o->rtr_month }}</td>
                    <td>{{ $o->status_name }}</td>
                    <td>{{ $o->comments }}</td>
                    <td>{{ $o->used_trans_id }}</td>
                    <td>{{ $o->used_date }}</td>
                    <td>{{ $o->upload_date }}</td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="20" class="text-center">No Record Found</td>
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
                    <h4 class="modal-title" id="title">Upload Lyca SIM</h4>
                </div>
                <div class="modal-body">

                    <form id="frm_upload" action="/admin/settings/lyca-sims/upload" class="form-horizontal filter" method="post" style="padding:15px;" target="ifm_upload" enctype="multipart/form-data">
                        {{ csrf_field() }}
                        <div class="form-group">
                            <label class="col-sm-4 control-label required">Select CSV File to Upload</label>
                            <div class="col-sm-8">
                                <input type="file" class="form-control" name="sim_csv_file" id="sim_csv_file"/>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="margin-right:15px;">
                    <a class="btn btn-warning" href="/upload_template/lyca_sim_upload_template.csv" target="_blank">Download Template</a>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="save_detail()">Upload</button>
                </div>
            </div>
        </div>
    </div>

    <div style="display:none">
        <iframe name="ifm_upload"></iframe>
    </div>
@stop
