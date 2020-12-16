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
            $('.note-check-box').tooltip();

            $('#sim').keyup(function() {
                let length = $(this).val().length;
                $('#sim_count').text(length);
            });

            $('#phone').keyup(function() {
                let length = $(this).val().length;
                $('#phone_count').text(length);
            });

            $('[data-toggle="popover-hover"]').popover({
                html: true,
                trigger: 'hover',
                content: function () { return '<img src="' + $(this).data('img') + '" />'; }
            });

        };

        function request_sim_swap() {

            var phone       = $('#phone').val();
            var sim         = $('#sim').val();
            var network     = $('input[name=network]:checked').val();

            if(!network){
                alert("Please Select Network");
                return;
            }
            if(phone.length < 1){
                alert("Please Insert Phone Number");
                return;
            }
            if(phone.length != 10){
                alert("Length Must be 10 Digits");
                return;
            }
            if(sim.length < 1){
                alert("Please Insert SIM");
                return;
            }
            if(sim.length != 20){
                alert("Length Must be 20 Digits");
                return;
            }

            var data = {
                _token: '{{ csrf_token() }}',
                phone: phone,
                sim: sim,
                network :network
            };

            $('#loading-modal-new').modal('show');

            $.ajax({
                url: '/sub-agent/tools/boom/post',
                data: data,
                type: 'post',
                dataType: 'json',
                cache: false,
                success: function(res) {
                    $('#loading-modal-new').modal('hide');
                    if (res.code == '0') {
                        myApp.showSuccess('Your request completed!', function(){
                            window.location.href = '/sub-agent/tools/boom';
                        });
                    } else {
                        myApp.showError(res.msg);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $('#loading-modal-new').modal('hide');
                    myApp.showError(errorThrown);
                }
            });
        }

        function printDiv() {
            window.print();
        }

        function boom_recharge_rtr() {
            $('#frm_boom_rtr').submit();
        }

        function boom_transactions() {
            $('#frm_boom_transaction').submit();
        }

        function boom_sim_order() {
            $('#frm_boom_sim_order').submit();
        }

        function boom_device_order() {
            $('#frm_boom_device_order').submit();
        }

    </script>

    <!-- Start parallax -->
    <div class="parallax no-print" data-background="/img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
        <div class="overlay white"></div>
        <div class="container">
            <div class="inner-head">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h4>SIM SWAP</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li class="active">Boom SIM SWAP</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End parallax -->

    <div class="modal" tabindex="-1" role="dialog" id="loading-modal-new" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Please wait up to </br>1 minutes or more...</h4>
                </div>
                <div class="modal-body">
                    <div class="progress" style="margin-top:20px;">
                        <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
                            <span class="sr-only"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Start contain wrapp -->
    <div class="contain-wrapp padding-bot70 no-print" style="">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-sm-12">
                    <div class="clearfix"></div>
                    <div class="tabbable tab">
                        <ul class="nav nav-tabs">
                            <li>
                                <a href="/sub-agent/activate/boom_blue" style="color: blue">Boom Blue Activation</a>
                            </li>
                            <li>
                                <a href="/sub-agent/activate/boom_red" style="color: red">Boom Red Activation</a>
                            </li>
                            <li>
                                <a href="/sub-agent/activate/boom_purple" style="color: #6600ff">Boom Purple Activation</a>
                            </li>
                            <li>
                                <a href="/sub-agent/rtr/boom">Boom Refill</a>
                            </li>
                            <li class="active">
                                <a href="/sub-agent/tools/boom" class="black-tab">SIM SWAP</a>
                            </li>
                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content" style="padding-top:36px;">
                            <form id="frm_act" method="post" class="row marginbot15">
                                {!! csrf_field() !!}

                                <div class="col-sm-2">
                                    <img src="/img/category-img-boom-3.jpg" style="width: 250px; margin-bottom: 16px;">
                                </div>

                                @if ($account->act_boom == 'Y')

                                    <div class="col-sm-8">
                                        <div class="col-sm-4" align="right">
                                            <div class="form-group">
                                                <label class="required">Network</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-5">
                                            <div class="form-group">
                                                <label style="color: blue"><input type="radio" name="network" id="network_blue" value="BLUE" />  BLUE </label> &nbsp;&nbsp;
                                                <label style="color: red"><input type="radio" name="network" id="network_red" value="RED" /> RED </label>&nbsp;&nbsp;
                                                <label style="color: #6600ff"><input type="radio" name="network" id="network_purple" value="PURPLE" /> PURPLE </label>&nbsp;&nbsp;
                                                <div id="error_msg_esn"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-sm-8">
                                        <div class="col-sm-4" align="right">
                                            <div class="form-group">
                                                <label class="required">Phone</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-5" align="right" style="padding-top: 3px;">
                                            <div class="form-group">
                                                <input type="text" class="form-control"
                                                       id="phone"
                                                       name="phone"
                                                       value=""
                                                       maxlength="10"
                                                       placeholder="10 digits and digits only"/>
                                                <div id="count" align="left" style="color: red;
                                                        font-size: 12px;
                                                        margin-left: 10px;">
                                                    You have entered in <span id="phone_count" style="font-weight: bold;">0</span> Digits
                                                </div>
                                                <div id="error_msg_phone"></div>
                                            </div>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-8">
                                        <div class="col-sm-4" align="right">
                                            <div class="form-group">
                                                <label class="required">New SIM</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-5" align="right" style="padding-top: 3px;">
                                            <div class="form-group">
                                                <input type="text" class="form-control"
                                                   id="sim"
                                                   name="sim"
                                                   value=""
                                                   maxlength="20"
                                                   placeholder="20 digits and digits only"/>
                                                <div id="count" align="left" style="color: red;
                                                        font-size: 12px;
                                                        margin-left: 10px;">
                                                    You have entered in <span id="sim_count" style="font-weight: bold;">0</span> Digits
                                                </div>
                                                <div id="error_msg_sim"></div>
                                            </div>
                                            <div class="divider2"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-8 marginbot10" style="margin-top: 16px;">
                                        <div class="col-md-7" align="right"></div>
                                        <div class="col-md-4 col-sm-5" align="right">
                                            <button id="act_btn" type="button" class="btn btn-primary" style="margin-top: 16px;" onclick="request_sim_swap()">
                                                Submit
                                            </button>
                                        </div>
                                        <div class="col-md-1"></div>
                                    </div>
                                    @else
                                        <div class="col-sm-8" align="left" style="color: red; font-size: 20px; margin-left: 140px;">
                                            <p>Activation required: You are not authorized agent yet. Please go through become an agent process first.</p> </br>
                                        </div>
                                    @endif

                            </form>

                        </div>

                    </div>

                </div>

            </div>

        </div>
    </div>
    <!-- End contain wrapp -->
@stop
