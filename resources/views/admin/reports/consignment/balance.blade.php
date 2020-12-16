@extends('admin.layout.default')

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

        function show_paypal() {
            $('#div_paypal').modal();
        }
    </script>

    <h4>Consignment Balance Report</h4>

    <div class="contain-wrapp padding-bot70">
        <div class="well filter" style="padding-bottom:5px;">
            <form id="frm_search" class="form-horizontal" method="post" action="/admin/reports/consignment/balance">
                {{ csrf_field() }}
                <input type="hidden" name="excel" id="excel"/>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Type</label>
                            <div class="col-md-8">
                                <select class="form-control" name="type">
                                    <option value="">All</option>
                                    <option value="SIM" {{ old('type', $type) == 'SIM' ? 'selected' : '' }}>
                                        SIM
                                    </option>
                                    <option value="ESN" {{ old('type', $type) == 'ESN' ? 'selected' : '' }}>
                                        ESN
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="col-md-4 control-label">SIM/ESN</label>
                            <div class="col-md-8">
                                <input type="text" name="sim_esn" class="form-control" value="{{ old('sim_esn', $sim_esn) }}">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-right">
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
                    <th>Parent</th>
                    <th>Owner</th>
                    <th>Type</th>
                    <th>SIM/ESN</th>
                    <th>Charge.Amount</th>
                </tr>
            </thead>
            <tbody>
                @if (isset($data) && count($data) > 0)
                    @foreach ($data as $o)
                        <tr>
                            <td>
                                {!! Helper::get_parent_name_html($o->account_id) !!}
                            </td>
                            <td>
                                <span>{!! Helper::get_hierarchy_img($o->account_type) !!}</span>
                                <a style="display:inline">
                                    {{ $o->account_name . ' ( ' . $o->account_id . ' )' }}
                                </a>
                            </td>
                            <td>{{ $o->type }}</td>
                            <td>{{ $o->sim_esn }}</td>
                            <td>${{ number_format($o->charge_amt, 2) }}</td>
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
                    <th colspan="4" class="text-right">Total {{ $data->total() }} record(s).</th>
                    <th style="color:{{ $amt >= 0 ? 'black' : 'red' }}">${{ number_format($amt, 2) }}</th>
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
