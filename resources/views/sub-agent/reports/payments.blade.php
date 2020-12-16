@extends('sub-agent.layout.default')

@section('content')

    <script type="text/javascript">

        var onload_func = window.onload;

        window.onload = function () {

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

                validate: function (actions) {
                    toggleButton(actions);

                    onChangeStatus(function () {
                        toggleButton(actions);
                    });
                },

                onClick: function () {
                    if (!isValid()) {
                        myApp.showError('Please enter valid amount first. The minimum payments is $100');
                    }
                },


                // payment() is called when the button is clicked
                payment: function (data, actions) {

                    // Can not go without payment request ID to PayPal
                    if ($('#n_pr_id').val().length < 1) {
                        alert("We are sorry. Please close current page. Then open and try again!");
                        return actions.redirect();
                    }

                    // Make a call to the REST api to create the payment
                    return actions.payment.create({
                        payment: {
                            transactions: [
                                {
                                    amount: {total: $('#n_deposit_amt').val(), currency: 'USD'},
                                    invoice_number: 'P-' + $('#n_invoice_no').val() + '-' + $('#n_pr_id').val()
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

                        // $('#div_paypal').modal('hide');
                        //
                        // // myApp.showSuccess('Your PayPal payment has been completed! Please refresh the page again if you still can not see the latest result.');
                        // alert('Your PayPal payment has been completed! Please refresh the page again if you still can not see the latest result.');
                        // search();

                        $('#div_paypal').modal('hide');
                        alert('Your PayPal payment has been completed! Please refresh the page again if you still can not see the latest result.');
                        search();
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

        function paypal_confirm() {
            $('#div_paypal_confirm').modal('hide');
            $('#paypal_close').hide();
            $('#paypal_next').hide();
            $('#n_amt').attr("disabled", true);
            $('#n_comments').attr("disabled", true);
            $('#paypal_label').show();
            $('#paypal-button-container').show();
        }

        function modal_close() {
            $('#div_paypal').modal('hide');
            window.location.href = '/sub-agent/reports/payments';
        }

        function next_btn() {
            var amt = $('#n_amt').val();
            if (amt < 100) {
                alert('We are sorry, but the minimum payments is $100.');
                return;
            }

            $.ajax({
                url: '/sub-agent/reports/payments/paypal/pre-save',
                data: {
                    _token: '{!! csrf_token() !!}',
                    account_id: '{{ Auth::user()->account_id }}',
                    deposit_amt: $('#n_deposit_amt').val(),
                    fee: $('#n_fee').val(),
                    amt: $('#n_amt').val(),
                    comments: $('#n_comments').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function (res) {
                    $('#n_pr_id').val(res.pr_id);
                },
                error: function (jqXHR, textStatus, errorThrown) {

                }
            });

            $('#div_paypal_confirm').modal();
        }

        function pay() {

            $('#paypal-button-container').hide();

            var amt = $('#n_amt').val();

            if (amt < 100) {
                alert('We are sorry, but the minimum payments is $100.');
                return;
            }

            myApp.showConfirm("Please click OK button for confirm");

            $.ajax({
                url: '/sub-agent/reports/payments/paypal/pre-save',
                data: {
                    _token: '{!! csrf_token() !!}',
                    account_id: '{{ Auth::user()->account_id }}',
                    deposit_amt: $('#n_deposit_amt').val(),
                    fee: $('#n_fee').val(),
                    amt: $('#n_amt').val(),
                    comments: $('#n_comments').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function (res) {
                    $('#n_pr_id').val(res.pr_id);
                    $('#paypal-button-container').show();
                },
                error: function (jqXHR, textStatus, errorThrown) {

                }
            });
            $('#n_amt').attr("disabled", true);
            $('#paypal-button-container').show();
        }

        function search() {
            $('#excel').val('N');
            $('#frm_search').submit();
        }

        function show_paypal() {
            $('#n_amt').attr("disabled", false);
            $('#div_paypal').modal();
            $('#paypal-button-container').hide();
        }

        function toggleButton(actions) {
            return isValid() ? actions.enable() : actions.disable();
        }

        function isValid() {

            var amt = $('#n_amt').val();

            if (amt < 100) {
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
            //var deposit_amt = $('#n_deposit_amt').val();
            if (!decimal.test(amt)) {
                amt = 0;
                $('#n_amt').val('0.00');
            }

            if (amt < 100) {
                myApp.showError('We are sorry, but the minimum payments is $100.');
                amt = 100;
                $('#n_amt').val('100.00');
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

    <div class="parallax" data-background="/img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
        <div class="overlay white"></div>
        <div class="container">
            <div class="inner-head">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h4>Payments</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li><a href="#">Reports</a></li>
                            <li class="active">Payments</li>
                        </ol>
                    </div>

                    <div class="col-md-12 text-right">
                        <ol class="breadcrumb">
                            <li style="color: black">Suggestion Link</li>
                            <li><a href="/sub-agent/reports/credit">Credit History</a></li>
                            <li><a href="/sub-agent/reports/vr-request">Track My Order</a></li>
                        </ol>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="contain-wrapp padding-bot70">
        <div class="well filter" style="padding-bottom:5px;">
            <form id="frm_search" class="form-horizontal" method="post" action="/sub-agent/reports/payments">
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
                                    <option value="D" {{ old('method', $method) == 'D' ? 'selected' : '' }}>
                                        Direct Deposit
                                    </option>
                                    <option value="A" {{ old('method', $method) == 'A' ? 'selected' : '' }}>
                                        Weekday ACH
                                    </option>
                                    <option value="B" {{ old('method', $method) == 'B' ? 'selected' : '' }}>
                                        Weekly Bill
                                    </option>
                                    <option value="H" {{ old('method', $method) == 'H' ? 'selected' : '' }}>
                                        Cash Pickup
                                    </option>
                                    <option value="C" {{ old('method', $method) == 'C' ? 'selected' : '' }}>
                                        Commission
                                    </option>
                                    <option value="V" {{ old('method', $method) == 'V' ? 'selected' : '' }}>
                                        Void
                                    </option>
                                    <option value="M" {{ old('method', $method) == 'M' ? 'selected' : '' }}>
                                        Manual Credit / Debit
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
{{--                    <div class="col-md-4">--}}
{{--                        <div class="form-group">--}}
{{--                            <label class="col-md-4 control-label">Category</label>--}}
{{--                            <div class="col-md-8">--}}
{{--                                <select class="form-control" name="category">--}}
{{--                                    <option value="">All</option>--}}
{{--                                    <option {{ old('category', $category) == 'Cash' ? 'selected' : '' }}>Cash</option>--}}
{{--                                    <option {{ old('category', $category) == 'Check' ? 'selected' : '' }}>Check</option>--}}
{{--                                    <option {{ old('category', $category) == 'Credit' ? 'selected' : '' }}>Payment Credit</option>--}}
{{--                                    <option {{ old('category', $category) == 'Bank Transfer' ? 'selected' : '' }}>Bank--}}
{{--                                        Transfer--}}
{{--                                    </option>--}}
{{--                                    <option {{ old('category', $category) == 'Money Order' ? 'selected' : '' }}>Money--}}
{{--                                        Order--}}
{{--                                    </option>--}}
{{--                                </select>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
                <!--div class="col-md-4">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Credit Limit</label>
                            <div class="col-md-8">
                                <span class="form-control" disabled>${{ number_format($credit_limit, 2) }}</span>
                            </div>
                        </div>
                    </div-->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="col-md-4 control-label">Current Balance</label>
                            <div class="col-md-8">
                                <span class="form-control" disabled>${{ number_format($balance, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                    </div>
                    <div class="col-md-4 col-md-offset-4 text-right">
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
                <th>Created.At</th>
                <th>Type</th>
                <th>Method</th>
                <th>Category</th>
                <th>Deposit.Amt</th>
                <th>Fee</th>
                <th>Applied.Amt</th>
                <th>Comments</th>
                <th>Created.By</th>
            </tr>
            </thead>
            <tbody>
            @if (isset($data) && count($data) > 0)
                @foreach ($data as $o)
                    @if($o->deposit_amt != '-')
                        <tr>
                            <td>{{ $o->cdate }}</td>
                            @if($o->type == 'P')
                                <td>Prepay</td>
                            @elseif($o->type == 'B')
                                <td>Weekly Billing</td>
                            @elseif($o->type == 'A')
                                <td>Post Pay</td>
                            @elseif($o->type == 'W')
                                <td>Weekday ACH</td>
                            @endif

                            @if($o->method == 'P')
                                <td>PayPal</td>
                            @elseif($o->method == 'D')
                                <td>Direct Deposit</td>
                            @elseif($o->method == 'C')
                                <td>Credit Card</td>
                            @elseif($o->method == 'A')
                                <td>ACH</td>
                            @elseif($o->method == 'B')
                                <td>Weekly Bill</td>
                            @elseif($o->method == 'H')
                                <td>Cash Pickup</td>
                            @endif
                            <td>{{ $o->category }}</td>
                            <td>${{ number_format($o->deposit_amt, 2) }}</td>
                            <td>${{ number_format($o->fee, 2) }}</td>
                            <td>${{ number_format($o->amt, 2) }}</td>
                            <td>{{ $o->comments }}</td>
                            <td>{{ $o->created_by }}</td>
                        </tr>
                    @else
                        <tr>
                            <td>{{ $o->cdate }}</td>
                            <td>{{ $o->type }}</td>
                            <td>{{ $o->method }}</td>
                            <td>{{ $o->category }}</td>
                            <td>-</td>
                            <td>-</td>
                            <td>${{ number_format($o->amt, 2) }}</td>
                            <td>{{ $o->comments }}</td>
                            <td>{{ $o->created_by }}</td>
                        </tr>
                    @endif
                @endforeach
            @else
                <tr>
                    <td colspan="30" class="text-center">No Record Found</td>
                </tr>
            @endif
            </tbody>
            <tfoot>
            {{--            <tr>--}}
            {{--                <th colspan="4" class="text-right">Total {{ $data->total() }} record(s).</th>--}}
            {{--                <th>${{ number_format($deposit_amt, 2) }}</th>--}}
            {{--                <th>${{ number_format($fee, 2) }}</th>--}}
            {{--                <th>${{ number_format($amt, 2) }}</th>--}}
            {{--                <th colspan="3"></th>--}}
            {{--            </tr>--}}
            </tfoot>
        </table>

        <div class="text-right">
            {{ $data->appends(Request::except('page'))->links() }}
        </div>
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
{{--                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">--}}
{{--                        <span aria-hidden="true">x</span></button>--}}

                    <h4 class="modal-title" id="title">Pay with PayPal</h4>
                </div>
                <div class="modal-body">
                    <form class="form-horizontal">

                        {{--                        <input type="hidden" id="n_invoice_no" value="{{ Auth::user()->account_id .--}}
                        {{--                        \Carbon\Carbon::now()--}}
                        {{--                        ->format('mdhm') }}">--}}

                        <input type="hidden" id="n_invoice_no" value="{{ Auth::user()->account_id }}">

                        <input type="hidden" id="n_pr_id" value=""/>
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
                    <label id="paypal_label" style="display: none">Click the <font color="red">PayPal Button Below</font> to complete payment!</label>
                    <div>
                        <button type="button" class="btn btn-default" id="modal_close" onclick="modal_close()">Close</button>
                        <button type="button" class="btn btn-primary" id="paypal_next" onclick="next_btn()">Next</button>
                        <div id="paypal-button-container" class="btn float-right" style="display:none"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="modal" id="div_paypal_confirm" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="title">Please Confirm</h4>
                </div>
                <div class="modal-body">
                    <h5>Please Click OK Button for confirmation. </h5>
                    <br/>
                    <h5>Then, <font color="red">Click PayPal Button to complete your payment</font></h5>
                    <br/><br/><br/><br/><br/>
                </div>

                <div class="modal-footer" style="margin-right:15px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="paypal_confirm" onclick="paypal_confirm()">OK</button>
                    <div id="paypal-button-container" class="btn float-right" style="display:none"></div>
                </div>
            </div>
        </div>
    </div>
@stop
