<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 1/25/2019
 * Time: 12:08 PM
 */

class WPGH_Form_Iframe
{

    /**
     * @var WPGH_Form
     */
    private $form;

    /**
     * @var WPGH_Step
     */
    private $step;

    public function __construct()
    {

        if ( isset( $_GET[ 'ghFormIframe' ] ) ){
            add_action( 'template_redirect', array( $this, 'get_iframe_code' ) );
        }

        if ( isset( $_GET[ 'ghFormIframeJS' ] ) ){
            add_action( 'template_redirect', array( $this, 'get_iframe_js' ) );
        }

    }

    /**
     * Get the Iframe JS code.
     */
    public function get_iframe_js()
    {
        include WPGH_PLUGIN_DIR . 'templates/iframe.js.php';
        die();
    }

    /**
     * Gets the Iframe Form Code
     */
    public function get_iframe_code()
    {
        $form_id = intval( $_GET[ 'formId' ] );

        $this->form = new WPGH_Form( array(
            'id' => $form_id
        ) );

        $this->form->set_iframe_compat( true );

        $this->step = wpgh_get_funnel_step( $form_id );

        $this->add_actions();

        ob_start();

        include WPGH_PLUGIN_DIR . 'templates/iframe.php';

        $HTML = ob_get_clean();

        $this->remove_actions();

        echo $HTML;

        die();

    }

    public function iframe_title()
    {
        echo $this->step->title;
    }

    public function iframe_content()
    {
        echo $this->form;
    }

    public function add_actions()
    {

        add_action( 'wpgh_form_iframe_title', array( $this, 'iframe_title' ) );
        add_action( 'wpgh_form_iframe_content', array( $this, 'iframe_content' ) );

    }

    public function remove_actions()
    {
        remove_action( 'wpgh_form_iframe_title', array( $this, 'iframe_title' ) );
        remove_action( 'wpgh_form_iframe_content', array( $this, 'iframe_content' ) );
    }

}
