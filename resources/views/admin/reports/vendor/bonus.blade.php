@extends('admin.layout.default')

@section('content')

    <script type="text/javascript">
        var onload_events = window.onload;
        window.onload = function() {
            if (onload_events) {
                onload_events();
            }

            $( "#sdate" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });
            $( "#edate" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $( "#nr_sdate" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });
            $( "#nr_edate" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });
        }

        function hide_red() {
            $(".red_class").show();
        }

        function close_modal(id) {
            $('#' + id).modal('hide');
        }

        function comm_search() {
            $('#excel').val('N');
            $('#frm_search').submit();
        }

        function excel_export() {
            $('#excel').val('Y');
            $('#frm_search').submit();
        }

        function show_upload() {
            $('#div_upload').modal();
        }

        function upload_data() {
            myApp.showLoading();
            $('#frm_upload').submit();
        }


        function show_batch_lookup() {
            $('#n_batch_esns').val('');
            $('#div_batch_lookup').modal();
        }

        function count_batch_ress() {
            var esns = $.trim($('#n_batch_ress').val()).split("\n");
            $('#n_batch_ress_qty').text(esns.length);
        }

        function batch_lookup() {
            var batch_ress = $('#n_batch_ress').val();
            batch_ress = $.trim(batch_ress);

            if (batch_ress === '') {
                myApp.showError('Please enter RESs to lookup');
                return;
            }

            $('#div_batch_lookup').modal('hide');
            $('#frm_batch_lookup').submit();
        }


    </script>

    <h4>Activation/Port-In Bonus Report</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post" action="/admin/reports/vendor/bonus">
            {{ csrf_field() }}

            <input type="hidden" name="excel" id="excel"/>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Carrier</label>
                        <div class="col-md-8">
                            <select name="carrier" class="form-control" onchange="$('#frm_spiff').submit()">
                                <option value="" {{ old('carrier', $carrier) == '' ? 'selected' : '' }}>Select</option>
                                <option value="H2O" {{ old('carrier', $carrier) == 'H2O' ? 'selected' : '' }}>H2O</option>
                                <option value="Lyca" {{ old('carrier', $carrier) == 'Lyca' ? 'selected' : '' }}>Lyca</option>
                                <option value="AT&T" {{ old('carrier', $carrier) == 'AT&T' ? 'selected' : '' }}>AT&T</option>
                                <option value="GEN Mobile" {{ old('carrier', $carrier) == 'GEN Mobile' ? 'selected' : ''
                                }}>GEN Mobile</option>
                                <option value="FreeUP" {{ old('carrier', $carrier) == 'FreeUP' ? 'selected' : '' }}>FreeUP</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Product</label>
                        <div class="col-md-8">
                            <select name="product_id" class="form-control" onchange="$('#frm_spiff').submit()">
                                <option value="">All</option>
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
                        <label class="col-md-4 control-label">Plan($)</label>
                        <div class="col-md-8">
                            <select name="denom" class="form-control" onchange="$('#frm_spiff').submit()">
                                <option value="">All</option>
                                @if (count($denoms) > 0)
                                    @foreach ($denoms as $o)
                                        <option value="{{ $o->denom }}" {{ old('denom', $denom) == $o->denom ? 'selected' : '' }}>{{ '$' . number_format($o->denom, 2) }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Upload.Date</label>
                        <div class="col-md-8">
                            <input type="text" style="width:100px; float:left;" class="form-control" id="sdate" name="sdate" value="{{ old('sdate', $sdate) }}"/>
                            <span class="control-label" style="margin-left:5px; float:left;"> ~ </span>
                            <input type="text" style="width:100px; margin-left: 5px; float:left;" class="form-control" id="edate" name="edate" value="{{ old('edate', $edate) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Status</label>
                        <div class="col-md-8">
                            <select name="status" class="form-control" >
                                <option value="">All</option>
                                <option value="A" {{ old('status', 'A') == $status ? 'selected' : '' }}>New</option>
                                <option value="P" {{ old('status', 'P') == $status ? 'selected' : '' }}>Paid</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Calculate Bonus</label>
                        <div class="col-md-8">
                            <input type="checkbox" name="calculate" value="Y" {{ $calculate == 'Y' ? 'checked' : '' }}/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8 col-md-offset-4 text-right">
                    <div class="form-group">
                        <a class="btn btn-primary btn-xs" id="btn_search" onclick="hide_red()">Button</a>
                        <a class="btn btn-primary btn-xs" id="btn_search" onclick="comm_search()">Search</a>
                        <!-- <button type="button" class="btn btn-blue btn-sm" onclick="show_batch_lookup()">Batch Lookup</button> -->
                        @if (in_array(Auth::user()->account_type, ['L']) && in_array(Auth::user()->user_id, ['admin', 'thomas', 'system']))
                            {{--<a type="button" class="btn btn-info btn-xs" onclick="excel_export()">Download</a>--}}
                        @endif
                    </div>
                </div>

            </div>
        </form>
    </div>


    <table class="table table-bordered table-hover table-condensed filter">
        <thead>
        <tr>
            <th>Carrier</th>
            @if (!empty($product_id))
            <th>Product</th>
            @if (!empty($denom))
            <th>Plan</th>
            @endif
            @endif
            <th>Action</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Status</th>
            <th>Ver</th>
            <th>Qty(Min)</th>
            <th>Qty(Max)</th>
            <th>Type</th>
            <th>Bonus.Amt</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @if (isset($rules) && count($rules) > 0)
            @foreach ($rules as $r)
                <tr>
                    <td rowspan="{{ $r->qty }}">{{ $r->carrier }}</td>
                    @if (!empty($product_id))
                    <td rowspan="{{ $r->qty }}">{{ $r->product_id }}</td>
                    @if (!empty($denom))
                    <td rowspan="{{ $r->qty }}">{{ $r->plan }}</td>
                    @endif
                    @endif
                    <td rowspan="{{ $r->qty }}">{{ $r->action }}</td>
                    <td rowspan="{{ $r->qty }}">{{ $r->sdate }}</td>
                    <td rowspan="{{ $r->qty }}">{{ $r->edate }}</td>
                    <td rowspan="{{ $r->qty }}">{{ $r->status == 'A' ? 'New' : 'Paid'}}</td>
                    <td rowspan="{{ $r->qty }}">{{ $r->version }}</td>
                    @foreach ($r->data as $d)
                        <td>{{ $d->qty_min }}</td>
                        <td>{{ $d->qty_max }}</td>
                        <td>{{ $d->type == 'T' ? 'Total Amount' : 'By Qty(Bunus * Qty)' }}</td>
                        <td>{{ $d->bonus_amt }}</td>
                        <td>
                            @if ($r->status == 'A')
                            <button type="button" onclick="remove_rule({{ $d->id }})" class="btn btn-primary
                        btn-xs">Remove</button>
                                @endif
                        </td>
                    </tr><tr>
                    @endforeach
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="20" class="text-center">No Record Found</td>
            </tr>
        @endif
        </tbody>
        <tfoot>
            <form class="form-horizontal" method="post" action="/admin/reports/vendor/bonus/add_rule">
                {{ csrf_field() }}

                <input type="hidden" name="carrier" value="{{ $carrier }}">
                <input type="hidden" name="product_id" value="{{ $product_id }}">
                <input type="hidden" name="denom" value="{{ $denom }}">
                <tr>
                    @if (!empty($product_id))
                        <th></th>
                        @if (!empty($denom))
                            <th></th>
                        @endif
                    @endif
                    <th>New Rule:</th>
                    <th>
                        <select id="nr_action" name="action" class="form-control">
                            <option value="">All</option>
                            <option value="Activation">Activation</option>
                            <option value="Port-In">Port-In</option>
                        </select>
                    </th>
                    <th><input type="text" id="nr_sdate" name="sdate" class="form-control"></th>
                    <th><input type="text" id="nr_edate" name="edate" class="form-control"></th>
                    <th></th>
                    <th><input type="text" id="nr_version" name="version" class="form-control"></th>
                    <th><input type="text" id="nr_qty_min" name="qty_min" class="form-control"></th>
                    <th><input type="text" id="nr_qty_max" name="qty_max" class="form-control"></th>
                    <th>
                        <select id="nr_type" name="type" class="form-control">
                            <option value="">Select</option>
                            <option value="S">By Qty(Bunus * Qty)</option>
                            <option value="T">Total Amount</option>
                        </select>
                    </th>
                    <th><input id="nr_bonus_amt" type="text" name="bonus_amt" class="form-control"></th>
                    <th><button type="button" onclick="add_rule()" class="btn btn-info btn-xs">Add New</button></th>
                </tr>
            </form>
        </tfoot>
    </table>

    @if ($calculate == 'Y')
    <script>
        function bonus_pay_out(idx) {

            myApp.showConfirm('Are you sure to proceed?', function() {
                myApp.showLoading();

                $.ajax({
                    url: '/admin/reports/vendor/bonus/pay_out',
                    data: {
                        _token: '{!! csrf_token() !!}',
                        carrier: $('#po_carrier_' + idx).val(),
                        product_id: $('#po_product_id_' + idx).val(),
                        denom: $('#po_denom_' + idx).val(),
                        action: $('#po_action_' + idx).val(),
                        version: $('#po_version_' + idx).val(),
                        sdate: $('#po_sdate_' + idx).val(),
                        edate: $('#po_edate_' + idx).val()
                    },
                    cache: false,
                    type: 'post',
                    dataType: 'json',
                    success: function (res) {
                        myApp.hideLoading();
                        comm_search();
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        myApp.hideLoading();
                        myApp.showError(errorThrown);
                    }
                });
            });
        }
    </script>
    @foreach($rules as $r)
    <hr>
    <div class="row">
        <div class="col-md-6">
            <p>
                {{$r->carrier}}   [ {{ $r->status == 'P' ? 'PAID' : 'NEW' }} ]
                <br>Plan: {{ empty($r->denom) ? 'All' : $r->denom }}
                <br>Action: {{ empty($r->action) ? 'Activation/Port-In' : $r->action }}
                <br>Start Date: {{ $r->sdate }}
                <br>End Date: {{ $r->edate }}
                <br>Version: {{ empty($r->version) ? '-' : $r->version }}
            </p>
        </div>
        <div class="col-md-6 text-right">
            <br>

            @php
                $idx = 0;
            @endphp
            @if ($r->status == 'A')
            <form class="form-horizontal" method="post" action="/admin/reports/vendor/bonus/pay_out">
                {{ csrf_field() }}
                @php
                    $idx++;
                @endphp

                <input type="hidden" id="po_carrier_{{ $idx }}" name="carrier" value="{{ $r->carrier }}">
                <input type="hidden" id="po_product_id_{{ $idx }}" name="product_id" value="{{ $r->product_id }}">
                <input type="hidden" id="po_denom_{{ $idx }}" name="denom" value="{{ $r->denom }}">
                <input type="hidden" id="po_action_{{ $idx }}" name="action" value="{{ $r->action }}">
                <input type="hidden" id="po_sdate_{{ $idx }}" name="sdate" value="{{ $r->sdate }}">
                <input type="hidden" id="po_edate_{{ $idx }}" name="edate" value="{{ $r->edate }}">
                <input type="hidden" id="po_version_{{ $idx }}" name="version" value="{{ $r->version }}">
                <button type="button" onclick="bonus_pay_out({{ $idx }})" class="btn btn-info btn-xs">Pay Out</button>
            </form>
            @else
                Paid at {{ $r->mdate }}
            @endif
        </div>
    </div>

    <table class="table table-bordered table-hover table-condensed filter">
        <thead>
        <tr>
            <th>Parent</th>
            <th>Account</th>
            <th>Action</th>
            <th>State</th>
            <th>Type</th>
            <th>Qty</th>
            <th>Bonus</th>
            <th>Status</th>
        </tr>
        </thead>
        <tbody>
            @foreach ($r->cal_data as $cal_d)
            @if ($cal_d->pay_status == 'Y')
            <tr id="cal_tr_{{ $cal_d->account_id }}">
                <td>{!! \App\Lib\Helper::get_parent_name_html($cal_d->account_id) !!} </td>
                <td>{{ $cal_d->account_id }}, {{ $cal_d->account_name }}</td>
                <td>
                    @if ($r->status == 'A')
                        <a type="button" class="btn btn-info btn-xs" onclick="add_exception({{ $cal_d->account_id }})">Do not pay</a>
                    @else
                        Paid
                    @endif
                </td>
                <td>{{ $cal_d->state }}</td>
                <td>{{ $cal_d->type == 'T' ? 'Total Amount' : 'By Qty(Bunus * Qty)' }}</td>
                <td>{{ $cal_d->qty }}</td>
                <td>{{ $cal_d->bonus_amt }}</td>
                <td>
                    @if ($r->status == 'A')
                        Pay
                    @else
                        Paid
                    @endif
                </td>
            </tr>
            @endif
            @endforeach

            @foreach ($r->cal_data as $cal_d)
            @if ($cal_d->pay_status !== 'Y')
            <tr id="cal_tr_{{ $cal_d->account_id }}" class="red_class" style="color:red; display: none;">
                <td>{!! \App\Lib\Helper::get_parent_name_html($cal_d->account_id) !!} </td>
                <td>{{ $cal_d->account_id }}, {{ $cal_d->account_name }}</td>
                <td>
                    @if ($r->status == 'A')
                        <a type="button" class="btn btn-info btn-xs" onclick="remove_exception({{ $cal_d->account_id }})">Pay again</a>
                    @else
                        Ignored
                    @endif
                </td>
                <td>{{ $cal_d->state }}</td>
                <td>{{ $cal_d->type == 'T' ? 'Total Amount' : 'By Qty(Bunus * Qty)' }}</td>
                <td>{{ $cal_d->qty }}</td>
                <td>{{ $cal_d->bonus_amt }}</td>
                <td>No Bonus</td>
            </tr>
            @endif
            @endforeach
        </tbody>
    </table>
        @endforeach
    @endif

    <form id="form_add_exception" method="post" class="form-horizontal"
          action="/admin/reports/vendor/bonus/add_exception">
        {{ csrf_field() }}

        <input type="hidden" name="carrier" value="{{ $carrier }}">
        <input type="hidden" name="product_id" value="{{ $product_id }}">
        <input type="hidden" id="a_account_id" name="account_id">
    </form>

    <form id="form_remove_exception" method="post" class="form-horizontal"
          action="/admin/reports/vendor/bonus/remove_exception">
        {{ csrf_field() }}

        <input type="hidden" name="carrier" value="{{ $carrier }}">
        <input type="hidden" name="product_id" value="{{ $product_id }}">
        <input type="hidden" id="r_account_id" name="account_id">
    </form>

    <script>
        function add_rule() {
            // $('#a_account_id').val(account_id);
            // $('#form_add_exception').submit();

            myApp.showLoading();

            $.ajax({
                url: '/admin/reports/vendor/bonus/add_rule',
                data: {
                    _token: '{!! csrf_token() !!}',
                    carrier: '{!! $carrier !!}',
                    product_id: '{{ $product_id }}',
                    denom: '{{ $denom }}',
                    action: $('#nr_action').val(),
                    sdate: $('#nr_sdate').val(),
                    edate: $('#nr_edate').val(),
                    version: $('#nr_version').val(),
                    qty_min: $('#nr_qty_min').val(),
                    qty_max: $('#nr_qty_max').val(),
                    type: $('#nr_type').val(),
                    bonus_amt: $('#nr_bonus_amt').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    comm_search();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });
        }

        function remove_rule(id) {
            // $('#a_account_id').val(account_id);
            // $('#form_add_exception').submit();

            myApp.showLoading();

            $.ajax({
                url: '/admin/reports/vendor/bonus/remove_rule',
                data: {
                    _token: '{!! csrf_token() !!}',
                    id: id
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    comm_search();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });
        }

        function add_exception(account_id) {
            // $('#a_account_id').val(account_id);
            // $('#form_add_exception').submit();

            myApp.showConfirm('Are you sure to proceed?', function() {
                myApp.showLoading();

                $.ajax({
                    url: '/admin/reports/vendor/bonus/add_exception',
                    data: {
                        _token: '{!! csrf_token() !!}',
                        carrier: '{!! $carrier !!}',
                        account_id: account_id
                    },
                    cache: false,
                    type: 'post',
                    dataType: 'json',
                    success: function (res) {
                        myApp.hideLoading();
                        comm_search();
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        myApp.hideLoading();
                        myApp.showError(errorThrown);
                    }
                });
            });
        }

        function remove_exception(account_id) {
            // $('#r_account_id').val(account_id);
            // $('#form_remove_exception').submit();

            myApp.showConfirm('Are you sure to proceed?', function() {
                myApp.showLoading();

                $.ajax({
                    url: '/admin/reports/vendor/bonus/remove_exception',
                    data: {
                        _token: '{!! csrf_token() !!}',
                        carrier: '{!! $carrier !!}',
                        account_id: account_id
                    },
                    cache: false,
                    type: 'post',
                    dataType: 'json',
                    success: function (res) {
                        myApp.hideLoading();
                        comm_search();
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        myApp.hideLoading();
                        myApp.showError(errorThrown);
                    }
                });
            });
        }
    </script>

    <div class="row">
        @if ($errors->has('exception'))
            <div class="alert alert-danger alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                <strong>Error!</strong> {{ $errors->first('exception') }}
            </div>
        @endif
    </div>

    <div class="modal" id="div_upload" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Upload Data</h4>
                </div>
                <div class="modal-body">

                    <form id="frm_upload" class="form-horizontal detail" action="/admin/reports/vendor/commission/upload" method="post" target="ifm_upload" enctype="multipart/form-data">
                        {!! csrf_field() !!}
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Select Commission File</label>
                                    <div class="col-sm-8">
                                        <input type="file" class="form-control" id="file" name="file"/>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="upload_data()">Upload</button>
                </div>
            </div>
        </div>
    </div>
    <div style="display:none">
        <iframe id="ifm_upload" name="ifm_upload"></iframe>
    </div>


    <div class="modal" id="div_batch_lookup" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Please enter ESNs to lookup</h4>
                </div>
                <div class="modal-body">
                    <form id="frm_batch_lookup" action="/admin/reports/vendor/commission/batch-lookup" class="form-horizontal filter"
                          method="post" style="padding:15px;">
                        {{ csrf_field() }}

                        <div class="form-group">
                            <div class="col-sm-12">
                                <label>
                                    Excel file will be downloaded after submit.
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-12">
                                <input type="radio" name="batch_res_type" value="M" checked> MDN
                                <input type="radio" name="batch_res_type" value="D"> Device.ID
                                <input type="radio" name="batch_res_type" value="I"> ICCID
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-12">
                                <textarea id="n_batch_ress" name="batch_ress" rows="10" style="width:100%; line-height: 150%;"
                                          onchange="count_batch_ress()"></textarea><br/>
                                Total <span id="n_batch_ress_qty">0</span> ESN(s).
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="margin-right:15px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button class="btn btn-primary" onclick="batch_lookup()">Submit</button>
                </div>
            </div>
        </div>
    </div>
@stop
