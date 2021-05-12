<?php

namespace Groundhogg\Admin\Funnels;

use Groundhogg\Funnel;
use Groundhogg\Step;
use function Groundhogg\Admin\Reports\Views\get_funnel_id;
use function Groundhogg\admin_page_url;
use function Groundhogg\dashicon;
use function Groundhogg\is_white_labeled;
use function Groundhogg\key_to_words;
use Groundhogg\Plugin;
use function Groundhogg\get_request_var;
use function Groundhogg\html;

/**
 * Edit Funnel
 *
 * This page allows one to edit the funnels they have installed.
 *
 * @since       File available since Release 0.1
 * @subpackage  Admin/Funnels
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$funnel_id = absint( get_request_var( 'funnel' ) );

$funnel = new Funnel( $funnel_id );

?>
<div class="funnel-editor-header">
	<div class="title-section">
		<div class="title-view">
			<?php printf( __( 'Now editing %s', 'groundhogg' ), html()->e( 'span', [ 'class' => 'title' ], $funnel->get_title() ) ); ?>
		</div>
		<div class="title-edit hidden">
			<input class="title" placeholder="<?php echo __( 'Enter Funnel Name Here', 'groundhogg' ); ?>"
			       type="text"
			       name="funnel_title" size="30" value="<?php esc_attr_e( $funnel->get_title() ); ?>" id="title"
			       spellcheck="true" autocomplete="off">
		</div>
	</div>
	<?php
	echo html()->button( [
		'type'  => 'submit',
		'text'  => dashicon( 'yes' ) . html()->wrap( __( 'Save' ), 'span', [ 'class' => 'save-text' ] ),
		'name'  => 'update',
		'id'    => 'update',
		'class' => 'button button-primary save-button',
		'value' => 'save',
	] );
	?>
</div>