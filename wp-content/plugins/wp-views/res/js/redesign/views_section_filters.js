// Layout display

jQuery(document).ready(function(){
	wpv_filters_exist();
	wpv_filters_colapse();
});

function wpv_filters_colapse() { // hide edition by default
	jQuery('.js-wpv-filter-edit').hide();
}

function wpv_filters_exist() {
	var empty = jQuery('.js-wpv-filter-add-filter').data('empty');
	var nonempty = jQuery('.js-wpv-filter-add-filter').data('nonempty');
	if (0 == jQuery('.js-filter-list').find('.js-filter-row').length) {
		jQuery('.js-filter-list').hide();
		jQuery('.js-no-filters').show();
		jQuery('.js-wpv-filter-add-filter').val(empty);
	} else {
		jQuery('.js-no-filters').hide();
		jQuery('.js-filter-list').show();
		jQuery('.js-wpv-filter-add-filter').val(nonempty);
	}
}

// Gerenal interaction: open and close filters edition

jQuery(document).on('click', '.js-wpv-filter-edit-open', function(e){ // open filters editor - common for all filters
	e.preventDefault();
	jQuery(this).attr('disabled', true).hide();
	jQuery(this).parents('.js-filter-row').find('.js-wpv-filter-summary').hide();
	jQuery(this).parents('.js-filter-row').find('.js-wpv-filter-edit').fadeIn('fast');
	if ( jQuery(this).parents('.js-filter-row').hasClass('js-filter-row-multiple') ) {
		jQuery(this).parents('.js-filter-row').find('.js-wpv-filter-edit-controls').hide();
	}
});

function wpv_close_filter_row(row) { // general close filters editor - just aesthetic changes & no actions
	jQuery(row).find('.js-wpv-filter-summary').fadeIn('fast');
	jQuery(row).find('.js-wpv-filter-edit').hide();
	jQuery(row).find('.js-wpv-filter-edit-controls');
	jQuery(row).find('.js-wpv-filter-edit-open').attr('disabled', false).show();
	if ( jQuery(row).hasClass('js-filter-row-multiple') ) {
		jQuery(row).find('.js-wpv-filter-edit-controls').show();
	}
//	jQuery('html,body').animate({scrollTop:jQuery('.js-wpv-settings-content-filter').offset().top-25}, 500);
}

// General validation

function wpv_validate_filter_inputs(row) {
	var valid = true;
	jQuery(jQuery(row).find('.js-wpv-filter-validate').get().reverse()).each(function(){
		jQuery(this).removeClass('filter-input-error');
		var type = jQuery(this).data('type');
		var input_valid = wpv_filter_validate_param(type, jQuery(this));
		if (input_valid == false ) {
			jQuery(this).addClass('filter-input-error');
			valid = false;
		}
	});
	return valid;
}

var wpv_param_missing = jQuery('.js-wpv-param-missing').hide();
var wpv_param_url_ilegal = jQuery('.js-wpv-param-url-ilegal').hide();
var wpv_param_shortcode_ilegal = jQuery('.js-wpv-param-shortcode-ilegal').hide();
var wpv_param_forbidden_wp = jQuery('.js-wpv-param-forbidden-wordpress').hide();
var wpv_param_forbidden_ts = jQuery('.js-wpv-param-forbidden-toolset').hide();
var wpv_param_forbidden_pt = jQuery('.js-wpv-param-forbidden-post-type').hide();
var wpv_param_forbidden_tax = jQuery('.js-wpv-param-forbidden-taxonomy').hide();
var wpv_filter_parent_type_not_hierarchical = jQuery('.js-wpv-filter-parent-type-not-hierarchical').hide();
var wpv_filter_taxonomy_parent_changed = jQuery('.js-wpv-filter-taxonomy-parent-changed').hide();
var wpv_filter_taxonomy_term_changed = jQuery('.js-wpv-filter-taxonomy-term-changed').hide();
var wpv_url_pattern = /^[a-z0-9\-\_]+$/;
var wpv_shortcode_pattern = /^[a-z0-9]+$/;

function wpv_filter_validate_param(type, selector, value) {
	var input_valid = true,
		value = selector.val(),
		save_button = selector.parents('.js-filter-row').find('.js-wpv-filter-edit-ok');
	if (type == 'url') {
		wpv_param_missing.remove();
		wpv_param_url_ilegal.remove();
		wpv_param_forbidden_wp.remove();
		wpv_param_forbidden_ts.remove();
		wpv_param_forbidden_pt.remove();
		wpv_param_forbidden_tax.remove();
		if (selector.val() == '') {
			wpv_param_missing.clone().insertAfter(save_button).show();
			input_valid = false;
		} else if (wpv_url_pattern.test(value) == false) {
			wpv_param_url_ilegal.clone().insertAfter(save_button).show();
			input_valid = false;
		} else if (jQuery.inArray(value, wpv_forbidden_parameters.wordpress) > -1) {
			wpv_param_forbidden_wp.clone().insertAfter(save_button).show();
			input_valid = false;
		} else if (jQuery.inArray(value, wpv_forbidden_parameters.toolset) > -1) {
			wpv_param_forbidden_ts.clone().insertAfter(save_button).show();
			input_valid = false;
		} else if (jQuery.inArray(value, wpv_forbidden_parameters.post_type) > -1) {
			wpv_param_forbidden_pt.clone().insertAfter(save_button).show();
			input_valid = false;
		} else if (jQuery.inArray(value, wpv_forbidden_parameters.taxonomy) > -1) {
			wpv_param_forbidden_tax.clone().insertAfter(save_button).show();
			input_valid = false;
		}
	}
	if (type == 'shortcode') {
		wpv_param_missing.remove();
		wpv_param_shortcode_ilegal.remove();
		if (selector.val() == '') {
			wpv_param_missing.clone().insertAfter(save_button).show();
			input_valid = false;
		} else if (wpv_shortcode_pattern.test(value) == false) {
			wpv_param_shortcode_ilegal.clone().insertAfter(save_button).show();
			input_valid = false;
		}
	}
	if (selector.parents('.js-filter-row').find('.js-filter-error').length < 1) {
		save_button.prop('disabled', false);
	} else {
		save_button.prop('disabled', true);
	}
	return input_valid;
}

function wpv_clear_validate_messages(row){
	jQuery(row).find('.toolset-alert-error').each(function(){
		jQuery(this).remove();
	});
}

// Add Filter popup

jQuery(document).ready(function(){
	jQuery('.js-filters-insert-filter').removeClass('button-primary').addClass('button-secondary').prop('disabled', true);
});

jQuery(document).on('change', '.js-filter-add-select', function(){
	if (jQuery(this).val() != '-1') {
		jQuery('.js-filters-insert-filter').addClass('button-primary').removeClass('button-secondary').prop('disabled', false);
	} else {
		jQuery('.js-filters-insert-filter').removeClass('button-primary').addClass('button-secondary').prop('disabled', true);
	}
});

jQuery(document).on('click', '.js-wpv-filter-add-filter', function(){
	jQuery(this).attr('disabled', true);
	jQuery('.js-filter-add-select').val('-1');
	jQuery('.js-filters-insert-filter').removeClass('button-primary').addClass('button-secondary').prop('disabled', true);
	var nonce = jQuery(this).data('nonce');
	wpv_update_filters_select(nonce, true);
});

function wpv_update_filters_select(nonce, openpopup){
	var data = {
		action: 'wpv_filters_upate_filters_select',
		id: jQuery('.js-post_ID').val(),
		wpnonce: nonce,
	};
	jQuery.post(ajaxurl, data, function(response) {
		if ( (typeof(response) !== 'undefined') ) {
			jQuery('.js-filter-add-select').replaceWith(response);
			if (openpopup) {
				wpv_open_filters_popup();
			}
		} else {
			console.log( "Error: AJAX returned ", response );
		}

	})
	.fail(function(jqXHR, textStatus, errorThrown) {
		console.log( "Error: ", textStatus, errorThrown );
	})
	.always(function() {
		jQuery('.js-wpv-filter-add-filter').attr('disabled', false);
	});
}

function wpv_open_filters_popup(){
	jQuery.colorbox({
		inline: true,
		 href:'.js-filter-add-filter-form-dialog',
		 open: true,
		onComplete:function()
		{
			var group = jQuery(".js-filter-add-select").find("optgroup");
			jQuery.each(group, function(i,v){			
				if( jQuery(v).children().length === 0 )
				{
					jQuery(this).remove();
				}
			});
		}
	});
};

jQuery(document).on('click', '.js-filters-cancel-filter', function(){
	jQuery('.js-filter-add-select').val('-1');
});

jQuery(document).on('click','.js-filters-insert-filter', function(){
	var filter_type = jQuery('.js-filter-add-select').val();
	var nonce = jQuery(this).data('nonce');
	var data = {
		action: 'wpv_filters_add_filter_row',
		id: jQuery('.js-post_ID').val(),
		    wpnonce: nonce,
		    filter_type: filter_type
	};
	jQuery.post(ajaxurl, data, function(response) {
		if ( (typeof(response) !== 'undefined') ) {
			if (filter_type == 'post_category' || filter_type.substr(0, 9) == 'tax_input') {
				if (jQuery('.js-filter-list .js-filter-taxonomy').length > 0) {
					var filter_type_fixed = filter_type.replace('[', '_').replace(']', '');
					if (jQuery('.js-filter-list .js-filter-taxonomy .js-filter-row-tax-' + filter_type_fixed).length > 0) {
						jQuery('.js-filter-list .js-filter-taxonomy .js-filter-row-tax-' + filter_type_fixed).remove();
					}
					var responseRow = jQuery('.js-filter-list .js-filter-taxonomy-edit').prepend(response);
					jQuery('.js-filter-list .js-filter-taxonomy-edit').show();
					jQuery('.js-filter-list .js-wpv-filter-taxonomy-summary').hide();
				} else {
					var tax_dummy_row = jQuery('.js-filter-placeholder .js-filter-taxonomy').clone();
					jQuery('.js-filter-list').show().append(tax_dummy_row);
					var responseRow = jQuery('.js-filter-list .js-filter-taxonomy-edit').prepend(response);
					jQuery('.js-filter-list .js-filter-taxonomy-edit').show();
					jQuery('.js-filter-list .js-wpv-filter-taxonomy-summary').hide();
				}
				jQuery('.js-filter-list .js-filter-taxonomy').find('.js-wpv-filter-edit-controls').hide();
				var save_text = jQuery('.js-filter-list .js-filter-taxonomy').find('.js-wpv-filter-edit-ok').data('save');
				jQuery('.js-filter-list .js-filter-taxonomy').find('.js-wpv-filter-edit-ok').val(save_text).addClass('button-primary').removeClass('button-secundary').addClass('js-wpv-section-unsaved');
				wpv_taxonomy_mode();
				wpv_taxonomy_relationship();
			} else if (filter_type.substr(0, 12) == 'custom-field') {
				if (jQuery('.js-filter-list .js-filter-custom-field').length > 0) {
					if (jQuery('.js-filter-list .js-filter-custom-field .js-filter-row-' + filter_type).length > 0) {
						jQuery('.js-filter-list .js-filter-custom-field .js-filter-row-' + filter_type).remove();
					}
					var responseRow = jQuery('.js-filter-list .js-filter-custom-field-edit').prepend(response);
					jQuery('.js-filter-list .js-filter-custom-field-edit').show();
					jQuery('.js-filter-list .js-wpv-filter-custom-field-summary').hide();
				} else {
					var tax_dummy_row = jQuery('.js-filter-placeholder .js-filter-custom-field').clone();
					jQuery('.js-filter-list').show().append(tax_dummy_row);
					var responseRow = jQuery('.js-filter-list .js-filter-custom-field-edit').prepend(response);
					jQuery('.js-filter-list .js-filter-custom-field-edit').show();
					jQuery('.js-filter-list .js-wpv-filter-custom-field-summary').hide();
				}
				jQuery('.js-filter-list .js-filter-custom-field').find('.js-wpv-filter-edit-controls').hide();
				var save_text = jQuery('.js-filter-list .js-filter-custom-field').find('.js-wpv-filter-edit-ok').data('save');
				jQuery('.js-filter-list .js-filter-custom-field').find('.js-wpv-filter-edit-ok').val(save_text).addClass('button-primary').removeClass('button-secundary').addClass('js-wpv-section-unsaved');
				wpv_custom_field_initialize_compare();
				wpv_custom_field_initialize_compare_mode();
				wpv_custom_field_initialize_relationship();
				
			} else if (filter_type.substr(0, 14) == 'usermeta-field') {
				
				if (jQuery('.js-filter-list .js-filter-usermeta-field').length > 0) {
					if (jQuery('.js-filter-list .js-filter-usermeta-field .js-filter-row-' + filter_type).length > 0) {
						jQuery('.js-filter-list .js-filter-usermeta-field .js-filter-row-' + filter_type).remove();
					}
					var responseRow = jQuery('.js-filter-list .js-filter-usermeta-field-edit').prepend(response);
					jQuery('.js-filter-list .js-filter-usermeta-field-edit').show();
					jQuery('.js-filter-list .js-wpv-filter-usermeta-field-summary').hide();
				} else {
					var tax_dummy_row = jQuery('.js-filter-placeholder .js-filter-usermeta-field').clone();
					jQuery('.js-filter-list').show().append(tax_dummy_row);
					var responseRow = jQuery('.js-filter-list .js-filter-usermeta-field-edit').prepend(response);
					jQuery('.js-filter-list .js-filter-usermeta-field-edit').show();
					jQuery('.js-filter-list .js-wpv-filter-usermeta-field-summary').hide();
				}
				jQuery('.js-filter-list .js-filter-usermeta-field').find('.js-wpv-filter-edit-controls').hide();
				var save_text = jQuery('.js-filter-list .js-filter-usermeta-field').find('.js-wpv-filter-edit-ok').data('save');
				jQuery('.js-filter-list .js-filter-usermeta-field').find('.js-wpv-filter-edit-ok').val(save_text).addClass('button-primary').removeClass('button-secundary').addClass('js-wpv-section-unsaved');
				wpv_usermeta_field_initialize_compare();
				wpv_usermeta_field_initialize_compare_mode();
				wpv_usermeta_field_initialize_relationship();
				
			}else {
				jQuery('.js-filter-list .js-filter-row-' + filter_type).remove();
				var responseRow = jQuery('.js-filter-list').append(response);
				responseRow.find('.js-filter-row-' + filter_type + ' .js-wpv-filter-edit-open').attr('disabled', true).hide();
				responseRow.find('.js-filter-row-' + filter_type + ' .js-wpv-filter-edit-controls');
				responseRow.find('.js-filter-row-' + filter_type + ' .js-wpv-filter-summary').hide();
				var save_text = responseRow.find('.js-filter-row-' + filter_type + ' .js-wpv-filter-edit-ok').data('save');
				responseRow.find('.js-filter-row-' + filter_type + ' .js-wpv-filter-edit-ok').val(save_text).addClass('button-primary').removeClass('button-secundary').addClass('js-wpv-section-unsaved');
				wpv_users_suggest();
			}
			setConfirmUnload(true);
		//	jQuery('html,body').animate({scrollTop:jQuery('.js-filter-list').offset().top-25}, 500);
		} else {
			console.log( "Error: AJAX returned ", response );
		}

	})
	.fail(function(jqXHR, textStatus, errorThrown) {
		console.log( "Error: ", textStatus, errorThrown );
	})
	.always(function() {
		jQuery.colorbox.close();
		jQuery('.js-filter-add-select').val('-1');
		wpv_taxonomy_relationship();
		wpv_filters_exist();
	});
});

// Remove filter

jQuery(document).on('click', '.js-filter-row-simple .js-filter-remove', function(){
	var data_view_id = jQuery('.js-post_ID').val();
	var row = jQuery(this).parents('li.js-filter-row');
	var filter = row.attr('id').substring(7);
	var nonce = jQuery(this).data('nonce');
	var action = 'wpv_filter_' + filter + '_delete';

	var data = {
		action: action,
		id: data_view_id,
		wpnonce: nonce,
	};
	jQuery.post(ajaxurl, data, function(response) {
		if ( (typeof(response) !== 'undefined') ) {
			jQuery(row).find('.js-wpv-filter-edit-ok').removeClass('js-wpv-section-unsaved');
			if (jQuery('.js-wpv-section-unsaved').length < 1) {
				setConfirmUnload(false);
			}
			jQuery(row).fadeOut(500, function(){
				jQuery(this).remove();
				wpv_filters_exist();
			});
			jQuery('.js-filter-add-select').val('-1');
		} else {
			console.log( "Error: AJAX returned ", response );
		}

	})
	.fail(function(jqXHR, textStatus, errorThrown) {
		console.log( "Error: ", textStatus, errorThrown );
	})
	.always(function() {

	});
});

jQuery(document).on('click', '.js-wpv-filter-taxonomy-controls .js-filter-remove', function() {
	var data_view_id = jQuery('.js-post_ID').val();
	var row = jQuery(this).parents('.js-filter-row-multiple-element');
	var taxonomy = row.data('taxonomy');
	var nonce = jQuery(this).data('nonce');
	var data = {
		action: 'wpv_filter_taxonomy_delete',
		id: data_view_id,
		taxonomy: taxonomy,
		wpnonce: nonce,
	};
	jQuery.post(ajaxurl, data, function(response) {
		if ( (typeof(response) !== 'undefined') && (response === data.id) ) {
			if ( !jQuery('.js-filter-list .js-filter-taxonomy .js-filter-taxonomy-row-remove').hasClass('js-multiple-items') ) {
				jQuery('.js-filter-list .js-filter-taxonomy').find('.js-wpv-filter-edit-ok').removeClass('js-wpv-section-unsaved');
				if (jQuery('.js-wpv-section-unsaved').length < 1) {
					setConfirmUnload(false);
				}
			}
			jQuery(row).fadeOut(500, function(){
				jQuery(this).remove();
				wpv_taxonomy_relationship();
				wpv_filters_exist();
			});
			jQuery('.js-filter-add-select').val('-1');
		} else {
			console.log( "Error: AJAX returned ", response );
		}

	})
	.fail(function(jqXHR, textStatus, errorThrown) {
		console.log( "Error: ", textStatus, errorThrown );
	})
	.always(function() {

	});
});

jQuery(document).on('click', '.js-filter-taxonomy .js-filter-taxonomy-row-remove', function(e) {
	if (jQuery(this).hasClass('js-multiple-items')) {
		jQuery.colorbox({
			inline: true,
			href:'.js-filter-taxonomy-delete-filter-row-dialog',
			open: true
		});
	} else {
		wpv_remove_taxonomy_filters();
	}
});

function wpv_remove_taxonomy_filters() {
	jQuery('.js-filter-list .js-filter-taxonomy').find('.js-wpv-filter-edit-ok').removeClass('js-wpv-section-unsaved');
	if (jQuery('.js-wpv-section-unsaved').length < 1) {
		setConfirmUnload(false);
	}
	jQuery('.js-filter-list .js-filter-taxonomy .js-filter-remove').each(function() {
		var data_view_id = jQuery('.js-post_ID').val(),
		row = jQuery(this).parents('.js-filter-row-multiple-element'),
		taxonomy = row.data('taxonomy'),
		nonce = jQuery(this).data('nonce');
		var data = {
			action: 'wpv_filter_taxonomy_delete',
			id: data_view_id,
			taxonomy: taxonomy,
			wpnonce: nonce,
		};
		jQuery.ajax({
			async:false,
			type:"POST",
			url:ajaxurl,
			data:data,
			success:function(response){
				if ( (typeof(response) !== 'undefined') && (response === data.id) ) {
					jQuery(row).fadeOut(500, function(){
						jQuery(this).remove();
						wpv_taxonomy_relationship();
						wpv_filters_exist();
					});
					jQuery('.js-filter-add-select').val('-1');
				} else {
					console.log( "Error: AJAX returned ", response );
				}
			},
			error: function (ajaxContext) {
				console.log( "Error: ", ajaxContext.responseText );
			},
			complete: function() {
				
			}
		});
	});
}

jQuery(document).on('click', '.js-filter-taxonomy-edit-filter-row', function(e) {
	e.preventDefault();
	jQuery('.js-filter-list .js-filter-taxonomy .js-wpv-filter-edit-open').trigger('click');
	jQuery('.js-filter-taxonomy .js-filter-taxonomy-row-remove').colorbox.close();
})

jQuery(document).on('click', '.js-filters-taxonomy-delete-filter-row', function() {
	spinnerContainer = jQuery('<div class="spinner ajax-loader">').insertBefore(jQuery(this)).show();
	wpv_remove_taxonomy_filters();
	spinnerContainer.remove();
	jQuery('.js-filter-taxonomy .js-filter-taxonomy-row-remove').colorbox.close();
});

jQuery(document).on('click', '.js-wpv-filter-custom-field-controls .js-filter-remove', function() {
	var data_view_id = jQuery('.js-post_ID').val();
	var row = jQuery(this).parents('.js-filter-row-multiple-element');
	var field = row.data('field');
	var nonce = jQuery(this).data('nonce');
	var data = {
		action: 'wpv_filter_custom_field_delete',
		id: data_view_id,
		field: field,
		wpnonce: nonce,
	};
	jQuery.post(ajaxurl, data, function(response) {
		if ( (typeof(response) !== 'undefined') && (response === data.id) ) {
			if ( !jQuery('.js-filter-list .js-filter-custom-field .js-filter-custom-field-row-remove').hasClass('js-multiple-items') ) {
				jQuery('.js-filter-list .js-filter-custom-field').find('.js-wpv-filter-edit-ok').removeClass('js-wpv-section-unsaved');
				if (jQuery('.js-wpv-section-unsaved').length < 1) {
					setConfirmUnload(false);
				}
			}
			jQuery(row).fadeOut(500, function(){
				jQuery(this).remove();
				wpv_custom_field_initialize_relationship();
				wpv_filters_exist();
			});
			jQuery('.js-filter-add-select').val('-1');
		} else {
			console.log( "Error: AJAX returned ", response );
		}

	})
	.fail(function(jqXHR, textStatus, errorThrown) {
		console.log( "Error: ", textStatus, errorThrown );
	})
	.always(function() {

	});
});

jQuery(document).on('click', '.js-filter-custom-field .js-filter-custom-field-row-remove', function(e) {
	if (jQuery(this).hasClass('js-multiple-items')) {
		jQuery.colorbox({
			inline: true,
		  href:'.js-filter-custom-field-delete-filter-row-dialog',
		  open: true
		});
	} else {
		wpv_remove_custom_field_filters();
	}
});


function wpv_remove_custom_field_filters() {
	jQuery('.js-filter-list .js-filter-custom-field').find('.js-wpv-filter-edit-ok').removeClass('js-wpv-section-unsaved');
	if (jQuery('.js-wpv-section-unsaved').length < 1) {
		setConfirmUnload(false);
	}
	jQuery('.js-filter-list .js-filter-custom-field .js-filter-remove').each(function() {
		var data_view_id = jQuery('.js-post_ID').val(),
		row = jQuery(this).parents('.js-filter-row-multiple-element'),
		field = row.data('field'),
		nonce = jQuery(this).data('nonce');console.log(row);
		var data = {
			action: 'wpv_filter_custom_field_delete',
			id: data_view_id,
			field: field,
			wpnonce: nonce,
		};
		jQuery.ajax({
			async:false,
			type:"POST",
			url:ajaxurl,
			data:data,
			success:function(response){
				if ( (typeof(response) !== 'undefined') && (response === data.id) ) {
				     jQuery(row).fadeOut(500, function(){
					     jQuery(this).remove();
					     wpv_custom_field_initialize_relationship();
					     wpv_filters_exist();
				     });
				     jQuery('.js-filter-add-select').val('-1');
				} else {
				     console.log( "Error: AJAX returned ", response );
				}
		     },
		     error: function (ajaxContext) {
			     console.log( "Error: ", ajaxContext.responseText );
		     },
		     complete: function() {
			     
		     }
		});
	});
}

jQuery(document).on('click', '.js-filter-custom-field-edit-filter-row', function(e) {
	e.preventDefault();
	jQuery('.js-filter-list .js-filter-custom-field .js-wpv-filter-edit-open').trigger('click');
	jQuery('.js-filter-custom-field .js-filter-custom-field-row-remove').colorbox.close();
})

jQuery(document).on('click', '.js-filters-custom-field-delete-filter-row', function() {
	spinnerContainer = jQuery('<div class="spinner ajax-loader">').insertBefore(jQuery(this)).show();
	wpv_remove_custom_field_filters();
	spinnerContainer.remove();
	jQuery('.js-filter-custom-field .js-filter-custom-field-row-remove').colorbox.close();
});


jQuery(document).on('click', '.js-wpv-filter-usermeta-field-controls .js-filter-remove', function() {
	var data_view_id = jQuery('.js-post_ID').val();
	var row = jQuery(this).parents('.js-filter-row-multiple-element');
	var field = row.data('field');
	var nonce = jQuery(this).data('nonce');
	var data = {
		action: 'wpv_filter_usermeta_field_delete',
		id: data_view_id,
		field: field,
		wpnonce: nonce,
	};
	jQuery.post(ajaxurl, data, function(response) {
		if ( (typeof(response) !== 'undefined') && (response === data.id) ) {
			if ( !jQuery('.js-filter-list .js-filter-usermeta-field .js-filter-usermeta-field-row-remove').hasClass('js-multiple-items') ) {
				jQuery('.js-filter-list .js-filter-usermeta-field').find('.js-wpv-filter-edit-ok').removeClass('js-wpv-section-unsaved');
				if (jQuery('.js-wpv-section-unsaved').length < 1) {
					setConfirmUnload(false);
				}
			}
			jQuery(row).fadeOut(500, function(){
				jQuery(this).remove();
				wpv_usermeta_field_initialize_relationship();
				wpv_filters_exist();
			});
			jQuery('.js-filter-add-select').val('-1');
		} else {
			console.log( "Error: AJAX returned ", response );
		}

	})
	.fail(function(jqXHR, textStatus, errorThrown) {
		console.log( "Error: ", textStatus, errorThrown );
	})
	.always(function() {

	});
});
jQuery(document).on('click', '.js-filter-usermeta-field .js-filter-usermeta-field-row-remove', function(e) {
	if (jQuery(this).hasClass('js-multiple-items')) {
		jQuery.colorbox({
			inline: true,
		  href:'.js-filter-usermeta-field-delete-filter-row-dialog',
		  open: true
		});
	} else {
		wpv_remove_usermeta_field_filters();
	}
});
function wpv_remove_usermeta_field_filters() {
	jQuery('.js-filter-list .js-filter-usermeta-field').find('.js-wpv-filter-edit-ok').removeClass('js-wpv-section-unsaved');
	if (jQuery('.js-wpv-section-unsaved').length < 1) {
		setConfirmUnload(false);
	}
	jQuery('.js-filter-list .js-filter-usermeta-field .js-filter-remove').each(function() {
		var data_view_id = jQuery('.js-post_ID').val(),
		row = jQuery(this).parents('.js-filter-row-multiple-element'),
		field = row.data('field'),
		nonce = jQuery(this).data('nonce');
		console.log(row);
		var data = {
			action: 'wpv_filter_usermeta_field_delete',
			id: data_view_id,
			field: field,
			wpnonce: nonce,
		};
		jQuery.ajax({
			async:false,
			type:"POST",
			url:ajaxurl,
			data:data,
			success:function(response){
				
				if ( (typeof(response) !== 'undefined') && (response === data.id) ) {
				     jQuery(row).fadeOut(500, function(){
					     jQuery(this).remove();
					     wpv_usermeta_field_initialize_relationship();
					     wpv_filters_exist();
				     });
				     jQuery('.js-filter-add-select').val('-1');
				} else {
				     console.log( "Error: AJAX returned ", response );
				}
		     },
		     error: function (ajaxContext) {
			     console.log( "Error: ", ajaxContext.responseText );
		     },
		     complete: function() {
			     
		     }
		});
	});
}

jQuery(document).on('click', '.js-filter-usermeta-field-edit-filter-row', function(e) {
	e.preventDefault();
	jQuery('.js-filter-list .js-filter-usermeta-field .js-wpv-filter-edit-open').trigger('click');
	jQuery('.js-filter-usermeta-field .js-filter-usermeta-field-row-remove').colorbox.close();
})

jQuery(document).on('click', '.js-filters-usermeta-field-delete-filter-row', function() {
	spinnerContainer = jQuery('<div class="spinner ajax-loader">').insertBefore(jQuery(this)).show();
	wpv_remove_usermeta_field_filters();
	spinnerContainer.remove();
	jQuery('.js-filter-usermeta-field .js-filter-usermeta-field-row-remove').colorbox.close();
});

/*jQuery(function($){
 *
 / / *h*ide filter details (if any) by default

 $('.wpv-filter-edit').hide();

 // hide multiple filters if empty

 $('.wpv-filter-multiple').each(function(){
	 if (0 == $(this).find('.wpv_edit_row').length) {
		 $(this).hide();
	 }
 });

 // hide filter list if there are no filters

 if (0 == $('.js-filter-list').find('.js-filter-row').length) {
	 $('.js-filter-list').hide();
	 //	$('.js-no-filters').show();
 } else {
	 $('.js-no-filters').hide();
 }

 // open edit details on demand

 $('.js-wpv-edit-open').click(function(e){
	 e.preventDefault();
	 var where = $(this).data('filter');
	 $('.'+ where + '-summary').hide();
	 $('#'+ where).fadeIn('fast');
	 $('#'+ where).prev('.edit-filter').addClass('hidden');
 });

 // close edit details

 $('.js-filter-list .js-wpv-filter-edit-cancel').click(function(e){
	 e.preventDefault();
	 var where = $(this).data('filter');
	 $('.'+ where + '-summary').fadeIn('fast');
	 $('#'+ where).hide();
	 $('#'+ where).prev('.edit-filter').removeClass('hidden');
	 $('html,body').animate({scrollTop:$('.js-wpv-settings-content-filter').offset().top-25}, 500);
 });
 });*/