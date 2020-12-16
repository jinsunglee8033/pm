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

            @foreach ($specials as $s)
            $( "#n_special_period_from_{{ $s->id }}").datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $( "#n_special_period_to_{{ $s->id }}").datetimepicker({
                format: 'YYYY-MM-DD'
            });
            @endforeach

            $(".tooltip").tooltip({
                html: true
            })
        }

        function add_special() {

            myApp.showLoading();
            $.ajax({
                url: '/admin/settings/spiff-setup/special/add',
                data: {
                    _token: '{!! csrf_token() !!}',
                    product_id: $('#product_id').val(),
                    denom: $('#denom').val(),
                    account_type: $('#account_type').val(),
                    name: $('#n_special_name').val(),
                    note1: $('#n_special_note1').val(),
                    note2: $('#n_special_note2').val(),
                    period_from: $('#n_special_period_from').val(),
                    period_to: $('#n_special_period_to').val(),
                    terms: $('#n_terms').val(),
                    maxqty: $('#n_maxqty').val(),
                    spiff: $('#n_special_spiff').val(),
                    include: $('#n_special_include').val(),
                    exclude: $('#n_special_exclude').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if (res.code == '0') {
                        $('#frm_spiff').submit();
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

        function add_referal_special() {

            myApp.showLoading();
            $.ajax({
                url: '/admin/settings/spiff-setup/special/add',
                data: {
                    _token: '{!! csrf_token() !!}',
                    product_id: $('#product_id').val(),
                    denom: $('#denom').val(),
                    account_type: $('#account_type').val(),
                    name: $('#n_special_referal_name').val(),
                    note1: $('#n_special_referal_note1').val(),
                    note2: $('#n_special_referal_note2').val(),
                    period_from: $('#n_special_referal_period_from').val(),
                    period_to: $('#n_special_referal_period_to').val(),
                    terms: 'referal',
                    spiff: $('#n_special_referal_spiff').val(),
                    include: $('#n_special_referal_include').val(),
                    pay_to: $('#n_special_pay_to').val(),
                    pay_to_amt: $('#n_special_pay_to_amt').val(),
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if (res.code == '0') {
                        $('#frm_spiff').submit();
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

        function update_special(id) {

            myApp.showLoading();
            $.ajax({
                url: '/admin/settings/spiff-setup/special/update',
                data: {
                    _token: '{!! csrf_token() !!}',
                    id: id,
                    name: $('#n_special_name_' + id).val(),
                    note1: $('#n_special_note1_' + id).val(),
                    note2: $('#n_special_note2_' + id).val(),
                    period_from: $('#n_special_period_from_' + id).val(),
                    period_to: $('#n_special_period_to_' + id).val(),
                    spiff: $('#n_special_spiff_' + id).val(),
                    terms: $('#n_special_terms_' + id).val(),
                    maxqty: $('#n_special_maxqty_' + id).val(),
                    include: $('#n_special_include_' + id).val(),
                    exclude: $('#n_special_exclude_' + id).val(),
                    pay_to: $('#n_special_pay_to_' + id).val(),
                    pay_to_amt: $('#n_special_pay_to_amt_' + id).val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if (res.code == '0') {
                        $('#frm_spiff').submit();
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

        function update_referal_special(id) {

            myApp.showLoading();
            $.ajax({
                url: '/admin/settings/spiff-setup/special/update',
                data: {
                    _token: '{!! csrf_token() !!}',
                    id: id,
                    name: $('#n_special_name_' + id).val(),
                    note1: $('#n_special_note1_' + id).val(),
                    note2: $('#n_special_note2_' + id).val(),
                    period_from: $('#n_special_period_from_' + id).val(),
                    period_to: $('#n_special_period_to_' + id).val(),
                    spiff: $('#n_special_spiff_' + id).val(),
                    include: $('#n_special_include_' + id).val(),
                    pay_to: $('#n_special_pay_to_' + id).val(),
                    pay_to_amt: $('#n_special_pay_to_amt_' + id).val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if (res.code == '0') {
                        $('#frm_spiff').submit();
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

        function excel_download() {
            $('#excel').val('Y');
            $('#frm_spiff').submit();
            myApp.hideLoading();
            $('#excel').val('');
        }

        function carrier_changed() {
            $('#product_id').val('');
            $('#denom').val('');
            $('#frm_spiff').submit();
        }

        function product_changed() {
            $('#denom').val('');
            $('#frm_spiff').submit();
        }

        function terms_changed() {
            var terms = $('#n_terms').val();
            if (terms == 'Byos') {
                $('#n_maxqty').show();
            } else {
                $('#n_maxqty').hide();
            }
        }
    </script>


    <h4>Spiff & Rebate Setup / Special Spiff</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_spiff" class="form-horizontal" action="/admin/settings/spiff-setup/special" method="post"
              onsubmit="myApp.showLoading();">
            {{ csrf_field() }}
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Carrier</label>
                        <div class="col-md-8">
                            <select name="carrier" class="form-control" onchange="carrier_changed()">
                                <option value="" {{ old('carrier', $carrier) == '' ? 'selected' : '' }}>All</option>
                                @foreach ($carriers as $c)
                                    <option value="{{ $c->name }}" {{ old('carrier', $carrier) == $c->name ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach

{{--                                <option value="AT&T" {{ old('carrier', $carrier) == 'AT&T' ? 'selected' : '' }}>AT&T</option>--}}
{{--                                <option value="FreeUP" {{ old('carrier', $carrier) == 'FreeUP' ? 'selected' : '' }}>FreeUP</option>--}}
{{--                                <option value="H2O" {{ old('carrier', $carrier) == 'H2O' ? 'selected' : '' }}>H2O</option>--}}
{{--                                <option value="Lyca" {{ old('carrier', $carrier) == 'Lyca' ? 'selected' : ''}}>Lyca</option>--}}
{{--                                <option value="GEN Mobile" {{ old('carrier', $carrier) == 'GEN Mobile' ? 'selected' : ''}}>GEN Mobile</option>--}}
{{--                                <option value="Liberty" {{ old('carrier', $carrier) == 'Liberty Mobile' ? 'selected' : ''}}>Liberty Mobile</option>--}}
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Product</label>
                        <div class="col-md-8">
                            <select id="product_id" name="product_id" class="form-control" onchange="product_changed()">
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
                        <label class="col-md-4 control-label">Amount($)</label>
                        <div class="col-md-8">
                            <select id="denom" name="denom" class="form-control" onchange="$('#frm_spiff').submit()">
                                <option value="">All</option>
                                @if (!empty($denoms) && count($denoms) > 0)
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
                        <label class="col-md-4 control-label">Account.Type</label>
                        <div class="col-md-8">
                            <select name="account_type" id="account_type" class="form-control" onchange="$
                            ('#frm_spiff').submit()">
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
                    </div>
                </div>

                <div class="col-md-4 text-right">
                    <div class="form-group">
                        <div class="col-md-12 text-right">
                            <button type="submit" class="btn btn-primary btn-sm" id="btn_search">Search</button>
                            <input type="hidden" id="excel" name="excel" value="">
                            <button type="button" class="btn btn-default btn-sm" onclick="excel_download()">Download</button>
                        </div>
                    </div>
                </div>

            </div>
        </form>
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

        <div role="tabpanel" class="tab-pane active" id="special1" style="padding:5px 0;">

            <table class="table table-bordered table-hover table-condensed">
                <thead>
                <tr style="font-size: 12px;">
                    <th style="text-align:center;">ID</th>
                    <th style="text-align:center;">Acct<br>Type</th>
                    <th style="text-align:center;">Product</th>
                    <th style="text-align:center;">Amt</th>
                    <th style="text-align:left;">Name</th>
                    <th style="text-align:left;">Note 1</th>
                    <th style="text-align:left;">Note 2</th>
                    <th style="width: 180px;text-align:left;">From ~ To</th>
                    <th style="text-align:center;">Spiff</th>
                    <th style="text-align:center;">Terms</th>
                    <th style="text-align:center;">Include</th>
                    <th style="text-align:center;">Exclude</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($specials as $special)
                @if ($special->terms != 'referal')
                <tr style="font-size: 12px;">
                    <td style="width: 50px;text-align:center;">{{ $special->id }}</td>
                    <td style="width: 50px;text-align:center;">{{ $special->account_type }}</td>
                    <td>{{ $special->prod_name }}</td>
                    <td style="text-align: right;">{{ $special->denom }}</td>
                    <td><textarea id="n_special_name_{{ $special->id }}">{{ $special->name }}</textarea></td>
                    <td><textarea id="n_special_note1_{{ $special->id }}">{{ $special->note1 }}</textarea></td>
                    <td><textarea id="n_special_note2_{{ $special->id }}">{{ $special->note2 }}</textarea></td>
                    <td style="width: 200px;text-align:left;">
                        <input type="text" style="width:80px;" id="n_special_period_from_{{ $special->id }}"
                               value="{{ $special->period_from }}"> ~
                        <input type="text" style="width:80px;" id="n_special_period_to_{{ $special->id }}"
                               value="{{ $special->period_to }}">
                    </td>
                    <td><input type="text" style="width:60px;" id="n_special_spiff_{{ $special->id }}" value="{{ $special->spiff }}"></td>
                    <td style="text-align:center;">
                        <select id="n_special_terms_{{ $special->id }}">
                            <option value="" {{ empty($special->terms) ? 'selected' : '' }}>All</option>
                            <option value="Byos" {{ $special->terms == 'Byos' ? 'selected' : '' }}>BYOS SIM</option>
                            <option value="Port" {{ $special->terms == 'Port' ? 'selected' : '' }}>Port-In</option>
                            <option value="NonBYOS" {{ $special->terms == 'NonBYOS' ? 'selected' : '' }}>NonBYOS</option>
                            <option value="NonBYOD" {{ $special->terms == 'NonBYOD' ? 'selected' : '' }}>NonBYOD</option>
                            <option value="StaticSIM" {{ $special->terms == 'StaticSIM' ? 'selected' : '' }}>Static SIM</option>
                        </select>
                        <input type="text" style="width:80px;"
                               id="n_special_maxqty_{{ $special->id }}" value="{{
                        $special->maxqty }}" placeholder="Max Qty">
                    </td>
                    <td><input type="text" style="width:120px;" id="n_special_include_{{ $special->id }}"
                               value="{{ $special->include }}"></td>
                    <td><input type="text" style="width:120px;" id="n_special_exclude_{{ $special->id }}"
                               value="{{ $special->exclude }}"></td>
                    <td><button type="button" class="btn btn-primary btn-xs" onclick="update_special({{ $special->id }})">Update</button></td>
                </tr>
                @endif
                @endforeach
                </tbody>
                <tfoot>
                @if (!empty($denom) && !empty($account_type))
                <tr style="font-size: 12px;">
                    <td colspan="4" style="text-align: right;"><strong>New:</strong></td>
                    <td><textarea id="n_special_name"></textarea></td>
                    <td><textarea id="n_special_note1"></textarea></td>
                    <td><textarea id="n_special_note2"></textarea></td>
                    <td>
                        <input type="text" style="width:80px; float:left;" id="n_special_period_from"/>
                        <span class="control-label" style="margin-left:5px; float:left;"> ~ </span>
                        <input type="text" style="width:80px; margin-left: 5px; float:left;"
                               id="n_special_period_to"/>
                    </td>
                    <td><input type="text" size="5" id="n_special_spiff"></td>
                    <td style="text-align:center;" onchange="terms_changed()">
                        <select id="n_terms">
                            <option value="">All</option>
                            <option value="Byos">BYOS SIM</option>
                            <option value="Port">Port-In</option>
                            <option value="NonBYOS">NonBYOS</option>
                            <option value="NonBYOD">NonBYOD</option>
                            <option value="StaticSIM">Static SIM</option>
                        </select>
                        <input type="text" style="width:80px;display: none;" id="n_maxqty" placeholder="Max Qty">
                    </td>
                    <td>
                        <input type="text" style="width:120px;" id="n_special_include">
                    </td>
                    <td><input type="text" style="width:120px;" id="n_special_exclude"></td>
                    <td><button type="button" class="btn btn-primary btn-xs" onclick="add_special()">Add</button></td>
                </tr>
                @endif
                </tfoot>
            </table>
        </div>
        <div role="tabpanel" class="tab-pane" id="special2" style="padding:5px 0;">

            <table class="table table-bordered table-hover table-condensed">
                <thead>
                <tr style="font-size: 12px;">
                    <th style="text-align:center;">ID</th>
                    <th style="text-align:center;">Acct<br>Type</th>
                    <th style="text-align:center;">Product</th>
                    <th style="text-align:center;">Amt</th>
                    <th style="text-align:left;">Name</th>
                    <th style="text-align:left;">Note 1</th>
                    <th style="text-align:left;">Note 2</th>
                    <th style="text-align:left;">From ~ To</th>
                    <th style="text-align:center;">Spiff</th>
                    <th style="text-align:center;">Include</th>
                    <th style="text-align:center;">Payto</th>
                    <th style="text-align:center;">Pay Amt</th>
                    <th></th>
                </tr>
                </thead>
                <tbody id="special_referal_details">
                @foreach($specials as $special)
                @if ($special->terms == 'referal')
                <tr style="font-size: 12px;">
                    <td style="width: 50px;text-align:center;">{{ $special->id }}</td>
                    <td style="width: 50px;text-align:center;">{{ $special->account_type }}</td>
                    <td>{{ $special->prod_name }}</td>
                    <td style="text-align: right;">{{ $special->denom }}</td>
                    <td><textarea id="n_special_name_{{ $special->id }}">{{ $special->name }}</textarea></td>
                    <td><textarea id="n_special_note1_{{ $special->id }}">{{ $special->note1 }}</textarea></td>
                    <td><textarea id="n_special_note2_{{ $special->id }}">{{ $special->note2 }}</textarea></td>
                    <td>
                        <input type="text" style="width:80px;" id="n_special_period_from_{{ $special->id }}"
                               value="{{ $special->period_from }}"> ~
                        <input type="text" style="width:80px;" id="n_special_period_to_{{ $special->id }}"
                               value="{{ $special->period_to }}">
                    </td>
                    <td><input type="text" style="width:60px;" id="n_special_spiff_{{ $special->id }}" value="{{
                    $special->spiff }}"></td>
                    <td><input type="text" style="width:120px;" id="n_special_include_{{ $special->id }}"
                               value="{{ $special->include }}"></td>
                    <td><input type="text" style="width:120px;" id="n_special_pay_to_{{ $special->id }}"
                               value="{{ $special->pay_to }}"></td>
                    <td><input type="text" style="width:60px;" id="n_special_pay_to_amt_{{ $special->id }}"
                               value="{{ $special->pay_to_amt }}"></td>
                    <td><button type="button" class="btn btn-primary btn-xs" onclick="update_referal_special({{ $special->id }})">Update</button></td>
                </tr>
                @endif
                @endforeach

                </tbody>
                <tfoot>
                @if (!empty($denom) && !empty($account_type))
                <tr style="font-size: 12px;">
                    <td colspan="4" style="text-align: right;"><strong>New:</strong></td>
                    <td><textarea id="n_special_referal_name"></textarea></td>
                    <td><textarea id="n_special_referal_note1"></textarea></td>
                    <td><textarea id="n_special_referal_note2"></textarea></td>
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
                @endif
                </tfoot>
            </table>
        </div>
    </div>

    <div class="text-right">
        {{ $specials->appends(Request::except('page'))->links() }}
    </div>
@stop
