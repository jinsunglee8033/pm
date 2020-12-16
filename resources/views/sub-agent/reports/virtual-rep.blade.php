@extends('sub-agent.layout.default')

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
                url: '/sub-agent/reports/virtual-rep/load-detail',
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
                        $('#my_vr_id').val(o.id);
                        $('#my_vr_category').val(o.category);

                        $('#my_vr_poster_vz').prop('checked', o.poster_vz == 'Y');
                        $('#my_vr_poster_h2o').prop('checked', o.poster_h2o == 'Y');
                        $('#my_vr_poster_lyca').prop('checked', o.poster_lyca == 'Y');
                        $('#my_vr_poster_patriot').prop('checked', o.poster_patriot == 'Y');

                        $('#my_vr_brochure_vz').prop('checked', o.brochure_vz == 'Y');
                        $('#my_vr_brochure_h2o').prop('checked', o.brochure_h2o == 'Y');
                        $('#my_vr_brochure_lyca').prop('checked', o.brochure_lyca == 'Y');
                        $('#my_vr_brochure_patriot').prop('checked', o.brochure_patriot == 'Y');

                        $('#my_vr_material_other').val(o.material_other);

                        $('#my_vr_sim_vz').prop('checked', o.sim_vz == 'Y');
                        $('#my_vr_sim_h2o').prop('checked', o.sim_h2o == 'Y');
                        $('#my_vr_sim_lyca').prop('checked', o.sim_lyca == 'Y');
                        $('#my_vr_sim_patriot').prop('checked', o.sim_patriot == 'Y');

                        $('#my_vr_handset_vz').prop('checked', o.handset_vz == 'Y');
                        $('#my_vr_handset_h2o').prop('checked', o.handset_h2o == 'Y');
                        $('#my_vr_handset_lyca').prop('checked', o.handset_lyca == 'Y');
                        $('#my_vr_handset_patriot').prop('checked', o.handset_patriot == 'Y');

                        $('#my_vr_equipment_other').val(o.equipment_other);

                        $('#my_vr_tech_vz').val(o.tech_vz);
                        $('#my_vr_tech_h2o').val(o.tech_h2o);
                        $('#my_vr_tech_lyca').val(o.tech_lyca);
                        $('#my_vr_tech_patriot').val(o.tech_patriot);
                        $('#my_vr_tech_portal').val(o.tech_portal);
                        $('#my_vr_tech_other').val(o.tech_other);

                        $('#my_vr_comments').val(o.comments);

                        $('#my_vr_last_modified').text(o.last_modified);
                        $('#my_vr_status').val(o.status);

                        $('#my_vr_op_comments').val(o.op_comments);
                        $('#my_vr_tracking_no').val(o.tracking_no);

                        $('.panel-marketing-material').hide();
                        $('.panel-equipment-ordering').hide();
                        $('.panel-tech').hide();
                        $('.panel-comments').hide();
                        switch (o.category) {
                            case 'M':
                                $('.panel-marketing-material').show();
                                $('#vr-panel1').collapse('show');
                                break;
                            case 'E':
                                $('.panel-equipment-ordering').show();
                                $('#vr-panel2').collapse('show');
                                break;
                            case 'T':
                                $('.panel-tech').show();
                                $('#vr-panel3').collapse('show');
                                break;
                            case 'C':
                                $('.panel-comments').show();
                                $('#vr-panel4').collapse('show');
                                break;

                        }
                        //$('.panel-collapse.in').collapse('hide');
                        $('#my_virtual_rep_modal').modal();

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

    <div class="parallax" data-background="/img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
        <div class="overlay white"></div>
        <div class="container">
            <div class="inner-head">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h4>Track My Order</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li><a href="#">Reports</a></li>
                            <li class="active">Track My Order</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
            <th>Category</th>
            <th>Tracking #</th>
            <th>Status</th>
            <th>Last.Updated</th>
        </tr>
        </thead>
        <tbody>
        @if (isset($records) && count($records) > 0)
            @foreach ($records as $o)
                <tr>
                    <td>{{ $o->id }}</td>
                    <td><a href="javascript:show_detail({{ $o->id }})">{{ $o->category_name }}</a></td>
                    <td>{{ $o->tracking_no }}</td>
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

    <div class="modal fade" id="my_virtual_rep_modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Virtual Representative</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="my_vr_id"/>
                    <input type="hidden" id="my_vr_category"/>

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

                            <div id="vr-panel1" class="panel-collapse collapse">
                                <div class="panel-body">
                                    <p><span class="highlight default2">POSTERS</span>
                                    <div class="checkbox">
                                        <label><input type="checkbox" id="my_vr_poster_vz" disabled> VERIZON WIRELESS &nbsp; &nbsp; </label>
                                        <label><input type="checkbox" id="my_vr_poster_h2o" disabled> H2O WIRELESS &nbsp; &nbsp; </label>
                                        <label><input type="checkbox" id="my_vr_poster_lyca" disabled> LYCA MOBILE </label>
                                        <label><input type="checkbox" id="my_vr_poster_patriot" disabled> PATRIOT MOBILE </label>
                                    </div>

                                    </p>
                                    <p><span class="highlight default2">BROCHURES</span>
                                    <div class="checkbox">
                                        <label><input type="checkbox" id="my_vr_brochure_vz" disabled> VERIZON WIRELESS &nbsp; &nbsp; </label>
                                        <label><input type="checkbox" id="my_vr_brochure_h2o" disabled> H2O WIRELESS &nbsp; &nbsp; </label>
                                        <label><input type="checkbox" id="my_vr_brochure_lyca" disabled> LYCA MOBILE </label>
                                        <label><input type="checkbox" id="my_vr_brochure_patriot" disabled> PATRIOT MOBILE </label>
                                    </div>
                                    </p>
                                    <p><span class="highlight default2">OTHER</span>
                                        <textarea class="form-control" rows="3" id="my_vr_material_other" disabled
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
                            <div id="vr-panel2" class="panel-collapse collapse">
                                <div class="panel-body">
                                    <p><span class="highlight default2">SIM CARDS</span>
                                    <div class="checkbox">
                                        <label><input type="checkbox" id="my_vr_sim_vz" disabled> VERIZON WIRELESS &nbsp; &nbsp; </label>
                                        <label><input type="checkbox" id="my_vr_sim_h2o" disabled> H2O WIRELESS &nbsp; &nbsp; </label>
                                        <label><input type="checkbox" id="my_vr_sim_lyca" disabled> LYCA MOBILE </label>
                                        <label><input type="checkbox" id="my_vr_sim_patriot" disabled> PATRIOT MOBILE </label>
                                    </div>

                                    </p>
                                    <p><span class="highlight default2">HANDSETS</span>
                                    <div class="checkbox">
                                        <label><input type="checkbox" id="my_vr_handset_vz" disabled> VERIZON WIRELESS &nbsp; &nbsp; </label>
                                        <label><input type="checkbox" id="my_vr_handset_h2o" disabled> H2O WIRELESS &nbsp; &nbsp; </label>
                                        <label><input type="checkbox" id="my_vr_handset_lyca" disabled> LYCA MOBILE </label>
                                        <label><input type="checkbox" id="my_vr_handset_patriot" disabled> PATRIOT MOBILE </label>
                                    </div>
                                    </p>
                                    <p><span class="highlight default2">OTHER</span>
                                        <textarea class="form-control" rows="3" id="my_vr_equipment_other" disabled
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
                            <div id="vr-panel3" class="panel-collapse collapse">
                                <div class="panel-body">
                                    <p><strong>VERIZON WIRELESS</strong>
                                        <textarea class="form-control" rows="3" id="my_vr_tech_vz" disabled></textarea>
                                    </p>
                                    <p><strong>H2O WIRELESS</strong>
                                        <textarea class="form-control" rows="3" id="my_vr_tech_h2o" disabled></textarea>
                                    </p>
                                    <p><strong>LYCA MOBILE</strong>
                                        <textarea class="form-control" rows="3" id="my_vr_tech_lyca" disabled></textarea>
                                    </p>
                                    <p><strong>PATRIOT MOBILE</strong>
                                        <textarea class="form-control" rows="3" id="my_vr_tech_patriot" disabled></textarea>
                                    </p>
                                    <p><strong>PORTAL RELATED</strong>
                                        <textarea class="form-control" rows="3" id="my_vr_tech_portal" disabled></textarea>
                                    </p>
                                    <p><strong>OTHERS</strong>
                                        <textarea class="form-control" rows="3" id="my_vr_tech_other" disabled></textarea>
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
                            <div id="vr-panel4" class="panel-collapse collapse">
                                <div class="panel-body">
                                    <p>
                                        <textarea class="form-control" rows="3" id="my_vr_comments" disabled></textarea>

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
                            <div class="panel-collapse collapse in">
                                <div class="panel-body">
                                    <p>
                                        <select class="form-control" id="my_vr_status" disabled>
                                            <option value="N">New</option>
                                            <option value="R">Rejected</option>
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
                            <div class="panel-collapse collapse in">
                                <div class="panel-body">
                                    <p>
                                        <textarea class="form-control" rows="3" id="my_vr_op_comments" disabled></textarea>
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
                                        <input type="text" class="form-control" rows="3" id="my_vr_tracking_no" disabled/>
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
                            <div class="panel-collapse collapse in">
                                <div class="panel-body">
                                    <p>
                                        <span id="my_vr_last_modified"></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End Accordion -->


                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary btn-sm" type="submit" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@stop
