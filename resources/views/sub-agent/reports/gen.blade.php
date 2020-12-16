@extends('sub-agent.layout.default')

@section('content')

    <script type="text/javascript">
        window.onload = function () {
            $("#sdate").datetimepicker({
                format: 'YYYY-MM-DD'
            });
            $("#edate").datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $('.note-check-box').tooltip();
        };

        function refresh_all() {
            window.location.href = '/sub-agent/reports/transaction';
        }

        function search() {
            myApp.showLoading();
            $('#excel').val('N');
            $('#frm_search').submit();
        }

        function excel_export() {
            $('#excel').val('Y');
            $('#frm_search').submit();
        }
    </script>

    <div class="contain-wrapp padding-bot70">

        <div class="well filter" style="padding-bottom:5px;">
            <form id="frm_search" class="form-horizontal" method="post" action="/sub-agent/reports/gen">
                {{ csrf_field() }}
                <input type="hidden" name="excel" id="excel"/>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Date</label>
                            <div class="col-md-8">
                                <input type="text" style="width:100px; float:left;" class="form-control"
                                       id="sdate" name="sdate" value="{{ old('sdate', $sdate) }}"/>
                                <span class="control-label" style="margin-left:5px; float:left;"> ~ </span>
                                <input type="text" style="width:100px; margin-left: 5px; float:left;"
                                       class="form-control" id="edate" name="edate"
                                       value="{{ old('edate', $edate) }}"/>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Action</label>
                            <div class="col-md-8">
                                <select class="form-control" name="action">
                                    <option value="" {{ old('action', $action) == '' ? 'selected' : '' }}>
                                        All
                                    </option>
                                    <option value="Activation" {{ old('action', $action) == 'Activation' ? 'selected' : '' }}>
                                        Activation
                                    </option>
                                    <option value="Port-In" {{ old('action', $action) == 'Port-In' ? 'selected' : '' }}>
                                        Port-In
                                    </option>
                                    <option value="Activation,Port-In" {{ old('action', $action) == 'Activation,Port-In' ? 'selected' :
                                 '' }}>Activation + Port-In</option>
                                    <option value="RTR" {{ old('action', $action) == 'RTR' ? 'selected' : '' }}>
                                        RTR
                                    </option>
                                    <option value="PIN" {{ old('action', $action) == 'PIN' ? 'selected' : '' }}>PIN</option>
                                    <option value="RTR,PIN" {{ old('action', $action) == 'RTR,PIN' ? 'selected' : '' }}>RTR +
                                        PIN</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Master</label>
                            <div class="col-md-8">
                                <select class="form-control" name="master_id">
                                    <option value="" {{ old('master_id', $master_id) == '' ? 'selected' : '' }}>
                                        All
                                    </option>
                                    @foreach ($masters as $master)
                                    <option value="{{ $master->id }}" {{ old('master_id', $master_id) == $master->id ?
                                    'selected' : ''
                                     }}>
                                        {{ $master->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                        </div>
                    </div>

                    <div class="col-md-4 text-right">
                        <div class="form-group">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-primary btn-sm" id="btn_search" onclick="search()">
                                    Search
                                </button>
                                <button type="button" class="btn btn-info btn-sm" onclick="excel_export()">
                                    Export
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>


        <table class="parameter-product table-bordered table-hover table-condensed filter">
            <thead>
            <tr class="active">
                <td><strong>ID</strong></td>
                <td><strong>Type</strong></td>
                <td><strong>Denom($)</strong></td>
                <td><strong>Action</strong></td>
                <td><strong>SIM</strong></td>
                <td><strong>ESN</strong></td>
                <td><strong>Phone</strong></td>
                <td><strong>Created</strong></td>
            </tr>
            </thead>
            <tbody>
            @if (isset($transactions) && count($transactions) > 0)
                @foreach ($transactions as $o)
                    <tr>
                        <td>{{ $o->id }}</td>
                        <td style="{{ $o->type_name == 'Void' ? 'color:red;' : '' }}">{{ $o->type_name }}</td>
                        <td>${{ $o->denom }}</td>
                        <td>{{ $o->action }}</td>
                        <td>
                            @if (!empty($o->sim))
                                {{ $o->sim }}
                            @endif
                        </td>
                        <td>{{ empty($o->esn) ? '' : $o->esn }}</td>
                        <td>{{ $o->phone }}</td>
                        <td>{{ $o->cdate }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="20" class="text-center">No Record Found</td>
                </tr>
            @endif
            </tbody>
            <tfoot>
            <tr>
                <th colspan="8" class="text-left">Total {{ $transactions->total() }} record(s). </th>
            </tr>
            </tfoot>
        </table>

        <div class="text-right">
            {{ $transactions->appends(Request::except('page'))->links() }}
        </div>


    </div>

@stop
