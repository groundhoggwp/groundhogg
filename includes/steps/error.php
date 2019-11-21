<?php

namespace Groundhogg\Steps;

use function Groundhogg\html;
use function Groundhogg\key_to_words;
use Groundhogg\Step;

class Error extends Funnel_Step
{

    public function get_name()
    {
        return __('Error');
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

    /**
     * @param \Groundhogg\Contact $contact
     * @param \Groundhogg\Event $event
     * @return bool|\WP_Error
     */
    public function run($contact, $event)
    {
        return new \WP_Error( 'invalid_step_type', $this->get_error_message( $event->get_step() ) );
    }

    /**
     * @param $step Step
     * @return string
     */
    protected function get_error_message( $step )
    {
        return sprintf(
            __('<b>%s</b> settings were not found. This may be because you disabled an add-on which utilized this step type.', 'groundhogg' ),
            key_to_words($step->get_type())
        );

    }

    /**
     * @param Step $step
     */
    public function settings($step)
    {
        echo html()->e('p', [
            'class' => 'description'],
            $this->get_error_message( $step )
         );
    }

    /**
     * @param Step $step
     * @return bool
     */
    public function save($step)
    {
        $this->add_error(new \WP_Error('invalid_step_type', $this->get_error_message( $step ) ) );
        return false;
    }
}