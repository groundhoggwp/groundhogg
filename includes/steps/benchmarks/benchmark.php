<?php

namespace Groundhogg\Steps\Benchmarks;

use Groundhogg\Steps\Funnel_Step;

if ( ! defined( 'ABSPATH' ) ) exit;

abstract class Benchmark extends Funnel_Step
{
    public function __construct()
    {
        $this->add_complete_action();
        parent::__construct();
    }

    /**
     * Here you must define the action to listen for.
     *
     * For example, add_action( 'action_to_listen_for', [ $this, 'complete' ], 10, 2 );
     *
     * @return void
     */
    abstract protected function add_complete_action();

    abstract protected function condition( $step, $contact, $args );

    /**
     * @return string
     */
    final public function get_group()
    {
        return self::BENCHMARK;
    }

}
