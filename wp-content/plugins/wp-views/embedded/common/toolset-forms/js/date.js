
var wptDate = (function($) {
    var _tempConditions, _tempField;
    function init(parent) {
        if ($.isFunction($.fn.datepicker)) {
            $('.js-wpt-date', $(parent)).each(function(index) {
                if (!$(this).is(':disabled') && !$(this).hasClass('hasDatepicker')) {
                    $(this).datepicker({
                        showOn: "button",
                        buttonImage: wptDateData.buttonImage,
                        buttonImageOnly: true,
                        buttonText: wptDateData.buttonText,
                        dateFormat: wptDateData.dateFormat,
                        altFormat: wptDateData.dateFormat,
                        changeMonth: true,
                        changeYear: true,
                        yearRange: '100:3000',
                        onSelect: function(dateText, inst) {
//                            $(this).trigger('wptDateSelect');
                            $(this).trigger('blur');
                        }
                    });
                    $(this).next().after('<span style="margin-left:10px"><i>' + wptDateData.dateFormatNote + '</i></span>');
                    // Wrap in CSS Scope
                    $("#ui-datepicker-div", $(parent)).each(function() {
                        if (!$(this).hasClass('wpt-jquery-ui-wrapped')) {
                            $(this).wrap('<div class="wpt-jquery-ui" />')
                                    .addClass('wpt-jquery-ui-wrapped');
                        }
                    });
                }
            });
        }
    }
    function ajaxConditional(formID, conditions, field) {
        _tempConditions = conditions;
        _tempField = field;
        wptCallbacks.conditionalCheck.add(wptDate.ajaxCheck);
    }
    function ajaxCheck(formID) {
        wptCallbacks.conditionalCheck.remove(wptDate.ajaxCheck);
        wptCond.ajaxCheck(formID, _tempField, _tempConditions);
    }
    return {
        init: init,
        ajaxConditional: ajaxConditional,
        ajaxCheck: ajaxCheck
    };
})(jQuery);

jQuery(document).ready(function() {
    wptDate.init('body');
});
wptCallbacks.addRepetitive.add(wptDate.init);
add_action('conditional_check_date', wptDate.ajaxConditional, 10, 3);