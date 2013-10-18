(function( $ ){

  $.fn.scrollTo = function( options ) {
  	
  	var contentId = [],
  		$window = $(window);
  		
  	var settings = $.extend( {
  	      'vertical_adjust': 0
  	    }, options);
  	  	    
  	var adjust = options.vertical_adjust;
  	  	
  	//		  Stop animated scroll if the user does something
	 $('html,body').bind('scroll mousedown DOMMouseScroll mousewheel keyup', function(e){
	  if ( e.which > 0 || e.type == 'mousedown' || e.type == 'mousewheel' ){
	   $('html,body').stop();
	  }
	 });
  			 
  	//		  Set up content an array of locations
	this.each(function(){
	  contentId.push($(this).attr('id'));
	 });
	 
	 
	 $('nav a').click(function(){
	 	var offset =  $($(this).attr('href')).offset();
	 	
	     $('html, body').animate({
	         scrollTop: offset.top
	     }, 1000);
	     return false;
	     $(this).parent().addClass('current');
	 });
	
	
	
	$(window).scroll(function(){
		setCurrent();
    });
    
    setCurrent();
    
    
    
    function setCurrent() {
    	var winTop = $window.scrollTop();
    	
	    jQuery.each(contentId, function(i, id){
	    	var loc = $("#"+id).offset();
	    	if(winTop >= loc.top - adjust && winTop < bottomOfPage(i)) {
	    		$('nav li').removeClass('current');
	    		$('[href=#'+contentId[i]+']').parent().addClass('current');
	    	}
	    });
	    
	    function bottomOfPage(i) {
	    	if(i == contentId.length - 1) {
	    		return $(document).height();
	    	} else {
	    		return $("#"+contentId[i+1]).offset().top; 
	    	}
	    }
    }

  };
})( jQuery );