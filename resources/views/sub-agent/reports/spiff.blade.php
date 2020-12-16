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

        function load_type(){
            if($('#spiff_month').val() == 1){
                $("#spiff_type").val("Regular_Spiff").attr("selected","selected");
            }
        }
    </script>

    <div class="parallax" data-background="/img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
        <div class="overlay white"></div>
        <div class="container">
            <div class="inner-head">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h4>Spiff Report</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li><a href="#">Reports</a></li>
                            <li class="active">Paid Spiff</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="contain-wrapp padding-bot70">
        <div class="well filter" style="padding-bottom:5px;">
            <form id="frm_search" class="form-horizontal" method="post" action="/sub-agent/reports/spiff">
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
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Spiff Month</label>
                            <div class="col-md-8">
                                <select name="spiff_month" id="spiff_month" class="form-control" onchange="load_type()">
                                    <option value="" {{ old('month', $spiff_month) == '' ? 'selected' : '' }}>Select</option>
                                    <option value="1" {{ old('month', $spiff_month) == '1' ? 'selected' : '' }}>1st Month</option>
                                    <option value="2" {{ old('month', $spiff_month) == '2' ? 'selected' : '' }}>2nd Month</option>
                                    <option value="3" {{ old('month', $spiff_month) == '3' ? 'selected' : '' }}>3rd Month</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Spiff Type</label>
                            <div class="col-md-8">
                                <select name="spiff_type" id="spiff_type" class="form-control">
                                    <option value="" {{ old('spiff_type', $spiff_type) == '' ? 'selected' : '' }}>Select</option>
                                    <option value="Regular_Spiff" {{ old('spiff_type', $spiff_type) == 'Regular_Spiff' ? 'selected' : '' }}>Regular Spiff</option>
                                    <option value="Special_Spiff" {{ old('spiff_type', $spiff_type) == 'Special_Spiff' ? 'selected' : '' }}>Special Spiff</option>
                                    <option value="Bonus" {{ old('spiff_type', $spiff_type) == 'Bonus' ? 'selected' : '' }}>Bonus</option>
                                </select>
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
                <th>Spiff.ID</th>
                <th>Type</th>
                <th>Spiff Type</th>
                <th>Tx.ID</th>
                <th>Phone</th>
                <th>Product</th>
                <th>SIM.Type</th>
                <th>Denom($)</th>
                <th>Spiff.Month</th>
                <th>Spiff.Amt($)</th>
                <th>Date</th>
                <th>Note</th>
            </tr>
            </thead>
            <tbody>
            @if (isset($data) && count($data) > 0)
                @foreach ($data as $o)
                    <tr>
                        <td>{{ $o->id }}</td>
                        <td>{{ $o->type_name }}</td>
                        <td>{{ $o->spiff_type }}</td>
                        <td>{{ $o->trans_id }}</td>
                        <td>{{ $o->phone }}</td>
                        <td>{{ $o->product }}</td>
                        <td>{{ $o->sim_type }}</td>
                        <td>${{ number_format($o->denom, 2) }}</td>
                        @if($o->spiff_type == 'Regular Spiff')
                            <td>{{ $o->spiff_month }}</td>
                        @else
                            <td></td>
                        @endif
                        <td>${{ number_format($o->spiff_amt, 2) }}</td>
                        <td>{{ $o->cdate }}</td>
                        <td>{{ $o->note }}</td>
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
{{--                <th colspan="9" class="text-right">Total {{ $data->total() }} record(s).</th>--}}
{{--                <th>${{ number_format($spiff_amt, 2) }}</th>--}}
                <th colspan="20"></th>
            </tr>
            </tfoot>
        </table>

        <div class="text-right">
{{--            {{ $data->appends(Request::except('page'))->links() }}--}}
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
