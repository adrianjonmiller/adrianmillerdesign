<?php
require_once 'class.textfield.php';

/**
 * Description of class
 *
 * @author Francesco
 */
class WPToolset_Field_Credfile extends WPToolset_Field_Textfield
{

    public function init() {
    }

    public function enqueueScripts() {
    }

    public function enqueueStyles() {
    }

    public function metaform() {
        $value = $this->getValue();
                
        $form = array();       
        // Set form
        $form[] = array(
            '#type' => 'file',
            '#name' => $this->getName(),
            '#value' => $value,
            '#title' => $this->getTitleFromName($this->getName()),
            /*'#suffix' => '&nbsp;' . $button,*/
            '#before' => '',
            '#after' => '<div style="clear:both;"></div>'.$value,
            '#validate' => $this->getValidationData(),
        );

        return $form;
    }
    
    private function getTitleFromName($name) {
        switch ($name) {
            
            case "_featured_image":
                return "Featured Image";
                
            default:
                return $name;
        }
    }
}