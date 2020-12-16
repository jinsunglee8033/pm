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

    <h4>Verizon Activation Report</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post" action="/admin/reports/verizon/activation">
            {{ csrf_field() }}
            <input type="hidden" name="excel" id="excel"/>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Act.Date</label>
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
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Year</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="year" id="year" value="{{ old('year', $year) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Month</label>
                        <div class="col-md-8">
                            <select id="month" name="month" class="form-control">
                                <option value="">All</option>
                                <option value="1" {{ old('month', $month) == '1' ? 'selected' : '' }}>1</option>
                                <option value="2" {{ old('month', $month) == '2' ? 'selected' : '' }}>2</option>
                                <option value="3" {{ old('month', $month) == '3' ? 'selected' : '' }}>3</option>
                                <option value="4" {{ old('month', $month) == '4' ? 'selected' : '' }}>4</option>
                                <option value="5" {{ old('month', $month) == '5' ? 'selected' : '' }}>5</option>
                                <option value="6" {{ old('month', $month) == '6' ? 'selected' : '' }}>6</option>
                                <option value="7" {{ old('month', $month) == '7' ? 'selected' : '' }}>7</option>
                                <option value="8" {{ old('month', $month) == '8' ? 'selected' : '' }}>8</option>
                                <option value="9" {{ old('month', $month) == '9' ? 'selected' : '' }}>9</option>
                                <option value="10" {{ old('month', $month) == '10' ? 'selected' : '' }}>10</option>
                                <option value="11" {{ old('month', $month) == '11' ? 'selected' : '' }}>11</option>
                                <option value="12" {{ old('month', $month) == '12' ? 'selected' : '' }}>12</option>
                            </select>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>


    <table class="table table-bordered table-hover table-condensed filter">
        <thead>
        <tr>
            @if (in_array(Auth::user()->account_type, ['L']))
            <th>Outlet.ID</th>
            @endif
            <th>Year</th>
            <th>Month</th>
            <th>Phone</th>
            <th>Device.Category</th>
            @if (in_array(Auth::user()->account_type, ['L']))
            <th>Device.ID</th>
            <th>Account.Number</th>
            @endif
            <th>Price.Plan</th>
            <th>Customer.Name</th>
            <th>Act.Date</th>
            <th>Access.Charge</th>
            <th>Model</th>
        </tr>
        </thead>
        <tbody>
        @if (isset($data) && count($data) > 0)
            @foreach ($data as $o)
                <tr>
                    @if (in_array(Auth::user()->account_type, ['L']))
                    <td>{{ $o->outlet_id }}</td>
                    @endif
                    <td>{{ $o->year }}</td>
                    <td>{{ $o->month }}</td>
                    <td>{{ $o->mobile_id }}</td>
                    <td>{{ $o->device_category }}</td>
                    @if (in_array(Auth::user()->account_type, ['L']))
                    <td>{{ $o->device_id }}</td>
                    <td>{{ $o->account_number }}</td>
                    @endif
                    <td>{{ $o->price_plan }}</td>
                    <td>{{ $o->customer_name }}</td>
                    <td>{{ $o->activation_date }}</td>
                    <td>{{ $o->access_charge }}</td>
                    <td>{{ $o->model }}</td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="19" class="text-center">No Record Found</td>
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

                    <form id="frm_upload" class="form-horizontal detail" action="/admin/reports/verizon/activation/upload" method="post" target="ifm_upload" enctype="multipart/form-data">
                        {!! csrf_field() !!}
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Select File From Verizon</label>
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
