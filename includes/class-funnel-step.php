?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-08-20
 * Time: 12:32 PM
 */

class WPFN_Funnel_Step
{

    var $type;

    var $name;

    var $group;

    var $settings_callback;

    var $action_callback;

    var $save_callback;

    var $icon;

    function __construct(
        $name,
        $group,
        $type,
        $settings_callback,
        $save_callback,
        $action_callback,
        $icon)
    {

        if ( $this->group === 'benchmarks' ){
            add_filter( 'wpfn_funnel_benchmarks', array( $this, 'register_type' ) );
        } else {
            add_filter( 'wpfn_funnel_actions', array( $this, 'register_type' ) );
        }

        add_filter( 'wpfn_builder_icons', array( $this, 'register_icon') );

    }

    /**
     * Add the step type to the actions/benchmarks array
     *
     * @param $array array Array of funnel actions/benchmarks
     * @return array
     */
    function register_type( $array )
    {
        $array[] = $this->type;

        return $array;
    }

    function register_icon( $array )
    {
        $array[ $this->type ] = $this->icon;
    }

    function register_callbacks()
    {
        if ( $this->group === 'benchmarks' ){



        } else {

        }
    }


}