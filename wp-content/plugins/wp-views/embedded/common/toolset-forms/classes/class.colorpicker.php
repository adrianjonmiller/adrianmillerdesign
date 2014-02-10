<?php
require_once 'class.textfield.php';

/**
 * Description of class
 *
 * @author Srdjan
 */
class WPToolset_Field_Colorpicker extends WPToolset_Field_Textfield
{

    public $useFarbtastic;

    public function init() {
        global $wp_version;
        wp_register_script( 'wptoolset-field-colorpicker',
                WPTOOLSET_FORMS_RELPATH . '/js/colorpicker.js', array('jquery'),
                WPTOOLSET_FORMS_VERSION, true );
        wp_register_style( 'wptoolset-field-colorpicker',
                WPTOOLSET_FORMS_RELPATH . '/css/colorpicker.css' );
        $this->useFarbtastic = (bool) version_compare( $wp_version, '3.5', '<' );
    }

    public function enqueueScripts() {
        wp_enqueue_script( 'wptoolset-field-colorpicker' );
        $js_data = array('use_farbtastic' => $this->useFarbtastic, 'pickTxt' => __( 'Pick color' ),
            'doneTxt' => __( 'Done' ));
        wp_localize_script( 'wptoolset-field-colorpicker', 'wptColorpickerData',
                $js_data );
        if ( $this->useFarbtastic ) {
            wp_enqueue_script( 'farbtastic' );
            add_action( 'wp_footer', array($this, 'renderFarbtastic') );
            add_action( 'admin_footer', array($this, 'renderFarbtastic') );
        } else {
            wp_enqueue_script( 'wp-color-picker' );
        }
    }

    public function enqueueStyles() {
        wp_enqueue_style( 'wptoolset-field-colorpicker' );
        if ( $this->useFarbtastic ) {
            wp_enqueue_style( 'farbtastic' );
        } else {
            wp_enqueue_style( 'wp-color-picker' );
        }
    }

    public function metaform() {
        $form = array();
        $form['name'] = array(
            '#type' => 'textfield',
            '#value' => $this->getValue(),
            '#name' => $this->getName(),
            '#attributes' => array('class' => 'js-wpt-colorpicker', 'style' => 'width:100px;'),
            '#validate' => $this->getValidationData(),
            '#after' => '',
        );
        if ( $this->useFarbtastic ) {
            $form['name']['#after'] .= '<a href="#" class="button-secondary js-wpt-pickcolor">' 
                    . __( 'Pick color' )
                    . '</a><div class="js-wpt-cp-preview" style="background-color:'
                    . $this->getValue() . '"></div>';
        }
        return $form;
    }

    /**
     * Pre WP 3.5.
     */
    public function renderFarbtastic() {
        echo '<div id="wpt-color-picker" style="display:none; background-color: #FFF; width:220px; padding: 10px;"></div>';
    }

}