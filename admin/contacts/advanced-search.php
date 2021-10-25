<?php

namespace Groundhogg\Admin\Contacts;

use Groundhogg\Preferences;
use Groundhogg\Saved_Searches;
use function Groundhogg\action_input;
use function Groundhogg\get_db;
use function Groundhogg\get_url_var;
use function Groundhogg\html;
use function Groundhogg\isset_not_empty;

$string_comparisons = [
	''            => __( 'Compare', 'groundhogg' ),
	'equals'      => __( 'Equals', 'groundhogg' ),
	'contains'    => __( 'Contains', 'groundhogg' ),
	'starts_with' => __( 'Starts with', 'groundhogg' ),
	'ends_with'   => __( 'Ends with', 'groundhogg' ),
	'empty'       => __( 'Empty', 'groundhogg' ),
	'not_empty'   => __( 'Not empty', 'groundhogg' ),
	'regex'       => __( 'Regex', 'groundhogg' ),
];

$search_id    = get_url_var( 'saved_search_id' );
$saved_search = $search_id ? Saved_Searches::instance()->get( $search_id ) : false;

?>
<div id="search-filters" class="postbox <?php echo ( get_url_var( 'is_searching' ) ) ? '' : 'hidden'; ?>">
	<form method="get">
		<?php echo html()->input( [
			'type'  => 'hidden',
			'name'  => 'is_searching',
			'value' => 'on',
		] ); ?>
		<?php html()->hidden_GET_inputs(); ?>

		<div class="first-name-search inline-block search-param">

			<?php

			echo html()->e( 'label', [ 'class' => 'search-label' ], __( 'Filter By First Name', 'groundhogg' ) );
			?>
			<p><?php

				echo html()->dropdown( [
					'name'        => 'first_name_compare',
					'class'       => 'first-name-compare',
					'options'     => $string_comparisons,
					'selected'    => sanitize_text_field( get_url_var( 'first_name_compare' ) ),
					'option_none' => false,
				] );
				?></p>
			<p><?php

				echo html()->input( [
					'name'        => 'first_name',
					'value'       => sanitize_text_field( get_url_var( 'first_name' ) ),
					'class'       => 'input first-name',
					'placeholder' => __( 'John' )
				] );
				?></p>
		</div>
		<div class="last-name-search inline-block search-param">

			<?php

			echo html()->e( 'label', [ 'class' => 'search-label' ], __( 'Filter By Last Name', 'groundhogg' ) );
			?>
			<p><?php

				echo html()->dropdown( [
					'name'        => 'last_name_compare',
					'class'       => 'last-name-compare',
					'options'     => $string_comparisons,
					'selected'    => sanitize_text_field( get_url_var( 'last_name_compare' ) ),
					'option_none' => false,
				] );
				?></p>
			<p><?php


				echo html()->input( [
					'name'        => 'last_name',
					'value'       => sanitize_text_field( get_url_var( 'last_name' ) ),
					'class'       => 'input last-name',
					'placeholder' => __( 'Doe' )
				] );
				?></p>
		</div>
		<div class="email-search inline-block search-param">
			<?php
			echo html()->e( 'label', [ 'class' => 'search-label' ], __( 'Filter By Email', 'groundhogg' ) );
			?>
			<p><?php
				echo html()->dropdown( [
					'name'        => 'email_compare',
					'class'       => 'email-compare',
					'options'     => $string_comparisons,
					'selected'    => sanitize_text_field( get_url_var( 'email_compare' ) ),
					'option_none' => false,
				] );
				?></p>
			<p><?php

				echo html()->input( [
					'name'        => 'email',
					'value'       => sanitize_text_field( get_url_var( 'email' ) ),
					'class'       => 'input email',
					'placeholder' => __( 'example@mydomain.com' )
				] );
				?></p>
			<p>
		</div>
		<div class="optin-status inline-block search-param">

			<?php

			echo html()->e( 'label', [ 'class' => 'search-label' ], __( 'Filter By Optin Status', 'groundhogg' ) );
			echo "&nbsp;";

			echo html()->wrap( html()->select2( [
				'name'     => 'optin_status[]',
				'id'       => 'optin_status_advanced',
				'class'    => 'gh-select2',
				'options'  => [
					Preferences::UNCONFIRMED  => __( 'Unconfirmed', 'groundhogg' ),
					Preferences::CONFIRMED    => __( 'Confirmed', 'groundhogg' ),
					Preferences::UNSUBSCRIBED => __( 'Unsubscribed', 'groundhogg' ),
//					4 => __( 'Weekly', 'groundhogg' ),
//					5 => __( 'Monthly', 'groundhogg' ),
					Preferences::HARD_BOUNCE  => __( 'Bounced', 'groundhogg' ),
					Preferences::SPAM         => __( 'Spam', 'groundhogg' ),
					Preferences::COMPLAINED   => __( 'Complained', 'groundhogg' ),
				],
				'multiple' => true,
				'selected' => Preferences::sanitize( get_url_var( 'optin_status' ) ),
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

				$meta_compare = get_url_var( 'meta_compare' );

				if ( $meta_compare ) {
					$map = [
						'>'  => 'gt',
						'>=' => 'gt_eq',
						'<'  => 'lt',
						'<=' => 'lt_eq',
					];

					if ( isset_not_empty( $map, $meta_compare ) ) {
						$meta_compare = $map[ $meta_compare ];
					}
				}

				echo html()->dropdown( [
					'name'        => 'meta_compare',
					'class'       => 'meta-compare',
					'options'     => [
						'='          => __( 'Equals', 'groundhogg' ),
						'!='         => __( 'Not Equals', 'groundhogg' ),
						'gt'         => __( 'Greater than', 'groundhogg' ),
						'lt'         => __( 'Less than', 'groundhogg' ),
						'gt_eq'      => __( 'Greater than or equal to', 'groundhogg' ),
						'lt_eq'      => __( 'Less than or equal to', 'groundhogg' ),
						'REGEXP'     => __( 'Contains', 'groundhogg' ),
						'NOT REGEXP' => __( 'Does not contain', 'groundhogg' ),
						'EXISTS'     => __( 'Exists', 'groundhogg' ),
						'NOT EXISTS' => __( 'Not Exists', 'groundhogg' ),
					],
					'selected'    => $meta_compare,
					'option_none' => false,
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
		</div>
		<div class="tags-filter search-param">
			<?php echo html()->e( 'label', [ 'class' => 'search-label' ], __( 'Filter By Tags', 'groundhogg' ) ); ?>
			<div class="filters">
				<div class="tags-include inline-block search-param">
					<?php

					echo html()->e( 'label', [ 'class' => 'search-label' ], __( 'Includes contacts with', 'groundhogg' ) );
					echo "&nbsp;";
					echo html()->dropdown( [
						'name'        => 'tags_include_needs_all',
						'id'          => 'tags_include_needs_all_advanced',
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
							'id'       => 'tags_include_advanced',
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
						'id'          => 'tags_exclude_needs_all_advanced',
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
							'id'       => 'tags_exclude_advanced',
							'selected' => wp_parse_id_list( get_url_var( 'tags_exclude' ) )
						] )
					] );

					?>
				</div>
			</div>
		</div>
		<?php do_action( 'groundhogg/admin/contacts/search' ); ?>

		<div class="start-search">
			<?php submit_button( __( 'Search' ), 'primary', 'search-advanced', false ); ?>
		</div>
	</form>
	<div class="saved-search-form">
		<div class="inline-block search-param">
			<form method="post">
				<?php

				wp_nonce_field( 'load_search' );
				action_input( 'load_search' );

				echo html()->e( 'label', [ 'class' => 'search-label' ], __( 'Saved Searches', 'groundhogg' ) );

				?><p><?php

					echo html()->dropdown( [
						'name'     => 'saved_search',
						'class'    => 'saved-search',
						'options'  => Saved_Searches::instance()->get_for_select(),
						'selected' => get_url_var( 'saved_search_id' ),
					] );

					?></p>
				<?php submit_button( __( 'Load Search', 'groundhogg' ), 'secondary', 'load-search', false ); ?>
			</form>
		</div>

		<?php if ( get_url_var( 'is_searching' ) === 'on' && ! $saved_search ): ?>
			<div class="inline-block search-param">
				<form method="post" class="save-this-search">
					<?php

					wp_nonce_field( 'save_this_search' );
					action_input( 'save_this_search' );

					echo html()->e( 'label', [ 'class' => 'search-label' ], __( 'Save This Search', 'groundhogg' ) );

					?><p><?php

						echo html()->input( [
							'name'        => 'saved_search_name',
							'placeholder' => __( 'My search name...', 'groundhogg' ),
						] );

						?></p>
					<?php submit_button( __( 'Save Search', 'groundhogg' ), 'secondary', 'save-search', false ); ?>
				</form>
			</div>
		<?php else: ?>
			<div class="inline-block search-param">
				<form method="post" class="save-this-search">
					<?php

					wp_nonce_field( 'update_this_search' );
					action_input( 'update_this_search' );

					echo html()->e( 'label', [ 'class' => 'search-label' ], __( 'Update This Search', 'groundhogg' ) );
					?>
					<p>
						<?php html()->button( [
							'type'  => 'submit',
							'text'  => __( 'Update Search', 'groundhogg' ),
							'name'  => 'update_search',
							'id'    => 'update_search',
							'value' => 'update_search',
						], true ); ?>
					</p>
				</form>
			</div>
		<?php endif; ?>
		<?php
		if ( $saved_search ): ?>
			<div class="inline-block search-param">
				<form method="post" class="delete-search">
					<?php

					wp_nonce_field( 'delete_search' );
					action_input( 'delete_search' );

					echo html()->input( [
						'type'  => 'hidden',
						'name'  => 'saved_search',
						'value' => $search_id
					] );

					echo html()->e( 'label', [ 'class' => 'search-label' ], __( 'Delete Current Search', 'groundhogg' ) ); ?>
					<p>
						<?php html()->button( [
							'type'  => 'submit',
							'text'  => __( 'Delete Search', 'groundhogg' ),
							'name'  => 'delete_search',
							'id'    => 'delete_search',
							'class' => 'button danger',
							'value' => 'delete_search',
						], true ); ?>
					</p>
				</form>
			</div>
		<?php endif; ?>
	</div>
</div>
