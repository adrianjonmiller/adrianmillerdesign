<?php

function wptoolset_form( $form_id, $config = array() ){
    global $wptoolset_forms;
    $html = $wptoolset_forms->form( $form_id, $config );
    return apply_filters( 'wptoolset_form', $html, $config );
}

function wptoolset_form_field( $form_id, $config, $value = array() ){
    global $wptoolset_forms;
    $html = $wptoolset_forms->field( $form_id, $config, $value );
    return apply_filters( 'wptoolset_fieldform', $html, $config, $form_id );
}

//function wptoolset_form_field_edit( $form_id, $config ){
//    global $wptoolset_forms;
//    $html = $wptoolset_forms->fieldEdit( $form_id, $config );
//    return apply_filters( 'wptoolset_fieldform_edit', $html, $config, $form_id );
//}

function wptoolset_form_validate_field( $form_id, $config, $value ) {
    global $wptoolset_forms;
    return $wptoolset_forms->validate_field( $form_id, $config, $value );
}

function wptoolset_form_conditional_check( $config, $values ) {
    global $wptoolset_forms;
    return $wptoolset_forms->conditional( $config, $values );
}