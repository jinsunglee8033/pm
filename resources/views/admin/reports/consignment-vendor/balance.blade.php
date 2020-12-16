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

    <h4>Consignment Vendor Balance Report</h4>

    <div class="contain-wrapp padding-bot70">
        <div class="well filter" style="padding-bottom:5px;">
            <form id="frm_search" class="form-horizontal" method="post" action="/admin/reports/consignment-vendor/balance">
                {{ csrf_field() }}
                <input type="hidden" name="excel" id="excel"/>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Type</label>
                            <div class="col-md-8">
                                <select class="form-control" name="type">
                                    <option value="">All</option>
                                    <option value="C" {{ old('type', $type) == 'C' ? 'selected' : '' }}>
                                        Add
                                    </option>
                                    <option value="D" {{ old('type', $type) == 'D' ? 'selected' : '' }}>
                                        Reduce
                                    </option>
                                </select>
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
                    <th>Date & Time</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Comments</th>
                </tr>
            </thead>
            <tbody>
                @if (isset($data) && count($data) > 0)
                    @foreach ($data as $o)
                        <tr>
                            <td>{{ $o->cdate }}</td>
                            <td>{{ $o->type_name }}</td>
                            <td><span style="{{ $o->type == 'D' ? 'color:red;' : ''}}">${{ number_format($o->amt, 2) }}</span></td>
                            <td>{{ $o->comments }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="4" class="text-center">No Record Found</td>
                    </tr>
                @endif
            </tbody>
            <tfoot>
            </tfoot>
        </table>

        <div class="text-right">
            {{ $data->appends(Request::except('page'))->links() }}
        </div>
    </div>
@stop
