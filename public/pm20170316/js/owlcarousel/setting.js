$(document).ready(function() {
	$("#testimonial").owlCarousel({
		autoPlay: 5000,
		slideSpeed : 1000,
		singleItem : true,
		navigation : true,
		pagination : false,
	});
	$("#client").owlCarousel({
		autoPlay: 5000,
		slideSpeed : 1000,
		navigation : true,
		pagination : false,
		items : 6,
		itemsDesktop : [1199,5],
        itemsDesktopSmall : [979,5],
		itemsTablet : [920,4],
		itemsTabletSmall : [767,3],
	});
	$("#single-item").owlCarousel({
		autoPlay: 15000,
		slideSpeed : 1000,
		singleItem : true,
		navigation : true,
		pagination : false,
	});
	$("#menu-item").owlCarousel({
		autoPlay: 18000,
		slideSpeed : 1000,
		singleItem : true,
		navigation : true,
		pagination : false,
	});
	$("#recent-4column").owlCarousel({
		autoPlay: 8000,
		slideSpeed : 1000,
		navigation : true,
		pagination : false,
		items : 4,
		itemsDesktop : [1199,4],
        itemsDesktopSmall : [979,3],
		itemsTablet : [920,3],
		itemsTabletSmall : [767,2],
	});
	$("#recent-3column").owlCarousel({
		autoPlay: 8000,
		slideSpeed : 1000,
		navigation : true,
		pagination : false,
		items : 3,
		itemsDesktop : [1199,3],
        itemsDesktopSmall : [979,2],
		itemsTablet : [920,2],
		itemsTabletSmall : [767,1],
	});
	$("#recent-2column").owlCarousel({
		autoPlay: 8000,
		slideSpeed : 1000,
		navigation : true,
		pagination : false,
		items : 2,
		itemsDesktop : [1199,2],
        itemsDesktopSmall : [979,1],
		itemsTablet : [920,1],
		itemsTabletSmall : [767,1],
	});
});

