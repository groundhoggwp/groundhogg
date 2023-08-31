<?php

use function Groundhogg\the_email;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email = the_email();

if ( $email->get_pre_header() ):
	?>
	<div style="display: none; max-height: 0; overflow: hidden;">
		<?php echo $email->get_merged_pre_header() ?>
	</div>
	<div style="display: none; max-height: 0; overflow: hidden;">
		&#847; &zwnj; &nbsp; &#8199; &shy; &#847; &zwnj; &nbsp; &#8199; &shy; &#847; &zwnj; &nbsp; &#8199; &shy; &#847; &zwnj; &nbsp; &#8199; &shy; &#847; &zwnj; &nbsp; &#8199; &shy; &#847; &zwnj; &nbsp; &#8199; &shy; &#847; &zwnj; &nbsp; &#8199; &shy; &#847; &zwnj; &nbsp; &#8199; &shy;
	</div>
<?php
endif;