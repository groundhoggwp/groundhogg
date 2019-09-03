<?php

namespace Groundhogg\Steps\Benchmarks;

use Groundhogg\Contact;
use Groundhogg\Step;
use function Groundhogg\get_contactdata;
use function Groundhogg\html;

class Plugin_Api extends Benchmark
{


    /**
     * get the hook for which the benchmark will run
     *
     * @return int[]
     */
    protected function get_complete_hooks()
    {
        return [ 'groundhogg/steps/benchmarks/api' => 3 ];
    }

    public function setup( $call_name='', $id_or_email='', $by_user_id=false )
    {
        $this->add_data( 'call_name', $call_name );
        $this->add_data( 'id_or_email', $id_or_email );
        $this->add_data( 'by_user_id', $by_user_id );
    }

    /**
     * Get the contact from the data set.
     *
     * @return Contact
     */
    protected function get_the_contact()
    {
        return get_contactdata( $this->get_data( 'id_or_email' ), $this->get_data( 'by_user_id' ) );
    }

    /**
     * Based on the current step and contact,
     *
     * @return bool
     */
    protected function can_complete_step()
    {
        return $this->get_data( 'call_name' ) === $this->get_setting( 'call_name' );
    }

    /**
     * Get the element name
     *
     * @return string
     */
    public function get_name()
    {
        return __( 'Plugin API', 'groundhogg' );
    }

    /**
     * Get the element type
     *
     * @return string
     */
    public function get_type()
    {
        return 'plugin_api';
    }

    /**
     * Get the description
     *
     * @return string
     */
    public function get_description()
    {
        return __( 'Developer friendly benchmark.' );
    }

    /**
     * Get the icon URL
     *
     * @return string
     */
    public function get_icon()
    {
        return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/plugin-api.png';
    }

    /**
     * Display the settings based on the given ID
     *
     * @param $step Step
     */
    public function settings($step)
    {
        html()->start_form_table();

        html()->start_row();

        html()->th( __( 'Call Name', 'groundhogg' ) );
        html()->td( [
            html()->input( [
                'id' => $this->setting_id_prefix( 'call_name' ),
                'name' => $this->setting_name_prefix( 'call_name' ),
                'value' => $this->get_setting( 'call_name' ),
            ] ),
            html()->description( __( 'The call name for the API trigger.', 'groundhogg' ) )
        ] );

        html()->end_row();

        html()->start_row();

        html()->th( __( 'Usage:', 'groundhogg' ) );
        html()->td( [
            html()->textarea( [
                'class' => 'code',
                'value' => "<?php \n\nadd_action( 'some_hook', function( \$args ){ \n\n    // todo get the contact ID or email from any passed arguments\n\n    \Groundhogg\do_api_benchmark( '{$this->get_setting( 'call_name' )}', \$contact_id, false );\n\n});\n\n?>",
                'style' => [ 'width' => '100%' ],
                'cols' => '',
                'wrap' => 'off',
                'readonly' => true,
                'onfocus' => "this.select()"
            ] ),
            html()->description( __( 'Copy and paste the above code into a custom plugin or your theme\'s functions.php file.', 'groundhogg' ) )
        ] );


        html()->end_row();

        html()->end_form_table();
    }

    /**
     * Save the step based on the given ID
     *
     * @param $step Step
     */
    public function save($step)
    {
        $this->save_setting( 'call_name', sanitize_key( $this->get_posted_data( 'call_name' ) ) );
    }
}