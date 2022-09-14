<?php

use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Step;
use function Groundhogg\get_request_var;
use function Groundhogg\html;

$funnel_id = absint( get_request_var( 'funnel' ) );
$funnel    = new Funnel( $funnel_id );

?>
<form class="" method="post">

	<?php wp_nonce_field( 'funnel_settings' ); ?>

    <table class="form-table">
        <tbody>
        <tr>
            <th><?php _e( 'Description', 'groundhogg' ); ?></th>
            <td><?php

				echo html()->textarea( [
					'name'  => 'description',
					'value' => $funnel->get_meta( 'description' ),
				] );

				?></td>
        </tr>
        </tbody>
    </table>

	<?php submit_button(); ?>
</form>
