<?php

use function Groundhogg\kses_e;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Lead scoring report
 */

?>
<div class="gh-panel span-6">
    <div class="gh-panel-header">
        <h2 class="title"><?php esc_html_e( 'Lead Score', 'groundhogg' ); ?></h2>
    </div>
	<?php if ( has_action( 'groundhogg/admin/report/lead_score' ) ) : ?>
		<?php do_action( 'groundhogg/admin/report/lead_score' ); ?>
	<?php else : ?>
        <img id="leadscore-ad" src="<?php echo GROUNDHOGG_ASSETS_URL . 'images/leadscoring-ad.png'; ?>">
        <div class="notice-no-data">
            <p><?php kses_e( __( 'Please install the <b>Lead Scoring</b> extension to view this report.', 'groundhogg' ) ); ?></p>
            <p><a href="https://www.groundhogg.io/downloads/lead-scoring/" target="_blank"
                  class="gh-button primary"><?php esc_html_e( 'Get it now!', 'groundhogg' ); ?></a></p>
        </div>
	<?php endif; ?>
</div>
