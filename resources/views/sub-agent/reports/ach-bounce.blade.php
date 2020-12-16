@extends('sub-agent.layout.default')

@section('content')

    <script type="text/javascript">

        var onload_func = window.onload;

        window.onload = function() {

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

    <h4>ACH Bounce Report</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post" action="/sub-agent/reports/ach-bounce">
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
                        <label class="col-md-4 control-label">Account.iD</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" id="account_id" name="account_id" value="{{ old('account_id', $account_id) }}"/>
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
                <th>ACH.ID</th>
                <th>Type</th>
                <th>Parent</th>
                <th>Account</th>
                <th>Bounce.Amt</th>
                <th>Bounce.Fee</th>
                <th>Post.Date</th>
                <th>Confirm.Date</th>
                <th>Bounce.Date</th>
                <th>Bounce.Msg</th>
            </tr>
        </thead>
        <tbody>
            @if (isset($data) && count($data) > 0)
                @foreach ($data as $o)
                    <tr>
                        <td>{{ $o->id }}</td>
                        <td>{{ $o->type_name }}</td>
                        <td>
                            {!! Helper::get_parent_name_html($o->account_id) !!}
                        </td>
                        <td>
                            <span>{!! Helper::get_hierarchy_img($o->account->type) !!}</span>
                            {{ $o->account->name . ' ( ' . $o->account->id . ' )' }}
                        </td>
                        <td>${{ number_format($o->amt, 2) }}</td>
                        <td>${{ number_format($o->bounce_fee, 2) }}</td>
                        <td>{{ $o->post_date }}</td>
                        <td>{{ $o->confirm_date }}</td>
                        <td>{{ $o->bounce_date }}</td>
                        <td>{{ $o->bounce_msg }}</td>
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
                <th>${{ number_format($bounce_amt, 2) }}</th>
                <th>${{ number_format($bounce_fee, 2) }}</th>
                <th colspan="4"></th>
            </tr>
        </tfoot>
    </table>

    <div class="text-right">
        {{ $data->appends(Request::except('page'))->links() }}
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
