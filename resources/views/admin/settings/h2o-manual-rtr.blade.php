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

            $(".tooltip").tooltip({
                html: true
            })
        }

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

    </script>


    <h4>H2O Manual RTR</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_news" class="form-horizontal" method="post" onsubmit="myApp.showLoading();">
            {{ csrf_field() }}
            <input type="hidden" id="id" name="id"/>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Recharge On</label>
                        <div class="col-md-8">
                            <input type="text" style="width:100px; float:left;" class="form-control" id="sdate" name="sdate" value="{{ old('sdate', $sdate) }}"/>
                            <span class="control-label" style="margin-left:5px; float:left;"> ~ </span>
                            <input type="text" style="width:100px; margin-left: 5px; float:left;" class="form-control" id="edate" name="edate" value="{{ old('edate', $edate) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Phone #</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="phone" value="{{ $phone }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Supplier.Name</label>
                        <div class="col-md-8">
                            <input type="text" name="supplier_name" class="form-control" value="{{ old('supplier_name', $supplier_name) }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Product</label>
                        <div class="col-md-8">
                            <select class="form-control" name="product_id">
                                <option value="">All</option>
                                @if (count($products) > 0)
                                    @foreach ($products as $o)
                                        <option value="{{ $o->id }}" {{ old('product_id', $product_id) == $o->id ? 'selected' : '' }}>{{ $o->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Amount</label>
                        <div class="col-md-8">
                            <select class="form-control" name="denom_id">
                                <option value="">All</option>
                                @if (count($denoms) > 0)
                                    @foreach ($denoms as $o)
                                        <option value="{{ $o->id }}" {{ old('denom_id', $denom_id) == $o->id ? 'selected' : '' }}>{{ $o->denom_name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Status</label>
                        <div class="col-md-8">
                            <select class="form-control" name="status">
                                <option value="">All</option>
                                <option value="N" {{ old('status', $status) == 'N' ? 'selected' : '' }}>Waiting</option>
                                <option value="S" {{ old('status', $status) == 'S' ? 'selected' : '' }}>Success</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">RTR.Q.Status</label>
                        <div class="col-md-8">
                            <select class="form-control" id="result" name="result" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                <option value="" {{ old('result', $result) == '' ? 'selected' : '' }}>All</option>
                                <option value="N" {{ old('result', $result) == 'N' ? 'selected' : '' }}>Waiting</option>
                                <option value="P" {{ old('result', $result) == 'P' ? 'selected' : '' }}>Processing</option>
                                <option value="S" {{ old('result', $result) == 'S' ? 'selected' : '' }}>Success</option>
                                <option value="F" {{ old('result', $result) == 'F' ? 'selected' : '' }}>Failed</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-md-offset-8 text-right">
                    <div class="form-group">
                        <div class="col-md-8 col-md-offset-4">
                            <button type="submit" class="btn btn-primary btn-sm" id="btn_search">Search</button>
                            @if(Auth::check() && in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']))
                                <button type="button" class="btn btn-default btn-sm" onclick="show_detail()">Upload New Manual RTR</button>
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
            <th>Phone</th>
            <th>Product</th>
            <th>Amount</th>
            <th>Recharge.On</th>
            <th>Supplier.Name</th>
            <th>Memo</th>
            <th>Fee</th>
            <th>Status</th>
            <th>Uploaded.At</th>
            <th>RTR.Q.ID</th>
            <th>RTR.Q.Status</th>
            <th>RTR.Q.Msg</th>
            <th>Ran.At</th>
        </tr>
        </thead>
        <tbody>
        @if (isset($data) && count($data) > 0)
            @foreach ($data as $o)
                <tr>
                    <td>{{ $o->phone }}</td>
                    <td>{{ $o->product }}</td>
                    <td>${{ number_format($o->amount, 2) }}</td>
                    <td>{{ $o->recharge_on }}</td>
                    <td>{{ $o->supplier_name }}</td>
                    <td>{{ $o->memo }}</td>
                    <td>{{ $o->fee }}</td>
                    <td>{{ $o->status_name }}</td>
                    <td>{{ $o->last_updated }}</td>
                    <td>{{ $o->rtr_id }}</td>
                    <td>{{ $o->rtr_status }}</td>
                    <td>{{ $o->rtr_message }}</td>
                    <td>{{ $o->ran_at }}</td>
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
                    <h4 class="modal-title" id="title">Upload H2O Manual RTR</h4>
                </div>
                <div class="modal-body">

                    <form id="frm_upload" action="/admin/settings/h2o-manual-rtr/upload" class="form-horizontal filter" method="post" style="padding:15px;" target="ifm_upload" enctype="multipart/form-data">
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
                    <a class="btn btn-warning" href="/upload_template/h2o_manual_rtr_template.csv" target="_blank">Download Template</a>
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
