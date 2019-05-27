<?php

namespace Groundhogg\Admin\Guided_Setup;

use Groundhogg\Admin\Admin_Page;
use Groundhogg\Admin\Guided_Setup\Steps\Business_Info;
use Groundhogg\Admin\Guided_Setup\Steps\Compliance;
use Groundhogg\Admin\Guided_Setup\Steps\Email;
use Groundhogg\Admin\Guided_Setup\Steps\Finished;
use Groundhogg\Admin\Guided_Setup\Steps\Import_Contacts;
use Groundhogg\Admin\Guided_Setup\Steps\Start;
use Groundhogg\Admin\Guided_Setup\Steps\Step;
use function Groundhogg\floating_phil;
use function Groundhogg\get_array_var;
use function Groundhogg\get_request_var;
use Groundhogg\Plugin;
use function Groundhogg\show_groundhogg_branding;

/**
 * Guided Setup
 *
 * An automated and simple experience that allows users to setup Groundhogg in a few steps.
 *
 * @package     Admin
 * @subpackage  Admin/Guided Setup
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Guided_Setup extends Admin_Page
{

    protected $steps = [];

    /**
     * Add Ajax actions...
     *
     * @return mixed
     */
    protected function add_ajax_actions(){}

    /**
     * Adds additional actions.
     *
     * @return void
     */
    protected function add_additional_actions()
    {
        add_action( 'init', [ $this, 'init_steps' ] );
    }

    public function init_steps()
    {
        $steps = [];
        $steps[] = new Start();
        $steps[] = new Business_Info();
        $steps[] = new Compliance();
        $steps[] = new Import_Contacts();
        $steps[] = new Email();
        $steps[] = new Finished();

        $steps = apply_filters( 'groundhogg/admin/guided_setup/steps', $steps );

        $this->steps = $steps;
    }

    /**
     * Get the page slug
     *
     * @return string
     */
    public function get_slug()
    {
        return 'gh_guided_setup';
    }

    /**
     * Get the menu name
     *
     * @return string
     */
    public function get_name()
    {
        return __( 'Guided Setup', 'groundhogg' );
    }

    /**
     * The required minimum capability required to load the page
     *
     * @return string
     */
    public function get_cap()
    {
        return 'manage_options';
    }

    /**
     * Get the item type for this page
     *
     * @return mixed
     */
    public function get_item_type()
    {
        return 'step';
    }

    /**
     * Output the basic view.
     *
     * @return void
     */
    public function view()
    {
        _e( 'Look away!' );
    }

    /**
     * Just use the step process...
     */
    public function process_view()
    {
        $this->get_current_step()->go_to_next();
    }

    /**
     * @return string
     */
    public function get_parent_slug()
    {
        return 'options.php';
    }

    /**
     * @return int
     */
    public function get_current_step_id()
    {
        return absint( get_request_var( 'step', 0 ) );
    }

    /**
     * @return bool|Step
     */
    public function get_current_step()
    {
        return get_array_var( $this->steps, $this->get_current_step_id() );
    }

    /**
     * The main output
     */
    public function page()
    {
        ?>
        <div class="wrap">
        <?php if ( show_groundhogg_branding() ):
            floating_phil();
        endif; ?>
        <form action="" method="post">
            <?php wp_nonce_field(); ?>
            <div style="max-width: 600px;margin: auto;">
                <?php $this->get_current_step()->view(); ?>
            </div>
            <?php
        ?></form>
        </div><?php
    }

    /**
     * Enqueue any scripts
     */
    public function scripts(){}

    /**
     * Add any help items
     *
     * @return mixed
     */
    public function help(){}

}