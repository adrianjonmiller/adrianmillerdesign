/* Author: 
    Adrian J. Miller
*/


$(document).ready(function(){

	$(document).on('mouseover', '.portfolio-thumbnail', function(){
		$this = $(this).find('.thumbnail-details');
		$this.css('opacity', '1');
	});
	
	$(document).on('mouseout', '.portfolio-thumbnail', function(){
		$this = $(this).find('.thumbnail-details');
		$this.css('opacity', '0');
	});
	
	$('nav a').each(function(){
		if($(this).attr('title') != 'logo-anchor') {
			$(this).append($("<div>").attr('class', 'underline'));
		}
	});
	
	var $window = $(window);
	
	function roundBadge() {
	
		$('#Process h4').each(function(){
			$this = $(this);		
			$width = $this.outerWidth();
			$radius = $width/2;
			
			
			$this.attr('style', 'height: '+$width+'px; border-radius:'+$radius+'px; -mox-border-radius: '+$radius+'px;-webkit-border-radius: '+$radius+';');
		});
		
	}roundBadge();
	
//	function pageSize() {
//		$('.page').each(function() {
//			$this = $(this);
//			
//			$width = $(window).width();
//			$height = $(window).height();
//			
//			$this.attr('style', 'min-height:'+$height+'px;');
//		});
//	}pageSize();	 
	 
	 
	 function Parallax() {
	 	$('.page').each(function(){
	 		$this = $(this);
			var pos =  $window.scrollTop() - $this.offset().top;
				$this.css({'backgroundPosition': '50% ' + pos*0.5 + 'px'});
	 	});
	 }
	 
	 $window.resize(function(){ 
	 	Parallax(); 
	 });		
	 
	 $window.on('scroll', function(){ 
	 	Parallax(); 
	 });
	 
	 $('#spinner').hide();
	 $('#loader').hide();
	 
//	 $( ".select-menu" ).change(function() { 
//	 	$offset =  $($(this).find("option:selected").val()).offset();
//	 	
//	 	$('html, body').animate({
//	 	    scrollTop: $offset.top
//	 	}, 1000);
//	 	return false;
//	 	$(this).parent().addClass('current');
//	 	
//	 });
	 
	 $(document).on('click', '.obj_loader', function(e){
	 	e.preventDefault();
	 	
	 	$this = $(this);
	 	$url = $this.attr('href');
		$('#spinner').show();
		$('#loader').show();	 	
	 	
	 	$.ajax({
	 	  url: $url
	 	}).done(function ( data ) {
	 	 	$('#loader #load-target').append(data);
	 	 	$('#spinner').hide();
	 	});
	 });
	 
	 $(document).on('click', '#close', function(){
	 	$('#loader #load-target').empty();
	 	$('#loader').hide();
	 });
	 
	 
	 $(window).resize(function(){
	 	roundBadge();
	 	pageSize();
	 });
     
     
//     $(document).ajaxStart(function() {
//             $('#spinner').show();
//             $('#loader').show();
//         })
//         .ajaxStop(function() {
//             $("#spinner").hide();
//         });
     
     $('.page').scrollTo({'vertical_adjust': 300});
  
	
}); 