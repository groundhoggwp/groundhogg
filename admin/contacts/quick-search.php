<?php

namespace Groundhogg\Admin\Contacts;

use Groundhogg\Saved_Searches;
use function Groundhogg\action_input;
use function Groundhogg\get_db;
use function Groundhogg\get_url_var;
use function Groundhogg\html;

?>
<div class="wp-clearfix"></div>
<div id="quick-search" class="postbox">
	<div class="left">
		<form method="post">
			<?php

			wp_nonce_field( 'load_search' );
			action_input( 'load_search' );

			echo html()->dropdown( [
				'name'        => 'saved_search',
				'class'       => 'saved-search',
				'options'     => Saved_Searches::instance()->get_for_select(),
				'selected'    => get_url_var( 'saved_search_id' ),
				'option_none' => __( 'Select a saved search', 'groundhogg' )
			] );

			html()->submit( [ 'text'  => __( 'Load Search', 'groundhogg' ),
			                  'class' => 'button button-secondary'
			], true )
			?>
		</form>
	</div>
	<div class="right">
		<form method="get">
			<?php html()->hidden_GET_inputs( true ); ?>
			<div class="tag-quick-search-wrap">
				<?php

				$includes = html()->dropdown( [
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

				$tags = html()->e( 'div', [
					'class' => 'small-select-wrap'
				], html()->tag_picker( [
					'name'     => 'tags_include[]',
					'id'       => 'tags_include',
					'selected' => wp_parse_id_list( get_url_var( 'tags_include' ) )
				] ) );

				printf( __( "Has %s of these tags %s", 'groundhogg' ), $includes, $tags );

				?>
			</div>
			<div class="quick-search">
				<?php
				echo html()->input( [
					'name'        => 's',
					'placeholder' => __( 'Name or Email', 'groundhogg' ),
					'class'       => 'input',
					'value'       => sanitize_text_field( get_url_var( 's' ) )
				] ) ?>
				<?php echo html()->submit( [
					'text' => 'Search'
				] ) ?>
			</div>
		</form>
	</div>
</div>
