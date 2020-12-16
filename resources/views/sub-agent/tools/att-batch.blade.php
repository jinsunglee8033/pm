@extends('sub-agent.layout.default')

@section('content')
<style type="text/css">

    .receipt .row {
        border: 1px solid #e5e5e5;
    }

    .receipt .col-sm-4 {
        border-right: 1px solid #e5e5e5;
    }

    .row + .row {
        border-top: 0;
    }

    .divider2 {
        margin: 5px 0px !important;
    }

    hr {
        margin-top: 5px !important;
        margin-bottom: 5px !important;
    }

</style>
<script type="text/javascript">

    window.onload = function () {
        $("#sdate").datetimepicker({
            format: 'YYYY-MM-DD'
        });
        $("#edate").datetimepicker({
            format: 'YYYY-MM-DD'
        });
        $("#s_expire_date").datetimepicker({
            format: 'YYYY-MM-DD'
        });
        $("#e_expire_date").datetimepicker({
            format: 'YYYY-MM-DD'
        });
        $("#process_date").datetimepicker({
            format: 'YYYY-MM-DD'
        });
        $("#expire_date").datetimepicker({
            format: 'YYYY-MM-DD'
        });
    };

    function add_batch() {

        myApp.showConfirm("Are you sure to add new batch?", function() {
            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/tools/att-batch/add',
                data: {
                    _token: '{{ csrf_token() }}',
                    mdn: $('#mdn').val(),
                    sim: $('#sim').val(),
                    plan: $('#plan').val(),
                    for_rtr: $('#for_rtr').val(),
                    for_sim_swap: $('#for_sim_swap').val(),
                    for_plan_change: $('#for_plan_change').val(),
                    process_date: $('#process_date').val(),
                    expire_date: $('#expire_date').val(),
                    notes: $('#notes').val()
                },
                type: 'get',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();
                    if (res.code === '0') {
                        myApp.showSuccess(res.msg, function() {
                            $('#frm_search').submit();
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
        }, function() {
            // do nothing
        });
    }

    function delete_batch(batch_id) {

        myApp.showConfirm("Are you sure to delete the batch?", function() {
            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/tools/att-batch/delete',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: batch_id
                },
                type: 'get',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();
                    if (res.code === '0') {
                        myApp.showSuccess(res.msg, function() {
                            $('#frm_search').submit();
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
        }, function() {
            // do nothing
        });
    }

</script>

<!-- Start parallax -->
<div class="parallax no-print" data-background="/img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
    <div class="overlay white"></div>
    <div class="container">
        <div class="inner-head">
            <div class="row">
                <div class="col-md-12 text-center">
                    <h4>Dealer Tools</h4>
                    <ol class="breadcrumb">
                        <li><a href="#">Home</a></li>
                        <li><a href="#">Tools</a></li>
{{--                        <li class="active">ATT Schedule</li>--}}
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End parallax -->

<!-- Start contain wrapp -->
<div class="contain-wrapp padding-bot70 no-print" style="">
    <div class="container">
        <div class="row">
            <div class="col-md-12 col-sm-12">
                <div class="clearfix"></div>


                <div class="tabbable tab">
                    <ul class="nav nav-tabs">
                        <li>
                            <a href="/sub-agent/activate/att">ATT Activation</a>
                        </li>
                        <li>
                            <a href="/sub-agent/tools/att">ATT Tools</a>
                        </li>
{{--                        <li class="active">--}}
{{--                            <a href="#" class="black-tab">ATT Schedule</a>--}}
{{--                        </li>--}}
                    </ul>

                    <!-- Tab panes -->
                    <div class="tab-content" style="padding-top:24px;">

                        <div class="tabbable tab-lg">
                            
                            <form id="frm_search" class="form-horizontal" method="post" action="/sub-agent/tools/att-batch">
                                {{ csrf_field() }}
                                <input type="hidden" name="excel" id="excel"/>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="col-md-4 control-label">Process Date</label>
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
                                            <label class="col-md-4 control-label">Phone</label>
                                            <div class="col-md-8">
                                                <input type="text" class="form-control" name="phone" value="{{ old('phone', $phone) }}"/>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="col-md-4 control-label">SIM</label>
                                            <div class="col-md-8">
                                                <input type="text" class="form-control" name="sim"
                                                       value="{{ old('sim', $sim) }}"/>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="col-md-4 control-label">Expire Date</label>
                                            <div class="col-md-8">
                                                <input type="text" style="width:100px; float:left;" class="form-control"
                                                       id="s_expire_date" name="s_expire_date" value="{{ old('s_expire_date', $s_expire_date)
                                                       }}"/>
                                                <span class="control-label" style="margin-left:5px; float:left;"> ~ </span>
                                                <input type="text" style="width:100px; margin-left: 5px; float:left;"
                                                       class="form-control" id="e_expire_date" name="e_expire_date"
                                                       value="{{ old('e_expire_date', $e_expire_date) }}"/>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="col-md-4 control-label">Status</label>
                                            <div class="col-md-8">
                                                <select class="form-control" name="status">
                                                    <option value="" {{ old('status', $status) == '' ? 'selected' : '' }}>
                                                        All
                                                    </option>
                                                    <option value="N" {{ old('status', $status) == 'N' ? 'selected' : '' }}>
                                                        New
                                                    </option>
                                                    <option value="P" {{ old('status', $status) == 'P' ? 'selected' : '' }}>
                                                        Processing
                                                    </option>
                                                    <option value="C" {{ old('status', $status) == 'C' ? 'selected' : '' }}>
                                                        Completed
                                                    </option>
                                                    <option value="F" {{ old('status', $status) == 'F' ? 'selected' : '' }}>
                                                        Failed
                                                    </option>
                                                    <option value="X" {{ old('status', $status) == 'X' ? 'selected' : '' }}>
                                                        Deleted
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="col-md-4 control-label">Notes</label>
                                            <div class="col-md-8">
                                                <input type="text" class="form-control" name="notes"
                                                       value="{{ old('notes', $notes) }}"/>
                                            </div>
                                        </div>
                                    </div>

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
                                                <button type="submit" class="btn btn-info btn-sm">
                                                    Search
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            </div>

        </div>

        <table class="parameter-product table-bordered table-hover table-condensed filter">
            <thead>
                <tr class="active">
                    <th rowspan="2">MDN</th>
                    @if ($authority->auth_batch_sim_swap == 'Y')
                    <th rowspan="2">New SIM</th>
                    @endif
                    <th rowspan="2">Plan</th>
                    @if ($authority->auth_batch_sim_swap == 'Y')
                    <th colspan="2">Sim Swap</th>
                    @endif
                    @if ($authority->auth_batch_plan_change == 'Y')
                    <th colspan="2">Plan Change</th>
                    @endif
                    @if ($authority->auth_batch_rtr == 'Y')
                    <th colspan="2">Recharge</th>
                    @endif
                    <th rowspan="2">Process Date</th>
                    <th rowspan="2">Expire Date</th>
                    <th rowspan="2">Notes</th>
                    <th rowspan="2">Created</th>
                    <th rowspan="2">Status</th>
                    <th rowspan="2"></th>
                </tr>
                <tr class="active">
                    @if ($authority->auth_batch_sim_swap == 'Y')
                        <th>Yes/No</th>
                        <th>Status</th>
                    @endif
                    @if ($authority->auth_batch_plan_change == 'Y')
                        <th>Yes/No</th>
                        <th>Status</th>
                    @endif
                    @if ($authority->auth_batch_rtr == 'Y')
                        <th>Yes/No</th>
                        <th>Status</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($batchs as $batch)
                <tr>
                    <td>{{ $batch->mdn }}</td>
                    @if ($authority->auth_batch_sim_swap == 'Y')
                    <td>{{ $batch->sim }}</td>
                    @endif
                    <td>{{ $batch->plan }}</td>
                    @if ($authority->auth_batch_sim_swap == 'Y')
                    <td>{{ $batch->for_sim_swap == 'Y' ? 'Yes' : 'No' }}</td>
                    <td>{{ $batch->for_sim_swap == 'Y' ? $batch->for_sim_swap_status_name : '' }}</td>
                    @endif
                    @if ($authority->auth_batch_plan_change == 'Y')
                    <td>{{ $batch->for_plan_change == 'Y' ? 'Yes' : 'No' }}</td>
                    <td>{{ $batch->for_plan_change == 'Y' ? $batch->for_plan_change_status_name : '' }}</td>
                    @endif
                    @if ($authority->auth_batch_rtr == 'Y')
                    <td>{{ $batch->for_rtr == 'Y' ? 'Yes' : 'No' }}</td>
                    <td>{{ $batch->for_rtr == 'Y' ? $batch->for_rtr_status_name : '' }}</td>
                    @endif
                    <td>{{ $batch->process_date }}</td>
                    <td>{{ $batch->expire_date }}</td>
                    <td>{{ $batch->notes }}</td>
                    <td>{{ $batch->cdate }}</td>
                    <td>{{ $batch->status_name }}</td>
                    <td>
                        @if ($batch->status == 'N')
                        <a class="btn btn-primary btn-xs" onclick="delete_batch({{ $batch->id }})" style="cursor: pointer;">Delete</a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="active">
                    <td style="width: 120px;">
                        <input type="text" id="mdn" style="width: 120px;" class="form-control">
                    </td>
                    @if ($authority->auth_batch_sim_swap == 'Y')
                    <td><input type="text" id="sim" style="width: 200px;" class="form-control"></td>
                    @endif
                    <td>
                        <select id="plan" class="form-control">
                            <option value="">Select</option>
                            @foreach ($plans as $plan)
                            <option value="{{ $plan->denom }}">{{ $plan->denom }}</option>
                            @endforeach
                        </select>
                    </td>
                    @if ($authority->auth_batch_sim_swap == 'Y')
                    <td colspan="2">
                        <select id="for_sim_swap" class="form-control">
                            <option value="">Select</option>
                            <option value="Y">Yes</option>
                            <option value="N">No</option>
                        </select>
                    </td>
                    @endif
                    @if ($authority->auth_batch_plan_change == 'Y')
                    <td colspan="2">
                        <select id="for_plan_change" class="form-control">
                            <option value="">Select</option>
                            <option value="Y">Yes</option>
                            <option value="N">No</option>
                        </select>
                    </td>
                    @endif
                    @if ($authority->auth_batch_rtr == 'Y')
                    <td colspan="2">
                        <select id="for_rtr" class="form-control">
                            <option value="">Select</option>
                            <option value="Y">Yes</option>
                            <option value="N">No</option>
                        </select>
                    </td>
                    @endif
                    <td style="width: 100px;"><input type="text" id="process_date" style="width: 100px;"
                                                     class="form-control"></td>
                    <td style="width: 100px;"><input type="text" id="expire_date" style="width: 100px;"
                                                     class="form-control"></td>
                    <td style="width: 180px;"><input type="text" id="notes" style="width: 180px;"
                                                     class="form-control"></td>
                    <td colspan='3'>
                        <a class="btn btn-primary btn-sm" onclick="add_batch()" style="cursor: pointer;">Add New</a>
                    </td>
                </tr>
            </tfoot>
        </table>

        <div class="text-right">
            {{ $batchs->appends(Request::except('page'))->links() }}
        </div>

    </div>
</div>
@stop
