<?php
/**
 * Email body
 *
 * @package     Templates/Emails
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */


if ( ! defined( 'ABSPATH' ) ) exit;


?>
<!-- START CONTENT -->
<?php echo apply_filters( 'wpgh_email_get_content', '' ); ?>
<!-- END CONTENT -->