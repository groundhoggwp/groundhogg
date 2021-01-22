<?php
namespace Groundhogg\Admin\Guided_Setup\Steps;

use function Groundhogg\get_request_var;
use function Groundhogg\html;
use Groundhogg\HTML;
use Groundhogg\Plugin;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-02-27
 * Time: 11:03 AM
 */

class Compliance extends Step
{

    public function get_title()
    {
        return _x( 'Compliance Information', 'guided_setup', 'groundhogg' );
    }

    public function get_slug()
    {
        return 'compliance_info';
    }

    public function get_description()
    {
        return _x( 'These features help you stay within the letter of the law and make it easy for your subscribers to access critical information about your policies.', 'guided_setup', 'groundhogg' );
    }

    public function get_content()
    {

        html()->start_form_table();

        html()->add_form_control( [
            'label' => _x( 'Privacy Policy', 'setting_label', 'groundhogg' ),
            'type' => HTML::LINK_PICKER,
            'field' => [
                'value' => get_option( 'gh_privacy_policy' ),
                'name' => 'gh_privacy_policy'
            ],
            'description' => _x( 'This will be linked in the footer of your emails. It is important to let subscribers know how you plan to use their information.', 'guided_setup', 'groundhogg' )
        ] );

        html()->add_form_control( [
            'label' => _x( 'Terms & Conditions', 'setting_label', 'groundhogg' ),
            'type' => HTML::LINK_PICKER,
            'field' => [
                'value' => get_option( 'gh_terms' ),
                'name' => 'gh_terms'
            ],
            'description' => _x( 'Terms & conditions tell users about what they can expect from using your site.', 'guided_setup', 'groundhogg' )
        ] );

        html()->start_row();

        html()->th( _x( 'I send email to subscribers in...', 'setting_label', 'groundhogg' ) );
        html()->td( [
            html()->checkbox([
                'name' => 'in_europe',
                'label' => _x( 'Europe', 'guided_setup', 'groundhogg' ),
                'value' => 1,
                'checked' => Plugin::$instance->settings->is_option_enabled( 'gh_enable_gdpr' )
            ]),
            '<br/>',
            html()->checkbox([
                'name' => 'in_canada',
                'label' => _x( 'Canada', 'guided_setup', 'groundhogg' ),
                'value' => 1,
                'checked' => Plugin::$instance->settings->is_option_enabled( 'gh_strict_confirmation' )
            ]),
            '<br/>',
            html()->checkbox([
                'name' => 'other',
                'label' => _x( 'Other', 'guided_setup', 'groundhogg' ),
                'value' => 1,
                'checked' => true
            ]),
            html()->description( _x( 'If you send email in these areas special settings will be enabled to keep your business within the law.', 'guided_setup', 'groundhogg' ) )
        ] );

        html()->end_row();

        html()->end_form_table();
    }

    /**
     * @return bool
     */
    public function save()
    {
        Plugin::$instance->settings->update_option( 'gh_privacy_policy', sanitize_text_field( get_request_var( 'gh_privacy_policy' ) ) );
        Plugin::$instance->settings->update_option( 'gh_terms', sanitize_text_field( get_request_var( 'gh_terms' ) ) );

        if ( isset( $_POST[ 'in_canada' ]  ) ){
            Plugin::$instance->settings->update_option( 'gh_strict_confirmation', array( 'on' ) );
            Plugin::$instance->settings->update_option( 'gh_confirmation_grace_period', 14 );
        }

        if ( isset( $_POST[ 'in_europe' ]  ) ){
            Plugin::$instance->settings->update_option( 'gh_enable_gdpr', array( 'on' ) );
        }

        return true;
    }

}