@extends('sub-agent.layout.default')

@section('content')

    <script type="text/javascript">

        var onload_func = window.onload;

        window.onload = function () {

            if (onload_func) {
                onload_func();
            }

            $("#sdate").datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $("#edate").datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $('.note-check-box').tooltip();

            // tooltip
            $('[data-toggle="tooltip"]').tooltip();



        };

        function search() {
            $('#excel').val('N');
            $('#frm_search').submit();
        }
    </script>

    <div class="parallax" data-background="/img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
        <div class="overlay white"></div>
        <div class="container">
            <div class="inner-head">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h4>Rebate Report</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li><a href="#">Reports</a></li>
                            <li class="active">Rebate</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="contain-wrapp padding-bot70">
        <div class="well filter" style="padding-bottom:5px;">
            <form id="frm_search" class="form-horizontal" method="post" action="/sub-agent/reports/rebate">
                {{ csrf_field() }}
                <input type="hidden" name="excel" id="excel"/>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Date</label>
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
                            <label class="col-md-4 control-label">Phone</label>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="phone" value="{{ old('phone', $phone) }}"/>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Tx.ID</label>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="trans_id" value="{{ old('trans_id', $trans_id) }}"/>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-md-offset-8 text-right">
                        <div class="form-group">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-primary btn-sm" id="btn_search" onclick="search()">
                                    Search
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>


        <table class="table table-bordered table-hover table-condensed filter">
            <thead>
            <tr>
                <th>Rebate.ID</th>
                <th>Type</th>
                <th>Tx.ID</th>
                <th>Phone</th>
                <th>Product</th>
                <th>Denom($)</th>
                <th>Rebate.Type</th>
                <th>Rebate.Month</th>
                <th>Rebate.Amt($)</th>
                <th>Date</th>
            </tr>
            </thead>
            <tbody>
            @if (isset($data) && count($data) > 0)
                @foreach ($data as $o)
                    <tr>
                        <td>{{ $o->id }}</td>
                        <td>{{ $o->type_name }}</td>
                        <td>{{ $o->trans_id }}</td>
                        <td>{{ $o->phone }}</td>
                        <td>{{ $o->product }}</td>
                        <td>${{ number_format($o->denom, 2) }}</td>
                        <td>{{ $o->rebate_type_name }}</td>
                        <td>{{ $o->rebate_month }}</td>
                        <td>${{ number_format($o->rebate_amt, 2) }}</td>
                        <td>{{ $o->cdate }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="30" class="text-center">No Record Found</td>
                </tr>
            @endif
            </tbody>
            <tfoot>
            <tr>
                <th colspan="7" class="text-right">Total {{ $data->total() }} record(s).</th>
                <th>${{ number_format($rebate_amt, 2) }}</th>
                <th colspan="20"></th>
            </tr>
            </tfoot>
        </table>

        <div class="text-right">
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
@stop
