@extends('admin.layout.default')

@section('content')

    <script type="text/javascript">
        window.onload = function() {
            $( "#sdate" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });
            $( "#edate" ).datetimepicker({
                format: 'YYYY-MM-DD'
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

        function show_detail(id) {
            myApp.showLoading();
            $.ajax({
                url: '/admin/reports/virtual-rep/load-detail',
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

                        var o = res.data;
                        $('#vr_id').val(o.id);
                        $('#vr_category').val(o.category);
                        
                        $('#vr_poster_vz').prop('checked', o.poster_vz == 'Y');
                        $('#vr_poster_h2o').prop('checked', o.poster_h2o == 'Y');
                        $('#vr_poster_lyca').prop('checked', o.poster_lyca == 'Y');
                        $('#vr_poster_patriot').prop('checked', o.poster_patriot == 'Y');

                        $('#vr_brochure_vz').prop('checked', o.brochure_vz == 'Y');
                        $('#vr_brochure_h2o').prop('checked', o.brochure_h2o == 'Y');
                        $('#vr_brochure_lyca').prop('checked', o.brochure_lyca == 'Y');
                        $('#vr_brochure_patriot').prop('checked', o.brochure_patriot == 'Y');
                        
                        $('#vr_material_other').val(o.material_other);

                        $('#vr_sim_vz').prop('checked', o.sim_vz == 'Y');
                        $('#vr_sim_h2o').prop('checked', o.sim_h2o == 'Y');
                        $('#vr_sim_lyca').prop('checked', o.sim_lyca == 'Y');
                        $('#vr_sim_patriot').prop('checked', o.sim_patriot == 'Y');

                        $('#vr_handset_vz').prop('checked', o.handset_vz == 'Y');
                        $('#vr_handset_h2o').prop('checked', o.handset_h2o == 'Y');
                        $('#vr_handset_lyca').prop('checked', o.handset_lyca == 'Y');
                        $('#vr_handset_patriot').prop('checked', o.handset_patriot == 'Y');
                        
                        $('#vr_equipment_other').val(o.equipment_other);

                        $('#vr_tech_vz').val(o.tech_vz);
                        $('#vr_tech_h2o').val(o.tech_h2o);
                        $('#vr_tech_lyca').val(o.tech_lyca);
                        $('#vr_tech_patriot').val(o.tech_patriot);
                        $('#vr_tech_portal').val(o.tech_portal);
                        $('#vr_tech_other').val(o.tech_other);

                        $('#vr_comments').val(o.comments);

                        $('#vr_last_modified').text(o.last_modified);
                        $('#vr_status').val(o.status);

                        $('#vr_op_comments').val(o.op_comments);
                        $('#vr_tracking_no').val(o.tracking_no);

                        // show all category
                        $('#panel1').collapse('show');
                        $('#panel2').collapse('show');
                        $('#panel3').collapse('show');
                        $('#panel4').collapse('show');

                        /*
                        $('.panel-marketing-material').hide();
                        $('.panel-equipment-ordering').hide();
                        $('.panel-tech').hide();
                        $('.panel-comments').hide();
                        switch (o.category) {
                            case 'M':
                                $('.panel-marketing-material').show();
                                $('#panel1').collapse('show');
                                break;
                            case 'E':
                                $('.panel-equipment-ordering').show();
                                $('#panel2').collapse('show');
                                break;
                            case 'T':
                                $('.panel-tech').show();
                                $('#panel3').collapse('show');
                                break;
                            case 'C':
                                $('.panel-comments').show();
                                $('#panel4').collapse('show');
                                break;

                        }
                        */
                        //$('.panel-collapse.in').collapse('hide');
                        $('#virtual_rep_modal').modal();

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

        function save_vr() {
            myApp.showLoading();
            $.ajax({
                url: '/admin/reports/virtual-rep/update',
                data: {
                    _token: '{!! csrf_token() !!}',
                    id: $('#vr_id').val(),
                    status: $('#vr_status').val(),
                    op_comments: $('#vr_op_comments').val(),
                    tracking_no: $('#vr_tracking_no').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        $('#virtual_rep_modal').modal('hide');
                        myApp.showSuccess('Your request has been processed successfully!', function() {
                            myApp.showLoading();
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
        }

    </script>

    <h4>Virtual Rep. Report</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post">
            {{ csrf_field() }}
            <input type="hidden" name="excel" id="excel"/>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Date</label>
                        <div class="col-md-8">
                            <input type="text" style="width:100px; float:left;" class="form-control" id="sdate" name="sdate" value="{{ old('sdate', $sdate) }}"/>
                            <span class="control-label" style="margin-left:5px; float:left;"> ~ </span>
                            <input type="text" style="width:100px; margin-left: 5px; float:left;" class="form-control" id="edate" name="edate" value="{{ old('edate', $edate) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Category</label>
                        <div class="col-md-8">
                            <select class="form-control" name="category" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                <option value="" {{ old('category', $category) == '' ? 'selected' : '' }}>All</option>
                                <option value="M" {{ old('category', $category) == 'M' ? 'selected' : '' }}>Marketing Material</option>
                                <option value="E" {{ old('category', $category) == 'E' ? 'selected' : '' }}>Equipment Ordering</option>
                                <option value="T" {{ old('category', $category) == 'T' ? 'selected' : '' }}>Technical Issues</option>
                                <option value="C" {{ old('category', $category) == 'C' ? 'selected' : '' }}>Comments</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Status</label>
                        <div class="col-md-8">
                            <select class="form-control" name="status" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                <option value="" {{ old('status', $status) == '' ? 'selected' : '' }}>All</option>
                                <option value="N" {{ old('status', $status) == 'N' ? 'selected' : '' }}>New</option>
                                <option value="R" {{ old('status', $status) == 'R' ? 'selected' : '' }}>Rejected</option>
                                <option value="P" {{ old('status', $status) == 'P' ? 'selected' : '' }}>Pending</option>
                                <option value="W" {{ old('status', $status) == 'W' ? 'selected' : '' }}>Processing</option>
                                <option value="C" {{ old('status', $status) == 'C' ? 'selected' : '' }}>Completed</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Account ID</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="account_id" value="{{ old('account_id', $account_id) }}"/>
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
                        <label class="col-md-4 control-label">Tracking #</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="tracking_no" value="{{ old('tracking_no', $tracking_no) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-md-offset-8 text-right">
                    <div class="form-group">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary btn-sm" id="btn_search">Search</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>


    <table class="table table-bordered table-hover table-condensed filter">
        <thead>
        <tr>
            <th>Q.ID</th>
            <th>Account</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Tracking #</th>
            <th>Category</th>
            <th>Status</th>
            <th>Last.Updated</th>
        </tr>
        </thead>
        <tbody>
        @if (isset($records) && count($records) > 0)
            @foreach ($records as $o)
                <tr>
                    <td>{{ $o->id }}</td>
                    @if (in_array(Auth::user()->account_type, ['L']))
                        <td>{!! Helper::get_parent_name_html($o->account_id) !!} <span>{!! Helper::get_hierarchy_img($o->account_type) !!}</span>{{ $o->account_name . ' ( ' . $o->account_id . ' )' }}</td>
                    @else
                        <td><span>{!! Helper::get_hierarchy_img($o->account_type) !!}</span>{{ $o->account_name . ' ( ' . $o->account_id . ' )' }}</td>
                    @endif
                    <td>{{ $o->account_phone }}</td>
                    <td>{{ $o->account_email }}</td>
                    <td>{{ $o->tracking_no }}</td>
                    <td><a href="javascript:show_detail({{ $o->id }})">{{ $o->category_name }}</a></td>
                    <td>{!! $o->status_name !!}</td>
                    <td>{{ $o->last_modified }}</td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="19" class="text-center">No Record Found</td>
            </tr>
        @endif
        </tbody>
    </table>

    <div class="text-right">
        {{ $records->appends(Request::except('page'))->links() }}
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

    <div class="modal fade" id="virtual_rep_modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Virtual Representative</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="vr_id"/>
                    <input type="hidden" id="vr_category"/>

                    <!-- Start Accordion -->
                    <div class="panel-group" id="accordion1">
                        <div class="panel panel-default panel-marketing-material">
                            <div class="panel-heading" id="heading1">
                                <h6 class="panel-title">
                                    <a class="collapsed">
                                        I REQUIRE MORE MARKETING MATERIALS
                                    </a>
                                </h6>
                            </div>

                            <div id="panel1" class="panel-collapse collapse">
                                <div class="panel-body">
                                    <p><span class="highlight default2">POSTERS</span>
                                    <div class="checkbox">
                                        <label><input type="checkbox" id="vr_poster_vz" disabled> VERIZON WIRELESS &nbsp; &nbsp; </label>
                                        <label><input type="checkbox" id="vr_poster_h2o" disabled> H2O WIRELESS &nbsp; &nbsp; </label>
                                        <label><input type="checkbox" id="vr_poster_lyca" disabled> LYCA MOBILE </label>
                                        <label><input type="checkbox" id="vr_poster_patriot" disabled> PATRIOT MOBILE </label>
                                    </div>

                                    </p>
                                    <p><span class="highlight default2">BROCHURES</span>
                                    <div class="checkbox">
                                        <label><input type="checkbox" id="vr_brochure_vz" disabled> VERIZON WIRELESS &nbsp; &nbsp; </label>
                                        <label><input type="checkbox" id="vr_brochure_h2o" disabled> H2O WIRELESS &nbsp; &nbsp; </label>
                                        <label><input type="checkbox" id="vr_brochure_lyca" disabled> LYCA MOBILE </label>
                                        <label><input type="checkbox" id="vr_brochure_patriot" disabled> PATRIOT MOBILE </label>
                                    </div>
                                    </p>
                                    <p><span class="highlight default2">OTHER</span>
                                        <textarea class="form-control" rows="3" id="vr_material_other" disabled
                                                  style="margin-top: 20px"></textarea>


                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="panel panel-default panel-equipment-ordering">
                            <div class="panel-heading" id="heading2">
                                <h6 class="panel-title">
                                    <a class="collapsed">
                                        EQUIPMENT ORDERING
                                    </a>
                                </h6>
                            </div>
                            <div id="panel2" class="panel-collapse collapse">
                                <div class="panel-body">
                                    <p><span class="highlight default2">SIM CARDS</span>
                                    <div class="checkbox">
                                        <label><input type="checkbox" id="vr_sim_vz" disabled> VERIZON WIRELESS &nbsp; &nbsp; </label>
                                        <label><input type="checkbox" id="vr_sim_h2o" disabled> H2O WIRELESS &nbsp; &nbsp; </label>
                                        <label><input type="checkbox" id="vr_sim_lyca" disabled> LYCA MOBILE </label>
                                        <label><input type="checkbox" id="vr_sim_patriot" disabled> PATRIOT MOBILE </label>
                                    </div>

                                    </p>
                                    <p><span class="highlight default2">HANDSETS</span>
                                    <div class="checkbox">
                                        <label><input type="checkbox" id="vr_handset_vz" disabled> VERIZON WIRELESS &nbsp; &nbsp; </label>
                                        <label><input type="checkbox" id="vr_handset_h2o" disabled> H2O WIRELESS &nbsp; &nbsp; </label>
                                        <label><input type="checkbox" id="vr_handset_lyca" disabled> LYCA MOBILE </label>
                                        <label><input type="checkbox" id="vr_handset_patriot" disabled> PATRIOT MOBILE </label>
                                    </div>
                                    </p>
                                    <p><span class="highlight default2">OTHER</span>
                                        <textarea class="form-control" rows="3" id="vr_equipment_other" disabled
                                                  style="margin-top: 20px"></textarea>
                                        <strong>*Pricing Information for Call or E-mail</strong>

                                    </p>
                                </div>
                            </div>
                        </div>


                        <div class="panel panel-default panel-tech">
                            <div class="panel-heading" id="heading3">
                                <h6 class="panel-title">
                                    <a class="collapsed">
                                        TECHNICAL ISSUES
                                    </a>
                                </h6>
                            </div>
                            <div id="panel3" class="panel-collapse collapse">
                                <div class="panel-body">
                                    <p><strong>VERIZON WIRELESS</strong>
                                        <textarea class="form-control" rows="3" id="vr_tech_vz" disabled></textarea>
                                    </p>
                                    <p><strong>H2O WIRELESS</strong>
                                        <textarea class="form-control" rows="3" id="vr_tech_h2o" disabled></textarea>
                                    </p>
                                    <p><strong>LYCA MOBILE</strong>
                                        <textarea class="form-control" rows="3" id="vr_tech_lyca" disabled></textarea>
                                    </p>
                                    <p><strong>PATRIOT MOBILE</strong>
                                        <textarea class="form-control" rows="3" id="vr_tech_patriot" disabled></textarea>
                                    </p>
                                    <p><strong>PORTAL RELATED</strong>
                                        <textarea class="form-control" rows="3" id="vr_tech_portal" disabled></textarea>
                                    </p>
                                    <p><strong>OTHERS</strong>
                                        <textarea class="form-control" rows="3" id="vr_tech_other" disabled></textarea>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="panel panel-default panel-comments">
                            <div class="panel-heading" id="heading4">
                                <h6 class="panel-title">
                                    <a class="collapsed">
                                        COMMENTS
                                    </a>
                                </h6>
                            </div>
                            <div id="panel4" class="panel-collapse collapse">
                                <div class="panel-body">
                                    <p>
                                        <textarea class="form-control" rows="3" id="vr_comments" disabled></textarea>

                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="panel panel-default">
                            <div class="panel-heading" id="heading4">
                                <h6 class="panel-title">
                                    <a class="collapsed">
                                        STATUS
                                    </a>
                                </h6>
                            </div>
                            <div id="panel4" class="panel-collapse collapse in">
                                <div class="panel-body">
                                    <p>
                                        <select class="form-control" id="vr_status">
                                            <option value="N">New</option>
                                            <option value="R">Rejected</option>
                                            <option value="P">Pending</option>
                                            <option value="W">Processing</option>
                                            <option value="C">Completed</option>
                                        </select>

                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="panel panel-default">
                            <div class="panel-heading" id="heading4">
                                <h6 class="panel-title">
                                    <a class="collapsed">
                                        OPERATOR.COMMENTS
                                    </a>
                                </h6>
                            </div>
                            <div id="panel4" class="panel-collapse collapse in">
                                <div class="panel-body">
                                    <p>
                                        <textarea class="form-control" rows="3" id="vr_op_comments"></textarea>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="panel panel-default">
                            <div class="panel-heading" id="heading4">
                                <h6 class="panel-title">
                                    <a class="collapsed">
                                        Tracking No.
                                    </a>
                                </h6>
                            </div>
                            <div id="panel4" class="panel-collapse collapse in">
                                <div class="panel-body">
                                    <p>
                                        <input type="text" class="form-control" rows="3" id="vr_tracking_no"/>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="panel panel-default">
                            <div class="panel-heading" id="heading4">
                                <h6 class="panel-title">
                                    <a class="collapsed">
                                        LAST.MODIFIED
                                    </a>
                                </h6>
                            </div>
                            <div id="panel4" class="panel-collapse collapse in">
                                <div class="panel-body">
                                    <p>
                                        <span id="vr_last_modified"></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End Accordion -->


                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary btn-sm" type="submit" data-dismiss="modal">Close</button>
                    <button class="btn btn-default btn-sm" type="button" onclick="save_vr()">Submit</button>
                </div>
            </div>
        </div>
    </div>
@stop
