@extends('admin.layout.default')

@section('content')

    <script type="text/javascript">
        var onload_events = window.onload;
        window.onload = function() {
            if (onload_events) {
                onload_events();
            }

            // if (document.referrer !== document.location.href) {
            //
            //     myApp.showLoading();
            //
            //     setTimeout(function() {
            //         document.location.reload()
            //     }, 1000);
            // }
        }

        function close_modal(id) {
            $('#' + id).modal('hide');
        }

        function excel_export() {
            $('#excel').val('Y');
            $('#frm_search').submit();
        }

        function upload_final() {

            myApp.showLoading();

            $('#frm_upload_final').submit();
        }

    </script>

    <h4>Compensation Bonus By Account Preview</h4>

    <div class="well filter" style="padding-bottom:5px;">
{{--        <form id="frm_search" class="form-horizontal" method="post" action="/admin/reports/vendor/commission_temp">--}}
{{--            {{ csrf_field() }}--}}
{{--            <input type="hidden" name="excel" id="excel"/>--}}
{{--            <div class="row">--}}

{{--            <div class="row">--}}
{{--                <div class="col-md-8 col-md-offset-3 text-right">--}}
{{--                    <div class="form-group">--}}
{{--                        @if (in_array(Auth::user()->account_type, ['L']) && in_array(Auth::user()->user_id, ['yongj', 'admin', 'thomas']))--}}
{{--                        <a type="button" class="btn btn-info btn-xs" onclick="excel_export()">Download</a>--}}
{{--                        @endif--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </form>--}}

{{--        <form id="frm_upload_final" class="form-horizontal" method="post" action="/admin/reports/vendor/commission/upload_final">--}}
{{--            {{ csrf_field() }}--}}
{{--            <input type="hidden" name="file_name" id="file_name"/>--}}
{{--            <div class="row">--}}
{{--                <div class="col-md-8 col-md-offset-3 text-right">--}}
{{--                    <div class="form-group">--}}
{{--                        @if (in_array(Auth::user()->account_type, ['L']) && in_array(Auth::user()->user_id, ['yongj', 'admin', 'thomas']))--}}
{{--                            <a type="button" onclick="upload_final()" class="btn btn-default btn-xs">Pay</a>--}}
{{--                        @endif--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </form>--}}

        <div class="row">
            <div class="col-md-8 col-md-offset-3 text-right">
                <a href="commission_bonus_temp?excel=Y" type="button" class="btn btn-info btn-xs">Download</a>
                <a href="commission?cancel=Y" type="button" class="btn btn-primary btn-xs">Cancel</a>
                <a href="commission/upload_bonus_by_acct_final" type="button" class="btn btn-default btn-xs">Pay Now !</a>
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <table class="table table-bordered table-hover table-condensed filter">
        <thead>
            <tr>
                <th>ID</th>
                <th>File.Name</th>
                <th>Product</th>
                <th>Product.Name</th>
                <th>Phone</th>
                <th>SIM</th>
                <th>Month</th>
                <th>Spiff.M</th>
                <th>Status</th>
                <th>Denom</th>
                <th>Spiff</th>
                <th>Spiff.Will</th>
                <th>Residual</th>
                <th>Residual.Will</th>
                <th>Bonus</th>
                <th style="color: red">Bonus.Will</th>
                <th>Value</th>
                <th>Total</th>
                <th>Description</th>
                <th>Account</th>
                <th>Act.Tx.ID</th>
                <th>Date.Added</th>
                <th>Upload.Date</th>
                <th>Upload.By</th>
            </tr>
        </thead>
        <tbody>
            @if (isset($data) && count($data) > 0)
                @foreach ($data as $o)
                    <tr>
                        <td>{{ $o->id }}</td>
                        <td>{{ $o->file_name }}</td>
                        <td>{{ $o->product_id }}</td>
                        <td>{{ $o->product_name }}</td>
                        <td>{{ $o->phone }}</td>
                        <td>{{ $o->sim }}</td>
                        <td>{{ $o->month }}</td>
                        <td>{{ $o->spiff_month }}</td>
                        <td>{{ $o->status == 'S' ? 'Paid' : 'Unpaid'}}</td>
                        <td>{{ empty($o->denom) ? '' : '$' . number_format($o->denom, 2) }}</td>
                        <td>${{ number_format($o->spiff, 2) }}</td>
                        <td>${{ number_format($o->paid_spiff, 2) }}</td>
                        <td>${{ number_format($o->residual, 2) }}</td>
                        <td>${{ number_format($o->paid_residual, 2) }}</td>
                        <td>${{ number_format($o->bonus, 2) }}</td>
                        <td style="{{ $o->paid_bonus == 0 ? '' : 'color:red' }}">${{ number_format($o->paid_bonus, 2) }}</td>
                        <td>${{ number_format($o->value, 2) }}</td>
                        <td>${{ number_format($o->total, 2) }}</td>
                        <td>{{ $o->notes }}</td>
                        @if (isset($o->account_id))
                            <td>
                                {!! Helper::get_parent_name_html($o->account_id) !!}
                                <span>{!! Helper::get_hierarchy_img($o->account_type) !!}</span>{{ $o->account_name }} ( {{ $o->account_id }} )
                            </td>
                        @else
                            <td></td>
                        @endif
                        <td>{{ $o->trans_id }}</td>
                        <td>{{ $o->date_added }}</td>
                        <td>{{ $o->cdate }}</td>
                        <td>{{ $o->created_by }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="23" class="text-center">No Record Found</td>
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

@stop
