@extends('admin.layout.default')

@section('content')

    <script type="text/javascript">
        var onload_events = window.onload;
        window.onload = function() {

            if (onload_events) {
                onload_events();
            }

            $("#sdate").datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $("#edate").datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $(".tooltip").tooltip({
                html: true
            })
        };

        function show_batch_lookup() {
            $('#n_batch_esns').val('');
            $('#div_batch_lookup').modal();
        }

        function show_detail() {
            $('#div_detail').modal();
        }

        function save_detail() {

            var file = $('#sim_csv_file').val();
            if (file == '') {
                myApp.showError('Please select file to upload');
                return;
            }

            myApp.showLoading();
            $('#frm_upload').submit();
        }

        function close_modal() {
            $('#div_detail').modal('hide');
            myApp.showSuccess('Your request has been processed successfully!', function() {
                $('#btn_search').click();
            });
        }

        function excel_export() {
            $('#excel').val('Y');
            $('#frm_search').submit();
            myApp.hideLoading();
            $('#excel').val('');
        }

        function show_esn_assign() {
            $('#n_clear_assign').attr('checked', false);
            $('#n_c_store_id').attr('disabled', false);
            $('#n_c_store_id').val('');
            $('#n_esns').val('');

            $('#div_esn_assign').modal();
        }

        function save_esn_assign() {
            myApp.showLoading();
            $.ajax({
                url: '/admin/settings/esn/rok/assign',
                data: {
                    _token: '{!! csrf_token() !!}',
                    c_store_id: $('#n_c_store_id').val(),
                    clear: $('#n_clear_assign').is(':checked') ? 'Y' : 'N',
                    esns: $('#n_esns').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if($.trim(res.msg) === '') {
                        myApp.showSuccess('Your request has been successfully processed!', function() {
                            $('#c_store_id').val($('#n_c_store_id').val());
                            $('#frm_search').submit();
                        });
                    } else {
                        myApp.showError(res.msg);
                    }
                }
            });
        }

        function change_assign_status() {
            var clear = $('#n_clear_assign').is(':checked');
            if (clear) {
                $('#n_c_store_id').attr('disabled', true);
            } else {
                $('#n_c_store_id').attr('disabled', false);
            }
        }

        function count_esns() {
            var esns = $.trim($('#n_esns').val()).split("\n");
            $('#n_esns_qty').text(esns.length);
        }

        function count_batch_esns() {
            var esns = $.trim($('#n_batch_esns').val()).split("\n");
            $('#n_batch_esns_qty').text(esns.length);
        }

        function batch_lookup() {
            var batch_esns = $('#n_batch_esns').val();
            batch_esns = $.trim(batch_esns);

            if (batch_esns === '') {
                myApp.showError('Please enter ESNs to lookup');
                return;
            }

            $('#div_batch_lookup').modal('hide');
            $('#frm_batch_lookup').submit();
        }
    </script>


    <h4>ROK ESN</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post" onsubmit="myApp.showLoading();">
            {{ csrf_field() }}
            <input type="hidden" name="excel" id="excel" value=""/>
            <input type="hidden" id="id" name="id"/>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">ESN</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="esn" value="{{ $esn }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Status</label>
                        <div class="col-md-8">
                            <select class="form-control" name="status">
                                <option value="">All</option>
                                <option value="A" {{ $status == 'A' ? 'selected' : '' }}>Active</option>
                                <option value="H" {{ $status == 'H' ? 'selected' : '' }}>On-Hold</option>
                                <option value="S" {{ $status == 'S' ? 'selected' : '' }}>Suspended</option>
                                <option value="U" {{ $status == 'U' ? 'selected' : '' }}>Used</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Type</label>
                        <div class="col-md-8">
                            <select class="form-control" name="type">
                                <option value="">All</option>
                                <option value="B" {{ $type == 'B' ? 'selected' : '' }}>Bundled</option>
                                <option value="P" {{ $type == 'P' ? 'selected' : '' }}>Wallet</option>
                                <option value="R" {{ $type == 'R' ? 'selected' : '' }}>Regular</option>
                                <option value="C" {{ $type == 'C' ? 'selected' : '' }}>Consignment</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">RTR.Month</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="rtr_month" value="{{ $rtr_month }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Sub.Carrier</label>
                        <div class="col-md-8">
                            <select class="form-control" name="sub_carrier" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                <option value="" {{ old('sub_carrier', $sub_carrier) == '' ? 'selected' : '' }}>All</option>
                                @foreach ($sub_carriers as $o)
                                    <option value="{{ $o->sub_carrier }}" {{ old('sub_carrier', $sub_carrier) == $o->sub_carrier ? 'selected' : '' }}>{{ $o->sub_carrier }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Upload.Date</label>
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
                        <label class="col-md-4 control-label">Supplier</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="supplier" value="{{ $supplier }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Supplier.Date</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="supplier_date" value="{{ $supplier_date }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Supplier.Memo</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="supplier_memo" value="{{ $supplier_memo }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Buyer.Name</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="buyer_name" value="{{ $buyer_name }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Buyer.Date</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="buyer_date" value="{{ $buyer_date }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Buyer.Memo</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="buyer_memo" value="{{ $buyer_memo }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Comments</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="comments" value="{{ $comments }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">eCommerce.ID</label>
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="c_store_id" name="c_store_id" value="{{ $c_store_id }}"/>
                        </div>
                        <div class="col-md-4">
                            <label>
                                <input type="checkbox" name="show_all_c_store" value="Y" {{ $show_all_c_store == 'Y' ? 'checked' : '' }}/> Show me All eCommerce?
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Owner.ID</label>
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="owner_id" name="owner_id" value="{{ $owner_id }}"/>
                        </div>
                        <div class="col-md-4">
                            <label>
                                <input type="checkbox" name="show_all_owner" value="Y"  {{ $show_all_owner == 'Y' ? 'checked' : '' }}/> Show me All Owner?
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Subsidy</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="subsidy" value="{{ $subsidy }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Supplier.Make</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="supplier_make" value="{{ $supplier_make }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Supplier.Model</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="supplier_model" value="{{ $supplier_model }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Shipped.Date</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="shipped_date" value="{{ $shipped_date }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Supplier.Cost</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="supplier_cost" value="{{ $supplier_cost }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Buyer.Price</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="buyer_price" value="{{ $buyer_price }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-md-offset-8">
                    <div class="form-group">
                        <div class="col-md-12 text-right">
                            <button type="submit" class="btn btn-primary btn-sm" id="btn_search">Search</button>
                            <button type="button" class="btn btn-blue btn-sm" onclick="show_batch_lookup()">Batch Lookup</button>
                            @if(Auth::check() && in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']))
                                <button type="button" class="btn btn-default btn-sm" onclick="show_detail()">Upload New ESN</button>
                                <button type="button" class="btn btn-info btn-sm" onclick="excel_export()">Download</button>
                                <button type="button" class="btn btn-danger btn-sm" onclick="show_esn_assign()">Assign eCommerce SIM</button>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>


    <table class="table table-bordered table-hover table-condensed filter">
        <thead>
        <tr>
            <th rowspan="3">ESN</th>
            <th rowspan="3">Sub.Carrier</th>
            <th rowspan="3">Amount</th>
            <th colspan="5">Consignment</th>
            <th rowspan="3">Type</th>
            <th rowspan="3">RTR.Month</th>
            <th rowspan="3">Spiff.Month</th>
            <th rowspan="3">Rebate.Month</th>
            <th colspan="3" rowspan="2">Spiff.Override</th>
            <th colspan="3" rowspan="2">Rebate.Override</th>
            <th rowspan="3">Status</th>
            <th rowspan="3">Device.Type</th>
            <th colspan="7" rowspan="2">Supplier</th>
            <th colspan="4" rowspan="2">Buyer</th>
            <th rowspan="3">Comments</th>
            <th rowspan="3">eCommerce.ID</th>
            <th rowspan="3">Used.Tx.ID</th>
            <th rowspan="3">Used.Date</th>
            <th rowspan="3">Upload.Date</th>
        </tr>
        <tr>
            <th colspan="3">Charge.Amount</th>
            <th rowspan="2">Owner.ID</th>
            <th rowspan="2">Shipped.Date</th>
        </tr>
        <tr>
            <th>R</th>
            <th>D</th>
            <th>M</th>

            <th>R</th>
            <th>D</th>
            <th>M</th>
            <th>R</th>
            <th>D</th>
            <th>M</th>
            <th>Name</th>
            <th>Subsidy</th>
            <th>Make</th>
            <th>Model</th>
            <th>Cost</th>
            <th>Date</th>
            <th>Memo</th>
            <th>Name</th>
            <th>Price</th>
            <th>Date</th>
            <th>Memo</th>

        </tr>
        </thead>
        <tbody>
        @if (isset($data) && count($data) > 0)
            @foreach ($data as $o)
                <tr>
                    <td>{{ $o->esn }}</td>
                    <td>{{ $o->sub_carrier }}</td>
                    <td style="{{ $o->type == 'R' ? 'background-color:#efefef' : '' }}">{{ $o->amount }}</td>
                    <td style="{{ $o->type != 'C' ? 'background-color:#efefef' : '' }}">{{ $o->charge_amount_r }}</td>
                    <td style="{{ $o->type != 'C' ? 'background-color:#efefef' : '' }}">{{ $o->charge_amount_d }}</td>
                    <td style="{{ $o->type != 'C' ? 'background-color:#efefef' : '' }}">{{ $o->charge_amount_m }}</td>
                    <td style="{{ $o->type != 'C' ? 'background-color:#efefef' : '' }}">{{ $o->owner_id }}</td>
                    <td style="{{ $o->type != 'C' ? 'background-color:#efefef' : '' }}">{{ $o->shipped_date }}</td>
                    <td>{{ $o->type_name }}</td>
                    <td>{{ $o->rtr_month }}</td>
                    <td>{{ $o->spiff_month }}</td>
                    <td>{{ $o->rebate_month }}</td>
                    <td style="{{ $o->spiff_month == 0 ? 'background-color:#efefef' : '' }}">{{ $o->spiff_override_r }}</td>
                    <td style="{{ $o->spiff_month == 0 ? 'background-color:#efefef' : '' }}">{{ $o->spiff_override_d }}</td>
                    <td style="{{ $o->spiff_month == 0 ? 'background-color:#efefef' : '' }}">{{ $o->spiff_override_m }}</td>
                    <td style="{{ $o->rebate_month == 0 ? 'background-color:#efefef' : '' }}">{{ $o->rebate_override_r }}</td>
                    <td style="{{ $o->rebate_month == 0 ? 'background-color:#efefef' : '' }}">{{ $o->rebate_override_d }}</td>
                    <td style="{{ $o->rebate_month == 0 ? 'background-color:#efefef' : '' }}">{{ $o->rebate_override_m }}</td>
                    <td>{{ $o->status_name }}</td>
                    <td>{{ $o->device_type }}</td>
                    <td>{{ $o->supplier }}</td>
                    <td>{{ $o->supplier_subsidy }}</td>
                    <td>{{ $o->supplier_make }}</td>
                    <td>{{ $o->supplier_model }}</td>
                    <td>{{ $o->supplier_cost }}</td>
                    <td>{{ $o->supplier_date }}</td>
                    <td>{{ $o->supplier_memo }}</td>
                    <td>{{ $o->buyer_name }}</td>
                    <td>{{ $o->buyer_price }}</td>
                    <td>{{ $o->buyer_date }}</td>
                    <td>{{ $o->buyer_memo }}</td>
                    <td>{{ $o->comments }}</td>
                    <td>{{ $o->c_store_id }}</td>
                    <td>{{ $o->used_trans_id }}</td>
                    <td>{{ $o->used_date }}</td>
                    <td>{{ $o->upload_date }}</td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="100" class="text-center">No Record Found</td>
            </tr>
        @endif
        </tbody>
    </table>

    <div class="text-left">
        Total {{ $data->total() }} record(s).
    </div>
    <div class="text-right">
        {{ $data->appends(Request::except('page'))->links() }}
    </div>

    <div class="modal" id="div_detail" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Upload ROK ESN</h4>
                </div>
                <div class="modal-body">

                    <form id="frm_upload" action="/admin/settings/esn/rok/upload" class="form-horizontal filter" method="post" style="padding:15px;" target="ifm_upload" enctype="multipart/form-data">
                        {{ csrf_field() }}
                        <div class="form-group">
                            <label class="col-sm-4 control-label required">Select CSV File to Upload</label>
                            <div class="col-sm-8">
                                <input type="file" class="form-control" name="sim_csv_file" id="sim_csv_file"/>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="margin-right:15px;">
                    <a class="btn btn-warning" href="/upload_template/rok_esn_upload_template.xlsx" target="_blank">Download Template</a>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="save_detail()">Upload</button>
                </div>
            </div>
        </div>
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
                    <form id="frm_batch_lookup" action="/admin/settings/esn/rok/batch-lookup" class="form-horizontal filter"
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
                                <textarea id="n_batch_esns" name="batch_esns" rows="10" style="width:100%; line-height: 150%;"
                                          onchange="count_batch_esns()"></textarea><br/>
                                Total <span id="n_batch_esns_qty">0</span> ESN(s).
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

    <div class="modal" id="div_esn_assign" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Assign eCommerce SIM</h4>
                </div>
                <div class="modal-body">

                    <form id="frm_upload" action="/admin/settings/rok-sims/assign" class="form-horizontal filter" method="post" style="padding:15px;">
                        {{ csrf_field() }}

                        <div class="form-group">
                            <label class="col-sm-4 control-label required">Clear.Assign?: </label>
                            <div class="col-sm-8">
                                <label>
                                    <input type="checkbox" id="n_clear_assign" value="Y" onclick="change_assign_status()"/> Yes, I need to clear assignment.
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label required">eCommerce.ID: </label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="n_c_store_id"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label required">ESNs to Assign: </label>
                            <div class="col-sm-8">
                                <textarea id="n_esns" rows="10" style="width:100%; line-height: 150%;" onchange="count_esns()"></textarea><br/>
                                Total <span id="n_esns_qty">0</span> ESN(s).
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="margin-right:15px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="save_esn_assign()">Assign</button>
                </div>
            </div>
        </div>
    </div>

    <div style="display:none">
        <iframe name="ifm_upload"></iframe>
    </div>
@stop
