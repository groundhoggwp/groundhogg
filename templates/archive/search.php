<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use function Groundhogg\get_url_var;
use function Groundhogg\html;

?>
<form method="get" class="list-search">
	<?php

    html( [
	    html()->input( [
		    'type'        => 'search',
		    'name'        => 'filter',
		    'id'          => 'search-input',
		    'value'       => sanitize_text_field( get_url_var( 'filter' ) ),
	    ] ),
	    html()->button( [
		    'class' => 'button',
		    'type'  => 'submit',
		    'id'    => 'search-submit',
		    'text' => __( 'Search', 'groundhogg' )
	    ] )
    ] );

	?>
</form>
