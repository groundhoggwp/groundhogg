<?php

use function Groundhogg\action_input;
use function Groundhogg\get_db;
use function Groundhogg\get_url_var;
use function Groundhogg\html;

$query = get_url_var( 'query' );

if ( $query ) {
	$count = get_db( 'contacts' )->count( $query );
}

$funnels = get_db( 'funnels' )->query( [ 'status' => 'active' ] );
$steps   = get_db( 'steps' )->query( [
	'funnel_id' => wp_list_pluck( $funnels, 'ID' ),
	'orderby'   => 'step_order',
	'order'     => 'asc'
] );

$json = [
	'funnels' => $funnels,
	'steps'   => $steps,
];

wp_enqueue_script( 'groundhogg-admin' );
wp_enqueue_style( 'select2' );

?>
<div class="gh-tools-wrap">
	<script>
      (function ($) {

        var json = <?php echo wp_json_encode( $json ); ?>;

        $(function () {

          $('#funnel').select2({
            data: json.funnels.map(f => {
              return {
                id: f.ID,
                text: f.title
              }
            })
          }).on('select2:select', function (e) {
            var funnelId = e.params.data.id

            console.log(funnelId)

            var steps = json.steps.filter(s => s.funnel_id == funnelId).map(s => {
              return {
                id: s.ID,
                text: s.step_title
              }
            })

            console.log(steps)

            $('#step').empty().select2({
              data: steps
            })
          })

          $('#step').select2()

        })

      })(jQuery)
	</script>
	<p class="tools-help"><?php _e( 'Add contacts to a funnel', 'groundhogg' ); ?></p>
	<form method="post" class="gh-tools-box">
		<?php action_input( 'add_to_funnel' ); ?>
		<?php wp_nonce_field(); ?>
		<?php if ( ! $query ): ?>
			<?php echo html()->tag_picker( [] ); ?>
		<?php else: ?>
			<?php html()->hidden_inputs( $query, 'query' ); ?>
		<?php endif; ?>
		<p>
			<label for="funnel" class="block"><?php _e( 'Which funnel?', 'groundhogg' ) ?></label>
			<?php echo html()->dropdown( [
				'name'        => 'funnel',
				'id'          => 'funnel',
				'option_none' => __( 'Please select a funnel' )
			] ); ?>
		</p>
		<p>
			<label for="funnel" class="block"><?php _e( 'Which step?', 'groundhogg' ) ?></label>
			<?php echo html()->dropdown( [
				'name'        => 'step',
				'id'          => 'step',
				'option_none' => __( 'Please select a step' )
			] ); ?>
		</p>
		<button class="button button-primary"
		        value="add">
			<?php if ( $query && $count ): ?>
				<?php printf( _nx( 'Add %s contact to funnel', 'Add %s contacts to funnel', $count, 'action', 'groundhogg' ), $count ); ?>
			<?php else: ?>
				<?php _ex( 'Delete contacts', 'action', 'groundhogg' ); ?>
			<?php endif; ?>
		</button>
	</form>
</div>
