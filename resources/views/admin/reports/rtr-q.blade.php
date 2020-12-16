@extends('admin.layout.default')

@section('content')

    <script type="text/javascript">
        window.onload = function() {
            $( "#sdate" ).datetimepicker({
                format: 'YYYY-MM-DD HH:00:00',
                sideBySide: true
            });
            $( "#edate" ).datetimepicker({
                format: 'YYYY-MM-DD HH:59:59',
                sideBySide: true
            });

            $( "#r_sdate" ).datetimepicker({
                format: 'YYYY-MM-DD HH:00:00',
                sideBySide: true
            });
            $( "#r_edate" ).datetimepicker({
                format: 'YYYY-MM-DD HH:59:59',
                sideBySide: true
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

        function search() {
            $('#id').val(id);
            $('#excel').val('N');
            $('#frm_search').prop('action', '/admin/reports/rtr-q');
            myApp.showLoading();
            $('#frm_search').submit();
        }

        function retry(id) {

            $('#id').val(id);
            $('#frm_search').prop('action', '/admin/reports/rtr-q/retry');

            myApp.showLoading();
            $('#frm_search').submit();
        }

    </script>

    <h4>RTR Queue Report</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post" action="/admin/reports/rtr-q">
            {{ csrf_field() }}
            <input type="hidden" name="excel" id="excel"/>
            <input type="hidden" name="id" id="id"/>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Scheduled Date</label>
                        <div class="col-md-8">
                            <input type="text" style="width:125px; float:left;" class="form-control" id="sdate" name="sdate" value="{{ old('sdate', $sdate) }}"/>
                            <span class="control-label" style="margin-left:5px; float:left;"> ~ </span>
                            <input type="text" style="width:125px; margin-left: 5px; float:left;" class="form-control" id="edate" name="edate" value="{{ old('edate', $edate) }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Carrier</label>
                        <div class="col-md-8">
                            <select class="form-control" name="carrier" id="carrier" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                <option value="" {{ old('carrier', $carrier) == '' ? 'selected' : '' }}>All</option>
                                @if (count($carriers) > 0)
                                    @foreach ($carriers as $o)
                                        <option value="{{ $o->name }}" {{ old('carrier', $carrier) == $o->name ? 'selected' : '' }}>{{ $o->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Result</label>
                        <div class="col-md-8">
                            <select class="form-control" id="result" name="result" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                <option value="" {{ old('result', $result) == '' ? 'selected' : '' }}>All</option>
                                <option value="N" {{ old('result', $result) == 'N' ? 'selected' : '' }}>Waiting</option>
                                <option value="P" {{ old('result', $result) == 'P' ? 'selected' : '' }}>Processing</option>
                                <option value="S" {{ old('result', $result) == 'S' ? 'selected' : '' }}>Success</option>
                                <option value="F" {{ old('result', $result) == 'F' ? 'selected' : '' }}>Failed</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Ran Date</label>
                        <div class="col-md-8">
                            <input type="text" style="width:125px; float:left;" class="form-control" id="r_sdate" name="r_sdate" value="{{ old('r_sdate', $r_sdate) }}"/>
                            <span class="control-label" style="margin-left:5px; float:left;"> ~ </span>
                            <input type="text" style="width:125px; margin-left: 5px; float:left;" class="form-control" id="r_edate" name="r_edate" value="{{ old('r_edate', $r_edate) }}"/>
                        </div>
                    </div>
                </div>


                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Airtime.Source</label>
                        <div class="col-md-8">
                            <select class="form-control" id="category" name="category" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                <option value="" {{ old('category', $category) == '' ? 'selected' : '' }}>All</option>
                                <option value="House" {{ old('category', $category) == 'House' ? 'selected' : '' }}>House</option>
                                <option value="Carrier" {{ old('category', $category) == 'Carrier' ? 'selected' : '' }}>Carrier</option>
                                <option value="Refill" {{ old('category', $category) == 'Refill' ? 'selected' : '' }}>Refill</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Vendor</label>
                        <div class="col-md-8">
                            <select class="form-control" name="vendor" id="vendor" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                <option value="" {{ old('vendor', $vendor) == '' ? 'selected' : '' }}>All</option>
                                @if (count($vendors) > 0)
                                    @foreach ($vendors as $o)
                                        <option value="{{ $o->code }}" {{ old('vendor', $vendor) == $o->code ? 'selected' : '' }}>{{ $o->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Action</label>
                        <div class="col-md-8">
                            <select class="form-control" name="action" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                <option value="" {{ old('action', $action) == '' ? 'selected' : '' }}>All</option>
                                <option value="Activation" {{ old('action', $action) == 'Activation' ? 'selected' : '' }}>Activation</option>
                                <option value="Port-In" {{ old('action', $action) == 'Port-In' ? 'selected' : '' }}>Port-In</option>
                                <option value="Activation,Port-In" {{ old('action', $action) == 'Activation,Port-In' ? 'selected' :'' }}>Activation + Port-In</option>
                                <option value="RTR" {{ old('action', $action) == 'RTR' ? 'selected' : '' }}>RTR</option>
                                <option value="PIN" {{ old('action', $action) == 'PIN' ? 'selected' : '' }}>PIN</option>
                                <option value="RTR,PIN" {{ old('action', $action) == 'RTR,PIN' ? 'selected' : '' }}>RTR + PIN</option>
                            </select>
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

{{--                <div class="col-md-4">--}}
{{--                    <div class="form-group">--}}
{{--                        <label class="col-md-4 control-label">Account ID</label>--}}
{{--                        <div class="col-md-8">--}}
{{--                            <input type="text" class="form-control" name="account_id" value="{{ old('account_id', $account_id) }}"/>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Product</label>
                        <div class="col-md-8">
                            <select class="form-control" name="product_id" id="product_id" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                <option value="" {{ old('product_id', $product_id) == '' ? 'selected' : '' }}>All</option>
                                @if (count($products) > 0)
                                    @foreach ($products as $o)
                                        <option value="{{ $o->id }}" {{ old('product_id', $product_id) == $o->id ? 'selected' : '' }}>{{ $o->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Account Name</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="account_name" value="{{ old('account_name', $account_name) }}"/>
                        </div>
                    </div>
                </div>


                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">SIM Type</label>
                        <div class="col-md-8">
                            <select name="sim_type" id="sim_type" class="form-control">
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
                        <label class="col-md-4 control-label">SIMs</label>
                        <div class="col-md-8">
{{--                            <input type="text" class="form-control" id="sim" name="sim" value="{{ old('sim', $sim) }}"/>--}}
                            <textarea class="form-control" name="sims" rows="2">{{ $sims }}</textarea>
                        </div>
                    </div>
                </div>


                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Account.IDs</label>
                        <div class="col-md-8">
                            <textarea class="form-control" name="account_ids" rows="2">{{ $account_ids }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Phones</label>
                        <div class="col-md-8">
                            {{--                            <input type="text" class="form-control" name="phone" id="phone" value="{{ old('phone', $phone) }}"/>--}}
                            <textarea class="form-control" name="phones" rows="2">{{ $phones }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">ESNs</label>
                        <div class="col-md-8">
                            <textarea class="form-control" name="esns" rows="2">{{ $esns }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 text-right">
                    <div class="form-group">
                        <div class="col-md-12">
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <div class="form-group">
                        <div class="col-md-12">
                        </div>
                    </div>
                </div>

                <div class="col-md-4 text-right">
                    <div class="form-group">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary btn-sm" id="btn_search" onclick="search()">Search</button>
                            @if($show_export)
                            <button type="button" class="btn btn-info btn-sm" onclick="excel_export()">Export</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="text-left">
        Total {{$counts }} record(s).
    </div>
    <table class="table table-bordered table-hover table-condensed filter">
        <thead>
        <tr>
            <th>Q.ID</th>
            <th>Act.Tx.ID</th>
            <th>Account</th>
            <th>Account.Type</th>
            <th>Carrier</th>
            <th>Phone</th>
            <th>SIM</th>
            <th>ESN</th>
            <th>SIM.Type</th>
            <th>Product</th>
            <th>Amt($)</th>
            <th>Scheduled.On</th>
            <th>Seq</th>
            <th>Vendor</th>
            <th>Airtime.Source</th>
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
                    @if ($o->seq == 'MANUAL-RTR')
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                    @else
                        <td>{{ $o->trans_id }}</td>
{{--                        <td>{!! Helper::get_parent_name_html($o->account_id) !!} <span>{!! Helper::get_hierarchy_img($o->account_type) !!}</span>{{ $o->account_name . ' ( ' . $o->account_id . ' )' }}</td>--}}
                        <td>{{ $o->acct_name . ' ( ' . $o->acct_id . ' )' }}</td>
                        <td>{{$o->acct_type}}</td>
                    @endif
                    <td>{{ $o->carrier_q }}</td>
                    <td>{{ $o->phone }}</td>
                    <td>{{ $o->sim_q }}</td>
                    <td>{{ $o->esn_q }}</td>
                    <td>{{ $o->sim_type_q }}</td>
                    <td>{{ $o->prod_name }}</td>
                    <td>{{ $o->amt_q }}</td>
                    <td>{{ $o->run_at }}</td>
                    <td>{{ $o->seq }}</td>
                    <td>{{ $o->vendor_code }}</td>
                    <td>{{ $o->category }}</td>
                    <td>
                        @if ($o->id != '-')
                            <span style="display:inline;">{{ $o->result_name }}</span>
                            @if ($o->result == 'F')
                                <div style="padding-left:10px; display:inline;">
                                    <button type="button" onclick="retry({{ $o->id }})" class="btn btn-sm btn-primary">Retry</button>
                                </div>
                            @endif
                        @else
                            @if ($o->result == 'C')
                                Success
                            @else
                                Fail
                            @endif
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
    <div class="text-left">
        Total {{$counts }} record(s).
    </div>

    <div class="text-right">
{{--        {{ $records->appends(Request::except('page'))->links() }}--}}
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
