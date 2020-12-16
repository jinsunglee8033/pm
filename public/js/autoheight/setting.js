(function ($) {
	"use strict";

    var core = {
		// ------------------------------------------------------------------------------ //
        // Initialize
        // ------------------------------------------------------------------------------ //
        initialize: function() {
            this.event();
			this.ResetGridColumns();
        },
		
		// ------------------------------------------------------------------------------ //
        // Event
        // ------------------------------------------------------------------------------ //
        event : function(){
            // Same Height
			$(".match-height").matchHeight();
        },
		
		// ------------------------------------------------------------------------------ //
        // Reset Grid
        // ------------------------------------------------------------------------------ //
		ResetGridColumns : function(){
			$('.grid-same').each(function() {            
				// find all columns
				var $cs = $(this).children('[class*="col-"]');

				// reset the height
				$cs.css('height', 'auto');

				// set the heights per row
				var rowWidth = $(this).width();
				var $curCols = $();
				var curMax = 0;
				var curWidth = 0;
				$cs.each(function() {
					var w = $(this).width();
					var h = $(this).height();
					if(curWidth+w <= rowWidth) {
						$curCols = $curCols.add(this);
						curWidth+= w;
						if(h>curMax) curMax = h;
					} else {
						if($curCols.length>1) $curCols.css('height', curMax+'px');
						$curCols = $(this);
						curWidth = w;
						curMax = h;
					}
				});
				if($curCols.length>1) $curCols.css('height', curMax+'px');
			});
		}
		
    };

    // Initaial on Load
    $(window).on("load", function(){
        core.initialize();
    });
	
	// Resize Action
    $(window).on("resize", function(){
		core.ResetGridColumns();
		setTimeout(function(){
			core.ResetGridColumns();
		}, 600);
    });
}(jQuery));
