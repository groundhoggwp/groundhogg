<?php

namespace Groundhogg\Admin\Contacts;

use Groundhogg\Saved_Searches;
use function Groundhogg\get_url_var;
use function Groundhogg\html;
use function Groundhogg\maybe_change_space_to_plus_in_email;

?>
<div class="wp-clearfix"></div>
<?php
if ( $saved_search = get_url_var( 'saved_search' ) ) :
	$saved_search = Saved_Searches::instance()->get( $saved_search );

	?>
    <h2 id="current-search"><?php printf( __( '<span style="font-weight: 400">Current search</span>: <span id="search-name">%s</span>' ), $saved_search['name'] ); ?></h2>
<?php endif; ?>
<div id="search-panel">
    <div class="filters"></div>
    <div class="contact-quick-search">
        <form method="get">
			<?php

			html()->hidden_GET_inputs();

			?>
            <div class="gh-input-group">
				<?php
				echo html()->input( [
					'name'        => 's',
					'placeholder' => __( 'Name or Email', 'groundhogg' ),
					'class'       => 'input',
					'value'       => maybe_change_space_to_plus_in_email( sanitize_text_field( get_url_var( 's' ) ) )
				] );

				echo html()->submit( [
					'text' => 'Search',
                    'class' => 'gh-button primary small'
				] );

				?>
            </div>
        </form>
    </div>
</div>
