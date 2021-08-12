<?php

namespace Groundhogg\Admin\Contacts;

use Groundhogg\Saved_Searches;
use function Groundhogg\action_input;
use function Groundhogg\get_db;
use function Groundhogg\get_url_var;
use function Groundhogg\html;

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

			echo html()->input( [
				'name'        => 's',
				'placeholder' => __( 'Name or Email', 'groundhogg' ),
				'class'       => 'input',
				'value'       => sanitize_text_field( get_url_var( 's' ) )
			] ) ?>
			<?php echo html()->submit( [
				'text' => 'Search'
			] ) ?>
		</form>
	</div>
</div>
