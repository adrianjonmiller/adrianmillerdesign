<?php
require_once 'abstract.form.php';
require_once 'class.eforms.php';
require_once 'class.field_factory.php';

define( "CLASS_NAME_PREFIX", "WPToolset_Field_" );

/**
 * FormFactory
 * Creation Form Class 
 * @author onTheGo System
 *
 */
class FormFactory extends FormAbstract
{

    private $field_count = 0;
    private $form = array();
    private $nameForm;
    private $theForm;
    protected $_validation, $_conditional, $_repetitive;

    public function __construct( $nameForm = 'default' ) {
        if ( !isset( $GLOBALS['formFactories'] ) )
                $GLOBALS['formFactories'] = array();
        $this->nameForm = $nameForm;
        $this->field_count = 0;
        $this->theForm = new Enlimbo_Forms( $nameForm );

        wp_register_script( 'wptoolset-forms',
                WPTOOLSET_FORMS_RELPATH . '/js/main.js',
                array('jquery', 'underscore'), WPTOOLSET_FORMS_VERSION, false );
        wp_enqueue_script( 'wptoolset-forms' );

        if ( is_admin() ) {
            wp_register_style( 'wptoolset-forms-admin',
                    WPTOOLSET_FORMS_RELPATH . '/css/admin.css', array(),
                    WPTOOLSET_FORMS_VERSION );
            wp_enqueue_style( 'wptoolset-forms-admin' );
        }
    }

    /**
     * (non-PHPdoc)
     * @see classes/FormAbstract::formNameExists()
     */
    public function formNameExists( &$nameForm ) {
        if ( !in_array( $nameForm, $GLOBALS['formFactories'] ) ) {
            $GLOBALS['formFactories'][] = $nameForm;
            return false;
        } else {
            echo "Form name already exists!";
            return true;
        }
    }

    /**
     * getClassFromType
     * Return the class name from a type
     * @param unknown_type $type
     */
    protected function getClassFromType( $type ) {
        return $type;
    }

    /**
     * (non-PHPdoc)
     * @see classes/FormAbstract::getFieldObject()
     */
    public function getFieldObject( $data, $global_name_field, $value ) {
        $type = $data['type'];
        $class = $this->getClassFromType( $type );
        $file = WPTOOLSET_FORMS_ABSPATH . "/classes/class.{$class}.php";
        if ( file_exists( $file ) ) {
            require_once WPTOOLSET_FORMS_ABSPATH . "/classes/class.{$class}.php";
            $class = CLASS_NAME_PREFIX . ucfirst( $class );
            if ( class_exists( $class ) ) {
                return new $class( $data, $global_name_field, $value );
            }
        }
        return null;
    }

    /**
     * (non-PHPdoc)
     * @see classes/FormAbstract::addFormField()
     */
    public function addFormField( $data ) {
        //check mandatory info in data like type and name field
        $global_name_field = $this->nameForm . '_field_' . $this->field_count;
        $obj = $this->getFieldObject( $data, $global_name_field );
        $this->form[$global_name_field] = $obj->metaform();
        $this->field_count++;
    }

    /**
     * (non-PHPdoc)
     * @see classes/FormAbstract::createForm()
     */
    public function createForm( $nameForm = 'default' ) {
        if ( $this->formNameExists( $nameForm ) ) return;
        $this->theForm->autoHandle( $nameForm, $this->form );

        $out = "";
        $out .= '<form method="post" action="" id="' . $nameForm . '">';
        $out .= $this->theForm->renderElements( $this->form );
        //$out .= $this->theForm->renderForm();
        $out .= '</form>';

        return $out;
    }

    /**
     * (non-PHPdoc)
     * @see classes/FormAbstract::displayForm()
     */
    public function displayForm( $nameForm = 'default' ) {
        if ( $this->formNameExists( $nameForm ) ) return;
        $myform = $this->theForm;
        $this->theForm->autoHandle( $nameForm, $this->form );

        echo '<form method="post" action="" id="' . $nameForm . '">';
        echo $this->theForm->renderForm();
        echo '</form>';
    }

    /**
     * metaform
     * @param type $name
     * @param type $type
     * @param type $config
     * @param type $global_name_field
     * @param type $value
     * @return type
     */
    public function metaform( $config, $global_name_field, $value ){
        $htmlArray = array();
        $_gnf = $global_name_field;
        $_cfg = $config;
        if ( empty( $value ) ) $value = array(null);
        elseif ( !is_array( $value ) ) $value = array($value);
        foreach ( $value as $val ) {
            if ( isset($config['repetitive']) && $config['repetitive'] ) {
                $_gnf = $_cfg['name'] = "{$global_name_field}[{$this->field_count}]";
            }
            if ( !is_wp_error( $field = $this->loadField( $_cfg, $_gnf, $val ) ) ) {
                $form = $field->metaform();
                $this->form[$global_name_field] = $form;
                $this->field_count++;
                $htmlArray[] = $this->theForm->renderElements( $form );
                if ( isset($config['repetitive']) && !$config['repetitive'] ) break;
            }
        }
        if ( !empty( $htmlArray ) && isset($config['repetitive']) && $config['repetitive'] ) {
            $_gnf = $_cfg['name'] = "{$global_name_field}[%%unique%%]";
            if ( !is_wp_error( $field = $this->loadField( $_cfg, $_gnf, null ) ) ) {
                $tpl = $this->_tplItem( $config,
                        $this->theForm->renderElements( $field->metaform() ) );
                $this->_repetitive()->add( $config, $tpl );
            }
        }
        return !empty( $htmlArray ) ? $this->_tpl( $config, $htmlArray ) : '';
    }

    /**
     * 
     * @staticvar array $loaded
     * @param type $config
     * @param string $global_name_field
     * @param type $value
     * @return \WP_Error|\class
     */
    public function loadField( $config, $global_name_field, $value ){
        static $loaded = array();
        $type = $config['type'];
        // Load built-in field
        $global_name_field = $this->nameForm . '_field_' . $this->field_count;
        $field = $this->getFieldObject( $config, $global_name_field, $value );
        // Check if third party
        if ( is_null( $field ) ) {
            // third party fields array $type => __FILE__
            $third_party_fields = apply_filters( 'wptoolset_registered_fields',
                    array() );
            if ( isset( $third_party_fields[$type] ) && file_exists( $third_party_fields[$type] ) ) {
                require_once $third_party_fields[$type];
                if ( class_exists( 'WPToolset_Forms_' . ucfirst( $type ) ) ) {
                    $class = 'WPToolset_Forms_' . ucfirst( $type );
                    $field = new $class( $config, $global_name_field );
                }
            }
        }
        if ( is_null( $field ) )
                return new WP_Error( 'wptoolset_forms', 'wrong field type' );
        
        // Load/enqueue scripts
        if ( !isset( $loaded[$type] ) ) {
            $loaded[$type] = 1;
            if ( isset( $field ) ) {
                // These should be performed only once
                $field::registerScripts();
                $field::registerStyles();
                $field->enqueueScripts();
                $field->enqueueStyles();
                $field::addFilters();
                $field::addActions();
            }
        }
        $this->_checkValidation( $config );
        $this->_checkConditional( $config );
        return $field;
    }

    protected function _checkValidation( $config ) {
        if ( isset( $config['validation'] ) && is_null( $this->_validation ) ) {
            require_once 'class.validation.php';
            $this->_validation = new WPToolset_Forms_Validation( $this->nameForm );            
        }
    }

    protected function _checkConditional( $config ) {
        if ( !empty( $config['conditional'] ) ) {
            if ( is_null( $this->_conditional ) ) {
                require_once 'class.conditional.php';
                $this->_conditional = new WPToolset_Forms_Conditional( $this->nameForm );
            }
            $this->_conditional->add( $config );
        }
    }

    protected function _repetitive() {
        if ( is_null( $this->_repetitive ) ) {
            require_once 'class.repetitive.php';
            $this->_repetitive = new WPToolset_Forms_Repetitive();
        }
        return $this->_repetitive;
    }

    protected function _tpl( $cfg, $html ) {
        ob_start();
        include WPTOOLSET_FORMS_ABSPATH . '/templates/metaform.php';
        $o = ob_get_contents();
        ob_get_clean();
        return $o;
    }

    protected function _tplItem( $cfg, $out ) {
        ob_start();
        include WPTOOLSET_FORMS_ABSPATH . '/templates/metaform-item.php';
        $o = ob_get_contents();
        ob_get_clean();
        return $o;
    }    
    
    public function validateField( $config, $value ) {
        $field = $this->loadField( $config, $config['name'], $value );
        if ( !is_wp_error( $field ) ) {
            if ( !empty( $config['validation'] ) ) {
                return $this->_validation->validateField( $field );
            }
            return true;
        }
        return false;
    }

    public function __toString() {
        return join( "\n", $this->elements );
    }

}
