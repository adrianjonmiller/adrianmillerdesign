<?php
require_once 'class.field_factory.php';
require_once WPTOOLSET_FORMS_ABSPATH . '/lib/adodb-time.inc.php';

/**
 * Description of class
 *
 * @author Srdjan
 */
class WPToolset_Field_Date extends FieldFactory
{

    protected static $_supported_date_formats = array('F j, Y', //December 23, 2011
        'Y/m/d', // 2011/12/23
        'm/d/Y', // 12/23/2011
        'd/m/Y' // 23/22/2011
    );
    protected $_supported_date_formats_text = array('F j, Y' => 'Month dd, yyyy',
        'Y/m/d' => 'yyyy/mm/dd',
        'm/d/Y' => 'mm/dd/yyyy',
        'd/m/Y' => 'dd/mm/yyyy'
    );

    public function init(){
        }

    public static function registerScripts() {
        if ( wp_script_is( 'wptoolset-field-date', 'registered' ) ) return;
        wp_register_script( 'wptoolset-field-date',
                WPTOOLSET_FORMS_RELPATH . '/js/date.js',
                array('jquery-ui-datepicker'), WPTOOLSET_FORMS_VERSION, true );
        // Localize datepicker
        if ( in_array( self::getDateFormat(), self::$_supported_date_formats ) ) {
            $locale = str_replace( '_', '-', strtolower( get_locale() ) );
            $file = WPTOOLSET_FORMS_ABSPATH . '/js/i18n/jquery.ui.datepicker-'
                    . $locale . '.js';
            if ( file_exists( $file ) ) {
                wp_register_script( 'wptoolset-field-date-localized',
                        WPTOOLSET_FORMS_RELPATH . '/js/i18n/jquery.ui.datepicker-'
                        . $locale . '.js', array('jquery-ui-datepicker'),
                        WPTOOLSET_FORMS_VERSION, true );
            }
        }
    }

    public static function registerStyles() {
        if ( wp_style_is( 'wptoolset-field-date', 'registered' ) ) return;
        wp_register_style( 'wptoolset-field-datepicker',
                WPTOOLSET_FORMS_RELPATH . '/css/wpt-jquery-ui/datepicker.css',
                array(), WPTOOLSET_FORMS_VERSION );
        wp_register_style( 'wptoolset-field-date',
                WPTOOLSET_FORMS_RELPATH . '/css/wpt-jquery-ui/jquery-ui-1.9.2.custom.min.css',
                array('wptoolset-field-datepicker'), WPTOOLSET_FORMS_VERSION );
    }

    public static function addFilters(){
        if ( has_filter( 'wptoolset_validation_value_date',
                        array('WPToolset_Field_Date', 'filterValidationValue') ) )
                return;
        // Filter validation
        add_filter( 'wptoolset_validation_value_date',
                array('WPToolset_Field_Date', 'filterValidationValue') );
        add_filter( 'wptoolset_validation_rule_js',
                array('WPToolset_Field_Date', 'filterValidationRuleJs') );
        add_filter( 'wptoolset_validation_args_php',
                array('WPToolset_Field_Date', 'filterValidationArgsPhp'), 10, 2 );
        // Filter conditional
        add_filter( 'wptoolset_conditional_args_php',
                array('WPToolset_Field_Date', 'filterConditionalArgsPhp'), 10, 2 );
        add_filter( 'wptoolset_conditional_value_php',
                array('WPToolset_Field_Date', 'filterConditionalValuePhp'), 10,
                2 );
        add_filter( 'wptoolset_conditional_args_js',
                array('WPToolset_Field_Date', 'filterConditionalArgsJs'), 10, 2 );
    }

    public function enqueueScripts() {
        if ( wp_script_is( 'wptoolset-field-date', 'enqueued' ) ) return;
        wp_enqueue_script( 'wptoolset-field-date' );
        $date_format = self::getDateFormat();
        $js_data = array('buttonImage' => WPTOOLSET_FORMS_RELPATH . '/images/calendar.gif',
            'buttonText' => __( 'Select date' ), 'dateFormat' => $this->_convertPhpToJs( $date_format ),
            'dateFormatPhp' => $date_format,
            'dateFormatNote' => esc_js( sprintf( __( 'Input format: %s' ),
                            self::getDateFormat() ) ));
        wp_localize_script( 'wptoolset-field-date', 'wptDateData', $js_data );
        wp_enqueue_script( 'wptoolset-field-date-localized' );
    }

    public function enqueueStyles() {
        if ( wp_style_is( 'wptoolset-field-date', 'enqueued' ) ) return;
        wp_enqueue_style( 'wptoolset-field-date' );
    }

    public function metaform() {
        $timestamp = $this->getValue();
        if ( !is_numeric( $timestamp ) )
                $timestamp = self::strtotime( $timestamp );
        if ( !empty( $timestamp ) ) {
            $datepicker = adodb_date( self::getDateFormat(), $timestamp );
            $hour = adodb_date( 'H', $timestamp );
            $minute = adodb_date( 'i', $timestamp );
        } else {
            $datepicker = $hour = $minute = null;
        }
        $data = $this->getData();
        $form = array();
        $form[] = array(
            '#type' => 'textfield',
            '#title' => $this->getTitle(),
            '#attributes' => array('class' => 'js-wpt-date', 'style' => 'width:150px;'),
            '#name' => $this->getName() . '[datepicker]',
            '#value' => $datepicker,
            '#validate' => $this->getValidationData(),
        );

        if ( !empty( $data['add_time'] ) ) {
            // Hour
            $hours = 24;
            $options = array();
            for ( $index = 0; $index < $hours; $index++ ) {
                $prefix = $index < 10 ? '0' : '';
                $options[$index] = array(
                    '#title' => $prefix . strval( $index ),
                    '#value' => $index,
                );
            }
            $form[] = array(
                '#type' => 'select',
                '#title' => __( 'Hour' ),
                '#options' => $options,
                '#default_value' => $hour,
                '#name' => $this->getName() . '[hour]',
                '#inline' => true,
            );
            // Minutes
            $minutes = 60;
            $options = array();
            for ( $index = 0; $index < $minutes; $index++ ) {
                $prefix = $index < 10 ? '0' : '';
                $options[$index] = array(
                    '#title' => $prefix . strval( $index ),
                    '#value' => $index,
                );
            }
            $form[] = array(
                '#type' => 'select',
                '#title' => __( 'Minute' ),
                '#options' => $options,
                '#default_value' => $minute,
                '#name' => $this->getName() . '[minute]',
                '#inline' => true,
            );
        }

        return $form;
    }

    public static function getDateFormat() {
        $date_format = get_option( 'date_format' );
        if ( !in_array( $date_format, self::$_supported_date_formats ) ) {
            $date_format = 'F j, Y';
        }
        return $date_format;
    }

    protected function _convertPhpToJs( $date_format ) {
        $date_format = str_replace( 'd', 'dd', $date_format );
        $date_format = str_replace( 'j', 'd', $date_format );
        $date_format = str_replace( 'l', 'DD', $date_format );
        $date_format = str_replace( 'm', 'mm', $date_format );
        $date_format = str_replace( 'n', 'm', $date_format );
        $date_format = str_replace( 'F', 'MM', $date_format );
        $date_format = str_replace( 'Y', 'yy', $date_format );

        return $date_format;
    }

    protected function _dateToStrftime( $format ) {
        $format = str_replace( 'd', '%d', $format );
        $format = str_replace( 'D', '%a', $format );
        $format = str_replace( 'j', '%e', $format );
        $format = str_replace( 'l', '%A', $format );
        $format = str_replace( 'N', '%u', $format );
        $format = str_replace( 'w', '%w', $format );

        $format = str_replace( 'W', '%W', $format );

        $format = str_replace( 'F', '%B', $format );
        $format = str_replace( 'm', '%m', $format );
        $format = str_replace( 'M', '%b', $format );
        $format = str_replace( 'n', '%m', $format );

        $format = str_replace( 'o', '%g', $format );
        $format = str_replace( 'Y', '%Y', $format );
        $format = str_replace( 'y', '%y', $format );

        return $format;
    }

    public static function filterValidationValue( $value ) {
        if ( isset( $value['datepicker'] ) ) {
            return $value['datepicker'];
        }
        return $value;
    }

    public static function filterValidationRuleJS( $rule ) {
        if ( $rule == 'date' && self::getDateFormat() == 'd/m/Y' ) {
            return 'dateITA';
        }
        return $rule;
    }

    public static function filterValidationArgsPhp( $args, $rule ) {
        if ( $rule == 'date' ) {
            return array('$value', self::getDateFormat());
        }
        return $args;
    }

    public static function filterConditionalArgsJs( $args, $type ) {
        if ( $type == 'date' ) {
            foreach ( $args as &$arg ) {
                if ( !is_numeric( $arg ) ) {
                    $arg = self::strtotime( $arg );
                }
            }
        }
        return $args;
    }

    public static function filterConditionalArgsPhp( $args, $type ) {
        return self::filterConditionalArgsJs( $args, $type );
    }

    public static function filterConditionalValuePhp( $value, $type ) {
        if ( $type == 'date' ) {
            return self::strtotime( $value );
        }
        return $value;
    }

    public static function strtotime( $value ) {
        if ( self::getDateFormat() == 'd/m/Y' ) {
            // strtotime requires a dash or dot separator to determine dd/mm/yyyy format
            $value = str_replace( '/', '-', $value );
        }
        $time = strtotime( strval( $value ) );
        if ( $time == false || !self::timeIsValid( $time ) ) {
            // Failed converting
            return false;
        }
        return $time;
    }

    /**
     * Checks if timestamp is numeric and within range.
     * 
     * @param type $timestamp
     * @return type
     */
    public static function timeIsValid( $time ) {
        /*
         * http://php.net/manual/en/function.strtotime.php
         * The valid range of a timestamp is typically
         * from Fri, 13 Dec 1901 20:45:54 UTC
         * to Tue, 19 Jan 2038 03:14:07 UTC.
         * (These are the dates that correspond to the minimum
         * and maximum values for a 32-bit signed integer.)
         * Additionally, not all platforms support negative timestamps,
         * therefore your date range may be limited to no earlier than
         * the Unix epoch.
         * This means that e.g. dates prior to Jan 1, 1970 will not
         * work on Windows, some Linux distributions,
         * and a few other operating systems.
         * PHP 5.1.0 and newer versions overcome this limitation though. 
         */
        // MIN 'Jan 1, 1970' - 0 | Fri, 13 Dec 1901 20:45:54 UTC
        $_min_time = self::timeNegativeSupported() ? -2147483646 : 0;
        // MAX 'Tue, 19 Jan 2038 03:14:07 UTC' - 2147483647
        $_max_time = 2147483647;

        return is_numeric( $time ) && $_min_time <= intval( $time ) && intval( $time ) <= $_max_time;
    }

    /**
     * Checks if timestamp supports negative values.
     * 
     * @return type
     */
    public static function timeNegativeSupported() {
        return strtotime( 'Fri, 13 Dec 1950 20:45:54 UTC' ) === -601010046;
    }

}