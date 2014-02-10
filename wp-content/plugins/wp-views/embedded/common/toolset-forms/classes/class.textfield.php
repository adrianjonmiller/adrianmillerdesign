<?php
require_once "class.fieldconfig.php";
require_once "class.field_factory.php";
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of class
 *
 * @author Franko
 */
class WPToolset_Field_Textfield extends FieldFactory {
	    
    public function init() {
    }
    
    public function enqueueScripts() {        
    }

    public function enqueueStyles() {        
    }    
            
    public function metaform() {      
        $metaform = array();
        $metaform[] = array(
            '#type' => 'textfield',
            '#title' => $this->getTitle(),
            '#description' => $this->getDescription(),
            '#name' => $this->getName(),
            '#value' => $this->getValue(),
            '#validate' => $this->getValidationData(),
        );
        $this->set_metaform($metaform);
        return $metaform;
    }

    public function editform() {
    }
    
    public function mediaEditor() {
    } 
}