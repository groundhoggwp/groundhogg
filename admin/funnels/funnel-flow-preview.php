<?php
/**
 * @var $funnel \Groundhogg\Funnel
 */

$steps = $funnel->get_steps( [
    'branch' => 'main'
] );

?>
<div id="table_funnel_stats">
    <div id="step-flow">
        <script>let Funnel = <?php echo wp_json_encode( $funnel ) ?></script>
        <div class="fixed-inside" style="position: relative">
            <div id="step-sortable" class="step-branch"
                 data-branch="main" style="padding: 24px 0"
            ><?php foreach ( $steps as $step ):$step->sortable_item();endforeach; ?></div>
        </div>
    </div>
</div>
