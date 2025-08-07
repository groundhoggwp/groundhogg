<?php

namespace Groundhogg\Admin\Reports\Views;

// Overview
use Groundhogg\Funnel;
use function Groundhogg\get_url_var;
use function Groundhogg\html;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

$funnel = new Funnel( get_url_var( 'funnel' ) );

if ( ! $funnel->exists() ) {
	wp_die( 'Flow does not exist!' );
}

?>

<div class="display-flex gap-20 align-center">
    <h1 class="report-title"><?php echo esc_html( $funnel->get_title() ); ?></h1>
	<?php echo html()->e( 'a', [
		'target' => '_blank',
		'id'     => 'edit-funnel',
		'href'   => $funnel->admin_link(),
		'class'  => 'gh-button secondary'
	], esc_html__( 'Edit Flow', 'groundhogg' ) ) ?>
</div>
<div class="display-grid gap-20">

	<?php quick_stat_report( [
		'id'    => 'total_contacts_added_to_funnel',
		'title' => esc_html__( 'Contacts Added', 'groundhogg' ),
		'class' => 'span-3',
	] ); ?>

	<?php quick_stat_report( [
		'id'    => 'total_contacts_in_funnel',
		'title' => esc_html__( 'Active Contacts', 'groundhogg' ),
		'class' => 'span-3',
	] ); ?>

	<?php quick_stat_report( [
		'id'    => 'total_funnel_conversions',
		'title' => esc_html__( 'Conversions', 'groundhogg' ),
		'class' => 'span-3',
	] );
	?>

	<?php quick_stat_report( [
		'id'    => 'total_funnel_conversion_rate',
		'title' => esc_html__( 'Conversion Rate', 'groundhogg' ),
		'class' => 'span-3',
	] ); ?>

	<?php do_action( 'groundhogg/admin/reports/pages/funnels/after_quick_stats' ); ?>

    <div class="gh-panel span-12">
        <div class="gh-panel-header">
            <h2 class="title"><?php esc_html_e( 'Flow Breakdown', 'groundhogg' ); ?></h2>
        </div>
        <div id="chart_funnel_breakdown"></div>
    </div>

	<?php

	quick_stat_report( [
		'id'    => 'total_emails_sent',
		'title' => esc_html__( 'Emails Sent', 'groundhogg' ),
		'class' => 'span-4'
	] );
	quick_stat_report( [
		'id'    => 'email_open_rate',
		'title' => esc_html__( 'Open Rate', 'groundhogg' ),
		'class' => 'span-4'
	] );
	quick_stat_report( [
		'id'    => 'email_click_rate',
		'title' => esc_html__( 'Click Thru Rate', 'groundhogg' ),
		'class' => 'span-4'
	] ); ?>

    <div class="gh-panel span-12">
        <div class="gh-panel-header">
            <h2 class="title"><?php esc_html_e( 'Email Performance', 'groundhogg' ); ?></h2>
        </div>
        <div id="table_all_funnel_emails_performance"></div>
    </div>

    <div class="gh-panel span-12">
        <div class="gh-panel-header">
            <h2 class="title"><?php esc_html_e( 'Forms', 'groundhogg' ); ?></h2>
        </div>
        <div id="table_form_activity"></div>
    </div>

    <div class="gh-panel span-12">
        <div class="gh-panel-header">
            <h2 class="title"><?php esc_html_e( 'Activity', 'groundhogg' ); ?></h2>
        </div>
		<?php

		wp_enqueue_style( 'groundhogg-admin-funnel-editor' );
		wp_enqueue_script( 'groundhogg-admin-funnel-editor' );

		include __DIR__ . '/../../funnels/funnel-flow-preview.php'
		?>
        <script>
          ( $ => {

            $('.step .stat-wrap').click(e => {
              let a = e.currentTarget.querySelector('a')

              if (a) {
                a.click()
              }
            })

            //$('.step[data-id]').click(e => {
            //  window.open(Groundhogg.element.adminPageURL('gh_funnels', {
            //    action: 'edit',
            //    funnel: <?php //echo $funnel->ID ?>
            //  }, e.currentTarget.dataset.id), '_self')
            //})
          } )(jQuery)
        </script>
    </div>
</div>

<?php do_action( 'groundhogg/admin/reports/pages/funnels/after_reports' ); ?>
