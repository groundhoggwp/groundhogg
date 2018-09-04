<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-08-20
 * Time: 12:32 PM
 */

interface WPFN_Funnel_Step_Interface
{
	function __construct( $name, $type, $icon );
	function settings( $id );
	function save( $id );
	function run( $id );
}

class WPFN_Funnel_Step implements WPFN_Funnel_Step_Interface
{

	/**
	 * @var $type string the type of action
	 */
    var $type;

	/**
	 * @var $name string the Display name of the action
	 */
    var $name;

	/**
	 * @var $icon string url to icon.
	 */
    var $icon;

    function __construct( $name, $type, $icon )
    {

    	$this->type = $type;
    	$this->name = $name;
    	$this->icon = $icon;

//    	add_filter( 'wpfn_funnel_actions', array( $this, 'register' ) );

	    add_action( 'wpfn_get_step_settings_' . $this->type, array( $this, 'settings' ) );
    	add_action( 'wpfn_save_step_' . $this->type, array( $this, 'save' ) );
    }

    function register( $array )
    {
		$array[ $this->type ] = array(
			'title' => $this->name,
			'icon'  => $this->icon
		);

		return $array;
    }

    function settings( $id )
    {
        _e( 'Settings not defined by child class...' );
    }

    function save( $id )
    {
        //do nothing
    }

    function run( $id )
    {
    	//do nothing
    }
}

class WPFN_Funnel_Action extends WPFN_Funnel_Step
{
	function __construct( $name, $type, $icon ) {

		parent::__construct( $name, $type, $icon );

		add_filter( 'wpfn_funnel_actions', array( $this, 'register' ) );
		add_action( 'wpfn_do_action_' . $this->type, array( $this, 'run' ) );

	}

}

class WPFN_Funnel_Benchmark extends WPFN_Funnel_Step
{
	function __construct( $name, $type, $icon ) {
		parent::__construct( $name, $type, $icon );

		add_filter( 'wpfn_funnel_benchmarks', array( $this, 'register' ) );
		add_action( 'wpfn_do_action_' . $this->type, array( $this, 'run' ) );
	}
}