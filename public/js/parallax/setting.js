(function($) {
  'use strict';    
    $(".parallax").each(function(){
        var getBg = $(this).data("background"),
            getSpeed = $(this).data("speed"),
            getPosition = $(this).data("size");
        
        $(this).css("background-image","url(" + getBg +")");
        $(this).parallax(getPosition, getSpeed);
    });
})(jQuery);