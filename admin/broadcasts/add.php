<?php

namespace Groundhogg\Admin\Broadcasts;

use Groundhogg\Email;
use GroundhoggSMS\Classes\SMS;
use function Groundhogg\get_url_var;
use function Groundhogg\isset_not_empty;
use function Groundhogg\one_of;

/**
 * This is the page which allows the user to schedule a broadcast.
 *
 * Broadcasts are a closed process and thus have very limited hooks to modify the functionality.
 * If you are looking to extend the broadcast experience you are better off designing your own page to schedule broadcasts.
 *
 * @since       File available since Release 0.1
 * @see         WPGH_Broadcasts_Page::add()
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Admin
 * @subpackage  Admin/Broadcasts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$type = one_of( get_url_var( 'type', 'email' ), [ 'email', 'sms' ] );

if ( $type === 'email' ): ?>

    <script>
      const GroundhoggNewBroadcast = <?php echo wp_json_encode( [
		  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		  'email' => isset_not_empty( $_GET, 'email' ) ? new Email( absint( get_url_var( 'email' ) ) ) : false,
	  ] ); ?>
    </script>
    <div class="gh-panel" style="width: 500px; margin: 20px 0;">
        <div id="gh-broadcast-form-inline" class="inside"></div>
    </div>

<?php else: ?>

    <script>
      const GroundhoggNewSmsBroadcast = <?php echo wp_json_encode( [
	      // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		  'sms' => isset_not_empty( $_GET, 'sms' ) ? new SMS( absint( get_url_var( 'sms' ) ) ) : false,
	  ] ); ?>
    </script>
    <div class="gh-panel" style="width: 500px; margin: 20px 0;">
        <div id="gh-broadcast-form-inline" class="inside"></div>
    </div>

<?php endif;
