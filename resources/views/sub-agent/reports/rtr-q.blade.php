@extends('sub-agent.layout.default')

@section('content')

    <script type="text/javascript">
        window.onload = function() {
            $( "#sdate" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });
            $( "#edate" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $('.note-check-box').tooltip();

            // tooltip
            $('[data-toggle="tooltip"]').tooltip();

        };

        function excel_export() {
            $('#excel').val('Y');
            $('#frm_search').submit();
        }

        function refresh_all() {
            $("form#frm_search input[type=text]").val('');
            $("form#frm_search select").val('');
        }


    </script>

    <div class="parallax" data-background="/img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
        <div class="overlay white"></div>
        <div class="container">
            <div class="inner-head">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h4>RTR Queue Report</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li><a href="#">Reports</a></li>
                            <li class="active">RTR Queue</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="contain-wrapp">
    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post" action="/sub-agent/reports/rtr-q">
            {{ csrf_field() }}
            <input type="hidden" name="excel" id="excel"/>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Date</label>
                        <div class="col-md-8">
                            <input type="text" style="width:100px; float:left;" class="form-control" id="sdate" name="sdate" value="{{ old('sdate', $sdate) }}"/>
                            <span class="control-label" style="margin-left:5px; float:left;"> ~ </span>
                            <input type="text" style="width:100px; margin-left: 5px; float:left;" class="form-control" id="edate" name="edate" value="{{ old('edate', $edate) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Carrier</label>
                        <div class="col-md-8">
                            <select class="form-control" name="carrier" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                <option value="" {{ old('carrier', $carrier) == '' ? 'selected' : '' }}>All</option>
                                <option value="Verizon" {{ old('carrier', $carrier) == 'Verizon' ? 'selected' : '' }}>Verizon</option>
                                <option value="H2O" {{ old('carrier', $carrier) == 'H2O' ? 'selected' : '' }}>H2O</option>
                                <option value="Lyca" {{ old('carrier', $carrier) == 'Lyca' ? 'selected' : '' }}>Lyca</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Result</label>
                        <div class="col-md-8">
                            <select class="form-control" name="result" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                <option value="" {{ old('status', $result) == '' ? 'selected' : '' }}>All</option>
                                <option value="N" {{ old('status', $result) == 'N' ? 'selected' : '' }}>Waiting</option>
                                <option value="P" {{ old('status', $result) == 'P' ? 'selected' : '' }}>Processing</option>
                                <option value="S" {{ old('status', $result) == 'S' ? 'selected' : '' }}>Success</option>
                                <option value="F" {{ old('status', $result) == 'F' ? 'selected' : '' }}>Failed</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Phone</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="phone" value="{{ old('phone', $phone) }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">SIM Type</label>
                        <div class="col-md-8">
                            <select name="sim_type" class="form-control">
                                <option value="">Show All</option>
                                <option value="B" {{ old('sim_type', $sim_type) == 'B' ? 'selected' : '' }}>Bundle</option>
                                <option value="P" {{ old('sim_type', $sim_type) == 'P' ? 'selected' : '' }}>Quick-Spiff</option>
                                <option value="R" {{ old('sim_type', $sim_type) == 'R' ? 'selected' : '' }}>Regular</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">SIM</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="sim" value="{{ old('sim', $sim) }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">RTR Month</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="seq" value="{{ old('seq', $seq) }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 col-md-offset-4 text-right">
                    <div class="form-group">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary btn-sm" id="btn_search">Search</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>


    <table class="table table-bordered table-hover table-condensed filter">
        <thead>
        <tr>
            <th>Q.ID</th>
            <th>Act.Tx.ID</th>
            <th>Carrier</th>
            <th>Phone</th>
            <th>SIM</th>
            <th>SIM.Type</th>
            <th>Product</th>
            <th>Amt($)</th>
            <th>Scheduled.On</th>
            <th>Seq</th>
            <th>Result</th>
            <th>Result.Msg</th>
            <th>Ran.At</th>
        </tr>
        </thead>
        <tbody>
        @if (isset($records) && count($records) > 0)
            @foreach ($records as $o)
                <tr>
                    <td>{{ $o->id }}</td>
                    <td>{{ $o->trans_id }}</td>
                    <td>{{ $o->category }}</td>
                    <td>{{ $o->phone }}</td>
                    <td>{{ $o->sim }}</td>
                    <td>{{ $o->sim_type }}</td>
                    <td>{{ $o->product }}</td>
                    <td>{{ $o->denom }}</td>
                    <td>{{ $o->run_at }}</td>
                    <td>{{ $o->seq }}</td>
                    <td>
                        <span style="display:inline;">{{ $o->result_name }}</span>
                        @if ($o->result == 'F')
                            <form method="post" action="/admin/reports/rtr-q/retry" style="padding-left:10px; display:inline;">
                                {{ csrf_field() }}
                                <input type="hidden" name="id" value="{{$o->id}}"/>
                                <button type="submit" class="btn btn-sm btn-primary">Retry</button>
                            </form>
                        @endif
                    </td>
                    <td style="max-width:200px;">{{ $o->result_msg }}</td>
                    <td>{{ $o->result_date }}</td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="19" class="text-center">No Record Found</td>
            </tr>
        @endif
        </tbody>
    </table>

    <div class="text-right">
        {{ $records->appends(Request::except('page'))->links() }}
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

    </div>
@stop
