/* 
 * @see WPToolset_Forms_Conditional (classes/conditional.php)
 * 
 */
var wptCondTriggers = {};
var wptCondFields = {};
var wptCondCustomTriggers = {};
var wptCondCustomFields = {};
var wptCond = (function($) {
    function init() {
        _.each(wptCondTriggers, function(triggers, formID) {
            _.each(triggers, function(fields, trigger) {
                var $trigger = _getTrigger(trigger, formID);
                _bindChange(formID, $trigger, function() {
                    _check(formID, fields);
                });
            });
        });
//        _.each(wptCondFields, function(fields, formID) {
//            _check(formID, _.keys(fields));
//        });
        _.each(wptCondCustomTriggers, function(triggers, formID) {
            _.each(triggers, function(fields, trigger) {
                var $trigger = _getTrigger(trigger, formID);
                _bindChange(formID, $trigger, function() {
                    _custom(formID, fields);
                });
            });
        });
//        _.each(wptCondCustomFields, function(fields, formID) {
//            _custom(formID, _.keys(fields));
//        });
        // Fire validation after init conditional
        wptCallbacks.validationInit.fire();
    }
    function _getTrigger(trigger, formID) {
        var $container = $('[data-wpt-id="' + trigger + '"]', formID);
        var $trigger = $('.js-wpt-cond-trigger', $container);
        if ($trigger.length < 1) {
            $trigger = $(':input', $container).first();
        }
        $trigger._wptType = $container.data('wpt-type');
        return $trigger;
    }
    function _getTriggerValue($trigger) {
        // Do not add specific filtering for fields here
        // Use add_filter() to apply filters from /js/$type.js
        var val = $trigger.val();
        if ($trigger._wptType == 'radio') {
            val = $('[name="' + $trigger.attr('name') + '"]:checked', formID).val();
        }
        val = apply_filters('conditional_value_' + $trigger._wptType,
                val, $trigger);
        return val;
    }
    function _getAffected(affected, formID) {
        return $('[data-wpt-id="' + affected + '"]', formID);
    }
    function _check(formID, fields) {
        _.each(fields, function(field) {
            var c = wptCondFields[formID][field];
            var passedOne = false, passedAll = true, passed = false;
            _.each(c.conditions, function(data) {
                var $trigger = _getTrigger(data.id, formID);
                var val = _getTriggerValue($trigger);
                do_action('conditional_check_' + data.type, formID, c, field);
                var operator = data.operator, _val = data.args[0];
                switch (operator) {
                    case '===':
                    case '==':
                    case '=':
                        passed = val == _val;
                        break;
                    case '!==':
                    case '!=':
                        passed = val != _val;
                        break;
                    case '>':
                        passed = parseInt(val) > parseInt(_val);
                        break;
                    case '<':
                        passed = parseInt(val) < parseInt(_val);
                        break;
                    case '>=':
                        passed = parseInt(val) >= parseInt(_val);
                        break;
                    case '<=':
                        passed = parseInt(val) <= parseInt(_val);
                        break;
                    case 'between':
                        passed = parseInt(val) > parseInt(_val) && parseInt(val) < parseInt(data.args[1]);
                        break;
                    default:
                        passed = false;
                        break;
                }
                if (!passed) {
                    passedAll = false;
                } else {
                    passedOne = true;
                }
            });

            if (c.relation === 'AND' && passedAll)
                passed = true;
            if (c.relation === 'OR' && passedOne)
                passed = true;

            _showHide(passed, _getAffected(field, formID));
        });
        wptCallbacks.conditionalCheck.fire(formID);
    }
    function _bindChange(formID, $trigger, func) {
        // Do not add specific binding for fields here
        // Use add_action() to bind change trigger from /js/$type.js
        // if not provided - default binding will be performed
        var binded = do_action('conditional_trigger_bind_' + $trigger._wptType,
                $trigger, func, formID);
        if (!binded) {
            if ($trigger._wptType == 'checkbox') {
                $trigger.on('click', func);
            } else if ($trigger._wptType == 'radio') {
                $('[name="' + $trigger.attr('name') + '"]').on('click', func);
            } else if ($trigger._wptType == 'select') {
                $trigger.on('change', func);
//            } else if ($trigger._wptType == 'date') {
//                $trigger.on('wptDateSelect', func);
            } else {
                $trigger.on('blur', func);
            }
        }
    }
    function _custom(formID, fields) {
        var data = {action: 'wptoolset_custom_conditional', 'conditions': {}, 'values': {}};
        _.each(fields, function(field) {
            var c = wptCondCustomFields[formID][field];
            data.conditions[field] = c.custom;
            _.each(c.triggers, function(t) {
                data.values[t] = _getTriggerValue(_getTrigger(t));
            });
        });
        $.post(ajaxurl, data, function(res) {
            _.each(res.passed, function(affected) {
                _showHide(true, _getAffected(affected, formID));
            });
            _.each(res.failed, function(affected) {
                _showHide(false, _getAffected(affected, formID));
            });
            wptCallbacks.conditionalCheck.fire(formID);
        }, 'json').fail(function(data) {
            alert(data.responseText);
        });
    }
    function _showHide(show, $el) {
        if (show) {
            $el.slideDown().removeClass('js-wpt-remove-on-submit js-wpt-validation-ignore');
        } else {
            $el.slideUp().addClass('js-wpt-remove-on-submit js-wpt-validation-ignore');
        }
    }
    function ajaxCheck(formID, field, conditions) {
        var values = {};
        _.each(conditions.conditions, function(c) {
            values[c.id] = _getTriggerValue(_getTrigger(c.id));
        });
        var data = {
            'action': 'wptoolset_conditional',
            'conditions': conditions,
            'values': values
        };
        $.post(ajaxurl, data, function(passed) {
            _showHide(passed, _getAffected(field, formID));
            wptCallbacks.conditionalCheck.fire(formID);
        }).fail(function(data) {
            alert(data);
        });
    }
    return {
        init: init,
        ajaxCheck: ajaxCheck
    };
})(jQuery);