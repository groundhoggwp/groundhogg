<?php

namespace Groundhogg\Bulk_Jobs;

// Exit if accessed directly
use Groundhogg\Plugin;
use function Groundhogg\isset_not_empty;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Bulk job
 *
 * Provides a framework for extensions which require bulk jobs through the bulk job processor.
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       1.3
 */
abstract class Bulk_Job
{

    /**
     * WPGH_Bulk_Jon constructor.
     */
    public function __construct()
    {
        add_filter( "groundhogg/bulk_job/{$this->get_action()}/max_items", [ $this, 'max_items' ], 10, 2 );
        add_filter( "groundhogg/bulk_job/{$this->get_action()}/query", [ $this, 'query' ] );
        add_action( "groundhogg/bulk_job/{$this->get_action()}/ajax", [ $this, 'process' ] );
    }

    /**
     * Start the bulk job by redirecting to the bulk jobs page.
     *
     * @param $additional array any additional arguments to add to the link
     */
    public function start( $additional=[] )
    {
        wp_redirect( $this->get_start_url( $additional ) );
        die();
    }

    /**
     * Get the URL which will start the job.
     *
     * @param $additional array any additional arguments to add to the link
     * @return string
     */
    public function get_start_url( $additional=[] )
    {
        return add_query_arg( array_merge( [ 'action' => $this->get_action() ], $this->get_start_query_args(), $additional ), admin_url( 'admin.php?page=gh_bulk_jobs' ) );
    }

    /**
     * Get additional query args if any
     *
     * @return array
     */
    protected function get_start_query_args()
    {
        return [];
    }

    /**
     * Get the action reference.
     *
     * @return string
     */
    abstract public function get_action();

    /**
     * Get an array of items someway somehow
     *
     * @param $items array
     * @return array
     */
    abstract public function query( $items );

    /**
     * Get the maximum number of items which can be processed at a time.
     *
     * @param $max int
     * @param $items array
     * @return int
     */
    abstract public function max_items( $max, $items );

    /**
     * Check to see if the current process will be the final one.
     *
     * @return mixed
     */
    public function is_then_end()
    {
        return filter_var( $_POST[ 'the_end' ], FILTER_VALIDATE_BOOLEAN );
    }

    /**
     * Get a list of items from the bulk job.
     *
     * @return array
     */
    public function get_items()
    {
        return isset_not_empty( $_POST, 'items' ) ? $_POST[ 'items' ] : [];
    }

    /**
     * Process the bulk job.
     */
    public function process()
    {

        $items = $this->get_items();

        $completed = 0;

        $this->pre_loop();

        foreach ( $items as $item ){
            $this->process_item( $item );
            $completed++;
        }

        $this->post_loop();

        $response = [ 'complete' => $completed ];

        if ( filter_var( $_POST[ 'the_end' ], FILTER_VALIDATE_BOOLEAN ) ){

            $this->clean_up();

            $response[ 'return_url' ] = $this->get_return_url();

            Plugin::instance()->notices->add('finished', $this->get_finished_notice() );

        }

        wp_die( json_encode( $response ) );

    }

    /**
     * Do stuff before the loop
     *
     * @return void
     */
    abstract protected function pre_loop();

    /**
     * do stuff after the loop
     *
     * @return void
     */
    abstract protected function post_loop();

    /**
     * Process an item
     *
     * @param $item mixed
     * @param $args array
     * @return void
     */
    abstract protected function process_item( $item );

    /**
     * Cleanup any options/transients/notices after the bulk job has been processed.
     *
     * @return void
     */
    abstract protected function clean_up();

    /**
     * Get the return url.
     *
     * @return string
     */
    protected function get_return_url()
    {
        return admin_url( 'admin.php?page=groundhogg' );
    }

    /**
     * get text for the finished notice
     *
     * @return string
     */
    protected function get_finished_notice()
    {
        return _x( 'Job finished!', 'notice', 'groundhogg' );
    }

}

