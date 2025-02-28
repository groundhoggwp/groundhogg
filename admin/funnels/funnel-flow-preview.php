<?php
/**
 * @var $funnel \Groundhogg\Funnel
 */

$steps = $funnel->get_steps( [
    'branch' => 'main'
] );

?>
<div id="step-flow">
    <div class="fixed-inside" style="position: relative">
        <div class="step-branch"
             data-branch="main" style="padding: 40px 0"
        ><?php foreach ( $steps as $step ):$step->sortable_item();endforeach; ?></div>
    </div>
</div>
