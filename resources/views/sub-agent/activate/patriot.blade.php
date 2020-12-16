@extends('sub-agent.layout.default')

@section('content')

    <script type="text/javascript">

        window.onload = function () {
            $('.note-check-box').tooltip();

            @if (session()->has('success') && session('success') == 'Y')
                $('#success').modal();
            @endif

            @if ($errors->has('exception'))
                $('#error').modal();
            @endif
        };

        function portin_checked() {
            var checked = $('[name=port_in]').is(':checked');
            if (checked) {
                $('.port-in').show();
                $('#div_npa').hide();
                $('[name=npa]').val('');
            } else {
                $('.port-in').hide();
                $('#div_npa').show();
                $('[name=number_to_port]').val('');
                $('[name=current_carrier]').val('');
                $('[name=account_no]').val('');
                $('[name=account_pin]').val('');
            }
        }

        function product_selected() {
            myApp.showLoading();
            $('#frm_act').prop('action', '/sub-agent/activate/lyca');
            $('#frm_act').submit();
        }

        function request_activation() {
            myApp.showLoading();
            $('#frm_act').prop('action', '/sub-agent/activate/lyca/post');
            $('#frm_act').submit();
        }
    </script>

    @if (session()->has('success') && session('success') == 'Y')
        <div id="success" class="modal fade " tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false"
             style="display:block;">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Activate / Port-In Success</h4>
                    </div>
                    <div class="modal-body">
                        <p>
                            Your request is being processed.<br/>
                            And email or SMS will be delivered shortly after for new activation phone number.<br/>
                            Or please refer to "Reports -> Activation / Port-In" for more information.
                        </p>
                    </div>
                    <div class="modal-footer">
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
                        <h4 class="modal-title" style="color:red">Activate / Port-In Error</h4>
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
	<div id="home" class="parallax paddingbot-clear" data-background="/img/parallax/contract-buyout.jpg" data-speed="0.5" data-size="5%">
		<div class="overlay"></div>
		<div class="container">
			<div class="content-parallax">
				<div class="row">
					<div class="col-md-12">
					  <div class="row">
						<div class="title-head centered">

										<p align="center">FIGHT FOR CONSERVATIVE VALUES WITH YOUR PHONE BILL!</p>
										<h2 align="center">UNLIMITED TALK / TEXT / DATA*</h2>
										<h7 class="head-title">Patriot Mobile Pricing & Plans</h7>
						  </div>
										<p align="center"> <em class="fa fa-check-circle" aria-hidden="true"></em> We’ll Buy Out Your Contract+ &nbsp;	&nbsp;	<em class="fa fa-check-circle" aria-hidden="true"></em> Keep Your Number and Phone<br>
										  <em class="fa fa-check-circle" aria-hidden="true"></em> Excellent Nationwide Coverage &nbsp;	&nbsp;	&nbsp;	<em class="fa fa-check-circle" aria-hidden="true"></em> No Overages or Roaming!<br> </p><br>
						</div>
						  

					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- End parallax -->
	
    
    
    

    <!-- Start contain wrapp -->
	<div class="contain-wrapp gray-container padding-bot70">	
		<div class="container">
			
			<div class="row">
				<div align="center">
	 				 <h5 class="head-title">Choose how much <span>high-speed data you want...</span></h5>

				</div>		
			</div>
			
			
			<div class="row">
				<div class="col-md-12 owl-column-wrapp">
					<div id="recent-3column" class="owl-carousel leftControls-right lg-nav">
						
						
						
						
						<!-- Start team 1 -->
						<div class="item">
							<div class="pricing-item active">
							<p class="pricing-sentence">Conservative</p>

							<h1><SPAN class="pricing-title">500<FONT size="-1">MB</FONT></SPAN></h1>
							
							<div class="pricing-price">
								<span class="pricing-anim pricing-anim-1">
									<span class="pricing-currency">$</span>40
								</span>
								<span class="pricing-anim pricing-anim-2">
									<span class="pricing-period">/MO.</span>
								</span>
							</div>
							<ul class="pricing-feature-list">
								<li class="pricing-feature">Unlimited<br>Talk/Text/Data*</li>
							</ul>
						</div>
						</div>
						<!-- End team 1 -->
						
						<!-- Start team 2 -->
						<div class="item">
							<div class="pricing-item active">
							<p class="pricing-sentence">Life</p>

							<h1><SPAN class="pricing-title">1<FONT size="-1">GB</FONT></SPAN></h1>
							
							<div class="pricing-price">
								<span class="pricing-anim pricing-anim-1">
									<span class="pricing-currency">$</span>50
								</span>
								<span class="pricing-anim pricing-anim-2">
									<span class="pricing-period">/MO.</span>
								</span>
							</div>
							<ul class="pricing-feature-list">
								<li class="pricing-feature">Unlimited<br>Talk/Text/Data*</li>
							</ul>
						</div>
						</div>
						<!-- End team 2 -->
						
						<!-- Start team 3 -->
						<div class="item">
							<div class="pricing-item active">
							<p class="pricing-sentence">Liberty</p>

							<h1><SPAN class="pricing-title">3<FONT size="-1">GB</FONT></SPAN></h1>
							
							<div class="pricing-price">
								<span class="pricing-anim pricing-anim-1">
									<span class="pricing-currency">$</span>65
								</span>
								<span class="pricing-anim pricing-anim-2">
									<span class="pricing-period">/MO.</span>
								</span>
							</div>
							<ul class="pricing-feature-list">
								<li class="pricing-feature">Unlimited<br>Talk/Text/Data*</li>
							</ul>
						</div>
						</div>
						<!-- End team 3 -->
						
						<!-- Start team 4 -->
						<div class="item">
							<div class="pricing-item active">
							<p class="pricing-sentence">Happiness</p>

							<h1><SPAN class="pricing-title">5<FONT size="-1">GB</FONT></SPAN></h1>
							
							<div class="pricing-price">
								<span class="pricing-anim pricing-anim-1">
									<span class="pricing-currency">$</span>80
								</span>
								<span class="pricing-anim pricing-anim-2">
									<span class="pricing-period">/MO.</span>
								</span>
							</div>
							<ul class="pricing-feature-list">
								<li class="pricing-feature">Unlimited<br>Talk/Text/Data*</li>
							</ul>
						</div>
						</div>
						<!-- End team 4 -->
						
						<!-- Start team 5 -->
						<div class="item">
							<div class="pricing-item active">
							<p class="pricing-sentence">Patriot</p>

							<h1><SPAN class="pricing-title">10<FONT size="-1">GB</FONT></SPAN></h1>
							
							<div class="pricing-price">
								<span class="pricing-anim pricing-anim-1">
									<span class="pricing-currency">$</span>100
								</span>
								<span class="pricing-anim pricing-anim-2">
									<span class="pricing-period">/MO.</span>
								</span>
							</div>
							<ul class="pricing-feature-list">
								<li class="pricing-feature">Unlimited<br>Talk/Text/Data*</li>
							</ul>
						</div>
						</div>
						<!-- End team 5 -->
						
						<!-- Start team 6 -->
						<div class="item">
							<div class="pricing-item active">
							<p class="pricing-sentence">Freedom</p>

							<h1><SPAN class="pricing-title">N/A<FONT size="-1"></FONT></SPAN></h1>
							
							<div class="pricing-price">
								<span class="pricing-anim pricing-anim-1">
									<span class="pricing-currency">$</span>25
								</span>
								<span class="pricing-anim pricing-anim-2">
									<span class="pricing-period">/MO.</span>
								</span>
							</div>
							<ul class="pricing-feature-list">
								<li class="pricing-feature">Unlimited<br>Talk/Text</li>
							</ul>
						</div>
						</div>
						<!-- End team 6 -->
						
						
						
						
					</div>
				</div>
			</div>
			
			
			
				<div class="row">
	<div align="center">
				<p style="line-height: 13px"><FONT size="-1">
		
					*With the $45 unlimited talk/text/data plan. *Data is unlimited, but at slower speeds once user reaches plan limit (e.g. the data<br> on a 1GB plan will be at reduced speeds after 1GB is used). +Contract buyout up to $1,500 or $500 per line.</FONT></p>
		</div>		
		</div>
		
			
			
		</div>
	</div>
    <!-- End contain wrapp -->
	
	
	

	
	
	
	
	
	<div class="contain-wrapp padding-bot70">	
		<div class="container">
			<div class="row">
				<div class="col-md-8 col-md-offset-2">
					<div class="title-head centered">
						<h5>Every Patriot Mobile Plan includes...</h5>
					</div>
				</div>
			</div>
			
			
			<div class="row">
				<div class="col-md-4">
					<div class="column-box centered">
						<em class="icon"><img src="/img/icon1.jpg" width="62" height="62"></em>
						<h5 style="margin-top: 10px">A Monthly Conservative Donation In Your Name</h5>
						<p>
						Up to 5% of monthly proceeds go to the conservative organization of your choice – at no extra cost to you – empowering you to advance conservative values.
						</p>
					</div>
				</div>
				
				<div class="col-md-4">
					<div class="column-box centered">
						<em class="icon"><img src="/img/icon2.jpg" width="62" height="62"></em>
						<br>
						<h5 style="margin-top: 10px">Top-Tier <br>Nationwide Coverage</h5>
						<p>
						Get excellent coverage on the top mobile networks across the country. In our coverage areas, you won’t have to worry about dropped calls or no reception.
						</p>
					</div>
				</div>
				
				<div class="col-md-4">
					<div class="column-box centered">
						<em class="icon"><img src="/img/icon3.jpg" width="62" height="62"></em>
						<h5 style="margin-top: 10px">No Hidden Fees!<br>No Roaming Charges!</h5>
						<p>
						There are no roaming charges or hidden fees. We believe in transparency so what you see is what you get. Taxes may apply.
						</p>
					</div>
				</div>
								

			</div>
			
				<div class="row">
				
				
				<div class="col-md-4">
					<div class="column-box centered">
						<em class="icon"><img src="/img/icon4.jpg" width="62" height="62"></em>
						<h5 style="margin-top: 10px">Flexible Plans <br>That Fit Your Needs</h5>
						<p>
						No contract required. Receive a $15 monthly discount on each additional line. Each plan is designed to give you the data flexibility you need.
						</p>
					</div>
				</div>
				
				<div class="col-md-4">
					<div class="column-box centered">
						<em class="icon"><img src="/img/icon5.jpg" width="62" height="62"></em>
						<h5 style="margin-top: 10px">Excellent Customer<br> Service and Care</h5>
						<p>
						Our professional customer support team will solve any issues you may face. We want to make sure you have the best possible experience with Patriot Mobile.
						</p>
					</div>
				</div>
				
				<div class="col-md-4">
					<div class="column-box centered">
						<em class="icon"><img src="/img/icon6.jpg" width="62" height="62"></em>
						<h5 style="margin-top: 10px">14-Day <br>Satisfaction Guarantee</h5>
						<p>
						If you’re not fully satisfied for any reason within 14 days, we’ll give you a full refund. We strive to make sure you’re pleased with our coverage and service.
						</p>
					</div>
				</div>
								

			</div>
			
		</div>
	</div>
  
  

  
    
      
        
          
         








@stop
