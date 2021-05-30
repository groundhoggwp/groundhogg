<?php

namespace Groundhogg\Admin\Funnels\Editor;

use function Groundhogg\dashicon_e;

?>
<div class="editor-header">
	<div class="back-to-admin"></div>
	<div class="header-stuff">
		<div class="title-wrap">
			<div class="above-title"><?php _e( 'Funnel Info', 'groundhogg' ); ?></div>
			<div class="title">
				<span class="title-inner">Funnel Title goes here</span><span class="pencil"><?php dashicon_e( 'edit' ); ?></span>
			</div>
		</div>
		<div class="header-actions">
			<div class="undo-and-redo">
				<div class="redo"><?php dashicon_e( 'redo' ); ?></div>
				<div class="undo"><?php dashicon_e( 'undo' ); ?></div>
			</div>
			<div class="publish-actions">
			</div>
		</div>
	</div>
</div>

