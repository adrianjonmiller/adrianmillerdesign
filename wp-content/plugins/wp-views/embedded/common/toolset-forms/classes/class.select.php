<?php
require_once 'class.textfield.php';

/**
 * Description of class
 *
 * @author Srdjan
 */
class WPToolset_Field_Select extends WPToolset_Field_Textfield
{

    public function metaform() {
        $value = $this->getValue();
        $data = $this->getData();

        $form = array();
        $options = array();
        if (isset($data['options']))
        foreach ( $data['options'] as $option ) {
            $options[] = array(
                '#value' => $option['value'],
                '#title' => $option['title'],
            );
        }
        if ( !empty( $value ) || $value == '0' ) {
            $data['default_value'] = $value;
        }
        $form[] = array(
            '#type' => 'select',
            '#title' => $this->getTitle(),
            '#description' => $this->getDescription(),
            '#name' => $this->getName(),
            '#options' => $options,
            '#default_value' => isset( $data['default_value'] ) ? $data['default_value'] : null,
            '#validate' => $this->getValidationData(),
        );
        return $form;
    }

}