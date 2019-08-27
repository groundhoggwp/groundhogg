<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 10/29/2018
 * Time: 11:44 AM
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class wpgh_api_v1_contacts extends WPGH_API_V1
{

    public function update($data)
    {
        // use to update a contact details

        if (!$this->user->has_cap('edit_contacts')) {
            return new WP_Error('INVALID_PERMISSIONS', __(WPGH()->roles->error('edit_contacts')));

        } else {

            if (isset ($data->contact_id)) {// check user enter contact id for operation
                $contact_id = $data->contact_id;
                unset($data->contact_id);
                //$contact = WPGH()->contacts->get_contacts(array('ID' => $contact_id));
                if (WPGH()->contacts->count(array('ID' => $contact_id)) > 0) { //check id exist in databse

                    if ((isset($data->email)) && (WPGH()->contacts->exists($data->email))) {//if email already exist in database and user wants to update it..
                        unset($data->email);
                    }

                    if (isset($data->meta)) {// update meta
                        //$data_meta = $data->meta;
                        foreach ($data->meta as $key => $value) {
                            WPGH()->contact_meta->update_meta($contact_id, sanitize_key($key), sanitize_text_field($value));
                        }
                        unset($data->meta);
                    }


                    //update contact table
                    $data_array = (array)$data;

                    unset($data_array['meta']);

                    $data_array = array_map('sanitize_text_field', $data_array);

                    if (count($data_array) > 0) {
                        // update data only if there is a data
                        WPGH()->contacts->update($contact_id, $data_array);
                    }
                    //fetch and send new updated data
                    $contact = WPGH()->contacts->get_contacts(array('ID' => $contact_id));
                    if (count($contact) > 0) {
                        $contact_meta = $this->get_meta($contact_id);
                        return array(
                            'contact' => $contact,
                            'meta' => $contact_meta,
                        );
                    }

                } else {
                    return new WP_Error('INVALID_ID', __('No contact exists with the given ID.'));
                }

            } else {// response to enter contact_id
                return new WP_Error('INVALID_ID', __('Please provide a contact ID.'));
            }
        }
    }

    public function add($data)
    {// create a new user

        if (!$this->user->has_cap('edit_contacts')) {
            return new WP_Error('INVALID_PERMISSIONS', __(WPGH()->roles->error('edit_contacts')));

        } else {

            if (isset($data->email)) {
                //  ---------------  Insert operation --------

                $data_array = (array)$data;

                unset($data_array['meta']);

                $data_array = array_map('sanitize_text_field', $data_array);


                //adding data in contact table
                $contact_id = WPGH()->contacts->add($data_array);

                // insert data in contact meta table if users send meta data
                if (isset($data->meta)) {
                    //$data_meta = $data->meta;
                    foreach ($data->meta as $key => $value) {
                        WPGH()->contact_meta->add_meta($contact_id, $key, $value);
                    }
                }

                //--------------- Fetch and Display operation

                $contact = WPGH()->contacts->get_contacts(array('ID' => $contact_id));
                if (count($contact) > 0) {
                    $contact_meta = $this->get_meta($contact_id);
                    return array(
                        'contact' => $contact,
                        'meta' => $contact_meta
                    );
                }

            } else {
                return array('message' => $data->email . 'please enter user email.');
            }

        }
    }

    public function get($data)
    {//get contact using contact Id
        // 1. to get all the contact pass all
        // 2. to get specific contact passs contact specified id

        return array('message' => 'get');

            if (isset($data->contact_id)) {//check contact id is set or not
                $contact_id = $data->contact_id;
                if ($contact_id == "all") {//return all the contacts
                    $contacts = WPGH()->contacts->get_contacts();
                    return $contacts;
                } else {
                    //
                    if (WPGH()->contacts->count(array('ID' => $contact_id)) > 0) {
                        //Featchig contact meta and adding in contact
                        $contact = WPGH()->contacts->get_contacts(array('ID' => $contact_id));
                        $contact_meta = $this->get_meta($contact_id);
                        return array(
                            'contact' => $contact,
                            'meta' => $contact_meta
                        );
                    } else {
                        return array('message' => 'No contact found with contact_id ' . $contact_id);
                    }
                }
                return $contact_id;
            } else {
                return array('message' => 'please enter contact_id to request.');
            }


    }

    public function get_meta($contact_id)
    {
        $contact_meta = WPGH()->contact_meta->get_meta($contact_id);
        foreach ($contact_meta as $meta_key => $value) {
            $contact_meta[$meta_key] = array_pop($value);
        }
        return $contact_meta;
    }

    public function delete($data)
    {// function invoked if user wants to delete one contact

        if (!$this->user->has_cap('edit_contacts')) {
            return new WP_Error('INVALID_PERMISSIONS', __(WPGH()->roles->error('edit_contacts')));

        } else {
            if (isset($data->contact_id)) {
                $contact_id = $data->contact_id;
                // ----------- code to delete contact

                if (WPGH()->contacts->count(array('ID' => $contact_id)) > 0) {
                    if (WPGH()->contacts->delete(array('ID' => $contact_id))) {
                        return array('message' => 'contact deleted successfully.');
                    } else {
                        return array('message' => 'Contact failed to delete.');
                    }
                }
            } else {

                return array('message' => 'please enter contact_id');
            }
        }

    }
}