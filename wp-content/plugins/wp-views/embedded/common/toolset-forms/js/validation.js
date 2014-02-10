/* 
 * Validation JS
 * 
 * - Initializes validation on selector (forms)
 * - Adds/removes rules on elements contained in var wptoolsetValidationData
 * - Checks if elements are hidden by conditionals
 * 
 * @see WPCF_Validation::renderJsonData( $selector ) how rules are added
 * Use wp_enqueue_script( 'types-validation' ) to enqueue this script.
 * Use wpcf_form_render_js_validation( $selector ) to render validation data used here.
 * 
 * Used in post-relationship.js in 2 places for callback.
 */
//var wptValidationData = {};
var wptValidationForms = [];
var wptValidation = (function($) {

    function init() {
        _.each(wptValidationForms, function(formID) {
            _initValidation(formID);
            applyRules(formID);
        });
        wptCallbacks.addRepetitive.add(function(container) {
            wptValidation.applyRules(container);
        });
        wptCallbacks.removeRepetitive.add(function(container) {
            wptValidation.applyRules(container);
        });
        wptCallbacks.conditionalCheck.add(function(container) {
            wptValidation.applyRules(container);
        });
    }

    function _initValidation(formID) {
        var $form = $(formID);
        $form.validate({
            // :hidden is kept because it's default value.
            // All accepted by jQuery.not() can be added.
            ignore: 'input[type="hidden"],:not(.js-wpt-validate)',
            errorPlacement: function(error, element) {
                error.insertBefore(element);
            },
            highlight: function(element, errorClass, validClass) {
                // Expand container
                $(element).parents('.collapsible').slideDown();
                if (formID == '#post') {
                    var box = $(element).parents('postbox');
                    if (box.hasClass('closed')) {
                        box.find('.handlediv').trigger('click');
                    }
                }
                // $.validator.defaults.highlight(element, errorClass, validClass); // Do not add class to element
            },
            unhighlight: function(element, errorClass, validClass) {
                $("input#publish, input#save-post").removeClass("button-primary-disabled").removeClass("button-disabled");
                // $.validator.defaults.unhighlight(element, errorClass, validClass);
            },
            invalidHandler: function(form, validator) {
                if (formID == '#post') {
                    $('#publishing-action .spinner').css('visibility', 'hidden');
                    $('#publish').bind('click', function() {
                        $('#publishing-action .spinner').css('visibility', 'visible');
                    });
                    $("input#publish").addClass("button-primary-disabled");
                    $("input#save-post").addClass("button-disabled");
                    $("#save-action .ajax-loading").css("visibility", "hidden");
                    $("#publishing-action #ajax-loading").css("visibility", "hidden");
                }
            },
            submitHandler: function(form) {
                // Remove failed conditionals
                $('.js-wpt-remove-on-submit', $(form)).remove();
                form.submit();
            },
            errorClass: "wpt-form-error"
        });
    }

    function isIgnored($el) {
        return $el.parents('.js-wpt-field').hasClass('js-wpt-validation-ignore');
    }

    function applyRules(container) {
        $('[data-wpt-validate]', $(container)).each(function() {
            _applyRules($(this).data('wpt-validate'), this);
        });
    }

    function _applyRules(rules, selector) {
        var element = $(selector);
        if (element.length > 0) {
            if (isIgnored(element)) {
                element.rules('remove');
                element.removeClass('js-wpt-validate');
            } else if (!element.hasClass('js-wpt-validate')) {
                _.each(rules, function(value, rule) {
                    var _rule = {messages: {}};
                    _rule[rule] = value.args;
                    if (value.message !== 'undefined') {
                        _rule.messages[rule] = value.message;
                    }
                    element.rules('add', _rule);
                    element.addClass('js-wpt-validate');
                });
            }
        }
    }

    return {
        init: init,
        applyRules: applyRules,
    };

})(jQuery);