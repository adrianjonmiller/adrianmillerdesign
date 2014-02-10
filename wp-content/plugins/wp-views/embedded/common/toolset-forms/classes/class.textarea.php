<?php
require_once 'class.textfield.php';

/**
 * Description of class
 *
 * @author Franko
 */
class WPToolset_Field_Textarea extends WPToolset_Field_Textfield
{

    public function metaform() {
        $metaform = array();
        $metaform[] = array(
            '#type' => 'textarea',
            '#title' => $this->getTitle(),
            '#description' => $this->getDescription(),
            '#name' => $this->getName(),
            '#value' => $this->getValue(),
            '#validate' => $this->getValidationData(),
        );
        $this->set_metaform($metaform); 
        return $metaform;
    }

}
