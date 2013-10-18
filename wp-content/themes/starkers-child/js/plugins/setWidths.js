(function( $ ){

	$.fn.setWidths = function( options ) {
		
		var children = this.children(),
		total = children.length,
		settings = $.extend({
		  'paginate' : 0
		}, options);
		
		var page = new Array();
		
		for (var i=0;i<children.length; i=i+settings.paginate) {
			page.push(children.slice(i,settings.paginate+i));		
		}
				
		if(total > 0 ) {
			if(settings.paginate > 0) {
				for(var i=0; i<page.length; i++) {
				
					page[i].each(function () {
						$(this).css('width', (1/settings.paginate)*100+'%');
						$(this).attr("data-page", i);
					});
				}
			} else { 
				children.each(function(){
					$(this).css('width', (1/total)*100+'%');		
				});
			}
		}
		
		var showPage = function(target) {	
			if(target == undefined) {
				target = 0;
			}
				
			children.each(function(){
				var show = $(this).attr("data-page");
				if(target == show) {
					$(this).fadeIn(1000);
					$('#portfolio-pagination-controls ul li a').each(function(){
						if($(this).attr('data-page') == show) {
							$(this).parent().addClass('current');
						} else {
							$(this).parent().removeClass('current');
						}
					});
						
				} else {
					$(this).hide();
				}	
			});
		}
		
		
		
		$('#portfolio-pagination-controls').append("<ul>");
		
		for(var i = 1; i<=page.length; i++) {
			$('#portfolio-pagination-controls ul').append($("<li>").append($("<a>").html(i).attr('href', "#").attr('data-page', i-1).on("click", function(){
				var target = $(this).attr("data-page");
				showPage(target);
				return false;
			})));
		}
		
		showPage();	
	};
})( jQuery );