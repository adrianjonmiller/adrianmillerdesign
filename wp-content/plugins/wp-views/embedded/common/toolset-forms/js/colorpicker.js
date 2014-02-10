
var wptColorpicker = (function($) {
    function init(parent) {
        if (wptColorpickerData.use_farbtastic && $.isFunction($.fn.farbtastic)) {
                farbtasticWptPickColor.init(parent);
        } else if ($.isFunction($.fn.wpColorPicker)) {
            wpColorpicker(parent);
            wptCallbacks.addRepetitive.add(wptColorpicker.wpColorpicker);
        }
    }
    function wpColorpicker(parent) {
        $('.js-wpt-colorpicker:not(.wp-color-picker)', $(parent)).each(function() {
            $(this).not(':disabled').wpColorPicker();
        });
    }
    return {init: init, wpColorpicker: wpColorpicker};
})(jQuery);

var farbtasticWpt;
var farbtasticWptPickColor = (function($) {
    var el;
    function init(parent) {
        $(parent).on('click', '.js-wpt-pickcolor', function(e) {
            e.preventDefault();
            toggle($(this));
            return false;
        });
        farbtasticWpt = $.farbtastic('#wpt-color-picker', callback);
    }
    function callback(color) {
        el.parent().find('.js-wpt-cp-preview').css('background-color', color)
                .parent().find('.js-wpt-colorpicker').val(color);
    }
    function toggle(element) {
        el = element;
        if ($('#wpt-color-picker').is(':visible')) {
            $('#wpt-color-picker').hide();
            toggleButton(false);
        } else {
            var offset = el.offset();
            farbtasticWpt.setColor(el.parent().find('.js-wpt-colorpicker').val());
            $('#wpt-color-picker').show().offset({left: offset.left, top: Math.round(offset.top + 25)});
            toggleButton(true);
        }
    }
    function toggleButton(show) {
        $('.js-wpt-pickcolor').text(wptColorpickerData.pickTxt);
        el.text(show ? wptColorpickerData.doneTxt : wptColorpickerData.pickTxt);
    }
    return {init: init, callback: callback};
})(jQuery);


jQuery(document).ready(function() {
    wptColorpicker.init('body');
});