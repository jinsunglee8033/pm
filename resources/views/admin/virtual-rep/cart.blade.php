@extends('admin.layout.default')

@section('content')

    <script type="text/javascript">
        @if (!empty($vr))
        window.onload = function() {
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
                                    amount: {total: $('#vr_total_amt').val(), currency: 'USD'},
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
                        // myApp.showLoading();

                        // myApp.showSuccess('Your PayPal payment has been completed! Please refresh the page again if you still can not see the latest result.');
                        alert('Your PayPal payment has been completed! Please refresh the page again if you still can not see the latest result.');

                        location.href="/admin/reports/vr-request";

                        {{--$.ajax({--}}
                        {{--    url: '/admin/virtual-rep/cart/paid',--}}
                        {{--    data: {--}}
                        {{--        _token: '{!! csrf_token() !!}',--}}
                        {{--        vr_id: $('#vr_id').val(),--}}
                        {{--        amt: $('#vr_total_amt').val(),--}}
                        {{--        comments: $('#paypal_comments').val(),--}}
                        {{--        order_notes: $('#order_notes').val(),--}}
                        {{--        promo_code: $('#promo_code').val(),--}}
                        {{--        payer_id: data.payerID,--}}
                        {{--        payment_id: data.payerID,--}}
                        {{--        payment_token: data.paymentToken--}}
                        {{--    },--}}
                        {{--    cache: false,--}}
                        {{--    type: 'post',--}}
                        {{--    dataType: 'json',--}}
                        {{--    success: function (res) {--}}

                        {{--        myApp.hideLoading();--}}
                        {{--        if ($.trim(res.msg) === '') {--}}
                        {{--            myApp.showSuccess('PayPal payment completed!');--}}

                        {{--            location.href="/admin/reports/vr-request";--}}
                        {{--        } else {--}}
                        {{--            myApp.showError(res.msg);--}}
                        {{--        }--}}

                        {{--    },--}}
                        {{--    error: function (jqXHR, textStatus, errorThrown) {--}}
                        {{--        myApp.hideLoading();--}}
                        {{--        myApp.showError(errorThrown);--}}
                        {{--    }--}}
                        {{--});--}}

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

        }

        function toggleButton(actions) {
            return isValid() ? actions.enable() : actions.disable();
        }

        function isValid() {
            var amt = $('#vr_amt').val();

            if (amt <= 0) {
                return false;
            }

            $('#div_paypal').modal('hide');
            return true;
        }

        function do_proceed() {
            var mtd = $('input[name=payment_method]:checked').val();

            if (mtd == undefined){
                alert("Please Select Payment Method");
                return;
            }

            if (mtd == 'PayPal') {
                $.ajax({
                    url: '/admin/virtual-rep/cart/status',
                    data: {
                        vr_id: $('#vr_id').val(),
                        address1: $('#address1').val(),
                        address2: $('#address2').val(),
                        city: $('#city').val(),
                        state: $('#state').val(),
                        zip: $('#zip').val()
                    },
                    cache: false,
                    type: 'get',
                    dataType: 'json',
                    success: function (res) {

                        if (res.code == '0') {
                            if (res.status !== 'CT') {
                                myApp.showError('Your order status is already updated. Please contact SoftPayPlus.');
                                location.href="/admin/reports/vr-request";
                                return;
                            }
                        } else {
                            myApp.showError(res.msg);
                            return;
                        }

                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        myApp.hideLoading();
                        myApp.showError(errorThrown);
                        return;
                    }
                });

                $('#div_paypal').modal();
            } else {
                myApp.showLoading();

                $.ajax({
                    url: '/admin/virtual-rep/cart/cod',
                    data: {
                        order_notes: $('#order_notes').val(),
                        promo_code: $('#promo_code').val(),
                        payment_method: mtd,
                        address1: $('#address1').val(),
                        address2: $('#address2').val(),
                        city: $('#city').val(),
                        state: $('#state').val(),
                        zip: $('#zip').val()
                    },
                    cache: false,
                    type: 'get',
                    dataType: 'json',
                    success: function (res) {

                        myApp.hideLoading();
                        if ($.trim(res.msg) === '') {
                            location.href="/admin/reports/vr-request-for-master";
                        } else {
                            myApp.showError(res.msg);
                        }

                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        myApp.hideLoading();
                        myApp.showError(errorThrown);
                    }
                });

            }
        }
        @endif

        function add_to_cart(id) {

            var qty = parseInt($('#qty_' + id).val());

            if (qty < 1) {
                myApp.showError('Please enter at least one');
                return;
            }

            $.ajax({
                url: '/admin/virtual-rep/add_to_cart',
                data: {
                    id: id,
                    qty: qty
                },
                cache: false,
                type: 'get',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        // myApp.showSuccess('Your request has been successfully submitted!');
                        location.href = "/admin/virtual-rep/cart";
                    } else {
                        $('#qty_'+id).val('0');
                        alert(res.msg);
                        location.href = "/admin/virtual-rep/cart";
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });
        }

        var currentValue = 0;
        function handleClick(myRadio) {
            currentValue = myRadio.value;

            if(currentValue == 'option') {
                $('#proceed').hide();
                $('#contact').show();
            }else if(currentValue == 'PayPal'){
                $('#proceed').show();
                $('#contact').hide();
            }
        }

        function contact_me() {

            $.ajax({
                url: '/admin/virtual-rep/cart/contact_me',
                data: {
                    vr_id: $('#vr_id').val(),
                    vr_account_id : $('#vr_account_id').val()
                },
                cache: false,
                type: 'get',
                dataType: 'json',
                success: function (res) {
                    if (res.code == '0') {
                        myApp.showSuccess('Your request has been successfully submitted!');
                        location.href = "/admin/virtual-rep/shop";
                        return;
                    } else {
                        myApp.showError(res.msg);
                        return;
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                    return;
                }
            });

        }

    </script>

    <!-- Start parallax -->
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
                            <li class="active">View Cart / Proceed to Checkout</li>
                            <li><a href="/admin/reports/vr-request-for-master">Track My Order</a></li>
                            <li><a href="/admin/virtual-rep/general_request">General Request</a></li>
                        </ol>
                        </b>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End parallax -->

    <div class="text-center">
        <a href="/admin/virtual-rep/shop" class="btn btn-default" style="margin-bottom: -10px; margin-top: 10px;">Continue Shopping</a>
    </div>

    <!-- Start contain wrapp -->
    <div class="contain-wrapp"> 
        <div class="container">
            @if (empty($vr) || empty($vrp))
            @else
            <div class="row">
                <!-- Start shopping cart -->
                <div class="col-md-12">
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th class="text-left"></th>
                                <th class="text-left"> </th>
                                <th class="text-left">Category </th>        
                                <th class="text-left">SubCategory </th>                             
                                <th class="text-left">Month.Service</th>
                                <th class="text-left">Plan </th>
                                <th class="text-left">Make </th>
                                <th class="text-left">Type </th>
                                <th class="text-left">Grade  </th>
                                <th class="text-left">Price</th>
                                <th class="text-left">Qty</th>
                                <th class="text-left">Remove</th>
                            </tr>
                        </thead>

                        <tbody>
                                <?php
                                    $idx = 1;
                                ?>
                            @foreach ($vrp as $v)
                            <tr>
                                <td data-title="No">{{ $idx++ }}</td>
                                <td data-title="Product">
                                    <a href="#" class="alignleft"><img src="{{ empty($v->product->url) ? '/img/no_image.jpg' : $v->product->url }}" alt="" style="max-width: 78px;"></a>
                                    <h6 class="item-title"><a href="#">{{ $v->product->model }}</a><br />
                                    {{ $v->product->carrier }}</h6>
                                </td>
                                <td data-title="">{{ $v->product->category }}</td>
                                <td data-title="">{{ $v->product->sub_category }}</td>
                                <td data-title="">{{ empty($v->product->service_month) ? '' : $v->product->service_month . ' months' }} </td>
                                <td data-title="">{{ $v->product->plan }}</td>
                                <td data-title="">{{ $v->product->make }}</td>
                                <td data-title="">{{ $v->product->type }}</td>
                                <td data-title="">{{ $v->product->grade }}</td>

                                <td data-title="Price" style="width:100px;">${{ number_format($v->order_price / $v->qty ,2) }}</td>

                                <td data-title="">
                                    @if($v->product->category != 'SHIPPING FEE')
                                        <input type="text" id="qty_{{ $v->product->id }}" onchange="add_to_cart({{ $v->product->id }})" class="form-control2" value="{{ $v->qty }}" style="width:60px;">
                                    @endif
                                </td>
                                <td data-title="Remove">
                                @if($v->product->category != 'SHIPPING FEE')
                                    <a href="/admin/virtual-rep/cart/remove/{{ $v->id }}" class="remove"><i class="fa fa-remove"></i></a>
                                @endif
                                </td>
                            </tr>
                            @endforeach   
                        </tbody>                            
                    </table>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="shoppingcart-action">
                                <p class="grand-total"><label></label></p>
                            </div>
                            <div class="divider2"></div>
                            <div class="row">
                                <div class="col-sm-6" style="text-align: right;font-size: 18px;">
                                    <label>Shipping Method</label>
                                </div>
                                <div class="col-sm-6">
                                    <select id="shipping_method" class="form-control" style="font-size: 16px;"
                                            onchange="update_shipping_method()">
                                        <option value="S" {{ $vr->shipping_method == 'S' ? 'selected' : '' }}>Shipping</option>
                                        <option value="P" {{ $vr->shipping_method == 'P' ? 'selected' : '' }}>In Store Pick UP</option>
                                    </select>
                                </div>
                            </div>
                            <script>
                                function update_shipping_method() {
                                    window.location.href = '/admin/virtual-rep/cart/shipping_method/' + $('#shipping_method').val();
                                }
                            </script>
                            <div class="divider2"></div>

                        </div>

                        <div class="col-md-6">
                            <div class="shoppingcart-action">
                                <p class="grand-total"><label>Price :</label> <span class="primary">${{ number_format($vr->price,2) }}</span></p>
                            </div>
                            <div class="divider2"></div>
                            <div class="shoppingcart-action">
                                <p class="grand-total"><label>Shipping :</label> <span class="primary">${{ number_format($vr->shipping,2) }}</span></p>
                            </div>
                            <div class="divider2"></div>
                            <div class="shoppingcart-action">
                                <p class="grand-total"><label>Grand total :</label> <span class="primary">${{ number_format($vr->total,2) }}</span></p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="divider2"></div>

            <div class="row">

                <div class="col-md-1">
                    <h5>Address 1:</h5>
                </div>
                <div class="col-md-3">
                    <textarea class="form-control" rows="2" id="address1">{{ $account->address1 }}</textarea>
                </div>

                <div class="col-md-1">
                    <h5>Address 2:</h5>
                </div>
                <div class="col-md-1">
                    <textarea class="form-control" rows="2" id="address2">{{ $account->address2 }}</textarea>
                </div>

                <div class="col-md-1">
                    <h5>City:</h5>
                </div>
                <div class="col-md-1">
                    <textarea class="form-control" rows="2" id="city">{{ $account->city }}</textarea>
                </div>

                <div class="col-md-1">
                    <h5>State:</h5>
                </div>
                <div class="col-md-1">
                    <textarea class="form-control" rows="2" id="state">{{ $account->state }}</textarea>
                </div>

                <div class="col-md-1">
                    <h5>Zip:</h5>
                </div>
                <div class="col-md-1">
                    <textarea class="form-control" rows="2" id="zip">{{ $account->zip }}</textarea>
                </div>

                <div class="divider2"></div>

                <div class="col-md-3 col-sm-3 col-xs-12">
                    <h5>Promo code:</h5>
                </div>
                <div class="col-md-9 col-sm-9 col-xs-12">
                    <textarea class="form-control" rows="2" id="promo_code" placeholder="Promo Code">{{ $vr->promo_code }}</textarea>
                </div>

                <div class="divider2"></div>

                <div class="col-md-3 col-sm-3 col-xs-12">
                    <h5>Order Note:</h5>
                </div>
                <div class="col-md-9 col-sm-9 col-xs-12">
                    @if ($vr->shipping_method == 'P')
                        <strong style="color:red;">When do you want to pickup ?</strong>
                    @endif
                    <textarea class="form-control" rows="4" id="order_notes" placeholder="Order note">{{ $vr->order }}</textarea>
                </div>


                @if (!empty($vr->op_comments))
                <div class="divider3"></div>

                <div class="col-md-12"> 
                    <div class="row margin-top10">
                        <div class="col-sm-12 margin-bot10">
                            <strong>Admin Notes</strong><br>
                            <textarea class="form-control" rows="4" disabled>{{ $vr->op_comments }}</textarea>
                        </div>
                    </div>
                </div>
                @endif

                <div class="divider2"></div>

                @php
                    $is_login_as = \App\Lib\Helper::is_login_as() ? 'Y' : 'N';
                    $is_super_user =
                        $is_login_as == 'N' ? 'N' : (in_array(Session::get('login-as-user')->user_id, ['thomas', 'admin', 'system']) ? 'Y' : 'N');
                @endphp

                @if ($is_login_as == 'Y' && $is_super_user == 'N')
                    <span class="btn btn-warning btn-sm">Login as not allowed to do payment</span>
                @else
                <div class="col-md-3 col-sm-3 col-xs-12">   
                <h5>Payment Method</h5>
                </div>
                <div class="col-md-6 col-sm-6 col-xs-12">   

                    <form class="form-horizontal">

                        @if ($is_super_user != 'Y')
                            @if ($vr->total != 0)
                            <div class="form-group">
                                <div class="checkbox col-sm-12">
                                    <label>
                                        <input type="radio" name="payment_method" name="payment_method" value="PayPal" checked onclick="handleClick(this)"> Paypal
                                    </label>
                                </div>
                            </div>
                            @endif
                            <div class="form-group">
                                <div class="checkbox col-sm-12">
                                    <label style="color:red;">
                                        <input type="radio" name="payment_method" name="payment_method" value="option" onclick="handleClick(this)">
                                        Other Option (Request Review)
                                    </label>
                                    <p>Item(s) will remain in the cart until further approval.</p>
                                </div>
                            </div>
                        @endif

                        @if ($is_login_as == 'Y' && $is_super_user == 'Y')

                            @if ($vr->total != 0)
                            <div class="form-group">
                                <div class="checkbox col-sm-12">
                                    <label>
                                        <input type="radio" name="payment_method" name="payment_method" value="PayPal" checked> Paypal
                                    </label>
                                </div>
                            </div>
                            @endif
                            <div class="form-group">
                                <div class="checkbox col-sm-12">
                                    <label>
                                        <input type="radio" name="payment_method" value="Direct Deposit"> Direct Deposit
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox col-sm-12">
                                    <label>
                                        <input type="radio" name="payment_method" value="Balance"> Pay by Account Balance
                                    </label>
                                </div>
                            </div>
                        @endif
                    </form>
                </div>              

                <div class="col-md-3 col-sm-3 col-xs-12" style="float: right">
                    <a type="submit" class="btn btn-primary btn-block" id="proceed" onclick="do_proceed()" >Proceed to checkout</a>
                    <a type="submit" class="btn btn-primary btn-block" id="contact" onclick="contact_me()" style="display: none">Contact Me</a>
                </div>
                @endif

            </div>
            @endif
        </div>
    </div>

    @if (!empty($vr))
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
                        <input type="hidden" id="vr_id" value="{{ $vr->id }}"/>
                        <input type="hidden" id="vr_account_id" value="{{ $vr->account_id }}"/>
                        <div class="form-group">
                            <label class="control-label col-md-4">Amount($)</label>
                            <div class="col-md-8">
                                <input type="text" class="form-control" id="vr_amt" value="{{ $vr->price }}" disabled/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-md-4">Shipping($)</label>
                            <div class="col-md-8">
                                <input type="text" class="form-control" id="vr_shipping_amt" value="{{ $vr->shipping }}" disabled/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-md-4">Total($)</label>
                            <div class="col-md-8">
                                <input type="text" class="form-control" id="vr_total_amt" value="{{ $vr->total }}" disabled/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-md-4">Comments</label>
                            <div class="col-md-8">
                                <textarea type="text" class="form-control" id="paypal_comments" rows="3" style="width:100%;"></textarea>
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
    @endif
    <!-- End contain wrapp -->


@stop
