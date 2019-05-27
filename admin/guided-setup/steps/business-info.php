<?php
namespace Groundhogg\Admin\Guided_Setup\Steps;

use function Groundhogg\html;
use Groundhogg\HTML;

class Business_Info extends Step
{

    public function get_title()
    {
        return _x( 'Business Information', 'guided_setup', 'groundhogg' );
    }

    public function get_slug()
    {
        return 'business_info';
    }

    public function get_description()
    {
        return _x( 'Your business information is needed so your list knows who is sending them emails. All this information will appear in your email footer to comply with CAN-SPAM legislation.', 'guided_setup', 'groundhogg' );
    }

    public function get_content()
    {
        html()->start_form_table();

        $fields = [
            [
                'name' => __( 'Business Name', 'groundhogg' ),
                'option' => 'gh_business_name'
            ],
            [
                'name' => __( 'Phone Number', 'groundhogg' ),
                'option' => 'gh_phone',
            ],
            [
                'name' => __( 'Street Address 1', 'groundhogg' ),
                'option' => 'gh_street_address_1'
            ],
            [
                'name' => __( 'Street Address 2', 'groundhogg' ),
                'option' => 'gh_street_address_2'
            ],
            [
                'name' => __( 'City', 'groundhogg' ),
                'option' => 'gh_city'
            ],
            [
                'name' => __( 'Postal/Zip code', 'groundhogg' ),
                'option' => 'gh_zip_or_postal'
            ],
            [
                'name' => __( 'State/Province/Region', 'groundhogg' ),
                'option' => 'gh_region'
            ],
            [
                'name' => __( 'Country', 'groundhogg' ),
                'option' => 'gh_country'
            ],
        ];

        foreach ( $fields as $field ){

            html()->add_form_control( [
                'label' => $field[ 'name' ],
                'type' => HTML::INPUT,
                'field' => [
                    'value' => get_option( $field[ 'option' ] )
                ],
            ] );

        }

        html()->end_form_table();
    }

    /**
     * Oh boy
     *
     * @return bool
     */
    public function save()
    {
        $settings = [
            'gh_business_name',
            'gh_phone',
            'gh_street_address_1',
            'gh_street_address_2',
            'gh_city',
            'gh_zip_or_postal',
            'gh_region',
            'gh_country',
        ];

        foreach ( $settings as $setting ){
            if ( isset( $_POST[ $setting ] ) && ! empty( $_POST[ $setting ] ) ){
                update_option( $setting, sanitize_text_field( $_POST[ $setting ] ) );
            }
        }

        return true;
    }

}