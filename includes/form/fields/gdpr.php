<?php
namespace Groundhogg\Form\Fields;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-09
 * Time: 4:25 PM
 */

class GDPR extends Checkbox
{
    public function get_default_args()
    {
        return [
            'label'         => _x( 'I consent to having my personal information collected, and to receive marketing and transactional information related to my request.', 'form_default', 'groundhogg' ),
            'name'          => 'gdpr_consent',
            'id'            => 'gdpr_consent',
            'class'         => 'gh-gdpr',
            'value'         => 'yes',
            'tag'           => 0,
            'title'         => _x( 'I Consent', 'form_default', 'groundhogg' ),
            'required'      => true,
        ];
    }

    /**
     * Get the name of the shortcode
     *
     * @return string
     */
    public function get_shortcode_name()
    {
        return 'gdpr';
    }
}