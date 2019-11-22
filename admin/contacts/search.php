<?php

namespace Groundhogg\Admin\Contacts;

use function Groundhogg\admin_page_url;
use function Groundhogg\get_url_var;
use function Groundhogg\html;

?>
<p></p>
<div class="postbox">
    <div class="inside">
        <form method="get">
            <?php html()->hidden_GET_inputs(); ?>
            <div class="tags-include inline-block search-param">
                <?php

                echo html()->e( 'label', [ 'class' => 'search-label' ], __( 'Includes contacts with', 'groundhogg' ) );
                echo "&nbsp;";
                echo html()->dropdown( [
                    'name' => 'tags_include_needs_all',
                    'id' => 'tags_include_needs_all',
                    'class' => '',
                    'options' => array(
                        0 => __( 'Any', 'groundhogg' ),
                        1 => __( 'All', 'groundhogg' )
                    ),
                    'selected' => absint( get_url_var( 'tags_include_needs_all' ) ),
                    'option_none' => false
                ] );

                echo html()->e( 'p', [], [
                    html()->tag_picker( [
                        'name' => 'tags_include[]',
                        'id' => 'tags_include',
                        'selected' => wp_parse_id_list( get_url_var( 'tags_include' ) )
                    ] )

                ] );

                ?>
            </div>
            <div class="tags-exclude inline-block search-param">
                <?php

                echo html()->e( 'label', [ 'class' => 'search-label' ], __( 'Excludes contacts with', 'groundhogg' ) );
                echo "&nbsp;";

                echo html()->dropdown( [
                    'name' => 'tags_exclude_needs_all',
                    'id' => 'tags_exclude_needs_all',
                    'class' => '',
                    'options' => array(
                        0 => __( 'Any', 'groundhogg' ),
                        1 => __( 'All', 'groundhogg' )
                    ),
                    'selected' => absint( get_url_var( 'tags_exclude_needs_all' ) ),
                    'option_none' => false
                ] );

                echo html()->e( 'p', [], [
                    html()->tag_picker( [
                        'name' => 'tags_exclude[]',
                        'id' => 'tags_exclude',
                        'selected' => wp_parse_id_list( get_url_var( 'tags_exclude' ) )
                    ] )
                ] );

                ?>
            </div>
            <div class="tags-exclude inline-block search-param">

                <?php

                echo html()->e( 'label', [ 'class' => 'search-label' ], __('Filter By Optin Status', 'groundhogg' ) );
                echo "&nbsp;";

                echo html()->wrap( html()->select2( [
                    'name' => 'optin_status[]',
                    'id' => 'optin_status',
                    'class' => 'gh-select2',
                    'options' => [
                        0 => __( 'Unconfirmed', 'groundhogg' ),
                        1 => __( 'Confirmed', 'groundhogg' ),
                        2 => __( 'Unsubscribed', 'groundhogg' ),
                        3 => __( 'Weekly', 'groundhogg' ),
                        4 => __( 'Monthly', 'groundhogg' ),
                        5 => __( 'Bounced', 'groundhogg' ),
                        6 => __( 'Spam', 'groundhogg' ),
                        7 => __( 'Complained', 'groundhogg' ),
                    ],
                    'multiple' => true,
                    'selected' => wp_parse_id_list( get_url_var( 'optin_status' ) ),
                ] ), 'p' );

                ?>
            </div>
            <div>
                <?php submit_button( __( 'Search' ), 'primary', 'submit', false ); ?>
            </div>
        </form>
    </div>
</div>
