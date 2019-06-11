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
<div class="body-content" style="text-align: left;">
    <?php do_action( 'groundhogg/templates/email/content/before' ); ?>

    <!-- START CONTENT -->
    <?php echo apply_filters( 'groundhogg/email_template/content', '' ); ?>
    <!-- END CONTENT -->

    <?php do_action( 'groundhogg/templates/email/content/after' ); ?>
</div>