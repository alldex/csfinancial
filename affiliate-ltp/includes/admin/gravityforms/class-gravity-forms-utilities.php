<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\GravityForms;

/**
 * Description of class-gravity-forms-utilities
 *
 * @author snielson
 */
class Gravity_Forms_Utilities {
    
    /**
     * Get field value by entry ID and field type
     *
     * @since 1.0
     */
    public static function get_form_field_value($form, $entry, $field_type = '') {

        if (!( $form || $entry || $field_type )) {
            error_log("get_form_field_value called without providing form, entry_id, or field_type");
            return;
        }

        switch ($field_type) {

            case 'product':
                $ids = affwp_afgf_get_product_field_ids();
                $product = array();

                if ($ids) {
                    foreach ($ids as $id) {
                        $product[] = $entry[$id];
                    }
                }

                $value = implode(', ', array_filter($product));

                break;

            default:
                $value = isset($entry[self::get_form_field_id($form, $field_type)]) ? $entry[self::get_form_field_id($form, $field_type)] : '';
                break;
        }


        if ($value) {
            return $value;
        }

        return false;
    }

    public static function get_form_field_id($form, $field_type = '') {

        if (!($form || $field_type )) {
            return;
        }

        // get form fields
        $fields = $form['fields'];

        if ($fields) {

            foreach ($fields as $field) {


                if (isset($field['type']) && $field_type == $field['type']) {

                    $field_id = $field['id'];

                    break;
                }
            }
        }

        if (!empty($field_id)) {
            return $field_id;
        }

        return false;
    }

    /**
     * Get name field ID
     *
     * @since 1.0
     */
    public static function form_get_name_field_ids($form) {
        // get form fields
        $fields = $form['fields'];

        $name = array();

        if ($fields) {
            foreach ($fields as $field) {

                if (isset($field['type']) && $field['type'] == 'name') {

                    $name[] = isset($field['inputs'][0]['id']) ? $field['inputs'][0]['id'] : '';
                    $name[] = isset($field['inputs'][1]['id']) ? $field['inputs'][1]['id'] : '';
                    $name[] = isset($field['inputs'][2]['id']) ? $field['inputs'][2]['id'] : '';
                    $name[] = isset($field['inputs'][3]['id']) ? $field['inputs'][3]['id'] : '';

                    break;
                }
            }
        }

        if (!empty($name)) {
            return $name;
        }

        return false;
    }
}
