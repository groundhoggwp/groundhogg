<?php

namespace Groundhogg\Admin\Contacts;

use Groundhogg\Contact_Query;
use Groundhogg\Saved_Searches;
use function Groundhogg\action_input;
use function Groundhogg\get_db;
use function Groundhogg\get_url_var;
use function Groundhogg\html;

$filters = get_url_var( 'filters' );

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
            <?php Contact_Query::render_filters( $filters ); ?>
        </div>
    </form>
</div>
