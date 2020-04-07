<?php

namespace Groundhogg\Admin\Events;


use function Amp\ParallelFunctions\parallelMap;
use function Amp\Promise\wait;



use Amp\Parallel\Worker\DefaultPool;



use Amp\Parallel\Worker\CallableTask;
use Amp\Parallel\Worker\DefaultWorkerFactory;

use Amp\Parallel\Worker;
use Amp\Promise;

use Amp\Loop;
use function Amp\call;



use Groundhogg\Admin\Admin_Page;
use Groundhogg\Event;
use function Amp\delay;
use function Groundhogg\get_db;
use Groundhogg\Plugin;
use function Groundhogg\get_request_var;

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * View Events
 *
 * Allow the user to view & edit the events
 * This allows one to manage all the events associated with funnels, broadcasts, and funnels.
 * This was included as a page for the convenience of the end user. Although only advanced users will use it probably.
 *
 * @package     Admin
 * @subpackage  Admin/Events
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class Events_Page extends Admin_Page
{

    //UNUSED FUNCTIONS
    protected function add_ajax_actions() {}
    public function help() {}
    protected function add_additional_actions() {}
    public function scripts() {
        wp_enqueue_style( 'groundhogg-admin' );
    }

    public function get_slug()
    {
       return 'gh_events';
    }

    public function get_name()
    {
        return _x( 'Events', 'page_title', 'groundhogg' );
    }

    public function get_cap()
    {
        return 'view_events';
    }

    public function get_item_type()
    {
        return 'event';
    }

    public function get_priority()
    {
        return 40;
    }

    protected function get_title_actions()
    {
        return [];
    }

    /**
     *  Sets the title of the page
     * @return string
     */
    public function get_title()
    {
        switch ( $this->get_current_action() ) {
            case 'view':
            default:
                return _x( 'Events', 'page_title', 'groundhogg' );
                break;
        }
    }

    /**
     * Cancels scheduled broadcast
     *
     * @return bool
     */
    public function process_cancel()
    {
        if ( !current_user_can( 'cancel_events' ) ) {
            $this->wp_die_no_access();
        }

        foreach ( $this->get_items() as $eid ) {
            Plugin::$instance->dbs->get_db( 'events' )->update(
                absint( $eid ),
                array(
                    'status' => 'cancelled'
                )
            );
        }

        $this->add_notice( 'cancelled', sprintf( _nx( '%d event cancelled', '%d events cancelled', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ) );

        if ( $contact_id = absint( get_request_var( 'return_to_contact' ) ) ) {
            return admin_url('admin.php?page=gh_contacts&action=edit&tab=activity&contact=' . $contact_id);
        }

        //false return users to the main page
        return false;
    }

    /**
     * Clean up the events DB if something goes wrong.
     *
     * @return bool
     */
    public function process_cleanup()
    {
        if ( !current_user_can( 'execute_events' ) ) {
            $this->wp_die_no_access();
        }

        global $wpdb;

        $events = get_db( 'events' );

        $wpdb->query( "UPDATE {$events->get_table_name()} SET claim = '' WHERE claim <> ''" );
        $wpdb->query( "UPDATE {$events->get_table_name()} SET status = 'complete' WHERE status = 'in_progress'" );

        return false;
    }

    public function process_purge()
    {
        if ( !current_user_can( 'cancel_events' ) ) {
            $this->wp_die_no_access();
        }

        global $wpdb;
        $events = get_db( 'events' );
        $result = $wpdb->delete( $events->get_table_name(), [
            'status' => Event::FAILED
        ] );

        if ( $result ){
            $this->add_notice( 'events_purged', __( 'Purged failed events!' ) );
        }
    }

    /**
     * Clean up the events DB if something goes wrong.
     *
     * @return bool
     */
    public function process_process_queue()
    {
        if ( !current_user_can( 'execute_events' ) ) {
            $this->wp_die_no_access();
        }

        $queue = Plugin::$instance->event_queue;

        Plugin::$instance->notices->add( 'queue-complete', sprintf( "%d events have been completed in %s seconds.", $queue->run_queue(), $queue->get_last_execution_time() ) );

        if ( $queue->has_errors() ){
            Plugin::$instance->notices->add( 'queue-errors', sprintf( "%d events failed to complete. Please see the following errors.", count( $queue->get_errors() ) ), 'warning' );

            foreach ( $queue->get_errors() as $error ){
                Plugin::instance()->notices->add( $error );
            }
        }

        if ( $contact_id = absint( get_request_var( 'return_to_contact' ) ) ){
            return admin_url( 'admin.php?page=gh_contacts&action=edit&tab=activity&contact=' . $contact_id );
        }

        return false;
    }


    public function process_process_test()
    {

	    function asyncMultiply($x)
	    {
		    // Create a new promisor
		    $deferred = new \Amp\Deferred;

		    // Resolve the async result one second from now
		    Loop::delay(rand ( 0 ,  1000 )  , function () use ($deferred, $x) {


		        echo $x . "<br/>";

//
//		        if ( $x / 5  == 0) {
//		            $deferred ->fail( "s" );
//		        }
//
			    $deferred->resolve( true );
//
//

		    });


		    return $deferred->promise();
	    }


	    function nl($x)
        {
            delay(1000);
	        echo $x . "<br/>";
        }



	    $start = microtime(true);

	    for ($i=0 ;$i< 100 ; $i++ )
        {
	        $promise[] = asyncMultiply($i ) ;

        }

	    $result = \Amp\Promise\wait(Promise\all( $promise ));


	    echo "special loop ". $time_elapsed_secs = microtime(true) - $start;

	    $start1 = microtime(true);

	    for ($i=0 ;$i< 100 ; $i++ )
	    {

		    nl($i);
	    }

	    echo  'Normal loop' . $time_elapsed_secs1 = microtime(true) - $start1;


	    $uris = [
		    "https://google.com/",
		    "https://github.com/",
		    "https://stackoverflow.com/",
	    ];

	    $results = [];
	    foreach ($uris as $uri) {
		    var_dump("fetching $uri..");
		    $results[$uri] = file_get_contents($uri);
		    var_dump("done fetching $uri.");
	    }

	    foreach ($results as $uri => $result) {
		    var_dump("uri : $uri");
		    var_dump("result : " . strlen($result));
	    }




//	    $urls = [
//		    'https://secure.php.net',
//		    'https://amphp.org',
//		    'https://github.com',
//	    ];
//
//	    $promises = [];
//	    foreach ($urls as $url) {
//		    $promises[$url] = Worker\enqueueCallable('file_get_contents', $url);
//	    }
//
//	    $responses = Promise\wait(Promise\all($promises));
//
//	    foreach ($responses as $url => $response) {
//		    \printf("Read %d bytes from %s\n", \strlen($response), $url);
//	    }




	    $start = \microtime(true);

// sleep() is executed in child processes, the results are sent back to the parent.
//
// All communication is non-blocking and can be used in an event loop. Amp\Promise\wait() can be used to use the library
// in a traditional synchronous environment.
	    wait(parallelMap([1, 2, 3], 'sleep'));

	    print 'Took ' . (\microtime(true) - $start) . ' seconds.' . \PHP_EOL;


	    wp_die( 'here ' );


    }




    /**
     * Executes the event
     *
     * @return bool
     */
    public function process_execute()
    {
        if ( !current_user_can( 'execute_events' ) ) {
            $this->wp_die_no_access();
        }

        foreach ( $this->get_items() as $eid ) {
            Plugin::$instance->dbs->get_db( 'events' )->update(
                $eid,
                array(
                    'status' => 'waiting',
                    'time' => time()
                )
            );
        }

        $this->add_notice( 'scheduled', sprintf( _nx( '%d event rescheduled', '%d events rescheduled', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ) );

        if ( $contact_id = absint( get_request_var( 'return_to_contact' ) ) ){
            return admin_url( 'admin.php?page=gh_contacts&action=edit&tab=activity&contact=' . $contact_id );
        }

        return false;
    }

    public function view()
    {
        if ( !current_user_can( 'view_events' ) ) {
            $this->wp_die_no_access();
        }

        if ( !class_exists( 'Events_Table' ) ) {
            include dirname( __FILE__ ) . '/events-table.php';
        }

        $events_table = new Events_Table();

        $events_table->views();
        ?>
        <form method="post" class="search-form wp-clearfix">
            <!-- search form -->
            <?php $events_table->prepare_items(); ?>
            <?php $events_table->display(); ?>
        </form>

        <?php
    }

}