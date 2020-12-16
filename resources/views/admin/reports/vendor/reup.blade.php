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



    </script>

    <h4>ReUP RTR Report</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post" action="/admin/reports/vendor/reup">
            {{ csrf_field() }}
            <input type="hidden" name="excel" id="excel"/>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">RTR.Date</label>
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
                            <input type="text" class="form-control" name="phone" id="phone" value="{{ old('phone', $phone) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-sm" id="btn_search">Search</button>
                        @if (in_array(Auth::user()->account_type, ['L']))
                            <button type="button" onclick="show_upload()" class="btn btn-default btn-sm">Import Data</button>
                        @endif
                    </div>
                </div>

            </div>
        </form>
    </div>


    <table class="table table-bordered table-hover table-condensed filter">
        <thead>
        <tr>
            <th>ROK.ID</th>
            <th>Dealer</th>
            <th>MDN</th>
            <th>Week.Ending</th>
            <th>Renewal.Date</th>
            <th>Plan.Value</th>
            <th>M2.Bonus</th>
            <th>M2.Renewal</th>
            <th>M3.Bonus</th>
            <th>M3.Renewal</th>
            <th>Residuals</th>
            <th>Residual.Payout</th>
            <th>Total.Payout</th>
            <th>Account</th>
            <th>Spiff.ID</th>
            <th>Residual.ID</th>
            <th>Upload.Date</th>
            <th>Upload.By</th>
        </tr>
        </thead>
        <tbody>
        @if (isset($data) && count($data) > 0)
            @foreach ($data as $o)
                <tr>
                    <td>{{ $o->rok_id }}</td>
                    <td>{{ $o->dealer_name }}</td>
                    <td>{{ $o->mdn }}</td>
                    <td>{{ $o->week_ending }}</td>
                    <td>{{ $o->renewal_date }}</td>
                    <td>${{ number_format($o->plan_value, 2) }}</td>
                    <td>${{ number_format($o->total_m2_bonus, 2) }}</td>
                    <td>{{ $o->total_m2_renewal }}</td>
                    <td>${{ number_format($o->total_m3_bonus, 2) }}</td>
                    <td>{{ $o->total_m3_renewal }}</td>
                    <td>{{ $o->total_residuals }}</td>
                    <td>${{ number_format($o->total_residual_payout, 2) }}</td>
                    <td>${{ number_format($o->total_dealer_payout, 2) }}</td>
                    @if (isset($o->account_id))
                        <td>
                            {!! Helper::get_parent_name_html($o->account_id) !!}
                            <span>{!! Helper::get_hierarchy_img($o->account_type) !!}</span>{{ $o->account_name }} ( {{ $o->account_id }} )
                        </td>
                    @else
                        <td></td>
                    @endif
                    <td>{{ $o->spiff_id }}</td>
                    <td>{{ $o->residual_id }}</td>
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

                    <form id="frm_upload" class="form-horizontal detail" action="/admin/reports/vendor/reup/upload" method="post" target="ifm_upload" enctype="multipart/form-data">
                        {!! csrf_field() !!}
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Select File From ReUP</label>
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
@stop
