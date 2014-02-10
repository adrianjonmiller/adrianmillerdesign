<?php
require_once 'api.php';

define( 'WPTOOLSET_FORMS_VERSION', '0.1' );
define( 'WPTOOLSET_FORMS_ABSPATH', dirname( __FILE__ ) );
define( 'WPTOOLSET_FORMS_RELPATH', plugins_url( '', __FILE__ ) );
define( 'WPTOOLSET_COMMON_PATH',
        dirname( dirname( __FILE__ ) ) . '/types/embedded/common' );

//define( 'WPTOOLSET_COMMON_PATH', dirname( WPTOOLSET_FORMS_ABSPATH ) );

class WPToolset_Forms_Bootstrap
{

    private $__forms;

    public final function __construct(){
        // Custom conditinal AJAX check
        add_action( 'wp_ajax_wptoolset_custom_conditional',
                array($this, 'ajaxCustomConditional') );
        
        // Date conditinal AJAX check
        add_action( 'wp_ajax_wptoolset_conditional',
                array($this, 'ajaxConditional') );

        // File media popup
        if ( (isset( $_GET['context'] ) && $_GET['context'] == 'wpt-fields-media-insert') || (isset( $_SERVER['HTTP_REFERER'] ) && strpos( $_SERVER['HTTP_REFERER'],
                        'context=wpt-fields-media-insert' ) !== false)
        ) {
            require_once WPTOOLSET_FORMS_ABSPATH . '/classes/class.file.php';
            add_action( 'init', array('WPToolset_Field_File', 'mediaPopup') );
        }
    }

    // returns HTML
    public function field( $form_id, $config, $value ) {
        $form = $this->form( $form_id, array() );
        return $form->metaform( $config, $config['name'], $value );
    }

    // returns HTML
//    public function fieldEdit( $form_id, $config ){
//        $form = $this->form( $form_id, array() );
//        return $form->editform( $config );
//    }

    public function form( $form_id, $config = array() ) {
        if ( isset( $this->__forms[$form_id] ) ) {
            return $this->__forms[$form_id];
        }
        require_once WPTOOLSET_FORMS_ABSPATH . '/classes/class.form_factory.php';
        return $this->__forms[$form_id] = new FormFactory( $form_id, $config );
    }

    public function validate_field( $form_id, $config, $value ) {
        if ( empty( $config['validation'] ) ) {
            return true;
        }
        $form = $this->form( $form_id, array() );
        return $form->validateField( $config, $value );
    }

    public function ajaxCustomConditional() {
        require_once WPTOOLSET_FORMS_ABSPATH . '/classes/class.conditional.php';
        WPToolset_Forms_Conditional::ajaxCustomConditional();
    }

    public function conditional( $config, $values = array() ) {
        require_once WPTOOLSET_FORMS_ABSPATH . '/classes/class.conditional.php';
        return WPToolset_Forms_Conditional::evaluate( $config, $values );
    }
    
    public function ajaxConditional() {
        echo $this->conditional( $_POST['conditions'], $_POST['values'] );
        die();
    }

}

$wptoolset_forms = new WPToolset_Forms_Bootstrap();