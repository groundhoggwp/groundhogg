<?php

namespace Groundhogg\Admin\Contacts;

use function Groundhogg\admin_page_url;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\get_url_var;
use function Groundhogg\html;

?>
<div id="search-filters" class="postbox <?php echo ( get_url_var( 'search' ) ) ? '' : 'hidden'; ?>">
    <div class="inside">
        <form method="get">
			<?php echo html()->input( [
				'type'  => 'hidden',
				'name'  => 'search',
				'value' => 'on',
			] ); ?>
			<?php html()->hidden_GET_inputs(); ?>
            <div class="tags-include inline-block search-param">
				<?php

				echo html()->e( 'label', [ 'class' => 'search-label' ], __( 'Includes contacts with', 'groundhogg' ) );
				echo "&nbsp;";
				echo html()->dropdown( [
					'name'        => 'tags_include_needs_all',
					'id'          => 'tags_include_needs_all',
					'class'       => '',
					'options'     => array(
						0 => __( 'Any', 'groundhogg' ),
						1 => __( 'All', 'groundhogg' )
					),
					'selected'    => absint( get_url_var( 'tags_include_needs_all' ) ),
					'option_none' => false
				] );

				echo html()->e( 'p', [], [
					html()->tag_picker( [
						'name'     => 'tags_include[]',
						'id'       => 'tags_include',
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
					'name'        => 'tags_exclude_needs_all',
					'id'          => 'tags_exclude_needs_all',
					'class'       => '',
					'options'     => array(
						0 => __( 'Any', 'groundhogg' ),
						1 => __( 'All', 'groundhogg' )
					),
					'selected'    => absint( get_url_var( 'tags_exclude_needs_all' ) ),
					'option_none' => false
				] );

				echo html()->e( 'p', [], [
					html()->tag_picker( [
						'name'     => 'tags_exclude[]',
						'id'       => 'tags_exclude',
						'selected' => wp_parse_id_list( get_url_var( 'tags_exclude' ) )
					] )
				] );

				?>
            </div>
            <div class="tags-exclude inline-block search-param">

				<?php

				echo html()->e( 'label', [ 'class' => 'search-label' ], __( 'Filter By Optin Status', 'groundhogg' ) );
				echo "&nbsp;";

				echo html()->wrap( html()->select2( [
					'name'     => 'optin_status[]',
					'id'       => 'optin_status',
					'class'    => 'gh-select2',
					'options'  => [
						1 => __( 'Unconfirmed', 'groundhogg' ),
						2 => __( 'Confirmed', 'groundhogg' ),
						3 => __( 'Unsubscribed', 'groundhogg' ),
						4 => __( 'Weekly', 'groundhogg' ),
						5 => __( 'Monthly', 'groundhogg' ),
						6 => __( 'Bounced', 'groundhogg' ),
						7 => __( 'Spam', 'groundhogg' ),
						8 => __( 'Complained', 'groundhogg' ),
					],
					'multiple' => true,
					'selected' => wp_parse_id_list( get_url_var( 'optin_status' ) ),
				] ), 'p' );

				?>
            </div>
            <div class="meta-search inline-block search-param">

				<?php

				echo html()->e( 'label', [ 'class' => 'search-label' ], __( 'Filter By Meta', 'groundhogg' ) );

				$keys = get_db( 'contactmeta' )->get_keys();

				?><p><?php

					echo html()->dropdown( [
						'name'        => 'meta_key',
						'class'       => 'meta-key',
						'options'     => $keys,
						'selected'    => sanitize_key( get_url_var( 'meta_key' ) ),
						'option_none' => __( 'Select a meta key', 'groundhogg' ),
						'id'          => '',
					] );

					?></p>
                <p><?php


					echo html()->dropdown( [
						'name'        => 'meta_compare',
						'class'       => 'meta-compare',
						'options'     => [
							'='          => __( 'Equals', 'groundhogg' ),
							'!='         => __( 'Not Equals', 'groundhogg' ),
							'>'          => __( 'Greater than', 'groundhogg' ),
							'<'          => __( 'Less than', 'groundhogg' ),
							'REGEXP'     => __( 'Contains', 'groundhogg' ),
							'NOT REGEXP' => __( 'Does not contain', 'groundhogg' ),
						],
						'selected'    => sanitize_text_field( get_url_var( 'meta_compare' ) ),
						'option_none' => __( 'Comparison', 'gorundhogg' ),
						'id'          => '',
					] );
					?></p>
                <p><?php


					echo html()->input( [
						'name'        => 'meta_value',
						'value'       => sanitize_text_field( get_url_var( 'meta_value' ) ),
						'class'       => 'input meta-value',
						'placeholder' => __( 'Value' )
					] );

					?>
                </p>
            </div>
            <div class="date-search inline-block search-param">

				<?php

				echo html()->e( 'label', [ 'class' => 'search-label' ], __( 'Filter By Date', 'groundhogg' ) );

				?><p><?php
					_e( 'From: ' );

					echo '<br/>';

					echo html()->date_picker( [
						'min-date' => date( 'Y-m-d', strtotime( '-100 years' ) ),
						'name'     => 'date_after',
						'class'    => 'date-after',
						'value'    => sanitize_text_field( get_url_var( 'date_after' ) ),
					] );

					?></p>
                <p>
                <p><?php

					_e( 'To: ' );
					echo '<br/>';

					echo html()->date_picker( [
						'min-date' => date( 'Y-m-d', strtotime( '-100 years' ) ),
						'name'     => 'date_before',
						'class'    => 'date-before',
						'value'    => sanitize_text_field( get_url_var( 'date_before' ) ),
					] );

					?></p>
            </div>
            <div class="owner-search inline-block search-param">

				<?php

				echo html()->e( 'label', [ 'class' => 'search-label' ], __( 'Filter By Owner', 'groundhogg' ) );

				?><p><?php

					echo html()->dropdown_owners( [
						'name'     => 'owner',
						'class'    => 'owner',
						'selected' => absint( get_url_var( 'owner' ) ),
					] );

					?></p>
                <p>
            </div>

			<?php do_action( 'groundhogg/admin/contacts/search' ); ?>

            <div class="start-search">
				<?php submit_button( __( 'Search' ), 'primary', 'submit', false ); ?>
            </div>
        </form>
    </div>
</div>
