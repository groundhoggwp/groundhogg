<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-02-27
 * Time: 11:03 AM
 */

class WPGH_Guided_Setup_Step_Compliance extends WPGH_Guided_Setup_Step
{

    public function get_title()
    {
        return _x( 'Compliance Information', 'guided_setup', 'groundhogg' );
    }

    public function get_slug()
    {
        return 'compliance_info';
    }

    public function get_description()
    {
        return _x( 'These features help you stay within the letter of the law and make it easy for your subscribers to access critical information about your policies.', 'guided_setup', 'groundhogg' );
    }

    public function get_settings()
    {

        $pages = get_posts( array(
            'numberposts'   => -1,
            'category'      => 0,
            'orderby'       => 'post_title',
            'order'         => 'ASC',
            'include'       => array(),
            'exclude'       => array(),
            'meta_key'      => '',
            'meta_value'    => '',
            'post_type'     => 'page',
            'suppress_filters' => true
        ) );

        $pops = array();

        if ( $pages ){
            foreach ( $pages as $page ){
                $pops[ $page->ID ] = $page->post_title;
            }
        }

        ob_start();
        ?>
        <table class="form-table">
            <tr>
                <th><?php _ex( 'Privacy Policy', 'setting_label', 'groundhogg' ); ?></th>
                <td><?php echo WPGH()->html->select2( array(
                        'name' => 'gh_privacy_policy',
                        'selected' => wpgh_get_option( 'gh_privacy_policy', get_option( 'wp_page_for_privacy_policy' ) ),
                        'data' => $pops,
                    ) ); ?>
                    <p class="description"><?php _ex( 'This will be linked in the footer of your emails. It is important to let subscribers know how you plan to use their information.', 'guided_setup', 'groundhogg' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php _ex( 'Terms & Conditions', 'setting_label', 'groundhogg' ); ?></th>
                <td><?php echo WPGH()->html->select2( array(
                        'name' => 'gh_terms',
                        'selected' => [ wpgh_get_option(  'gh_terms', get_option( 'wp_page_for_privacy_policy' ) ) ],
                        'data' => $pops,
                    ) ); ?>
                    <p class="description"><?php _ex( 'Terms & conditions tell users about what they can expect from using your site.', 'guided_setup', 'groundhogg' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php _ex( 'I send email to subscribers in...', 'setting_label', 'groundhogg' ); ?></th>
                <td>
                    <?php

                    echo WPGH()->html->checkbox( array(
                        'name' => 'in_europe',
                        'label' => _x( 'Europe', 'guided_setup', 'groundhogg' ),
                        'value' => 1,
                        'checked' => wpgh_is_option_enabled( 'gh_enable_gdpr' )
                    ) );
                    ?>&nbsp;&nbsp;<?php
                    echo WPGH()->html->checkbox( array(
                        'name' => 'in_canada',
                        'label' => _x( 'Canada', 'guided_setup', 'groundhogg' ),
                        'value' => 1,
                        'checked' => wpgh_is_option_enabled( 'gh_strict_confirmation' )
                    ) );
                    ?>&nbsp;&nbsp;<?php
                    echo WPGH()->html->checkbox( array(
                        'name' => 'other',
                        'label' => _x( 'Other', 'guided_setup', 'groundhogg' ),
                        'value' => 1,
                        'checked' => true,
                    ) );
                    ?>
                    <p class="description"><?php _ex( 'If you send email in these areas special settings will be enabled to keep your business within the law.', 'guided_setup', 'groundhogg' ); ?></p>
                </td>
            </tr>
        </table>
        <?php
        return ob_get_clean();
    }

    public function save()
    {

        if ( isset( $_POST[ 'gh_privacy_policy' ] ) && ! empty( $_POST[ 'gh_privacy_policy' ] ) ){
            wpgh_update_option( 'gh_privacy_policy', intval( $_POST[ 'gh_privacy_policy' ] ) );
        }

        if ( isset( $_POST[ 'gh_terms' ] ) && ! empty( $_POST[ 'gh_terms' ] ) ){
            wpgh_update_option( 'gh_terms', intval( $_POST[ 'gh_terms' ] ) );
        }

        if ( isset( $_POST[ 'in_canada' ]  ) ){
            wpgh_update_option( 'gh_strict_confirmation', array( 'on' ) );
            wpgh_update_option( 'gh_confirmation_grace_period', 14 );
        }

        if ( isset( $_POST[ 'in_europe' ]  ) ){
            wpgh_update_option( 'gh_enable_gdpr', array( 'on' ) );
        }

        return true;
    }

}