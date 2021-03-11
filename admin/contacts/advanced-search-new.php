<?php

namespace Groundhogg\Admin\Contacts;

use function Groundhogg\get_url_var;
use function Groundhogg\html;

?>
<div id="search-filters" class="postbox <?php echo ( get_url_var( 'is_searching' ) ) ? '' : 'hidden'; ?>">
	<form method="get">
		<?php echo html()->input( [
			'type'  => 'hidden',
			'name'  => 'is_searching',
			'value' => 'on',
		] ); ?>
		<?php html()->hidden_GET_inputs(); ?>

		<div id="filters">
			<div class="filter-or-group" v-for="(filterGroup, index) in filterGroups" :key="index">
				<filter-group :id="index" :filters="filterGroup.filters" @add-filter="addFilter"></filter-group>
				<or-separator></or-separator>
			</div>
			<div class="filter-group-wrap">
				<button class="button" type="button"
				        v-on:click="addFilterGroup"><?php _e( 'Add Filter', 'groundhogg' ) ?></button>
			</div>
		</div>
	</form>
</div>
