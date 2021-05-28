<?php

namespace Groundhogg\Admin\Contacts;

use Groundhogg\Saved_Searches;
use function Groundhogg\action_input;
use function Groundhogg\get_db;
use function Groundhogg\get_url_var;
use function Groundhogg\html;

?>
<div class="wp-clearfix"></div>
<div id="search-panel">
	<div class="filters"></div>
	<div class="contact-quick-search">
		<form method="get">
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
		</form>
	</div>
</div>
