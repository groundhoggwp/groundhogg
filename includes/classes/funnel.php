<?php
namespace Groundhogg;

use Groundhogg\DB\Funnels;
use Groundhogg\DB\Steps;

class Funnel extends Base_Object
{

    /**
     * Do any post setup actions.
     *
     * @return void
     */
    protected function post_setup()
    {
        // TODO: Implement post_setup() method.
    }

    /**
     * Return the DB instance that is associated with items of this type.
     *
     * @return Funnels
     */
    protected function get_db()
    {
        return Plugin::instance()->dbs->get_db('funnels');
    }

    /**
     * @return Steps
     */
    protected function get_steps_db()
    {
        return Plugin::instance()->dbs->get_db('steps');
    }

    /**
     * A string to represent the object type
     *
     * @return string
     */
    protected function get_object_type()
    {
        return 'funnel';
    }

    public function get_id()
    {
        return absint( $this->ID );
    }

    public function get_title()
    {
        return $this->title;
    }

    public function get_status()
    {
        return $this->status;
    }

    public function is_active()
    {
        return $this->get_status() === 'active';
    }

    /**
     * Get the step IDs associate with this funnel
     *
     * @param array $query
     * @return array
     */
    public function get_step_ids( $query = [] )
    {
        $query = array_merge( $query, [ 'funnel_id' => $this->get_id() ] );
        return wp_parse_id_list( wp_list_pluck( $this->get_steps_db()->get_steps( $query ), 'ID' ) );
    }

    /**
     * Get a bunch of steps
     *
     * @param array $query
     * @return Step[]
     */
    public function get_steps( $query = [] )
    {
        $raw_step_ids = $this->get_step_ids( $query );

        $steps = [];

        foreach ( $raw_step_ids as $raw_step_id ){
            $steps[] = Plugin::$instance->utils->get_step( $raw_step_id );
        }

        return $steps;
    }

    /**
     * Get the funnel as an array.
     *
     * @return array|bool
     */
    public function get_as_array()
    {
        $export = [];
        $export[ 'title' ] = $this->get_title();
        $export[ 'steps' ] = [];

        $steps = $this->get_steps();

        if ( ! $steps )
            return false;

        foreach ( $steps as $i => $step )
        {

            $export['steps'][$i] = [];
            $export['steps'][$i]['title'] = $step->get_title();
            $export['steps'][$i]['group'] = $step->get_group();
            $export['steps'][$i]['order'] = $step->get_order();
            $export['steps'][$i]['type']  = $step->get_type();
            $export['steps'][$i]['meta']  = $step->get_meta();
            $export['steps'][$i]['args']  = apply_filters( "groundhogg/elements/{$step->get_type()}/export" , array(), $step );

            /* allow other plugins to modify */
            $export['steps'][$i] = apply_filters( 'groundhogg/elements/step/export', $export['steps'][$i], $step );
        }

        return $export;
    }

    /**
     * @return false|string
     */
    public function get_as_json()
    {
        return wp_json_encode( $this->get_as_array() );
    }
}