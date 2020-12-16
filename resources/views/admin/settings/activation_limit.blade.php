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

            $(".tooltip").tooltip({
                html: true
            })
        }

        function update_limit(account_id) {
            $('#account_id_label').text(account_id);
            $('#account_id').val(account_id);
            $('#hourly_preload').val($('#hourly_preload_' + account_id).text());
            $('#hourly_regular').val($('#hourly_regular_' + account_id).text());
            $('#hourly_byos').val($('#hourly_byos_' + account_id).text());
            $('#daily_preload').val($('#daily_preload_' + account_id).text());
            $('#daily_regular').val($('#daily_regular_' + account_id).text());
            $('#daily_byos').val($('#daily_byos_' + account_id).text());
            $('#weekly_preload').val($('#weekly_preload_' + account_id).text());
            $('#weekly_regular').val($('#weekly_regular_' + account_id).text());
            $('#weekly_byos').val($('#weekly_byos_' + account_id).text());
            $('#monthly_preload').val($('#monthly_preload_' + account_id).text());
            $('#monthly_regular').val($('#monthly_regular_' + account_id).text());
            $('#monthly_byos').val($('#monthly_byos_' + account_id).text());
            $('#allow_activation_over_max').val($('#allow_activation_over_max_' + account_id).text());

            $('#modal_activation_limit').modal();
        }

    </script>


    <h4>Activation Limit Setup</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_spiff" class="form-horizontal" action="/admin/settings/activation-limit" method="get"
              onsubmit="myApp.showLoading();">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Account</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="account" value="{{ old('account', $account) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Times (Minutes)</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="intime" value="{{ old('intime', $intime)
                            }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <div class="form-group">
                        <div class="col-md-12 text-right">
                            <button type="submit" class="btn btn-primary btn-sm" id="btn_search">Search</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>


    <table class="table table-bordered table-hover table-condensed filter table-fixed">
        <thead>
        <tr>
            <th rowspan="2" style="text-align: center;">Account</th>
            <th colspan="3" style="text-align: center;">Hourly</th>
            <th colspan="3" style="text-align: center;">Daily</th>
            <th colspan="3" style="text-align: center;">Weekly</th>
            <th colspan="3" style="text-align: center;">Monthly</th>
            <th rowspan="2" style="text-align: center;">Is<br>Default</th>
            <th rowspan="2" style="text-align: center;"></th>
        </tr>
        <tr>
            <th style="text-align: center;">Preload</th>
            <th style="text-align: center;">Regular</th>
            <th style="text-align: center;">BYOS</th>
            <th style="text-align: center;">Preload</th>
            <th style="text-align: center;">Regular</th>
            <th style="text-align: center;">BYOS</th>
            <th style="text-align: center;">Preload</th>
            <th style="text-align: center;">Regular</th>
            <th style="text-align: center;">BYOS</th>
            <th style="text-align: center;">Preload</th>
            <th style="text-align: center;">Regular</th>
            <th style="text-align: center;">BYOS</th>
        </tr>
        </thead>
        <tbody>
        @if (!empty($threshold_activations))
        @foreach ($threshold_activations as $t)
        <tr>
            <td rowspan="2">{{ $t->limit->account_id . ', ' . $t->limit->account_name }}</td>

            <td style="text-align: right;" id="hourly_preload_{{ $t->limit->account_id }}">{{$t->limit->hourly_preload }}</td>
            <td style="text-align: right;" id="hourly_regular_{{ $t->limit->account_id }}">{{ $t->limit->hourly_regular }}</td>
            <td style="text-align: right;" id="hourly_byos_{{ $t->limit->account_id }}">{{ $t->limit->hourly_byos }}</td>
            <td style="text-align: right;" id="daily_preload_{{ $t->limit->account_id }}">{{ $t->limit->daily_preload }}</td>
            <td style="text-align: right;" id="daily_regular_{{ $t->limit->account_id }}">{{ $t->limit->daily_regular }}</td>
            <td style="text-align: right;" id="daily_byos_{{ $t->limit->account_id }}">{{ $t->limit->daily_byos }}</td>
            <td style="text-align: right;" id="weekly_preload_{{ $t->limit->account_id }}">{{ $t->limit->weekly_preload }}</td>
            <td style="text-align: right;" id="weekly_regular_{{ $t->limit->account_id }}">{{ $t->limit->weekly_regular }}</td>
            <td style="text-align: right;" id="weekly_byos_{{ $t->limit->account_id }}">{{ $t->limit->weekly_byos }}</td>
            <td style="text-align: right;" id="monthly_preload_{{ $t->limit->account_id }}">{{ $t->limit->monthly_preload }}</td>
            <td style="text-align: right;" id="monthly_regular_{{ $t->limit->account_id }}">{{ $t->limit->monthly_regular }}</td>
            <td style="text-align: right;" id="monthly_byos_{{ $t->limit->account_id }}">{{ $t->limit->monthly_byos }}</td>
            <td style="text-align: center;">Plan{{ empty($t->limit->cdate) ? ' (D)' : '' }}</td>
            <td rowspan="2" style="text-align: center;"><button class="btn btn-primary btn-sm" onclick="update_limit({{
            $t->limit->account_id }})">Edit</button></td>
        </tr>
        <tr>
            <td style="text-align: right; {{ $t->hourly_preload >= $t->limit->hourly_preload ? 'color:red;' : ''}};
                    ">{{ $t->hourly_preload > 0 ? $t->hourly_preload : '' }}</td>
            <td style="text-align: right; {{ $t->hourly_regular >= $t->limit->hourly_regular ? 'color:red;' : ''}};"><strong>{{ $t->hourly_regular > 0 ? $t->hourly_regular : '' }}</strong></td>
            <td style="text-align: right; {{ $t->hourly_byos >= $t->limit->hourly_byos ? 'color:red;' : ''}};"><strong>{{ $t->hourly_byos > 0 ? $t->hourly_byos : '' }}</strong></td>
            <td style="text-align: right; {{ $t->daily_preload >= $t->limit->daily_preload ? 'color:red;' : ''}};"><strong>{{ $t->daily_preload > 0 ? $t->daily_preload : '' }}</strong></td>
            <td style="text-align: right; {{ $t->daily_regular >= $t->limit->daily_regular ? 'color:red;' : ''}};"><strong>{{ $t->daily_regular > 0 ? $t->daily_regular : '' }}</strong></td>
            <td style="text-align: right; {{ $t->daily_byos >= $t->limit->daily_byos ? 'color:red;' : ''}};"><strong>{{ $t->daily_byos > 0 ? $t->daily_byos : '' }}</strong></td>
            <td style="text-align: right; {{ $t->weekly_preload >= $t->limit->weekly_preload ? 'color:red;' : ''}};"><strong>{{ $t->weekly_preload > 0 ? $t->weekly_preload : '' }}</strong></td>
            <td style="text-align: right; {{ $t->weekly_regular >= $t->limit->weekly_regular ? 'color:red;' : ''}};"><strong>{{ $t->weekly_regular > 0 ? $t->weekly_regular : '' }}</strong></td>
            <td style="text-align: right; {{ $t->weekly_byos >= $t->limit->weekly_byos ? 'color:red;' : ''}};"><strong>{{ $t->weekly_byos > 0 ? $t->weekly_byos : '' }}</strong></td>
            <td style="text-align: right; {{ $t->monthly_preload >= $t->limit->monthly_preload ? 'color:red;' : ''}};"><strong>{{ $t->monthly_preload > 0 ? $t->monthly_preload : '' }}</strong></td>
            <td style="text-align: right; {{ $t->monthly_regular >= $t->limit->monthly_regular ? 'color:red;' : ''}};"><strong>{{ $t->monthly_regular > 0 ? $t->monthly_regular : '' }}</strong></td>
            <td style="text-align: right; {{ $t->monthly_byos >= $t->limit->monthly_byos ? 'color:red;' : ''}};"><strong>{{ $t->monthly_byos > 0 ? $t->monthly_byos : '' }}</strong></td>
            <td style="text-align: center;">Real</td>
        </tr>
        @endforeach
        <tr>
            <td colspan="15">-</td>
        </tr>
        @endif
        <tr>
            <td>Default</td>
            <td style="text-align: right;" id="hourly_preload_100000">{{ $default_limit->hourly_preload }}</td>
            <td style="text-align: right;" id="hourly_regular_100000">{{ $default_limit->hourly_regular }}</td>
            <td style="text-align: right;" id="hourly_byos_100000">{{ $default_limit->hourly_byos }}</td>
            <td style="text-align: right;" id="daily_preload_100000">{{ $default_limit->daily_preload }}</td>
            <td style="text-align: right;" id="daily_regular_100000">{{ $default_limit->daily_regular }}</td>
            <td style="text-align: right;" id="daily_byos_100000">{{ $default_limit->daily_byos }}</td>
            <td style="text-align: right;" id="weekly_preload_100000">{{ $default_limit->weekly_preload }}</td>
            <td style="text-align: right;" id="weekly_regular_100000">{{ $default_limit->weekly_regular }}</td>
            <td style="text-align: right;" id="weekly_byos_100000">{{ $default_limit->weekly_byos }}</td>
            <td style="text-align: right;" id="monthly_preload_100000">{{ $default_limit->monthly_preload }}</td>
            <td style="text-align: right;" id="monthly_regular_100000">{{ $default_limit->monthly_regular }}</td>
            <td style="text-align: right;" id="monthly_byos_100000">{{ $default_limit->monthly_byos }}</td>
            <td style="text-align: center;">Yes</td>
            <td style="text-align: center;"><button class="btn btn-primary btn-sm" onclick="update_limit(100000)">Edit</button></td>
        </tr>
        @foreach ($limits as $limit)
            @if (!in_array($limit->account_id, $account_ids))
            <tr>
                <td>{{ $limit->account_id . ', ' . $limit->account_name }}</td>
                @if (empty($limit->cdate))
                <td style="text-align: right;" id="hourly_preload_{{ $limit->account_id }}">{{
                $default_limit->hourly_preload }}</td>
                <td style="text-align: right;" id="hourly_regular_{{ $limit->account_id }}">{{ $default_limit->hourly_regular }}</td>
                <td style="text-align: right;" id="hourly_byos_{{ $limit->account_id }}">{{ $default_limit->hourly_byos }}</td>
                <td style="text-align: right;" id="daily_preload_{{ $limit->account_id }}">{{ $default_limit->daily_preload }}</td>
                <td style="text-align: right;" id="daily_regular_{{ $limit->account_id }}">{{ $default_limit->daily_regular }}</td>
                <td style="text-align: right;" id="daily_byos_{{ $limit->account_id }}">{{ $default_limit->daily_byos }}</td>
                <td style="text-align: right;" id="weekly_preload_{{ $limit->account_id }}">{{ $default_limit->weekly_preload }}</td>
                <td style="text-align: right;" id="weekly_regular_{{ $limit->account_id }}">{{ $default_limit->weekly_regular }}</td>
                <td style="text-align: right;" id="weekly_byos_{{ $limit->account_id }}">{{ $default_limit->weekly_byos }}</td>
                <td style="text-align: right;" id="monthly_preload_{{ $limit->account_id }}">{{ $default_limit->monthly_preload }}</td>
                <td style="text-align: right;" id="monthly_regular_{{ $limit->account_id }}">{{ $default_limit->monthly_regular }}</td>
                <td style="text-align: right;" id="monthly_byos_{{ $limit->account_id }}">{{ $default_limit->monthly_byos }}</td>
                <td style="text-align: center;">Yes</td>
                @else
                <td style="text-align: right;" id="hourly_preload_{{ $limit->account_id }}">{{
                $limit->hourly_preload }}</td>
                <td style="text-align: right;" id="hourly_regular_{{ $limit->account_id }}">{{ $limit->hourly_regular }}</td>
                <td style="text-align: right;" id="hourly_byos_{{ $limit->account_id }}">{{ $limit->hourly_byos }}</td>
                <td style="text-align: right;" id="daily_preload_{{ $limit->account_id }}">{{ $limit->daily_preload }}</td>
                <td style="text-align: right;" id="daily_regular_{{ $limit->account_id }}">{{ $limit->daily_regular }}</td>
                <td style="text-align: right;" id="daily_byos_{{ $limit->account_id }}">{{ $limit->daily_byos }}</td>
                <td style="text-align: right;" id="weekly_preload_{{ $limit->account_id }}">{{ $limit->weekly_preload }}</td>
                <td style="text-align: right;" id="weekly_regular_{{ $limit->account_id }}">{{ $limit->weekly_regular }}</td>
                <td style="text-align: right;" id="weekly_byos_{{ $limit->account_id }}">{{ $limit->weekly_byos }}</td>
                <td style="text-align: right;" id="monthly_preload_{{ $limit->account_id }}">{{ $limit->monthly_preload }}</td>
                <td style="text-align: right;" id="monthly_regular_{{ $limit->account_id }}">{{ $limit->monthly_regular }}</td>
                <td style="text-align: right;" id="monthly_byos_{{ $limit->account_id }}">{{ $limit->monthly_byos }}</td>
                <td></td>
                @endif
                <td style="text-align: center;"><button class="btn btn-primary btn-sm" onclick="update_limit({{
                $limit->account_id }})">Edit</button></td>
            </tr>
            @endif
        @endforeach
        </tbody>
        <tfoot>
        </tfoot>
    </table>

    <div class="row">
        <div class="col-md-2">
            Total {{ $limits->total() }} record(s).
        </div>
        <div class="col-md-10  text-right">
            {{ $limits->appends(Request::except('page'))->links() }}
        </div>
    </div>


    <div class="modal" id="modal_activation_limit" tabindex="-1" role="dialog" data-backdrop="static"
         data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="frm_search" class="form-horizontal" method="post" action="/admin/settings/activation-limit/update">
                    {{ csrf_field() }}
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="n_title">Activation Limit Setup for <span
                                id="account_id_label"></span></h4>
                </div>
                <div class="modal-body">

                    <div class="form-horizontal">

                        <input type="hidden" class="form-control" id="account_id" name="account_id"/>

                        <div class="form-group">
                            <label class="col-sm-4 control-label">hourly_preload</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="hourly_preload" name="hourly_preload"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">hourly_regular</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="hourly_regular" name="hourly_regular"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">hourly_byos</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="hourly_byos" name="hourly_byos"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-4 control-label">daily_preload</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="daily_preload" name="daily_preload"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">daily_regular</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="daily_regular" name="daily_regular"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">daily_byos</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="daily_byos" name="daily_byos"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-4 control-label">weekly_preload</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="weekly_preload" name="weekly_preload"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">weekly_regular</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="weekly_regular" name="weekly_regular"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">weekly_byos</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="weekly_byos" name="weekly_byos"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-4 control-label">monthly_preload</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="monthly_preload" name="monthly_preload"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">monthly_regular</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="monthly_regular" name="monthly_regular"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">monthly_byos</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="monthly_byos" name="monthly_byos"/>
                            </div>
                        </div>
                        <div class="form-group" style="visibility: hidden;">
                            <label class="col-sm-4 control-label">allow_activation_over_max</label>
                            <div class="col-sm-8">
                                <select id="allow_activation_over_max" name="allow_activation_over_max"
                                        class="form-control">
                                    <option value="Y">Yes</option>
                                    <option value="N">No</option>
                                </select>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer" style="margin-right:15px;">
                    <a type="button" class="btn btn-default" data-dismiss="modal">Close</a>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
                </form>
            </div>
        </div>
    </div>
@stop
