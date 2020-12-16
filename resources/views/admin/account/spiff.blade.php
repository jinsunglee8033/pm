@extends('admin.layout.default')

@section('content')
    <style type="text/css">

        input[type=text]:disabled {
            background-color: #efefef;
        }
    </style>

    <script type="text/javascript">
        window.onload = function() {
            $( "#sdate" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });
            $( "#edate" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });
        };

        function excel_download() {
            $('#excel').val('Y');
            $('#frm_search').submit();
        }

        function onchange_carrier() {
            $('#excel').val('');

            $('#product_id').val('');
            $('#product_id').empty();

            $('#denom').val('');
            $('#denom').empty();

            $('#frm_search').submit();
        }

        function onchange_product() {
            $('#excel').val('');

            $('#denom').val('');
            $('#denom').empty();

            $('#frm_search').submit();
        }

        function search() {
            $('#excel').val('');
            $('#frm_search').submit();
        }
    </script>

    <h4>Spiff List</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form class="form-horizontal" id="frm_search" name="frm_search" method="post" action="/admin/account/spiff">
            {{ csrf_field() }}

            <input type="hidden" name="excel" id="excel">

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Carrier</label>
                        <div class="col-md-8">
                            <select name="carrier" class="form-control" onchange="onchange_carrier()">
                                <option value="" {{ old('carrier', $carrier) == '' ? 'selected' : '' }}>All</option>
                                @foreach ($carriers as $o)
                                    <option value="{{ $o->name }}" {{ $carrier == $o->name ? 'selected' : '' }}>{{ $o->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Product</label>
                        <div class="col-md-8">
                            <select id="product_id" name="product_id" class="form-control" onchange="onchange_product()">
                                <option value="">All</option>
                                @if (count($products) > 0)
                                    @foreach ($products as $o)
                                        <option value="{{ $o->id }}" {{ old('product_id', $product_id) == $o->id ?
                                        'selected' : '' }}>{{ $o->name }}</option>
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
                            <select id="denom" name="denom" class="form-control" onchange="$('#frm_spiff').submit()">
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
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Account</label>
                        <div class="col-md-8">
                            <input type="text" name="account" value="{{ $account }}" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Template</label>
                        <div class="col-md-8">
                            <input type="text" name="template" value="{{ $template }}" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Master Spiff</label>
                        <div class="col-md-4">
                            <input type="text" name="m_spiff" value="{{ $m_spiff }}" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <select name="m_spiff_type">
                                <option value="=" {{ $m_spiff_type == '=' ? 'selected' : '' }}>Equal</option>
                                <option value=">" {{ $m_spiff_type == '>' ? 'selected' : '' }}>Bigger</option>
                                <option value="<" {{ $m_spiff_type == '<' ? 'selected' : '' }}>Smaller</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Distributor Spiff</label>
                        <div class="col-md-4">
                            <input type="text" name="d_spiff" value="{{ $d_spiff }}" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <select name="d_spiff_type">
                                <option value="=" {{ $d_spiff_type == '=' ? 'selected' : '' }}>Equal</option>
                                <option value=">" {{ $d_spiff_type == '>' ? 'selected' : '' }}>Bigger</option>
                                <option value="<" {{ $d_spiff_type == '<' ? 'selected' : '' }}>Smaller</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Sub-Agent Spiff</label>
                        <div class="col-md-4">
                            <input type="text" name="s_spiff" value="{{ $s_spiff }}" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <select name="s_spiff_type">
                                <option value="=" {{ $s_spiff_type == '=' ? 'selected' : '' }}>Equal</option>
                                <option value=">" {{ $s_spiff_type == '>' ? 'selected' : '' }}>Bigger</option>
                                <option value="<" {{ $s_spiff_type == '<' ? 'selected' : '' }}>Smaller</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <div class="col-md-8 col-md-offset-4 text-right">
                            <button class="btn btn-primary btn-sm" type="button" onclick="search()">Search</button>
                            <button type="button" class="btn btn-primary btn-sm" onclick="excel_download()">DOWNLOAD</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="row">
        <div class="col-md-12">
            @if ($errors->has('exception'))
                <div class="alert alert-danger alert-dismissible" role="alert">
                    <strong>Error!</strong> {{ $errors->first('exception') }}
                </div>
            @endif
        </div>
    </div>

    <table class="tree table table-bordered table-hover table-condensed filter">
        <thead>
        <tr>
            <th>Parent</th>
            <th>Sub-Agent</th>
            <th>Product</th>
            <th>Denom</th>
            <th>S.Template</th>
            <th>S.Spiff</th>
            <th>D.Template</th>
            <th>D.Spiff</th>
            <th>M.Template</th>
            <th>M.Spiff</th>
        </tr>
        </thead>
        <tbody>
            @foreach ($spiffs as $spiff)
            <tr style="{{ ($spiff->spiff > $spiff->d_spiff || $spiff->d_spiff > $spiff->m_spiff) ?
            'color:red' : '' }}">
                <td>{!! \App\Lib\Helper::get_parent_name_html($spiff->account_id) !!}</td>
                <td>{{ '(' . $spiff->account_id . ') ' . $spiff->account_name }}</td>
                <td>{{ $spiff->product_id }}</td>
                <td>{{ $spiff->denom }}</td>
                <td>{{ empty($spiff->template) ? '' : '(' . $spiff->template . ') ' .
                \App\Model\SpiffTemplate::get_template_name($spiff->template) }}</td>
                <td>{{ $spiff->spiff }}</td>
                <td>{{ empty($spiff->d_template) ? '' : '(' . $spiff->d_template . ') ' .
                \App\Model\SpiffTemplate::get_template_name($spiff->d_template) }}</td>
                <td>{{ empty($spiff->d_account_id) ? '' : $spiff->d_spiff }}</td>
                <td>{{ empty($spiff->m_template) ? '' : '(' . $spiff->m_template . ') ' .
                \App\Model\SpiffTemplate::get_template_name($spiff->m_template) }}</td>
                <td>{{ $spiff->m_spiff }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="text-right">
        {{ $spiffs->appends(Request::except('page'))->links() }}
    </div>

@stop
