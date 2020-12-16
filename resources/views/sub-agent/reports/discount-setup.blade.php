@extends('sub-agent.layout.default')

@section('content')

    <script type="text/javascript">

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
                        <h4>Discount Setup Report</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li><a href="#">Reports</a></li>
                            <li class="active">Discount Setup Report</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="contain-wrapp padding-bot70">
        <div class="well filter" style="padding-bottom:5px;">
            <form id="frm_search" class="form-horizontal" method="post" action="/sub-agent/reports/discount-setup">
                {{ csrf_field() }}
                <input type="hidden" name="excel" id="excel"/>
                <div class="row">

                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Action</label>
                            <div class="col-md-8">
                                <select class="form-control" name="action">
                                    <option value="">All</option>
                                    <option value="RTR" {{ old('action', $action) == 'RTR' ? 'selected' : '' }}>
                                        RTR
                                    </option>
                                    <option value="PIN" {{ old('action', $action) == 'PIN' ? 'selected' : '' }}>
                                        PIN
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Carrier</label>
                            <div class="col-md-8">
                                <select class="form-control" name="carrier">
                                    <option value="">All</option>
                                    @foreach ($carriers as $o)
                                        <option value="{{ $o->name }}" {{ $carrier == $o->name ? 'selected' : '' }}>{{ $o->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 text-right">
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
        *Subject to change without notice.
        <table class="table table-bordered table-hover table-condensed filter">
            <thead>
                <tr>
                    <th style="text-align: center">Action</th>
                    <th style="text-align: center">Carrier</th>
                    <th style="text-align: center">Product</th>
                    <th style="text-align: center">Account.Type</th>
                    <th style="text-align: center">Denom</th>
                    <th style="text-align: center">Rates</th>
                </tr>
            </thead>
            <tbody>
                @if (isset($data) && count($data) > 0)
                    @foreach ($data as $o)
                        <tr>
                            <td  style="text-align: center">{{ $o->action }}</td>
                            <td style="text-align: center">{{ $o->carrier }}</td>
                            <td style="text-align: center">{{ $o->name }}</td>
                            <td style="text-align: center">{!! Helper::get_hierarchy_img($account_type) !!}</td>
                            <td style="text-align: right">${{ $o->denom}}</td>
                            <td style="text-align: right">{{ $o->rates }}%</td>
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
{{--                    <th colspan="2" class="text-right">Total {{ $data->total() }} record(s).</th>--}}
{{--                    <th style="color:{{ $amt >= 0 ? 'black' : 'red' }}">${{ number_format($amt, 2) }}</th>--}}
{{--                    <th colspan="3"></th>--}}
                </tr>
            </tfoot>
        </table>

{{--        <div class="text-right">--}}
{{--            {{ $data->appends(Request::except('page'))->links() }}--}}
{{--        </div>--}}
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
