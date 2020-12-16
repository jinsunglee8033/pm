@extends('sub-agent.layout.default')

@section('content')

    <script type="text/javascript">
        function show_sub_dealer_agreemnt() {
            window.open('{{ url('/esig') }}', '_blank');
            $('#sub-dealer-agreement-popup').modal('hide')
        }

        function pin(carrier) {
        	$('#p_carrier').val(carrier);
            $('#frm_pin').submit();
        }

        function recharge(carrier) {
        	$('#r_carrier').val(carrier);
            $('#frm_rtr').submit();
        }

        function transactions(carrier) {
        	$('#t_carrier').val(carrier);
            $('#frm_transaction').submit();
        }
    </script>

   
<!-- Start contain wrapp -->

<div id="blog" class="contain-wrapp full-recent-post  gray-container">

	<div class="container">
		<div class="row" style="margin-top: -80px;">

			{!! Helper::get_headline_only_sub() !!}

		</div>
		<div class="row">
			<div class="col-md-8 col-md-offset-2" style="margin-bottom: 10px;">
				<div class="img-wrapper">
					<img src="img/logo-white.png" class="img-responsive" alt="" />
				</div>
			</div>
		</div>

		<div style="width: 100%; text-align: center; overflow: hidden;">
			<form id="frm_pin" method="post" action="/sub-agent/pin/domestic">
				{!! csrf_field() !!}
				<input type="hidden" id="p_carrier" name="carrier" value="">
			</form>
			<form id="frm_rtr" method="post" action="/sub-agent/rtr/domestic">
				{!! csrf_field() !!}
				<input type="hidden" id="r_carrier" name="carrier" value="">
			</form>
			<form id="frm_transaction" method="post" action="/sub-agent/reports/transaction">
				{!! csrf_field() !!}
				<input type="hidden" id="t_carrier" name="carrier" value="">
			</form>

			<div class="pm_container">
			  	<img src="img/category-img-att-usa.jpg" class="pm_image">
				<div class="pm_overlayer">
					<div class="pm_link_text"><a href="/sub-agent/activate/att">Activation</a></div>
					<div class="pm_link_text"><a onclick="recharge('AT&T')" style="cursor: pointer;">Recharge</a></div>
					<div class="pm_link_text"><a onclick="transactions('AT&T')" style="cursor: pointer;">Transactions</a></div>
					<div class="pm_link_text"><a href="/sub-agent/tools/att">Tools</a></div>
				</div>
			</div>

{{--			<div class="pm_container">--}}
{{--				<img src="img/category-img-attprvi.jpg" class="pm_image">--}}
{{--				<div class="pm_overlayer">--}}
{{--					<div class="pm_link_text"><a href="/sub-agent/activate/attprvi">Activation</a></div>--}}
{{--					<div class="pm_link_text"><a onclick="recharge('AT&T')" style="cursor: pointer;">Recharge</a></div>--}}
{{--					<div class="pm_link_text"><a onclick="transactions('AT&T')" style="cursor: pointer;">Transactions</a></div>--}}
{{--					<div class="pm_link_text"><a href="#">Tools</a></div>--}}
{{--				</div>--}}
{{--			</div>--}}

{{--			<div class="pm_container">--}}
{{--				<img src="img/category-img-att-dataonly.jpg" class="pm_image">--}}
{{--				<div class="pm_overlayer">--}}
{{--					<div class="pm_link_text"><a href="/sub-agent/activate/attdataonly">Activation</a></div>--}}
{{--					<div class="pm_link_text"><a onclick="recharge('AT&T')" style="cursor: pointer;">Recharge</a></div>--}}
{{--					<div class="pm_link_text"><a onclick="transactions('AT&T')" style="cursor: pointer;">Transactions</a></div>--}}
{{--					<div class="pm_link_text"><a href="/sub-agent/tools/att">Tools</a></div>--}}
{{--				</div>--}}
{{--			</div>--}}

			<div class="pm_container">
			  	<img src="img/category-img-h2o.jpg" class="pm_image">
				<div class="pm_overlayer">
					<div class="pm_link_text"><a href="/sub-agent/activate/h2oe">Activation</a></div>
					<div class="pm_link_text"><a onclick="recharge('H2O')">Recharge</a></div>
					<div class="pm_link_text"><a onclick="transactions('H2O')">Transactions</a></div>
					<div class="pm_link_text"><a href="#">Tools</a></div>
				</div>
			</div>


			<div class="pm_container">
			  	<img src="img/category-img-003.jpg" class="pm_image">
				<div class="pm_overlayer">
					<div class="pm_link_text"><a href="/sub-agent/activate/lyca">Activation</a></div>
					<div class="pm_link_text"><a onclick="recharge('Lyca')" style="cursor: pointer;">Recharge</a></div>
					<div class="pm_link_text"><a onclick="transactions('Lyca')" style="cursor: pointer;">Transactions</a></div>
					<div class="pm_link_text"><a href="#">Tools</a></div>
				</div>
			</div>

			<div class="pm_container">
			  	<img src="img/category-img-liberty.jpg" class="pm_image">
				<div class="pm_overlayer">
					<div class="pm_link_text"><a href="/sub-agent/activate/liberty">Activation</a></div>
					<div class="pm_link_text"><a onclick="recharge('Liberty Mobile')" style="cursor: pointer;">Recharge</a></div>
					<div class="pm_link_text"><a onclick="transactions('Liberty Mobile')" style="cursor: pointer;">Transactions</a></div>
					<div class="pm_link_text"><a href="#">Tools</a></div>
				</div>
			</div>

			<div class="pm_container">
			  	<img src="img/freeup_main.jpg" class="pm_image">
				<div class="pm_overlayer">
					<div class="pm_link_text"><a href="/sub-agent/activate/freeup">Activation</a></div>
					<div class="pm_link_text"><a onclick="recharge('FreeUP')" style="cursor: pointer;">Recharge</a></div>
					<div class="pm_link_text"><a onclick="transactions('FreeUP')" style="cursor: pointer;">Transactions</a></div>
					<div class="pm_link_text"><a href="#">Tools</a></div>
				</div>
			</div>

			<div class="pm_container">
			  	<img src="img/category-img-gen.jpg" class="pm_image">
				<div class="pm_overlayer">
					<div class="pm_link_text"><a href="/sub-agent/activate/gen">Activation (SPR)</a></div>
					<div class="pm_link_text"><a href="/sub-agent/activate/gen_tmo">Activation (TMO)</a></div>
					<div class="pm_link_text"><a href="/sub-agent/rtr/gen">Recharge</a></div>
					<div class="pm_link_text"><a onclick="transactions('GEN Mobile')" style="cursor: pointer;
			">Transactions</a></div>
				</div>
			</div>

			<div class="pm_container">
				<img src="img/category-img-boom-3.jpg" class="pm_image">
				<div class="pm_overlayer">
					<div class="pm_link_text"><a href="/sub-agent/activate/boom_blue" style="color: blue">Boom Blue Activation</a></div>
					<div class="pm_link_text"><a href="/sub-agent/activate/boom_red" style="color: red">Boom Red Activation</a></div>
					<div class="pm_link_text"><a href="/sub-agent/activate/boom_purple" style="color: #6600ff">Boom Purple Activation</a></div>
					<div class="pm_link_text"><a href="/sub-agent/rtr/boom" style="color: black">Boom Refill</a></div>
				</div>
			</div>

			<div class="pm_container">
				<img src="img/category-img-airvoice.jpg" class="pm_image">
				<div class="pm_overlayer">
					<div class="pm_link_text"><a href="/sub-agent/activate/air_voice">Activation</a></div>
					<div class="pm_link_text"><a onclick="pin('Air Voice')" style="cursor: pointer;">Recharge</a></div>
				</div>
			</div>

			<div class="pm_container">
				<img src="img/category-img-xfinity.png" class="pm_image">
				<div class="pm_overlayer">
					<div class="pm_link_text"><a href="/sub-agent/activate/xfinity">Activation</a></div>
					<div class="pm_link_text"><a onclick="recharge('XFINITY')" style="cursor: pointer;">Recharge</a></div>
				</div>
			</div>

		</div>
	</div>
</div>

<div class="clearfix"></div>

<div class="contain-wrapp padding-bot60">
	<div class="container">
		<div class="row">
			<div class="col-md-8 col-md-offset-2">
				<div class="title-head centered">
					<h2>REFILL - RTR</h2>

					<a onclick="pin('ROKiT')">
						<img src="/img/ROKiT_Telemedicine.png" style="width: 40%">
					</a>

				</div>
			</div>
		</div>

		<div style="width: 100%; text-align: center; overflow: hidden;">
{{--			<div class="pm_container">--}}
{{--				<img src="/img/category-img-boost.jpg" class="pm_image">--}}
{{--				<div class="pm_overlayer">--}}
{{--					<div class="pm_link_text"><a onclick="recharge('Boost Mobile')" style="cursor: pointer;">Recharge</a></div>--}}
{{--					<div class="pm_link_text"><a onclick="transactions('Boost Mobile')" style="cursor: pointer;">Transactions</a></div>--}}
{{--				</div>--}}
{{--			</div>--}}

			<div class="pm_container">
				<img src="/img/category-img-cricket.jpg" class="pm_image">
				<div class="pm_overlayer">
					<div class="pm_link_text"><a onclick="recharge('Cricket')" style="cursor: pointer;">Recharge</a></div>
					<div class="pm_link_text"><a onclick="transactions('Cricket')" style="cursor: pointer;">Transactions</a></div>
				</div>
			</div>

			<div class="pm_container">
				<img src="/img/category-img-metro.jpg" class="pm_image">
				<div class="pm_overlayer">
					<div class="pm_link_text"><a onclick="recharge('MetroPcs')" style="cursor: pointer;">Recharge</a></div>
					<div class="pm_link_text"><a onclick="transactions('MetroPcs')" style="cursor: pointer;">Transactions</a></div>
				</div>
			</div>

			<div class="pm_container">
				<img src="/img/category-img-verizon.jpg" class="pm_image">
				<div class="pm_overlayer">
					<div class="pm_link_text"><a onclick="recharge('Verizon')" style="cursor: pointer;">Recharge</a></div>
					<div class="pm_link_text"><a onclick="transactions('Verizon')" style="cursor: pointer;">Transactions</a></div>
				</div>
			</div>

			<div class="pm_container">
				<img src="/img/category-img-claro.jpg" class="pm_image">
				<div class="pm_overlayer">
					<div class="pm_link_text"><a onclick="recharge('Claro')" style="cursor: pointer;">Recharge</a></div>
					<div class="pm_link_text"><a onclick="transactions('Claro')" style="cursor: pointer;">Transactions</a></div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Start contain wrapp -->
<div class="clearfix"></div>

<div class="contain-wrapp padding-bot60">
	<div class="container">
		<div class="row">
			<div class="col-md-8 col-md-offset-2">
				<div class="title-head centered">
					<h2>REFILL - International Pinless ILD</h2>
				</div>
			</div>
		</div>

		<div style="width: 100%; text-align: center; overflow: hidden;">
			<div class="pm_container">
				<a href="/sub-agent/rtr/dpp">
					<img src="/img/category-img-dollarphone.jpg" class="pm_image" style="height: 200px">
				</a>
			</div>
			<div class="pm_container">
				<a href="/sub-agent/rtr/boss">
					<img src="/img/category-img-boss.jpg" class="pm_image" style="height: 200px">
				</a>
			</div>
		</div>
	</div>
</div>

<div class="clearfix"></div>


<!-- Start contain wrapp -->
<div id="download" class="contain-wrapp padding-bot60">
	<div class="container">

		<div class="row">
			<div class="col-md-8 col-md-offset-2">
				<div class="title-head centered">
					<p>&ensp;</p>
					<h2>SoftpayPlus Offers</h2>
				</div>
			</div>
		</div>
		<div class="col-md-12 owl-column-wrapp">

				<!-- Start Gallery 01 -->
				<div class="grid-item col-md-3 col-sm-6 col-xs-12">
					<div class="img-wrapper">
						<a href="#" target="new"><img src="img/instant.jpg" class="img-responsive" alt="" /></a>
					</div>
				</div>
				<!-- End Gallery 01 -->

				<!-- Star Gallery 02 -->
				<div class="grid-item col-md-3 col-sm-6 col-xs-12">
					<div class="img-wrapper">
						<a href="#" target="new"><img src="img/activation.jpg" class="img-responsive" alt="" /></a>
					</div>
				</div>
				<!-- End Gallery 02 -->

				<!-- Start Gallery 03 -->
				<div class="grid-item col-md-3 col-sm-6 col-xs-12">
					<div class="img-wrapper">
						<a href="#" target="new"><img src="img/refill.jpg" class="img-responsive" alt="" /></a>
					</div>
				</div>
				<!-- End Gallery 03 -->

				<!-- Start Gallery 03 -->
				<div class="grid-item col-md-3 col-sm-6 col-xs-12">
					<div class="img-wrapper">
						<a href="#" target="new"><img src="img/report.jpg" class="img-responsive" alt="" /></a>
					</div>
				</div>
				<!-- End Gallery 03 -->


		</div>
	</div>
</div>
<!-- End contain wrapp -->


@if ($show_dealer_agreement_popup == 'Y')
    <div class="modal fade" tabindex="-1" role="dialog" id="sub-dealer-agreement-popup">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Warning</h4>
                </div>
                <div class="modal-body">
                    <p>Please sign verizon sub dealer agreement...&hellip;</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="show_sub_dealer_agreemnt()">View & Sign</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
    <script type="text/javascript">
        var onload_events = window.onload;
        window.onload = function() {
            if (onload_events) {
                onload_events();
            }

            $('#sub-dealer-agreement-popup').modal();
        }
    </script>
@endif
@stop