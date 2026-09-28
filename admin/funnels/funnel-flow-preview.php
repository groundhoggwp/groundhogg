<?php
/**
 * @var $funnel Funnel
 */

use Groundhogg\Funnel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

// drawn by flow-canvas.js, the same as the flow editor
wp_add_inline_script( 'groundhogg-admin-funnel-editor', 'Groundhogg.drawFlow( Funnel, { reporting: true } )' );

?>
<div id="table_funnel_stats">
    <div id="step-flow">
        <script>let Funnel = <?php echo wp_json_encode( array_merge( $funnel->get_as_array(), [
				'canvas' => $funnel->get_canvas_data(),
			] ) ) ?></script>
        <div class="fixed-inside" style="position: relative">
            <div id="step-sortable" class="step-branch"
                 data-branch="main" style="padding: 24px 0"
            ></div>
        </div>
    </div>
</div>
