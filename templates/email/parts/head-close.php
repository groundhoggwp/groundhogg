<?php

use function Groundhogg\is_browser_view;
use function Groundhogg\managed_page_url;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $campaign;
global $broadcast;
global $event;

if ( is_browser_view() && ( ( isset( $campaign ) && isset( $broadcast ) ) || isset( $event ) ) ) {
	?>
    <style id="archive">
        <?php load_css( 'archive' ); ?>
    </style>
	<?php
}

if ( is_browser_view() ){
    ?><style>
        .hide-in-browser{
            display: none;
            visibility: hidden;
        }
    </style><?php
}
