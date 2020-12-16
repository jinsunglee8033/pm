			var tpj=jQuery;
			
			var revapi490;
			tpj(document).ready(function() {
				if(tpj("#rev-slider").revolution == undefined){
					revslider_showDoubleJqueryError("#rev-slider");
				}else{
					revapi490 = tpj("#rev-slider").show().revolution({
						sliderType:"hero",
						sliderLayout:"fullwidth",
						dottedOverlay:"none",
						delay:9000,
						navigation: {
						},
						responsiveLevels:[1920,1920,1920,1920],
						visibilityLevels:[1275,1275,1275,1275],
						gridwidth:[1920,1920,1920,1920],
						gridheight:[1275,1275,1275,1275],
						lazyType:"none",
						parallax: {
							type:"mouse",
							origo:"slidercenter",
							speed:2000,
							levels:[2,3,4,5,6,7,12,16,10,50,46,47,48,49,50,55],
							type:"mouse",
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