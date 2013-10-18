(function( $ ){

	$.fn.pageSize = function( options ) {
		
			this.each(function() {
				$this = $(this);
				$width = $(window).width();
				$height = $(window).height();
				
				$this.attr('style', 'min-height:'+$height+'px;');
			});
			
			return this;
			
		};
})( jQuery );