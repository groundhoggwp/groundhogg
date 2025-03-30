<?php

namespace Groundhogg\Admin\Funnels;

use Groundhogg\Funnel;
use Groundhogg\Plugin;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use function Groundhogg\is_pro_features_active;
use function Groundhogg\is_white_labeled;

/**
 * Add Funnel
 *
 * Similar to the Email add page, this allows one to select a funnel from some pre-installed defaults.
 * Or upload their own funnel if they purchased one from us or another provider
 *
 * @since       File available since Release 0.1
 * @subpackage  Admin/Funnels
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Admin
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
	<?php $active_tab = sanitize_key( get_request_var( 'tab', 'templates' ) ); ?>
    <h2 class="nav-tab-wrapper gh-nav">
        <a id="funnel-templates" href="?page=gh_funnels&action=add&tab=templates"
           class="nav-tab <?php echo $active_tab == 'templates' ? 'nav-tab-active' : ''; ?>"><?php _ex( 'Flow Templates', 'add_funnel_tab', 'groundhogg' ); ?></a>
        <a id="funnel-import" href="?page=gh_funnels&action=add&tab=import"
           class="nav-tab <?php echo $active_tab == 'import' ? 'nav-tab-active' : ''; ?>"><?php _ex( 'Import Flow', 'add_funnel_tab', 'groundhogg' ); ?></a>
    </h2>
    <script>
      ( () => {
        const pageHeader = document.getElementById('<?php esc_attr_e( $this->get_slug() . '-header' ) ?>')
        const navTabs = document.querySelector('h2.gh-nav')

        if (pageHeader) {
          pageHeader.insertAdjacentElement('afterend', navTabs)
        }
      } )()
    </script>

	<?php if ( 'templates' === $active_tab ):

	$funnel_templates = Plugin::$instance->library->get_funnel_templates();

	if ( ! is_array( $funnel_templates ) ) {
		$funnel_templates = [];
	}

	$funnel_templates = array_map( function ( $template ) {
		return new Funnel( $template, true );
	}, $funnel_templates );

	$campaigns = array_reduce( $funnel_templates, function ( $carry, Funnel $template ) {
		foreach ( $template->campaigns as $campaign ) {
			$carry[ $campaign->data->slug ] = $campaign->data->name;
		}

		return $carry;
	}, [] );

	// Filter out templates that have steps that are not registered on this site
	$funnel_templates = array_filter( $funnel_templates, function ( $funnel ) {

		$funnel->is_premium = false;

		foreach ( $funnel->steps as $step ) {
			$step = (object) $step;

			// don't show funnels with unregistered types
			if ( ! Plugin::instance()->step_manager->type_is_registered( $step->data->step_type ) ) {
				return false;
			}

			if ( Plugin::instance()->step_manager->get_element( $step->data->step_type )->is_premium() && ! is_pro_features_active() && ! is_white_labeled() ) {
				$funnel->is_premium = true;
			}
		}

		return true;
	} )

	?>
    <p></p>
    <div class="display-flex gap-10" id="template-filters">
        <div style="width: 200px">
			<?php echo html()->select2( [
				'options'        => $campaigns,
				'name'           => 'filter_campaigns',
				'id'             => 'filter-campaigns',
				'placeholder'    => 'Filter by campaign',
				'data-clearable' => 1
			] ); ?>
        </div>
        <input type="search" placeholder="Search..." id="search-templates" name="search">
    </div>
    <script>
      ( () => {

        document.querySelector( 'a.page-title-action' ).insertAdjacentElement( 'beforebegin', document.getElementById( 'template-filters' ) )

        let selected = '', search = ''

        const updateStyle = () => {

          let rules = []

          if (search) {
            rules.push(`.funnel-template:not([data-title*=${ search } i]){display:none;}`)
          }

          if (selected) {
            rules.push(`.funnel-template:not([data-campaigns*=${ selected } i]){display:none;}`)
          }

          document.getElementById('template-filter-css').innerHTML = rules.join('\n')
        }

        // show selected campaigns as or
        document.getElementById('filter-campaigns').addEventListener('change', e => {
          selected = e.target.value
          updateStyle()
        })

        // hide funnels that don't match search
        document.getElementById('search-templates').addEventListener('input', e => {
          search = e.target.value
          updateStyle()
        })
      } )()
    </script>
    <style id="template-filter-css"></style>
    <form method="post">
		<?php wp_nonce_field( 'add' ); ?>
        <div class="post-box-grid">
			<?php foreach ( $funnel_templates as $template ):

                /* @var $template Funnel */

				$campaigns = array_map( function ( $campaign ) {
					return $campaign->data->slug;
				}, $template->campaigns );

				?>
                <div class="gh-panel funnel-template <?php echo $template->is_premium ? 'premium' : ''; ?> display-flex column" data-campaigns="<?php esc_attr_e( implode( ',', $campaigns ) ); ?>"
                     data-title="<?php esc_attr_e( $template->title ); ?>">
                    <div class="gh-panel-header">
                        <h2><?php echo $template->get_title(); ?></h2>
						<?php if ( $template->is_premium ): ?>
                            <div style="padding: 5px">
                                <span class="pill dark">PRO
                                    <div class="gh-tooltip top">This funnel contains paid features.</div>
                                </span>
                            </div>
						<?php endif; ?>
                    </div>
                    <div class="inside display-flex column align-top" style="flex-grow: 1">
                        <div class="gh-tags space-below-20">
							<?php foreach ( $template->campaigns as $campaign ): ?>
                                <span class="gh-tag"><?php esc_html_e( $campaign->data->name ); ?></span>
							<?php endforeach; ?>
                        </div>
                        <?php $template->flow_preview() ?>
                        <p><?php echo $template->get_meta( 'description' ); ?></p>
						<?php if ( $template->is_premium ): ?>
                            <a style="margin-top: auto" class="gh-button primary text" href="https://groundhogg.io/pricing/" target="_blank"><?php _ex( '🔓 Upgrade to unlock!', 'action', 'groundhogg' ); ?></a>
						<?php else: ?>
                            <button style="margin-top: auto" class="gh-button primary" name="funnel_template"
                                    value="<?php echo $template->ID ?>"><?php _ex( 'Use Template', 'action', 'groundhogg' ); ?></button>
						<?php endif; ?>
                    </div>
                </div>
			<?php endforeach; ?>
        </div>
    </form>
<?php else: ?>
    <div class="gh-tools-wrap">
        <p class="tools-help"><?php _e( 'If you have a flow import file (ends in .funnel) you can upload it here!', 'groundhogg' ); ?></p>
        <form method="post" enctype="multipart/form-data" class="gh-tools-box gh-panel">
			<?php wp_nonce_field(); ?>
            <p class="description"><?php _e( 'Upload a .funnel export file.', 'groundhogg' ); ?></p>
            <hr/>
            <input type="file" name="funnel_template" id="funnel_template" accept=".funnel,.json">
            <button style="float: right" class="gh-button primary" name="funnel_import"
                    value="import"><?php _ex( 'Import Flow', 'action', 'groundhogg' ); ?></button>
            <div class="wp-clearfix"></div>
        </form>
        <form method="post" class="gh-tools-box gh-panel">
			<?php wp_nonce_field(); ?>
            <p class="description"><?php _e( 'Copy and paste JSON from a .funnel export file if you are having issue uploading.', 'groundhogg' ); ?></p>
            <hr/>
            <textarea style="width: 100%;margin-bottom: 5px;" rows="3" name="funnel_json" id="funnel_json"
                      placeholder="<?php esc_attr_e( 'Paste JSON from .funnel file.', 'groundhogg' ); ?>"></textarea>
            <button style="float: right" class="gh-button primary" name="funnel_import"
                    value="import"><?php _ex( 'Import Flow', 'action', 'groundhogg' ); ?></button>
            <div class="wp-clearfix"></div>
        </form>
    </div>
<?php endif;

