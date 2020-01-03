<?php
namespace Groundhogg\Form\Fields;

use function Groundhogg\html;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-09
 * Time: 4:25 PM
 */

class Terms extends Checkbox
{
    public function get_default_args()
    {
        return [
            'label'         => _x( 'I agree to the <i>terms of service</i>.', 'form_default', 'groundhogg' ),
            'name'          => 'agree_terms',
            'id'            => 'agree_terms',
            'class'         => 'gh-terms',
            'value'         => 'yes',
            'tag'           => 0,
            'title'         => _x( 'Please agree to the terms of service.', 'form_default', 'groundhogg' ),
            'required'      => true,
        ];
    }

    public function render()
    {
        $terms_link = get_option( 'gh_terms' );

        $label = $this->get_label();

        if ( $terms_link ){
            $terms_link = html()->e( 'a', [
                'href' => $terms_link,
                'class' => 'gh-terms-link',
                'target' => '_blank'
            ], __( 'view terms', 'groundhogg' ) );

            $label = sprintf( "%s (%s)", $label, $terms_link );
        }

        $atts = [
            'label' => $label,
            'name'  => $this->get_name(),
            'id'    => $this->get_id(),
            'class' => $this->get_classes() . ' gh-checkbox',
            'value' => $this->get_value(),
            'title' => $this->get_title(),
            'required' => $this->is_required(),
            'checked' => $this->is_checked()
        ];

        return html()->checkbox( $atts );
    }

    /**
     * Get the name of the shortcode
     *
     * @return string
     */
    public function get_shortcode_name()
    {
        return 'terms';
    }
}