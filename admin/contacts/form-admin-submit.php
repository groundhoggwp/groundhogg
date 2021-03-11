<?php
namespace Groundhogg\Admin\Contacts;

use Groundhogg\Step;
use function Groundhogg\get_form_list;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use Groundhogg\Plugin;

/**
 * Submit a form manually via the admin
 *
 * @package     Admin
 * @subpackage  Admin/Contacts
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @see         WPGH_Contacts_Page::edit()
 * @since       File available since Release 1.1.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$form_id = absint( get_request_var( 'form' ) );

$step = new Step( $form_id ); //todo check

$forms = get_form_list();
?>
<!-- Title -->
<span class="hidden" id="new-title"><?php echo $step->get_title() ?> &lsaquo; </span>
<script>
  document.title = jQuery('#new-title').text() + document.title
</script>
<table class="form-table">
	<tr>
		<th><?php _ex( 'Internal Form', 'contact_record', 'groundhogg' ); ?></th>
		<td>
			<div style="max-width: 400px;">
				<form method="get">
					<?php html()->hidden_GET_inputs(); ?>
					<?php wp_nonce_field( 'switch_form', '_wpnonce', false ); ?>
					<?php

					echo Plugin::$instance->utils->html->select2( [
						'name'        => 'form',
						'id'          => 'manual_form_submission',
						'class'       => 'manual-submission gh-select2',
						'data'        => $forms,
						'multiple'    => false,
						'selected'    => $form_id,
						'placeholder' => __( 'Please select a form', 'groundhogg' ),
					] );

					submit_button( __( 'Switch Form', 'groundhogg' ) );
					?>
				</form>
			</div>
		</td>
	</tr>
</table>
<hr>
<div>
	<div style="max-width: 800px; margin: 100px auto">
		<?php

		if ( ! $form_id ) {
			$ids     = array_keys( $forms );
			$form_id = array_shift( $ids );
		}

		echo do_shortcode( sprintf( '[gh_form id="%d"]', $form_id ) ); ?>
	</div>
</div>