/* 
 * Repetitive JS.
 */
var wptRep = (function($) {
    function init() {
        // Reorder elements if repetitive
        $('.js-wpt-repetitive').each(function(){
            $('.description', $(this)).first().prependTo($(this));
            $('label', $(this)).first().prependTo($(this));
            $('.js-wpt-field-items label, .js-wpt-field-items .description', $(this)).remove();
        });
        sortable('.js-wpt-field');
        $('.js-wpt-repadd').on('click', function() {
            var $this = $(this), $parent = $this.parents('.js-wpt-field');
            var tpl = $('<div>' + $('#tpl-wpt-field-' + $parent.data('wpt-id')).html() + '</div>');
            $('[id]', tpl).each(function() {
                var $this = $(this), uniqueId = _.uniqueId('wpt-form-el');
                tpl.find('label[for="' + $this.attr('id') + '"]').attr('for', uniqueId);
                $this.attr('id', uniqueId);
            });
            $('label', tpl).first().remove();
            $('.description', tpl).first().remove();
            $('.js-wpt-field-items', $parent).append(tpl.html().replace("%%unique%%", _.uniqueId('wptrep-')));
            wptCallbacks.addRepetitive.fire($parent);
            sortable($parent);
            return false;
        });
        $('.js-wpt-field').on('click', '.js-wpt-repdelete', function() {
            var $this = $(this), $parent = $this.parents('.js-wpt-field');
            // Allow deleting if more than one field item
            if ($('.js-wpt-field-item', $parent).length > 1) {
                var formID = $this.parents('form').attr('id');
                $this.parents('.js-wpt-field-item').remove();
                wptCallbacks.removeRepetitive.fire(formID);
            }
            return false;
        });
    }
    function sortable(container) {
        $('.js-wpt-field-items', $(container)).sortable({
            revert: true,
            handle: '.js-wpt-repdrag',
            axis: "y"
        });
    }
    return {
        init: init
    };
})(jQuery);

jQuery(document).ready(wptRep.init);