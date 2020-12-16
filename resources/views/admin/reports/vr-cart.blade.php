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
                url: '/admin/reports/vr-cart/detail',
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
                        var p = res.products;

                        $('#vr_id').val(o.id);
                        $('#vr_id_title').text(o.id);

                        $('#vr_products').empty();
                        $('#vr_price').val('');
                        $('#vr_shipping').val('');
                        $('#vr_total').val('');
                        $('#vr_op_comments').val('');

                        if (p.length > 0) {

                            var prod_html = '<table class="table table-bordered table-hover table-condensed filter">' +
                                    '<tr><th>SKU</th><th>Category</th><th>Model</th><th>Price</th><th>Qty</th><th>Is DropShip</th></tr>';

                            $.each(p, function(k, v){
                                var model = v.model;
                                var is_dropship = (v.is_dropship == null) ? '' : v.is_dropship;
                                if (v.url) {
                                    model = "<a href='" + v.url + "' target='_blank'>" + v.model + "</a>" ;
                                }

                                var key = v.prod_id; //v.prod_sku.replace(/\W+/g, '_');
                                prod_html += "<tr><td>" + v.prod_sku + "</td><td>" +
                                        v.category + "</td><td>" +
                                        model +
                                        "</td><td>$<span>" +
                                        v.order_price + "</span></td><td>" + v.qty + 
                                        "</td><td>"+ is_dropship+"</td></tr>";
                            });

                            prod_html += '</table>';

                            $('#vr_products').html(prod_html);

                            $('#vr_price').val(o.price);
                            $('#vr_shipping').val(o.shipping);
                            $('#vr_total').val(o.total);

                            // $('#vr_pay_method_paypal').prop('checked', o.pay_method == 'PayPal');
                            // $('#vr_pay_method_cod').prop('checked', o.pay_method == 'COD');


                            $('#vr_op_comments').val(o.op_comments);

                            $('#update').show();

                            $("#vr_price").prop('disabled', false);
                            $("#vr_shipping").prop('disabled', false);
                        }

                        $('#vr_last_modified').text(o.last_modified);
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

        function update() {
            myApp.showLoading();

            $.ajax({
                url: '/admin/reports/vr-cart/update',
                data: {
                    _token: '{!! csrf_token() !!}',
                    id: $('#vr_id').val(),
                    op_comments: $('#vr_op_comments').val()
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
                        location.href = "/admin/reports/vr-cart";
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

        function refresh_all() {
            window.location.href = '/admin/reports/vr-cart';
        }

    </script>

    <h4>Virtual Rep. Cart</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post">
            {{ csrf_field() }}
            <input type="hidden" name="excel" id="excel"/>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Account ID</label>
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="account_id" value="{{ old('account_id', $account_id) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Account Name</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="account_name" value="{{ old('account_name', $account_name) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Is Other Option</label>
                        <div class="col-md-2">
                            <input type="checkbox" name="contact_me" value="Y" {{ $contact_me == 'Y' ? 'checked' : '' }}/>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 text-right">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Is Dropship</label>
                        <div class="col-md-4">
                            <input type="checkbox" name="is_dropship" value="Y" {{ $is_dropship == 'Y' ? 'checked' : '' }}/>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <div class="col-md-8">
                            <button type="button" class="btn btn-info btn-sm" onclick="refresh_all()">Refresh All</button>
                            <button type="submit" class="btn btn-primary btn-sm" id="btn_search">Search</button>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <div class="text-left">
        Total {{ $records->total() }} record(s).
    </div>
    <table class="table table-bordered table-hover table-condensed filter">
        <thead>
        <tr>
            <th>Q.ID</th>
            <th>Account</th>
            <th>State</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Tracking #</th>
            <th>Category</th>
            <th>Total</th>
            <th>Status</th>
            <th>Other.Option</th>
            <th>Payment</th>
            <th>Last.Updated</th>
        </tr>
        </thead>
        <tbody>
        @if (isset($records) && count($records) > 0)
            @foreach ($records as $o)
                <tr>
                    <td>{{ $o->id }}</td>
                    @if (in_array(Auth::user()->account_type, ['L']))
                        <td>{!! Helper::get_parent_name_html($o->account_id) !!} <span>{!! Helper::get_hierarchy_img($o->acct_type) !!}</span>{{ $o->acct_name . ' ( ' . $o->account_id . ' )' }}</td>
                    @else
                        <td><span>{!! Helper::get_hierarchy_img($o->acct_type) !!}</span>{{ $o->account_name . ' ( ' . $o->account_id . ' )' }}</td>
                    @endif
                    <td>{{ $o->state }}</td>
                    <td>{{ $o->account_phone }}</td>
                    <td>{{ $o->account_email }}</td>
                    <td>{{ $o->tracking_no }}</td>
                    <td><a href="javascript:show_detail({{ $o->id }})">{{ $o->category_name }}</a></td>
                    <td>@if ($o->total && $o->category == 'O') ${!! $o->total !!} @endif</td>
                    <td>{!! $o->status_name() !!}</td>
                    <td>{{ $o->contact_me }}</td>
                    <td>{!! $o->pay_method !!}</td>
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
                    <h4 class="modal-title">Virtual Representative (ID: <span id="vr_id_title"></span>)</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="vr_id"/>

                    <!-- Start Accordion -->
                    <div class="panel-group" id="accordion1">
                        <div class="panel panel-default panel-order">
                            <div class="panel-heading" id="heading1">
                                <h6 class="panel-title">
                                    <a class="collapsed">
                                        CART
                                    </a>
                                </h6>
                            </div>

                            <div class="panel-body">
                                <p><span class="highlight default2">Order of Device, Posters, Brochures, etc.</span></p>
                                <p id="vr_products"></p>

                                <p style="margin-top: 20px"><span class="highlight default2">Notes</span>
                                    <textarea class="form-control" rows="5" id="vr_op_comments"></textarea>
                                </p>

                                <p style="margin-top: 20px"><span class="highlight default2">Price($)</span>
                                    <input type="number" class="form-control" id="vr_price" onchange="change_price(false)">
                                </p>

                                <p style="margin-top: 20px"><span class="highlight default2">Shipping($)</span>
                                    <input type="number" class="form-control" id="vr_shipping" onchange="change_price(false)">
                                </p>

                                <p style="margin-top: 20px"><span class="highlight default2">Total($)</span>
                                    <input type="number" class="form-control" id="vr_total" disabled="disabled">
                                </p>

                            </div>
                        </div>

                    </div>
                    <!-- End Accordion -->


                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary btn-sm" type="submit" data-dismiss="modal">Close</button>
                    <button class="btn btn-default btn-sm" id="update" type="button" onclick="update()">Update</button>
                </div>
            </div>
        </div>
    </div>
@stop
