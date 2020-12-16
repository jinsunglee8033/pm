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

            @if (!empty($trans))
            $('#activate_invoice').modal();
            @endif

            $('[data-toggle="popover-hover"]').popover({
                html: true,
                trigger: 'hover',
                content: function () { return '<img src="' + $(this).data('img') + '" />'; }
            });

        };

        function rokit_pin() {
            $('#frm_rokit_pin').submit();
        }

        function printDiv() {
            window.print();
        }


    </script>


    <!-- Start parallax -->
    <div class="parallax no-print" data-background="/img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
        <div class="overlay white"></div>
        <div class="container">
            <div class="inner-head">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h4>ROKiT</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li class="active">ROKiT</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End parallax -->

    @php
        $promotion = Helper::get_promotion('ROKiT');
    @endphp
    @if (!empty($promotion))
        <div class="news-headline no-print">
            {!!$promotion !!}
        </div>
    @endif

    <form id="frm_rokit_pin" method="post" action="/sub-agent/pin/domestic">
        {!! csrf_field() !!}
        <input type="hidden" name="carrier" value="ROKiT">
    </form>

    <!-- Start contain wrapp -->
        <div class="contain-wrapp padding-bot70 no-print" style="">
            <div class="container">
                <div class="row">
                    <div class="col-md-12 col-sm-12">
                        <div class="clearfix"></div>
                        <div class="tabbable tab">
                            <ul class="nav nav-tabs">
                                <li class="active">
                                    <a href="/sub-agent/activate/rokit" class="black-tab">ROKiT Activation</a>
                                </li>
                                <li>
                                    <a onclick="rokit_pin()" style="cursor: pointer;">ROKiT PIN</a>
                                </li>
                            </ul>

                            <!-- Tab panes -->
                            <div class="tab-content" style="padding-top:36px;">
                                <form id="frm_act" method="post" class="row marginbot15">
                                    {!! csrf_field() !!}
                                    <div class="col-sm-2">
                                        <img src="/img/rokit.jpg" style="width: 250px; margin-bottom: 16px;">
                                    </div>

                                    @if (Helper::over_activation('ROKiT') != '')
                                        {!! Helper::over_activation('ROKiT') !!}
                                    @else

                                    @endif
                                        <!-- End info box -->
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                {!! Helper::get_reminder('ROKiT') !!}

            </div>
        </div>
    <!-- End contain wrapp -->
@stop
