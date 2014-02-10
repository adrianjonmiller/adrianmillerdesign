
var wptSkype = (function($) {
    var $parent, $skypename, $style, $preview;
    var $popup = $('#tpl-wpt-skype-edit-button > div');
    function init() {
        $('.js-wpt-field').on('click', '.js-wpt-skype-edit-button', function() {
            $parent = $(this).parents('.js-wpt-field');
            $skypename = $('.js-wpt-skypename', $parent);
            $style = $('.js-wpt-skypestyle', $parent);
            $preview = $('.js-wpt-skype-preview', $parent);
            $('.js-wpt-skypename', $popup).val($skypename.val());
            $('[name="_js-wpt-skypestyle"][value="' + $style.val() + '"]', $popup)
                    .attr('checked', true);
            tb_show(wptSkypeData.title, "#TB_inline?height=280&width=620&inlineId=tpl-wpt-skype-edit-button", "");
        });
        $('.js-wpt-close-thickbox').on('click', function() {
            $skypename.val($skypename.val());
            var $selected = $('[name="_js-wpt-skypestyle"]:checked', $popup);
            $style.val($selected.val());
            $preview.replaceWith($selected.next().clone());
            tb_remove();
        });
    }
    return {
        init: init
    };
})(jQuery);

jQuery(document).ready(wptSkype.init);