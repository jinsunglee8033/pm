					var tpj=jQuery;							
					var revapi8;
					tpj(document).ready(function() {
						if(tpj("#rev_slider_8_1").revolution == undefined){
							revslider_showDoubleJqueryError("#rev_slider_8_1");
						}else{
							revapi8 = tpj("#rev_slider_8_1").show().revolution({
								sliderType:"standard",
								sliderLayout:"auto",
								dottedOverlay:"none",
								delay:9000,
								navigation: {
									keyboardNavigation:"on",
									keyboard_direction: "horizontal",
									mouseScrollNavigation:"off",
									onHoverStop:"off",
									touch:{
										touchenabled:"on",
										swipe_threshold: 75,
										swipe_min_touches: 1,
										swipe_direction: "horizontal",
										drag_block_vertical: false
									}
									,
									arrows: {
										style:"hesperiden",
										enable:true,
										hide_onmobile:true,
										hide_under:778,
										hide_onleave:true,
										hide_delay:200,
										hide_delay_mobile:200,
										tmp:'',
										left: {
											h_align:"left",
											v_align:"center",
											h_offset:20,
											v_offset:0
										},
										right: {
											h_align:"right",
											v_align:"center",
											h_offset:20,
											v_offset:0
										}
									}
									,
									thumbnails: {
										style:"hesperiden",
										enable:true,
										width:100,
										height:75,
										min_width:60,
										wrapper_padding:5,
										wrapper_color:"#222222",
										wrapper_opacity:"1",
										tmp:'<span class="tp-thumb-img-wrap">  <span class="tp-thumb-image"></span></span>',
										visibleAmount:5,
										hide_onmobile:false,
										hide_over:777,
										hide_onleave:false,
										direction:"vertical",
										span:true,
										position:"outer-left",
										space:5,
										h_align:"left",
										v_align:"top",
										h_offset:0,
										v_offset:0
									}
									,
									tabs: {
										style:"hesperiden",
										enable:true,
										width:370,
										height:80,
										min_width:350,
										wrapper_padding:0,
										wrapper_color:"#fff",
										wrapper_opacity:"1",
										tmp:'<div class="tp-tab-content">  <span class="tp-tab-date">{{param1}}</span>  <span class="tp-tab-title">{{title}}</span></div><div class="tp-tab-image"></div>',
										visibleAmount: 10,
										hide_onmobile: true,
										hide_under:778,
										hide_onleave:false,
										hide_delay:200,
										direction:"vertical",
										span:true,
										position:"outer-left",
										space:0,
										h_align:"left",
										v_align:"top",
										h_offset:0,
										v_offset:0
									}
								},
								gridwidth:1920,
								gridheight:1390,
								lazyType:"single",
								shadow:0,
								spinner:"off",
								shuffle:"off",
								autoHeight:"off",
								disableProgressBar:"on",
								hideThumbsOnMobile:"off",
								hideSliderAtLimit:0,
								hideCaptionAtLimit:0,
								hideAllCaptionAtLilmit:0,
								debugMode:false,
							});
						}
					});	/*ready*/