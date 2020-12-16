	var tpj=jQuery;
			
	var revapi491;
	tpj(document).ready(function() {
		if(tpj("#rev-slider").revolution == undefined){
			revslider_showDoubleJqueryError("#rev-slider");
		}else{
			revapi491 = tpj("#rev-slider").show().revolution({
			sliderType:"hero",
			sliderLayout:"fullwidth",
			dottedOverlay:"none",
			delay:9000,
			navigation: {
			},
			viewPort: {
				enable:true,
				outof:"pause",
				visible_area:"80%",
				presize:false
			},
			  gridwidth:1920,
			  gridheight:1020,
			lazyType:"none",
			parallax: {
				type:"scroll",
				origo:"enterpoint",
				speed:400,
				levels:[5,10,15,20,25,30,35,40,45,50,46,47,48,49,50,55],
				type:"scroll",
			},
			shadow:0,
			spinner:"off",
			autoHeight:"off",
			disableProgressBar:"on",
			hideThumbsOnMobile:"off",
			hideSliderAtLimit:0,
			hideCaptionAtLimit:0,
			hideAllCaptionAtLilmit:0,
			debugMode:false,
			fallbacks: {
				simplifyAll:"off",
				disableFocusListener:false,
			}
			});
		}
	});	/*ready*/