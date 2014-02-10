<?php
require_once 'class.textfield.php';

/**
 * Description of class
 *
 * @author Srdjan
 */
class WPToolset_Field_Checkboxes extends WPToolset_Field_Textfield
{

    public function metaform() {
        $value = $this->getValue();
        $data = $this->getData();
        
        $form = array();
        $_options = array();
        if (isset($data['options'])) {
            foreach ( $data['options'] as $option_key => $option ) {
                $_options[$option_key] = array(
                    '#value' => $option['value'],
                    '#title' => $option['title'],
                    '#type' => 'checkbox',
                    '#default_value' => $option['checked'],
                    '#name' => $data['name'],
                    '#inline' => true,
                    '#after' => '<br />',
                );
            }
        }
        $form[] = array(
            '#type' => 'checkboxes',
            '#name' => $this->getName(),
            '#options' => $_options,
        );
        return $form;
    }

}