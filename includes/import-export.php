<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-08-29
 * Time: 8:56 AM
 */

/**
 * import contacts with a CSV.
 */
function wpgh_import_contacts()
{
    //todo security check...
    if ( ! isset( $_POST[ 'import_contacts' ] ))
            return;

    if ( ! current_user_can( 'gh_manage_contacts' ) ){
        wp_die( 'You do not have permission to do that.' );
    }

    if ( ! isset( $_FILES['contacts'] ) ){
        wp_die( 'No contacts supplied!' );
    }

    if ( isset(  $_POST[ 'import_tags' ] ) ){
        $tags = wpgh_validate_tags( $_POST[ 'import_tags' ] );
    }

    if ( $_FILES['contacts']['error'] == UPLOAD_ERR_OK && is_uploaded_file( $_FILES['contacts']['tmp_name'] ) ) {

        if ( strpos( $_FILES['contacts']['name'], '.csv' ) === false ){
            wp_die( 'You did not upload a csv!' );
        }

        $row = 0;
        if ( ( $handle = fopen( $_FILES['contacts']['tmp_name'], "r" ) ) !== FALSE ) {

            $columns = fgetcsv( $handle, 1000, "," );

            $first_index = array_search( 'first_name', $columns );
            $last_index  = array_search( 'last_name', $columns );
            $email_index = array_search( 'email', $columns );

            $row++;

            while ( ( $data = fgetcsv( $handle, 1000, "," ) ) !== FALSE ) {

                $first_name = $data[ $first_index ];
                $last_name  = $data[ $last_index ];
                $email      = $data[ $email_index ];

                $cid = wpgh_quick_add_contact( $email, $first_name, $last_name );

                if ( ! $cid )
                    continue;

                unset( $data[ $first_index ] );
                unset( $data[ $last_index ] );
                unset( $data[ $email_index ] );

                foreach ( $data as $i => $attr ){
                    $meta_key = sanitize_key( $columns[$i] );
                    wpgh_update_contact_meta( $cid, $meta_key, sanitize_text_field( $attr ) );
                }

                foreach ( $tags as $tag_id )
                {
                    wpgh_apply_tag( $cid, $tag_id );
                }

                $row++;
            }

            fclose($handle);

            add_settings_error( 'import', esc_attr( 'imported' ), __( 'Imported Contacts' ), 'updated' );
        } else {
            wp_die('Oops, something went wrong.');
        }
    } else {
        wp_die( 'Please upload a proper file!' );
    }
}

add_action( 'gh_settings_tools', 'wpgh_import_contacts' );

/**
 * import contacts with a CSV.
 */
function wpgh_export_contacts()
{
    if ( ! isset( $_POST[ 'export_contacts' ] ) )
        return;




}