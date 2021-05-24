<?php

namespace Groundhogg\Admin\Funnels\Editor;

?>
<div class="editor">
	<?php

	include __DIR__ . '/header.php';

	?>
	<div class="flow-and-edit">
		<?php
		include __DIR__ . '/flow.php';
		?>
		<div id="control-panel">
			<?php
			include __DIR__ . '/step-add.php';
			?>
		</div>
	</div>
</div>