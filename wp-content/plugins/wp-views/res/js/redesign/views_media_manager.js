/**
 * Thanks to Thomas Griffin for his super useful example on Github
 *
 * https://github.com/thomasgriffin/New-Media-Image-Uploader
 */
jQuery(document).ready(function($){
	
	// Prepare the variable that holds our custom media manager.
	var wpv_media_frame;
	// var wp_media_post_id = wp.media.model.settings.post.id; // Store the old id
	var set_to_post_id = $('.js-post_ID').val(); // Set this
	
	// Bind to our click event in order to open up the new media experience.
	$(document.body).on('click', '.js-wpv-media-manager', function(e){ //mojo-open-media is the class of our form button
	// Prevent the default action from occuring.
	e.preventDefault();
	
	var referred_id = $(this).attr('data-id');
	if (typeof referred_id !== 'undefined' && referred_id !== false) {
		set_to_post_id = referred_id;
	}

	var active_textarea = $(this).data('content');
	window.wpcfActiveEditor = active_textarea;
			    // If the frame already exists, re-open it.
	if ( wpv_media_frame ) {
		wpv_media_frame.uploader.uploader.param( 'post_id', set_to_post_id );
		wpv_media_frame.open();
		return;
	} else {
		// Set the wp.media post id so the uploader grabs the ID we want when initialised
		wp.media.model.settings.post.id = set_to_post_id;
	}
	wpv_media_frame = wp.media.frames.wpv_media_frame = wp.media({
		
		//Create our media frame
		className: 'media-frame mojo-media-frame',
		frame: 'post',
		multiple: false, //Disallow Mulitple selections
		library: {
			type: 'image' //Only allow images
		},
	});
	
	
	wpv_media_frame.on('open', function(event){
		if( !$(".selected").is('li') )
		{
			$('.media-button-insert').addClass('button-secondary').removeClass('button-primary');
		}	
		
		$('.media-button-insert').live("attributeChanged", function(event, args, val ){
			
			if( args == 'disabled' && val == true )
			{
				$(event.target).addClass('button-secondary').removeClass('button-primary');
			}
			else if( args == 'disabled' && val == false )
			{
				$(event.target).removeClass('button-secondary').addClass('button-primary');
			}
		});
	}); 
	
	wpv_media_frame.on('insert', function(){
		var media_attachment = wpv_media_frame.state().get('selection').first().toJSON();
		var size = $('.attachment-display-settings .size').val();
		var code = media_attachment.sizes[size].url;
		if ( window.wpcfActiveEditor == 'wpv-pagination-spinner-image' ) {
			$('.js-wpv-pagination-spinner-image').val('');
			$('.js-wpv-pagination-spinner-image-preview').attr("src",code);
		}
		icl_editor.insert(code);
		if ( window.wpcfActiveEditor == 'wpv-pagination-spinner-image' ) {
			$('.js-wpv-pagination-spinner-image').trigger('keyup');
		}
	});
	
	var _AttachmentDisplay = wp.media.view.Settings.AttachmentDisplay;
	wp.media.view.Settings.AttachmentDisplay = _AttachmentDisplay.extend({
		render: function() {
			_AttachmentDisplay.prototype.render.apply(this, arguments);
			this.$el.find('select.link-to').parent().remove();
			this.$el.find('select.alignment').parent().remove();
			this.model.set('link', 'none');
			this.updateLinkTo();
		}
	});

// Now that everything has been set, let's open up the frame.
wpv_media_frame.open();
	});
});

jQuery(document).on("DOMNodeInserted", function(){
	// Lock uploads to "Uploaded to this post"
	jQuery('select.attachment-filters [value="uploaded"]').attr( 'selected', true ).parent().trigger('change');
	jQuery('.attachments-browser .media-toolbar-secondary').addClass('hidden');
});