
var wptFile = (function($, w) {
    var $parent, $preview;
    function init() {
        $('.js-wpt-field').on('click', 'a.js-wpt-file-upload', function() {
            $parent = $(this).parents('.js-wpt-field');
            $preview = $('.js-wpt-file-preview', $parent);
            tb_show(wptFileData.title, wptFileData.adminurl + 'media-upload.php?' + wptFileData.for_post + 'type=file&context=wpt-fields-media-insert&wpt[id]=' + $parent.data('wpt-id') + '&wpt[type]=' + $parent.data('wpt-type') + '&TB_iframe=true');
            return false;
        });
    }
    function mediaInsert(url, type) {
        $(':input', $parent).first().val(url);
        if (type == 'image') {
            $preview.html('<img src="' + url + '" />');
        } else {
            $preview.html('');
        }
        tb_remove();
    }
    function mediaInsertTrigger(guid, type) {
        window.parent.wptFile.mediaInsert(guid, type);
        window.parent.jQuery('#TB_closeWindowButton').trigger('click');
    }
    return {
        init: init,
        mediaInsert: mediaInsert,
        mediaInsertTrigger: mediaInsertTrigger
    };
})(jQuery);

jQuery(document).ready(wptFile.init);