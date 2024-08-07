<?php
namespace Groundhogg\Admin\Broadcasts;

use Groundhogg\Email;
use GroundhoggSMS\Classes\SMS;
use function Groundhogg\get_url_var;
use function Groundhogg\isset_not_empty;

/**
 * This is the page which allows the user to schedule a broadcast.
 *
 * Broadcasts are a closed process and thus have very limited hooks to modify the functionality.
 * If you are looking to extend the broadcast experience you are better off designing your own page to schedule broadcasts.
 *
 * @package     Admin
 * @subpackage  Admin/Broadcasts
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @see         WPGH_Broadcasts_Page::add()
 * @since       File available since Release 0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$type = isset( $_REQUEST['type'] ) && $_REQUEST['type'] === 'sms' ? 'sms' : 'email';

if ( $type === 'email' ): ?>

    <script>
        const GroundhoggNewBroadcast = <?php echo wp_json_encode( [
            'email' => isset_not_empty( $_GET, 'email' ) ? new Email( get_url_var( 'email' )  ) : false,
        ] ); ?>
    </script>
    <div class="gh-panel" style="width: 500px; margin: 20px 0;">
        <div id="gh-broadcast-form-inline" class="inside"></div>
    </div>

<?php else: ?>

    <script>
      const GroundhoggNewSmsBroadcast = <?php echo wp_json_encode( [
		  'sms' => isset_not_empty( $_GET, 'sms' ) ? new SMS( get_url_var( 'sms' ) ) : false,
	  ] ); ?>
    </script>
    <div class="gh-panel" style="width: 500px; margin: 20px 0;">
        <div id="gh-broadcast-form-inline" class="inside"></div>
    </div>

<?php endif;
