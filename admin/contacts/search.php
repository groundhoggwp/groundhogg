<?php
namespace Groundhogg\Admin\Contacts;

use Groundhogg\Plugin;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-10-29
 * Time: 4:54 PM
 */

/**
 * Displays a comparison input field.
 *
 * @param $section
 * @param $table
 */
function gscmp( $section, $table = 'meta' ){
    echo Plugin::$instance->utils->html->dropdown( array(
        'name'              => sprintf( '%s[%s][comp]', $table, $section ),
        'id'                => sprintf( '%s__%s__comp', $table, $section ),
        'class'             => '',
        'options'           => array(
            '='             => __( 'Equals', 'groundhogg' ),
            '!='            => __( 'Not Equals', 'groundhogg' ),
            'LIKE sw'       => __( 'Starts With', 'groundhogg' ),
            'LIKE ew'       => __( 'Ends With', 'groundhogg' ),
            'LIKE c'        => __( 'Contains', 'groundhogg' ),
            'NOT LIKE c'    => __( 'Does Not Contain', 'groundhogg' ),
            'EMPTY'         => __( 'Is Empty', 'groundhogg' ),
            'NOT EMPTY'     => __( 'Is Filled', 'groundhogg' ),
        ),
        'selected'          => '=',
        'multiple'          => false,
        'option_none'       => 'Please Select One',
        'attributes'        => '',
        'option_none_value' => '',
    ) );
}

/**
 * Displays a search field
 *
 * @param $section
 * @param $table
 */
function gsfld( $section, $table = 'meta' ){
    echo Plugin::$instance->utils->html->input(array(
        'type'  => 'text',
        'name'  => sprintf( '%s[%s][search]', $table, $section ),
        'id'    => sprintf( '%s__%s__search', $table, $section ),
        'class' => 'input',
        'value' => '',
        'attributes' => '',
        'placeholder' => ''
    ));
}

?>
<style>
    th, td {
        padding: 7px;
        text-align: left;
    }

    th {
        font-weight: 500;
    }

    .third{
        width: 33%;
        float: left;
    }

    .third table {
        width: 100%;
    }

    td select {
        max-width: 100%;
    }

    .postbox .alignleft h2.section-header {
        font-weight: 500;
        font-size: 16px;
        padding-left: 7px;
    }
</style>
<form action="" method="post" class="">
    <?php wp_nonce_field(); ?>
    <div class="postbox" style="margin-top: 30px;">
        <div class="inside">

            <?php $general = array(
                'first_name'        => __( 'First Name', 'groundhogg' ),
                'last_name'         => __( 'Last Name', 'groundhogg' ),
                'email'             => __( 'Email', 'groundhogg' ),
            );

            $general_meta = array(
                'primary_phone'     => __( 'Phone', 'groundhogg' ),
                'company_name'      => __( 'Company Name', 'groundhogg' ),
                'job_title'         => __( 'Job Title', 'groundhogg' ),
                'company_address'   => __( 'Company Address', 'groundhogg' ),
                'street_address_1'  => __( 'Street Address 1', 'groundhogg' ),
                'street_address_2'  => __( 'Street Address 2', 'groundhogg' ),
                'city'              => __( 'City', 'groundhogg' ),
                'postal_zip'        => __( 'Postal/Zip Code', 'groundhogg' ),
                'region'            => __( 'State/Province', 'groundhogg' ),
                'country'           => __( 'Country', 'groundhogg' ),
            ); ?>

            <!-- Name, Contact Information, Company Info, Address -->
            <div class="alignleft">
                <h2 class="section-header"><?php _e( 'General Information', 'groundhogg' ); ?></h2>
                <table>
                    <?php foreach ( $general as $section => $title ): ?>
                        <tr>
                            <th><?php echo $title; ?></th>
                            <td><?php gscmp( $section, 'c' ); ?></td>
                            <td><?php gsfld( $section, 'c' ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php foreach ( $general_meta as $section => $title ): ?>
                        <tr>
                            <th><?php echo $title; ?></th>
                            <td><?php gscmp( $section ); ?></td>
                            <td><?php gsfld( $section ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <!-- Segmentation, Tags, Lead Source, source page-->
            <div class="alignleft">
                <h2 class="section-header"><?php _e( 'Segmentation', 'groundhogg' ); ?></h2>
                <table>
                    <tr>
                        <th><?php _e( 'Owner', 'groundhogg' ); ?></th>
                        <td><?php gscmp( 'owner', 'c' ); ?></td>
                        <td><?php echo Plugin::$instance->utils->html->dropdown_owners( array(
                                'name'              => sprintf( 'c[%s][comp]', 'owner' ),
                                'id'                => sprintf( 'c__%s__comp', 'owner' ),
                            ) ) ?></td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Source Page', 'groundhogg' ); ?></th>
                        <td><?php gscmp( 'source_page' ); ?></td>
                        <td><?php gsfld( 'source_page' ); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Lead Source', 'groundhogg' ); ?></th>
                        <td><?php gscmp( 'lead_source' ); ?></td>
                        <td><?php gsfld( 'lead_source' ); ?></td>
                    </tr>
                    <tr>
                        <th colspan="1">
                            <?php _e( 'Tags' ); ?>
                        </th>
                        <td colspan="2" ><?php echo Plugin::$instance->utils->html->dropdown( array(
                                'name'              => sprintf( 'tags[%s][comp]', 'tags_1' ),
                                'id'                => sprintf( 'tags__%s__comp', 'tags_1' ),
                                'class'             => '',
                                'options'           => array(
                                    'has_all'       => __( 'Has ALL of these tags', 'groundhogg' ),
                                    'has_any'       => __( 'Has ANY of these tags', 'groundhogg' ),
                                    'not_has_all'   => __( 'Does not have ALL of these tags', 'groundhogg' ),
                                    'not_has_any'   => __( 'Does not have ANY of these tags', 'groundhogg' ),
                                ),
                                'selected'          => 'has_any',
                            ) )?></td>

                    </tr>
                    <tr>
                        <td colspan="3"><?php echo Plugin::$instance->utils->html->tag_picker(array(
                                'name'              => sprintf( 'tags[%s][tags][]', 'tags_1' ),
                                'id'                => sprintf( 'tags__%s__tags', 'tags_1' ),
                            )); ?></td>
                    </tr>
                    <tr>
                        <th colspan="1">
                            <?php _e( 'And' ); ?>
                        </th>
                        <td colspan="2" ><?php echo Plugin::$instance->utils->html->dropdown( array(
                                'name'              => sprintf( 'tags[%s][comp]', 'tags_2' ),
                                'id'                => sprintf( 'tags__%s__comp', 'tags_2' ),
                                'class'             => '',
                                'options'           => array(
                                    'has_all'       => __( 'Has ALL of these tags', 'groundhogg' ),
                                    'has_any'       => __( 'Has ANY of these tags', 'groundhogg' ),
                                    'not_has_all'   => __( 'Does not have ALL of these tags', 'groundhogg' ),
                                    'not_has_any'   => __( 'Does not have ANY of these tags', 'groundhogg' ),
                                ),
                                'selected'          => 'has_any',
                            ) )?></td>

                    </tr>
                    <tr>
                        <td colspan="3"><?php echo Plugin::$instance->utils->html->tag_picker(array(
                                'name'              => sprintf( 'tags[%s][tags][]', 'tags_2' ),
                                'id'                => sprintf( 'tags__%s__tags', 'tags_2' ),
                            )); ?></td>
                    </tr>
                </table>
            </div>

            <!-- META -->
            <div class="alignleft">
                <h2 class="section-header"><?php _e( 'Custom Data (Meta)', 'groundhogg' ); ?></h2>
                <table>

                    <?php for( $i=1; $i<6; $i++ ): ?>

                    <tr>
                        <td><?php echo Plugin::$instance->utils->html->meta_key_picker( array(
                                'name' => sprintf( 'c_meta[%s][key]', 'meta_' . $i ),
                                'id'   => sprintf( 'c_meta__%s__key', 'meta_' . $i ),
                            ) ); ?></td>
                        <td><?php gscmp( 'meta_' . $i, 'c_meta' ); ?></td>
                        <td><?php gsfld( 'meta_' . $i, 'c_meta' ); ?></td>
                    </tr>

                    <?php endfor; ?>
                </table>
            </div>

            <div class="wp-clearfix"></div>
        </div>
    </div>

    <?php submit_button( 'Search' ); ?>
</form>
