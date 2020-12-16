@extends('admin.layout.default')

@section('content')

    <script type="text/javascript">
        var onload_events = window.onload;
        window.onload = function () {

            if (onload_events) {
                onload_events();
            }
        };

        function excel_export() {
            $('#excel').val('Y');
            $('#frm_search').submit();
            myApp.hideLoading();
            $('#excel').val('');
        }

        function show_mapping() {
            $('#div_mapping').modal();
        }

        function save_mapping() {
            myApp.showLoading();
            $.ajax({
                url: '/admin/settings/mapping/freeup/bind',
                data: {
                    _token: '{!! csrf_token() !!}',
                    clear: $('#n_clear_assign').is(':checked') ? 'Y' : 'N',
                    binds: $('#n_binds').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function (res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        myApp.showSuccess('Your request has been successfully processed!', function () {
                            $('#frm_search').submit();
                        });
                    } else {
                        myApp.showError(res.msg);
                    }
                }
            });
        }

        function count_mappings() {
            var binds = $.trim($('#n_binds').val()).split("\n");
            $('#n_binds_qty').text(binds.length);
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


    <h4>FreeUP MAPPING</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post" onsubmit="myApp.showLoading();">
            {{ csrf_field() }}
            <input type="hidden" name="excel" id="excel" value=""/>
            <input type="hidden" id="id" name="id"/>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">SIM #</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="sim" value="{{ $sim }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">ESN/IMEI</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="esn" value="{{ $esn }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <div class="col-md-12 text-right">
                            <button type="submit" class="btn btn-primary btn-sm" id="btn_search">Search</button>
                            <button type="button" class="btn btn-info btn-sm" onclick="excel_export()">Download
                            </button>
                            <button type="button" class="btn btn-blue btn-sm" onclick="show_batch_lookup()">Batch Lookup</button>
                            @if(Auth::check() && in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']))
                                <button type="button" class="btn btn-default btn-sm" onclick="show_mapping()">New Mapping
                                </button>
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
                <th colspan="4">SIM</th>
                <th colspan="4">ESN</th>
                <th rowspan="2" style="text-align: center">Status</th>
                <th rowspan="2">Upload.Date</th>
            </tr>
            <tr>
                <th>#</th>
                <th>Carrier</th>
                <th>Amount</th>
                <th>RTR.Month</th>
                <th>#</th>
                <th>Carrier</th>
                <th>Amount</th>
                <th>RTR.Month</th>
            </tr>
        </thead>
        <tbody>
            @if (isset($data) && count($data) > 0)
                @foreach ($data as $o)
                    <tr>
                        <td>{{ $o->sim }}</td>
                        <td>{{ empty($o->sim_obj) ? 'Empty' : $o->sim_obj->sub_carrier }}</td>
                        <td>{{ empty($o->sim_obj) ? 'Empty' : $o->sim_obj->amount }}</td>
                        <td>{{ empty($o->sim_obj) ? 'Empty' : $o->sim_obj->rtr_month }}</td>
                        <td>{{ $o->esn }}</td>
                        <td>{{ empty($o->esn_obj) ? 'Empty' : $o->esn_obj->sub_carrier }}</td>
                        <td>{{ empty($o->esn_obj) ? 'Empty' : $o->esn_obj->amount }}</td>
                        <td>{{ empty($o->esn_obj) ? 'Empty' : $o->esn_obj->rtr_month }}</td>
                        <td style="text-align: center">{{ $o->status }}</td>
                        <td>{{ $o->upload_date }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="50" class="text-center">No Record Found</td>
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

    <div class="modal" id="div_mapping" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Mapping SIM and ESN</h4>
                </div>
                <div class="modal-body">

                    <form id="frm_upload" action="" class="form-horizontal filter"
                          method="post" style="padding:15px;">
                        {{ csrf_field() }}

                        <div class="form-group">
                            <label class="col-sm-4 control-label required">Clear.Mapping?: </label>
                            <div class="col-sm-8">
                                <label>
                                    <input type="checkbox" id="n_clear_assign" value="Y"/> Yes, I need to clear mapping.
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-4 control-label required">SIM ESN Mapping (sim,esn): </label>
                            <div class="col-sm-8">
                                <textarea id="n_binds" rows="10" style="width:100%; line-height: 150%;"
                                          onchange="count_mappings()"></textarea><br/>
                                Total <span id="n_binds_qty">0</span> Mapping(s).
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="margin-right:15px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="save_mapping()">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <div style="display:none">
        <iframe name="ifm_upload"></iframe>
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
                    <form id="frm_batch_lookup" action="/admin/settings/mapping/freeup/batch-lookup" class="form-horizontal filter"
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
                                <input type="radio" name="batch_res_type" value="S" checked> SIM
                                <input type="radio" name="batch_res_type" value="D"> Device.ID
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
