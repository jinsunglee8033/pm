@extends('admin.layout.default')

@section('content')
    <style type="text/css">
        .modal-dialog{
            position: relative;
            #display: table; /* This is important */
            overflow-y: auto;
            overflow-x: auto;
            width: auto;
            min-width: 600px;
        }
    </style>

    <script type="text/javascript">
        var onload_events = window.onload;
        window.onload = function() {

            if (onload_events) {
                onload_events();
            }

            $( "#n_special_period_from" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $( "#n_special_period_to" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $( "#n_special_referal_period_from" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $( "#n_special_referal_period_to" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $(".tooltip").tooltip({
                html: true
            })
        }

        function search() {
            $('#frm_spiff').submit();
        }

        var current_setup_id = null;
        function show_detail(setup_id) {
            var mode = (typeof setup_id === 'undefined') ? 'new' : 'edit';
            var title = '';
            if (mode === 'new') {
                $('.edit').hide();
                title = 'New Spiff Setup';

                $('#n_id').val('');
                $('#n_carrier').val('');
                $('#n_product_id').val('');
                $('#n_denom').val('');
                $('#n_account_type').val($('#account_type').val());
                $('#n_spiff_1st').val('');
                $('#n_spiff_2nd').val('');
                $('#n_spiff_3rd').val('');
                $('#n_regular_rebate_1st').val('');
                $('#n_regular_rebate_2nd').val('');
                $('#n_regular_rebate_3rd').val('');
                $('#n_byod_rebate_1st').val('');
                $('#n_byod_rebate_2nd').val('');
                $('#n_byod_rebate_3rd').val('');
                $('#n_last_updated').val('');
            } else {
                $('.edit').show();
                title = 'Spiff Setup Detail - ' + setup_id;
            }

            $('#n_title').text(title);
            $('#div_detail').modal();


            current_setup_id = setup_id;
        }

        function load_detail(id) {
            myApp.showLoading();
            $.ajax({
                url: '/admin/settings/spiff-setup/load-detail',
                data: {
                    _token: '{!! csrf_token() !!}',
                    id: id
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {

                        $('#n_product_id').empty();
                        $('#n_product_id').append('<option value="">Please Select</option>');

                        $.each(res.products, function(i, o) {
                            $('#n_product_id').append('<option value="' + o.id + '">' + o.name + '</option>');
                        });

                        $('#n_denom').empty();
                        $('#n_denom').append('<option value="">Please Select</option>');
                        $.each(res.denoms, function(i, o) {
                            $('#n_denom').append('<option value="' + o.denom + '">$' + parseFloat(o.denom).toFixed(2) + '</option>');
                        });

                        var d = res.spiff_setup;
                        $('#n_id').val(d.id);
                        $('#n_carrier').val(d.carrier);
                        $('#n_product_id').val(d.product_id);
                        $('#n_denom').val(d.denom);
                        $('#n_account_type').val(d.account_type);
                        $('#n_spiff_1st').val(d.spiff_1st);
                        $('#n_spiff_2nd').val(d.spiff_2nd);
                        $('#n_spiff_3rd').val(d.spiff_3rd);
                        $('#n_regular_rebate_1st').val(d.regular_rebate_1st);
                        $('#n_regular_rebate_2nd').val(d.regular_rebate_2nd);
                        $('#n_regular_rebate_3rd').val(d.regular_rebate_3rd);
                        $('#n_byod_rebate_1st').val(d.byod_rebate_1st);
                        $('#n_byod_rebate_2nd').val(d.byod_rebate_2nd);
                        $('#n_byod_rebate_3rd').val(d.byod_rebate_3rd);
                        $('#n_last_updated').val(d.last_updated);

                        show_detail(id);
                    } else {
                        myApp.showError(res.msg);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            })
        }

        function save_detail() {

            var url = typeof current_setup_id === 'undefined' ? '/admin/settings/spiff-setup/add' : '/admin/settings/spiff-setup/update'

            myApp.showLoading();
            $.ajax({
                url: url,
                data: {
                    _token: '{!! csrf_token() !!}',
                    id: current_setup_id,
                    product_id: $('#n_product_id').val(),
                    denom: $('#n_denom').val(),
                    account_type: $('#n_account_type').val(),
                    spiff_1st: $('#n_spiff_1st').val(),
                    spiff_2nd: $('#n_spiff_2nd').val(),
                    spiff_3rd: $('#n_spiff_3rd').val(),
                    regular_rebate_1st: $('#n_regular_rebate_1st').val(),
                    regular_rebate_2nd: $('#n_regular_rebate_2nd').val(),
                    regular_rebate_3rd: $('#n_regular_rebate_3rd').val(),
                    byod_rebate_1st: $('#n_byod_rebate_1st').val(),
                    byod_rebate_2nd: $('#n_byod_rebate_2nd').val(),
                    byod_rebate_3rd: $('#n_byod_rebate_3rd').val(),
                    template: $('#template').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        myApp.showSuccess('Your request has been processed successfully!', function() {
                            search();
                        });

                    } else {
                        myApp.showError(res.msg);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });
        }

        function load_product() {
            myApp.showLoading();
            $.ajax({
                url: '/admin/settings/spiff-setup/load-product',
                data: {
                    _token: '{!! csrf_token() !!}',
                    carrier: $('#n_carrier').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {

                        $('#n_product_id').empty();
                        $('#n_product_id').append('<option value="">Please Select</option>');
                        $.each(res.products, function(i, o) {
                            $('#n_product_id').append('<option value="' + o.id + '">' + o.name + '</option>');
                        });
                    } else {
                        myApp.showError(res.msg);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            })
        }

        function load_denoms() {
            myApp.showLoading();
            $.ajax({
                url: '/admin/settings/spiff-setup/load-denoms',
                data: {
                    _token: '{!! csrf_token() !!}',
                    product_id: $('#n_product_id').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {

                        $('#n_denom').empty();
                        $('#n_denom').append('<option value="">Please Select</option>');
                        $.each(res.denoms, function(i, o) {
                            $('#n_denom').append('<option value="' + o.denom + '">$' + parseFloat(o.denom).toFixed(2) + '</option>');
                        });
                    } else {
                        myApp.showError(res.msg);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            })
        }

        function excel_download() {
            $('#excel').val('Y');
            $('#frm_spiff').submit();
            myApp.hideLoading();
            $('#excel').val('');
        }
    </script>


    <h4>Spiff & Rebate Setup</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_spiff" class="form-horizontal" action="/admin/settings/spiff-setup" method="post" onsubmit="myApp.showLoading();">
            {{ csrf_field() }}
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Account.Type</label>
                        <div class="col-md-8">
                            <select id="account_type" name="account_type" class="form-control" onchange="$('#frm_spiff').submit()">
                                <option value="">All</option>
                                <option value="M" {{ old('account_type', $account_type) == 'M' ? 'selected' : '' }}>Master</option>
                                <option value="D" {{ old('account_type', $account_type) == 'D' ? 'selected' : '' }}>Distributor</option>
                                <option value="S" {{ old('account_type', $account_type) == 'S' ? 'selected' : '' }}>Sub-Agent</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Carrier</label>
                        <div class="col-md-8">
                            <select name="carrier" class="form-control" onchange="$('#frm_spiff').submit()">
                                <option value="" {{ old('carrier', $carrier) == '' ? 'selected' : '' }}>All</option>
                                <option value="H2O" {{ old('carrier', $carrier) == 'H2O' ? 'selected' : '' }}>H2O</option>
                                <option value="Lyca" {{ old('carrier', $carrier) == 'Lyca' ? 'selected' : '' }}>Lyca</option>
                                <option value="AT&T" {{ old('carrier', $carrier) == 'AT&T' ? 'selected' : '' }}>AT&T</option>
                                <option value="GEN Mobile" {{ old('carrier', $carrier) == 'GEN Mobile' ? 'selected' : ''
                                }}>GEN Mobile</option>
                                <option value="FreeUP" {{ old('carrier', $carrier) == 'FreeUP' ? 'selected' : '' }}>FreeUP</option>
                                <option value="Liberty Mobile" {{ old('carrier', $carrier) == 'Liberty Mobile' ? 'selected' : '' }}>Liberty Mobile</option>
                                <option value="Boom Mobile" {{ old('carrier', $carrier) == 'Boom Mobile' ? 'selected' : '' }}>Boom Mobile</option>
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
                                        <option value="{{ $o->id }}" {{ old('product', $product_id) == $o->id ? 'selected' : '' }}>{{ $o->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Amount($)</label>
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
                @if (!empty($account_type))
                <div class="col-md-8" style="{{ empty($account_type) ? 'display:none' : '' }}">
                    <div class="form-group">
                        <label class="col-md-2 control-label">Template</label>
                        <div class="col-md-4">
                            <select id="template" name="template" class="form-control" onchange="$('#frm_spiff').submit()">
                                <option value="">Not setup</option>
                                <option value="default" {{ old('template', $template) == 'NULL' ?
                                         'selected' : '' }}>Default Template</option>
                                @if (!empty($templates) && count($templates) > 0)
                                    @foreach ($templates as $t)
                                        <option value="{{ $t->id }}" {{ old('template', $template) == $t->id ?
                                         'selected' : '' }}>{{ $t->template }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        @if (!empty($template))
                        <div class="col-md-6">
                            <input id="master_ids" class="form-control" onchange="" value="{{
                            $template_owners  }}" placeholder="Master IDs" disabled>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            <div class="row">
                <div class="col-md-12 text-right">
                    <div class="form-group">
                        <div class="col-md-12 text-right">
                            <input type="hidden" id="excel" name="excel" value="">
                            <button type="submit" class="btn btn-primary btn-sm" id="btn_search">Search</button>
                            <button type="button" class="btn btn-default btn-sm" onclick="excel_download()">Download</button>
                            @if (!empty($account_type))
                            <button type="button" class="btn btn-default btn-sm" onclick="show_detail()">Add New</button>
                            <button type="button" class="btn btn-default btn-sm" onclick="add_template()">Add Template</button>
                            @if (!empty($template))
                            <button type="button" class="btn btn-default btn-sm" onclick="edit_template()">Edit Template
                            </button>
                            @endif
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
            <th>ID</th>
            <th>Product</th>
            <th>Amount($)</th>
            <th>Account.Type</th>
            <th>Spiff.1st</th>
            <th>Spiff.2nd</th>
            <th>Spiff.3rd</th>
            <th>Template</th>
            @if(Auth::user()->account_type == 'L' && (getenv('APP_ENV') == 'production' && in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']) || getenv('APP_ENV') == 'local'))
            <th>Action</th>
            @endif
            <th>Last.Updated</th>
        </tr>
        </thead>
        <tbody>
        @if (isset($data) && count($data) > 0)
            @foreach ($data as $o)
                <tr>
                    <td><a href="javascript:load_detail('{{$o->id}}')">{{ $o->id }}</a></td>
                    <td>{{ $o->product }}</td>
                    <td>${{ number_format($o->denom, 2) }}</td>
                    <td>{!! Helper::get_hierarchy_img($o->account_type) !!} {{ $o->account_type_name }}</td>
                    <td>${{ number_format($o->spiff_1st, 2) }}</td>
                    <td>${{ number_format($o->spiff_2nd, 2) }}</td>
                    <td>${{ number_format($o->spiff_3rd, 2) }}</td>
                    <td>{{ $o->template_name }}</td>

                    @if(Auth::user()->account_type == 'L' && (getenv('APP_ENV') == 'production' && in_array(Auth::user()->user_id, ['thomas','admin', 'system']) || getenv('APP_ENV') == 'local'))
                    <td><button onclick="load_special('{{ $o->carrier }}', '{{ $o->product_id }}', '{{ $o->product
                    }}', '{{ number_format($o->denom, 2) }}', '{{ $o->account_type }}', '{{ $o->account_type_name }}', {{ $o->spiff_1st }})">Special Spiff</button></td>
                    @endif
                    <td>{{ $o->last_updated }}</td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="20" class="text-center">No Record Found</td>
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

    <script>
        function edit_template() {
            $('#e_template_id').val($('#template').val());
            $('#e_template_name').val($('#template option:selected').text());
            $('#e_master_ids').val($('#master_ids').val());
            $('#div_spiff_template').modal();
        }

        function add_template() {
            $('#e_template_id').val();
            $('#e_master_ids').val();
            $('#div_spiff_template').modal();
        }
    </script>
    <div class="modal" id="div_spiff_template" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">

                <form action="/admin/settings/spiff-setup/add/template" method="post">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                    aria-hidden="true">x</span></button>
                        <h4 class="modal-title" id="n_title">Spiff Template</h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-horizontal">
                            {{ csrf_field() }}

                            <input type="hidden" name="account_type" value="{{ $account_type }}">
                            <input type="hidden" name="template_id" id="e_template_id">

                            <div class="form-group edit">
                                <label class="col-sm-4 control-label">Name</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" name="template_name" id="e_template_name"/>
                                </div>
                            </div>

                            <div class="form-group edit">
                                <label class="col-sm-4 control-label">Master IDs</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" name="master_ids" id="e_master_ids"/>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="margin-right:15px;">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal" id="div_detail" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="n_title">Spiff Detail</h4>
                </div>
                <div class="modal-body">

                    <div class="form-horizontal">
                        {{ csrf_field() }}
                        <div class="form-group edit">
                            <label class="col-sm-4 control-label">ID</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="n_id" disabled/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">Carrier</label>
                            <div class="col-sm-8">
                                <select id="n_carrier" class="form-control" onchange="load_product()">
                                    <option value="">Please Select</option>
                                    <option value="H2O">H2O</option>
                                    <option value="Lyca">Lyca</option>
                                    <option value="AT&T">AT&T</option>
                                    <option value="GEN Mobile">GEN Mobile</option>
                                    <option value="FreeUP">FreeUP</option>
                                    <option value="Liberty Mobile">Liberty Mobile</option>
                                    <option value="Boom Mobile">Boom Mobile</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">Product</label>
                            <div class="col-sm-8">
                                <select id="n_product_id" class="form-control" onchange="load_denoms()">
                                    <option value="">Please Select</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">Amount($)</label>
                            <div class="col-sm-8">
                                <select id="n_denom" class="form-control">
                                    <option value="">Please Select</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">Account.Type</label>
                            <div class="col-sm-8">
                                <select id="n_account_type" class="form-control" disabled>
                                    <option value="">Please Select</option>
                                    <option value="M" {{ $account_type == 'M' ? 'selected' : '' }}>Master</option>
                                    <option value="D" {{ $account_type == 'D' ? 'selected' : '' }}>Distributor</option>
                                    <option value="S" {{ $account_type == 'S' ? 'selected' : '' }}>Sub-Agent</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">Spiff.1st($)</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="n_spiff_1st"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">Spiff.2nd($)</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="n_spiff_2nd"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">Spiff.3rd($)</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="n_spiff_3rd"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">Regular.Rebate.1st</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="n_regular_rebate_1st"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">Regular.Rebate.2nd</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="n_regular_rebate_2nd"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">Regular.Rebate.3rd</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="n_regular_rebate_3rd"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">BYOD.Rebate.1st</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="n_byod_rebate_1st"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">BYOD.Rebate.2nd</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="n_byod_rebate_2nd"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">BYOD.Rebate.3rd</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="n_byod_rebate_3rd"/>
                            </div>
                        </div>
                        <div class="form-group edit">
                            <label class="col-sm-4 control-label">Last.Updated</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="n_last_updated" disabled/>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer" style="margin-right:15px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="save_detail()">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="div_special" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="n_title">Special Spiff</h4>
                </div>
                <div class="modal-body">

                    <div class="form-horizontal">
                        <input type="hidden" id="n_special_product_id">
                        <input type="hidden" id="n_special_denom">
                        <input type="hidden" id="n_special_account_type">

                        <div class="form-group">
                            <label class="col-sm-4 control-label">Product</label>
                            <div class="col-sm-8">
                                <span id="special_product"></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">Amount($)</label>
                            <div class="col-sm-8">
                                $ <span id="special_denom"></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">Account.Type</label>
                            <div class="col-sm-8">
                                <span id="special_account_type"></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">1st Spiff</label>
                            <div class="col-sm-8">
                                $ <span id="special_instance_spiff"></span>
                            </div>
                        </div>

                    </div>

                    <hr>
                    <ul class="nav nav-tabs" role="tablist">
                        <li role="presentation" class="active"><a href="#special1" aria-controls="special1"
                                                                  role="tab"
                                                                  data-toggle="tab">Special 1</a></li>
                        <li role="presentation"><a href="#special2" aria-controls="special2" role="tab"
                                                   data-toggle="tab">Special 2</a></li>
                    </ul>

                    <!-- Tab panes -->
                    <div class="tab-content">

                        <div role="tabpanel" class="tab-pane active" id="special1" style="padding:15px;">

                            <table class="table table-bordered table-hover table-condensed">
                                <thead>
                                    <tr style="font-size: 12px;">
                                        <th style="text-align:center;">ID</th>
                                        <th style="text-align:left;">Name</th>
                                        <th style="text-align:left;">From ~ To</th>
                                        <th style="text-align:center;">Spiff</th>
                                        <th style="text-align:center;">Terms</th>
                                        <th style="text-align:center;">Include</th>
                                        <th style="text-align:center;">Exclude</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="special_details">

                                </tbody>
                                <tfoot>
                                    <tr style="font-size: 12px;">
                                        <td>New</td>
                                        <td><input type="text" size="35" id="n_special_name"></td>
                                        <td>
                                            <input type="text" style="width:80px; float:left;" id="n_special_period_from"/>
                                            <span class="control-label" style="margin-left:5px; float:left;"> ~ </span>
                                            <input type="text" style="width:80px; margin-left: 5px; float:left;"
                                                   id="n_special_period_to"/>
                                        </td>
                                        <td><input type="text" size="5" id="n_special_spiff"></td>
                                        <td>
                                            <select id="n_terms">
                                                <option value="">None</option>
                                                <option value="Byos">BYOS SIM</option>
                                                <option value="Port">Port-In</option>
                                                <option value="NonBYOS">NonBYOS</option>
                                                <option value="NonBYOD">NonBYOD</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" style="width:120px;" id="n_special_include">
                                        </td>
                                        <td><input type="text" style="width:120px;"
                                                   id="n_special_exclude"></td>
                                        <td><button type="button" class="btn btn-primary btn-xs" onclick="add_special()">Add</button></td>
                                    </tr>
                                    <tr>
                                        <td colspan="8">-</td>
                                    </tr>
                                    <tr style="font-size: 12px;">
                                        <td colspan="3" style="text-align: right;"><strong>Max Special Spiff Today: </strong></td>
                                        <td style="text-align:right;"><strong><span id="max_special_spiff"></span></strong></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr style="font-size: 12px;">
                                        <td colspan="3" style="text-align: right;"><strong>Max Total Spiff Today: </strong></td>
                                        <td style="text-align:right;"><strong><span id="max_total_spiff"></span></strong></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div role="tabpanel" class="tab-pane" id="special2" style="padding:15px;">

                            <table class="table table-bordered table-hover table-condensed">
                                <thead>
                                <tr style="font-size: 12px;">
                                    <th style="text-align:center;">ID</th>
                                    <th style="text-align:left;">Name</th>
                                    <th style="text-align:left;">From ~ To</th>
                                    <th style="text-align:center;">Spiff</th>
                                    <th style="text-align:center;">Include</th>
                                    <th style="text-align:center;">Payto</th>
                                    <th style="text-align:center;">Pay Amt</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody id="special_referal_details">

                                </tbody>
                                <tfoot>
                                    <tr style="font-size: 12px;">
                                        <td>New</td>
                                        <td><input type="text" size="35" id="n_special_referal_name"></td>
                                        <td>
                                            <input type="text" style="width:80px; float:left;" id="n_special_referal_period_from"/>
                                            <span class="control-label" style="margin-left:5px; float:left;"> ~ </span>
                                            <input type="text" style="width:80px; margin-left: 5px; float:left;"
                                                   id="n_special_referal_period_to"/>
                                        </td>
                                        <td><input type="text" size="5" id="n_special_referal_spiff"></td>
                                        <td>
                                            <input type="text" style="width:120px;" id="n_special_referal_include">
                                        </td>
                                        <td><input type="text" style="width:120px;" id="n_special_pay_to"></td>
                                        <td><input type="text" size="5" id="n_special_pay_to_amt"></td>
                                        <td><button type="button" class="btn btn-primary btn-xs"
                                                    onclick="add_referal_special()">Add</button></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="margin-right:15px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>



    <form id="frm_special_spiff" class="form-horizontal" action="/admin/settings/spiff-setup/special" method="post" target="_blank"
          style="display: none;">
        {{ csrf_field() }}
        <input type="hidden" id="ss_special_carrier" name="carrier">
        <input type="hidden" id="ss_special_product_id" name="product_id">
        <input type="hidden" id="ss_special_denom" name="denom">
        <input type="hidden" id="ss_special_account_type" name="account_type">
    </form>
@stop
