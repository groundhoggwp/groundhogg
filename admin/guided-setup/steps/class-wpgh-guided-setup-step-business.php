<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-02-27
 * Time: 11:03 AM
 */

class WPGH_Guided_Setup_Step_Business extends WPGH_Guided_Setup_Step
{

    public function get_title()
    {
        return _x( 'Business Information', 'guided_setup', 'groundhogg' );
    }

    public function get_slug()
    {
        return 'business_info';
    }

    public function get_description()
    {
        return _x( 'Your business information is needed so your list knows who is sending them emails. All this inforamtion will appear in your email footer to comply with CAN-SPAM legislation.', 'guided_setup', 'groundhogg' );
    }

    public function get_settings()
    {
        ob_start();

        ?>
        <table class="form-table">
            <tr>
                <th><?php _ex( 'Business Name', 'setting_label', 'groundhogg' ); ?></th>
                <td><?php echo WPGH()->html->input( array(
                    'name' => 'gh_business_name',
                    'value' => wpgh_get_option(  'gh_business_name', get_bloginfo( 'name' ) ),
                    'required' => true
                    ) ); ?>
                <p class="description"><?php _ex( 'This will allow your subscribers to identify who you are.', 'guided_setup', 'groundhogg' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php _ex( 'Phone Number', 'setting_label', 'groundhogg' ); ?></th>
                <td><?php echo WPGH()->html->input( array(
                        'name' => 'gh_phone',
                        'value' => wpgh_get_option(  'gh_phone' ),
                        'required' => true
                    ) ); ?>
                </td>
            </tr>
            <tr>
                <th><?php _ex( 'Street Address 1', 'setting_label', 'groundhogg' ); ?></th>
                <td><?php echo WPGH()->html->input( array(
                        'name' => 'gh_street_address_1',
                        'value' => wpgh_get_option(  'gh_street_address_1' ),
                        'required' => true
                    ) ); ?>
                </td>
            </tr>
            <tr>
                <th><?php _ex( 'Street Address 2', 'setting_label', 'groundhogg' ); ?></th>
                <td><?php echo WPGH()->html->input( array(
                        'name' => 'gh_street_address_2',
                        'value' => wpgh_get_option(  'gh_street_address_2' ),
                        'required' => false
                    ) ); ?>
                </td>
            </tr>
            <tr>
                <th><?php _ex( 'City', 'setting_label', 'groundhogg' ); ?></th>
                <td><?php echo WPGH()->html->input( array(
                        'name' => 'gh_city',
                        'value' => wpgh_get_option(  'gh_city' ),
                        'required' => true
                    ) ); ?>
                </td>
            </tr>
            <tr>
                <th><?php _ex( 'Postal/Zip code', 'setting_label', 'groundhogg' ); ?></th>
                <td><?php echo WPGH()->html->input( array(
                        'name' => 'gh_zip_or_postal',
                        'value' => wpgh_get_option(  'gh_zip_or_postal' ),
                        'required' => true
                    ) ); ?>
                </td>
            </tr>
            <tr>
                <th><?php _ex( 'State/Province/Region', 'setting_label', 'groundhogg' ); ?></th>
                <td><?php echo WPGH()->html->input( array(
                        'name' => 'gh_region',
                        'value' => wpgh_get_option(  'gh_region' ),
                        'required' => true
                    ) ); ?>
                </td>
            </tr>
            <tr>
                <th><?php _ex( 'Country', 'setting_label', 'groundhogg' ); ?></th>
                <td><?php echo WPGH()->html->input( array(
                        'name' => 'gh_country',
                        'value' => wpgh_get_option(  'gh_country' ),
                        'required' => true
                    ) ); ?>
                </td>
            </tr>
        </table>
        <?php
        return ob_get_clean();
    }

    public function save()
    {
        $settings = [
            'gh_business_name',
            'gh_phone',
            'gh_street_address_1',
            'gh_street_address_2',
            'gh_city',
            'gh_zip_or_postal',
            'gh_region',
            'gh_country',
        ];

        foreach ( $settings as $setting ){
            if ( isset( $_POST[ $setting ] ) && ! empty( $_POST[ $setting ] ) ){
                wpgh_update_option( $setting, sanitize_text_field( $_POST[ $setting ] ) );
            }
        }

        return true;
    }

}