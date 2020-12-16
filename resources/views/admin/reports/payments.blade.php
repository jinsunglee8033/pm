@extends('admin.layout.default')

@section('content')

    <script type="text/javascript">

        var onload_func = window.onload;

        window.onload = function() {

            if (onload_func) {
                onload_func();
            }

            $("#sdate").datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $("#edate").datetimepicker({
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

                validate: function(actions) {
                    toggleButton(actions);

                    onChangeStatus(function() {
                        toggleButton(actions);
                    });
                },

                onClick: function() {
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
                                    amount: {total: $('#n_deposit_amt').val(), currency: 'USD'}
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
                            url: '/admin/reports/payments/paypal/add',
                            data: {
                                _token: '{!! csrf_token() !!}',
                                account_id: '{{ Auth::user()->account_id }}',
                                deposit_amt: $('#n_deposit_amt').val(),
                                fee: $('#n_fee').val(),
                                amt: $('#n_amt').val(),
                                comments: $('#n_comments').val(),
                                payer_id: data.payerID,
                                payment_id: data.payerID,
                                payment_token: data.paymentToken
                            },
                            cache: false,
                            type: 'post',
                            dataType: 'json',
                            success: function(res) {

                                myApp.hideLoading();
                                if ($.trim(res.msg) === '') {
                                    $('#div_paypal').modal('hide');
                                    myApp.showSuccess('PayPal payment completed!');
                                    search();
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

        function search() {
            $('#excel').val('N');
            $('#frm_search').submit();
        }

        function show_paypal() {
            $('#div_paypal').modal();
        }

        function toggleButton(actions) {
            return isValid() ? actions.enable() : actions.disable();
        }


        function isValid() {
            var amt = $('#n_amt').val();

            if (amt <= 0) {
                return false;
            }

            // var comments = $('#n_comments').val();
            // if ($.trim(comments) === '') {
            //     return false;
            // }
            
            return true;
        }

        function calc_total() {
            var decimal = /^\d+(\.\d{1,2})?/;
            var amt = $('#n_amt').val();
            if (!decimal.test(amt)) {
                amt = 0;
                $('#n_amt').val('0.00');
            }

            var fee_rates = 0.035;
            var fee = amt * fee_rates;
            fee = Math.round(fee * 100) / 100;
            var deposit_amt = amt * 1 + fee;

            $('#n_fee').val(fee.toFixed(2));
            $('#n_deposit_amt').val(deposit_amt.toFixed(2));
        }

        function onChangeStatus(handler) {
            document.querySelector('#n_amt').addEventListener('change', handler);
            // document.querySelector('#n_comments').addEventListener('change', handler);
        }
    </script>

    <h4>Payments Report</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_search" class="form-horizontal" method="post" action="/admin/reports/payments">
            {{ csrf_field() }}
            <input type="hidden" name="excel" id="excel"/>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Date</label>
                        <div class="col-md-8">
                            <input type="text" style="width:100px; float:left;" class="form-control" id="sdate"
                                   name="sdate" value="{{ old('sdate', $sdate) }}"/>
                            <span class="control-label" style="float:left;">&nbsp;~&nbsp;</span>
                            <input type="text" style="width:100px; float:left;" class="form-control" id="edate"
                                   name="edate" value="{{ old('edate', $edate) }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Method</label>
                        <div class="col-md-8">
                            <select class="form-control" name="method">
                                <option value="">All</option>
                                <option value="P" {{ old('method', $method) == 'P' ? 'selected' : '' }}>
                                    PayPal
                                </option>
                                <option value="D" {{ old('method', $method) == 'M' ? 'selected' : '' }}>
                                    Direct Deposit
                                </option>
                                <option value="B" {{ old('method', $method) == 'B' ? 'selected' : '' }}>
                                    Weekly Bill
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Category</label>
                        <div class="col-md-8">
                            <select class="form-control" name="category">
                                <option value="">All</option>
                                <option {{ old('category', $category) == 'Cash' ? 'selected' : '' }}>Cash</option>
                                <option {{ old('category', $category) == 'Check' ? 'selected' : '' }}>Check</option>
                                <option {{ old('category', $category) == 'Credit' ? 'selected' : '' }}>Credit</option>
                                <option {{ old('category', $category) == 'Bank Transfer' ? 'selected' : '' }}>Bank
                                    Transfer
                                </option>
                                <option {{ old('category', $category) == 'Money Order' ? 'selected' : '' }}>Money
                                    Order
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Credit Limit</label>
                        <div class="col-md-8">
                            <span class="form-control" disabled>${{ number_format($credit_limit, 2) }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Available Sales Amount</label>
                        <div class="col-md-8">
                            <span class="form-control" disabled>${{ number_format($balance, 2) }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <div class="form-group">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary btn-sm" id="btn_search" onclick="search()">
                                Search
                            </button>
                            @if ($is_login_as == 'Y')
                                <span class="btn btn-warning btn-sm">Login as user is not allowed to make payment</span>
                            @else
                                <button type="button" class="btn btn-default btn-sm" onclick="show_paypal()">Pay with
                                    PayPal / Credit Card
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
            <th>ID</th>
            <th>Type</th>
            <th>Method</th>
            <th>Category</th>
            <th>Deposit.Amt</th>
            <th>Fee</th>
            <th>Applied.Amt</th>
            <th>Comments</th>
            <th>Created.At</th>
            <th>Created.By</th>
        </tr>
        </thead>
        <tbody>
        @if (isset($data) && count($data) > 0)
            @foreach ($data as $o)
                <tr>
                    <td>{{ $o->id }}</td>
                    <td>{{ $o->type_name }}</td>
                    <td>{{ $o->method_name }}</td>
                    <td>{{ $o->category }}</td>
                    <td>${{ number_format($o->deposit_amt, 2) }}</td>
                    <td>${{ number_format($o->fee, 2) }}</td>
                    <td>${{ number_format($o->amt, 2) }}</td>
                    <td>{{ $o->comments }}</td>
                    <td>{{ $o->cdate }}</td>
                    <td>{{ $o->created_by }}</td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="30" class="text-center">No Record Found</td>
            </tr>
        @endif
        </tbody>
        <tfoot>
        <tr>
            <th colspan="4" class="text-right">Total {{ $data->total() }} record(s).</th>
            <th>${{ number_format($deposit_amt, 2) }}</th>
            <th>${{ number_format($fee, 2) }}</th>
            <th>${{ number_format($amt, 2) }}</th>
            <th colspan="3"></th>
        </tr>
        </tfoot>
    </table>

    <div class="text-right">
        {{ $data->appends(Request::except('page'))->links() }}
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

    <div class="modal" id="div_paypal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Pay with PayPal</h4>
                </div>
                <div class="modal-body">
                    <form class="form-horizontal">
                        <div class="form-group">
                            <label class="control-label col-md-4">Amount($)</label>
                            <div class="col-md-8">
                                <input type="text" class="form-control" id="n_amt" onchange="calc_total()"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-md-4">Fee($)</label>
                            <div class="col-md-8">
                                <input type="text" class="form-control" id="n_fee" disabled/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-md-4">Deposit.Amt($)</label>
                            <div class="col-md-8">
                                <input type="text" class="form-control" id="n_deposit_amt" disabled/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-md-4">Comments</label>
                            <div class="col-md-8">
                                <textarea type="text" class="form-control" id="n_comments" rows="5"
                                          style="width:100%;"></textarea>
                            </div>
                        </div>
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
