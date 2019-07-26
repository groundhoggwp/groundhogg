<?php
namespace Groundhogg\Steps;

use function Groundhogg\html;
use function Groundhogg\key_to_words;
use Groundhogg\Step;

class Error extends Funnel_Step
{

    public function get_name()
    {
        return __( 'Error' );
    }

    public function get_type()
    {
        return 'error';
    }

    public function get_group()
    {
        return false;
    }

    public function get_description()
    {
        return '';
    }

    public function get_icon()
    {
        return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/no-icon.png';

    }

    public function settings( $step )
    {
        echo html()->e( 'p', [
            'style' => [ 'text-align' => 'center' ],
            'class' => 'description' ],
            sprintf(
                __( '<b>%s</b> settings were not found. This may be because you disabled an add-on which utilized this step type.' ),
                key_to_words( $step->get_type() )
            )
        );
    }

    public function save( $step )
    {
        return false;
    }
}