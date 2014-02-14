DLN.Behaviors.pageSize = function(container){
	function pageResize() {
		$this = $(this);
		$height = $(window).height();
		$this.attr('style', 'min-height:'+$height+'px;');
		console.log("child");
	}
	container.children().each(pageResize());
	$(window).on("resize", pageResize());
}


DLN.Behaviors.flexslider = function(container){
	console.log(width());
	function width() {
		return container.width()/4;
	}
	function flexSlider() {
		container.flexslider({
		animation: "slide",
	    animationLoop: true,
	    slideshow: false,
	    itemWidth: width(),
	    minItems: 2,
	    maxItems: 4,
	    prevText: '<i class="ion-ios7-arrow-thin-left"></i>',
			nextText: '<i class="ion-ios7-arrow-thin-right"></i>', 
		});
	}
	flexSlider();
	$(window).on("resize", flexSlider());
}