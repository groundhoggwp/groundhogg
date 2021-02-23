<?php

namespace Groundhogg\Admin\Contacts;

use Groundhogg\Saved_Searches;
use function Groundhogg\action_input;
use function Groundhogg\get_db;
use function Groundhogg\get_url_var;
use function Groundhogg\html;

?>
<div class="wp-clearfix"></div>
<form method="get">
	<?php html()->hidden_GET_inputs( true ); ?>
    <div id="quick-search" class="postbox">
        <div class="left">
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
            <span>
                <?php

                printf( "Owner is %s", html()->dropdown_owners( [
					'name'     => 'owner',
					'class'    => 'owner',
					'selected' => absint( get_url_var( 'owner' ) ),
				] ) );

                ?>
            </span>
        </div>
        <div class="right">
			<?php
			echo html()->input( [
				'name'        => 's',
				'placeholder' => 'Search contacts',
				'class'       => 'input',
				'value'       => sanitize_text_field( get_url_var( 's' ) )
			] ) ?>
			<?php echo html()->submit( [
				'text' => 'Search'
			] ) ?>
        </div>
    </div>
</form>
