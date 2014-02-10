<?php
require_once 'class.textfield.php';

/**
 * Description of class
 *
 * @author Srdjan
 */
class WPToolset_Field_Checkbox extends WPToolset_Field_Textfield
{

    public function metaform() {
        $value = $this->getValue();
        $data = $this->getData();

        if ( !empty( $value ) || $value == '0' ) {
            $data['default_value'] = $value;
        }
        
        $form = array();
        $form[] = array(
            '#type' => 'checkbox',
            '#value' => $value,
            '#default_value' => isset($data['default_value']) ? (bool) $data['default_value'] : null,
            '#name' => $this->getName(),
            '#title' => $this->getTitle(),
            '#validate' => $this->getValidationData(),
            '#after' => '<input type="hidden" name="_wptoolset_checkbox[' . $this->getId() . ']" value="1" />',
        );
        return $form;
    }

}