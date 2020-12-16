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
            border-top:0;
        }

        .content-overlay img {
            opacity: 0.3;
        }

        .content-overlay:hover img {
            opacity: 1.0;
        }

    </style>
    <script type="text/javascript">

        window.onload = function() {
            @if (session()->has('success') && session('success') == 'Y')
                $('#success').modal();
            @endif

            @if ($errors->has('exception'))
                $('#error').modal();
            @endif

            $('#phone').keyup(function() {
                let length = $(this).val().length;
                $('#phone_count').text(length);
            });

            $(".img_1").click(function(){
                $('#carrier').val('AT&T');

                refresh_page();
            });
            // $(".img_2").click(function(){
            //     $('#carrier').val('AT&T');
            //     $('#product_id').val('WATTPVR');
            //     refresh_page();
            // });
            $(".img_3").click(function(){
                $('#carrier').val('Boost Mobile');
                refresh_page();
            });
            $(".img_4").click(function(){
                $('#carrier').val('Cricket');
                refresh_page();
            });
            $(".img_5").click(function(){
                $('#carrier').val('FreeUP');
                refresh_page();
            });
            $(".img_6").click(function(){
                $('#carrier').val('GEN Mobile');
                refresh_page();
            });
            $(".img_7").click(function(){
                $('#carrier').val('Go Smart');
                refresh_page();
            });
            $(".img_8").click(function(){
                $('#carrier').val('H2O');
                refresh_page();
            });
            $(".img_9").click(function(){
                $('#carrier').val('Lyca');
                refresh_page();
            });
            $(".img_10").click(function(){
                $('#carrier').val('MetroPcs');
                refresh_page();
            });
            $(".img_11").click(function(){
                $('#carrier').val('Net 10');
                refresh_page();
            });
            $(".img_12").click(function(){
                $('#carrier').val('Red Pocket');
                refresh_page();
            });
            $(".img_13").click(function(){
                $('#carrier').val('SafeLink');
                refresh_page();
            });
            $(".img_14").click(function(){
                $('#carrier').val('Simple');
                refresh_page();
            });
            $(".img_15").click(function(){
                $('#carrier').val('Telcel America');
                refresh_page();
            });
            $(".img_16").click(function(){
                $('#carrier').val('Tracfone');
                refresh_page();
            });
            $(".img_17").click(function(){
                $('#carrier').val('T-Mobile');
                refresh_page();
            });
            $(".img_18").click(function(){
                $('#carrier').val('Ultra Mobile');
                refresh_page();
            });
            $(".img_19").click(function() {
                $('#carrier').val('Verizon');
                refresh_page();
            })
            $(".img_20").click(function() {
                $('#carrier').val('Liberty Mobile');
                refresh_page();
            })
            $(".img_21").click(function() {
                $('#carrier').val('Boom Mobile');
                refresh_page();
            })
            $(".img_22").click(function() {
                $('#carrier').val('XFINITY');
                refresh_page();
            })
            $(".img_23").click(function() {
                $('#carrier').val('Claro');
                refresh_page();
            })
        };

        function change_logo($name) {
            $('#img_select').attr('src',$name);
        }

        function refresh_page(flag) {
            if(flag =='s'){
                var c = $('#carrier1').val();
                $('#carrier').val(c);
            }
            $('#frm_rtr').attr('action', '/sub-agent/rtr/domestic');
            $('#frm_rtr').submit();
        }

        function confirm_order() {

            @if($carrier == 'Claro')
                var phone = $('#phone').val();
                var country_code = $('#country_code').val();

                if(country_code != undefined) {
                    var code_len = country_code.length;
                    var head_phone = phone.substring(0, code_len);
                    if (country_code == head_phone) {
                        alert("Please enter phone number Without " + country_code + ". It's a Country Code");
                        $('#phone').val('');
                        return;
                    }
                }
            @endif

            var msg = '<h4>REFILL PURCHASE POLICY - Please Note</h4>';
            msg += '<p>';
            msg += '<ul>';
            msg += '    <li>All REFILL (Real Time Replenishments) sales are final.</li>';
            msg += '    <li>This product is NON-REFUNDABLE. Please be sure to print the customer receipt.</li>';
            msg += '    <li>Please re-confirm REFILL Month & Consumer Phone Number prior to making purchase.</li>';
            msg += '</ul>';
            msg += '</p>';

            myApp.showConfirm(msg, function() {
                myApp.showLoading();
                $('#frm_rtr').submit();
            });
        }

        @if ($product_id == 'WBST')
        function get_boost_pin() {

            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/get_boost_pin',
                data: {
                    mdn: $('#phone').val()
                },
                type: 'get',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    myApp.hideLoading();
                    alert(res.msg);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });
        }
        @endif

        function printDiv() {
            window.print();
        }
    </script>

    @if (session()->has('success') && session('success') == 'Y')
        <div id="success" class="modal fade " tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false"
             style="display:block;">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">REFILL - RTR Success</h4>
                    </div>
                    <div class="modal-body receipt">
                        <p class="text-center">
                            Your request has been processed successfully.
                        </p>
                        <div class="row">
                            <div class="col-sm-4">Date / Time</div>
                            <div class="col-sm-8">{{ date('Y-M-d H:i:s') }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Invoice no.</div>
                            <div class="col-sm-8">{{ session('invoice_no') }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Carrier</div>
                            <div class="col-sm-8">{{ session('carrier') }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Product</div>
                            <div class="col-sm-8">{{ session('product') }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Amount</div>
                            <div class="col-sm-8">${{ number_format(session('amount'), 2) }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Refill Month</div>
                            <div class="col-sm-8">{{ session('rtr_month') }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Phone</div>
                            <div class="col-sm-8">{{ session('phone') }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Sub Total</div>
                            <div class="col-sm-8">${{ number_format(session('sub_total'), 2) }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Vendor Fee</div>
                            <div class="col-sm-8">${{ number_format(session('fee'), 2) }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">Total</div>
                            <div class="col-sm-8">${{ number_format(session('total'), 2) }}</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" onclick="printDiv()">Print</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($errors->has('exception'))
        <div id="error" class="modal fade " tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false"
             style="display:block;">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" style="color:red">REFILL - RTR Error</h4>
                    </div>
                    <div class="modal-body">
                        <p>
                            {{ $errors->first('exception') }}
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Start parallax -->
    <div class="parallax no-print" data-background="/img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
        <div class="overlay white"></div>
        <div class="container">
            <div class="inner-head">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h4>REFILL - RTR</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li class="active">REFILL - RTR</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End parallax -->

    <div class="contain-wrapp padding-bot70 no-print">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-sm-12">

                    <div class="form-group" style="margin-bottom:0px;">
                        {!! Helper::get_reminder_refill() !!}
                    </div>

                    <div style="clear:both"></div>

                    <div class="panel panel-default">
                        <div class="panel-body">
                            <form id="frm_rtr" method="post" class="form-horizontal" action="/sub-agent/rtr/domestic/process" onsubmit="myApp.showLoading()">
                                {!! csrf_field() !!}

                                <div class="col-sm-12">
                                    <div class="col-sm-1">
                                    </div>
                                    <div class="col-sm-1">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_1" src="/img/rtr/AT&T.jpg" style="width: 100%; height: 100%; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_21" src="/img/rtr/Boom Mobile.jpg" style="width: 100%; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_3" src="/img/rtr/Boost Mobile.jpg" style="width: 100%; height: 100%; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_4" src="/img/rtr/Cricket.jpg" style="width: 100%; height: 100%; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_5" src="/img/rtr/FreeUP.jpg" style="width: 100%; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_6" src="/img/rtr/GEN Mobile.jpg" style="width: 100%; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_20" src="/img/rtr/Liberty.jpg" style="width: 100%; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_7" src="/img/rtr/Go Smart.png" style="width: 100%; height: 74.75px; margin-bottom: 16px;">
                                    </div>
                                </div>

                                <div class="col-sm-12">
                                    <div class="col-sm-1">
                                    </div>
                                    <div class="col-sm-1">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_8" src="/img/rtr/H2O.jpg" style="width: 100%; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_9" src="/img/rtr/Lyca.jpg" style="width: 100%; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_10" src="/img/rtr/MetroPcs.jpg" style="width: 100%; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_11" src="/img/rtr/Net 10.jpg" style="width: 100%; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_12" src="/img/rtr/Red Pocket.jpg" style="width: 100%; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_13" src="/img/rtr/SafeLink.jpg" style="width: 100%; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_14" src="/img/rtr/Simple.png" style="width: 100%; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_15" src="/img/rtr/Telcel America.jpg" style="width: 100%; margin-bottom: 16px;">
                                    </div>
                                </div>

                                <div class="col-sm-12">
                                    <div class="col-sm-1">
                                    </div>
                                    <div class="col-sm-1">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_16" src="/img/rtr/Tracfone.jpg" style="width: 100%; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_17" src="/img/rtr/T-Mobile.jpg" style="width: 100%; height: 74.75px; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_18" src="/img/rtr/Ultra Mobile.png" style="width: 100%; height: 74.75px; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_22" src="/img/rtr/Xfinity.png" style="width: 100%; height: 74.75px; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_19" src="/img/rtr/Verizon.jpg" style="width: 100%; height: 74.75px; margin-bottom: 16px;">
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <a href="/sub-agent/rtr/dpp">
                                            <img class="img_19" src="/img/rtr/dollarphone.jpg" style="width: 100%; height: 74.75px; margin-bottom: 16px;">
                                        </a>
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <a href="/sub-agent/rtr/boss">
                                            <img class="img_19" src="/img/rtr/boss.jpg" style="width: 100%; height: 74.75px; margin-bottom: 16px;">
                                        </a>
                                    </div>
                                    <div class="col-sm-1 content-overlay">
                                        <img class="img_23" src="/img/rtr/Claro.jpg" style="width: 100%; height: 74.75px; margin-bottom: 16px;">
                                    </div>
                                </div>

                                <div class="col-sm-2">
                                    @if(!empty($carrier))
                                        @if($carrier == 'AT&T')
                                            <img id="img_select" src="/img/rtr/AT&T.jpg" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'Boost Mobile')
                                            <img id="img_select" src="/img/rtr/Boost Mobile.jpg" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'Cricket')
                                            <img id="img_select" src="/img/rtr/Cricket.jpg" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'FreeUP')
                                            <img id="img_select" src="/img/rtr/FreeUP.jpg" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'GEN Mobile')
                                            <img id="img_select" src="/img/rtr/GEN Mobile.jpg" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'Liberty Mobile')
                                            <img id="img_select" src="/img/rtr/Liberty.jpg" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'Go Smart')
                                            <img id="img_select" src="/img/rtr/Go Smart.png" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'H2O')
                                            <img id="img_select" src="/img/rtr/H2O.jpg" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'Lyca')
                                            <img id="img_select" src="/img/rtr/Lyca.jpg" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'MetroPcs')
                                            <img id="img_select" src="/img/rtr/MetroPcs.jpg" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'Net 10')
                                            <img id="img_select" src="/img/rtr/Net 10.jpg" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'Red Pocket')
                                            <img id="img_select" src="/img/rtr/Red Pocket.jpg" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'SafeLink')
                                            <img id="img_select" src="/img/rtr/SafeLink.jpg" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'Simple')
                                            <img id="img_select" src="/img/rtr/Simple.png" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'T-Mobile')
                                            <img id="img_select" src="/img/rtr/T-Mobile.jpg" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'Boom Mobile')
                                            <img id="img_select" src="/img/rtr/Boom Mobile.jpg" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'Telcel America')
                                            <img id="img_select" src="/img/rtr/Telcel America.jpg" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'Tracfone')
                                            <img id="img_select" src="/img/rtr/Tracfone.jpg" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'Ultra Mobile')
                                            <img id="img_select" src="/img/rtr/Ultra Mobile.png" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'XFINITY')
                                            <img id="img_select" src="/img/rtr/Xfinity.png" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'Verizon')
                                            <img id="img_select" src="/img/rtr/Verizon.jpg" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @elseif($carrier == 'Claro')
                                            <img id="img_select" src="/img/rtr/Claro.jpg" style="width: 150px; height: 100px; margin-bottom: 16px;">
                                        @endif
                                    @endif
                                </div>
                                <div class="col-sm-10">

                                    <div class="form-group{{ $errors->has('carrier') ? ' has-error' : '' }}">
                                        <input type="hidden" class="form-control" id="carrier" name="carrier" value="{{ isset($carrier) ? $carrier : old('carrier') }}"/>

                                        <label class="control-label col-sm-1" style="text-align: left">Carrier: </label>
                                        <div class="col-sm-8">

                                            <select class="form-control col-xs-offset-1" name="carrier1" id="carrier1" onchange="refresh_page('s')">
                                                <option value="">Please Select</option>
                                                @if (count($carriers) > 0)
                                                    @foreach ($carriers as $o)
                                                        <option value="{{ $o->name }}" {{ old('carrier1', $carrier) == $o->name ? 'selected' : ''  }}>{{ $o->name }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            @if ($errors->has('carrier'))
                                                <span class="help-block">
                                                <strong>{{ $errors->first('carrier') }}</strong>
                                            </span>
                                            @endif
                                        </div>

                                    </div>

                                    <div class="form-group{{ $errors->has('product_id') ? ' has-error' : '' }}">
                                        <label class="control-label col-sm-1" style="text-align: left">Product: </label>
                                        <div class="col-sm-8">
                                            <select class="form-control col-xs-offset-1" name="product_id" onchange="refresh_page()">
                                                <option value="">Please Select</option>
                                                @if (count($products) > 0)
                                                    @foreach ($products as $o)
                                                        <option value="{{ $o->product_id }}" {{ old('product_id', $product_id) == $o->product_id ? 'selected' : ''  }}>{{ $o->product_name }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            @if ($errors->has('product_id'))
                                                <span class="help-block">
                                                <strong>{{ $errors->first('product_id') }}</strong>
                                            </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="form-group{{ $errors->has('denom_id') ? ' has-error' : '' }}">
                                        <label class="control-label col-sm-1" style="text-align: left">Amount($): </label>
                                        <div class="col-sm-8">
                                            @if ($open_denom == 'N')
                                                <select class="form-control col-xs-offset-1" name="denom_id" onchange="refresh_page()">
                                                    <option value="">Please Select</option>
                                                    @if (count($denominations) > 0)
                                                        @foreach ($denominations as $o)
                                                            <option value="{{ $o->denom_id }}" {{ old('denom_id', $denom_id) == $o->denom_id ? 'selected' : '' }}>${{ number_format($o->denom, 2) }} ({{ $o->name }})</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            @else
                                                <input type="text" name="denom" class="form-control col-xs-offset-1" value="{{ old('denom', $denom) }}" onchange="refresh_page()"/>
                                                @if ($errors->has('denom_id'))
                                                    <span class="help-block">
                                                    <strong>{{ $errors->first('denom_id') }}</strong>
                                                </span>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                    <div class="form-group{{ $errors->has('rtr_month') ? ' has-error' : '' }}">
                                        <label class="control-label col-sm-1" style="text-align: left">REFILL.Month:    </label>
                                        <div class="col-sm-8">
                                            <select class="form-control col-xs-offset-1" name="rtr_month" onchange="refresh_page()">
                                                <option value="1" {{ old('rtr_month', $rtr_month) == 1 ? 'selected' : '' }}>1</option>
                                                <option value="2" {{ old('rtr_month', $rtr_month) == 2 ? 'selected' : '' }}>2</option>
                                                <option value="3" {{ old('rtr_month', $rtr_month) == 3 ? 'selected' : '' }}>3</option>
                                                <option value="4" {{ old('rtr_month', $rtr_month) == 4 ? 'selected' : '' }}>4</option>
                                                <option value="5" {{ old('rtr_month', $rtr_month) == 5 ? 'selected' : '' }}>5</option>
                                                <option value="6" {{ old('rtr_month', $rtr_month) == 6 ? 'selected' : '' }}>6</option>
                                                <option value="9" {{ old('rtr_month', $rtr_month) == 9 ? 'selected' : '' }}>9</option>
                                                <option value="12" {{ old('rtr_month', $rtr_month) == 12 ? 'selected' : '' }}>12</option>
                                            </select>
                                            @if ($errors->has('rtr_month'))
                                                <span class="help-block">
                                                <strong>{{ $errors->first('rtr_month') }}</strong>
                                            </span>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($product_id == 'WXITN')
                                        <div class="form-group{{ $errors->has('zip') ? ' has-error' : '' }}">
                                            <label class="control-label col-sm-1" style="text-align: left">ZIP: </label>
                                            <div class="col-sm-8">
                                                <input type="text" name="zip" maxlength="5" placeholder="Please Insert Zip" class="form-control col-xs-offset-1" value="{{ old('zip') }}"/>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="form-group{{ $errors->has('phone') ? ' has-error' : '' }}">
                                        <label class="control-label col-sm-1" style="text-align: left">Phone:</label>
                                            @if(!empty($country_code))
                                                <input type="hidden" id="country_code" value="{{ $country_code }}"/>
                                                <label class="control-label col-sm-2" style="margin-left: -1%;">Country Code: </label>
                                                <label class="control-label col-sm-1" for="phone" style="margin-left: -4%;">({{ $country_code }})</label>
                                                <div class="col-sm-6">
                                                    <input type="text" id="phone" name="phone" maxlength="15" class="form-control" value="{{ old('phone') }}" style="margin-left: 4%;"/>
                                            @else
                                                <div class="col-sm-8">
                                                    <input type="text" id="phone" name="phone" maxlength="10" class="form-control col-xs-offset-1" value="{{ old('phone') }}"/>
                                            @endif
                                            @if ($errors->has('phone'))
                                            <span class="help-block">
                                            <strong>{{ $errors->first('phone') }}</strong>
                                            </span>
                                            @endif
                                            <div id="count" align="left"
                                                 style="color: red;
                                                 font-size: 12px;
                                                 margin-left: 9%;
                                                 display:inline;">
                                                You have entered in <span id="phone_count" style="font-weight: bold;">0</span> Digits
                                            </div>
                                            @if(!empty($country_code))
                                                <div style="display:inline;">
                                                    <label>(Please do not enter country code)</label>
                                                </div>
                                            @endif

                                        </div>
                                    </div>
                                    @if ($product_id == 'WBST')
                                        <div class="form-group{{ $errors->has('mdn_pin') ? ' has-error' : '' }}">
                                            <label class="control-label col-sm-1" style="text-align: left">Pin: </label>
                                            <div class="col-sm-8">
                                                <input type="text" name="mdn_pin" maxlength="4" class="form-control col-xs-offset-1" value="{{ old('mdn_pin') }}"/>
                                                @if ($errors->has('mdn_pin'))
                                                    <span class="help-block">
                                                <strong>{{ $errors->first('mdn_pin') }}</strong>
                                            </span>
                                                @endif
                                                <a style="cursor: pointer;background-color: red;color:white; margin-left: 9%;" onclick="get_boost_pin()">&nbsp; Please send my PIN with TEXT right now &nbsp;</a>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="divider2"></div>
                                    <div class="form-group">
                                        <label class="control-label col-sm-1"></label>
                                        <div class="col-sm-4">
                                            Amount : ${{ number_format($amt, 2) }}<br/>
                                            Refill.Month : {{ number_format($rtr_month, 0) }}<br/>
                                            <hr style="margin:0px;"/>
                                            Sub Total : ${{ number_format($sub_total, 2) }}<br/>
                                            Vendor Fee : ${{ number_format($fee, 2) }}<br/>
                                            <hr style="margin:0px;"/>
                                            <b>Total : ${{ number_format($total, 2) }}</b>
                                        </div>
                                        <div class="col-sm-4 text-right">
                                            <button type="button" onclick="confirm_order()" class="btn btn-primary">SUBMIT</button>
                                        </div>
                                    </div>

                                </div>


                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <table class="parameter-product table-bordered table-hover table-condensed filter">
                <thead>
                <tr class="active">
                    <td><strong>ID</strong></td>
                    <td><strong>Type</strong></td>
                    <td><strong>Status</strong></td>
                    <td><strong>Note</strong></td>
                    <td><strong>Product</strong></td>
                    <td><strong>Denom($)</strong></td>
                    <td><strong>RTR.M</strong></td>
                    <td><strong>Total($)</strong></td>
                    <td><strong>Vendor.Fee($)</strong></td>
                    <td><strong>Action</strong></td>
                    <td><strong>Phone/PIN</strong></td>
                    <td><strong>User.ID</strong></td>
                    <td><strong>Last.Updated</strong></td>
                </tr>
                </thead>
                <tbody>
                @if (isset($transactions) && count($transactions) > 0)
                    @foreach ($transactions as $o)
                        <tr>
                            <td>
                                @if ($o->status == 'C')
                                    <a target="_RECEIPT" href="/sub-agent/reports/receipt/{{ $o->id }}">{{ $o->id }}</a>
                                @else
                                    {{ $o->id }}
                                @endif
                            </td>
                            <td style="{{ $o->type_name == 'Void' ? 'color:red;' : '' }}">{{ $o->type_name }}</td>
                            @if ($o->status == 'R')
                                <td><a href="/sub-agent/reports/transaction/{{ $o->id }}">{!! $o->status_name() !!}</a></td>
                            @else
                                <td>{!! $o->status_name() !!}</td>
                            @endif
                            <td>
                                @if (!empty($o->note))
                                    {{ $o->note }}
                                @else
                                @endif
                            </td>
                            <td>{{ $o->product_name  }}</td>
                            <td>${{ $o->denom }}</td>
                            <td>{{ $o->rtr_month }}</td>
                            <td style="{{ $o->type_name == 'Void' ? 'color:red;' : '' }}">${{ $o->collection_amt }}</td>
                            <td style="{{ $o->type_name == 'Void' ? 'color:red;' : '' }}">${{ $o->fee + $o->pm_fee}}</td>
                            <td>{{ $o->action }}</td>
                            <td>{{ $o->action == 'PIN' ? Helper::mask_pin($o->pin) : $o->phone }}</td>
                            <td>{{ $o->created_by }}</td>
                            <td>{{ $o->last_updated }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="20" class="text-center">No Record Found</td>
                    </tr>
                @endif
                </tbody>
            </table>
        </div>
    </div>
@stop