<?php

/* Groundhogg Settings Page */
class WPFN_Settings_Page
{
	public function __construct()
    {
		//add_action( 'admin_menu', array( $this, 'wpfn_create_settings' ) );
		add_action( 'admin_init', array( $this, 'wpfn_setup_sections' ) );
		add_action( 'admin_init', array( $this, 'wpfn_setup_fields' ) );
	}

	public function wpfn_settings_content()
    {
        wp_enqueue_style( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css' );
        wp_enqueue_script( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js' );

        ?>
		<div class="wrap">
			<h1>Groundhogg <?php _e( 'Settings' ); ?></h1>
			<?php settings_errors(); ?>
            <?php if ( isset( $_GET[ 'token' ] ) ) :
                ?><div class="notice notice-success is-dismissible"><p><strong><?php _e( 'Connected to Groundhogg!', 'groundhogg' ); ?></strong></p></div><?php
            endif; ?>
			<form method="POST" action="options.php">
                <?php $active_tab = isset( $_GET[ 'tab' ] ) ?  $_GET[ 'tab' ] : 'general'; ?>
                <h2 class="nav-tab-wrapper">
                    <a href="?page=groundhogg&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>"><?php _e( 'General', 'groundhogg'); ?></a>
                    <a href="?page=groundhogg&tab=marketing" class="nav-tab <?php echo $active_tab == 'marketing' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Compliance', 'groundhogg'); ?></a>
                    <a href="?page=groundhogg&tab=emails" class="nav-tab <?php echo $active_tab == 'emails' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Email', 'groundhogg'); ?></a>
                    <a href="?page=groundhogg&tab=tools" class="nav-tab <?php echo $active_tab == 'tools' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Tools', 'groundhogg'); ?></a>
                    <a href="?page=groundhogg&tab=extensions" class="nav-tab <?php echo $active_tab == 'extensions' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Extensions', 'groundhogg'); ?></a>
                </h2>
                <?php switch ( $active_tab ):
                    case 'general':
                        settings_fields( 'groundhogg_business_settings' );
                        do_settings_sections( 'groundhogg_business_settings' );
                        submit_button();

                        break;
                    case 'marketing':
                        settings_fields( 'groundhogg_marketing_settings' );
                        do_settings_sections( 'groundhogg_marketing_settings' );
                        submit_button();

                        break;
                    case 'emails':

                        GH_Account::$instance->connect_button();

                        settings_fields( 'groundhogg_email_settings' );
                        do_settings_sections( 'groundhogg_email_settings' );
                        submit_button();

                        break;
                    case 'tools':
                        ?>
                        <div id="poststuff">
                            <div class="postbox">
                                <h2 class="hndle"><?php _e( 'Import Contacts', 'groundhogg' ); ?></h2>
                                <div class="inside">
                                    <p>
                                        <input type="file" accept=".csv" >
                                    </p>
                                    <?php $tag_args = array();
                                    $tag_args[ 'id' ] = 'superlink_tags';
                                    $tag_args[ 'name' ] = 'superlink_tags[]';
                                    $tag_args[ 'width' ] = '100%';
                                    $tag_args[ 'class' ] = 'hidden'; ?>
                                    <?php wpfn_dropdown_tags( $tag_args ); ?>
                                    <p class="description"><?php _e( 'Select tags to apply tags to this import', 'groundhogg' ); ?></p>
                                    <?php submit_button( 'Import' ); ?>
                                </div>
                            </div>
                        </div>

                        <?php


                        break;
                    case 'extensions':

                        GH_Account::$instance->connect_button();

                        settings_fields( 'groundhogg_extensions_settings' );
                        do_settings_sections( 'groundhogg_extensions_settings' );
                        submit_button();

                        break;

                    default:

                        do_action( 'grounhogg_' . $active_tab . '_settings'  );
                        submit_button();

                        break;

                    endswitch;
                    ?>
			</form>
		</div> <?php
	}

	public function wpfn_setup_sections()
    {
        /* general */
        add_settings_section('business_info', 'Edit Business Settings', array(), 'groundhogg_business_settings');

        /* marketing */
        add_settings_section('contact_endpoints', __ ( 'Contact Endpoints' , 'grounhogg' ), array(), 'groundhogg_marketing_settings');
//        add_settings_section('confirmation_page', 'Confirmation Page', array(), 'groundhogg_marketing_settings');
//        add_settings_section('email_preferences_page', 'Email Preferences Page', array(), 'groundhogg_marketing_settings');
        add_settings_section('compliance', __( 'Compliance Settings', 'groundhogg' ), array(), 'groundhogg_marketing_settings');


        add_settings_section( 'default_mail_settings', 'Default Mail Settings', array(), 'groundhogg_email_settings' );
    }

	public function wpfn_setup_fields()
    {
		$fields = array(
			array(
				'label' => 'Business Name',
				'id' => 'gh_business_name',
				'type' => 'text',
                'placeholder' => 'My Awesome Company',
                'desc' => 'As it should appear in your email footer.',
                'section' => 'business_info',
                'page' => 'groundhogg_business_settings'
			),
			array(
				'label' => 'Street Address 1',
				'id' => 'gh_street_address_1',
				'type' => 'text',
				'placeholder' => '123 Awesome St',
                'desc' => 'As it should appear in your email footer.',
                'section' => 'business_info',
                'page' => 'groundhogg_business_settings'
			),
			array(
				'label' => 'Street Address 2',
				'id' => 'gh_street_address_2',
				'type' => 'text',
                'placeholder' => 'Unit 0',
                'desc' => '(Optional) As it should appear in your email footer.',
                'section' => 'business_info',
                'page' => 'groundhogg_business_settings'
			),
            array(
                'label' => 'City',
                'id' => 'gh_city',
                'type' => 'text',
                'placeholder' => 'Nowhere',
                'desc' => 'As it should appear in your email footer.',
                'section' => 'business_info',
                'page' => 'groundhogg_business_settings'
            ),
			array(
				'label' => 'Postal/Zip Code',
				'id' => 'gh_zip_or_postal',
				'type' => 'text',
				'placeholder' => 'A1A 1A1',
                'desc' => 'As it should appear in your email footer.',
                'section' => 'business_info',
                'page' => 'groundhogg_business_settings'
			),
			array(
				'label' => 'State/Province',
				'id' => 'gh_region',
				'type' => 'text',
				'placeholder' => 'Somewhere',
                'desc' => 'As it should appear in your email footer.',
                'section' => 'business_info',
                'page' => 'groundhogg_business_settings'
			),
			array(
				'label' => 'Country',
				'id' => 'gh_country',
				'type' => 'text',
				'placeholder' => 'Canada',
                'desc' => 'As it should appear in your email footer.',
                'section' => 'business_info',
                'page' => 'groundhogg_business_settings'
            ),
            array(
                'label' => 'Phone',
                'id' => 'gh_phone',
                'type' => 'tel',
                'placeholder' => '555-555-5555',
                'section' => 'business_info',
                'page' => 'groundhogg_business_settings'
            ),
            array(
                'label' => 'Phone',
                'id' => 'gh_phone',
                'type' => 'tel',
                'placeholder' => '555-555-5555',
                'desc' => 'As it should appear in your email footer.',
                'section' => 'business_info',
                'page' => 'groundhogg_business_settings'
            ),
            array(
                'label' => 'Email Confirmation Page',
                'id' => 'gh_email_confirmation_page',
                'type' => 'page',
                'desc' => 'Page contacts see when they confirm their email.',
                'section' => 'contact_endpoints',
                'page' => 'groundhogg_marketing_settings'
            ),
//            array(
//                'label' => 'Email Confirmation Text',
//                'id' => 'gh_email_confirmation_text',
//                'type' => 'textarea',
//                'placeholder' => 'Thank you for confirming your email.',
//                'desc' => 'What the contact sees when they confirm their email address',
//                'section' => 'confirmation_page',
//                'page' => 'groundhogg_marketing_settings'
//            ),
            array(
                'label' => 'Unsubscribe Page',
                'id' => 'gh_unsubscribe_page',
                'type' => 'page',
                'desc' => 'Page contacts see when they unsubscribe.',
                'section' => 'contact_endpoints',
                'page' => 'groundhogg_marketing_settings'
            ),
//            array(
//                'label' => 'Unsubscribe Text',
//                'id' => 'gh_unsubscribe_text',
//                'type' => 'textarea',
//                'placeholder' => 'We\'re sorry to see you go. We hope you come back soon!',
//                'desc' => 'What the contact sees when they unsubscribe.',
//                'section' => 'unsubscribe_page',
//                'page' => 'groundhogg_marketing_settings'
//            ),
            array(
                'label' => 'Email Preferences Page',
                'id' => 'gh_email_preferences_page',
                'type' => 'page',
                'desc' => 'Page where contacts can manage their email preferences.',
                'section' => 'contact_endpoints',
                'page' => 'groundhogg_marketing_settings'
            ),
//            array(
//                'label' => 'Email Preferences Text',
//                'id' => 'gh_email_preferences_text',
//                'type' => 'textarea',
//                'placeholder' => 'Manage your email preferences below.',
//                'desc' => 'What the contact sees before the email preferences form.',
//                'section' => 'email_preferences_page',
//                'page' => 'groundhogg_marketing_settings'
//            ),
            array(
                'label' => 'Privacy Policy',
                'id' => 'gh_privacy_policy',
                'type' => 'page',
                'desc' => 'Link to your privacy policy.',
                'section' => 'compliance',
                'page' => 'groundhogg_marketing_settings'
            ),
            array(
                'label' => 'Terms & Conditions (Terms of Service)',
                'id' => 'gh_terms',
                'type' => 'page',
                'desc' => 'Link to your terms & conditions.',
                'section' => 'compliance',
                'page' => 'groundhogg_marketing_settings'
            ),
            array(
                'label' => 'Enable GDPR features.',
                'id' => 'gh_enable_gdpr',
                'type' => 'radio',
                'desc' => 'This will add a consent box to your forms as well as a "Delete Everything" Button to your email preferences page.',
                'section' => 'compliance',
                'page' => 'groundhogg_marketing_settings',
                'options' => array(
                    'on' => 'On',
                    'off' => 'Off',
                ),
            ),
            array(
                'label' => 'Send mail with default SMTP provider or Groundhogg Mail',
                'id' => 'gh_mail_server',
                'type' => 'radio',
                'desc' => 'You may choose to send mail using your default provider (your own server) or you can use Groundhogg to send mail. 
                Groundhogg Mail is an inexpensive and monitored mail service designed to get your email to the inbox.',
                'section' => 'default_mail_settings',
                'page' => 'groundhogg_email_settings',
                'options' => array(
                    'groundhogg' => 'Groundhogg Mail',
                    'default' => 'Default Mail Service',
                ),
            ),

		);
		foreach( $fields as $field ){
			add_settings_field( $field['id'], $field['label'], array( $this, 'wpfn_field_callback' ), $field['page'] , $field['section'], $field );
			register_setting( $field['page'], $field['id'] );
		}
	}

	public function wpfn_field_callback( $field )
    {
		$value = get_option( $field['id'] );
		switch ( $field['type'] ) {
            case 'radio':
            case 'checkbox':
                if( ! empty ( $field['options'] ) && is_array( $field['options'] ) ) {
                    $options_markup = '';
                    $iterator = 0;

                    if ( ! is_array( $value ) ){
                        $value = array( $value );
                    }

                    foreach( $field['options'] as $key => $label ) {
                        $iterator++;
                        $options_markup.= sprintf('<label for="%1$s_%6$s"><input id="%1$s_%6$s" name="%1$s[]" type="%2$s" value="%3$s" %4$s /> %5$s</label><br/>',
                            $field['id'],
                            $field['type'],
                            $key,
                            checked( $value[array_search($key, $value, true)], $key, false ),
                            $label,
                            $iterator
                        );
                    }
                    printf( '<fieldset>%s</fieldset>',
                        $options_markup
                    );
                }
                break;
            case 'textarea':
                printf( '<textarea name="%1$s" id="%1$s" placeholder="%2$s" rows="5" cols="50">%3$s</textarea>',
                    $field['id'],
                    $field['placeholder'],
                    $value
                );
                break;
            case 'wysiwyg':
                wp_editor($value, $field['id']);
                break;
            case 'page':
                if ( $value ){ $args['selected'] = $value; }
                $args['name'] = $field['id'] ;
                $args['id'] = $field['id'];

                wp_dropdown_pages( $args );
                printf( '<script>jQuery(function($){$( "#%1$s" ).width(200);$( "#%1$s" ).select2()});</script>',
                    $field['id']
                );

                break;
			default:
				printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />',
					$field['id'],
					$field['type'],
					$field['placeholder'],
					$value
				);
		}
		if( isset( $field['desc'] ) && $desc = $field['desc'] ) {
			printf( '<p class="description">%s </p>', $desc );
		}
	}
}