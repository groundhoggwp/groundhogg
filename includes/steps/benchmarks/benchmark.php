<?php

namespace Groundhogg\Steps\Benchmarks;

use Groundhogg\Contact;
use Groundhogg\Event;
use function Groundhogg\isset_not_empty;
use Groundhogg\Steps\Funnel_Step;

if ( ! defined( 'ABSPATH' ) ) exit;

abstract class Benchmark extends Funnel_Step
{

	/**
	 * @return string
	 */
	public function get_help_article()
	{
		return 'https://docs.groundhogg.io/docs/builder/benchmarks/';
	}

    /**
     * List for arbitrary data manipulation
     *
     * @var array
     */
    protected $data = [];

    /**
     * Add arbitrary data
     *
     * @param string $key
     * @param int $data
     */
    protected function add_data( $key = '', $data = 0 )
    {
        $this->data[ $key ] = $data;
    }

    /**
     * Get arbitrary data.
     *
     * @param string $key
     * @param bool $default
     * @return bool|mixed
     */
    protected function get_data( $key = '', $default = false )
    {
        if ( isset_not_empty( $this->data, $key ) ){
            return $this->data[ $key ];
        }

        return $default;
    }

    public function __construct()
    {
        // Setup the main complete function
        // Accepts no arguments, but requires that child implementations setup the data ahead of time.
        foreach ( $this->get_complete_hooks() as $hook => $args ){
            if ( is_array( $args ) ){
                add_action( $args[ 0 ], [ $this, 'setup' ], 98, $args[ 1 ] );
                add_action( $args[ 0 ], [ $this, 'complete' ], 99, 0 );
            } else {
                add_action( $hook, [ $this, 'setup' ], 98, $args );
                add_action( $hook, [ $this, 'complete' ], 99, 0 );
            }
        }

        parent::__construct();
    }

    /**
     * get the hook for which the benchmark will run
     *
     * @return int[]
     */
    abstract protected function get_complete_hooks();

    /**
     * Get the contact from the data set.
     *
     * @return Contact
     */
    abstract protected function get_the_contact();

    /**
     * Based on the current step and contact,
     *
     * @return bool
     */
    abstract protected function can_complete_step();

    /**
     * Start completing the thing....
     */
    public function complete()
    {
        $steps = $this->get_like_steps();

        foreach ( $steps as $step ) {

            $this->set_current_step( $step );

            $contact = $this->get_the_contact();

            if ( is_wp_error( $contact ) || ! $contact ){
                continue;
            }

            $this->set_current_contact( $this->get_the_contact() );

            if ( $this->can_complete_step() && $step->can_complete( $this->get_current_contact() ) ){

                $step->enqueue( $this->get_current_contact() );

            }

        }

    }

    /**
     * @return string
     */
    final public function get_group()
    {
        return self::BENCHMARK;
    }

    /**
     * Process the tag applied step...
     *
     * @param $contact Contact
     * @param $event Event
     *
     * @return true
     */
    public function run( $contact, $event )
    {
        //do nothing...

        return true;
    }

}
