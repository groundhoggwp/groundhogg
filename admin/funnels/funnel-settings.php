<?php

use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Step;
use function Groundhogg\get_request_var;
use function Groundhogg\html;

$funnel_id = absint( get_request_var( 'funnel' ) );
$funnel = new Funnel( $funnel_id );

?>
<form class="" method="post">

	<?php wp_nonce_field( 'funnel_settings' ); ?>

	<table class="form-table">
		<tbody>
		<tr>
			<th><?php _e( 'Conversion Benchmark', 'groundhogg' ); ?></th>
			<td><?php

				$steps = $funnel->get_steps( [
					'step_group' => 'benchmark',
					'funnel_id'  => $funnel_id
				] );

				$options = [];

				foreach ( $steps as $step ) {
					$step                       = new Step( absint( $step->ID ) );
					$options[ $step->get_id() ] = $step->get_title();
				}

				$args = [
					'name'        => 'conversion_step_id',
					'id'          => 'conversion-step',
					'options'     => $options,
					'selected'    => $funnel->get_conversion_step_id(),
					'option_none' => false,
				];

				echo Plugin::$instance->utils->html->dropdown( $args );

				echo html()->description( __( 'This is used to calculate the conversion rate of a funnel in a report. By default it is assumed the last step should be used.', 'groundhogg' ) )

				?></td>
		</tr>
		</tbody>
	</table>

	<?php submit_button(); ?>
</form>
