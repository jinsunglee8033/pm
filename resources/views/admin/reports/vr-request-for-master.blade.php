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

            paypal.Button.render({

                env: '{{ getenv('APP_ENV') == 'production' ? 'production' : 'sandbox' }}', // sandbox | production

                // PayPal Client IDs - replace with your own
                // Create a PayPal app: https://developer.paypal.com/developer/applications/create
                client: {
                    sandbox: "ARPlPJ9KlcqJcnM3UdLSvfQP6ZjGr3XBXKUUpGnK4jMrqY4eDedjRdLUI9_JyjIlMIqOX7tZ7nQ-OOoX",
                    production: "AcCYHutE4WPxUSkYIL08sJJeEFFZ9ggozC8FrupXijXCOBFPtTE9UhESlIdBAAMObIXVYGNmKlH76Rzc"
                },

                // Show the buyer a 'Pay Now' button in the checkout flow
                commit: true,

                validate: function (actions) {
                    // toggleButton(actions);
                    //
                    // onChangeStatus(function () {
                    //     toggleButton(actions);
                    // });
                },

                onClick: function () {
                    if (!isValid()) {
                        myApp.showError('Please enter valid amount first');
                    }
                },

                // payment() is called when the button is clicked
                payment: function (data, actions) {

                    // Make a call to the REST api to create the payment
                    return actions.payment.create({
                        payment: {
                            transactions: [
                                {
                                    amount: {total: $('#amt').val(), currency: 'USD'},
                                    invoice_number: 'VR-' + $('#vr_account_id').val() +'-' + $('#vr_id').val()
                                }
                            ]
                        },
                        experience: {
                            input_fields: {
                                no_shipping: 1
                            }
                        }
                    });
                },

                // onAuthorize() is called when the buyer approves the payment
                onAuthorize: function (data, actions) {

                    /*
                     intent:"sale"
                     payerID:"SZ5BE7V7LH97Q"
                     paymentID:"PAY-73908049SM280515FLHAZR5A"
                     paymentToken:"EC-8C721893PX7060832"
                     returnUrl:"http://demo.softpayplus.com/?paymentId=PAY-73908049SM280515FLHAZR5A&token=EC-8C721893PX7060832&PayerID=SZ5BE7V7LH97Q
                     */

                    // Make a call to the REST api to execute the payment
                    return actions.payment.execute().then(function () {
                        //myApp.showSuccess('PayPal payment completed!');

                        // Add this new payment on our DB
                        myApp.showLoading();

                        $.ajax({
                            url: '/sub-agent/reports/vr-request/paypal/add',
                            data: {
                                _token: '{!! csrf_token() !!}',
                                vr_id: $('#vr_id').val(),
                                account_id: '{{ Auth::user()->account_id }}',
                                amt: $('#amt').val(),
                                comments: $('#paypal_comments').val(),
                                payer_id: data.payerID,
                                payment_id: data.payerID,
                                payment_token: data.paymentToken
                            },
                            cache: false,
                            type: 'post',
                            dataType: 'json',
                            success: function (res) {

                                myApp.hideLoading();
                                if ($.trim(res.msg) === '') {
                                    $('#div_paypal').modal('hide');
                                    myApp.showSuccess('PayPal payment completed!');
                                    search();
                                } else {
                                    myApp.showError(res.msg);
                                }

                            },
                            error: function (jqXHR, textStatus, errorThrown) {
                                myApp.hideLoading();
                                myApp.showError(errorThrown);
                            }
                        });

                    });
                },

                onCancel: function (data, actions) {
                    myApp.showError('You have cancelled PayPal payment!');
                },

                onError: function (err) {
                    // Show an error page here, when an error occurs
                    myApp.showError(err);
                }

            }, '#paypal-button-container');

        };

        function excel_export() {
            $('#excel').val('Y');
            $('#frm_search').submit();
        }

        function refresh_all() {
            $("form#frm_search input[type=text]").val('');
            $("form#frm_search select").val('');
        }

        function search() {
            $('#excel').val('N');
            $('#frm_search').submit();
        }

        function show_paypal(vr_id, amt, vr_account_id) {
            $('#div_paypal').modal();

            $('#amt').val(amt);
            $('#vr_id').val(vr_id);
            $('#vr_account_id').val(vr_account_id);
        }

        function toggleButton(actions) {
            return isValid() ? actions.enable() : actions.disable();
        }

        function isValid() {

            var amt = $('#amt').val();

            if (amt <= 0) {
                return false;
            }

            return true;
        }

        function onChangeStatus(handler) {
            document.querySelector('#amt').addEventListener('change', handler);
            document.querySelector('#paypal_comments').addEventListener('change', handler);
        }

        function show_detail(id) {
            myApp.showLoading();
            $.ajax({
                url: '/admin/reports/vr-request/load-detail-for-master',
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

                        $('#my_vr_id').val(o.id);
                        $('#my_vr_id_title').text(o.id);
                        $('#my_vr_category').val(o.category);

                        if (p.length > 0) {

                            var prod_html = '<table class="table table-bordered table-hover table-condensed filter">' +
                                    '<tr><th>Category</th><th>Model</th><th>Price</th><th>Qty</th></tr>';

                            $.each(p, function(k, v){
                                var model = v.model;
                                if (v.url) {
                                    model = "<a href='" + v.url + "' target='_blank'>" + v.model + "</a>";
                                }
                                prod_html += "<tr><td>" +
                                        v.category + "</td><td>" +
                                        model +
                                        "</td><td>$<span id='denom_" + v.prod_sku + "'>" +
                                        v.order_price + "</span></td><td>" +
                                        v.qty + "</td></tr>";
                            });

                            prod_html += '</table>';


                            $('#my_vr_products').html(prod_html);

                            $('#my_vr_order').val(o.order);
                            $('#my_vr_promo_code').val(o.promo_code);
                            if (o.price) {
                                $('#my_vr_price').text(o.price);
                                if (o.status == 'Requested') {
                                    $('#my_vr_shipping').text('TBD');
                                    $('#my_vr_total').text(o.price + ' + Shipping Cost');
                                } else {
                                    $('#my_vr_shipping').text(o.shipping);
                                    $('#my_vr_total').text(o.total);
                                }

                            }
                            $('#my_vr_pay_method').text(o.pay_method);
                            var addr2 = '';
                            if( (o.address2 == null) ? addr2 = '' : addr2 = o.address2);
                            $('#my_vr_address').text(o.address1 + ' ' + addr2 + ' ' + o.city + ' ' + o.state + ' ' + o.zip);
                            $('#my_vr_status').html(o.status);
                            $('#my_vr_op_comments').val(o.op_comments);

                            if (o.tracking_no) {
                                $('#my_vr_tracking_no').text(o.tracking_no);
                            }

                            $('.panel-order').show();
                            $('.panel-comments').hide();

                            $('#vr-panel1').collapse('show');
                            $('#vr-panel4').collapse('hide');

                        }

                        if (o.comments) {
                            $('#my_vr_comments').val(o.comments);
                            $('#my_vr_status2').html(o.status);
                            $('#my_vr_op_comments2').val(o.op_comments);

                            $('.panel-comments').show();
                            $('.panel-order').hide();

                            $('#vr-panel1').collapse('hide');
                            $('#vr-panel4').collapse('show');
                        }

                        $('#my_vr_last_modified').text(o.last_modified);

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

        function cancel(id) {

            myApp.showConfirm('Are you sure?', function() {
                myApp.showLoading();
                $.ajax({
                    url: '/sub-agent/reports/vr-request/cancel',
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

            });
        }

    </script>

    <div class="parallax" data-background="/img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
        <div class="overlay white"></div>
        <div class="container">
            <div class="inner-head">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h4>Market Place</h4>
                        <b>
                        <ol class="breadcrumb">
                            <li><a href="/admin/virtual-rep/shop">Order</a></li>
                            <li><a href="/admin/virtual-rep/cart">View Cart / Proceed to Checkout</a></li>
                            <li class="active">Track My Order</li>
                            <li><a href="/admin/virtual-rep/general_request">General Request</a></li>
                        </ol>
                        </b>
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
                                <option value="O" {{ old('category', $category) == 'O' ? 'selected' : '' }}>Order</option>
                                <option value="C" {{ old('category', $category) == 'C' ? 'selected' : '' }}>General Request</option>
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
                                <option value="RQ" {{ old('status', $status) == 'RQ' ? 'selected' : '' }}>Requested</option>
                                <option value="CP" {{ old('status', $status) == 'CP' ? 'selected' : '' }}>Confirmed Price</option>
                                <option value="PC" {{ old('status', $status) == 'PC' ? 'selected' : '' }}>Paid</option>
                                <option value="SH" {{ old('status', $status) == 'SH' ? 'selected' : '' }}>Shipped</option>
                                <option value="C" {{ old('status', $status) == 'C' ? 'selected' : '' }}>Completed</option>
                                <option value="R" {{ old('status', $status) == 'R' ? 'selected' : '' }}>Rejected</option>
                                <option value="R" {{ old('status', $status) == 'CC' ? 'selected' : '' }}>Canceled</option>
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

    <div class="text-left">
        Total {{ $records->total() }} record(s).
    </div>
    
    <table class="table table-bordered table-hover table-condensed filter">
        <thead>
        <tr>
            <th>Q.ID</th>
            <th>Category</th>
            <th>Tracking #</th>
            <th>Status</th>
            <th>Total</th>
            <th>Payment</th>
            <th>Last.Updated</th>
            <th>Update</th>
        </tr>
        </thead>
        <tbody>
        @if (isset($records) && count($records) > 0)
            @foreach ($records as $o)
                <tr>
                    <td>{{ $o->id }}</td>
                    <td><a href="javascript:show_detail({{ $o->id }})">{{ $o->category_name }}</a></td>
                    <td>{{ $o->tracking_no }}</td>
                    <td>{!! $o->status_name() !!}</td>
                    <td>@if($o->total && $o->category == 'O')${!! $o->total !!} @endif</td>
                    <td>
                        @php
                            $is_login_as = \App\Lib\Helper::is_login_as() ? 'Y' : 'N';
                        @endphp
                        @if($o->pay_method == 'PayPal' && $o->category == 'O' && !empty($o->total) && $o->status == 'CP')
                            @if ($is_login_as == 'Y')
                                <span class="btn btn-warning btn-xs">Not allowed</span>
                            @else
                            <button id="pay_now" class="btn btn-info btn-xs" onclick="show_paypal({{ $o->id }},{{$o->total}},{{$o->account_id}})">Pay Now</button>
                            @endif
                        @else
                            @if ($o->status == 'CT')
                                <a href="/admin/virtual-rep/cart" class="collapsed">
                                    Shipping Fee
                                </a>
                            @else
                                {{ $o->pay_method }}
                            @endif
                        @endif
                    </td>
                    <td>{{ $o->last_modified }}</td>
                    <td>
{{--                        @if( ($o->status == 'RQ' || $o->status == 'CP') && ($is_login_as == 'Y') )--}}
{{--                            <button id="cancel" class="btn btn-default btn-xs" onclick="cancel({{ $o->id }})">Cancel</button>--}}
{{--                        @endif--}}
                    </td>

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
                    <h4 class="modal-title">Virtual Representative (ID: <span id="my_vr_id_title"></span>)</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="my_vr_id"/>
                    <input type="hidden" id="my_vr_category"/>

                    <!-- Start Accordion -->
                    <div class="panel-group" id="accordion1">

                        <div class="panel panel-default panel-order">
                            <div class="panel-heading" id="heading1">
                                <h6 class="panel-title">
                                    <a class="collapsed">
                                        ORDER
                                    </a>
                                </h6>
                            </div>

                            <div id="vr-panel1" class="panel-collapse">
                                <div class="panel-body">

                                    <p>
                                        <span class="highlight default2 ">Order of Device, Posters, Brochures, etc.</span>
                                    </p>
                                    <p id="my_vr_products"></p>

                                    <textarea class="form-control" rows="5" id="my_vr_promo_code" style="margin-top: 20px" disabled="disabled"></textarea>
                                    <textarea class="form-control" rows="5" id="my_vr_order" style="margin-top: 20px" disabled="disabled"></textarea>

                                    <p style="margin-top: 20px"><span class="highlight default2">Reply</span></p>
                                      <p>  <textarea class="form-control" rows="5" id="my_vr_op_comments" disabled="disabled"></textarea>
                                    </p>

                                    <p style="margin-top: 20px;"><span class="highlight default2">Price($)</span></p>
                                    <p><span id="my_vr_price"></span></p>

                                    <p style="margin-top: 20px;">
                                        <span class="highlight default2">Shipping Cost($)</span></p>
                                    <p><span id="my_vr_shipping"></span></p>

                                    <p style="margin-top: 20px">
                                        <span class="highlight default2">Total($)</span></p>
                                    <p><span id="my_vr_total"></span></p>

                                    <p style="margin-top: 20px">
                                        <span class="highlight default2">Status</span></p>
                                    <p><span id="my_vr_status"></span></p>

                                    <p style="margin-top: 20px">
                                        <span class="highlight default2">Payments</span></p>
                                    <p><span id="my_vr_pay_method"></span></p>

                                    <p style="margin-top: 20px">
                                        <span class="highlight default2">Address</span></p>
                                    <p><span id="my_vr_address"></span></p>

                                    <p style="margin-top: 20px">
                                        <span class="highlight default2">Tracking No.</span></p>
                                    <p><span id="my_vr_tracking_no"></span></p>

                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel-group" id="accordion4">

                        <div class="panel panel-default panel-comments">
                            <div class="panel-heading" id="heading1">
                                <h6 class="panel-title">
                                    <a class="collapsed">
                                        GENERAL REQUEST
                                    </a>
                                </h6>
                            </div>

                            <div id="vr-panel4" class="panel-collapse">
                                <div class="panel-body">
                                    <p><span class="highlight default2 ">Comments</span>
                                        <textarea class="form-control" rows="10" id="my_vr_comments" style="margin-top: 20px" disabled="disabled"></textarea>
                                    </p>

                                    <p><span class="highlight default2">Reply</span>
                                        <textarea class="form-control" rows="10" id="my_vr_op_comments2" style="margin-top: 20px" disabled="disabled"></textarea>
                                    </p>

                                    <p>
                                        <span class="highlight default2">Status</span> &nbsp;
                                        <span id="my_vr_status2" style="margin-top: 20px"></span>
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


    <div class="modal fade" id="div_paypal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Pay with PayPal</h4>
                </div>
                <div class="modal-body">
                    <form class="form-horizontal">
                        <input type="hidden" id="vr_id"/>
                        <input type="hidden" id="vr_account_id"/>
                        <div class="form-group">
                            <label class="control-label col-md-4">Amount($)</label>
                            <div class="col-md-8">
                                <input type="text" class="form-control" id="amt" disabled/>
                            </div>
                        </div>
{{--                        <div class="form-group">--}}
{{--                            <label class="control-label col-md-4">Comments</label>--}}
{{--                            <div class="col-md-8">--}}
{{--                                <textarea type="text" class="form-control" id="paypal_comments" rows="3" style="width:100%;" disabled></textarea>--}}
{{--                            </div>--}}
{{--                        </div>--}}
                    </form>
                </div>

                <div class="modal-footer" style="margin-right:15px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <div id="paypal-button-container" class="btn float-right"></div>
                </div>
            </div>
        </div>

    </div>

@stop
